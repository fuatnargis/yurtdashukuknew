<?php
// Assigns distinct, topic-matched images to each article entry.
declare(strict_types=1);
$db = new PDO('sqlite:' . __DIR__ . '/../storage/site.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$map = [
    'Hukuki görüşmeye nasıl hazırlanmalısınız?' => '/assets/images/articles/hazirlik.jpg',
    'Sözleşme imzalamadan önce dikkatli okumanın önemi' => '/assets/images/articles/sozlesme.jpg',
    'İş ilişkilerinde belgelerin düzenli tutulması' => '/assets/images/articles/is-belgeleri.jpg',
    'Taşınmaz işlemlerinde görüşme öncesi hazırlık' => '/assets/images/articles/tasinmaz.jpg',
    'Aile hukukunda iletişim ve mahremiyet' => '/assets/images/articles/aile-mahremiyet.jpg',
    'Kurumsal hukuki danışmanlıkta düzenli iletişim' => '/assets/images/articles/kurumsal-danismanlik.jpg',
];

$db->beginTransaction();
try {
    $stmt = $db->prepare("UPDATE entries SET image = ? WHERE type = 'article' AND title = ?");
    foreach ($map as $title => $image) {
        $stmt->execute([$image, $title]);
        echo $title, ' -> ', $image, ' (', $stmt->rowCount(), ' satir)', PHP_EOL;
    }
    $db->commit();
    echo 'Tamamlandi.', PHP_EOL;
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
