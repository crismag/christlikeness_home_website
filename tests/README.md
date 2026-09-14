# Tests

## Policy

Test **our code and our integration boundaries**. Don't build tests whose purpose
is proving WordPress or established plugins work. For a third-party plugin, test
only the behavior Christlikeness depends on. Prefer the smallest test that gives
meaningful confidence.

## Levels

### `scripts/test.sh` — routine

Fast and read-only; never writes to the database. Run after ordinary changes.

- database connection, core checksum verification
- site URL and `WP_ENVIRONMENT_TYPE`
- `/` renders with the theme header and footer, no PHP errors; `/wp-admin/` reachable
- `cacdemo` theme active, symlinked to the repository source, readable by PHP-FPM
- `theme.json` valid; PHP under `wordpress/` has no syntax errors
- repository holds no WordPress core, wp-config, uploads, dumps, secrets or
  third-party plugin code
- only plugins listed in `config/plugins.txt` are running

### `scripts/test.sh --interop` — acceptance

Creates temporary pages, a navigation menu, a logo and a footer override, then
removes all of it (including revisions and editor auto-drafts) and verifies the
database is back to its previous state. Covers the editor, publishing, revisions,
scheduling, navigation and submenus, Site Logo and the footer template part.

Run only for:

- initial bootstrap verification
- WordPress or PHP upgrades
- major theme architecture changes
- deployment/release verification
- explicit request

Not after routine content, CSS, template or documentation changes.

## Future

Plugin-level tests (e.g. PHPUnit) only if `cacdemo-content` gains real custom code.
