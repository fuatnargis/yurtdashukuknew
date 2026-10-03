<?php
$db=new PDO('sqlite:'.__DIR__.'/../storage/site.sqlite');
$keys=['hero_eyebrow','hero_title','hero_text','hero_button','hero_secondary','hero_note','intro_eyebrow','intro_title','intro_text','approach_eyebrow','approach_title','approach_text','office_eyebrow','office_title','office_text','practice_eyebrow','practice_title','practice_text','articles_eyebrow','articles_title','articles_text','contact_eyebrow','contact_title','contact_text','seo_title','seo_description','home_profile_eyebrow','home_profile_title','home_profile_text','footer_text','legal_notice','brand_subtitle'];
foreach($keys as $k){$q=$db->prepare('SELECT value FROM settings WHERE key=?');$q->execute([$k]);$v=(string)$q->fetchColumn();echo "=== $k ===\n$v\n\n";}
echo "=== PRACTICE ENTRIES ===\n";
foreach($db->query("SELECT id,title,excerpt,body,category FROM entries WHERE type='practice' ORDER BY id") as $p){
    echo "--- {$p['title']} (cat: {$p['category']}) ---\n";
    echo "Excerpt: {$p['excerpt']}\n";
    echo "Body: ".mb_substr((string)$p['body'],0,400)."\n\n";
}
echo "=== TEAM ENTRIES ===\n";
foreach($db->query("SELECT title,excerpt,body FROM entries WHERE type='team'") as $t){
    echo "--- {$t['title']} ---\nExcerpt: {$t['excerpt']}\nBody: {$t['body']}\n\n";
}
echo "=== ARTICLE ENTRIES (first 3) ===\n";
$i=0;
foreach($db->query("SELECT title,excerpt,body FROM entries WHERE type='article' ORDER BY id LIMIT 3") as $a){
    echo "--- {$a['title']} ---\nExcerpt: {$a['excerpt']}\nBody: ".mb_substr((string)$a['body'],0,500)."\n\n";
}
