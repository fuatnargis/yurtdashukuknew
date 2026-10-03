<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
header('Cache-Control: no-store');header('X-Robots-Tag: noindex, nofollow');
if(current_user())redirect('/admin/');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$email=mb_strtolower(input('email'));$bucket=rate_bucket('login');$accountBucket=hash('sha256','account|'.$email);
    if(rate_limited($bucket,8,900)||rate_limited($accountBucket,8,900)){$error='Çok sayıda giriş denemesi yapıldı. 15 dakika sonra tekrar deneyin.';http_response_code(429);}
    else{
        $q=db()->prepare('SELECT * FROM users WHERE email=?');$q->execute([$email]);$u=$q->fetch();
        if($u && password_verify(input('password'),$u['password'])){
            session_regenerate_id(true);$_SESSION['user_id']=$u['id'];$_SESSION['user_name']=$u['name'];$_SESSION['user_version']=$u['version'];$_SESSION['last_active']=time();$_SESSION['csrf']=bin2hex(random_bytes(32));
            db()->prepare('DELETE FROM attempts WHERE bucket IN (?,?)')->execute([$bucket,$accountBucket]);audit('Yönetim paneline giriş yapıldı');redirect('/admin/');
        }
        if(!$u)password_verify(input('password'),'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        rate_hit($bucket);rate_hit($accountBucket);$error='E-posta adresi veya parola hatalı.';http_response_code(401);
    }
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Yönetim Paneli | <?=e(setting('brand'))?></title><link rel="icon" href="/assets/images/yurtdas-favicon-v2.svg"><link rel="stylesheet" href="/assets/site.css"><link rel="stylesheet" href="/assets/admin.css?v=8"></head><body class="login-page"><div class="login-visual"><a class="login-brand" href="/"><img src="/assets/images/yurtdas-seal-v2.svg" width="40" height="44" alt=""><span><?=e(setting('brand'))?></span></a><div><span class="eyebrow">İÇERİK YÖNETİMİ</span><h1>Büronuzun dijital<br>dünyası, sizin elinizde.</h1><p>İçeriklerinizi güncel tutun.<br>Her ayrıntıyı tek bir yerden yönetin.</p></div><small>Güvenli yönetim alanı</small></div><main class="login-main"><div class="login-card"><span class="eyebrow">YÖNETİM PANELİ</span><h2>Tekrar hoş geldiniz.</h2><p>Devam etmek için hesabınıza giriş yapın.</p><?php if($error):?><div class="notice error" role="alert"><?=e($error)?></div><?php endif;?><?=flash_html()?><?php if(!(int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn()):?><div class="notice">Yönetici hesabı henüz oluşturulmadı. Proje klasöründe baslat.ps1 komutunu çalıştırın veya kurulum belgesindeki CLI adımlarını izleyin.</div><?php else:?><form action="/admin/login.php" method="post"><?=csrf_field()?><label>E-posta adresi<input type="email" name="email" required autocomplete="username" maxlength="190" value="<?=e(input('email'))?>" autofocus></label><label>Parola<input type="password" name="password" required autocomplete="current-password" maxlength="200"></label><button class="button" type="submit">Giriş yap <?=icon('arrow')?></button></form><?php endif;?><a class="text-link login-return" href="/">← Web sitesine dön</a><p class="login-help">Giriş bilgileriniz yerel kurulumda storage/admin-access.txt dosyasında bulunur.</p></div></main></body></html>
