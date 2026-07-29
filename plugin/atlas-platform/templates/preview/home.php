<?php
/** @var array{query: string, resources: list<array<string, string>>, total: int, user_name: string, organization_name: string, has_organization: bool, navigation: list<array{slug:string,label:string,icon:string,url:string,capability:string,current:bool}>} $view */
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="atlas-preview-wrap">
    <div class="atlas-shell">
        <header class="atlas-topbar">
            <div class="atlas-brand" aria-label="<?php echo esc_attr__('Atlas home', 'atlas-platform'); ?>">
                <span class="atlas-brand-mark" aria-hidden="true">A</span>
                <span>
                    <strong>Atlas</strong>
                    <small><?php echo esc_html__('Clinical operations', 'atlas-platform'); ?></small>
                </span>
            </div>
            <div class="atlas-context">
                <span class="atlas-preview-badge"><?php echo esc_html__('Foundation preview', 'atlas-platform'); ?></span>
                <span class="atlas-context-copy">
                    <strong><?php echo esc_html($view['user_name']); ?></strong>
                    <small><?php echo esc_html($view['organization_name']); ?></small>
                </span>
                <span class="atlas-avatar" aria-hidden="true"><?php echo esc_html(strtoupper(substr($view['user_name'], 0, 1))); ?></span>
            </div>
        </header>

        <div class="atlas-layout">
            <nav class="atlas-sidebar" aria-label="<?php echo esc_attr__('Atlas primary navigation', 'atlas-platform'); ?>">
                <?php foreach ($view['navigation'] as $item) : ?>
                    <a class="atlas-nav-item<?php echo $item['current'] ? ' is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>"<?php echo $item['current'] ? ' aria-current="page"' : ''; ?>>
                        <span class="dashicons <?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></span>
                        <?php echo esc_html($item['label']); ?>
                    </a>
                <?php endforeach; ?>
                <div class="atlas-sidebar-note">
                    <strong><?php echo esc_html($view['has_organization'] ? __('Organization context', 'atlas-platform') : __('Organization required', 'atlas-platform')); ?></strong>
                    <p><?php echo esc_html($view['has_organization'] ? $view['organization_name'] : __('Ask an Atlas administrator to assign your organization before using organization-owned content.', 'atlas-platform')); ?></p>
                </div>
            </nav>

            <main class="atlas-main" id="atlas-main-content">
                <section class="atlas-welcome" aria-labelledby="atlas-welcome-title">
                    <p class="atlas-eyebrow"><?php echo esc_html__('Welcome to Atlas', 'atlas-platform'); ?></p>
                    <h1 id="atlas-welcome-title"><?php echo esc_html__('Turn reliable information into practical action.', 'atlas-platform'); ?></h1>
                    <p><?php echo esc_html__('Search operational guidance, reviewed workflows, and reusable clinical resources without rebuilding the process from scratch.', 'atlas-platform'); ?></p>

                    <form class="atlas-search" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" role="search">
                        <input type="hidden" name="page" value="atlas">
                        <label for="atlas-search-input"><?php echo esc_html__('Search the preview resources', 'atlas-platform'); ?></label>
                        <div class="atlas-search-control">
                            <span class="dashicons dashicons-search" aria-hidden="true"></span>
                            <input id="atlas-search-input" name="atlas_search" type="search" value="<?php echo esc_attr($view['query']); ?>" placeholder="<?php echo esc_attr__('Try “coverage”, “injection”, or “tracking”', 'atlas-platform'); ?>">
                            <button type="submit"><?php echo esc_html__('Search', 'atlas-platform'); ?></button>
                        </div>
                    </form>
                </section>

                <section class="atlas-section" aria-labelledby="atlas-resources-title">
                    <div class="atlas-section-heading">
                        <div>
                            <p class="atlas-eyebrow"><?php echo esc_html__('Frequently used', 'atlas-platform'); ?></p>
                            <h2 id="atlas-resources-title">
                                <?php
                                echo $view['query'] === ''
                                    ? esc_html__('Explore the Atlas experience', 'atlas-platform')
                                    : esc_html(sprintf(__('Results for “%s”', 'atlas-platform'), $view['query']));
                                ?>
                            </h2>
                        </div>
                        <span class="atlas-result-count">
                            <?php echo esc_html(sprintf(_n('%d result', '%d results', $view['total'], 'atlas-platform'), $view['total'])); ?>
                        </span>
                    </div>

                    <?php if ($view['resources'] === []) : ?>
                        <div class="atlas-empty-state">
                            <span class="dashicons dashicons-search" aria-hidden="true"></span>
                            <h3><?php echo esc_html__('No preview resources matched.', 'atlas-platform'); ?></h3>
                            <p><?php echo esc_html__('Try a broader term such as coverage, patient, clinical, or organization.', 'atlas-platform'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=atlas')); ?>"><?php echo esc_html__('Clear search', 'atlas-platform'); ?></a>
                        </div>
                    <?php else : ?>
                        <div class="atlas-resource-grid">
                            <?php foreach ($view['resources'] as $resource) : ?>
                                <article class="atlas-resource-card atlas-tone-<?php echo esc_attr($resource['tone']); ?>">
                                    <div class="atlas-card-meta">
                                        <span><?php echo esc_html($resource['type']); ?></span>
                                        <span class="atlas-status-dot"><?php echo esc_html($resource['status']); ?></span>
                                    </div>
                                    <h3><?php echo esc_html($resource['title']); ?></h3>
                                    <p><?php echo esc_html($resource['summary']); ?></p>
                                    <footer>
                                        <span class="atlas-authority"><?php echo esc_html($resource['authority']); ?></span>
                                        <small><?php echo esc_html($resource['updated']); ?></small>
                                    </footer>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="atlas-activity-grid" aria-label="<?php echo esc_attr__('Atlas activity preview', 'atlas-platform'); ?>">
                    <article class="atlas-activity-card">
                        <p class="atlas-eyebrow"><?php echo esc_html__('Continue working', 'atlas-platform'); ?></p>
                        <h2><?php echo esc_html__('Your focused work will appear here.', 'atlas-platform'); ?></h2>
                        <p><?php echo esc_html__('Future builds will preserve drafts, review tasks, and recently opened resources without crowding the home screen.', 'atlas-platform'); ?></p>
                    </article>
                    <article class="atlas-activity-card is-update">
                        <p class="atlas-eyebrow"><?php echo esc_html__('Trust stays visible', 'atlas-platform'); ?></p>
                        <h2><?php echo esc_html__('Sources, scope, and review state travel together.', 'atlas-platform'); ?></h2>
                        <p><?php echo esc_html__('Atlas will keep official policy, reviewed interpretation, organization guidance, and community experience clearly distinct.', 'atlas-platform'); ?></p>
                    </article>
                </section>
            </main>
        </div>
    </div>
</div>
