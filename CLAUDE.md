# CLAUDE.md — Christlikeness website

Public identity: **Christlikeness**. Technical identifier everywhere on disk,
hostnames, theme/plugin slugs: **cacdemo**. Never display "CAC Demo" publicly.

## Locations

| What | Where |
|---|---|
| Git source (this repo) | `/mnt/ai/workspaces/christlikeness_home_website` (`/home/cris/workspaces` is a symlink to `/mnt/ai/workspaces`) |
| Local WordPress runtime (NOT in Git, disposable) | `/mnt/ai/workspaces/cacdemo` |
| Local URL | `http://cacdemo.local` (nginx vhost `/etc/nginx/sites-available/cacdemo`) |
| Local DB | `u471078694_2oQHk`, prefix `wp_`, user `cacdemo_wp` (password in git-ignored `config/local.env`) |
| Staging (READ-ONLY unless explicitly told otherwise) | `https://cacdemo.crishub.com/` via `~/bin/hostinger` |
| Production (future) | `https://christlikeness.ca/` |

**Do not touch the unrelated `christlikeness` local site** (`christlikeness.local`,
`/mnt/ai/workspaces/christlikeness`, its nginx vhost, databases, or PHP config).

## Non-negotiable rules

1. **WordPress core and third-party plugins are immutable dependencies.** Never
   modify, patch, replace or copy into this repo: `wp-admin/**`, `wp-includes/**`,
   root core PHP files, bundled libraries, or any third-party plugin/theme. Invalid
   core is reinstalled from verified official source, never repaired. Customize
   plugins only through their documented hooks/APIs or by styling their public
   output from our theme.
2. **Capability decision order: Core → free established plugin → custom.** For
   every capability, in order:
   1. **WordPress core** (blocks, Site Editor, Navigation, Query Loop, patterns,
      synced patterns, templates/parts, theme.json, Media Library, users, roles,
      revisions, scheduling, taxonomies, REST). If it solves it well, use it.
   2. **A mature, maintained FREE plugin**, preferably from WordPress.org. Evaluate
      with the checklist in `docs/ARCHITECTURE.md` (free-tier coverage, maintenance,
      compatibility, block-theme fit, standard data, security history, frontend
      weight, lock-in, what happens on removal, overlap, stylability).
   3. **Custom code in `wordpress/plugins/cacdemo-content/`** only when core and
      free plugins are inadequate or impose unacceptable complexity, lock-in,
      security, performance or UX costs, or the need is genuinely
      Christlikeness-specific.
   Never buy or depend on paid/pro-only features without explicit approval. Don't
   install plugins indiscriminately. Use at most **one** block extension library.
3. **Before any substantial new capability, report first:**
   ```
   Native WordPress option:
   Free plugin options:
   Recommended approach:
   Reason:
   Tradeoffs:
   ```
   then implement the simplest maintainable solution. Record adopted plugins in
   `config/plugins.txt` and the plugin register in `docs/ARCHITECTURE.md`.
4. **Plugins provide capability; the theme provides Christlikeness design.**
   Typography, color, spacing, visual language, templates, header/footer and
   responsive presentation belong to `wordpress/themes/cacdemo/`.
5. **Admin interoperability.** Admins must be able to change the logo, navigation
   (including submenus), footer part, featured images, and use ordinary blocks,
   and see the theme respond. Visually correct but bypassing native controls = wrong.
6. **Source/runtime boundary.** Our code lives only in:
   - `wordpress/themes/cacdemo/` — presentation
   - `wordpress/plugins/cacdemo-content/` — Christlikeness-specific domain code (last resort, see rule 2)
   - `scripts/`, `config/` (non-secret), `tests/`, `docs/`
   The runtime consumes theme/plugin via symlinks. Never keep a second copy.
   Third-party plugins are installed into the runtime, never committed.
7. **Domain content must not depend on the theme.** Content models go in plugins,
   never the theme.
8. **Clean baseline.** Bootstrap creates no pages, posts, menus or placeholder
   content. The information architecture comes from the christlikeness.ca content
   harvest and design review. Never import Hostinger-generated content.
9. **No secrets in Git** (public repo): no wp-config.php, DB dumps, passwords,
   salts, SSH keys, API keys. Local secrets → `config/local.env` (git-ignored).
10. No Hostinger AI theme/plugins, LiteSpeed, or WooCommerce locally. No Docker.
11. **Multi-centre: one site, one canonical source per centre.** No separate installs or
    Multisite without approval. Centre facts (addresses, service times, maps, contact)
    are maintained once as structured data and derived wherever shown (header, footer,
    New Here, About, Centres, events, ministries, sitemap…); never hand-copied into
    templates or pages. Centre listings are generated from active centre records.
    Content may be church-wide (default), one centre, or several — never force a centre.
    See `docs/ARCHITECTURE.md` → Multi-centre architecture.
