<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/public-copy.php';
$db = db();
$previous = substr(PUBLIC_COOKIE_BODY, 0, -strlen("\n\n".PUBLIC_NAVIGATION_NOTICE));
$former = str_replace('Kayıt en fazla 10 saniye boyunca geçerlidir; hedef sayfada silinir. Geçiş iptal edilip aynı sayfada kalınırsa 10 saniye sonra temizlenir. Sekme kapatıldığında tarayıcı tarafından kaldırılır.', 'Kayıt hedef sayfada silinir; geçiş gerçekleşmezse 10 saniye sonra temizlenir.', PUBLIC_COOKIE_BODY);
$version = static fn(string $cookie): string => hash('sha256', json_encode([PUBLIC_SITE_COPY, PUBLIC_PRACTICE_COPY, PUBLIC_PROFILE_BODY, PUBLIC_CORPORATE_BODY, PUBLIC_APPOINTMENT_ANSWER, $cookie], JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
$db->beginTransaction();
try {
    $query = $db->prepare("SELECT * FROM entries WHERE type='page' AND slug='cerez-politikasi'");
    $query->execute();
    $entry = $query->fetch();
    if ($entry && in_array($entry['body'], [$previous, $former], true)) {
        $db->prepare('INSERT INTO revisions(entry_id,snapshot,created_at) VALUES(?,?,?)')->execute([$entry['id'], json_encode($entry, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR), date('Y-m-d H:i:s')]);
        $db->prepare('UPDATE entries SET body=?,updated_at=? WHERE id=?')->execute([PUBLIC_COOKIE_BODY, date('Y-m-d H:i:s').'.'.bin2hex(random_bytes(3)), $entry['id']]);
        audit('Sayfa gecislerinin tarayici depolamasi aciklamasi eklendi');
        echo "Cerez metni onceki surumu korunarak guncellendi.\n";
    } else {
        echo "Mevcut veya ozellestirilmis cerez metni degistirilmedi.\n";
    }
    $key = 'private_public_copy_review_20261003';
    $query = $db->prepare('SELECT value FROM settings WHERE key=?');
    $query->execute([$key]);
    if (in_array($query->fetchColumn(), [$version($previous), $version($former)], true)) save_setting($key, $version(PUBLIC_COOKIE_BODY));
    $db->commit();
} catch (Throwable $error) {
    $db->rollBack();
    throw $error;
}
