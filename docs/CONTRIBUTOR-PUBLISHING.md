# Contextual publishing and contributors — discovery and proposal

**Status (2026-09-16):** Phases A–E implemented locally (foundation, Sermons, contributor administration); Phase F for Ministries
(updates) and Phase G for ministry pages and ways to serve. Other sections (not yet modelled) and a Content Admin overview (H) are not built. See §7.

---

## 1. Phase A — what the repository and runtime actually contain

### 1.1 The brief's premise does not match this project

The brief describes a separate "Christlikeness application" that shares WordPress's database and talks to it across an
integration boundary. **No such application exists.** Christlikeness *is* a WordPress site:

| Layer | What it is | Owner |
|---|---|---|
| WordPress 7.1 core (runtime `/mnt/ai/workspaces/cacdemo`, not in Git) | Users, roles, posts, media, revisions, REST, block editor, Navigation | WordPress |
| Secure Custom Fields 6.9.5 (only approved third-party plugin) | Post type and field registration, field storage in post meta, `acf_form()` front-end forms | Third party (never modified) |
| `wordpress/themes/cacdemo/` | Templates, patterns, CSS, small presentation JS | Christlikeness |
| `wordpress/plugins/cacdemo-content/` | Sermon media/filters/table blocks, bindings sources, query filters, upload naming, sermon comments switch, ministry roles query | Christlikeness |
| `config/scf/` | Post type and field definitions as JSON, imported into SCF | Christlikeness (definitions), stored by SCF |
| `scripts/` | Harvest, import, seed, sync, deploy, tests (WP-CLI `eval-file`) | Christlikeness |

Consequences:

- The "WordPress integration boundary" is **the WordPress PHP API itself**, called in-process by our plugin
  (`wp_insert_post`, `update_field`, `media_handle_upload`, `current_user_can`). Nothing in the project uses raw SQL against
  WordPress tables. The importer and seed scripts already work only through these APIs.
- There is **no second authentication system, session layer, user table or invitation system**. Accounts are WordPress users.
- Update safety already holds structurally (verified by `scripts/test.sh`: core checksums, no core or third-party code in Git,
  theme and plugin symlinked from the repository).

### 1.2 Discovery checklist (brief §2)

| # | Question | Finding |
|---|---|---|
| 1 | Framework | WordPress block theme + one custom plugin. No separate app, no JS framework, no build step |
| 2 | Routing | WordPress rewrite rules: pages (`/sermons/`, `/ministries/`…), post type permalinks (`/sermons/<slug>/`, `/ministries/<slug>/`), taxonomy archives. Templates chosen by the template hierarchy |
| 3 | WordPress integration | In-process PHP hooks: `query_loop_block_query_vars`, block bindings sources, `render_block_*`, `pre_get_posts`, upload prefilter, comment filters |
| 4 | Database access | Only through WordPress APIs. No custom tables |
| 5 | REST | Core REST is on for all our types (`show_in_rest`), used only by the block editor. No custom routes |
| 6 | Direct SQL | None |
| 7 | Auth/session | Core login cookies. Registration off (`users_can_register = 0`) |
| 8 | Christlikeness users vs WP users | Same thing. One local account (administrator) |
| 9 | Project plugins | `cacdemo-content` 0.2.0 |
| 10 | Custom tables | None |
| 11 | Namespacing | Functions `cacdemo_*`, meta `_cacdemo_*`, options `cacdemo_*`, SCF fields `sermon_*`, `ministry_*`, `role_*`, `centre_*`, table prefix `wp_` |
| 12 | Media | Core Media Library. Sermon uploads renamed `YYYYMMDD_Title_…` into the sermon date's `YYYY/MM`; importer covers carry `_cacdemo_still_source` |
| 13 | Page/post retrieval | Query Loop blocks + bindings; server-rendered blocks for filters/table |
| 14 | Content models | `page` (9), `sermon` (287, taxonomies series/speaker/topic), `ministry` (7), `serve_role` (52), `centre` (2). `post` exists but has 0 records |
| 15 | Roles/permissions | Core five roles, unmodified. **All our post types use `capability_type: post`**, so any Author can publish sermons, ministries and centres, and there is no per-section scope |
| 16 | Admin functionality | wp-admin only; one custom setting (Settings → Discussion → Sermon comments) |
| 17 | Editor components | Core block editor; SCF field panels under the editor |
| 18 | Forms/validation | No form plugin. Only form on the site is the sermon filter bar (GET). SCF's `acf_form()` is available (free, in SCF) |
| 19 | Reusable front-end pieces | Buttons, text links, Mist/Night bands, ruled rows, filter-bar styles, "Filters" toggle, collapsible Details bar (sermon comments) |
| 20 | Sections that exist | **Sermons, Ministries (+ ways to serve), Centres**, and static pages. **Events, Reflections, Testimonies, Resources and News do not exist** (Events: no model chosen; Resources: deferred until the first approved item, D-5) |

