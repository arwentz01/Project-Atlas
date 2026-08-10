<?php

declare(strict_types=1);

namespace Atlas\FrontEnd\Views;

use Atlas\Support\Fixtures;

final class Playbooks
{
    public static function library(): string
    {
        $items=Fixtures::playbooks();ob_start();?>
        <section class="atlas-page-head"><div><p class="atlas-eyebrow">Playbooks</p><h1>Turn policy into the next right step.</h1><p>Practical, ordered guidance for work that crosses teams, payers, documentation, and discharge timing.</p></div><div class="atlas-count"><strong><?php echo count($items);?></strong><span>demo playbooks</span></div></section>
        <div class="atlas-card-grid"><?php foreach($items as$p):$url=$p['slug']==='arrange-home-oxygen'?home_url('/playbooks/arrange-home-oxygen'):'#';?><article class="atlas-resource-card"><div class="atlas-card-meta"><span class="atlas-badge"><?php echo esc_html($p['area']);?></span><span class="atlas-trust">● <?php echo esc_html($p['status']);?></span></div><h2><a href="<?php echo esc_url($url);?>"><?php echo esc_html($p['title']);?></a></h2><p><?php echo esc_html($p['summary']);?></p><div class="atlas-progress" aria-label="<?php echo esc_attr($p['steps']);?> step playbook"><span style="width:<?php echo esc_attr($p['progress']);?>%"></span></div><div class="atlas-card-footer"><span><?php echo esc_html($p['steps']);?> steps · <?php echo esc_html($p['time']);?></span><a href="<?php echo esc_url($url);?>">Open playbook →</a></div></article><?php endforeach;?></div><?php return(string)ob_get_clean();
    }

    public static function detail(): string
    {
        $p=Fixtures::playbookDetail();if(!$p)return'<div class="atlas-empty"><h1>Demo content disabled</h1></div>';ob_start();?>
        <nav class="atlas-breadcrumb"><a href="<?php echo esc_url(home_url('/playbooks'));?>">Playbooks</a><span>/</span><span>Arrange Home Oxygen</span></nav>
        <section class="atlas-detail-head"><div><div class="atlas-card-meta"><span class="atlas-badge"><?php echo esc_html($p['area']);?></span><span class="atlas-trust">● Published</span></div><h1><?php echo esc_html($p['title']);?></h1><p><?php echo esc_html($p['summary']);?></p></div><aside class="atlas-trust-panel"><span>Playbook snapshot</span><strong><?php echo esc_html($p['steps_count']);?> ordered steps</strong><small>Reviewed <?php echo esc_html($p['reviewed']);?></small><button class="atlas-button atlas-button-secondary" type="button" onclick="window.print()">Print playbook</button></aside></section>
        <div class="atlas-two-col" style="margin-top:24px"><div class="atlas-panel"><p class="atlas-eyebrow">Guided use</p><h2>Complete in order</h2><ol class="atlas-step-list"><?php foreach($p['steps']as$i=>$step):?><li><span><?php echo $i+1;?></span><div><strong><?php echo esc_html($step['title']);?></strong><p class="atlas-muted"><?php echo esc_html($step['detail']);?></p><?php if(!empty($step['warning'])):?><div class="atlas-alert"><strong>Watch for:</strong> <?php echo esc_html($step['warning']);?></div><?php endif;?></div></li><?php endforeach;?></ol></div><aside><section class="atlas-panel"><p class="atlas-eyebrow">Bring with you</p><h2>Required documentation</h2><div class="atlas-pill-row"><?php foreach($p['documents']as$d):?><span class="atlas-pill"><?php echo esc_html($d);?></span><?php endforeach;?></div></section><section class="atlas-panel" style="margin-top:16px"><p class="atlas-eyebrow">Connected in Atlas</p><h2>Related guidance</h2><a href="<?php echo esc_url(home_url('/insurance/medicare-dme-oxygen'));?>"><strong>Medicare DME: Oxygen</strong></a><br><a href="<?php echo esc_url(home_url('/resources/home-oxygen-qualification'));?>"><strong>Home Oxygen Qualification & Documentation</strong></a></section></aside></div><?php return(string)ob_get_clean();
    }
}
