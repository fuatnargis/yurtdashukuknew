<?php
$db=new PDO('sqlite:'.__DIR__.'/../storage/site.sqlite');
$q=$db->prepare('SELECT value FROM settings WHERE key=?');
foreach(['dark_section_color','dark_section_text_color'] as $k){$q->execute([$k]);echo $k,' = ',$q->fetchColumn(),PHP_EOL;}
