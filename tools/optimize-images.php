<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);
foreach(['justice-hero','architecture','library'] as $name){
    $path=$root.'/assets/images/'.$name.'.png';if(!file_exists($path))continue;
    $source=imagecreatefrompng($path);if(!$source)throw new RuntimeException($name.' okunamadı.');
    imageinterlace($source,true);imagejpeg($source,$root.'/assets/images/'.$name.'.jpg',88);imagedestroy($source);
    echo $name.'.jpg: '.round(filesize($root.'/assets/images/'.$name.'.jpg')/1024)." KB\n";
}
