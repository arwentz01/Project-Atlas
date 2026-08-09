<?php

declare(strict_types=1);

namespace Atlas\FrontEnd;

use Atlas\Support\Fixtures;

final class App
{
    public static function routes(): array
    {
        return [
            ['name'=>'home','pattern'=>'atlas','label'=>'Home','nav'=>true],
            ['name'=>'resources','pattern'=>'resources','label'=>'Resources','nav'=>true],
            ['name'=>'resource-detail','pattern'=>'resources/home-oxygen-qualification','label'=>'Resource','nav'=>false],
        ];
    }

    public function render(string $route): void
    {
        $routes = array_column(self::routes(), null, 'name');
        if (!isset($routes[$route])) { status_header(404); nocache_headers(); $this->shell('Not found','<div class="atlas-empty"><h1>Page not found</h1><p>This Atlas destination is not available.</p></div>',$route); return; }
        wp_enqueue_style('atlas-app', ATLAS_URL.'assets/app.css', [], ATLAS_VERSION);
        wp_enqueue_script('atlas-app', ATLAS_URL.'assets/app.js', [], ATLAS_VERSION, true);
        $method='page_'.str_replace('-','_',$route); $this->shell($routes[$route]['label'], method_exists($this,$method)?$this->{$method}():'', $route);
    }

    private function page_home(): string
    {
        $data=Fixtures::dashboard(); $name=wp_get_current_user()->display_name?:wp_get_current_user()->user_login; ob_start(); ?>
        <section class="atlas-hero"><div><p class="atlas-eyebrow"><?php echo esc_html($data['welcome']??'Welcome'); ?></p><h1><?php echo esc_html($name); ?></h1><p>Everything you need to move care forward, without hunting through five systems to find it.</p></div><div class="atlas-org-card" aria-label="Current organization"><span>Working in</span><strong><?php echo esc_html($data['organization']??'Personal workspace'); ?></strong></div></section>
        <section aria-labelledby="quick-actions-title"><div class="atlas-section-heading"><div><p class="atlas-eyebrow">Start here</p><h2 id="quick-actions-title">What do you need to do?</h2></div></div><div class="atlas-action-grid"><?php foreach(($data['quick_actions']??[]) as $item): ?><a class="atlas-action-card" href="<?php echo esc_url(home_url($item['href'])); ?>"><span class="atlas-action-arrow" aria-hidden="true">↗</span><strong><?php echo esc_html($item['label']); ?></strong><span><?php echo esc_html($item['hint']); ?></span></a><?php endforeach; ?></div></section>
        <section aria-labelledby="recent-title"><div class="atlas-section-heading"><div><p class="atlas-eyebrow">Pick up where you left off</p><h2 id="recent-title">Recent work</h2></div></div><div class="atlas-list-card"><?php foreach(($data['recent']??[]) as $item): ?><div class="atlas-list-row"><div><span class="atlas-badge"><?php echo esc_html($item['type']); ?></span><strong><?php echo esc_html($item['title']); ?></strong></div><span class="atlas-muted"><?php echo esc_html($item['meta']); ?></span></div><?php endforeach; ?></div></section><?php return (string)ob_get_clean();
    }

    private function page_resources(): string
    {
        $all=Fixtures::resources(); $query=sanitize_text_field(wp_unslash($_GET['q']??'')); $category=sanitize_text_field(wp_unslash($_GET['category']??''));
        $items=array_values(array_filter($all,static function($r)use($query,$category){$matchesQ=$query===''||stripos($r['title'].' '.$r['summary'].' '.$r['category'],$query)!==false;$matchesC=$category===''||$r['category']===$category;return $matchesQ&&$matchesC;}));
        $categories=array_values(array_unique(array_column($all,'category'))); ob_start(); ?>
        <section class="atlas-page-head"><div><p class="atlas-eyebrow">Resource Library</p><h1>Find the answer, then keep moving.</h1><p>Operational references with visible sources, review status, and the context needed to use them confidently.</p></div><div class="atlas-count"><strong><?php echo count($all); ?></strong><span>demo resources</span></div></section>
        <form class="atlas-filterbar" method="get" action="<?php echo esc_url(home_url('/resources')); ?>" role="search"><label><span>Search resources</span><input name="q" value="<?php echo esc_attr($query); ?>" placeholder="Try oxygen, DME, transportation…"></label><label><span>Category</span><select name="category"><option value="">All categories</option><?php foreach($categories as $cat): ?><option value="<?php echo esc_attr($cat); ?>" <?php selected($category,$cat); ?>><?php echo esc_html($cat); ?></option><?php endforeach; ?></select></label><button class="atlas-button" type="submit">Search</button><?php if($query||$category): ?><a class="atlas-button atlas-button-secondary" href="<?php echo esc_url(home_url('/resources')); ?>">Clear</a><?php endif; ?></form>
        <div class="atlas-results-head"><strong><?php echo count($items); ?> results</strong><span class="atlas-muted">Demo content is isolated from WordPress data.</span></div>
        <?php if(!$items): ?><div class="atlas-empty"><h2>No resources found</h2><p>Try a broader search or clear the category filter.</p></div><?php else: ?><div class="atlas-card-grid"><?php foreach($items as $item): $detail=$item['slug']==='home-oxygen-qualification'?home_url('/resources/home-oxygen-qualification'):'#'; ?><article class="atlas-resource-card"><div class="atlas-card-meta"><span class="atlas-badge"><?php echo esc_html($item['category']); ?></span><span class="atlas-trust">● <?php echo esc_html($item['trust']); ?></span></div><h2><a href="<?php echo esc_url($detail); ?>"><?php echo esc_html($item['title']); ?></a></h2><p><?php echo esc_html($item['summary']); ?></p><dl><div><dt>Source</dt><dd><?php echo esc_html($item['source']); ?></dd></div><div><dt>Reviewed</dt><dd><?php echo esc_html($item['reviewed']); ?></dd></div></dl><div class="atlas-card-footer"><span><?php echo esc_html($item['audience']); ?></span><a href="<?php echo esc_url($detail); ?>" aria-label="Open <?php echo esc_attr($item['title']); ?>">Open →</a></div></article><?php endforeach; ?></div><?php endif; ?><?php return (string)ob_get_clean();
    }