Also relevant:

- Revisions are on (`WP_POST_REVISIONS` default) and all our types support them. Trash is core.
- Core statuses already available: draft, pending (review), future (scheduled), publish, private, trash.
- Core "Add New User" emails a set-password link. That is the only invitation mechanism, and local mail is not configured.
- `docs/PUBLISHING.md` recorded "native WordPress only; simplified publisher interface deferred; must extend native statuses,
  roles/capabilities and revisions". `docs/CONTENT-MODEL.md` lists "Simplified publishing UX → core roles, statuses, revisions,
  block locking first" and "Member access → Members plugin, needs authorization".
- `docs/SERMON-SOURCES.md` has a deferred "Published by" decision; this work touches it.

### 1.3 Ownership

| WordPress-owned | Christlikeness-owned | Shared (WordPress storage, Christlikeness meaning) | Unclear |
|---|---|---|---|
| Users, sessions, roles table (`wp_user_roles` option), posts, revisions, media files and attachment records, REST, block editor, wp-admin | Theme, plugin, SCF definitions, templates/patterns, section list, contextual UI, scoped permission rules | Sermon/ministry/serve role/centre records (post + SCF meta), contributor assignments (would be user meta) | Who in the church administers contributors; whether contributors should ever use wp-admin; mail delivery on staging/production |

---

## 2. Native, plugin and custom options (project rule 3)

**Native WordPress option**
- Contextual entry points: the admin bar already shows "Edit Sermon"/"Edit Page" to logged-in editors. Nothing section-aware beyond that.
- Scoped authorization: the core capability system — custom `capability_type` per post type plus `map_meta_cap` / `user_has_cap`
  filters. It is enforced in wp-admin, REST and front end alike.
- Review: `pending` status, Contributor role. History: revisions (author per revision). Safety: trash/restore.
- Invitations: "Add New User" + set-password email.
- Forms: SCF `acf_form()` (bundled with the approved plugin) builds front-end create/edit forms for SCF fields and core
  title/content, with nonces, validation and valid `wp_insert_post` records.

**Free plugin options** (candidates only — none evaluated against the checklist yet)
- Roles/capabilities: **Members** (already the documented candidate for member access), PublishPress Capabilities, User Role Editor.
  They edit role capabilities but have no per-ministry *scope*.
- Scoped editing: PublishPress Permissions (per-term/per-post editing exceptions; which parts are free needs verifying).
- Front-end posting: WP User Frontend, Frontend Admin. Both put significant features in paid tiers and bring their own form UI
  and styling.

**Recommended approach (chosen)** — core capabilities + contextual buttons that open the **core block editor, trimmed to the
section**, + a thin layer in `cacdemo-content`. No new plugin.

**Reason:** every requirement maps onto something WordPress or SCF already does: the editor, autosave, revisions, media, SCF
fields, statuses and trash. The only Christlikeness-specific part is "which user may act on which section", and it is small. A
scoped-permission plugin would still not know what a ministry "section" is, and a front-end posting plugin would bring a second
form system. Publishing happens mostly on desktop or tablet, where the block editor works well.

**Tradeoffs:** contributors see the WordPress editor rather than a website-styled form. We reduce that with locked templates, a
reduced admin menu and a "View on site" return link. We own the scope rules and their tests. Front-end forms (`acf_form()`) stay
possible later for a specific simple case.

---

## 3. Phase B — proposed architecture

