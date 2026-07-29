<?php
/** @var list<Atlas\Platform\Organizations\Domain\Organization> $organizations */
/** @var ?Atlas\Platform\Organizations\Domain\Organization $current */
declare(strict_types=1);
if (! defined('ABSPATH')) { exit; }
$messages = ['selected' => __('Organization context updated.', 'atlas-platform'), 'selection-failed' => __('That organization is not available.', 'atlas-platform'), 'created' => __('Organization created.', 'atlas-platform'), 'existing' => __('The existing organization request was restored.', 'atlas-platform'), 'create-failed' => __('The organization could not be created. Check the name and slug.', 'atlas-platform')];
?>
<div class="atlas-preview-wrap"><div class="atlas-shell"><main class="atlas-resource-main">
    <p class="atlas-eyebrow"><?php echo esc_html__('Atlas administration', 'atlas-platform'); ?></p>
    <h1><?php echo esc_html__('Organizations', 'atlas-platform'); ?></h1>
    <p><?php echo esc_html__('Choose the active organization context used for organization-owned Atlas content.', 'atlas-platform'); ?></p>
    <?php if (isset($messages[$notice])) : ?><div class="notice notice-info inline"><p><?php echo esc_html($messages[$notice]); ?></p></div><?php endif; ?>
    <section class="atlas-admin-panel" aria-labelledby="atlas-org-list-title">
        <h2 id="atlas-org-list-title"><?php echo esc_html__('Available organizations', 'atlas-platform'); ?></h2>
        <?php if ($organizations === []) : ?><p><?php echo esc_html__('No active organization memberships are available.', 'atlas-platform'); ?></p><?php endif; ?>
        <div class="atlas-selection-list">
        <?php foreach ($organizations as $organization) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="atlas-selection-row">
                <input type="hidden" name="action" value="atlas_select_organization"><input type="hidden" name="organization_id" value="<?php echo esc_attr($organization->id); ?>">
                <?php wp_nonce_field('atlas_select_organization'); ?>
                <span><strong><?php echo esc_html($organization->name); ?></strong><small><?php echo esc_html($organization->slug); ?></small></span>
                <?php if ($current?->id === $organization->id) : ?><span class="atlas-status-dot"><?php echo esc_html__('Current', 'atlas-platform'); ?></span><?php else : ?><button class="button" type="submit"><?php echo esc_html__('Use organization', 'atlas-platform'); ?></button><?php endif; ?>
            </form>
        <?php endforeach; ?>
        </div>
    </section>
    <?php if (current_user_can('atlas_manage_organizations')) : ?>
    <section class="atlas-admin-panel" aria-labelledby="atlas-org-create-title"><h2 id="atlas-org-create-title"><?php echo esc_html__('Create organization', 'atlas-platform'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="atlas-form-grid"><input type="hidden" name="action" value="atlas_create_organization"><input type="hidden" name="idempotency_key" value="<?php echo esc_attr(wp_generate_uuid_4()); ?>"><?php wp_nonce_field('atlas_create_organization'); ?>
            <label><?php echo esc_html__('Name', 'atlas-platform'); ?><input required maxlength="255" name="name" type="text"></label>
            <label><?php echo esc_html__('Slug', 'atlas-platform'); ?><input required maxlength="191" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" name="slug" type="text"></label>
            <p><button class="button button-primary" type="submit"><?php echo esc_html__('Create organization', 'atlas-platform'); ?></button></p>
        </form>
    </section><?php endif; ?>
</main></div></div>
