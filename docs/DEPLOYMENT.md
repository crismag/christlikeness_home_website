# Deployment

```text
local ──► Git ──► staging (cacdemo.crishub.com) ──► verification ──► production (christlikeness.ca)
```

Staging runs on Hostinger (WordPress 7.1, PHP 8.3), reached with `~/bin/hostinger` (SSH).
Path: `~/domains/cacdemo.crishub.com/public_html`. The same Hostinger account hosts other
sites (`home.crishub.com`, `church_portal`…); the scripts only touch the cacdemo path.

## Commands

```bash
scripts/deploy-staging.sh                                   # theme + approved plugins + SCF definitions
scripts/deploy-staging.sh --seed-content                    # also rewrite page content (scripts/seed-content.php)
scripts/deploy-staging.sh --replace-hostinger --seed-content  # ONE-TIME replacement of the Hostinger site (asks first; --yes to skip)
scripts/verify-staging.sh                                   # read-only checks
```

## What a deployment ships

- `wordpress/themes/cacdemo/` — uploaded over SSH (tar), swapped into
  `wp-content/themes/cacdemo`, activated.
- Our plugins (`wordpress/plugins/*`, currently `cacdemo-content`): uploaded the same way, activated.
- Plugins listed in `config/plugins.txt` — installed from WordPress.org by slug, never copied.
- SCF definitions (`config/scf/`) — imported with `scripts/scf-sync.php`.
- With `--seed-content` only: `scripts/seed-content.php` plus the five curated images it uses, and
  `scripts/import-sermons.php` with the committed Facebook and YouTube harvests, the review
  decisions and the locally cached stills; staging never contacts Facebook or YouTube.
  It creates missing pages, centres, the Main menu and images, and **rewrites the nine pages'
  content**. Do not use it once editors are maintaining staging content.

Scripts, definitions and images travel in a temporary `~/cacdemo-deploy` directory outside
the web root, removed at the end.

## What a deployment never replaces

- WordPress core (updated separately, through WordPress)
- `wp-config.php`, secrets, the staging/production database (never downloaded)
- Uploads, except images the seed script imports when missing
- Content created by editors (unless `--seed-content` is passed)

## One-time Hostinger replacement (`--replace-hostinger`)

Approved 2026-09-15: replace the Hostinger-generated site and remove Hostinger plugins.

1. Server-side backup in `~/backups/cacdemo-staging-<timestamp>/`: `db.sql` (mode 600),
   `.htaccess`, `mu-plugins/`, `plugins-themes.tgz`. Backups stay on the server.
2. Deploy SCF and the theme; activate `cacdemo`.
3. Remove Hostinger must-use plugins (`hostinger-auto-updates`, `hostinger-preview-domain`),
   uninstall `hostinger`, `hostinger-ai-assistant`, `hostinger-easy-onboarding`, `hostinger-reach`
   and `litespeed-cache`, delete `hostinger-ai-theme`.
4. Delete all pages, posts, navigation menus, template/template-part overrides, global
   styles and synced patterns (the Hostinger Header/Footer overrides would otherwise replace
   the theme's parts), and unset the privacy policy page.
5. Seed content.

A backup was already taken before the first attempt: `~/backups/cacdemo-staging-20260915-044612/`.

**Restore** (if needed): import `db.sql` with `mysql` using the credentials from `wp config get`,
restore `.htaccess` and `mu-plugins/`, extract `plugins-themes.tgz` into the site root.

## Hostinger notes

- `wp db export` exits 255 silently; `mysqldump --no-tablespaces` works (the DB user lacks PROCESS).
- Hostinger may re-add its must-use plugins; `verify-staging.sh` fails if anything other than
  the approved plugins is running.
- No server-side page cache remains once LiteSpeed Cache is uninstalled.

## Email (set-password links for new contributors) — deployment checklist

New contributors added in the Content Manager (People) or Users → Page contributors get WordPress's own set-password
email (`wp_new_user_notification`). The account and its sections are created whether or not the email is sent.

| Environment | Status (2026-09-16) | Behaviour |
|---|---|---|
| Local | **No mail transport** (`sendmail_path=/usr/sbin/sendmail`, not installed; no SMTP plugin or `phpmailer_init` hook) | `wp_mail()` fails; the screen says "Account created, but the email could not be sent" and gives the lost-password link to pass on |
| Staging (Hostinger) | **Not verified** | Hostinger's PHP `mail()` may deliver, but From address, SPF/DKIM for the staging domain and deliverability are untested |
| Production (christlikeness.ca) | **Not configured** | Needs a decided transport before contributors are invited |

Before inviting real contributors on an environment:

1. Choose the transport: host PHP mail, or SMTP through a provider (Google Workspace / transactional service). SMTP needs a
   free plugin (e.g. WP Mail SMTP or FluentSMTP), evaluated and approved under the plugin rules (`config/plugins.txt`,
   plugin register) — none is installed now.
2. Set the From name/address to a church domain mailbox, and SPF, DKIM (and DMARC) records for that domain.
3. Keep SMTP credentials out of Git: server-side `wp-config.php` constants or the host's secret store; locally
   `config/local.env` (git-ignored). No environment variable is used by the current code.
4. Verify: add a test person with a real mailbox you control, confirm the email arrives and the link sets a password, then
   remove the test person.

Expected failure behaviour stays as above: no data is lost; the administrator passes on the lost-password link.

## Open questions

- Production (christlikeness.ca) hosting and cut-over.
- Staging/production secrets management.
