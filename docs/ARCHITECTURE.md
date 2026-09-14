# Architecture

## Source vs runtime

```text
christlikeness_home_website/  (Git — source we own)
  wordpress/themes/cacdemo/ ─────────┐ symlink
  wordpress/plugins/cacdemo-content/ ┐│ (only if ever needed)
                                     ││
/mnt/ai/workspaces/cacdemo/  (runtime — disposable, not in Git)
  wp-admin/, wp-includes/, *.php     pristine WordPress core
  wp-config.php                      local config + secrets
  wp-content/themes/cacdemo  ────────┘
  wp-content/plugins/cacdemo-content ┘
  wp-content/plugins/<third-party>   installed from WordPress.org, never committed
  wp-content/uploads/                local media
        │
     nginx + PHP-FPM ──► http://cacdemo.local
```

The runtime can be deleted and rebuilt at any time:

delete runtime → install pristine, checksum-verified WordPress → configure DB →
symlink theme → clean baseline (`scripts/bootstrap-local.sh`) → verify
(`scripts/test.sh`).

## Capability decision order

For every capability, evaluate in this order and stop at the first that fits well:

```text
WordPress core
      ↓ not adequate
Free, established plugin (prefer WordPress.org)
      ↓ not adequate, or unacceptable cost
Small custom Christlikeness extension (wordpress/plugins/cacdemo-content)
      ↓
Larger custom implementation — only when justified
```

### 1. WordPress core

Blocks, Site Editor, Navigation, Query Loop, patterns and synced patterns,
templates and template parts, theme.json, Media Library, users, roles and
capabilities, revisions, scheduling, taxonomies, REST API.

| Need | Native mechanism |
|---|---|
| Menus, dropdowns | Navigation block / `wp_navigation` |
| Logo, site name | Site Logo, Site Title blocks |
| Header, footer | Template parts |
| Layouts, reusable sections | Templates, patterns, synced patterns |
| Lists of content | Query Loop |
| Design tokens | theme.json |
| Content editing | Gutenberg |
| Pages, media, users, auth, permissions | Pages, Media Library, Users, roles/capabilities |
| Draft/publish/schedule/revisions | Native post statuses and revisions |
| Featured image, excerpt, categories | Native supports and taxonomy APIs |
| URLs, API | Permalinks, REST API |

### 2. Free established plugin

Likely areas: extra blocks, forms, events/calendars, sermons/media, galleries,
sliders, SEO, caching, redirects, security, backups, SMTP, social, maps,
accessibility, analytics, custom fields/content modeling, publishing workflow.

Evaluation checklist — answer all before adopting:

- Is the needed functionality in the **free** version?
- Actively maintained? Compatible with our WordPress and PHP versions?
- Works naturally with Gutenberg and block themes?
- Stores data in standard WordPress structures (posts, meta, taxonomies) where practical?
- Maintenance and security history?
- Frontend assets / performance overhead?
- Vendor lock-in? **What happens to our content if it is removed?**
- Duplicates something already installed?
- Much larger than our actual requirement?
- Can our theme style its output properly?

Rules: no paid/pro-only dependencies without explicit approval; no indiscriminate
installs; at most **one** block extension library alongside core blocks and
patterns; never modify plugin code — use documented hooks/APIs or style its public
output from our theme.

### 3. Custom code

Only in `wordpress/plugins/cacdemo-content/`, and only when core and free plugins
are inadequate, impose unacceptable complexity/lock-in/security/performance/UX
costs, or the requirement is genuinely Christlikeness-specific. Keep it small and
built on native APIs (`register_post_type`, `register_taxonomy`, `register_post_meta`,
blocks, REST).

### Proposal format

Before implementing any substantial capability, report:

```text
Native WordPress option:
Free plugin options:
Recommended approach:
Reason:
Tradeoffs:
```

## Plugin register

Adopted plugins are listed by slug in `config/plugins.txt` (enforced by
`scripts/test.sh`) and recorded here.

| Plugin | Capability | Why chosen over core/custom | Data stored | Removal impact | Approved |
|---|---|---|---|---|---|
| *(none yet)* | | | | | |

## Responsibilities

| WordPress core | Plugins (third-party) | Theme `cacdemo` | `cacdemo-content` (if ever needed) |
|---|---|---|---|
| Editor, blocks, Site Editor, navigation, media, users, roles, publishing, revisions, permalinks, REST | Capabilities core lacks (forms, events, extra blocks, SEO…) | **Christlikeness design**: typography, color, spacing, visual language, templates, parts, patterns, header/footer, responsive presentation, styling of core and plugin output | Genuinely Christlikeness-specific behavior not met by core or a suitable free plugin |

A plugin provides **capability**; the theme provides **Christlikeness design**.
Content models must never live in the theme — switching themes must not lose content.

## WordPress core and third-party code protection

Never modify `wp-admin/`, `wp-includes/`, root core PHP files, bundled libraries,
or third-party plugin/theme code. Configuration through `wp-config.php`, nginx,
WP-CLI, options and symlinks is environment configuration, not a code change. If
core is invalid, reinstall it from verified official source. If something appears
to need a core or plugin patch, the design is wrong — find the hook, block, theme
or plugin-level approach.

## Admin interoperability

Admins must be able to change the Site Logo, edit navigation and submenus, edit
the footer template part, use ordinary blocks, set featured images and use native
publishing, and see the theme respond.

## Testing

Test our code and integration boundaries, not WordPress itself. See
[tests/README.md](../tests/README.md).

## Current state

- WordPress 7.1, PHP 8.4 (FPM), MariaDB 10.11, nginx 1.24.
- Theme scaffold: `style.css`, `theme.json`, `templates/{index,page,front-page}.html`,
  `parts/{header,footer}.html` — core blocks only.
- Header: Site Logo + Site Title + Navigation (no `ref`: WordPress uses the most
  recently published navigation menu until one is chosen in the Site Editor).
- **Clean baseline:** no pages, posts, media, comments or custom menus. The only
  records are WordPress's own auto-created page-list "Navigation" menu and empty
  global styles. The information architecture will come from the christlikeness.ca
  content harvest and design review.
- No plugins active or approved.

## Staging origin

`cacdemo.crishub.com` was created by Hostinger's AI site builder (WordPress 7.1,
`hostinger-ai-theme`, Hostinger AI/onboarding/Reach plugins, LiteSpeed Cache). None
of that is reproduced locally, and its generated content is not imported. Its
proprietary contact form and newsletter blocks are deferred requirements to be
evaluated with the decision order above.
