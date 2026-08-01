<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Presentation;

final class StructuredContentRenderer
{
    /** @param array<string, mixed> $body */
    public function render(array $body): string
    {
        // Authoring stores a canonical list of blocks; older imports may wrap
        // that list in a "blocks" member. Support both without changing the
        // reviewed payload.
        $blocks=array_is_list($body)?$body:($body['blocks']??[]);
        if(!is_array($blocks)){return '';}
        $html=''; foreach($blocks as $block){if(!is_array($block)){continue;} $type=(string)($block['type']??'');
            if($type==='paragraph'){$html.='<p>'.esc_html((string)($block['text']??'')).'</p>';}
            elseif($type==='heading'){$level=max(2,min(4,(int)($block['level']??2)));$html.='<h'.$level.'>'.esc_html((string)($block['text']??'')).'</h'.$level.'>';}
            elseif($type==='list'){ $items=is_array($block['items']??null)?$block['items']:preg_split('/\R/',(string)($block['text']??''));$html.='<ul>';foreach($items?:[] as $item){if(trim((string)$item)!==''){$html.='<li>'.esc_html((string)$item).'</li>';}}$html.='</ul>';}
            elseif($type==='callout'){$html.='<aside class="atlas-resource-callout"><strong>'.esc_html((string)($block['label']??__('Important','atlas-platform'))).'</strong><p>'.esc_html((string)($block['text']??'')).'</p></aside>';}
        } return $html;
    }
}
