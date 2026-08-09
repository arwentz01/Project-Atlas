<?php

declare(strict_types=1);

namespace Atlas\FrontEnd\Modules;

use Atlas\Support\Fixtures;

final class Workspace
{
    public static function boot(): void
    {
        add_filter('atlas_frontend_routes',[self::class,'routes']);
        add_filter('atlas_render_view',[self::class,'render'],10,2);
    }

    public static function routes(array $routes): array
    {
        $routes[]=['name'=>'workspace','pattern'=>'workspace','label'=>'My Workspace','nav'=>true,'view'=>'workspace'];
        return $routes;
    }

    public static function render(string $content,string $view): string
    {
        if($content!==''||$view!=='workspace')return $content;
        return self::page();
    }

    private static function page(): string
    {
        if(!Fixtures::enabled())return'<div class="atlas-empty"><h1>Your workspace is empty</h1><p>Demo content is disabled. Saved-item persistence will be implemented after the visual model is approved.</p></div>';
        $saved=[
            ['type'=>'Resource','title'=>'Home Oxygen Qualification & Documentation','meta'=>'Reviewed Jul 2026','href'=>'/resources/home-oxygen-qualification'],
            ['type'=>'Playbook','title'=>'Arrange Home Oxygen','meta'=>'7 steps · Published','href'=>'/playbooks/arrange-home-oxygen'],
            ['type'=>'Policy','title'=>'Discharge Documentation Standard','meta'=>'Care Transitions Council','href'=>'/knowledgebase/discharge-documentation-standard'],
            ['type'=>'Patient Resource','title'=>'Home Oxygen: What to Expect','meta'=>'6th grade reading level','href'=>'/patient-resources/home-oxygen-what-to-expect'],
        ];
        ob_start();?>
        <section class="atlas-page-head"><div><p class="atlas-eyebrow">My Workspace</p><h1>The things you come back to.</h1><p>A personal landing place for saved guidance, recent work, packet drafts, and shortcuts. This build validates the workspace concept before saved-item persistence is introduced.</p></div><div class="atlas-count"><strong><?php echo count($saved);?></strong><span>demo saved items</span></div></section>
        <div class="atlas-summary-strip"><div class="atlas-summary-card"><span>Saved guidance</span><strong>4</strong></div><div class="atlas-summary-card"><span>Packet drafts</span><strong>1</strong></div><div class="atlas-summary-card"><span>Recently opened</span><strong>6</strong></div></div>
        <div class="atlas-home-grid"><section class="atlas-panel"><div class="atlas-section-heading"><div><p class="atlas-eyebrow">Saved</p><h2>Your shortcuts</h2></div><a href="<?php echo esc_url(home_url('/search'));?>">Find more</a></div><div class="atlas-stack"><?php foreach($saved as$item):?><a class="atlas-priority-item" style="text-decoration:none;color:inherit" href="<?php echo esc_url(home_url($item['href']));?>"><div><span class="atlas-badge"><?php echo esc_html($item['type']);?></span><strong style="display:block;margin-top:7px"><?php echo esc_html($item['title']);?></strong><span><?php echo esc_html($item['meta']);?></span></div><span>Open →</span></a><?php endforeach;?></div></section>
        <aside class="atlas-stack"><section class="atlas-panel"><p class="atlas-eyebrow">Continue</p><h2>Patient education packet</h2><p class="atlas-muted">2 materials selected · approx. 3 pages</p><a class="atlas-button" href="<?php echo esc_url(home_url('/patient-resources/packet-builder'));?>">Resume packet</a></section><section class="atlas-panel"><p class="atlas-eyebrow">Quick jump</p><h2>Common tasks</h2><div class="atlas-stack"><a href="<?php echo esc_url(home_url('/insurance'));?>">Check a payer requirement →</a><a href="<?php echo esc_url(home_url('/playbooks'));?>">Open a Playbook →</a><a href="<?php echo esc_url(home_url('/patient-resources'));?>">Find patient education →</a><a href="<?php echo esc_url(home_url('/profile'));?>">Switch organization →</a></div></section></aside></div>
        <div class="atlas-callout" style="margin-top:20px"><strong>Visual-only saved state</strong><span>Nothing on this page is persisted yet. The approved version will use user-scoped storage and organization-aware authorization rather than browser storage as the source of truth.</span></div>
        <?php return(string)ob_get_clean();
    }
}
