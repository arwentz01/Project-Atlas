# Atlas Design Language

## Product Character

Atlas should feel calm, credible, fast, and purpose-built. It should not resemble a generic WordPress admin, marketing site, or novelty AI product.

## Context Over Navigation

- Assume the user's organization when they belong to one organization.
- Do not show a persistent organization switcher by default.
- Show organization identity inside the user menu.
- Show "Switch organization" only when the user belongs to more than one organization.
- Preserve task context when opening, reviewing, or editing content.

## Application Shell

Top bar:

- Atlas identity
- Universal search
- Notifications
- User menu

User menu:

- User identity
- Current organization
- Conditional organization switch action
- Profile
- Preferences
- Help
- Sign out

Primary navigation:

- Home
- Knowledge Base
- Patient Education
- Clinical References
- Insurance & Coverage
- Workflows
- Directory & Community
- Review Center, when permitted
- Administration, when permitted
- Settings

## Interaction Rules

- Search is the primary entry point for routine clinical use.
- Prefer inline actions, drawers, and contextual panels over disruptive modal dialogs.
- Do not hide critical state changes behind animation.
- Never lose entered work.
- Use autosave only when save state is clearly communicated.
- Avoid full-page reloads where a focused update is practical.
- Do not show controls a user cannot use.

## Visual Rules

- Information hierarchy must be obvious without relying on color alone.
- Use no more than three primary type sizes within one working view.
- Reserve warning colors for meaningful warnings.
- Distinguish verified guidance, organization policy, and community reports with labels, iconography, and text, not color alone.
- Optimize tables for scanning and comparison.
- Keep clinical content readable when printed in black and white.

## Motion

- Motion must explain a transition or preserve context.
- No bouncing, decorative loading, or attention-seeking animation.
- Loading states should communicate what is happening and preserve layout stability.

## Home Experience

The home screen should prioritize:

1. Universal search
2. Continue working
3. Favorites
4. Recently viewed
5. Recently updated or review-relevant content

Administrative analytics must not crowd the clinician's primary workspace.
