<?php
declare(strict_types=1);

function valid_public_path(string $path,bool $source=false): bool {
    if($path==='/' )return !$source;
    if(strlen($path)>500||!preg_match('~^/[a-z0-9][a-z0-9._-]*(?:/[a-z0-9][a-z0-9._-]*)*$~D',$path)||str_contains($path,'..'))return false;
    if(preg_match('~^/(admin|app|storage|tools|tests|api|assets|node_modules|template-original)(?:/|$)~',$path))return false;
    return !in_array($path,['/sitemap.xml','/robots.txt','/rss.xml','/llms.txt'],true);
}
function legacy_redirect_path(string $path): ?string {
    $aliases=['/sayfa/kurumsal'=>'/kurumsal','/kanunlar'=>'/','/ekibimiz'=>'/avukatlar','/index.php'=>'/','/index.html'=>'/','/makaleler.php'=>'/makaleler','/blog-list.html'=>'/makaleler','/blog.html'=>'/makaleler','/about-us.html'=>'/kurumsal','/practice-areas.html'=>'/calisma-alanlari','/contact.html'=>'/iletisim','/iletisim.php'=>'/iletisim','/kurumsal.php'=>'/kurumsal'];
    if(isset($aliases[$path]))return $aliases[$path];
    if($path==='/basinda-biz'||preg_match('~^/basinda-biz/[a-z0-9-]+$~',$path))return '/';
    if(preg_match('~^/(?:home-[a-z0-9-]+|top-header-[23])\.html$~',$path))return '/';
    if(preg_match('~^/(?:about-[a-z0-9-]+|gallery-1|testimonials)\.html$~',$path))return '/kurumsal';
    if(preg_match('~^/attorneys[a-z0-9-]*\.html$~',$path))return '/';
    if(preg_match('~^/(?:practice-[a-z0-9-]+|case[a-z0-9-]*)\.html$~',$path))return '/calisma-alanlari';
    if(preg_match('~^/blog[a-z0-9-]*\.html$~',$path))return '/makaleler';
    if(preg_match('~^/(?:contact[a-z0-9-]*|pricing)\.html$~',$path))return '/iletisim';
    if($path==='/faq.html')return '/#sik-sorulan-sorular';
    return null;
}
function automatic_redirect_path(string $path,bool $includeScheduled=false): ?string {
    if($legacy=legacy_redirect_path($path))return $legacy;
    if(!preg_match('~^/(makaleler|calisma-alanlari|sayfa|avukatlar)/([a-z0-9-]+)$~',$path,$m))return null;
    $type=['makaleler'=>'article','calisma-alanlari'=>'practice','sayfa'=>'page','avukatlar'=>'team'][$m[1]];
    if(entry($type,$m[2]))return null;
    $q=db()->prepare("SELECT e.* FROM entry_redirects r JOIN entries e ON e.id=r.entry_id WHERE r.type=? AND r.slug=? AND e.status='published'".($includeScheduled?'':' AND e.published_at<=?'));
    $q->execute($includeScheduled?[$type,$m[2]]:[$type,$m[2],date('Y-m-d H:i:s')]);$target=$q->fetch();
    return $target?entry_url($target):null;
}
function managed_redirects(): array {
    static $cache=null;
    if($cache===null){$cache=[];foreach(db()->query('SELECT * FROM url_redirects WHERE enabled=1')->fetchAll() as $row)$cache[$row['source_path']]=$row;}
    return $cache;
}
function assert_redirect_graph(array $rows): void {
    $edges=[];
    foreach($rows as $row){
        if(!is_array($row)||!is_string($row['source_path']??null)||!is_string($row['target_path']??null)||!is_scalar($row['status_code']??null)||!is_scalar($row['enabled']??null)||!valid_public_path($row['source_path'],true)||!valid_public_path($row['target_path'])||!in_array((string)$row['status_code'],['301','302'],true)||!in_array((string)$row['enabled'],['0','1'],true))throw new RuntimeException('Geçerli site içi adresler, 301/302 kodu ve yayın durumu seçin.');
        if($row['source_path']===$row['target_path'])throw new RuntimeException('Kaynak ve hedef adres aynı olamaz.');
        if((int)$row['enabled'])$edges[$row['source_path']]=$row['target_path'];
    }
    foreach($edges as $source=>$target){
        $visited=[$source=>true];
        for($i=0;$i<100;$i++){
            $target=(string)parse_url($target,PHP_URL_PATH);
            if(isset($visited[$target]))throw new RuntimeException('Yönlendirme döngüsü oluşuyor. Farklı bir hedef seçin.');
            $visited[$target]=true;
            // Also reject cycles that would activate when a scheduled article is published.
            $next=$edges[$target]??automatic_redirect_path($target,true);
            if(!$next)break;
            $target=$next;
            if($i===99)throw new RuntimeException('Yönlendirme zinciri çok uzun.');
        }
    }
}
function seo_url_policies(): array {
    static $cache=null;
    if($cache===null){$cache=[];foreach(db()->query('SELECT * FROM seo_urls')->fetchAll() as $row)$cache[$row['path']]=$row;}
    return $cache;
}
function seo_url_noindex(string $path): bool {return (int)(seo_url_policies()[$path]['noindex']??0)===1;}
function sitemap_candidates(): array {
    $pages=[];$latest=(string)(db()->query("SELECT MAX(updated_at) FROM entries WHERE status='published' AND published_at<='".date('Y-m-d H:i:s')."'")->fetchColumn()?:date('Y-m-d'));
    foreach(SEO_ROUTES as $route=>$info)$pages[$info['path']]=['path'=>$info['path'],'title'=>$info['label'],'lastmod'=>substr($latest,0,10),'image'=>'','noindex'=>seo_route_meta($route)['noindex']];
    $pages['/kurumsal']=['path'=>'/kurumsal','title'=>'Hakkımızda','lastmod'=>substr($latest,0,10),'image'=>'','noindex'=>false];
    foreach(['article','practice','page','team'] as $type)foreach(entries($type) as $row){
        if($type==='page'&&$row['slug']==='ekibimiz')continue;
        $path=entry_url($row);
        $pages[$path]=['path'=>$path,'title'=>$row['title'],'lastmod'=>substr($row['updated_at']?:$row['published_at'],0,10),'image'=>$row['image'],'noindex'=>false];
    }
    ksort($pages);return $pages;
}
function sitemap_rows(): array {
    $out=[];$policies=seo_url_policies();$redirects=managed_redirects();
    foreach(sitemap_candidates() as $path=>$page){
        $policy=$policies[$path]??[];
        if(isset($redirects[$path])||$page['noindex']||!empty($policy['noindex'])||(isset($policy['sitemap_enabled'])&&!(int)$policy['sitemap_enabled']))continue;
        if(!empty($policy['lastmod']))$page['lastmod']=$policy['lastmod'];
        $out[]=$page;
    }
    return $out;
}
function valid_lastmod(string $value): bool {
    if($value==='')return true;
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
    return $date&&$date->format('Y-m-d')===$value&&$value<=date('Y-m-d');
}
function validate_url_policy(array $row): void {
    if(!is_string($row['path']??null)||!is_scalar($row['sitemap_enabled']??null)||!is_scalar($row['noindex']??null)||!valid_public_path($row['path'])||!in_array((string)$row['sitemap_enabled'],['0','1'],true)||!in_array((string)$row['noindex'],['0','1'],true)||!is_string($row['lastmod']??null)||!valid_lastmod($row['lastmod']))throw new RuntimeException('Geçerli bir site adresi ve son değişiklik tarihi yazın.');
}
function office_locality(): string {return implode(', ',array_filter([setting('district'),setting('city')]));}