12. **Google integration is future work, not current phase.** Calendar, Drive and
    Workspace automation are planned (see `docs/ARCHITECTURE.md` → Future Google
    integration). Do not build OAuth/API infrastructure, placeholders or stubs, do not
    hard-code Google account details, and do not design current content around an
    unimplemented API. Before implementing, record the source of truth and sync
    direction for each integration. Meanwhile, avoid choices that would make it harder
    (e.g. keep events/centres/ministries structured and distinctly identified).
    The same applies to Facebook/Meta: no API work now, never canonical for essential
    information, no dependency on Facebook CDN image URLs, Group content not assumed public.
13. **Legacy archive is immutable; legacy is not the spec.** Never rewrite
    `content-source/legacy-site/` (SOURCE CONTENT = source wording, HARVEST NOTES =
    analysis, curated WordPress content = separate). "Existed on the old site" ≠ publish;
    "old/hidden" ≠ useless. Classify content first (core / resource / member / historical /
    media — see `docs/INFORMATION-ARCHITECTURE.md`). Topical teachings are Resources, never
    top-level Pages. Unverified, conflicting, expired or sensitive content is not published;
    empty is better than fabricated or stale. Sensitive material's audience is a church decision.
14. **Media sources of truth.** YouTube = canonical public video (no routine video files in
    Git, uploads, theme or plugin). Media Library = curated site images only (don't bulk
    upload the harvest). High-volume gallery photos = future review-before-publish workflow.
15. **Generated, not hand-maintained.** Directories, archives, topic lists, centre
    listings and the human sitemap are derived from structured content (Query Loop, term
    blocks, archives). The header menu stays a curated native Navigation menu — don't add
    every type/taxonomy to it. Access control is enforced server-side, never by hiding links.
16. **Responsive and portable by default.** Structures must work on desktop, tablet and
    mobile. Content stays in WordPress-standard data (exportable), never inside theme
    templates. Any plugin we write installs/deactivates/upgrades safely and never deletes
    content on deactivation.

## Design system

Approved direction: "Living Worship" on an editorial foundation (`docs/DESIGN-DIRECTION.md`,
approved rules in `docs/DESIGN-SYSTEM.md`). One expressive moment per page; tone bands, not
boxes; lists before cards; two typefaces (Schibsted Grotesk, Source Serif 4); no motion
except opt-in site-theme effects (reduced-motion aware); arch image only as a dark-band accent;
missing images fall back deliberately (content → parent → theme slot; see DESIGN-SYSTEM → Imagery);
editors use locked patterns and theme presets, never custom colours or sizes. Visual themes and
palettes: `docs/VISUAL-THEMES-DESIGN.md`. `design/prototype/` is
disposable reference, not production code.

## Content Manager

Everyday publishing happens at `/manage/` (plugin `includes/manage/`, look in theme `assets/css/manage.css`), not wp-admin.
It uses SCF front-end forms and the section permissions in `includes/publishing.php`; every screen and save checks capabilities
on the record resolved from the address. Writing there is simple formatted text; designed pages open the full editor. See
`docs/CONTENT-MANAGER.md`.

## Structured content (Secure Custom Fields)

Post types and field groups are defined in the SCF admin and **versioned as JSON** in
`config/scf/` (`post-types/`, `taxonomies/`, `field-groups/`). Workflow: edit in wp-admin →
`wp --path=/mnt/ai/workspaces/cacdemo eval-file scripts/scf-sync.php export` → review the
diff → commit. `bootstrap-local.sh` installs the plugins in `config/plugins.txt` and runs
`scf-sync.php import` (idempotent). Records (e.g. centres) are content, not code — never
in Git. Fields shown on the front end need "Allow access in bindings"; themes display them
only through block bindings (`acf/field`) in patterns. `test.sh` fails if `config/scf/`
and WordPress disagree.

## Testing policy

Test **our code and our integration boundaries**, not WordPress or established
plugins. Prefer the smallest test that gives meaningful confidence.

- `scripts/test.sh` — **routine.** Fast, read-only, never writes to the database:
  WordPress responds, wp-admin reachable, core checksums, DB connection, URL/env,
  theme active and symlinked to the repo, theme.json/PHP syntax, repo boundaries,
  approved plugins, centre model / SCF definitions in sync. Run after ordinary changes.
- `scripts/test.sh --interop` — **acceptance only.** Creates and removes temporary
  content to exercise editor, navigation/submenus, Site Logo, footer part,
  publishing, revisions, scheduling. Run only for bootstrap verification,
  WordPress/PHP upgrades, major theme architecture changes, releases, or when
  asked. Do **not** run it after routine content/CSS/template/doc changes.
- For third-party plugins, test only the behavior Christlikeness depends on.

## Commands

