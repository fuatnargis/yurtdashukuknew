<?php
declare(strict_types=1);

if(in_array($action,['save-redirect','delete-redirect'],true)){
    $back='/admin/?view=redirects';$id=(int)input('id');
    db()->beginTransaction();
    $q=db()->prepare('SELECT * FROM url_redirects WHERE id=?');$q->execute([$id]);$existing=$q->fetch();
    if($id&&!$existing)throw new RuntimeException('Yönlendirme bulunamadı.');
    if($existing&&input('original_updated_at')!==$existing['updated_at'])throw new RuntimeException('Bu yönlendirme değiştirildi. Sayfayı yenileyin.');
    if($action==='delete-redirect'){
        db()->prepare('DELETE FROM url_redirects WHERE id=?')->execute([$id]);audit('Yönlendirme silindi: '.$existing['source_path']);
    }else{
        $row=['source_path'=>input('source_path'),'target_path'=>input('target_path'),'status_code'=>input('status_code'),'enabled'=>input('enabled','0')];
        $rows=db()->query('SELECT * FROM url_redirects')->fetchAll();
        $rows=array_values(array_filter($rows,fn($r)=>(int)$r['id']!==$id));$rows[]=$row;assert_redirect_graph($rows);
        $values=[$row['source_path'],$row['target_path'],$row['status_code'],$row['enabled'],date('Y-m-d H:i:s').'.'.bin2hex(random_bytes(3))];
        if($id){$values[]=$id;db()->prepare('UPDATE url_redirects SET source_path=?,target_path=?,status_code=?,enabled=?,updated_at=? WHERE id=?')->execute($values);}
        else db()->prepare('INSERT INTO url_redirects(source_path,target_path,status_code,enabled,updated_at) VALUES(?,?,?,?,?)')->execute($values);
        audit('Yönlendirme kaydedildi: '.$row['source_path']);
    }
    db()->commit();flash('Yönlendirmeler güncellendi.');redirect($back);
}
if(in_array($action,['save-sitemap-url','reset-sitemap-url','toggle-sitemap'],true)){
    $back='/admin/?view=sitemap';db()->beginTransaction();
    if($action==='toggle-sitemap')save_setting('sitemap_enabled',input('sitemap_enabled')==='1'?'1':'0');
    else{
        $path=input('path');if(!valid_public_path($path))throw new RuntimeException('Geçerli bir site adresi yazın.');
        if($action==='reset-sitemap-url')db()->prepare('DELETE FROM seo_urls WHERE path=?')->execute([$path]);
        else{
            $pages=sitemap_candidates();if(!isset($pages[$path]))throw new RuntimeException('Önce sayfayı yayımlayın. Yalnızca sitedeki yayınlanmış sayfalar haritaya eklenebilir.');
            if(isset(managed_redirects()[$path])||automatic_redirect_path($path))throw new RuntimeException('Yönlendirme kaynağı yerine hedef sayfayı site haritasına ekleyin.');
            $row=['path'=>$path,'sitemap_enabled'=>input('sitemap_enabled','0'),'noindex'=>input('noindex','0'),'lastmod'=>input('lastmod')];validate_url_policy($row);
            db()->prepare('INSERT INTO seo_urls(path,sitemap_enabled,noindex,lastmod,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(path) DO UPDATE SET sitemap_enabled=excluded.sitemap_enabled,noindex=excluded.noindex,lastmod=excluded.lastmod,updated_at=excluded.updated_at')->execute([$path,$row['sitemap_enabled'],$row['noindex'],$row['lastmod'],date('Y-m-d H:i:s')]);
            foreach(SEO_ROUTES as $route=>$info)if($route!=='home'&&$info['path']===$path)save_setting('meta_'.$route.'_noindex',$row['noindex']);
        }
    }
    audit('Site haritası ayarları güncellendi');db()->commit();flash('Site haritası ayarları kaydedildi.');redirect($back);
}
