<?php
$db=new PDO('sqlite:'.__DIR__.'/../storage/site.sqlite');
$keys=['hero_eyebrow','hero_title','hero_text','hero_button','hero_secondary','hero_note','intro_eyebrow','intro_title','intro_text','approach_eyebrow','approach_title','office_eyebrow','office_title','office_text','practice_eyebrow','practice_title','practice_text','articles_eyebrow','articles_title','contact_eyebrow','contact_title','contact_text','hero_image','intro_image','approach_image','seo_title','seo_description'];
foreach($keys as $k){$q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute([$k]);$v=(string)$q->fetchColumn();echo $k,' = ',mb_substr($v,0,110),PHP_EOL;}
echo '--- team entries ---'.PHP_EOL;
foreach($db->query("SELECT title,excerpt FROM entries WHERE type='team'") as $t)echo $t['title'],' | ',mb_substr((string)$t['excerpt'],0,80),PHP_EOL;
echo '--- practice count ---'.PHP_EOL;
echo $db->query("SELECT COUNT(*) FROM entries WHERE type='practice'")->fetchColumn(),PHP_EOL;
