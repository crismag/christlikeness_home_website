# Christlikeness website

Source for the Christlikeness church website: a custom WordPress block theme, plus
tooling and documentation. WordPress core and third-party plugins are external
dependencies and are **not** stored here.

| Environment | URL | Notes |
|---|---|---|
| Local | http://cacdemo.local | Runtime at `/mnt/ai/workspaces/cacdemo` |
| Staging | https://cacdemo.crishub.com/ | Hostinger |
| Production | https://christlikeness.ca/ | Future |

## Repository layout

```text
wordpress/themes/cacdemo/            Theme "Christlikeness" — the design system
wordpress/plugins/cacdemo-content/   Custom Christlikeness code, only if core and free plugins fall short (not created)
config/                              Non-secret config: nginx template, env sample, approved plugins
scripts/                             Bootstrap, reset and test tooling
tests/                               Test policy
docs/                                Architecture and process docs
```

## Local setup

Requirements: Linux with PHP 8.3+ (FPM), MariaDB/MySQL, nginx, WP-CLI, passwordless
sudo. No Docker.

```bash
cp config/local.sample.env config/local.env && chmod 600 config/local.env
# fill in CACDEMO_DB_PASSWORD, CACDEMO_ADMIN_PASSWORD, CACDEMO_ADMIN_EMAIL
scripts/bootstrap-local.sh
scripts/test.sh --interop      # once, to accept the new environment
```

`bootstrap-local.sh` creates the database and a local-only DB user, installs
checksum-verified WordPress 7.1 outside the repo, writes `wp-config.php`, adds the
`cacdemo.local` nginx vhost and hosts entry, symlinks the theme from this repo, and
leaves a clean baseline with no pages, posts or menus. It is safe to re-run.

`reset-local.sh` deletes the local runtime and database and rebuilds them. It asks
for confirmation.

Day to day, run `scripts/test.sh` (fast, read-only). See [tests/README.md](tests/README.md).

## Principles

- WordPress core and third-party plugins are never modified.
- For every capability: **WordPress core → established free plugin → custom code**, in that order.
- Plugins provide capability; the theme provides the Christlikeness design.
- Deployments ship our theme/plugin code, never core, databases or uploads.

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) and
[docs/INFORMATION-ARCHITECTURE.md](docs/INFORMATION-ARCHITECTURE.md).
