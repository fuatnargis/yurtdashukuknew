<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$source=__DIR__.'/../assets/images/law-hero-light-v1.png';
$target=__DIR__.'/../assets/images/law-hero-light-v1.webp';
$image=imagecreatefrompng($source);
if(!$image||!imagewebp($image,$target,84))throw new RuntimeException('Ana görsel hazırlanamadı.');
imagedestroy($image);
echo 'WebP ana görsel: '.filesize($target)." bayt\n";
