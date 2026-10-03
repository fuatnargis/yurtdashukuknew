<?php
declare(strict_types=1);

function sanitize_svg(string $bytes): string {
    $error='SVG yalnızca güvenli vektör şekilleri içermelidir; betik, stil, harici görsel ve bağlantıları kaldırın.';
    if(strlen($bytes)>512*1024||stripos($bytes,'<!DOCTYPE')!==false||stripos($bytes,'<!ENTITY')!==false)throw new RuntimeException($error);
    $previous=libxml_use_internal_errors(true);
    $source=new DOMDocument();
    $source->resolveExternals=false;
    $source->substituteEntities=false;
    try {
        if(!$source->loadXML($bytes,LIBXML_NONET|LIBXML_NOBLANKS)||$source->doctype||!$source->documentElement||$source->documentElement->localName!=='svg')throw new RuntimeException($error);
        $xpath=new DOMXPath($source);
        if($xpath->query('//processing-instruction()')->length)throw new RuntimeException($error);
        $elements=['svg','g','path','rect','circle','ellipse','line','polyline','polygon','title','desc','defs','linearGradient','radialGradient','stop','clipPath'];
        $attributes=['viewBox','preserveAspectRatio','width','height','x','y','x1','y1','x2','y2','cx','cy','r','rx','ry','d','points','transform','fill','stroke','stroke-width','stroke-linecap','stroke-linejoin','stroke-miterlimit','stroke-dasharray','stroke-dashoffset','fill-rule','clip-rule','clip-path','opacity','fill-opacity','stroke-opacity','id','gradientUnits','gradientTransform','offset','stop-color','stop-opacity','fx','fy','fr','role','aria-label','aria-labelledby'];
        $target=new DOMDocument('1.0','UTF-8');
        $count=0;
        $copy=function(DOMNode $node)use(&$copy,&$count,$target,$elements,$attributes,$error): ?DOMNode {
            if(++$count>20000)throw new RuntimeException($error);
            if($node instanceof DOMText)return $target->createTextNode($node->textContent);
            if($node instanceof DOMComment)return null;
            if(!$node instanceof DOMElement||!in_array($node->localName,$elements,true)||!in_array($node->namespaceURI,[null,'','http://www.w3.org/2000/svg'],true))throw new RuntimeException($error);
            $out=$target->createElementNS('http://www.w3.org/2000/svg',$node->localName);
            foreach($node->attributes as $attribute){
                $name=$attribute->nodeName;$value=$attribute->value;
                if(str_starts_with($name,'xmlns'))continue;
                if(preg_match('/^on|href|style/i',$name))throw new RuntimeException($error);
                if(!in_array($name,$attributes,true))continue;
                if(preg_match('~javascript:|data:|https?:|//|[<>]~i',$value))throw new RuntimeException($error);
                if(stripos($value,'url')!==false&&!preg_match('/^url\(#[A-Za-z_][A-Za-z0-9_.-]*\)$/',$value))throw new RuntimeException($error);
                $out->setAttribute($name,$value);
            }
            foreach($node->childNodes as $child){$result=$copy($child);if($result)$out->appendChild($result);}
            return $out;
        };
        $root=$copy($source->documentElement);
        $box=preg_split('/[\s,]+/',trim($root->getAttribute('viewBox')));
        if(count($box)!==4||count(array_filter($box,'is_numeric'))!==4)throw new RuntimeException('SVG dosyasında geçerli bir viewBox alanı olmalıdır.');
        [$x,$y,$width,$height]=array_map('floatval',$box);
        if(!is_finite($x)||!is_finite($y)||!is_finite($width)||!is_finite($height)||$width<=0||$height<=0||$width>8000||$height>8000||$width*$height>20000000)throw new RuntimeException('SVG boyutları çok büyük veya geçersiz.');
        $target->appendChild($root);
        return $target->saveXML($root);
    } finally {
        libxml_clear_errors();libxml_use_internal_errors($previous);
    }
}
