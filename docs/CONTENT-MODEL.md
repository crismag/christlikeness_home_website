# Content model

**Status:** Pages, navigation, Centre and Sermon are implemented; other models are proposed.

- **Current:** 9 Pages, the native "Main" navigation menu, `centre` and `sermon` records.
- **Detail:** classification, generated views and the decision gate are in
  [INFORMATION-ARCHITECTURE.md](INFORMATION-ARCHITECTURE.md).

Every model goes through the capability decision order in
[ARCHITECTURE.md](ARCHITECTURE.md), with a proposal (native option, free plugin options,
recommendation, reason, tradeoffs) and approval before implementation. Content models never
live in the theme. Data stays in standard posts, post meta and terms, so it is exportable.

## Proposed models

| Model | Kind | Status | Notes |
|---|---|---|---|
| Pages (Home, About, New Here, Centres, Sermons, Ministries, Connect, Music, Contact) | Core Pages | **Current** (empty) | Authored content only; centre facts inside them are derived, never typed |
| Primary navigation | Core Navigation (`wp_navigation` "Main") | **Current** | Curated; not auto-expanded with types/taxonomies |
| **Centre** | Structured record: post type `centre` (Secure Custom Fields, `config/scf/`) | **Current** — records are drafts until church-confirmed (D-3) | Canonical source for service day/time, street, unit, city, province, postal code, map URL, phone; `centre_verification_notes` is internal and never displayed. Publishing state is the active status (draft = not shown). Order by *Order* (menu order). Displayed only through Query Loops with field bindings |
| **Centre association** | Taxonomy (non-hierarchical) on other content | Proposed — at first content that needs it (SCF taxonomy) | Optional; no term = church-wide; zero, one or many centres |
| **Resource / Teaching** | Structured record: post type `resource` | Proposed — **deferred until first approved item** (D-5) | Same modeling mechanism as Centre |
| **Resource topic** | Hierarchical taxonomy | Deferred | Terms only for topics with content (evidence today: Deliverance, Bible) |
| **Audience** | Taxonomy (Public / Member / Leader / Restricted) | Deferred | Classification only; not access control |
| **Sermon** | Structured record: post type `sermon` (SCF), taxonomies `sermon_series`, `sermon_speaker`, `sermon_topic` (hierarchical) | **Current** | One sermon per service. Fields: `sermon_scripture`, `sermon_featured`, `sermon_location` (centre), `sermon_language` (English / Tagalog / Taglish), `sermon_sources` (repeater: type video/audio, platform, link, label, published, length, source ID). Post date = service date; featured image = saved preview still. Summary and Related resources in the content. Comments (core) supported, closed by default. Imported from the Facebook and YouTube harvests with reconciliation: [SERMON-SOURCES.md](SERMON-SOURCES.md) |
| Event | Future (events plugin vs core) | Future | Prefer iCal/Google-friendly option; centre association applies |
| **Ministry** | Structured record: post type `ministry` (SCF), URL `/ministries/<slug>/` | **Current** — seven records from the brief's draft copy, local only until the church confirms (marked `_cacdemo_seed = ministries-brief-draft`) | Title, excerpt, content (introduction; section hides when empty), featured image (optional; omitted when missing). Fields: `ministry_tagline`, `ministry_short_name`, `ministry_contact_label`/`_url` (public contacts only), `ministry_invite_heading`/`_text` (defaults provided). Order = menu order. Listed on the Ministries page by Query Loop; the menu's Ministries submenu is curated. Design: [MINISTRIES-SERVE-DESIGN.md](MINISTRIES-SERVE-DESIGN.md) |
| **Social channel** | Structured record: post type `channel` (SCF), not public | **Current** — 5 Facebook pages/group confirmed by the church 2026-09-16 | Name, short name, platform (Facebook page/group, Instagram, YouTube, Spotify, X), https link checked against the platform, one-line purpose, optional centre, "show recent posts" (Facebook page widget on Follow Us), "show on home page". Order = menu order. Shown by block `cacdemo/channels`: compact (home band), list (Connect), feeds (Follow Us). Links only; nothing fetched or stored from Facebook |
| **Ministry update** | Core post + SCF field `update_ministry` (→ ministry) | **Current** (no records) | Dated news for one ministry, created with **Add update** on the ministry page; listed in that ministry's Updates. Posts without a ministry are church-wide |
| **Serve role** | Structured record: post type `serve_role` (SCF), not publicly queryable yet | **Current** (Phase 1: listed on ministry pages only) | One way to serve in one ministry. Fields: `role_ministry` (required), `role_status` (Ongoing · Needed now · Paused · Filled; public views show Ongoing and Needed now), `role_experience`. Excerpt = one-line description. Never mark "Needed now" without a real need. `/serve/` pages, filters and the interest form are Phase 2 |
| People / leaders | Future — separate church organization page (not built) | Future | Only verified, consented information. Known (2026-09-16): Apostle Eljay Payopay and Prophet Czarina Payopay (lead, still preaching); assigned pastors Carol Reyes and Justine Arceo. Sermon speakers are a taxonomy and can later link to people records |
| Gallery | Future review-before-publish ingestion | Future | Not built |

## Other deferred requirements

| Requirement | Evaluate first |
|---|---|
| Contact / RSVP / guest forms | Free form plugins (replace Hostinger and Wix forms) |
| Newsletter subscription | Provider's free plugin/embed (replaces Hostinger Reach block) |
| Member / restricted access | Core roles + **Members** plugin (see INFORMATION-ARCHITECTURE §4.4) — needs explicit authorization |
| Legacy URL redirects | Free redirection plugin or server rules |
| Simplified publishing UX | Core roles, statuses, revisions, block locking/patterns first |
| YouTube | Core Embed block |
| Google Calendar / Drive / Workspace automation | **Future phase — not now.** Decide source of truth and sync direction first; see ARCHITECTURE.md → Future Google integration |
