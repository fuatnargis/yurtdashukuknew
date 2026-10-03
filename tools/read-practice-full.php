<?php
$db=new PDO('sqlite:'.__DIR__.'/../storage/site.sqlite');
foreach($db->query("SELECT id,title,body FROM entries WHERE type='practice' ORDER BY id") as $p){
    echo "===ID {$p['id']}: {$p['title']}===\n{$p['body']}\n\n";
}
