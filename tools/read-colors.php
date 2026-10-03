<?php
$db=new PDO('sqlite:'.__DIR__.'/../storage/site.sqlite');
$q=$db->query("SELECT key,value FROM settings WHERE key LIKE '%color%' OR key IN ('brand','brand_subtitle','hero_image_alt')");
foreach($q as $r)echo $r['key'],' = ',$r['value'],PHP_EOL;
