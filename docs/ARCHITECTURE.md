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
| `secure-custom-fields` (SCF, WordPress.org, free) | Structured content definitions: the `centre` post type and the "Centre details" field group; block bindings (`acf/field`) so theme patterns display centre fields without custom PHP | Core has no admin UI for custom post types/fields; SCF is maintained on WordPress.org, stores data as ordinary posts and post meta, and its definitions export to JSON (`config/scf/`) so they are reviewable in Git. A custom plugin would be code we maintain for a capability that already exists | Definitions: `acf-post-type` / `acf-field-group` posts (imported from `config/scf/` by `scripts/scf-sync.php`). Centre records: `centre` posts + plain post meta (`centre_*` keys) | Centre post type stops registering: centre records remain in the database but disappear from admin and front end; centre-derived sections (Centres directory, footer centres, visit strip) render nothing. Meta survives, so reinstalling or re-registering the type restores everything | D-1, 2026-09-14 |
| `cacdemo-content` (**our code**, `wordpress/plugins/cacdemo-content/`) | Sermon media block (a sermon's Sources as tabs: YouTube via core oEmbed, Facebook via its embed player, audio via the core player); block bindings source `cacdemo/sermon` (location, first published, last updated); "More in this series" Query Loop filter; uploads made to a sermon are named `YYYYMMDD_Sermon_Title_…` and filed under the sermon date's `YYYY/MM`; sermon browsing (filter bar block, Table view block grouped by year/series/speaker/topic, filters and sort applied to the Grid/List Query Loops and archives); featured sermons query; sermon comments controls (site switch in Settings → Discussion, closed by default, publish immediately, collapsed bar); ministries: "Ways to serve" Query Loop filter (a ministry's public serve roles) and bindings source `cacdemo/ministry` (ways-to-serve summary, "Needed now", experience, invitation defaults, contact link); contextual publishing: section-scoped capabilities for sermons, ministries and ways to serve (Contributor/Publisher assignments in user meta, Page contributor role, capabilities added to administrator/editor), `cacdemo/publish-actions` block, Users → Page contributors screen; Content Manager at `/manage/` (own screens and SCF front-end forms over the same capabilities; `includes/manage/`); social channels block `cacdemo/channels` (compact / list / feeds with Facebook's page widget loaded in the browser when scrolled near) | Core Query Loop cannot filter by the current post's terms, repeater rows and derived values cannot be bound by core or SCF, and core files block-editor uploads by upload month with the original name. All small and Christlikeness-specific | Nothing (reads SCF meta and terms) | Sermon pages lose the players and the derived details, and the series band hides. No data affected | 2026-09-15 |

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

## Multi-centre architecture

Christlikeness is **one church website** serving multiple centres/campuses. No separate
WordPress installations and no WordPress Multisite unless explicitly approved.

**One canonical source per centre.** Each centre (name, address, service times, map
link, contact details, status, etc.) is maintained once as structured data. Editors
manage centre information in one place; the website decides where and how it appears.

- Never duplicate addresses, service times, maps or contact details as hand-maintained
  text across templates or pages.
- Wherever centre facts appear — header, footer, New Here, About, Centres, sitemap,
  service-location components, events, ministries, other views — derive them from the
  centre records whenever practical.
- Centre directories and repeated centre listings are generated automatically from
  **active** centre records (adding, closing or editing a centre updates every view).
- Church identity and global content (name, mission, beliefs, branding) stay shared.

**Association is optional.** Content (events, ministries, sermons, pages, media) may be:

- church-wide / all centres (the default — no association required),
- associated with one centre, or
- associated with several selected centres.

Do not force every piece of content to have a centre.

**Implementation** (a later phase) goes through the capability decision order — native
structured content first (e.g. a centre content type and a shared centre taxonomy for
associations), an established free content-modeling plugin if core alone is
insufficient, custom code in `cacdemo-content` last. The choice must allow Query Loop /
block-based listings so presentation stays in the theme. Future centre calendars
(see below) should attach to these same centre records.

## Future Google integration (not current phase)

Planned, but **not** to be built during the content/migration phase.

| Area | Expected future uses |
|---|---|
| Google Calendar | Church-wide, centre/campus and ministry calendars; event synchronization; automatically generated upcoming-event views |
| Google Drive | Document integration; possible image/gallery drop location; shared church resources |
| Automation | Publishing workflows, notifications, synchronization, scheduled processing, other Google Workspace integrations |

**Before implementing any of these**, decide and record for each integration:

- **Source of truth** — Google or WordPress (e.g. is an event authored in Google
  Calendar or in WordPress?).
- **Sync direction** — one-way (which way) or two-way, and how conflicts resolve.
- **Ownership** — which Google account/Workspace owns the data, managed through
  configuration and secrets, never hard-coded.
- The capability decision order still applies (core → free plugin → custom).

**Rules for now:**

- No Google OAuth/API infrastructure, credentials, placeholder integrations or stub
  code. No Google account details in code, config or docs.
- Don't shape current WordPress content around an unimplemented Google API.
- Do avoid choices that would make these integrations harder later:
  - Model events, centres and ministries as distinct, consistently identified content
    (e.g. taxonomies/terms, not free text scattered in page copy), so they can later
    map to separate calendars.
  - Keep dates, times and locations as structured data wherever events are stored,
    not only inside prose or images.
  - Prefer event/calendar plugins that support standard import/export (iCal/ICS) or
    have a documented Google Calendar path, and don't lock event data into a
    proprietary format.
  - Keep media in the WordPress Media Library with meaningful titles/alt text, so a
    future Drive-based intake can map onto it.

## Information architecture, media and sources of truth

Content classes, site structure, generated views, and the Centres / Resources / Sitemap /
access-control analyses are in [INFORMATION-ARCHITECTURE.md](INFORMATION-ARCHITECTURE.md).
The durable rules it establishes:

| System | Role |
|---|---|
| WordPress | Canonical: pages, navigation, centres, resources, classification, presentation |
| YouTube | Canonical public video host (no routine video files in Git, uploads, theme or plugin) |
| WordPress Media Library | Canonical curated site imagery (only images pages use) |
| External/drop storage | Future candidate source for high-volume gallery photos (draft → review → publish) |
| Google Calendar | Future calendar integration; ownership TBD |
| Facebook / social | Distribution and community; never canonical for essential information; no Facebook CDN image dependencies; Group content not assumed public; no Meta API work now |

Every integration declares its role (canonical source, synchronized source, publishing
destination, embed provider or external archive). No uncontrolled bidirectional sync.

**Responsive and portable.** All structures must work on desktop, tablet and mobile. The
site must be rebuildable from pristine WordPress + theme + approved plugins + optional
`cacdemo-content` + exported content/configuration; content never lives in theme templates.

## Testing

Test our code and integration boundaries, not WordPress itself. See
[tests/README.md](../tests/README.md).

## Current state

- WordPress 7.1, PHP 8.4 (FPM), MariaDB 10.11, nginx 1.24.
- Theme scaffold: `style.css`, `theme.json`, `templates/{index,page,front-page}.html`,
  `parts/{header,footer}.html` — core blocks only.
- Header: Site Logo + Site Title + Navigation (no `ref`: WordPress uses the most
  recently published navigation menu until one is chosen in the Site Editor).
- Content: 9 empty Pages (Home, About, New Here, Centres, Sermons, Ministries, Connect,
  Music, Contact), Home as static front page, native "Main" navigation menu. No custom
  types or taxonomies beyond `centre`; no media.
- Centres (D-1 = Secure Custom Fields): `centre` post type + "Centre details" fields,
  definitions in `config/scf/`, imported by bootstrap. North York and Scarborough exist
  as **drafts** pending church confirmation (D-3). Centres directory, footer centres and
  the visit-strip pattern are Query Loops with field bindings and render nothing until a
  centre is published.
- Sermons: `sermon` post type + series/speaker/topic taxonomies (SCF), sermon views in the theme,
  `cacdemo-content` plugin, YouTube metadata importer. See `docs/SERMON-SOURCES.md`.
- Plugins: Secure Custom Fields and our `cacdemo-content` (see register).

## Staging origin

`cacdemo.crishub.com` was created by Hostinger's AI site builder (WordPress 7.1,
`hostinger-ai-theme`, Hostinger AI/onboarding/Reach plugins, LiteSpeed Cache). None
of that is reproduced locally, and its generated content is not imported. Its
proprietary contact form and newsletter blocks are deferred requirements to be
evaluated with the decision order above.
