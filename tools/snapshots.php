<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$base=rtrim($argv[1]??'http://127.0.0.1:8088','/');
if(!preg_match('~^http://(?:127\.0\.0\.1|localhost):\d+$~',$base)){fwrite(STDERR,"Yerel sunucu adresi belirtin.\n");exit(1);}
foreach(['index.html'=>'/','blog-list.html'=>'/makaleler'] as $file=>$url){
    $html=file_get_contents($base.$url);if($html===false){fwrite(STDERR,"Sunucuya erişilemedi.\n");exit(1);}
    $html=preg_replace_callback('~(href|src|action)="(/[^"<>]*)"~',function($m)use($base){
        return $m[1].'="'.(str_starts_with($m[2],'/assets/')?substr($m[2],1):$base.$m[2]).'"';
    },$html);
    $html=preg_replace('~<input type="hidden" name="csrf" value="[a-f0-9]+">~','',$html);
    $html="<!-- Tasarım önizlemesi. Asıl site PHP üzerinden çalışır. baslat.ps1 -> ".$base." -->\n".$html;
    file_put_contents(dirname(__DIR__).'/'.$file,$html);
    echo $file." güncellendi.\n";
}
