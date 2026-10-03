<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require_once __DIR__.'/runtime.php';
define('DATA_DIR', getenv('HUKUK_DATA_DIR') ?: (vercel_runtime()?sys_get_temp_dir().'/hukuk':ROOT.'/storage'));
require_once __DIR__.'/media-storage.php';
require_once __DIR__.'/svg-media.php';
require_once __DIR__.'/site-texts.php';
require_once __DIR__.'/public-copy.php';
require_once __DIR__.'/theme.php';
require_once __DIR__.'/profile.php';
require_once __DIR__.'/seo.php';
require_once __DIR__.'/seo-management.php';
require_once __DIR__.'/article-components.php';
require_once __DIR__.'/content-format.php';
date_default_timezone_set('Europe/Istanbul');
if (!is_dir(DATA_DIR)) { mkdir(DATA_DIR, 0700, true); }
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', vercel_runtime()?'php://stderr':DATA_DIR.'/php-error.log');
set_exception_handler(function(Throwable $error): void {
    error_log('Application failure: '.get_class($error).' code '.$error->getCode());
    if(PHP_SAPI==='cli'){fwrite(STDERR,"İşlem tamamlanamadı. Bağlantı ve kurulum ayarlarını kontrol edin.\n");exit(1);}
    http_response_code(503);header('Content-Type: text/plain; charset=utf-8');header('Cache-Control: no-store');echo 'Site geçici olarak kullanılamıyor. Lütfen daha sonra tekrar deneyin.';
});
if(vercel_runtime()&&!cloud_database())throw new RuntimeException('Persistent database required');
if(vercel_runtime()){$_SERVER['HTTPS']='on';if(isset($_SERVER['HTTP_X_VERCEL_FORWARDED_FOR'])){$_SERVER['REMOTE_ADDR']=trim(explode(',',$_SERVER['HTTP_X_VERCEL_FORWARDED_FOR'])[0]);}}
if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; connect-src 'self'; frame-src https://maps.google.com https://www.google.com; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'");
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') { header('Strict-Transport-Security: max-age=31536000'); }
    header('Cache-Control: private, no-store');
    $mediaPath=(string)parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);
    if(cloud_database()&&preg_match('~^/assets/uploads/[a-f0-9]{32}\.(webp|png|jpg|jpeg|svg)$~',$mediaPath)){
        if(!in_array($_SERVER['REQUEST_METHOD']??'GET',['GET','HEAD'],true)){http_response_code(405);header('Allow: GET, HEAD');exit;}
        serve_cloud_media($mediaPath);
    }
    if(cloud_database()){require_once __DIR__.'/database-session.php';session_set_save_handler(new DatabaseSession(),true);}
    session_name('hukuk_session');
    session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Lax', 'path'=>'/']);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function db(): PDO {
    static $db;
    if ($db) return $db;
    $db=database_connection();
    if(cloud_database()&&getenv('HUKUK_SETUP')!=='1'){
        migrate_entry_presentation($db);
        migrate_natural_hero($db);
        migrate_owner_profile($db);
        migrate_remove_press($db);
        migrate_site_controls($db);
        return $db;
    }
    initialize_database($db);
    migrate_entry_presentation($db);
    if (!(int)$db->query('SELECT COUNT(*) FROM settings')->fetchColumn()) {
        require __DIR__ . '/seed.php';
        seed($db);
    }
    require_once __DIR__.'/migrations.php';
    migrate_reference_design($db);
    migrate_minimal_redesign($db);
    migrate_cinar_theme($db);
    migrate_editorial_theme($db);
    migrate_juris_home($db);
    migrate_navy_palette($db);
    migrate_arial_font($db);
    migrate_natural_hero($db);
    migrate_owner_profile($db);
    migrate_remove_press($db);
    migrate_site_controls($db);
    return $db;
}
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function &settings_cache(): array {
    static $values=null;
    if($values===null)$values=db()->query('SELECT key,value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    return $values;
}
function setting(string $key, string $fallback=''): string {
    if($key==='site_url'&&getenv('HUKUK_SITE_URL'))return rtrim((string)getenv('HUKUK_SITE_URL'),'/');
    if($key==='indexing'&&vercel_runtime()&&getenv('VERCEL_ENV')!=='production')return '0';
    $values=settings_cache();return array_key_exists($key,$values)?(string)$values[$key]:$fallback;
}
function save_setting(string $key, string $value): void {
    db()->prepare('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')->execute([$key,$value]);
    $values=&settings_cache();$values[$key]=$value;
}
function scalar_input(array $source, string $key, string $default=''): string { return isset($source[$key]) && is_scalar($source[$key]) ? trim((string)$source[$key]) : $default; }
function input(string $key, string $default=''): string { return scalar_input($_POST,$key,$default); }
function query(string $key, string $default=''): string { return scalar_input($_GET,$key,$default); }
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function verify_csrf(): void { if (!hash_equals(csrf(), input('csrf'))) { http_response_code(419); exit('Oturum doğrulanamadı. Sayfayı yenileyip tekrar deneyin.'); } }
function redirect(string $url): never { header('Location: '.$url, true, 303); exit; }
function flash(string $text, string $kind='success'): void { $_SESSION['flash']=['text'=>$text,'kind'=>$kind]; }
function flash_html(): string {
    $f=$_SESSION['flash']??null; unset($_SESSION['flash']);
    return $f ? '<div class="notice '.e($f['kind']).'" role="status">'.e($f['text']).'</div>' : '';
}
function slugify(string $title): string {
    $s=mb_strtolower(strtr($title,['İ'=>'i','I'=>'i','ı'=>'i','Ş'=>'s','ş'=>'s','Ğ'=>'g','ğ'=>'g','Ü'=>'u','ü'=>'u','Ö'=>'o','ö'=>'o','Ç'=>'c','ç'=>'c']), 'UTF-8');
    return trim(preg_replace('/[^a-z0-9]+/', '-', $s) ?? '', '-');
}
function entries(string $type, bool $public=true): array {
    $sql='SELECT * FROM entries WHERE type=?';
    if ($public) $sql.=" AND status='published' AND published_at<=?";
    $sql.=$type==='article' ? ' ORDER BY published_at DESC, id DESC' : ' ORDER BY sort_order ASC, id ASC';
    $q=db()->prepare($sql);$q->execute($public?[$type,date('Y-m-d H:i:s')]:[$type]);return $q->fetchAll();
}
function entry(string $type,string $slug, bool $public=true): ?array {
    $q=db()->prepare('SELECT * FROM entries WHERE type=? AND slug=?'.($public?" AND status='published' AND published_at<=?":''));
    $q->execute($public?[$type,$slug,date('Y-m-d H:i:s')]:[$type,$slug]); return $q->fetch() ?: null;
}
function entry_url(array $entry): string {
    return match($entry['type']) {'article'=>'/makaleler/'.$entry['slug'], 'practice'=>'/calisma-alanlari/'.$entry['slug'], 'team'=>'/avukatlar/'.$entry['slug'], 'page'=>$entry['slug']==='kurumsal'?'/kurumsal':'/sayfa/'.$entry['slug'], default=>safe_url($entry['link']??'/')};
}
function sync_entry_identity(array $before,array $after): void {
    if($before['slug']!==$after['slug']&&in_array($before['type'],['article','practice','page','team'],true)){
        db()->prepare('INSERT INTO entry_redirects(type,slug,entry_id) VALUES(?,?,?) ON CONFLICT(type,slug) DO UPDATE SET entry_id=excluded.entry_id')->execute([$before['type'],$before['slug'],$before['id']]);
        db()->prepare("UPDATE entries SET link=? WHERE type='menu' AND link=?")->execute([entry_url($after+['type'=>$before['type']]),entry_url($before)]);
        $oldPath=entry_url($before);$newPath=entry_url($after+['type'=>$before['type']]);
        $policy=db()->prepare('SELECT * FROM seo_urls WHERE path=?');$policy->execute([$oldPath]);
        if($saved=$policy->fetch()){
            db()->prepare('INSERT INTO seo_urls(path,sitemap_enabled,noindex,lastmod,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(path) DO NOTHING')->execute([$newPath,$saved['sitemap_enabled'],$saved['noindex'],$saved['lastmod'],date('Y-m-d H:i:s')]);
            db()->prepare('DELETE FROM seo_urls WHERE path=?')->execute([$oldPath]);
        }
    }
    if($before['type']==='team'&&$before['title']!==$after['title'])db()->prepare("UPDATE entries SET author=? WHERE type='article' AND author=?")->execute([$after['title'],$before['title']]);
}
function safe_url(string $url): string {
    if (preg_match('~^/(?!/)[^\\\\\x00-\x20]*$~', $url)) return $url;
    if (filter_var($url,FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['https','http'],true)) return $url;
    return '/';
}
function safe_image(string $url): string {
    return !str_contains($url,'..') && preg_match('~^/assets/(images|uploads)/[a-zA-Z0-9_/-]+\.(jpg|jpeg|png|webp|gif|svg)$~',$url) ? $url : '/assets/images/justice-hero.jpg';
}
function text_markup(string $text): string {
    $lines=preg_split('/\R/',trim($text)); $html='';$list=false;
    foreach($lines as $line) {
        $line=trim($line);
        if (str_starts_with($line,'- ')) { if(!$list){$html.='<ul>';$list=true;} $html.='<li>'.inline_markup(substr($line,2)).'</li>'; continue; }
        if($list){$html.='</ul>';$list=false;}
        if(!$line) continue;
        if(preg_match('/^(#{2,3})\s+(.+)$/u',$line,$m)) {$tag=strlen($m[1])===2?'h2':'h3';$html.='<'.$tag.'>'.inline_markup($m[2]).'</'.$tag.'>';}
        else $html.='<p>'.inline_markup($line).'</p>';
    }
    return $html.($list?'</ul>':'');
}
function inline_markup(string $text): string {
    $text=e($text); $text=preg_replace('/\*\*(.+?)\*\*/u','<strong>$1</strong>',$text)??$text;
    return preg_replace_callback('~\[([^\]]+)\]\((https?://[^\s)]+|/(?!/)[^\s)]+)\)~',fn($m)=>'<a href="'.e(safe_url(html_entity_decode($m[2],ENT_QUOTES,'UTF-8'))).'" rel="noopener noreferrer">'.$m[1].'</a>',$text)??$text;
}
function date_tr(string $date): string {
    $months=['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    $t=strtotime($date); if(!$t)return '';return date('j',$t).' '.$months[(int)date('n',$t)].' '.date('Y',$t);
}
function read_minutes(string $body): int { return max(1,(int)ceil(count(preg_split('/\s+/u',$body))/180)); }
function audit(string $action): void { db()->prepare('INSERT INTO audit(actor,action,created_at) VALUES(?,?,?)')->execute([$_SESSION['user_name']??'Sistem',$action,date('Y-m-d H:i:s')]); }
function rate_bucket(string $context): string { return hash('sha256',$context.'|'.($_SERVER['REMOTE_ADDR']??'cli').'|'.setting('private_salt')); }
function rate_limited(string $bucket,int $limit,int $seconds): bool {
    db()->prepare('DELETE FROM attempts WHERE created_at<?')->execute([time()-86400]);
    $q=db()->prepare('SELECT COUNT(*) FROM attempts WHERE bucket=? AND created_at>?');$q->execute([$bucket,time()-$seconds]);return (int)$q->fetchColumn()>=$limit;
}
function rate_hit(string $bucket): void { db()->prepare('INSERT INTO attempts(bucket,created_at) VALUES(?,?)')->execute([$bucket,time()]); }
function current_user(): ?array {
    if(empty($_SESSION['user_id'])) return null;
    if(time()-(int)($_SESSION['last_active']??0)>3600){unset($_SESSION['user_id']);return null;}
    $q=db()->prepare('SELECT id,email,name,version FROM users WHERE id=?');$q->execute([$_SESSION['user_id']]);$u=$q->fetch();
    if(!$u || (int)$u['version']!==(int)($_SESSION['user_version']??0)){unset($_SESSION['user_id']);return null;}
    $_SESSION['last_active']=time();return $u;
}
function require_user(): array { $u=current_user();if(!$u)redirect('/admin/login.php');return $u; }
function icon(string $name, string $class=''): string {
    $paths=[
        'arrow'=>'<path d="M5 12h14m-5-5 5 5-5 5"/>', 'arrow-up'=>'<path d="M6 18 18 6M6 6h12v12"/>',
        'scale'=>'<path d="M12 3v17M8 21h8M4 7h16M4 7 1 14h6L4 7Zm16 0-3 7h6l-3-7Z"/><circle cx="12" cy="5" r="2"/>',
        'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'phone'=>'<path d="m7 3 3 5-3 3a15 15 0 0 0 6 6l3-3 5 3c0 4-3 5-6 4C8 19 5 16 3 9 2 6 3 3 7 3Z"/>',
        'whatsapp'=>'<path d="M20.5 11.5a8.5 8.5 0 0 1-12.4 7.5L3 20.5l1.5-5.1A8.5 8.5 0 1 1 20.5 11.5Z"/><path d="m8.1 7.8 1.7 2-1 1.1c.8 1.4 2 2.6 3.4 3.4l1.1-1 2 1.7-.8 1.3c-3.6.8-8.1-3.7-7.3-7.3l.9-1.2Z"/>',
        'pin'=>'<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2"/>',
        'mail'=>'<rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 6 9 7 9-7"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'shield'=>'<path d="M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Z"/><path d="m8 12 3 3 5-6"/>',
        'building'=>'<path d="m3 8 9-5 9 5H3Zm1 13h16M6 10v8m6-8v8m6-8v8M3 18h18"/>',
        'briefcase'=>'<rect x="3" y="7" width="18" height="14" rx="1"/><path d="M8 7V3h8v4M3 12l9 3 9-3M12 12v5"/>',
        'users'=>'<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m2 5a5 5 0 0 1 3 5"/>',
        'home'=>'<path d="m3 10 9-7 9 7v11h-7v-7h-4v7H3V10Z"/>',
        'book'=>'<path d="M12 5C8 2 3 3 3 3v16s5-1 9 2c4-3 9-2 9-2V3s-5-1-9 2v16"/>',
        'file'=>'<path d="M14 3H5v18h14V8l-5-5Zm0 0v5h5M8 12h8m-8 4h6"/>',
        'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>', 'close'=>'<path d="m5 5 14 14M5 19 19 5"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>', 'chevron'=>'<path d="m9 5 7 7-7 7"/>',
        'plus'=>'<path d="M12 4v16M4 12h16"/>', 'edit'=>'<path d="m15 4 5 5M4 20l5-1L21 7l-4-4L5 15l-1 5Z"/>',
        'settings'=>'<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3"/><circle cx="16" cy="17" r="3"/>',
        'image'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="2"/><path d="m3 18 5-5 4 4 4-7 5 8"/>',
        'logout'=>'<path d="M10 3H4v18h6m4-14 5 5-5 5m-6-5h12"/>',
        'download'=>'<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>',
    ];
    return '<svg class="icon '.e($class).'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['scale']).'</svg>';
}
