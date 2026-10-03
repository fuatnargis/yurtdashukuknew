<?php
declare(strict_types=1);
// Only this file is a Vercel function. Never route a user supplied path to require().
$path=rawurldecode((string)parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH));
if(preg_match('~(?:^|/)\.|\\\\|\x00|^/(?:app|storage|tools|tests|template-original|node_modules|api)(?:/|$)~i',$path)) {http_response_code(404);exit;}
if(str_starts_with($path,'/assets/')&&!preg_match('~^/assets/uploads/[a-f0-9]{32}\.(jpg|jpeg|png|webp|svg)$~',$path)){http_response_code(404);exit;}
$admin=['/admin'=>'index.php','/admin/'=>'index.php','/admin/index.php'=>'index.php','/admin/login.php'=>'login.php','/admin/action.php'=>'action.php'];
if(isset($admin[$path])){require dirname(__DIR__).'/admin/'.$admin[$path];exit;}
require dirname(__DIR__).'/index.php';
