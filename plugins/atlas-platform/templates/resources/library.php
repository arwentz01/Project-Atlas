<?php
/** @var Atlas\Platform\Resources\Search\SearchCriteria $criteria */
/** @var Atlas\Platform\Resources\Search\SearchPage $results */
/** @var list<string> $types */
declare(strict_types=1);
if (! defined('ABSPATH')) { exit; }
$baseUrl = admin_url('admin.php?page=atlas-resources');
?>
<div class="atlas-preview-wrap"><div class="atlas-shell"><main class="atlas-resource-main">
    <p class="atlas-eyebrow"><?php echo esc_html__('Reviewed knowledge', 'atlas-platform'); ?></p>
    <div class="atlas-section-heading"><div><h1><?php echo esc_html__('Resources', 'atlas-platform'); ?></h1><p><?php echo esc_html__('Search published platform and current-organization guidance with its review state and source visible.', 'atlas-platform'); ?></p></div></div>
    <?php if ($error !== '') : ?><div class="notice notice-error inline"><p><?php echo esc_html($error); ?></p></div><?php endif; ?>
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="atlas-library-filters" role="search">
        <input type="hidden" name="page" value="atlas-resources">
        <label for="atlas-resource-search"><?php echo esc_html__('Search resources', 'atlas-platform'); ?><input id="atlas-resource-search" type="search" name="atlas_search" maxlength="100" value="<?php echo esc_attr($criteria->query); ?>"></label>
        <label for="atlas-resource-type"><?php echo esc_html__('Resource type', 'atlas-platform'); ?><select id="atlas-resource-type" name="atlas_type"><option value=""><?php echo esc_html__('All types', 'atlas-platform'); ?></option><?php foreach ($types as $type) : ?><option value="<?php echo esc_attr($type); ?>"<?php selected($criteria->type, $type); ?>><?php echo esc_html(ucwords(str_replace('_', ' ', $type))); ?></option><?php endforeach; ?></select></label>
        <button class="button button-primary" type="submit"><?php echo esc_html__('Search', 'atlas-platform'); ?></button>
        <?php if ($criteria->query !== '' || $criteria->type !== null) : ?><a class="button" href="<?php echo esc_url($baseUrl); ?>"><?php echo esc_html__('Clear', 'atlas-platform'); ?></a><?php endif; ?>
    </form>
    <p class="atlas-result-count"><?php echo esc_html(sprintf(_n('%d resource on this page', '%d resources on this page', count($results->results), 'atlas-platform'), count($results->results))); ?></p>
    <?php if ($results->results === []) : ?><section class="atlas-empty-state"><span class="dashicons dashicons-book-alt" aria-hidden="true"></span><h2><?php echo esc_html__('No published resources found.', 'atlas-platform'); ?></h2><p><?php echo esc_html__('Try a broader search, clear the type filter, or ask an editor to publish approved content.', 'atlas-platform'); ?></p></section><?php else : ?>
    <div class="atlas-library-list">
    <?php foreach ($results->results as $resource) : ?>
        <article class="atlas-library-row"><div><div class="atlas-card-meta"><span><?php echo esc_html(ucwords(str_replace('_', ' ', $resource->type))); ?></span><span><?php echo esc_html(ucfirst($resource->scope)); ?></span><span class="atlas-status-dot"><?php echo esc_html(ucwords(str_replace('_', ' ', $resource->reviewStatus))); ?></span></div><h2><a href="<?php echo esc_url(add_query_arg(['page'=>'atlas-resource','id'=>$resource->id], admin_url('admin.php'))); ?>"><?php echo esc_html($resource->title); ?></a></h2><p><?php echo esc_html($resource->summary); ?></p><small><?php echo esc_html($resource->sourcePublisher !== null ? sprintf(__('Source: %s — %s', 'atlas-platform'), $resource->sourcePublisher, $resource->sourceTitle ?? '') : __('Source details unavailable', 'atlas-platform')); ?></small></div><a class="button" href="<?php echo esc_url(add_query_arg(['page'=>'atlas-resource','id'=>$resource->id], admin_url('admin.php'))); ?>"><?php echo esc_html__('View', 'atlas-platform'); ?></a></article>
    <?php endforeach; ?></div><?php endif; ?>
    <nav class="atlas-pagination" aria-label="<?php echo esc_attr__('Resource result pages', 'atlas-platform'); ?>">
        <?php if ($results->page > 1) : ?><a class="button" href="<?php echo esc_url(add_query_arg(['atlas_search'=>$criteria->query,'atlas_type'=>$criteria->type,'atlas_page'=>$results->page-1],$baseUrl)); ?>"><?php echo esc_html__('Previous', 'atlas-platform'); ?></a><?php endif; ?>
        <span><?php echo esc_html(sprintf(__('Page %d', 'atlas-platform'), $results->page)); ?></span>
        <?php if ($results->hasMore) : ?><a class="button" href="<?php echo esc_url(add_query_arg(['atlas_search'=>$criteria->query,'atlas_type'=>$criteria->type,'atlas_page'=>$results->page+1],$baseUrl)); ?>"><?php echo esc_html__('Next', 'atlas-platform'); ?></a><?php endif; ?>
    </nav>
</main></div></div>
