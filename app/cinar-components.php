<?php
declare(strict_types=1);
function service_card(array $p,int $i=0): void { ?>
<article class="service-card"><a class="service-image" href="<?=e(entry_url($p))?>" tabindex="-1" aria-hidden="true"><img src="<?=e(safe_image($p['image']))?>" alt="" width="600" height="350" loading="lazy"><span><?=icon($p['category']?:category_symbol($p['title']))?></span></a><div class="service-body"><span class="service-number"><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><h3><a href="<?=e(entry_url($p))?>"><?=e($p['title'])?></a></h3><p><?=e($p['excerpt'])?></p><a class="text-link" href="<?=e(entry_url($p))?>"><?=e(ui('practice_detail'))?> <?=icon('arrow')?></a></div></article>
<?php }
function faq_list(): void { ?><div class="faq-list"><?php foreach(entries('faq') as $i=>$f):?><details><summary><span class="faq-number"><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><?=e($f['title'])?><span class="faq-plus">+</span></summary><div><?=rich_text($f['body'])?></div></details><?php endforeach;?></div><?php }
function public_navigation(string $canonical): void {
    foreach(entries('menu') as $nav){
        $url=safe_url($nav['link']);$active=$canonical===$url||($url!=='/'&&str_starts_with($canonical,$url.'/'));
        $children=$url==='/calisma-alanlari'&&setting('show_practice_dropdown')==='1'?entries('practice'):[];
        ?><div class="nav-item"><a href="<?=e($url)?>" <?=$active?'aria-current="page"':''?>><?=e($nav['title'])?></a><?php if($children):?><button type="button" class="submenu-toggle" aria-label="<?=e($nav['title'])?> alt menüsünü aç" aria-expanded="false" aria-controls="submenu-<?=$nav['id']?>"><?=icon('chevron')?></button><div class="nav-submenu" id="submenu-<?=$nav['id']?>" hidden><?php foreach($children as $p):?><a href="<?=e(entry_url($p))?>"><?=e($p['title'])?></a><?php endforeach;?></div><?php endif;?></div><?php
    }
}
