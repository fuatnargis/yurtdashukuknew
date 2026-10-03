<?php
declare(strict_types=1);
// Makale sayfaları bileşenleri: kategori merkezi, liste kartları, içindekiler, yazar kutusu.
function simple_breadcrumb(array $items): void {?>
    <nav class="crumb" aria-label="İçerik yolu"><a href="/"><?=e(setting('brand'))?></a><?php foreach($items as $label=>$url):?><span class="crumb-sep">-</span><?php if($url):?><a href="<?=e($url)?>"><?=e($label)?></a><?php else:?><span class="crumb-last"><?=e($label)?></span><?php endif;endforeach;?></nav><?php
}
function category_url(string $name): string {return '/makaleler?'.http_build_query(['kategori'=>$name]);}
function hub_card(string $name,int $count): void {
    $text=str_replace('{kategori}',$name,ui('hub_card_text'));?>
    <a class="hub-card" href="<?=e(category_url($name))?>"><span class="hub-icon"><?=icon(category_symbol($name))?></span><h2><?=e($name)?></h2><p><?=e($text)?></p><span class="hub-count"><?=$count?> <?=e(ui('article_count_suffix'))?></span></a><?php
}
function post_card(array $a,bool $compact=false): void {?>
    <article class="post-card <?=$compact?'post-card-compact':''?>"><a class="post-card-img" href="<?=e(entry_url($a))?>" tabindex="-1" aria-hidden="true"><img src="<?=e(safe_image($a['image']))?>" alt="" loading="lazy" width="800" height="400"></a><div class="post-card-body"><h3><a href="<?=e(entry_url($a))?>"><?=e($a['title'])?></a></h3><?php if(!$compact&&$a['excerpt']):?><p><?=e(seo_plain($a['excerpt'],190))?></p><?php endif;?><?php $author=author_of($a);?><span class="post-card-author"><?=e(ui('author_label'))?> <?php if($author):?><a href="<?=e(entry_url($author))?>"><?=e($author['title'])?></a><?php else:?><?=e($a['author']?:setting('brand'))?><?php endif;?></span><span class="post-card-line" aria-hidden="true"></span></div></article><?php
}
/** Gövde HTML'ine başlık kimlikleri ekler ve içindekiler listesini döndürür. */
function prose_with_toc(array $article): array {
    $html=content_html($article);$toc=[];$used=[];
    $html=preg_replace_callback('~<(h2|h3)(?:\s[^>]*)?>(.*?)</\1>~su',function($m)use(&$toc,&$used){
        $plain=trim(html_entity_decode(strip_tags($m[2]),ENT_QUOTES|ENT_HTML5,'UTF-8'));$id=slugify($plain)?:'bolum';$base=$id;$n=2;
        while(isset($used[$id])){$id=$base.'-'.$n++;}$used[$id]=true;
        $toc[]=['id'=>$id,'title'=>$plain,'level'=>$m[1]==='h2'?2:3];
        return '<'.$m[1].' id="'.e($id).'">'.$m[2].'</'.$m[1].'>';
    },$html);
    return ['html'=>$html,'toc'=>$toc];
}
function toc_html(array $toc,string $class=''): void {
    $top=array_values(array_filter($toc,fn($t)=>$t['level']===2));if(count($top)<2)$top=$toc;if(!$top)return;?>
    <nav class="toc <?=e($class)?>" aria-label="<?=e(ui('toc_title'))?>"><span class="toc-title"><?=e(mb_strtoupper(ui('toc_title'),'UTF-8'))?></span><ol class="toc-list"><?php foreach($top as $i=>$t):?><li class="<?=$t['level']===3?'toc-sub':''?>"><a href="#<?=e($t['id'])?>"><span class="toc-num"><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><span><?=e($t['title'])?></span></a></li><?php endforeach;?></ol></nav><?php
}
function author_of(array $article): ?array {
    $name=trim((string)$article['author']);if(!$name)return null;
    foreach(entries('team') as $p)if(profile_name_key($p['title'])===profile_name_key($name))return $p;
    return null;
}
function author_card(array $article,bool $wide=false): void {
    $p=author_of($article);$name=$article['author']?:setting('brand');
    $img=$p?($p['image']?safe_image($p['image']):''):(setting('logo')?safe_image(setting('logo')):'');
    $text=$p?($p['excerpt']?:seo_plain($p['body'],220)):setting('footer_text');
    $url=$p?entry_url($p):'/kurumsal';?>
    <a class="author-card <?=$wide?'author-card-wide':''?>" href="<?=e($url)?>"><?php if($img):?><span class="author-photo"><img src="<?=e($img)?>" alt="<?=e($name)?>" width="112" height="112" loading="lazy"></span><?php else:?><span class="author-photo author-initial"><?=e(mb_substr($name,0,1))?></span><?php endif;?><span class="author-body"><strong class="author-name"><?=e($name)?></strong><?php if($p&&$p['category']):?><span class="author-role"><?=e($p['category'])?></span><?php endif;?><span class="author-description"><?=e($text)?></span><span class="button button-small"><?=e($p?ui('author_button'):'Büromuz hakkında')?></span></span></a><?php
}
function side_list(string $title,array $items): void {if(!$items)return;?>
    <div class="side-box"><h2 class="side-title"><?=e($title)?></h2><ul class="side-list"><?php foreach($items as $a):?><li><a href="<?=e(entry_url($a))?>"><time datetime="<?=e(substr($a['published_at'],0,10))?>"><?=date_tr($a['published_at'])?></time><?=e($a['title'])?></a></li><?php endforeach;?></ul></div><?php
}
