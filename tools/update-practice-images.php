<?php
// Assigns distinct images to practice entries and the founder lawyer profile.
// Safe UPDATE only; no schema changes, no data loss for existing fields.
declare(strict_types=1);
$db = new PDO('sqlite:' . __DIR__ . '/../storage/site.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$practiceImages = [
    'Aile Hukuku' => '/assets/images/practice/aile-hukuku.jpg',
    'Ceza Hukuku' => '/assets/images/practice/ceza-hukuku.jpg',
    'İş Hukuku' => '/assets/images/practice/is-hukuku.jpg',
    'Ticaret & Şirketler Hukuku' => '/assets/images/practice/ticaret-sirketler-hukuku.jpg',
    'Gayrimenkul Hukuku' => '/assets/images/practice/gayrimenkul-hukuku.jpg',
    'Miras Hukuku' => '/assets/images/practice/miras-hukuku.jpg',
];

$db->beginTransaction();
try {
    $stmt = $db->prepare("UPDATE entries SET image = ? WHERE type = 'practice' AND title = ?");
    foreach ($practiceImages as $title => $image) {
        $stmt->execute([$image, $title]);
        echo $title, ' -> ', $image, ' (', $stmt->rowCount(), ' satir)', PHP_EOL;
    }

    $lawyerStmt = $db->prepare("UPDATE entries SET image = ? WHERE type = 'team' AND title = ?");
    $lawyerStmt->execute(['/assets/images/lawyer-portrait.jpg', 'Halil İbrahim Yurtdaş']);
    echo 'Halil İbrahim Yurtdaş -> /assets/images/lawyer-portrait.jpg (', $lawyerStmt->rowCount(), ' satir)', PHP_EOL;

    $db->commit();
    echo 'Tamamlandi.', PHP_EOL;
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
