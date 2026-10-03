<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
putenv('HUKUK_SETUP=1');
require dirname(__DIR__).'/app/bootstrap.php';
$db=db();
if((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn()){echo "Site veritabanı ve yönetici hesabı hazır.\n";exit;}
$options=getopt('',['email:','name:']);$email=mb_strtolower((string)($options['email']??'yonetici@localhost.test'));$name=(string)($options['name']??'Site Yöneticisi');
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Geçerli --email belirtin.\n");exit(1);}
$password=bin2hex(random_bytes(12)).'!aA9';
$db->prepare('INSERT INTO users(email,name,password) VALUES(?,?,?)')->execute([$email,$name,password_hash($password,PASSWORD_DEFAULT)]);
$text="YÖNETİM PANELİ ERİŞİM BİLGİLERİ\n\nAdres: http://127.0.0.1:8088/admin/\nE-posta: ".$email."\nParola: ".$password."\n\nBu dosyayı paylaşmayın. İlk girişte Hesabım ekranından parolayı değiştirin.\nParola değiştirildiğinde bu kurulum dosyasındaki eski parola geçersiz olur.\n";
file_put_contents(DATA_DIR.'/admin-access.txt',$text,LOCK_EX);@chmod(DATA_DIR.'/admin-access.txt',0600);
echo "Kurulum tamamlandı. Giriş bilgileri: ".DATA_DIR."/admin-access.txt\n";
