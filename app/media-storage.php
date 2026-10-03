<?php
declare(strict_types=1);
function media_exists(string $path): bool {
    if(safe_image($path)!==$path)return false;
    if(is_file(ROOT.$path))return true;
    if(!cloud_database())return false;
    $q=db()->prepare('SELECT 1 FROM media_files WHERE path=?');$q->execute([$path]);return (bool)$q->fetchColumn();
}
function store_media(string $path,string $bytes,string $mime): void {
    if(!preg_match('~^/assets/uploads/[a-f0-9]{32}\.(webp|png|jpg|jpeg|svg)$~',$path))throw new RuntimeException('Görsel yolu geçersiz.');
    if(cloud_database()){
        db()->prepare('INSERT INTO media_files(path,content_type,data,size) VALUES(?,?,?,?)')->execute([$path,$mime,base64_encode($bytes),strlen($bytes)]);
    }else{
        $dir=ROOT.'/assets/uploads';if(!is_dir($dir))mkdir($dir,0755,true);
        if(file_put_contents(ROOT.$path,$bytes,LOCK_EX)===false)throw new RuntimeException('Görsel kaydedilemedi.');
    }
}
function serve_cloud_media(string $path): never {
    $q=db()->prepare('SELECT content_type,data,size FROM media_files WHERE path=?');$q->execute([$path]);$file=$q->fetch();
    if(!$file){http_response_code(404);exit;}
    if(!in_array($file['content_type'],['image/jpeg','image/png','image/webp','image/svg+xml'],true)){http_response_code(404);exit;}
    $bytes=base64_decode($file['data'],true);if($bytes===false){http_response_code(500);exit;}
    header_remove('Expires');header_remove('Pragma');header('Content-Type: '.$file['content_type']);header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=31536000, immutable');header('Content-Length: '.strlen($bytes));
    if($file['content_type']==='image/svg+xml')header("Content-Security-Policy: default-src 'none'; style-src 'none'; sandbox");
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD')echo $bytes;exit;
}
function persist_content_backup(array $data): void {
    $name='before-import-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';$json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    if(cloud_database())db()->prepare('INSERT INTO content_backups(name,data,created_at) VALUES(?,?,?)')->execute([$name,$json,date('Y-m-d H:i:s')]);
    elseif(file_put_contents(DATA_DIR.'/'.$name,$json,LOCK_EX)===false)throw new RuntimeException('İşlem öncesi yedek alınamadı.');
}
