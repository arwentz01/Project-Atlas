<?php

declare(strict_types=1);

namespace Atlas\FrontEnd\Modules;

use Atlas\Support\Fixtures;

final class Account
{
    public static function boot(): void
    {
        add_filter('atlas_frontend_routes',[self::class,'routes']);
        add_filter('atlas_render_view',[self::class,'render'],10,2);
    }

    public static function routes(array $routes): array
    {
        $routes[]=['name'=>'profile','pattern'=>'profile','label'=>'Profile','nav'=>true,'view'=>'profile'];
        return $routes;
    }

    public static function render(string $content,string $view): string
    {
        if($content!==''||$view!=='profile')return $content;
        $u=wp_get_current_user();
        $name=$u->display_name?:$u->user_login;
        ob_start();?>
        <section class="atlas-page-head"><div><p class="atlas-eyebrow">Account & workspace</p><h1><?php echo esc_html($name);?></h1><p>Your Atlas identity and the organization context that controls which local policies, playbooks, and resources you see.</p></div><div class="atlas-count"><strong>2</strong><span>demo organizations</span></div></section>
        <div class="atlas-profile-grid"><section class="atlas-panel"><p class="atlas-eyebrow">Your profile</p><h2>Account</h2><div class="atlas-field-list"><div class="atlas-field-row"><span>Name</span><strong><?php echo esc_html($name);?></strong></div><div class="atlas-field-row"><span>Email</span><strong><?php echo esc_html($u->user_email?:'Not provided');?></strong></div><div class="atlas-field-row"><span>Atlas role</span><strong>Clinical staff · Demo</strong></div><div class="atlas-field-row"><span>Workspace</span><strong>Atlas Demo Health</strong></div></div><div class="atlas-callout" style="margin-top:18px"><strong>Visual pass</strong><span>Profile editing, invitations, and role changes are deliberately not wired yet. This screen is validating account hierarchy and organization switching before we design those services.</span></div></section>
        <aside class="atlas-panel"><p class="atlas-eyebrow">Organization context</p><h2>Switch workspace</h2><div class="atlas-stack"><a class="atlas-org-option" aria-current="true" href="<?php echo esc_url(home_url('/profile'));?>"><div><strong>Atlas Demo Health</strong><span>Primary organization</span></div><span class="atlas-badge">Current</span></a><a class="atlas-org-option" href="<?php echo esc_url(home_url('/profile?organization=community'));?>"><div><strong>Community Care Collaborative</strong><span>Demo secondary organization</span></div><span>→</span></a></div><hr style="border:0;border-top:1px solid var(--atlas-line);margin:20px 0"><p class="atlas-muted">Organization context will eventually be resolved server-side from membership. Atlas will never trust an organization ID supplied by the browser as authorization.</p></aside></div>
        <section style="margin-top:20px" class="atlas-panel"><p class="atlas-eyebrow">What changes by organization</p><h2>One account, different local context</h2><div class="atlas-summary-strip"><div class="atlas-summary-card"><span>Policies</span><strong>Local</strong></div><div class="atlas-summary-card"><span>Playbooks</span><strong>Overlayable</strong></div><div class="atlas-summary-card"><span>Payer guidance</span><strong>Shared + local</strong></div></div><p class="atlas-muted">The visual model keeps global guidance, payer rules, and organization-owned policy distinct while allowing them to connect in the same workflow.</p></section>
        <?php return(string)ob_get_clean();
    }
}