    private function page_resource_detail(): string
    {
        $r=Fixtures::resource('home-oxygen-qualification'); if(!$r)return '<div class="atlas-empty"><h1>Demo content disabled</h1><p>The resource fixture is not available.</p></div>'; ob_start(); ?>
        <nav class="atlas-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/resources')); ?>">Resources</a><span aria-hidden="true">/</span><span>Home Oxygen</span></nav>
        <article class="atlas-detail"><header class="atlas-detail-head"><div><div class="atlas-card-meta"><span class="atlas-badge"><?php echo esc_html($r['category']); ?></span><span class="atlas-trust">● <?php echo esc_html($r['trust']); ?></span></div><h1><?php echo esc_html($r['title']); ?></h1><p><?php echo esc_html($r['summary']); ?></p></div><aside class="atlas-trust-panel"><span>Trust snapshot</span><strong><?php echo esc_html($r['source']); ?></strong><small>Reviewed <?php echo esc_html($r['reviewed']); ?></small><button class="atlas-button atlas-button-secondary" type="button" onclick="window.print()">Print resource</button></aside></header>
        <div class="atlas-detail-layout"><div class="atlas-prose"><section><p class="atlas-eyebrow">At a glance</p><h2>Key points</h2><ul class="atlas-check-list"><?php foreach($r['key_points'] as $point): ?><li><?php echo esc_html($point); ?></li><?php endforeach; ?></ul></section><section><p class="atlas-eyebrow">Operational sequence</p><h2>From qualification to delivery</h2><ol class="atlas-step-list"><?php foreach($r['workflow'] as $i=>$step): ?><li><span><?php echo (int)$i+1; ?></span><div><strong><?php echo esc_html($step); ?></strong></div></li><?php endforeach; ?></ol></section><section class="atlas-source-box"><p class="atlas-eyebrow">Source citation</p><p><?php echo esc_html($r['citation']); ?></p></section></div><aside class="atlas-related"><p class="atlas-eyebrow">Connected in Atlas</p><h2>Related guidance</h2><?php foreach($r['related'] as $item): ?><a href="#"><strong><?php echo esc_html($item); ?></strong><span>View related item →</span></a><?php endforeach; ?></aside></div></article><?php return (string)ob_get_clean();
    }

    private function shell(string $title,string $content,string $current): void
    {
        $user=wp_get_current_user(); ?><!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?php echo esc_html($title.' | Atlas'); ?></title><?php wp_head(); ?></head><body class="atlas-body"><a class="atlas-skip-link" href="#atlas-main">Skip to main content</a><div class="atlas-app"><aside class="atlas-sidebar" id="atlas-sidebar" aria-label="Atlas navigation"><a class="atlas-brand" href="<?php echo esc_url(home_url('/atlas')); ?>"><span class="atlas-brand-mark">A</span><span>Atlas</span></a><nav><?php foreach(self::routes() as $route):if(empty($route['nav']))continue; ?><a href="<?php echo esc_url(home_url('/'.trim($route['pattern'],'/'))); ?>" <?php echo ($current===$route['name']||($current==='resource-detail'&&$route['name']==='resources'))?'aria-current="page"':''; ?>><span class="atlas-nav-dot" aria-hidden="true"></span><?php echo esc_html($route['label']); ?></a><?php endforeach; ?></nav><div class="atlas-sidebar-footer"><span class="atlas-avatar"><?php echo esc_html(strtoupper(substr($user->display_name?:$user->user_login,0,1))); ?></span><div><strong><?php echo esc_html($user->display_name?:$user->user_login); ?></strong><span>Atlas Demo Health</span></div></div></aside><div class="atlas-workspace"><header class="atlas-topbar"><button class="atlas-menu-button" type="button" data-atlas-menu aria-controls="atlas-sidebar" aria-expanded="false">Menu</button><span class="atlas-context">Atlas Demo Health</span><div class="atlas-top-actions"><a href="<?php echo esc_url(home_url('/resources')); ?>">Search</a><a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Sign out</a></div></header><main class="atlas-main" id="atlas-main"><?php echo $content; // phpcs:ignore ?></main></div></div><?php wp_footer(); ?></body></html><?php
    }
}
