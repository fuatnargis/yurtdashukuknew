<?php
declare(strict_types=1);

/** Limited HTML for administrator-authored editorial content. Scripts and embeds never run. */
function safe_content_html(string $html): string {
    if(!class_exists(DOMDocument::class))return '<p>'.nl2br(e(strip_tags($html))).'</p>';
    $doc=new DOMDocument('1.0','UTF-8');
    $old=libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="content-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();libxml_use_internal_errors($old);
    $root=$doc->getElementById('content-root');if(!$root)return '';
    $allowed=['p','h2','h3','h4','ul','ol','li','strong','b','em','i','blockquote','a','br','hr','code','pre','img','table','thead','tbody','tr','th','td'];
    $drop=['script','style','iframe','object','embed','svg','math','form','input','button','textarea','select','video','audio','meta','link'];
    $clean=function(DOMNode $node)use(&$clean,$allowed,$drop,$doc): void {
        foreach(iterator_to_array($node->childNodes) as $child){
            if($child instanceof DOMComment){$node->removeChild($child);continue;}
            if(!$child instanceof DOMElement){if(!$child instanceof DOMText)$node->removeChild($child);continue;}
            $tag=strtolower($child->tagName);
            if(in_array($tag,$drop,true)){$node->removeChild($child);continue;}
            $clean($child);
            if(!in_array($tag,$allowed,true)){
                while($child->firstChild)$node->insertBefore($child->firstChild,$child);
                $node->removeChild($child);continue;
            }
            $href=$child->getAttribute('href');$src=$child->getAttribute('src');$alt=$child->getAttribute('alt');$title=$child->getAttribute('title');
            while($child->attributes->length)$child->removeAttributeNode($child->attributes->item(0));
            if($tag==='a'){
                if(!$href||safe_url($href)!==$href){while($child->firstChild)$node->insertBefore($child->firstChild,$child);$node->removeChild($child);continue;}
                $child->setAttribute('href',$href);
                if(str_starts_with($href,'http'))$child->setAttribute('rel','noopener noreferrer');
                if($title)$child->setAttribute('title',mb_substr($title,0,180));
            }
            if($tag==='img'){
                if(!$src||safe_image($src)!==$src){$node->removeChild($child);continue;}
                $child->setAttribute('src',$src);$child->setAttribute('alt',mb_substr($alt,0,200));$child->setAttribute('loading','lazy');
            }
        }
    };
    $clean($root);$output='';foreach($root->childNodes as $node)$output.=$doc->saveHTML($node);
    return $output;
}
function content_html(array $entry): string {
    return ($entry['body_format']??'text')==='html'?safe_content_html((string)$entry['body']):text_markup((string)$entry['body']);
}
/** Ayar metinleri: HTML etiketi içeren girdiler güvenli HTML olarak, diğerleri kolay metin biçimi olarak yorumlanır. */
function rich_text(string $text): string {
    $t=trim($text);
    return $t!==''&&preg_match('~<[a-zA-Z][^>]*>~',$t)?safe_content_html($t):text_markup($t);
}
function entry_plain(array $entry,int $limit=160): string {
    $body=(string)($entry['body']??'');
    $plain=($entry['body_format']??'text')==='html'?html_entity_decode(strip_tags(safe_content_html($body)),ENT_QUOTES|ENT_HTML5,'UTF-8'):$body;
    return seo_plain($plain,$limit);
}
