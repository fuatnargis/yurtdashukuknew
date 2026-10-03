<?php
declare(strict_types=1);
function category_symbol(string $title): string {
    $s=slugify($title);
    foreach(['aile'=>'users','is-hukuku'=>'briefcase','ticaret'=>'building','gayrimenkul'=>'home','ceza'=>'scale','miras'=>'book','bilisim'=>'shield','sozlesme'=>'file'] as $part=>$symbol)if(str_contains($s,$part))return $symbol;
    return 'book';
}
function category_directory(array $articles): void {
    $groups=[];foreach($articles as $article){$name=$article['category']?:'Genel';$groups[$name][]=$article;}ksort($groups);
    if(!$groups)return;
    ?><section class="category-directory container" aria-labelledby="category-directory-title"><div class="directory-heading"><span class="eyebrow">MAKALE KATEGORİLERİ</span><h2 id="category-directory-title"><?=e(setting('category_directory_title'))?></h2><p><?=e(setting('category_directory_text'))?></p></div><div class="category-grid"><?php foreach($groups as $name=>$items):?><article class="category-card"><div class="category-card-heading"><span class="category-emblem"><?=icon(category_symbol($name))?></span><h3><a href="/makaleler?<?=e(http_build_query(['kategori'=>$name]))?>#yayinlar"><?=e($name)?></a></h3><span class="category-count"><?=count($items)?></span></div><ul><?php foreach(array_slice($items,0,3) as $a):?><li><a href="<?=e(entry_url($a))?>"><?=e($a['title'])?><?=icon('chevron')?></a></li><?php endforeach;?></ul><a class="category-more" href="/makaleler?<?=e(http_build_query(['kategori'=>$name]))?>#yayinlar">Kategoriyi inceleyin <?=icon('arrow')?></a></article><?php endforeach;?></div></section><?php
}
function office_panel(): void {
    if(setting('show_office')!=='1')return;
    ?><section class="office-section" data-home-section="office"><div class="container"><div class="office-panel">
    <div class="office-panel-copy"><span class="eyebrow"><?=e(setting('office_eyebrow'))?> · <?=e(setting('city'))?></span><h2><?=nl2br(e(setting('office_title')))?></h2><div class="office-text"><?=rich_text(setting('office_text'))?></div><a class="text-link" href="/kurumsal"><?=e(ui('office_about'))?> <?=icon('arrow-up')?></a></div>
    <div class="office-panel-contact"><span class="office-symbol"><?=icon('building')?></span><h3><?=e(ui('office_info'))?></h3><address><?=nl2br(e(setting('address')))?></address><p class="office-hours"><?=icon('clock')?><?=e(setting('office_hours'))?></p><div class="office-channels">
    <?php if(setting('phone')):?><a href="tel:<?=e(preg_replace('/[^+0-9]/','',setting('phone')))?>"><?=icon('phone')?><?=e(setting('phone'))?></a><?php endif;?>
    <?php if(setting('email')):?><a href="mailto:<?=e(setting('email'))?>"><?=icon('mail')?><?=e(setting('email'))?></a><?php endif;?></div><a class="button button-outline" href="/iletisim">İletişime geçin <?=icon('arrow-up')?></a></div>
    </div></div></section><?php
}
function practice_photo(array $p,int $i): void {
    $fallback=['/assets/images/library.jpg','/assets/images/justice-hero.jpg','/assets/images/architecture.jpg'];
    ?><a class="practice-photo-card" href="<?=e(entry_url($p))?>"><div class="practice-photo"><img src="<?=e(safe_image($p['image']?:$fallback[$i%3]))?>" alt="" loading="lazy" width="400" height="400"><span class="practice-photo-icon"><?=icon($p['category'])?></span></div><h3><?=e($p['title'])?></h3><span><?=e(ui('practice_detail'))?> <?=icon('arrow-up')?></span></a><?php
}
