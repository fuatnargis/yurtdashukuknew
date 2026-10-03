<?php
$db=new PDO('sqlite:'.__DIR__.'/../storage/site.sqlite');
$keys=['heading_font','header_layout','home_articles_layout','home_section_order','hero_overlay_opacity','hero_height','card_radius','show_home_profile','show_home_map','contact_band_image','home_profile_eyebrow','home_profile_title','home_profile_text'];
foreach($keys as $k){$q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute([$k]);echo $k,' = ',$q->fetchColumn(),PHP_EOL;}