### 3.1 Shape

```text
Section page (theme template)
 └─ "New Sermon" / "Edit sermon" / "Edit Page" / "Add Update"
      cacdemo/publish-actions block: renders nothing unless current_user_can(…) for this section
      └─ link to the core editor for the resolved target
           new:  post-new.php?post_type=sermon            (section implied by the post type)
           new:  post-new.php?cacdemo_update=<ministry>   (ministry update; ministry re-checked server-side)
           edit: post.php?post=<id>&action=edit           (core; capability checked on the post)
      └─ Publishing layer (cacdemo-content/includes/publishing/)
           capabilities + map_meta_cap scope rules · editor trimming per content type · links/fixed values set on save
           → WordPress saves as usual (revisions, autosave, kses, media, SCF fields, hooks, permalinks)
```

- **Trimmed editor per content type:** a block template with `template_lock` for sermons and ministry updates (only the summary
  and resources areas are editable), SCF field panel with the real sermon fields, no unrelated panels. Page contributors get a
  minimal admin menu (their sections, Media, Profile) and return to the public page after publishing.
- **The target is resolved server-side:** a ministry update's ministry comes from the `cacdemo_update` value only after a
  capability check on that ministry, and it is re-checked on every save (`wp_insert_post_data` / `rest_pre_insert_post`). A
  client that changes it to a ministry outside its scope is refused. Post type, author and status permissions are all core
  capability checks.
- **Domain names in our code** (`cacdemo_publishing_can( 'create', 'sermon' )`, `cacdemo_publishing_new_url( 'ministry_update',
  $ministry )`); WordPress specifics stay inside the publishing layer.

### 3.2 Permissions

User-facing model: **Contributor**, **Publisher**, **Content Admin**, each assignment = level + scope.

| Level | Can |
|---|---|
| Contributor | Create and publish in scope; edit, unpublish and trash **their own** items there |
| Publisher | Everything a Contributor can, for **anyone's** items in scope, and edit the section page (e.g. the Psalmists ministry record) |
| Content Admin | Everything, all sections, and manage contributors (core Editor role + `cacdemo_manage_contributors`) |

Scopes are generated, not hard-coded: **Sermons** (post type) and **each published ministry record** (dynamic list). New
sections (Events, Resources…) register a scope when they exist.

Implementation:

1. Give `sermon` and `ministry` their own capability types (`sermon/sermons`, `ministry/ministries`) in `config/scf/`. Ministry
   updates are core `post` records linked to a ministry.
2. On plugin activation, add the new primitive capabilities to **administrator and editor roles** (stored by WordPress in the
   roles option). This keeps wp-admin working for them even if the plugin is later deactivated. Nothing else is written.
3. A base role **Page contributor** (`read`, `upload_files`) for people with assignments but no other role.
4. `map_meta_cap` resolves `edit_post`, `publish_post`, `delete_post` against the actual post's section and the user's
   assignments. The same rules apply in wp-admin, the block editor's REST calls and uploads (attachments inherit the parent's check).
5. Assignments are stored as **user meta** (`cacdemo_publishing_scopes`: section → level), so they are WordPress-standard,
   exportable and visible on the user record. No custom table.

### 3.3 Contributor administration

Smallest native form: **Users → Page contributors** (one wp-admin screen, Content Admin only):
list (name, sections, level, account status) and add/change/remove. "Add" searches existing users by name or email first and
attaches the assignment to that account (no duplicates). If no account exists, it creates one with the core flow (Page
contributor role + core set-password email) and saves the assignment at once, so it applies on first sign-in. No separate
"pending invitation" store is needed. Removing a scope takes effect on the next request (capabilities are computed per request).

Alternative (even smaller): the same assignment fields as an SCF field group on the user profile plus a Users list column. It is
cheaper, but "search or invite" and the per-section overview are worse.

### 3.4 First vertical slice (Phase D): Sermons

- `/sermons/` shows **+ New Sermon** to users with sermon scope; a sermon page shows **Edit sermon**.
- The editor shows the real sermon model: title, service date (publish date), speaker and series (taxonomy panels), scripture,
  language, centre, video/audio sources (repeater: platform + link; ID and length filled by our code on save), cover image
  (featured image), summary and resources (locked template). **Save draft** and **Publish** are core.