```bash
scripts/bootstrap-local.sh     # build/converge runtime (idempotent, non-destructive)
scripts/test.sh                # routine checks
scripts/test.sh --interop      # acceptance checks (see policy above)
scripts/test.sh --appearance   # visual themes acceptance (site theme saving, previews, theme and Content Manager images)
scripts/test.sh --publishing   # publishing permissions + Content Manager acceptance (temporary users/content; after those changes)
scripts/reset-local.sh         # DESTRUCTIVE: drop DB + delete runtime, then bootstrap (asks first)
wp --path=/mnt/ai/workspaces/cacdemo <command>
wp --path=/mnt/ai/workspaces/cacdemo eval-file scripts/seed-content.php  # demo/design content (rewrites pages)
wp --path=/mnt/ai/workspaces/cacdemo eval-file scripts/seed-channels.php [dry-run]    # Facebook pages/group records, Follow Us under Connect (after seed-content)
wp --path=/mnt/ai/workspaces/cacdemo eval-file scripts/seed-ministries.php [dry-run]  # seven ministries, ways to serve, Ministries submenu (draft copy; non-destructive)
/tmp/ytvenv/bin/python scripts/harvest-facebook.py  # Facebook Group sermon harvest (needs yt-dlp venv; see docs/SERMON-SOURCES.md)
/tmp/ytvenv/bin/python scripts/harvest-youtube.py   # YouTube sermon harvest
wp --path=/mnt/ai/workspaces/cacdemo eval-file scripts/import-sermons.php [dry-run]  # reconcile harvests into sermons
scripts/sync-sermons.sh [--dry-run]           # discover new Facebook/YouTube sermons and import (manual; no cron)
wp --path=/mnt/ai/workspaces/cacdemo eval-file scripts/seed-appearance.php [dry-run] [regenerate]  # placeholder art for empty image slots
scripts/deploy-staging.sh [--seed-content|--seed-appearance]   # Hostinger staging — see docs/DEPLOYMENT.md
scripts/verify-staging.sh                    # read-only staging checks
```

## Gotchas

- **Symlink via the physical path.** PHP-FPM runs as `www-data`, which cannot
  traverse `/home/cris` (mode 750). A symlink through `/home/cris/workspaces/...`
  renders pages as empty 200 responses. Link to `/mnt/ai/workspaces/...`.
- **Don't use `wp core download` for WP 7.1.** Its PharData extraction truncates
  tar paths >100 chars (`wp-includes/php-ai-client`). `bootstrap-local.sh` uses
  the SHA-1-verified official tarball + GNU tar and gates on `wp core verify-checksums`.
- **Header navigation has no `ref`.** Like core themes, `parts/header.html` uses
  `<!-- wp:navigation /-->`, so WordPress shows the most recently published
  `wp_navigation` menu until an admin picks one in the Site Editor. WordPress
  auto-created a page-list "Navigation" menu on install; that is core behavior.
- **theme.json preset slugs must not contain digits after letters** (`h2` becomes
  `--wp--preset--font-size--h-2`, so `var:preset|font-size|h2` silently resolves to
  nothing). Use word slugs (`heading`, `subheading`).
- **Block style variation `css` doesn't support ancestor selectors** (`.parent &` breaks the
  generated rule). For `core/button`, `&` already targets the link element. Put
  context-dependent rules in `theme.json` `styles.css`.
- **Core Navigation's overlay breakpoint is fixed at 600px**; the header overrides it with
  theme CSS so the menu overlay applies below 1200px.
- **SCF field values set from code need the field reference.** `wp post meta update` stores the
  value but not `_<field>` → `field_…`, and block bindings then render empty (e.g. `href=""`).
  Use `update_field()` (the admin does this automatically).
- **Theme pattern files are cached.** After adding or renaming a file in `patterns/`, run
  `wp eval 'wp_get_theme()->delete_pattern_cache();'` or the pattern is "not registered".
- **A `wp:pattern` block renders without its parent's block context** (`render_block_core_pattern`
  calls `do_blocks`). Never nest a pattern inside a Post Template; share per-post markup through
  PHP (`inc/sermon-card.php`) inside a pattern that contains the whole Query Loop.
- **Version theme/plugin assets by `filemtime()`, not the theme version.** A changed JS/CSS file under the same `?ver=`
  stays cached in browsers (the old sermon view switch kept forcing Grid).
- **Reserved query vars:** never use `year`, `s`, `name`, `page`, `m` etc. as custom URL parameters on a Page (they
  turn it into a 404, a search or an archive). The sermon filters use `q` and `yr`.
- `wp eval-file` rejects unknown `--flags`; pass script options as positional words.
- PHP-FPM pool is shared with the other local site: don't change
  `/etc/php/8.4/fpm/*` or use `fastcgi_param PHP_VALUE` (leaks across workers).
  Upload limit is therefore PHP's 2M / post 8M.
