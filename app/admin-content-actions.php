<?php
declare(strict_types=1);

if(in_array($action,['bulk-content','delete-entry'],true)){
    $type=input('type');$back='/admin/?view=content&type='.urlencode($type);
    if(!in_array($type,['article','practice'],true))throw new RuntimeException('Bu işlem yalnızca makaleler ve çalışma alanları için kullanılabilir.');
    $ids=$action==='delete-entry'?[(int)input('id')]:($_POST['ids']??[]);
    if(!is_array($ids)||!$ids||count($ids)>100)throw new RuntimeException('1–100 içerik seçin.');
    $mode=$action==='delete-entry'?'delete':input('mode');
    if(!in_array($mode,['archive','restore','delete'],true)||($action==='bulk-content'&&$mode==='delete'))throw new RuntimeException('Geçerli bir işlem seçin.');
    db()->beginTransaction();$count=0;
    foreach(array_unique(array_map('intval',$ids)) as $id){
        $q=db()->prepare('SELECT * FROM entries WHERE id=? AND type=?');$q->execute([$id,$type]);$en=$q->fetch();
        if(!$en)throw new RuntimeException('Seçilen içerik bulunamadı. Listeyi yenileyin.');
        $expected=$action==='delete-entry'?input('original_updated_at'):($_POST['versions'][$id]??'');
        if($expected!==$en['updated_at'])throw new RuntimeException('Seçilen içeriklerden biri değiştirildi. Listeyi yenileyin.');
        if($mode==='delete'){
            if($en['status']!=='archived')throw new RuntimeException('Kalıcı silmeden önce içeriği arşive taşıyın.');
            persist_content_backup(backup_data());
            db()->prepare('DELETE FROM seo_urls WHERE path=?')->execute([entry_url($en)]);
            db()->prepare('DELETE FROM revisions WHERE entry_id=?')->execute([$id]);
            db()->prepare('DELETE FROM entry_redirects WHERE entry_id=?')->execute([$id]);
            db()->prepare('DELETE FROM entries WHERE id=?')->execute([$id]);
        }else{
            if($mode==='restore'&&$en['status']!=='archived')throw new RuntimeException('Yalnızca arşivlenen içerik geri alınabilir.');
            revision($en);db()->prepare('UPDATE entries SET status=?,updated_at=? WHERE id=?')->execute([$mode==='archive'?'archived':'draft',date('Y-m-d H:i:s').'.'.bin2hex(random_bytes(3)),$id]);
        }
        audit($en['title'].($mode==='delete'?' kalıcı silindi':($mode==='archive'?' arşivlendi':' taslaklara geri alındı')));$count++;
    }
    assert_redirect_graph(db()->query('SELECT * FROM url_redirects')->fetchAll());db()->commit();flash($count.' içerik güncellendi.'.($mode==='delete'?' İşlem öncesi içerik yedeği saklandı.':''));redirect($back.($mode==='delete'?'&status=archived':''));
}
