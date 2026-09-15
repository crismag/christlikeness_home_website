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

### `scripts/test.sh --publishing` — contextual publishing acceptance

Runs `tests/publishing.php` after the routine checks. Creates temporary users (visitor, unassigned, sermon Contributor and
Publisher, ministry Publisher, Content Admin), temporary ministries, sermons, terms and an upload attempt; checks section
permissions through REST and capability checks, tampering with a way to serve's or an update's ministry, ministry updates (publish only in scope, own vs anyone's, no synced patterns), term rules, content sanitizing, link
validation, upload types, removal of access, the contributors service (existing account reuse, new accounts, who may manage whom)
and the website's contextual actions for signed-in and anonymous requests. Everything created, including anything the temporary
users authored, is deleted. Run after changing publishing code, and before releases.

The same flag also runs `tests/manage.php`: it signs in temporary accounts over HTTP, reads the Content Manager's rendered
forms (including SCF's signed data) and submits them like a browser, checking access per section, publishing and unpublishing,
tampering, trash permissions, context links (ministry updates, sub-pages), People, and the website's links into `/manage/`.

