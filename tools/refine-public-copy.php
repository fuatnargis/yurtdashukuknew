<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/public-copy.php';
$db=db();
$key='private_public_copy_review_20261003';
$version=hash('sha256',json_encode([PUBLIC_SITE_COPY,PUBLIC_PRACTICE_COPY,PUBLIC_PROFILE_BODY,PUBLIC_CORPORATE_BODY,PUBLIC_APPOINTMENT_ANSWER,PUBLIC_COOKIE_BODY],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
$check=$db->prepare('SELECT value FROM settings WHERE key=?');
$check->execute([$key]);
if($check->fetchColumn()===$version){echo "İçerik incelemesi daha önce uygulandı.\n";exit;}
$db->beginTransaction();
try {
    $settings=$db->query('SELECT key,value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    $backup=['format'=>'mizan-cms','version'=>1,'exported_at'=>date(DATE_ATOM),
        'settings'=>array_filter($settings,fn($name)=>!str_starts_with($name,'private_'),ARRAY_FILTER_USE_KEY),
        'entries'=>$db->query('SELECT * FROM entries ORDER BY id')->fetchAll(),
        'redirects'=>$db->query('SELECT r.type,r.slug,e.slug AS target_slug FROM entry_redirects r JOIN entries e ON e.id=r.entry_id')->fetchAll()];
    $db->prepare('INSERT INTO content_backups(name,data,created_at) VALUES(?,?,?)')->execute([
        '2026-10-03 içerik incelemesi öncesi',json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),date('Y-m-d H:i:s')]);
    foreach(PUBLIC_SITE_COPY as $name=>$value){
        if($name==='hero_title')$value=$settings['brand']??$value;
        save_setting($name,$value);
    }
    $revise=$db->prepare('INSERT INTO revisions(entry_id,snapshot,created_at) VALUES(?,?,?)');
    $count=0;
    foreach($backup['entries'] as $entry){
        $changes=[];
        if($entry['type']==='practice'&&isset(PUBLIC_PRACTICE_COPY[$entry['slug']])&&
            (str_starts_with($entry['body'],'## Bu alanda neye dikkat ederiz')||str_starts_with($entry['body'],'## Çalışma yaklaşımımız'))){
            $copy=PUBLIC_PRACTICE_COPY[$entry['slug']];
            $changes=['excerpt'=>$copy['excerpt'],'body'=>public_practice_body($copy),'body_format'=>'text'];
        }
        if($entry['type']==='team'&&$entry['slug']==='halil-ibrahim-yurtdas'&&str_contains($entry['body'],'dosyalar stajyerden')){
            $changes=['excerpt'=>PUBLIC_PROFILE_EXCERPT,'body'=>PUBLIC_PROFILE_BODY,'body_format'=>'text'];
        }
        if($entry['type']==='page'&&$entry['slug']==='kurumsal'&&$entry['excerpt']==='Hukukun rehberliğinde, insana ve emeğe saygıyla.'&&str_starts_with($entry['body'],'## Yaklaşımımız')){
            $changes=['excerpt'=>PUBLIC_CORPORATE_EXCERPT,'body'=>PUBLIC_CORPORATE_BODY,'body_format'=>'text'];
        }
        if($entry['type']==='page'&&$entry['slug']==='cerez-politikasi'&&str_starts_with($entry['body'],'## Oturum çerezi')&&!str_contains($entry['body'],'## Harita ve harici bağlantılar')){
            $changes=['body'=>PUBLIC_COOKIE_BODY,'body_format'=>'text'];
        }
        if($entry['type']==='faq'&&$entry['title']==='İlk görüşme için nasıl randevu alabilirim?'&&str_starts_with($entry['body'],'İletişim formunu kullanarak')){
            $changes=['body'=>PUBLIC_APPOINTMENT_ANSWER,'body_format'=>'text'];
        }
        if($entry['type']==='practice'&&$entry['title']==='Deneme Hukuk'&&$entry['excerpt']==='deneme test'&&$entry['status']==='published')$changes=['status'=>'draft'];
        if($entry['type']==='practice'&&$entry['image']===''&&isset(PUBLIC_PRACTICE_COPY[$entry['slug']]))$changes['image']=PUBLIC_PRACTICE_COPY[$entry['slug']]['image'];
        if(!$changes)continue;
        $revise->execute([$entry['id'],json_encode($entry,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),date('Y-m-d H:i:s')]);
        $changes['updated_at']=date('Y-m-d H:i:s').'.'.bin2hex(random_bytes(3));
        $columns=array_keys($changes);
        $db->prepare('UPDATE entries SET '.implode('=?,',$columns).'=? WHERE id=?')->execute([...array_values($changes),$entry['id']]);
        $count++;
    }
    save_setting($key,$version);
    audit('Kamusal içerik incelemesi: olgusal metinler ve deneme içeriği taslağı');
    $db->commit();
    echo "İçerik yedeği ve önceki sürümler kaydedildi. {$count} kayıt düzenlendi.\n";
}catch(Throwable $error){$db->rollBack();throw $error;}
