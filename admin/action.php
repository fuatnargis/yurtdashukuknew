<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/admin-fields.php';
header('Cache-Control: no-store');header('X-Robots-Tag: noindex, nofollow');
$user=require_user();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit('Bu işlem POST gerektirir.');}
verify_csrf();$action=input('action');$back='/admin/';
function revision(array $en): void { db()->prepare('INSERT INTO revisions(entry_id,snapshot,created_at) VALUES(?,?,?)')->execute([$en['id'],json_encode($en,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),date('Y-m-d H:i:s')]); }
function backup_data(): array {
    $settings=db()->query("SELECT key,value FROM settings WHERE key NOT LIKE 'private_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
    return ['format'=>'mizan-cms','version'=>1,'exported_at'=>date(DATE_ATOM),'settings'=>$settings,'redirects'=>db()->query('SELECT r.type,r.slug,e.slug AS target_slug FROM entry_redirects r JOIN entries e ON e.id=r.entry_id')->fetchAll(),'url_redirects'=>db()->query('SELECT source_path,target_path,status_code,enabled FROM url_redirects ORDER BY source_path')->fetchAll(),'seo_urls'=>db()->query('SELECT path,sitemap_enabled,noindex,lastmod FROM seo_urls ORDER BY path')->fetchAll(),'entries'=>db()->query('SELECT * FROM entries ORDER BY id')->fetchAll()];
}
try {
    require ROOT.'/app/admin-seo-actions.php';
    require ROOT.'/app/admin-content-actions.php';
    if($action==='logout'){audit('Oturum kapatıldı');$_SESSION=[];session_destroy();setcookie('hukuk_session','',['expires'=>time()-3600,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);redirect('/admin/login.php');}
    if($action==='save-settings'){
        $group=input('group');$schema=settings_fields()[$group]??null;if(!$schema)throw new RuntimeException('Ayar grubu bulunamadı.');$back='/admin/?view=settings&group='.urlencode($group);
        $values=[];foreach($schema['fields'] as $key=>[$label,$kind]){$val=input($key,$kind==='checkbox'?'0':'');if(!valid_setting($key,$val,$kind))throw new RuntimeException($label.' alanındaki değeri kontrol edin.');$values[$key]=$val;}
        db()->beginTransaction();
        if($group==='privacy'){
            $notice=entry('page','kvkk');
            if(($values['privacy_notice_reviewed']??'0')==='1'&&(!$notice||preg_match('/taslak|tamamlanması gereken/iu',$notice['body'])))throw new RuntimeException('Önce KVKK sayfasındaki taslak metni büronuzun gerçek bilgileriyle tamamlayın.');
            save_setting('private_privacy_notice_hash',($values['privacy_notice_reviewed']??'0')==='1'?hash('sha256',$notice['body']):'');
        }
        foreach($values as $k=>$v)save_setting($k,$v);audit($schema['title'].' ayarları güncellendi');db()->commit();flash('Ayarlar kaydedildi. Değişiklikler sitenize yansıdı.');redirect($back);
    }
    if(in_array($action,['save-entry','archive-entry','restore-entry','restore-revision'],true)){
        $id=(int)input('id');$type=input('type');if(!isset(CONTENT_TYPES[$type]))throw new RuntimeException('İçerik türü bulunamadı.');
        $back='/admin/?view=edit&type='.$type.($id?'&id='.$id:'');
        db()->beginTransaction();$existing=null;
        if($id){$q=db()->prepare('SELECT * FROM entries WHERE id=? AND type=?');$q->execute([$id,$type]);$existing=$q->fetch();if(!$existing)throw new RuntimeException('İçerik bulunamadı.');}
        if(in_array($action,['archive-entry','restore-entry'],true)){
            if(!$existing)throw new RuntimeException('İçerik bulunamadı.');
            if(input('original_updated_at')&&input('original_updated_at')!==$existing['updated_at'])throw new RuntimeException('Bu içerik değiştirildi. Listeyi yenileyin.');
            if($type==='page'&&in_array($existing['slug'],['kurumsal','kvkk','cerez-politikasi','yasal-bilgilendirme'],true))throw new RuntimeException('Temel sayfalar arşivlenemez. İçeriğini düzenleyebilirsiniz.');
            revision($existing);db()->prepare('UPDATE entries SET status=?,updated_at=? WHERE id=?')->execute([$action==='archive-entry'?'archived':'draft',date('Y-m-d H:i:s.u'),$id]);assert_redirect_graph(db()->query('SELECT * FROM url_redirects')->fetchAll());audit($existing['title'].($action==='archive-entry'?' arşivlendi':' taslaklara geri alındı'));db()->commit();flash('İçerik durumu güncellendi.');redirect('/admin/?view=content&type='.$type);
        }
        if($action==='restore-revision'){
            $q=db()->prepare('SELECT snapshot FROM revisions WHERE id=? AND entry_id=?');$q->execute([(int)input('revision_id'),$id]);$saved=$q->fetchColumn();if(!$saved||!$existing)throw new RuntimeException('İçerik sürümü bulunamadı.');
            $data=json_decode($saved,true,512,JSON_THROW_ON_ERROR);revision($existing);$columns=['profile_details','title','slug','excerpt','body','body_format','image','category','author','status','published_at','sort_order','meta_title','meta_description','og_title','og_description','og_image','link'];$values=[];foreach($columns as $c)$values[]=$data[$c]??($c==='body_format'?'text':'');$values[]=date('Y-m-d H:i:s').'.'.bin2hex(random_bytes(3));$values[]=$id;
            db()->prepare('UPDATE entries SET '.implode('=?,', $columns).'=?,updated_at=? WHERE id=?')->execute($values);sync_entry_identity($existing,$data);assert_redirect_graph(db()->query('SELECT * FROM url_redirects')->fetchAll());audit($existing['title'].' önceki sürüme döndürüldü');db()->commit();flash('Önceki içerik sürümü geri yüklendi.');redirect($back);
        }
        if($existing && input('original_updated_at')!==$existing['updated_at'])throw new RuntimeException('Bu içerik başka bir oturumda değiştirildi. Değişikliklerinizi kopyalayıp sayfayı yenileyin.');
        $data=[];foreach(['title','slug','excerpt','body','body_format','image','category','author','status','published_at','sort_order','meta_title','meta_description','og_title','og_description','og_image','link'] as $k)$data[$k]=input($k);
        $data['profile_details']=$type==='team'?profile_details_input(is_array($_POST['profile']??null)?$_POST['profile']:profile_details($existing??[])):'';
        if(!in_array($data['body_format'],['text','html'],true))throw new RuntimeException('İçerik biçimini seçin.');
        if(mb_strlen($data['title'])<2||mb_strlen($data['title'])>200)throw new RuntimeException('Başlık 2–200 karakter arasında olmalıdır.');
        $data['slug']=slugify($data['slug']?:$data['title']);if(!$data['slug']||strlen($data['slug'])>180)throw new RuntimeException('Geçerli bir bağlantı adresi yazın.');
        if($existing && $type==='page'&&in_array($existing['slug'],['kurumsal','kvkk','cerez-politikasi','yasal-bilgilendirme'],true)){$data['slug']=$existing['slug'];$data['status']='published';}
        if(!in_array($data['status'],['draft','published'],true))throw new RuntimeException('Yayın durumunu seçin.');
        if(mb_strlen($data['body'])>150000||mb_strlen($data['excerpt'])>1500||mb_strlen($data['category'])>100||mb_strlen($data['author'])>150||mb_strlen($data['meta_title'])>200||mb_strlen($data['meta_description'])>500||mb_strlen($data['og_title'])>200||mb_strlen($data['og_description'])>500)throw new RuntimeException('İçerik alanlarından biri çok uzun.');
        if($data['image'] && (!valid_setting('image',$data['image'],'image')))throw new RuntimeException('Medya kütüphanesinden geçerli bir görsel seçin.');
        if($data['og_image'] && !valid_setting('image',$data['og_image'],'image'))throw new RuntimeException('Geçerli bir OG görseli seçin.');
        if($data['link'] && safe_url($data['link'])!==$data['link'])throw new RuntimeException('Geçerli bir bağlantı yazın.');
        if($type==='menu' && !$data['link'])throw new RuntimeException('Menü için bağlantı zorunludur.');
        $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$data['published_at']);
        if(!$date||$date->format('Y-m-d\TH:i')!==$data['published_at'])throw new RuntimeException('Geçerli bir yayın tarihi yazın.');
        $data['published_at']=$date->format('Y-m-d H:i:s');$data['sort_order']=max(-9999,min(9999,(int)$data['sort_order']));
        $columns=array_keys($data);$values=array_values($data);$values[]=date('Y-m-d H:i:s').'.'.bin2hex(random_bytes(3));
        if($existing){revision($existing);$values[]=$id;db()->prepare('UPDATE entries SET '.implode('=?,',$columns).'=?,updated_at=? WHERE id=?')->execute($values);
            sync_entry_identity($existing,$data);}
        else{$values[]=$type;$insert=db()->prepare('INSERT INTO entries('.implode(',',$columns).',updated_at,type) VALUES('.implode(',',array_fill(0,count($values),'?')).')');$insert->execute($values);$id=(int)db()->lastInsertId();}
        assert_redirect_graph(db()->query('SELECT * FROM url_redirects')->fetchAll());audit($data['title'].' kaydedildi');db()->commit();flash($data['status']==='published'?'İçerik kaydedildi. Yayın tarihi geldiğinde sitede görünür.':'Taslak kaydedildi. Henüz sitede görünmüyor.');redirect('/admin/?view=edit&type='.$type.'&id='.$id);
    }
    if($action==='save-seo-static'){
        $route=input('route');$back='/admin/?view=seo';if(!isset(SEO_ROUTES[$route])||$route==='home')throw new RuntimeException('Sayfa bulunamadı.');
        $t=input('meta_title');$d=input('meta_description');if(mb_strlen($t)>200||mb_strlen($d)>500)throw new RuntimeException('Başlık en fazla 200, açıklama en fazla 500 karakter olabilir.');
        db()->beginTransaction();save_setting('meta_'.$route.'_title',$t);save_setting('meta_'.$route.'_description',$d);save_setting('meta_'.$route.'_noindex',input('noindex')==='1'?'1':'0');
        db()->prepare('UPDATE seo_urls SET noindex=?,updated_at=? WHERE path=?')->execute([input('noindex')==='1'?1:0,date('Y-m-d H:i:s'),SEO_ROUTES[$route]['path']]);
        audit(SEO_ROUTES[$route]['label'].' sayfası meta bilgileri güncellendi');db()->commit();
        flash(SEO_ROUTES[$route]['label'].' sayfasının meta bilgileri kaydedildi.');redirect($back.'#sabit-'.$route);
    }
    if($action==='save-seo-entry'){
        $id=(int)input('id');$type=input('type');$back='/admin/?view=seo'.(input('filter')?'&type='.urlencode(input('filter')):'');if(!in_array($type,['article','practice','page','team'],true))throw new RuntimeException('İçerik türü bulunamadı.');
        db()->beginTransaction();$q=db()->prepare('SELECT * FROM entries WHERE id=? AND type=?');$q->execute([$id,$type]);$existing=$q->fetch();if(!$existing)throw new RuntimeException('İçerik bulunamadı.');
        if(input('original_updated_at')!==$existing['updated_at'])throw new RuntimeException('Bu içerik başka bir oturumda değiştirildi. Sayfayı yenileyip tekrar deneyin.');
        $title=input('title');$slug=slugify(input('slug')?:$title);$excerpt=input('excerpt');$mt=input('meta_title');$md=input('meta_description');$ot=input('og_title');$od=input('og_description');$oi=input('og_image');
        if(mb_strlen($title)<2||mb_strlen($title)>200)throw new RuntimeException('Başlık 2–200 karakter arasında olmalıdır.');
        if(!$slug||strlen($slug)>180)throw new RuntimeException('Geçerli bir bağlantı adresi yazın.');
        if($type==='page'&&in_array($existing['slug'],['kurumsal','kvkk','cerez-politikasi','yasal-bilgilendirme'],true))$slug=$existing['slug'];
        if(mb_strlen($excerpt)>1500||mb_strlen($mt)>200||mb_strlen($md)>500||mb_strlen($ot)>200||mb_strlen($od)>500||($oi&&!valid_setting('image',$oi,'image')))throw new RuntimeException('Meta/OG alanlarının uzunluğunu ve paylaşım görselini kontrol edin.');
        $dup=db()->prepare('SELECT id FROM entries WHERE type=? AND slug=? AND id<>?');$dup->execute([$type,$slug,$id]);if($dup->fetch())throw new RuntimeException('Bu bağlantı adresi aynı türde başka bir içerikte kullanılıyor.');
        revision($existing);db()->prepare('UPDATE entries SET title=?,slug=?,excerpt=?,meta_title=?,meta_description=?,og_title=?,og_description=?,og_image=?,updated_at=? WHERE id=?')->execute([$title,$slug,$excerpt,$mt,$md,$ot,$od,$oi,date('Y-m-d H:i:s').'.'.bin2hex(random_bytes(3)),$id]);
        sync_entry_identity($existing,['type'=>$type,'title'=>$title,'slug'=>$slug]);
        assert_redirect_graph(db()->query('SELECT * FROM url_redirects')->fetchAll());audit($title.' SEO bilgileri güncellendi');db()->commit();flash('“'.$title.'” için başlık, bağlantı ve meta bilgileri kaydedildi.');redirect($back.'#icerik-'.$id);
    }
    if($action==='upload'){
        $back='/admin/?view=media';$file=$_FILES['image']??null;
        if(!$file||!is_scalar($file['error'])||$file['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Görsel yüklenemedi. Dosyayı ve sunucu yükleme limitini kontrol edin.');
        if($file['size']>upload_limit())throw new RuntimeException('Görsel en fazla '.(upload_limit()/1024/1024).' MB olabilir.');
        $bytes=file_get_contents($file['tmp_name']);
        if(strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))==='svg'){
            $bytes=sanitize_svg($bytes);$mime='image/svg+xml';$extension='svg';
        }else{
        $info=@getimagesize($file['tmp_name']);if(!$info||!in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP],true))throw new RuntimeException('Yalnızca JPG, PNG, WebP veya güvenli SVG görseller yüklenebilir.');
        if($info[0]*$info[1]>20000000||$info[0]>8000||$info[1]>8000)throw new RuntimeException('Görsel boyutları çok büyük. En fazla 20 megapiksel ve 8000 piksel kullanın.');
        $mime=image_type_to_mime_type($info[2]);$extension=match($info[2]){IMAGETYPE_JPEG=>'jpg',IMAGETYPE_PNG=>'png',default=>'webp'};
        if(extension_loaded('gd')){
            $img=imagecreatefromstring($bytes);if(!$img)throw new RuntimeException('Görsel okunamadı.');
            imagepalettetotruecolor($img);imagealphablending($img,false);imagesavealpha($img,true);
            ob_start();$encoded=imagewebp($img,null,87);$bytes=ob_get_clean();imagedestroy($img);
            if(!$encoded)throw new RuntimeException('Görsel dönüştürülemedi.');$extension='webp';$mime='image/webp';
        }
        }
        if(strlen($bytes)>upload_limit())throw new RuntimeException('İşlenmiş görsel çok büyük. Daha küçük bir görsel kullanın.');
        $path='/assets/uploads/'.bin2hex(random_bytes(16)).'.'.$extension;
        db()->beginTransaction();store_media($path,$bytes,$mime);
        db()->prepare('INSERT INTO media(path,name,created_at) VALUES(?,?,?)')->execute([$path,mb_substr(basename($file['name']),0,150),date('Y-m-d H:i:s')]);db()->commit();audit('Medya kütüphanesine görsel eklendi');flash('Görsel yüklendi. İçerik veya ayar sayfalarında kütüphaneden seçebilirsiniz.');redirect($back);
    }
    if($action==='message-status'){
        $status=input('status');if(!in_array($status,['new','read','replied','archived'],true))throw new RuntimeException('Geçersiz durum.');
        db()->prepare('UPDATE messages SET status=? WHERE id=?')->execute([$status,(int)input('id')]);audit('İletişim talebi durumu güncellendi');flash('Talep durumu güncellendi.');redirect('/admin/?view=messages&id='.(int)input('id'));
    }
    if($action==='account'){
        $back='/admin/?view=account';$q=db()->prepare('SELECT password FROM users WHERE id=?');$q->execute([$user['id']]);
        $bucket=rate_bucket('account');if(rate_limited($bucket,8,900))throw new RuntimeException('Çok sayıda parola denemesi yapıldı. Daha sonra tekrar deneyin.');
        if(!password_verify(input('current_password'),$q->fetchColumn())){rate_hit($bucket);throw new RuntimeException('Mevcut parola hatalı.');}
        $email=mb_strtolower(input('email'));$name=input('name');$password=input('new_password');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||mb_strlen($name)<2||mb_strlen($name)>100)throw new RuntimeException('Ad ve e-posta alanlarını kontrol edin.');
        if($password && (strlen($password)<12||strlen($password)>72||$password!==input('confirm_password')))throw new RuntimeException('Yeni parola 12–72 karakter olmalı ve tekrar alanıyla eşleşmelidir.');
        if($password)db()->prepare('UPDATE users SET email=?,name=?,password=?,version=version+1 WHERE id=?')->execute([$email,$name,password_hash($password,PASSWORD_DEFAULT),$user['id']]);
        else db()->prepare('UPDATE users SET email=?,name=?,version=version+1 WHERE id=?')->execute([$email,$name,$user['id']]);
        $_SESSION['user_version']=(int)$user['version']+1;$_SESSION['user_name']=$name;session_regenerate_id(true);audit('Yönetici hesabı güncellendi');flash('Hesabınız güncellendi. Diğer açık oturumlar sonlandırıldı.');redirect($back);
    }
    if($action==='export'){
        $data=backup_data();audit('İçerik yedeği indirildi');header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="hukuk-icerik-'.date('Y-m-d-His').'.json"');echo json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;
    }
    if($action==='import'){
        $back='/admin/?view=backup';$f=$_FILES['backup']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK||$f['size']>backup_upload_limit())throw new RuntimeException((backup_upload_limit()/1024/1024).' MB altında geçerli bir yedek dosyası seçin.');
        $data=json_decode(file_get_contents($f['tmp_name']),true,512,JSON_THROW_ON_ERROR);
        if(!is_array($data)||($data['format']??'')!=='mizan-cms'||($data['version']??0)!==1||!is_array($data['entries']??null)||!is_array($data['settings']??null)||count($data['entries'])>10000)throw new RuntimeException('Yedek biçimi desteklenmiyor.');
        if(isset($data['settings']['home_section_order'])&&is_string($data['settings']['home_section_order'])){
            $oldOrder=array_map('trim',explode(',',$data['settings']['home_section_order']));
            if(count($oldOrder)===count(array_unique($oldOrder))&&!array_diff($oldOrder,array_keys(HOME_SECTIONS))){
                if(!in_array('home_consultation',$oldOrder,true))array_unshift($oldOrder,'home_consultation');
                if(!in_array('home_profile',$oldOrder,true))array_unshift($oldOrder,'home_profile');
                $data['settings']['home_section_order']=implode(',',array_unique(array_merge($oldOrder,array_keys(HOME_SECTIONS))));
            }
        }
        $redirects=$data['redirects']??[];
        if(!is_array($redirects)||count($redirects)>20000)throw new RuntimeException('Yedekte geçersiz yönlendirme listesi var.');
        foreach($redirects as $redirect){
            if(!is_array($redirect)||!in_array($redirect['type']??'',['article','practice','page','team'],true))throw new RuntimeException('Yedekte geçersiz yönlendirme var.');
            foreach(['slug','target_slug'] as $key)if(!is_string($redirect[$key]??null)||!$redirect[$key]||strlen($redirect[$key])>180||slugify($redirect[$key])!==$redirect[$key])throw new RuntimeException('Yedekte geçersiz yönlendirme adresi var.');
        }
        $manual=$data['url_redirects']??[];$policies=$data['seo_urls']??[];
        if(!is_array($manual)||!is_array($policies)||count($manual)>20000||count($policies)>20000)throw new RuntimeException('Yedekteki SEO kayıtları geçersiz.');
        $combined=array_column(db()->query('SELECT * FROM url_redirects')->fetchAll(),null,'source_path');
        foreach($manual as $row){assert_redirect_graph([$row]);$combined[$row['source_path']]=$row;}
        assert_redirect_graph(array_values($combined));foreach($policies as $row){if(!is_array($row))throw new RuntimeException('Geçersiz site haritası kaydı.');validate_url_policy($row);}
        $valid=[];foreach(settings_fields() as $g)foreach($g['fields'] as $k=>$schema)$valid[$k]=$schema;
        // Preserve older content/settings when importing a backup from this installation.
        foreach(['laws_title','laws_text','laws_note','team_empty_title','team_empty_text'] as $k)$valid[$k]=[$k,'textarea'];
        foreach(SEO_ROUTES as $route=>$info)foreach(['title'=>'text','description'=>'textarea','noindex'=>'checkbox'] as $suffix=>$kind)$valid['meta_'.$route.'_'.$suffix]=[$route,$kind];
        foreach($data['settings'] as $k=>$v){if(!is_string($v)||!isset($valid[$k])||!valid_setting($k,$v,$valid[$k][1])){if(in_array($k,['brand_initial','private_privacy_notice_hash'],true))continue;throw new RuntimeException('Yedekte geçersiz ayar: '.$k);}}
        $allowed=['profile_details','type','title','slug','excerpt','body','body_format','image','category','author','status','published_at','sort_order','meta_title','meta_description','og_title','og_description','og_image','link','updated_at'];
        $rows=[];foreach($data['entries'] as $en){
            if(!is_array($en)||(!isset(CONTENT_TYPES[$en['type']??''])&&!in_array($en['type']??'',['law','team'],true))||!is_string($en['title']??null)||mb_strlen($en['title'])<2||mb_strlen($en['title'])>200||!is_string($en['slug']??null)||!$en['slug']||strlen($en['slug'])>180||slugify($en['slug'])!==$en['slug']||!in_array($en['status']??'', ['draft','published','archived'],true))throw new RuntimeException('Yedekte geçersiz içerik var.');
            foreach($allowed as $k){if(!isset($en[$k])&&in_array($k,['body_format','og_title','og_description','og_image','profile_details'],true))$en[$k]=$k==='body_format'?'text':'';if(!isset($en[$k])||!is_scalar($en[$k]))throw new RuntimeException('İçerikte eksik alan var.');}
            if(!in_array($en['body_format'],['text','html'],true)||mb_strlen((string)$en['body'])>150000||mb_strlen((string)$en['og_title'])>200||mb_strlen((string)$en['og_description'])>500||($en['image']&&!valid_setting('image',(string)$en['image'],'image'))||($en['og_image']&&!valid_setting('image',(string)$en['og_image'],'image'))||($en['link']&&safe_url((string)$en['link'])!==$en['link']))throw new RuntimeException('Yedekte geçersiz görsel, metin veya bağlantı var.');
            if(!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',$en['published_at']))throw new RuntimeException('Yedekte geçersiz tarih var.');
            if($en['type']==='page'&&in_array($en['slug'],['kurumsal','kvkk','cerez-politikasi','yasal-bilgilendirme'],true))$en['status']='published';
            $en['profile_details']=profile_details_input(profile_details($en));
            $row=[];foreach($allowed as $k)$row[$k]=$en[$k];$row['sort_order']=max(-9999,min(9999,(int)$en['sort_order']));$row['updated_at']=date('Y-m-d H:i:s').'.'.bin2hex(random_bytes(3));$rows[]=$row;
        }
        persist_content_backup(backup_data());
        db()->beginTransaction();foreach($data['settings'] as $k=>$v){if(isset($valid[$k]))save_setting($k,$v);}
        foreach($rows as $row){$old=entry($row['type'],$row['slug'],false);if($old)revision($old);$sql='INSERT INTO entries('.implode(',',$allowed).') VALUES('.implode(',',array_fill(0,count($allowed),'?')).') ON CONFLICT(type,slug) DO UPDATE SET '.implode(',',array_map(fn($c)=>$c.'=excluded.'.$c,$allowed));db()->prepare($sql)->execute(array_values($row));}
        foreach($redirects as $redirect){
            $target=entry($redirect['type'],$redirect['target_slug'],false);
            if($target&&$target['slug']!==$redirect['slug'])db()->prepare('INSERT INTO entry_redirects(type,slug,entry_id) VALUES(?,?,?) ON CONFLICT(type,slug) DO UPDATE SET entry_id=excluded.entry_id')->execute([$redirect['type'],$redirect['slug'],$target['id']]);
        }
        foreach($manual as $row)db()->prepare('INSERT INTO url_redirects(source_path,target_path,status_code,enabled,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(source_path) DO UPDATE SET target_path=excluded.target_path,status_code=excluded.status_code,enabled=excluded.enabled,updated_at=excluded.updated_at')->execute([$row['source_path'],$row['target_path'],$row['status_code'],$row['enabled'],date('Y-m-d H:i:s')]);
        foreach($policies as $row)db()->prepare('INSERT INTO seo_urls(path,sitemap_enabled,noindex,lastmod,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(path) DO UPDATE SET sitemap_enabled=excluded.sitemap_enabled,noindex=excluded.noindex,lastmod=excluded.lastmod,updated_at=excluded.updated_at')->execute([$row['path'],$row['sitemap_enabled'],$row['noindex'],$row['lastmod'],date('Y-m-d H:i:s')]);
        assert_redirect_graph(db()->query('SELECT * FROM url_redirects')->fetchAll());
        audit('İçerik yedeği içe aktarıldı');db()->commit();flash('Yedek birleştirildi. Mevcut içeriklerin önceki kopyası sunucuda saklandı.');redirect($back);
    }
    throw new RuntimeException('İşlem bulunamadı.');
} catch(Throwable $error) {
    if(db()->inTransaction())db()->rollBack();
    $message=$error instanceof PDOException?'Kayıt tamamlanamadı. Aynı bağlantı adresi veya e-posta zaten kullanılıyor olabilir.':($error instanceof JsonException?'Geçerli bir JSON yedek dosyası seçin.':$error->getMessage());
    error_log('Admin action '.$action.': '.$error->getMessage());
    if(in_array($action,['save-entry','save-settings'],true)){
        $_SESSION['form_old']=$_POST;unset($_SESSION['form_old']['csrf']);
        if($action==='save-settings')foreach((settings_fields()[input('group')]['fields']??[]) as $k=>$field){if($field[1]==='checkbox'&&!isset($_POST[$k]))$_SESSION['form_old'][$k]='0';}
    }
    flash($message,'error');redirect($back);
}
