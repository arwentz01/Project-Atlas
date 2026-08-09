<?php

declare(strict_types=1);

namespace Atlas\FrontEnd\Modules;

use Atlas\Support\Fixtures;

final class PacketBuilder
{
    public static function boot(): void
    {
        add_filter('atlas_frontend_routes',[self::class,'routes']);
        add_filter('atlas_render_view',[self::class,'render'],10,2);
    }

    public static function routes(array $routes): array
    {
        $routes[]=['name'=>'packet-builder','pattern'=>'patient-resources/packet-builder','label'=>'Packet Builder','nav'=>false,'view'=>'packet-builder'];
        return $routes;
    }

    public static function render(string $content,string $view): string
    {
        if($content!==''||$view!=='packet-builder')return $content;
        return self::builder();
    }

    private static function builder(): string
    {
        if(!Fixtures::enabled())return'<div class="atlas-empty"><h1>Demo content disabled</h1></div>';
        $items=[
            'oxygen'=>['Home Oxygen: What to Expect','Respiratory','2 pages'],
            'meds'=>['Medication List: Questions to Ask','Medication Safety','1 page'],
            'followup'=>['Your Follow-Up Plan','Care Transitions','1 page'],
            'homehealth'=>['Preparing for Your First Home Health Visit','Home Health','1 page'],
        ];
        $selected=array_map('sanitize_key',(array)($_GET['packet']??['oxygen','followup']));
        $selected=array_values(array_intersect(array_keys($items),$selected));
        ob_start();?>
        <nav class="atlas-breadcrumb"><a href="<?php echo esc_url(home_url('/patient-resources'));?>">Patient Resources</a><span>/</span><span>Packet Builder</span></nav>
        <section class="atlas-page-head"><div><p class="atlas-eyebrow">Packet Builder</p><h1>Assemble the right education, not a paper avalanche.</h1><p>Select reusable patient education and preview the packet before printing. This visual build intentionally stores no patient identifiers and saves nothing to the database.</p></div><div class="atlas-count"><strong><?php echo count($selected);?></strong><span>items selected</span></div></section>
        <div class="atlas-callout"><strong>PHI-free by design</strong><span>Packets are collections of reusable education. Do not enter patient names, dates of birth, MRNs, diagnoses, or patient-specific notes.</span></div>
        <form class="atlas-tool-grid" method="get"><section class="atlas-panel"><p class="atlas-eyebrow">Choose materials</p><h2>Packet contents</h2><div class="atlas-stack"><?php foreach($items as$key=>$item):?><label class="atlas-choice"><input type="checkbox" name="packet[]" value="<?php echo esc_attr($key);?>" <?php checked(in_array($key,$selected,true));?>><span><strong><?php echo esc_html($item[0]);?></strong><span><?php echo esc_html($item[1].' · '.$item[2]);?></span></span></label><?php endforeach;?></div><div style="margin-top:16px"><button class="atlas-button">Update preview</button></div></section>
        <aside class="atlas-panel"><p class="atlas-eyebrow">Packet preview</p><h2>Ready to print</h2><?php if(!$selected):?><div class="atlas-empty"><strong>No materials selected</strong><p>Choose at least one handout to build a packet.</p></div><?php else:?><div class="atlas-stack"><?php $pages=0;foreach($selected as$key):$item=$items[$key];$pages+=(int)$item[2];?><div class="atlas-priority-item"><div><strong><?php echo esc_html($item[0]);?></strong><span><?php echo esc_html($item[1]);?></span></div><span><?php echo esc_html($item[2]);?></span></div><?php endforeach;?></div><div class="atlas-summary-strip" style="margin-top:16px"><div class="atlas-summary-card"><span>Materials</span><strong><?php echo count($selected);?></strong></div><div class="atlas-summary-card"><span>Approx. pages</span><strong><?php echo $pages;?></strong></div><div class="atlas-summary-card"><span>Patient data</span><strong>None</strong></div></div><button type="button" class="atlas-button" onclick="window.print()">Print packet preview</button><?php endif;?></aside></form>
        <?php return(string)ob_get_clean();
    }
}
