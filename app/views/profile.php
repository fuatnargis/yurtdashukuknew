<?php declare(strict_types=1);
$written=profile_articles($detail);
$facts=profile_details($detail);
?>
<section class="lawyer-profile" aria-labelledby="lawyer-name"><div class="container">
    <?php simple_breadcrumb([ui('founder_title')=>'/avukatlar',$detail['title']=>'']); ?>
    <div class="lawyer-profile-grid">
        <aside class="lawyer-profile-aside">
            <div class="lawyer-profile-portrait"><?php if($detail['image']):?><img src="<?=e(safe_image($detail['image']))?>" alt="<?=e($detail['title'])?>" width="640" height="760" fetchpriority="high"><?php else:?><span class="lawyer-monogram" aria-hidden="true"><?=e(profile_initials($detail['title']))?></span><?php endif;?></div>
            <div class="lawyer-identity"><span><?=e($detail['category']?:'Avukat')?></span><strong><?=e($detail['title'])?></strong><small><?=e(setting('brand'))?> · <?=e(setting('city'))?></small></div>
            <?php if($facts):?><section class="profile-facts" aria-labelledby="profile-facts-title"><h2 id="profile-facts-title"><?=e(ui('profile_facts'))?></h2><dl><?php foreach($facts as $key=>$value):if(!$value)continue;?><div><dt><?=e(ui('profile_'.$key))?></dt><dd><?=e($key==='started'?date_tr($value):$value)?></dd></div><?php endforeach;?></dl></section><?php endif;?>
        </aside>
        <div class="lawyer-profile-copy">
            <span class="eyebrow"><?=e($detail['category']?:'Avukat')?> · <?=e(setting('city'))?></span>
            <h1 id="lawyer-name"><?=e($detail['title'])?></h1>
            <?php if($detail['excerpt']):?><p class="lawyer-profile-lead"><?=e($detail['excerpt'])?></p><?php endif;?>
            <nav class="profile-jump-links" aria-label="<?=e(ui('founder_title'))?>"><a href="#profile-bio"><?=e(ui('profile_bio'))?></a><a href="#lawyer-publications-title"><?=e(ui('profile_publications_label'))?></a><a href="#profile-contact"><?=e(ui('profile_contact'))?></a></nav>
            <div id="profile-bio" class="prose"><h2><?=e(ui('profile_bio'))?></h2><?=content_html($detail)?></div>
            <section id="profile-contact" class="lawyer-profile-contact"><h2><?=e(ui('profile_contact'))?></h2>
                <address><?=nl2br(e(setting('address')))?></address>
                <div class="lawyer-profile-actions"><?php if(setting('phone')):?><a class="button" href="<?=e(phone_url(setting('phone')))?>"><?=icon('phone')?> <?=e(setting('phone'))?></a><?php endif;if(setting('email')):?><a class="button button-outline" href="mailto:<?=e(setting('email'))?>"><?=icon('mail')?> <?=e(ui('contact_email_label'))?></a><?php endif;?></div>
                <?php if(setting('whatsapp')):?><a class="text-link" href="<?=e(whatsapp_url(setting('whatsapp')))?>" target="_blank" rel="noopener noreferrer"><?=e(ui('whatsapp_contact'))?> <?=icon('arrow-up')?></a><?php endif;?>
                <?php if(setting('address')):?><a class="text-link" href="<?=e(map_open_url())?>" target="_blank" rel="noopener noreferrer"><?=e(ui('profile_map'))?> <?=icon('arrow-up')?></a><?php endif;?>
            </section>
        </div>
    </div>
</div></section>
<section class="lawyer-publications" aria-labelledby="lawyer-publications-title"><div class="container"><div class="section-heading"><div><span class="eyebrow"><?=e(ui('profile_publications_label'))?></span><h2 id="lawyer-publications-title"><?=e($detail['title'])?> <?=e(ui('profile_publications'))?></h2></div><a class="text-link" href="/makaleler"><?=e(ui('all_publications'))?> <?=icon('arrow')?></a></div><?php if($written):?><div class="article-grid"><?php foreach($written as $article)article_card($article);?></div><?php else:?><p><?=e(ui('profile_empty'))?></p><?php endif;?></div></section>