- Sources are validated (YouTube/Facebook/audio URL patterns; unknown hosts rejected). Cover upload goes through the Media
  Library with the existing naming prefilter and core type/size checks.
- Created sermons are identical to imported ones and render through the existing templates. The importer keeps working
  (matching skips known source IDs).
- Records `_cacdemo_published_by` on the first publish (core already records creator and per-revision authors). This settles the
  deferred "Published by" for manual entries.

### 3.5 Later phases (after the slice works)

- **Ministries:** **Edit Page** (Publishers of that ministry) opens the ministry record in the editor: purpose, introduction,
  contact, invitation text, photo; ways to serve are edited as their own records, filtered to that ministry. **Add Update** creates a `post` linked to that ministry; the ministry page
  gains an "Updates" list that hides when empty.
- **Events, Reflections, Testimonies, Resources:** not built until each has an approved model (Events needs its plugin-vs-core
  decision; Resources D-5). The scope and form pattern accepts them without changes to the permission layer.
- **Content Admin overview:** use core list screens first (the "Pending" filter already exists). Build a front-end overview only if
  that proves insufficient.

### 3.6 What touches WordPress

| Touches (through APIs only) | Does not touch |
|---|---|
| Post type capability types (SCF JSON), capabilities added to administrator/editor roles, one new role, user meta, posts/meta/terms/media created through core and SCF, rewrite endpoints | Core files, `wp-admin`, `wp-includes`, schema, third-party plugin code, raw SQL, authentication, sessions |

On deactivation: records, users and media remain valid; admins/editors keep access (capabilities were added to their roles);
contextual buttons and forms disappear; Page contributors lose section access (safe default). Nothing is deleted.

### 3.7 Tests

`scripts/test.sh` stays read-only. Add `scripts/test.sh --publishing` (acceptance level, like `--interop`: creates temporary users
and records, then removes them and checks the database is restored), covering brief §27: visitor and unscoped user refused,
Contributor publishes and edits own items in scope but not others', Publisher edits anyone's in scope, cross-section refused (sermon Publisher → Psalmists), route/target
tampering refused, invalid IDs, kses strips script, unsafe URLs rejected, upload type refused, removing a scope removes access,
existing-user add does not duplicate, created sermon renders and passes `get_post` / REST checks. The update-safety gate (§28)
reuses the existing core checksum and repository-boundary checks and adds a deactivate/reactivate check.

---

## 4. Security notes

Core editor/REST nonces; capability check on the resolved target for every create, edit, publish, trash and upload;
Page contributors and Contributors lack `unfiltered_html`, so kses applies; URL allow-list for sources; core upload MIME/size checks;  trash instead of delete; no user, author or ministry IDs accepted from the client.

## 5. Out of scope

A dashboard replacing wp-admin; a page builder; custom tables; a second login or invitation system; sample content or users.

## 6. Decisions

Answered 2026-09-16:

- **Devices:** publishing happens mostly on desktop or tablet.
- **Editing surface:** the core block editor, trimmed to the section (§3.1). **Superseded the same day:** everyday publishing now
  happens in the separate Content Manager at `/manage/` ([CONTENT-MANAGER.md](CONTENT-MANAGER.md)); the permission model below is unchanged and
  wp-admin remains for administrators.
- **Approval:** Contributors publish directly in their scope; `pending` is not used.
- **Levels:** Contributor = their own items in scope; Publisher = anyone's items in scope plus the section page (§3.2).
- **Contributor administration:** Users → Page contributors screen (§3.3).

Still open:

- Who are Content Admins in the church, and will staging/production send mail for set-password links?

Original questions (for the record):

1. **Editing surface:** small front-end forms styled by the theme (recommended), or contextual buttons that open the core editor
   pre-set to the section (much less code, but wp-admin UI and weaker on mobile).
2. **Contributor approval:** Contributors submit for review and a Publisher approves (recommended), or Contributors publish directly
   in their scope.
3. **Contributor administration:** a small Users → Page contributors screen (recommended), or assignment fields on the user profile.
4. **Who are Content Admins** in the church (core Editor role holders), and will staging/production send mail for set-password links?

