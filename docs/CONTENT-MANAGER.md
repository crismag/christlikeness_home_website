# Content Manager (`/manage/`)

**Status (2026-09-16):** v1 implemented locally. Replaces the earlier "trimmed WordPress editor" surface for everyday
publishing; permissions are unchanged ([CONTRIBUTOR-PUBLISHING.md](CONTRIBUTOR-PUBLISHING.md)).

## Decisions (2026-09-16)

| Question | Decision |
|---|---|
| Where | Same WordPress site at `/manage/`: its own layout and navigation, no wp-admin. Same login, records, media, revisions |
| Writing | Simple formatted text (paragraph, heading, subheading, bold, italic, lists, quote, link). Designed pages (groups, patterns…) are not edited here: “Open full editor” instead, so layouts are never flattened |
| v1 scope | Sermons · Ministries (page details, ways to serve, updates) · News & updates · Pages and sub-pages · Social channels · People |
| Not in v1 | Overview dashboard (drafts / recent activity), Events and other sections without a content model, menu editing |

Native / plugin / custom (project rule 3): hiding wp-admin menus still leaves wp-admin; front-end posting plugins
(Frontend Admin, WP User Frontend) keep dashboards and scoped control in paid tiers and bring their own UI. Chosen: a small
screen layer in `cacdemo-content` using SCF's own front-end forms, over the existing capability rules. A separate app was
rejected: own hosting, deployment and cross-site login for no user benefit.

## Who sees what

| Section | Who | What they can do |
|---|---|---|
| Sermons | Sermon Contributors (their own sermons) and Publishers (all); Content Admins | List, search, filter by status; new / edit (title, date preached, speaker, series, summary, scripture, language, centre, video/audio links, topics, cover image, featured for Publishers); publish, unpublish, trash |
| Ministries | Anyone assigned to a ministry; Content Admins (all) | Updates for that ministry (Contributors: add and edit their own); Publishers also edit the page details (introduction, purpose, contact, invitation, photo) and ways to serve |
| News & updates | Content Admins | Church-wide news and all ministry updates |
| Pages | Content Admins | Page tree; new page or sub-page (“Place under”, order); edit title, place and simple text |
| Social channels | Content Admins | The Facebook pages and group (Follow Us, Connect, home band) |
| People | Content Admins (administrators also make Content Admins) | Add by email or existing account, set Contributor / Publisher per section, remove sections |

On the website, the small links near page titles (**+ New sermon**, **Edit sermon**, **+ Add update**, **Edit page**,
**Add sub-page**, **Add a way to serve**) open the Content Manager. After signing in, Page contributors land on `/manage/`;
the admin bar shows **Content Manager** for everyone with access.

## How it works

| Piece | Where |
|---|---|
| Routing, access, layout, form handling, actions | `wordpress/plugins/cacdemo-content/includes/manage/manage.php` |
| Screens and the extra inputs (date, speaker/series/topics, cover, parent page, excerpt) | `includes/manage/screens.php` |
| Look | `wordpress/themes/cacdemo/assets/css/manage.css` (enqueued by the theme on `cacdemo_manage_enqueue`) |
| Tests | `tests/manage.php`, run by `scripts/test.sh --publishing` |

- `/manage/…` is a rewrite rule to one query var; signed-out visitors go to the WordPress sign-in and come back.
  Pages are `noindex`, not cached, and have no admin bar.
- Forms are SCF `acf_form()`: SCF signs the form settings and allowed fields, checks its nonce, runs kses and saves through
  WordPress. Before saving, `cacdemo_manage_authorize_save()` checks `edit_post` on the record (or the create capability,
  plus the ministry for new updates and ways to serve). After SCF's save, the extras are saved with their own capability
  checks, links come from the signed form context (never from inputs), and the chosen button publishes, schedules (future
  date) or unpublishes, each checked with `publish_post`.
- Screens resolve records from the address and refuse anything the user cannot edit (403 page). Trash and People actions
  carry their own nonces and capability checks.
- Importer-only source details (posted date, length, platform ID) are kept in the form but hidden; the ID is derived from
  the link.
- wp-admin still works for administrators and for anything unusual (menus, templates, designed pages).

## Verified

`scripts/test.sh --publishing`: routine checks, the publishing permissions test and 21 Content Manager checks, including
access per section, publishing a sermon through the real form, unpublish, altered signed form data saving nothing, trashing
someone else's sermon refused, a ministry Contributor publishing an update linked to their ministry, a Content Admin creating a
draft sub-page, designed pages opening the full editor, adding a person, a copied People form refused for non-admins, and the
website's links. In the browser (desktop): signing in, creating and publishing a sermon with a YouTube link, the sermon and ministry
screens.

Release closure (2026-09-16), in the browser with temporary scoped accounts: a sermon Contributor at phone width (sign-in to
`/manage/`, **+ New sermon** from the Sermons page, empty list, validation error on a wrong link, draft, edit, publish, trash
confirmation, no controls on another ministry, direct REST writes to a ministry, page and channel refused); a Psalmists
Contributor at tablet width (**+ Add update** on their ministry, publish, shown on Psalmists only, other ministry and ways to
serve refused); a Content Admin at phone and desktop width (Pages tree, **Add sub-page** from a page, designed page opens the
full editor, church-wide news, channels and people screens). Fixed during closure: creating an item with only a title did not
save and could change the newest existing post (regression test added); stacked-table labels; home page address in Pages.

## Next

Overview dashboard (my drafts, recently published) if wanted; a check with the church's real contributors; Events and other sections once
their content models exist; image focal point / multiple images; menu placement for new pages (still wp-admin).
