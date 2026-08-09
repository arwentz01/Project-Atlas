<?php

declare(strict_types=1);

namespace Atlas\FrontEnd;

use Atlas\Support\Fixtures;

final class App
{
    public static function routes(): array
    {
        $routes=[
            ['name'=>'home','pattern'=>'atlas','label'=>'Home','nav'=>true,'view'=>'home'],
            ['name'=>'resources','pattern'=>'resources','label'=>'Resources','nav'=>true,'view'=>'resources'],
            ['name'=>'resource-detail','pattern'=>'resources/home-oxygen-qualification','label'=>'Resource','nav'=>false,'view'=>'resource-detail'],
        ];
        if (class_exists(Views\Insurance::class)) {
            $routes[]=['name'=>'insurance','pattern'=>'insurance','label'=>'Insurance','nav'=>true,'view'=>'insurance'];
            $routes[]=['name'=>'insurance-detail','pattern'=>'insurance/medicare-dme-oxygen','label'=>'Payer requirement','nav'=>false,'view'=>'insurance-detail'];
        }
        if (class_exists(Views\Playbooks::class)) {
            $routes[]=['name'=>'playbooks','pattern'=>'playbooks','label'=>'Playbooks','nav'=>true,'view'=>'playbooks'];
            $routes[]=['name'=>'playbook-detail','pattern'=>'playbooks/arrange-home-oxygen','label'=>'Playbook','nav'=>false,'view'=>'playbook-detail'];
        }
        if (class_exists(Views\KnowledgeBase::class)) {
            $routes[]=['name'=>'knowledgebase','pattern'=>'knowledgebase','label'=>'Knowledge Base','nav'=>true,'view'=>'knowledgebase'];
            $routes[]=['name'=>'knowledge-detail','pattern'=>'knowledgebase/discharge-documentation-standard','label'=>'Knowledge article','nav'=>false,'view'=>'knowledge-detail'];
        }
        return $routes;
    }

    public function render(string $route): void
    {
        $routes=array_column(self::routes(),null,'name');
        if(!isset($routes[$route])){status_header(404);nocache_headers();$this->shell('Not found','<div class="atlas-empty"><h1>Page not found</h1><p>This Atlas destination is not available.</p></div>',$route);return;}
        wp_enqueue_style('atlas-app',ATLAS_URL.'assets/app.css',[],ATLAS_VERSION);
        wp_enqueue_script('atlas-app',ATLAS_URL.'assets/app.js',[],ATLAS_VERSION,true);
        $content=match($routes[$route]['view']){
            'home'=>$this->home(),
            'resources'=>$this->resources(),
            'resource-detail'=>$this->resourceDetail(),
            'insurance'=>Views\Insurance::library(),
            'insurance-detail'=>Views\Insurance::detail(),
            'playbooks'=>Views\Playbooks::library(),
            'playbook-detail'=>Views\Playbooks::detail(),
            'knowledgebase'=>Views\KnowledgeBase::library(),
            'knowledge-detail'=>Views\KnowledgeBase::detail(),
            default=>''
        };
        $this->shell($routes[$route]['label'],$content,$route);
    }

    private function home(): string
    {
        $d=Fixtures::dashboard();$name=wp_get_current_user()->display_name?:wp_get_current_user()->user_login;ob_start(); ?>
        <section class="atlas-hero"><div><p class="atlas-eyebrow"><?php echo esc_html($d['welcome']??'Welcome'); ?></p><h1><?php echo esc_html($name); ?></h1><p>Everything you need to move care forward, without hunting through five systems to find it.</p></div><div class="atlas-org-card"><span>Working in</span><strong><?php echo esc_html($d['organization']??'Personal workspace'); ?></strong></div></section>
        <section><div class="atlas-section-heading"><div><p class="atlas-eyebrow">Start here</p><h2>What do you need to do?</h2></div></div><div class="atlas-action-grid"><?php foreach(($d['quick_actions']??[])as$i):?><a class="atlas-action-card" href="<?php echo esc_url(home_url($i['href'])); ?>"><span class="atlas-action-arrow">↗</span><strong><?php echo esc_html($i['label']); ?></strong><span><?php echo esc_html($i['hint']); ?></span></a><?php endforeach;?></div></section>
        <section><div class="atlas-section-heading"><div><p class="atlas-eyebrow">Pick up where you left off</p><h2>Recent work</h2></div></div><div class="atlas-list-card"><?php foreach(($d['recent']??[])as$i):?><div class="atlas-list-row"><div><span class="atlas-badge"><?php echo esc_html($i['type']); ?></span><strong><?php echo esc_html($i['title']); ?></strong></div><span class="atlas-muted"><?php echo esc_html($i['meta']); ?></span></div><?php endforeach;?></div></section><?php return(string)ob_get_clean();
    }

