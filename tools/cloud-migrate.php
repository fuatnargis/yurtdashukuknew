<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
define('ROOT',dirname(__DIR__));
require ROOT.'/app/runtime.php';
require ROOT.'/app/svg-media.php';
define('DATA_DIR',getenv('HUKUK_DATA_DIR')?:ROOT.'/storage');
$options=getopt('',['check','include-messages']);
try {
    if(!is_file(DATA_DIR.'/site.sqlite'))throw new RuntimeException('Yerel site veritabanı bulunamadı.');
    $source=new PDO('sqlite:'.DATA_DIR.'/site.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $source->exec('PRAGMA query_only=ON');
    $tables=['settings','users','entries','revisions','media','audit'];if(isset($options['include-messages']))$tables[]='messages';
    $available=$source->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    foreach(['entry_redirects','content_backups','url_redirects','seo_urls'] as $table)if(in_array($table,$available,true))$tables[]=$table;
    $rows=[];$source->beginTransaction();foreach($tables as $table){$rows[$table]=$source->query('SELECT * FROM '.$table)->fetchAll();echo $table.': '.count($rows[$table])." kayıt\n";}$source->commit();
    // Uploaded images may be referenced by an imported content backup without a media row.
    $files=[];foreach(glob(ROOT.'/assets/uploads/*') as $file){
        if(!is_file($file)||!preg_match('/^[a-f0-9]{32}\.(webp|png|jpg|jpeg|svg)$/',basename($file)))continue;
        if(filesize($file)>3*1024*1024)throw new RuntimeException('Bir görsel 3 MB sınırını aşıyor; taşıma öncesi küçültün.');
        $bytes=file_get_contents($file);
        if(pathinfo($file,PATHINFO_EXTENSION)==='svg'){
            $bytes=sanitize_svg($bytes);$mime='image/svg+xml';
        }else{
            $info=getimagesize($file);if(!$info||!in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP],true))throw new RuntimeException('Yüklenen görseller arasında geçersiz dosya var.');
            $mime=image_type_to_mime_type($info[2]);
        }
        $files[]=['/assets/uploads/'.basename($file),$mime,base64_encode($bytes),strlen($bytes)];
    }
    echo count($files)." kalıcı görsel aktarılacak.\n";
    if(isset($options['check'])){echo "Salt okunur kontrol tamamlandı; hiçbir veri gönderilmedi.\n";exit;}
    if(!cloud_database())throw new RuntimeException('HUKUK_DATABASE_URL ayarlanmamış.');
    $target=database_connection();$target->beginTransaction();$target->exec('SELECT pg_advisory_xact_lock(713520140)');initialize_database($target);
    migrate_entry_presentation($target);
    foreach(array_unique(array_merge($tables,['messages','media_files','content_backups','entry_redirects','url_redirects','seo_urls'])) as $table)if((int)$target->query('SELECT COUNT(*) FROM '.$table)->fetchColumn())throw new RuntimeException('Hedef veritabanı boş değil. Mevcut canlı verilerin üzerine yazılmadı.');
    foreach($rows as $table=>$data){
        foreach($data as $row){$cols=array_keys($row);foreach($cols as $col)if(!preg_match('/^[a-z_]+$/',$col))throw new RuntimeException('Beklenmeyen sütun.');
            $query=$target->prepare('INSERT INTO '.$table.'('.implode(',',$cols).') VALUES('.implode(',',array_fill(0,count($cols),'?')).')');$query->execute(array_values($row));}
        if(!in_array($table,['settings','entry_redirects','seo_urls'],true))$target->exec("SELECT setval(pg_get_serial_sequence('hukuk.$table','id'),COALESCE((SELECT MAX(id) FROM $table),0)+1,false)");
    }
    $insert=$target->prepare('INSERT INTO media_files(path,content_type,data,size) VALUES(?,?,?,?)');foreach($files as $file)$insert->execute($file);
    // Never index sample data automatically. Production visibility is an admin choice.
    $target->exec("UPDATE settings SET value='0' WHERE key='indexing'");
    $target->commit();echo "Veriler boş PostgreSQL veritabanına aktarıldı. Yönetici hesabınız korunuyor.\n";
}catch(Throwable $e){if(isset($target)&&$target->inTransaction())$target->rollBack();fwrite(STDERR,($e instanceof PDOException?'Veritabanı işlemi başarısız. Bağlantı, TLS ve sunucu izinlerini kontrol edin.':$e->getMessage())."\n");exit(1);}
