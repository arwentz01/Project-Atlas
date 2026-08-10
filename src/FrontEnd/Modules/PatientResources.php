<?php

declare(strict_types=1);

namespace Atlas\FrontEnd\Modules;

use Atlas\Support\Fixtures;

final class PatientResources
{
    public static function boot(): void
    {
        add_filter('atlas_frontend_routes',[self::class,'routes']);
        add_filter('atlas_render_view',[self::class,'render'],10,2);
    }

    public static function routes(array $routes): array
    {
        $routes[]=['name'=>'patient-resources','pattern'=>'patient-resources','label'=>'Patient Resources','nav'=>true,'view'=>'patient-resources'];
        $routes[]=['name'=>'patient-resource-detail','pattern'=>'patient-resources/home-oxygen-what-to-expect','label'=>'Patient Resource','nav'=>false,'view'=>'patient-resource-detail'];
        return $routes;
    }

    public static function render(string $content,string $view): string
    {
        if($content!=='')return $content;
        return match($view){'patient-resources'=>self::library(),'patient-resource-detail'=>self::detail(),default=>''};
    }

    private static function items(): array
    {
        if(!Fixtures::enabled())return [];
        return [
            ['slug'=>'home-oxygen-what-to-expect','title'=>'Home Oxygen: What to Expect','summary'=>'Plain-language teaching for patients and caregivers preparing to use oxygen at home.','topic'=>'Respiratory','format'=>'2-page handout','reading'=>'6th grade','reviewed'=>'Jul 2026'],
            ['slug'=>'after-snf-discharge','title'=>'After Your Skilled Nursing Stay','summary'=>'Questions, medication checks, follow-up, and warning signs to review before leaving a skilled nursing facility.','topic'=>'Transitions','format'=>'2-page handout','reading'=>'7th grade','reviewed'=>'Jun 2026'],
            ['slug'=>'home-health-first-visit','title'=>'Preparing for Your First Home Health Visit','summary'=>'What home health does, what to have ready, and who to call if plans change.','topic'=>'Home Health','format'=>'1-page guide','reading'=>'6th grade','reviewed'=>'Jun 2026'],
            ['slug'=>'transportation-choices','title'=>'Choosing Safe Transportation After Discharge','summary'=>'A simple guide to transportation options based on mobility and assistance needs.','topic'=>'Transportation','format'=>'1-page guide','reading'=>'6th grade','reviewed'=>'May 2026']
        ];
    }

    private static function library(): string
    {
        $items=self::items();ob_start();?>
        <section class="atlas-page-head"><div><p class="atlas-eyebrow">Patient Resources</p><h1>Education that is ready to hand over.</h1><p>Reviewed, patient-friendly materials designed for discharge teaching and care transitions. Demo resources are clearly separated from clinical documentation and contain no patient information.</p></div><div class="atlas-count"><strong><?php echo count($items);?></strong><span>demo handouts</span></div></section>
        <div class="atlas-callout"><strong>No PHI workspace</strong><span>Patient Resources are reusable education materials. Atlas does not ask for patient names, MRNs, dates of birth, or clinical notes.</span></div>
        <div class="atlas-card-grid"><?php foreach($items as$item):$url=$item['slug']==='home-oxygen-what-to-expect'?home_url('/patient-resources/home-oxygen-what-to-expect'):'#';?><article class="atlas-resource-card"><div class="atlas-card-meta"><span class="atlas-badge"><?php echo esc_html($item['topic']);?></span><span class="atlas-trust">● Patient reviewed</span></div><h2><a href="<?php echo esc_url($url);?>"><?php echo esc_html($item['title']);?></a></h2><p><?php echo esc_html($item['summary']);?></p><dl><div><dt>Format</dt><dd><?php echo esc_html($item['format']);?></dd></div><div><dt>Reading level</dt><dd><?php echo esc_html($item['reading']);?></dd></div></dl><div class="atlas-card-footer"><span>Reviewed <?php echo esc_html($item['reviewed']);?></span><a href="<?php echo esc_url($url);?>">Preview →</a></div></article><?php endforeach;?></div>
        <?php return(string)ob_get_clean();
    }

    private static function detail(): string
    {
        if(!Fixtures::enabled())return'<div class="atlas-empty"><h1>Demo content disabled</h1></div>';
        ob_start();?><nav class="atlas-breadcrumb"><a href="<?php echo esc_url(home_url('/patient-resources'));?>">Patient Resources</a><span>/</span><span>Home Oxygen</span></nav>
        <article class="atlas-detail"><header class="atlas-detail-head"><div><div class="atlas-card-meta"><span class="atlas-badge">Respiratory</span><span class="atlas-trust">● Patient reviewed</span></div><h1>Home Oxygen: What to Expect</h1><p>A calm, plain-language handout that helps patients and caregivers understand delivery, safe use, and who to call when something changes.</p></div><aside class="atlas-trust-panel"><span>Patient handout</span><strong>6th grade reading level</strong><small>Reviewed Jul 2026</small><button class="atlas-button" onclick="window.print()">Print handout</button></aside></header>
        <div class="atlas-detail-layout"><div class="atlas-prose atlas-patient-preview"><section><p class="atlas-eyebrow">For patients and caregivers</p><h2>Your oxygen equipment</h2><p>Your oxygen supplier will show you how to use your equipment before or when it arrives. Keep the supplier phone number somewhere easy to find.</p></section><section><h2>Before you leave</h2><ul class="atlas-check-list"><li>Know how much oxygen you were told to use and when to use it.</li><li>Know when your equipment will arrive and who is delivering it.</li><li>Know who to call if the equipment does not arrive or stops working.</li><li>Keep oxygen away from flames, cigarettes, and other heat sources.</li></ul></section><section><h2>Call for help</h2><p>Follow the instructions your care team gave you for worsening breathing or other symptoms. If you believe you are having a medical emergency, seek emergency help.</p></section></div>
        <aside class="atlas-related"><p class="atlas-eyebrow">Staff connections</p><h2>Related in Atlas</h2><a href="<?php echo esc_url(home_url('/playbooks/arrange-home-oxygen'));?>"><strong>Arrange Home Oxygen</strong><span>Open staff Playbook →</span></a><a href="<?php echo esc_url(home_url('/resources/home-oxygen-qualification'));?>"><strong>Home Oxygen Qualification</strong><span>Open operational resource →</span></a><a href="<?php echo esc_url(home_url('/insurance/medicare-dme-oxygen'));?>"><strong>Medicare DME: Oxygen</strong><span>Open payer requirement →</span></a></aside></div></article>
        <?php return(string)ob_get_clean();
    }
}
