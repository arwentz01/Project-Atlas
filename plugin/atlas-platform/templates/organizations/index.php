<?php
/** @var list<Atlas\Platform\Organizations\Domain\Organization> $organizations */
/** @var ?Atlas\Platform\Organizations\Domain\Organization $current */
declare(strict_types=1);
if (! defined('ABSPATH')) { exit; }
$messages = ['selected'=>__('Organization context updated.','atlas-platform'),'selection-failed'=>__('That organization is not available.','atlas-platform'),'created'=>__('Organization created.','atlas-platform'),'existing'=>__('The existing organization request was restored.','atlas-platform'),'create-failed'=>__('The organization could not be created. Check the name and slug.','atlas-platform'),'invited'=>__('Invitation sent.','atlas-platform'),'invite-failed'=>__('The invitation could not be sent.','atlas-platform'),'invitation-accepted'=>__('Invitation accepted.','atlas-platform'),'invitation-invalid'=>__('The invitation is invalid, expired, or belongs to another email address.','atlas-platform'),'invitation-revoked'=>__('Invitation revoked.','atlas-platform'),'roles-updated'=>__('Member roles updated.','atlas-platform'),'member-removed'=>__('Member removed.','atlas-platform'),'member-action-failed'=>__('The member operation could not be completed.','atlas-platform'),'branding-saved'=>__('Branding profile saved.','atlas-platform'),'branding-failed'=>__('The branding profile could not be saved.','atlas-platform'),'context-required'=>__('Select an organization first.','atlas-platform')];
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
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="atlas-form-grid"><input type="hidden" name="action" value="atlas_create_organization"><input type="hidden" name="idempotency_key" value="<?php echo esc_attr(wp_generate_uuid4()); ?>"><?php wp_nonce_field('atlas_create_organization'); ?>
            <label><?php echo esc_html__('Name', 'atlas-platform'); ?><input required maxlength="255" name="name" type="text"></label>
            <label><?php echo esc_html__('Slug', 'atlas-platform'); ?><input required maxlength="191" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" name="slug" type="text"></label>
            <p><button class="button button-primary" type="submit"><?php echo esc_html__('Create organization', 'atlas-platform'); ?></button></p>
        </form>
    </section><?php endif; ?>
    <?php if ($current !== null && current_user_can('atlas_manage_members')) : ?>
    <section class="atlas-admin-panel"><h2><?php echo esc_html__('Members and invitations','atlas-platform'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="atlas-form-grid"><input type="hidden" name="action" value="atlas_invite_member"><?php wp_nonce_field('atlas_invite_member'); ?>
            <label><?php echo esc_html__('Email address','atlas-platform'); ?><input required type="email" name="email"></label>
            <fieldset><legend><?php echo esc_html__('Initial roles','atlas-platform'); ?></legend><?php foreach($roles as $role):?><label><input type="checkbox" name="roles[]" value="<?php echo esc_attr($role); ?>"<?php checked($role,'member'); ?>> <?php echo esc_html(ucwords(str_replace('_',' ',$role))); ?></label><?php endforeach;?></fieldset>
            <p><button class="button button-primary"><?php echo esc_html__('Send invitation','atlas-platform'); ?></button></p>
        </form>
        <?php foreach($invitations as $invitation):?><div class="atlas-selection-row"><span><strong><?php echo esc_html($invitation['email']); ?></strong><small><?php echo esc_html(sprintf(__('Expires %s','atlas-platform'),$invitation['expires_at'])); ?></small></span><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="atlas_revoke_invitation"><input type="hidden" name="invitation_id" value="<?php echo esc_attr($invitation['id']); ?>"><?php wp_nonce_field('atlas_revoke_invitation'); ?><button class="button"><?php echo esc_html__('Revoke','atlas-platform'); ?></button></form></div><?php endforeach;?>
        <?php foreach($members as $member):?><div class="atlas-selection-row"><span><strong><?php echo esc_html($member['display_name']?:$member['user_email']); ?></strong><small><?php echo esc_html($member['user_email']); ?></small></span><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="atlas_update_member_roles"><input type="hidden" name="membership_id" value="<?php echo esc_attr($member['id']); ?>"><?php wp_nonce_field('atlas_update_member_roles'); ?><div class="atlas-role-checks"><?php foreach($roles as $role):?><label><input type="checkbox" name="roles[]" value="<?php echo esc_attr($role); ?>"<?php checked(in_array($role,$member['roles'],true)); ?>><?php echo esc_html(ucwords(str_replace('_',' ',$role))); ?></label><?php endforeach;?></div><button class="button"><?php echo esc_html__('Update roles','atlas-platform'); ?></button></form><?php if((int)$member['user_id']!==get_current_user_id()):?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="atlas_remove_member"><input type="hidden" name="membership_id" value="<?php echo esc_attr($member['id']); ?>"><?php wp_nonce_field('atlas_remove_member'); ?><button class="button"><?php echo esc_html__('Remove','atlas-platform'); ?></button></form><?php endif;?></div><?php endforeach;?>
    </section>
    <?php endif; ?>
    <?php if ($current !== null && $brand !== null && current_user_can('atlas_manage_branding')) : ?>
    <section class="atlas-admin-panel"><h2><?php echo esc_html__('Organization branding','atlas-platform'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="atlas-authoring-form"><input type="hidden" name="action" value="atlas_save_branding"><?php wp_nonce_field('atlas_save_branding'); ?>
            <div class="atlas-form-columns"><label><?php echo esc_html__('Display name','atlas-platform'); ?><input required maxlength="255" name="display_name" value="<?php echo esc_attr($brand->displayName); ?>"></label><label><?php echo esc_html__('Primary color','atlas-platform'); ?><input required type="color" name="primary_color" value="<?php echo esc_attr($brand->primaryColor); ?>"></label><label><?php echo esc_html__('Logo attachment ID','atlas-platform'); ?><input type="number" min="1" name="logo_attachment_id" value="<?php echo esc_attr((string)($brand->logoAttachmentId??'')); ?>"></label></div>
            <label><?php echo esc_html__('Contact block','atlas-platform'); ?><textarea maxlength="1000" name="contact_block"><?php echo esc_textarea($brand->contactBlock); ?></textarea></label><label><?php echo esc_html__('Approved footer','atlas-platform'); ?><textarea maxlength="1000" name="footer"><?php echo esc_textarea($brand->footer); ?></textarea></label>
            <div class="atlas-brand-preview" style="--atlas-brand-color:<?php echo esc_attr($brand->primaryColor); ?>"><strong><?php echo esc_html($brand->displayName); ?></strong><p><?php echo nl2br(esc_html($brand->contactBlock)); ?></p><small><?php echo esc_html($brand->footer); ?></small></div><p><button class="button button-primary"><?php echo esc_html__('Save branding','atlas-platform'); ?></button></p>
        </form>
    </section>
    <?php endif; ?>
</main></div></div>
