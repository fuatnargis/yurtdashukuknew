<?php
$db=new PDO('sqlite:'.__DIR__.'/../storage/site.sqlite');
foreach($db->query("SELECT id,type,title,image FROM entries WHERE type IN ('practice','article','team')") as $r){
    echo $r['id'],' | ',$r['type'],' | ',$r['title'],' | img=[',($r['image']?:'BOS'),']',PHP_EOL;
}
