<?php

declare(strict_types=1);

namespace Atlas\FrontEnd\Views;

use Atlas\Support\Fixtures;

final class Insurance
{
    public static function library(): string
    {
        $items=Fixtures::insurance();ob_start();?>
        <section class="atlas-page-head"><div><p class="atlas-eyebrow">Insurance workspace</p><h1>Know what the payer needs before the referral stalls.</h1><p>Coverage requirements, effective dates, source anchors, related forms, and the operational next step in one place.</p></div><div class="atlas-count"><strong><?php echo count($items);?></strong><span>demo requirements</span></div></section>
        <div class="atlas-stat-grid"><div class="atlas-stat"><strong>4</strong><span>Payers represented</span></div><div class="atlas-stat"><strong>3</strong><span>Current source versions</span></div><div class="atlas-stat"><strong>1</strong><span>Needs review soon</span></div></div>
        <div class="atlas-panel"><table class="atlas-table"><thead><tr><th>Requirement</th><th>Payer</th><th>Context</th><th>Effective</th><th>Status</th></tr></thead><tbody><?php foreach($items as$i):$url=$i['slug']==='medicare-dme-oxygen'?home_url('/insurance/medicare-dme-oxygen'):'#';?><tr><td><a href="<?php echo esc_url($url);?>"><?php echo esc_html($i['title']);?></a><div class="atlas-muted"><?php echo esc_html($i['summary']);?></div></td><td><?php echo esc_html($i['payer']);?></td><td><?php echo esc_html($i['context']);?></td><td><?php echo esc_html($i['effective']);?></td><td><span class="atlas-badge"><?php echo esc_html($i['status']);?></span></td></tr><?php endforeach;?></tbody></table></div><?php return(string)ob_get_clean();
    }

    public static function detail(): string
    {
        $r=Fixtures::insuranceDetail();if(!$r)return'<div class="atlas-empty"><h1>Demo content disabled</h1></div>';ob_start();?>
        <nav class="atlas-breadcrumb"><a href="<?php echo esc_url(home_url('/insurance'));?>">Insurance</a><span>/</span><span>Medicare DME: Oxygen</span></nav>
        <section class="atlas-detail-head"><div><div class="atlas-card-meta"><span class="atlas-badge"><?php echo esc_html($r['payer']);?></span><span class="atlas-trust">● Current source</span></div><h1><?php echo esc_html($r['title']);?></h1><p><?php echo esc_html($r['summary']);?></p></div><aside class="atlas-trust-panel"><span>Source anchor</span><strong><?php echo esc_html($r['source']);?></strong><small><?php echo esc_html($r['anchor']);?></small><small>Effective <?php echo esc_html($r['effective']);?></small></aside></section>
        <div class="atlas-two-col" style="margin-top:24px"><div><section class="atlas-panel"><p class="atlas-eyebrow">Coverage checklist</p><h2>What must be true</h2><ul class="atlas-check-list"><?php foreach($r['requirements']as$x):?><li><?php echo esc_html($x);?></li><?php endforeach;?></ul></section><section class="atlas-panel" style="margin-top:16px"><p class="atlas-eyebrow">Documentation packet</p><h2>What to send</h2><div class="atlas-pill-row"><?php foreach($r['documents']as$x):?><span class="atlas-pill"><?php echo esc_html($x);?></span><?php endforeach;?></div><div class="atlas-alert"><strong>Common failure point</strong><br><?php echo esc_html($r['warning']);?></div></section></div><aside class="atlas-panel"><p class="atlas-eyebrow">Do the work</p><h2>Connected guidance</h2><p>The payer rule should never be a dead end. Atlas connects it directly to the operational path.</p><a class="atlas-action-card" href="<?php echo esc_url(home_url('/resources/home-oxygen-qualification'));?>" style="min-height:120px"><strong>Home Oxygen Qualification & Documentation</strong><span>Resource</span></a><div class="atlas-pill-row"><span class="atlas-pill">CMS-484 reference</span><span class="atlas-pill">DME supplier checklist</span><span class="atlas-pill">Home oxygen education</span></div></aside></div><?php return(string)ob_get_clean();
    }
}
