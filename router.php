<?php
declare(strict_types=1);
$path=rawurldecode((string)parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH));
if(preg_match('~(?:^|/)\.|(?:^|/)(?:storage|app|tools|tests|template-original|node_modules)(?:/|$)|\\\\|\x00~i',$path)||preg_match('~\.(?:sqlite|db|log|ini|ps1|md|json|lock|bak|txt)$~i',$path) && !in_array($path,['/robots.txt','/llms.txt'],true)) {http_response_code(404);exit('Sayfa bulunamadı.');}
if(str_starts_with($path,'/assets/') && is_file(__DIR__.$path)) return false;
if($path==='/admin' || $path==='/admin/') {require __DIR__.'/admin/index.php';return true;}
$admin=['/admin/index.php','/admin/login.php','/admin/action.php'];
if(in_array($path,$admin,true)){require __DIR__.$path;return true;}
require __DIR__.'/index.php';
