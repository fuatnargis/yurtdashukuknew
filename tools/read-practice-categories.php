<?php
$db=new PDO('sqlite:'.__DIR__.'/../storage/site.sqlite');
foreach($db->query("SELECT id,title,category FROM entries WHERE type='practice' ORDER BY id") as $r){
    echo $r['id'],' | ',$r['title'],' | category=[',($r['category']?:'BOS'),']',PHP_EOL;
}
