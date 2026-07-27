# Atlas Design Foundation

Status: Initial design brief

## Product character

Atlas should feel calm, precise, credible, and fast. It is a clinical operations and learning platform, not a lifestyle brand, social network, or traditional WordPress site.

The interface should reduce cognitive load during real work. Users may be interrupted, tired, unfamiliar with a topic, or trying to find an answer while another person is waiting.

## Design principles

### Information first

The most relevant answer, status, source, and next action should be visible before decoration.

### Confidence is visible

Users should immediately recognize:

- Verified source material
- Organization-approved material
- Community-reported experience
- Draft or unreviewed content
- Material that is due for review

### Progressive disclosure

Show a concise operational answer first, then allow users to expand citations, rationale, exceptions, document history, and related resources.

### Familiar patterns

Use predictable navigation, tables, cards, forms, filters, breadcrumbs, and status labels. Clinical users should not need to learn a novel interface vocabulary.

### Print is a first-class surface

Patient handouts, logs, checklists, and quick-reference cards must have intentional print layouts rather than being ordinary screens with the navigation hidden.

### Accessibility by default

Target WCAG 2.2 AA, including keyboard access, visible focus, sufficient contrast, semantic headings, labels, error summaries, non-color status indicators, and scalable text.

## Primary application navigation

```text
Home
Search
Knowledge Base
Patient Education
Clinical References
Insurance & Coverage
Workflows
Directory & Community
Organizations
Review Center
Reports
Settings
```

Navigation items appear according to role and enabled modules.

## Global header

The application header should contain:

- Atlas or future product identity
- Universal search
- Current organization switcher
- Create action where permitted
- Notifications
- Help
- User menu

The organization context must remain obvious whenever a user can belong to more than one organization.

## Core screen types

### Dashboard

Role-aware overview showing assigned reviews, expiring content, recent resources, organization activity, saved packets, and platform notices.

### Search results

Unified search across resource types with filters for:

- Content type
- Scope
- Organization
- Payer
- Clinical topic
- Audience
- Language
- Verification status
- Review status
- Updated date

Every result should show type, title, short answer or summary, scope, status, source freshness, and relevant tags.

### Resource detail

Recommended information order:

1. Title and purpose
2. Status and scope
3. Concise operational summary
4. Actions such as print, save, add to packet, fork, or start workflow
5. Main content
6. Warnings and exceptions
7. Sources and effective dates
8. Related resources
9. Version history and review information

### Resource editor

Use structured fields and modular content blocks rather than one unrestricted visual editor. Fields vary by resource type but share title, summary, audience, scope, tags, sources, review dates, and status.

### Organization workspace

Includes:

- Overview
- Members and roles
- Branding
- Resource library
- Workflows
- Patient packets
- Directory notes
- Review queue
- Audit history
- Data export and organization settings

### Review center

A dedicated queue for assigned reviews, requested changes, due dates, source changes, and expiring resources. Reviewers must be able to compare versions and see cited sources without leaving the review flow.

### Packet builder

A stepwise interface:

1. Choose organization branding
2. Select approved resources
3. Choose language and print options
4. Arrange packet order
5. Preview
6. Save reusable packet or print

No patient name or identifying fields are included.

## Status language

Use consistent status labels:

- Draft
- In Review
- Changes Requested
- Approved
- Published
- Review Due
- Superseded
- Archived

Community confidence is separate:

- Unconfirmed report
- Community reported
- Recently confirmed
- Source verified

## Initial visual direction

Until branding is selected, use neutral design tokens rather than a temporary brand identity.

### Typography

- Highly legible sans-serif interface family
- Comfortable default reading size
- Clear hierarchy with restrained heading scale
- Monospace only for identifiers or technical values

### Layout

- Maximum readable line length for content
- Dense but breathable tables
- Consistent 8-point spacing system
- Responsive desktop-first workspace with functional tablet and mobile views

### Components to design first

1. Application shell
2. Organization switcher
3. Universal search field
4. Status badge
5. Provenance/source panel
6. Resource card and result row
7. Data table
8. Form controls and validation
9. Empty state
10. Review comparison panel
11. Print header and footer
12. Organization branding preview

## WordPress separation

The standard WordPress administration interface may be used for platform maintenance during early development, but ordinary Atlas users should experience a purpose-built application shell.

Hide or remove irrelevant WordPress menus for non-platform administrators. Do not expose Posts, Comments, Appearance, Plugins, or Tools merely because WordPress provides them.

## First wireframes

Design these screens before detailed visual branding:

1. Sign-in and organization selection
2. Role-aware dashboard
3. Search results
4. Resource detail
5. Organization overview
6. Organization member management
7. Organization branding settings
8. Review queue
9. Resource editor
10. Patient packet builder

## Design acceptance questions

For every screen, verify:

- What is the user's immediate question?
- What is the primary action?
- What information establishes trust?
- Is organization context visible?
- Is content status visible?
- Can the screen be used by keyboard?
- What happens when data is empty, unavailable, stale, or unauthorized?
- Does the design accidentally invite entry of patient information?