---

## 7. Implementation (2026-09-16, local)

| Piece | Where |
|---|---|
| Permission types | `config/scf/post-types/{sermon,ministry,serve_role}.json`: `rename_capabilities` → `sermon/sermons`, `ministry/ministries`, `serve_role/serve_roles`. Taxonomies: series and speakers `assign_terms`/`edit_terms` = `edit_sermons`; topics `assign_terms` = `edit_sermons`, creating topics stays `manage_categories` |
| Scoped permissions | `wordpress/plugins/cacdemo-content/includes/publishing.php`: role setup (versioned option `cacdemo_publishing_version`), `create_ministries`, assignments (user meta `cacdemo_publishing_scopes`), `user_has_cap` grants, `map_meta_cap` per ministry / way to serve / term, SCF guards for `role_ministry`, `_cacdemo_published_by`, sermon source link checks and derived source IDs, trimmed admin menu and login redirect for Page contributors, `cacdemo_publishing_actions()` |
| Contributors screen | `includes/publishing-admin.php`: Users → Page contributors (list, add/change, remove sections). Service functions `cacdemo_contributors_save()` / `_remove()` are used by the screen and the tests |
| Contextual actions | Block `cacdemo/publish-actions` (server-rendered, nothing for visitors) in `page-sermons.html`, `single-sermon.html`, the three sermon taxonomy templates and `single-ministry.html`; styles in `theme.json` |
| Ministry updates | SCF field group `config/scf/field-groups/ministry-update.json` (`update_ministry` on core posts, sidebar); in `publishing.php`: post permissions per ministry, REST and save-path publish guards, prefill from **Add update**, ministry choice limits, no synced patterns for Page contributors, "Updates" labels for them; in `ministries.php`: `cacdemoMinistryUpdates` Query Loop flag and `ministry_name`/`ministry_url` bindings. Display: "Updates" section on `single-ministry.html` (latest 3, hidden when none) and a link to the ministry on `single.html` |
| Tests | `tests/publishing.php`, run by `scripts/test.sh --publishing` (62 checks, temporary users and content removed) |

What each person sees:

| Where | Sermon Contributor / Publisher | Ministry Publisher | Visitor or unassigned user |
|---|---|---|---|
| `/sermons/`, series/speaker/topic pages | **+ New sermon** | nothing | nothing |
| A sermon | **Edit sermon** (Contributor: own only), **New sermon** | nothing | nothing |
| Their ministry page | nothing | **+ Add update**, **Edit page**, **Add a way to serve** (ministry preselected), **Ways to serve** list. A ministry *Contributor* sees **+ Add update** only | nothing |
| An update | nothing | **Edit update** (Contributor: own only) | nothing |
| wp-admin (Page contributors) | Media, Sermons, Profile; start page after sign-in is the website | Media, Ministries (their page only editable), Ways to serve (their ministry's only), Profile | — |

Verified: `scripts/test.sh --publishing` (routine + 47 publishing checks) passes. Over HTTP as a temporary sermon Contributor: sign-in
lands on the website, **New sermon** appears, the editor loads with the sermon fields, the REST requests it uses for the post type,
terms, media, patterns and templates succeed, and generic posts, pages and the contributors screen are refused. The browser-level
visual check of the editor as a contributor was not possible in this session (isolated browser contexts closed immediately); do it
once with a real contributor account.

Behaviour to know:

- Existing Author/Contributor accounts no longer get sermon or ministry access from their core role (there are none today). Access comes
  from Editor/Administrator or an assignment.
- A ministry update is a normal post. Posts without a ministry are church-wide news, edited only by Content Admins and administrators.
- An update can only be published once it belongs to one of the person's ministries; **Add update** sets that at once. Starting from
  wp-admin → Updates → Add, they choose the ministry in the sidebar before publishing.
- On staging/production the change applies on deploy: SCF import sets the permission types, and the plugin adds the capabilities to the
  administrator and editor roles on the first request. Mail must work there for set-password emails; otherwise the screen shows the
  lost-password link to pass on.
- Importer, seed and sync scripts run through WP-CLI without a user and are unaffected.