    private function resources(): string
    {
        $all=Fixtures::resources();$q=sanitize_text_field(wp_unslash($_GET['q']??''));$cat=sanitize_text_field(wp_unslash($_GET['category']??''));$items=array_values(array_filter($all,fn($r)=>($q===''||stripos($r['title'].' '.$r['summary'].' '.$r['category'],$q)!==false)&&($cat===''||$r['category']===$cat)));$cats=array_values(array_unique(array_column($all,'category')));ob_start();?>
        <section class="atlas-page-head"><div><p class="atlas-eyebrow">Resource Library</p><h1>Find the answer, then keep moving.</h1><p>Operational references with visible sources, review status, and the context needed to use them confidently.</p></div><div class="atlas-count"><strong><?php echo count($all);?></strong><span>demo resources</span></div></section>
        <form class="atlas-filterbar" method="get"><label><span>Search resources</span><input name="q" value="<?php echo esc_attr($q);?>" placeholder="Try oxygen, DME, transportation…"></label><label><span>Category</span><select name="category"><option value="">All categories</option><?php foreach($cats as$c):?><option <?php selected($cat,$c);?>><?php echo esc_html($c);?></option><?php endforeach;?></select></label><button class="atlas-button">Search</button><a class="atlas-button atlas-button-secondary" href="<?php echo esc_url(home_url('/resources'));?>">Clear</a></form>
        <div class="atlas-results-head"><strong><?php echo count($items);?> results</strong><span class="atlas-muted">Visible trust and review context</span></div><div class="atlas-card-grid"><?php foreach($items as$r):$url=$r['slug']==='home-oxygen-qualification'?home_url('/resources/home-oxygen-qualification'):'#';?><article class="atlas-resource-card"><div class="atlas-card-meta"><span class="atlas-badge"><?php echo esc_html($r['category']);?></span><span class="atlas-trust">● <?php echo esc_html($r['trust']);?></span></div><h2><a href="<?php echo esc_url($url);?>"><?php echo esc_html($r['title']);?></a></h2><p><?php echo esc_html($r['summary']);?></p><dl><div><dt>Source</dt><dd><?php echo esc_html($r['source']);?></dd></div><div><dt>Reviewed</dt><dd><?php echo esc_html($r['reviewed']);?></dd></div></dl><div class="atlas-card-footer"><span><?php echo esc_html($r['audience']);?></span><a href="<?php echo esc_url($url);?>">Open →</a></div></article><?php endforeach;?></div><?php return(string)ob_get_clean();
    }

    private function resourceDetail(): string
    {
        $r=Fixtures::resource('home-oxygen-qualification');if(!$r)return'<div class="atlas-empty"><h1>Demo content disabled</h1></div>';ob_start();?><nav class="atlas-breadcrumb"><a href="<?php echo esc_url(home_url('/resources'));?>">Resources</a><span>/</span><span>Home Oxygen</span></nav><article class="atlas-detail"><header class="atlas-detail-head"><div><div class="atlas-card-meta"><span class="atlas-badge"><?php echo esc_html($r['category']);?></span><span class="atlas-trust">● <?php echo esc_html($r['trust']);?></span></div><h1><?php echo esc_html($r['title']);?></h1><p><?php echo esc_html($r['summary']);?></p></div><aside class="atlas-trust-panel"><span>Trust snapshot</span><strong><?php echo esc_html($r['source']);?></strong><small>Reviewed <?php echo esc_html($r['reviewed']);?></small><button class="atlas-button atlas-button-secondary" onclick="window.print()">Print resource</button></aside></header><div class="atlas-detail-layout"><div class="atlas-prose"><section><p class="atlas-eyebrow">At a glance</p><h2>Key points</h2><ul class="atlas-check-list"><?php foreach($r['key_points']as$p):?><li><?php echo esc_html($p);?></li><?php endforeach;?></ul></section><section><p class="atlas-eyebrow">Operational sequence</p><h2>From qualification to delivery</h2><ol class="atlas-step-list"><?php foreach($r['workflow']as$i=>$s):?><li><span><?php echo $i+1;?></span><strong><?php echo esc_html($s);?></strong></li><?php endforeach;?></ol></section><section class="atlas-source-box"><p class="atlas-eyebrow">Source citation</p><p><?php echo esc_html($r['citation']);?></p></section></div><aside class="atlas-related"><p class="atlas-eyebrow">Connected in Atlas</p><h2>Related guidance</h2><?php foreach($r['related']as$x):?><a href="#"><strong><?php echo esc_html($x);?></strong><span>View related item →</span></a><?php endforeach;?></aside></div></article><?php return(string)ob_get_clean();
    }

    private function shell(string$title,string$content,string$current):void
    {
        $u=wp_get_current_user();?><!doctype html><html <?php language_attributes();?>><head><meta charset="<?php bloginfo('charset');?>"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?php echo esc_html($title.' | Atlas');?></title><?php wp_head();?></head><body class="atlas-body"><a class="atlas-skip-link" href="#atlas-main">Skip to main content</a><div class="atlas-app"><aside class="atlas-sidebar" id="atlas-sidebar"><a class="atlas-brand" href="<?php echo esc_url(home_url('/atlas'));?>"><span class="atlas-brand-mark">A</span><span>Atlas</span></a><nav aria-label="Atlas navigation"><?php foreach(self::routes()as$r):if(empty($r['nav']))continue;$active=$current===$r['name']||str_starts_with($current,$r['name'].'-');?><a href="<?php echo esc_url(home_url('/'.trim($r['pattern'],'/')));?>" <?php echo $active?'aria-current="page"':'';?>><span class="atlas-nav-dot"></span><?php echo esc_html($r['label']);?></a><?php endforeach;?></nav><div class="atlas-sidebar-footer"><span class="atlas-avatar"><?php echo esc_html(strtoupper(substr($u->display_name?:$u->user_login,0,1)));?></span><div><strong><?php echo esc_html($u->display_name?:$u->user_login);?></strong><span>Atlas Demo Health</span></div></div></aside><div class="atlas-workspace"><header class="atlas-topbar"><button class="atlas-menu-button" data-atlas-menu aria-controls="atlas-sidebar" aria-expanded="false">Menu</button><span class="atlas-context">Atlas Demo Health</span><div class="atlas-top-actions"><a href="<?php echo esc_url(home_url('/resources'));?>">Search</a><a href="<?php echo esc_url(wp_logout_url(home_url('/')));?>">Sign out</a></div></header><main class="atlas-main" id="atlas-main"><?php echo $content;// phpcs:ignore?></main></div></div><?php wp_footer();?></body></html><?php
    }
}
