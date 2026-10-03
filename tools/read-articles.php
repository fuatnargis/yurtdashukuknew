<?php
$db=new PDO('sqlite:'.__DIR__.'/../storage/site.sqlite');
foreach($db->query("SELECT id,title,image FROM entries WHERE type='article' ORDER BY id") as $r){
    echo $r['id'],' | ',$r['title'],' | img=[',($r['image']?:'BOS'),']',PHP_EOL;
}
