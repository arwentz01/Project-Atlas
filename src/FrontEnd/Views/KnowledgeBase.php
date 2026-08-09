<?php

declare(strict_types=1);

namespace Atlas\FrontEnd\Views;

use Atlas\Support\Fixtures;

final class KnowledgeBase
{
    public static function library(): string
    {
        $items=Fixtures::knowledgeBase();
        ob_start(); ?>
        <section class="atlas-page-head"><div><p class="atlas-eyebrow">Knowledge Base</p><h1>Local policy without the scavenger hunt.</h1><p>Organization policies, SOPs, and reference articles with owners, review dates, and clear status.</p></div><div class="atlas-count"><strong><?php echo count($items); ?></strong><span>demo articles</span></div></section>
        <div class="atlas-two-col"><div class="atlas-panel"><table class="atlas-table"><thead><tr><th>Article</th><th>Type</th><th>Owner</th><th>Review</th></tr></thead><tbody><?php foreach ($items as $item): $url=$item['slug']==='discharge-documentation-standard'?home_url('/knowledgebase/discharge-documentation-standard'):'#'; ?><tr><td><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($item['title']); ?></a><div class="atlas-muted"><?php echo esc_html($item['summary']); ?></div></td><td><span class="atlas-badge"><?php echo esc_html($item['type']); ?></span></td><td><?php echo esc_html($item['owner']); ?></td><td><?php echo esc_html($item['review']); ?></td></tr><?php endforeach; ?></tbody></table></div><aside class="atlas-panel"><p class="atlas-eyebrow">Why it matters</p><h2>Organization context belongs beside broader guidance.</h2><p>Atlas should distinguish local policy from payer rules and general references while still connecting them when the work overlaps.</p><div class="atlas-alert"><strong>Visual rule:</strong><br>Users should always be able to tell who owns a local article and when it was last reviewed.</div></aside></div><?php return (string) ob_get_clean();
    }

    public static function detail(): string
    {
        $a=Fixtures::knowledgeDetail();
        if (!$a) return '<div class="atlas-empty"><h1>Demo content disabled</h1></div>';
        ob_start(); ?>
        <nav class="atlas-breadcrumb"><a href="<?php echo esc_url(home_url('/knowledgebase')); ?>">Knowledge Base</a><span>/</span><span>Discharge Documentation Standard</span></nav>
        <section class="atlas-detail-head"><div><div class="atlas-card-meta"><span class="atlas-badge"><?php echo esc_html($a['type']); ?></span><span class="atlas-trust">● Approved</span></div><h1><?php echo esc_html($a['title']); ?></h1><p><?php echo esc_html($a['summary']); ?></p></div><aside class="atlas-trust-panel"><span>Ownership</span><strong><?php echo esc_html($a['owner']); ?></strong><small>Reviewed <?php echo esc_html($a['reviewed']); ?></small><small>Next review <?php echo esc_html($a['next_review']); ?></small><button class="atlas-button atlas-button-secondary" type="button" onclick="window.print()">Print article</button></aside></section>
        <div class="atlas-detail-layout"><div class="atlas-prose"><section><p class="atlas-eyebrow">Standard</p><h2>Required discharge documentation</h2><ul class="atlas-check-list"><?php foreach($a['requirements'] as $r): ?><li><?php echo esc_html($r); ?></li><?php endforeach; ?></ul></section><section><p class="atlas-eyebrow">Escalation</p><h2>When something is missing</h2><p><?php echo esc_html($a['escalation']); ?></p></section></div><aside class="atlas-related"><p class="atlas-eyebrow">Connected in Atlas</p><h2>Use with</h2><a href="<?php echo esc_url(home_url('/playbooks/arrange-home-oxygen')); ?>"><strong>Arrange Home Oxygen</strong><span>Playbook →</span></a><a href="<?php echo esc_url(home_url('/resources/home-oxygen-qualification')); ?>"><strong>Home Oxygen Qualification & Documentation</strong><span>Resource →</span></a><a href="<?php echo esc_url(home_url('/insurance/medicare-dme-oxygen')); ?>"><strong>Medicare DME: Oxygen</strong><span>Payer requirement →</span></a></aside></div><?php return (string) ob_get_clean();
    }
}
