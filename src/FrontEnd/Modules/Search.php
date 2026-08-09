<?php

declare(strict_types=1);

namespace Atlas\FrontEnd\Modules;

use Atlas\Support\Fixtures;

final class Search
{
    public static function boot(): void
    {
        add_filter('atlas_frontend_routes',[self::class,'routes']);
        add_filter('atlas_render_view',[self::class,'render'],10,2);
    }

    public static function routes(array $routes): array
    {
        $routes[]=['name'=>'search','pattern'=>'search','label'=>'Search','nav'=>true,'view'=>'search'];
        return $routes;
    }

    public static function render(string $content,string $view): string
    {
        if($content!==''||$view!=='search')return $content;
        return self::page();
    }

    private static function page(): string
    {
        $q=sanitize_text_field(wp_unslash($_GET['q']??''));
        $groups=self::index();
        if($q!==''){
            foreach($groups as$type=>$items){$groups[$type]=array_values(array_filter($items,fn($i)=>stripos($i['title'].' '.$i['summary'],$q)!==false));}
        }
        $total=array_sum(array_map('count',$groups));
        ob_start();?>
        <section class="atlas-search-hero"><p class="atlas-eyebrow">Search Atlas</p><h1>One search across the work.</h1><p class="atlas-muted">Find operational resources, payer requirements, Playbooks, local policy, and patient education without deciding which library probably has the answer first.</p><form class="atlas-search-box" method="get"><label class="screen-reader-text" for="atlas-global-search">Search Atlas</label><input id="atlas-global-search" autofocus name="q" value="<?php echo esc_attr($q);?>" placeholder="Try oxygen, home health, discharge…"><button class="atlas-button">Search</button></form></section>
        <?php if($q===''):?><div class="atlas-callout"><strong>Try a workflow term</strong><span>Search for “oxygen” to see how Atlas connects payer guidance, operational resources, Playbooks, local policy, and patient education around one task.</span></div><?php else:?><div class="atlas-results-head"><strong><?php echo $total;?> results for “<?php echo esc_html($q);?>”</strong><a href="<?php echo esc_url(home_url('/search'));?>">Clear search</a></div><?php endif;?>
        <?php foreach($groups as$type=>$items):if(!$items)continue;?><section class="atlas-search-group"><h2><?php echo esc_html($type);?> <span class="atlas-muted">· <?php echo count($items);?></span></h2><div class="atlas-list-card"><?php foreach($items as$item):?><a class="atlas-search-result" href="<?php echo esc_url(home_url($item['href']));?>"><span class="atlas-badge"><?php echo esc_html($type);?></span><div><strong><?php echo esc_html($item['title']);?></strong><small><?php echo esc_html($item['summary']);?></small></div><span>Open →</span></a><?php endforeach;?></div></section><?php endforeach;?>
        <?php if($q!==''&&$total===0):?><div class="atlas-empty"><h2>No Atlas results found</h2><p>Try a broader clinical or operational term. Search currently covers the visual fixture content only.</p></div><?php endif;?>
        <?php return(string)ob_get_clean();
    }

    private static function index(): array
    {
        $groups=['Resources'=>[],'Insurance'=>[],'Playbooks'=>[],'Knowledge Base'=>[],'Patient Resources'=>[]];
        foreach(Fixtures::resources() as $r){$groups['Resources'][]=['title'=>$r['title'],'summary'=>$r['summary'],'href'=>$r['slug']==='home-oxygen-qualification'?'/resources/home-oxygen-qualification':'/resources'];}
        foreach(Fixtures::insurance() as $r){$groups['Insurance'][]=['title'=>$r['title'],'summary'=>$r['summary'],'href'=>$r['slug']==='medicare-dme-oxygen'?'/insurance/medicare-dme-oxygen':'/insurance'];}
        foreach(Fixtures::playbooks() as $r){$groups['Playbooks'][]=['title'=>$r['title'],'summary'=>$r['summary'],'href'=>$r['slug']==='arrange-home-oxygen'?'/playbooks/arrange-home-oxygen':'/playbooks'];}
        foreach(Fixtures::knowledgeBase() as $r){$groups['Knowledge Base'][]=['title'=>$r['title'],'summary'=>$r['summary'],'href'=>$r['slug']==='discharge-documentation-standard'?'/knowledgebase/discharge-documentation-standard':'/knowledgebase'];}
        if(Fixtures::enabled()){$groups['Patient Resources']=[['title'=>'Home Oxygen: What to Expect','summary'=>'Patient-friendly guidance for oxygen delivery, safe use, and getting help.','href'=>'/patient-resources/home-oxygen-what-to-expect'],['title'=>'Preparing for Your First Home Health Visit','summary'=>'Patient guide to what home health does and how to prepare.','href'=>'/patient-resources']];}
        return $groups;
    }
}
