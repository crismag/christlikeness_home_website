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

## Testing policy

Test **our code and our integration boundaries**, not WordPress or established
plugins. Prefer the smallest test that gives meaningful confidence.

- `scripts/test.sh` — **routine.** Fast, read-only, never writes to the database:
  WordPress responds, wp-admin reachable, core checksums, DB connection, URL/env,
  theme active and symlinked to the repo, theme.json/PHP syntax, repo boundaries,
  approved plugins. Run after ordinary changes.
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
scripts/reset-local.sh         # DESTRUCTIVE: drop DB + delete runtime, then bootstrap (asks first)
wp --path=/mnt/ai/workspaces/cacdemo <command>
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
- PHP-FPM pool is shared with the other local site: don't change
  `/etc/php/8.4/fpm/*` or use `fastcgi_param PHP_VALUE` (leaks across workers).
  Upload limit is therefore PHP's 2M / post 8M.
