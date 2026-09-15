# Information architecture

How Christlikeness content is classified, where it lives in WordPress, which views
WordPress generates, and which capability decisions are still open.

- **Status:** Phase 4 proposal (2026-09-14). Nothing below is implemented unless marked **current**.
- **Inputs:** `docs/content-harvest/*` and `content-source/legacy-site/`.
- **Governing principle:** editors manage content and classification · WordPress manages
  publishing and organization · plugins add only necessary capability · the theme provides
  the design · external services provide clearly defined integrations.

---

## 1. Content classes

The legacy site is **source material, not the specification**. Having existed on the old
site does not mean content is published on the new one. Being old or hidden does not make it
useless.

| Class | Meaning | Where it goes | Default visibility |
|---|---|---|---|
| **A. Core site information** | Identity, mission, history, beliefs, centres, service times, ministries, contact, visitor info | Pages and structured church data (centres) | Public, once verified |
| **B. Topical / teaching resources** | Teachings, Bible material, prayer, deliverance, discipleship, devotionals | Resources (structured, topic-classified) — **never** top-level Pages | Per item; church decides |
| **C. Member / internal resources** | Checklists, training, internal forms, restricted teaching | Future member/leader area with real access control | Restricted (not built) |
| **D. Historical / time-bound** | Past events, campaigns, prayer seasons, conventions, expired forms | Source archive; optional future "past events" | Not republished |
| **E. Media** | Sermons, devotionals, teaching videos, music, galleries | YouTube (video), Media Library (curated images), future gallery workflow | Per item |

Cross-cutting flag: **VERIFY** (fact conflict or unconfirmed), **REVIEW** (authorship,
pastoral, leadership). A flagged item is not published until cleared, whatever its class.

## 2. Classification of harvested material

Legacy page files are in `content-source/legacy-site/pages/`. Review IDs (R-xx) are in
`docs/content-harvest/REVIEW-NEEDED.md`.

### A · Core site information

| Material | Legacy source | Flags | Destination |
|---|---|---|---|
| Mission statement | `/about` | VERIFY current (R-16) | About, Home |
| Founding / lead pastors sentence | `/about` | VERIFY current (R-16) | About |
| "LOVE is our highest goal!" | `/about` | VERIFY current | About |
| Declaration of Faith (19 titles + references) | `/about` desktop & mobile | VERIFY — versions differ (R-03) | About (beliefs section) |
| Centre: North York (4544 Dufferin St. Unit 210, L4K 5M5; Sunday 10:00 AM) | `/church`, `/rsvp`, home artwork | VERIFY time/pairing (R-02) | Centre record |
| Centre: Scarborough (2220 Midland Ave., M1P 3E6; Sunday 2:00 PM) | `/church`, `/rsvp` | **Unit conflict 84 BR vs 102 BR (R-01)** + R-02 | Centre record — unit stays unconfirmed |
| Welcome wording ("Be our guest. Come as you are.", "Looking for a church?") | home artwork | VERIFY still wanted | New Here, Home |
| Phone 289.212.0807 | home artwork only | VERIFY (R-05) | Contact / centre contact |
| Social profiles (Facebook, Instagram, Twitter, YouTube) | Home, Church, Connect | VERIFY active (R-09) | Connect, footer |
| Christlike Worship & Radical Music descriptions, logos, Spotify artists | `/music` | VERIFY dated wording (R-12) | Music |
| Ministries with some description: Psalmists, R.A.D.I.C.A.L youth, More Than Enough | `/music`, `/closed`, `/mte-closed` | VERIFY current, need descriptions (R-07) | Ministries |
| Ministry names only (10 volunteer teams, Lifegroups, Sunday school, SEED) | `/volunteer`, `/laf`, `/stc`, `/finalsbatch3` | Names only (R-07) | Ministries, once described |

### B · Topical / teaching resource candidates

| Material | Legacy source | Topic (derived) | Flags | Publication status |
|---|---|---|---|---|
| "deliverance" (intro + 110-item list) | `/deliverance` | Deliverance | Provenance/authorship/copyright unverified; pastoral review; audience undecided (R-17) | **Undecided** |
| "Prayers for deliverance" | `/deliveranceprayer` | Deliverance (also prayer) | Same as above (R-17) | **Undecided** |
| Devo Bible (printed devotional resource) + description | `/devobible`, `/church` | Bible | Availability/ordering unknown (R-06) | Undecided |
| Seven Bible-book overview videos (Joshua, 3D Tabernacle, Ezra-Nehemiah, Job, Isaiah, Ezekiel 1 & 2) | `/devobible-*` | Bible | Authorship/rights unknown; not downloaded (R-06) | Undecided |

Only two topics are evidenced by real content: **Deliverance** and **Bible**. Prayer,
Discipleship, Christian Living and Leadership have **no** harvested teaching content, so no
categories are created for them now.

### C · Member / internal candidates

| Material | Legacy source | Likely audience | Note |
|---|---|---|---|
| Psalmists' weekly report | `/pwr` | Leader/ministry | Internal accountability form |
| Sunday school teacher's checklist | `/stc` | Leader/ministry | Training/process material |
| SEED Level 1 quiz | `/finalsbatch3` | Member (discipleship participants) | Assessment; implies a discipleship course |
| Lifegroup availability form | `/laf` | Member | Internal coordination |
| Volunteer worker application | `/volunteer` | Member | Process; ministry list is class A evidence |
| VIP guest form | `/vip` | Public (form) | Welcome wording is class A |
| Sunday RSVP form | `/rsvp` | Public (form) | Visit planning; forms are a future capability |

None of these is recreated. Forms are a future capability (core → free plugin → custom).

### D · Historical / archive only

| Material | Legacy source |
|---|---|
| Youth Convention 2024 "COUNTER-CULTURE" (registration closed) | `/closed` |
| MTE Spring Drive — Food Pantry & Clothes Closet, April 20, 2024 | `/mte-closed` |
| 21 Days Prayer & Fasting, Feb 1–21, 2022 | `/paf` |
| "Home2" alternate landing, "Copy of Church", empty gallery, menu popup | `/coming-soon-03`, `/hs2`, `/fullscreen-page`, `/popup-ipify` |

Preserved in the archive; not republished. Useful facts inside them (youth ministry
statement, MTE identity, Scarborough unit evidence) are extracted into class A as evidence only.

### E · Media

| Material | Treatment |
|---|---|
| 8 Wix-hosted videos (1.47 GB) | Stay outside Git and WordPress uploads. Future canonical host: YouTube. No migration authorized. |
| 14 harvested images | Source library only. Upload to the Media Library only when a page actually uses one. |
| Spotify artists (CW, RM) | Core Embed block on Music |
| YouTube channel | Future Sermons source (core Embed / Query of sermon records) |

### Obsolete / not migrated

Wix platform structures, SEO keyword list, placeholder video titles/descriptions, blank
video frames and textures, Wix stock icons and clip-art.

---

## 3. Site structure

### 3.1 Primary navigation (native Navigation)

**Approved (DD-6, 2026-09-14):** Contact leaves the primary navigation.

- **Header menu:** `Home · About · New Here · Centres · Sermons · Ministries · Connect · Music`
- **Header action:** a persistent **Find a centre** action, more prominent than Contact.
- **Contact:** prominent in the footer and linked wherever contextually useful (New Here,
  centre views, Connect).
- **Current state:** the live "Main" menu still contains Contact. It is removed when the
  footer that carries Contact is built, so the Contact page is never orphaned.

Connect and Contact still overlap. Proposed split:

- **Connect:** social, groups, serving, next steps.
- **Contact:** phone, email, forms, centre contact derived from centre records.

Content types, taxonomies and resource categories are **not** added to the header
automatically. Resources are reached from Explore, cross-links and (optionally, later) a
curated menu item.

### 3.2 What is authored vs structured vs generated

| Item | Kind | Maintained by | Notes |
|---|---|---|---|
| Home, About, New Here, Connect, Music, Contact | **Pages** (authored) | Editors, Gutenberg | Centre facts inside them come from centre records, not typed text |
| Centres | **Page + generated directory** | Page intro authored; list generated from active centre records | |
| Sermons | **Page intro + generated sermon collection** | Editors + importers | Sermon records (SCF); see SERMON-SOURCES.md |
| Ministries | **Page** now → generated directory later | Editors | Becomes generated if ministries become records |
| Individual centre (North York, Scarborough) | **Structured record** | Editors, once | Canonical source for addresses/times/contact |
| Individual resource/teaching | **Structured record** (model pending) | Editors | Topic + audience classification |
| Resource topics | **Taxonomy** (hierarchical) | Editors create terms only when content exists | |
| Centre association on other content | **Taxonomy** (optional, multi-select) | Editors tick zero, one or many centres | Zero = church-wide |
| Resource audience | **Taxonomy or field** (Public / Member / Leader / Restricted) | Editors | Classification only — **not** security |
| Centres directory, resource topic directory, recent resources, archives | **Generated** (Query Loop, Terms Query, archives) | WordPress | Never hand-maintained |
| Explore / human sitemap | **Page built from generated blocks** | Structure once; content auto-updates | See §4.3 |
| XML sitemap | **Generated** (core `wp-sitemap.xml`) | WordPress | Status issue noted (R-18) |
| Header, footer | **Theme template parts** | Theme structure; content from Navigation, Site Logo, centre records | No hard-coded addresses |

### 3.3 Proposed tree (public)

```text
Home                      (Page)
About                     (Page: story, mission, beliefs, leadership)
New Here                  (Page: welcome + generated centre/service summary)
Centres                   (Page: intro + generated directory)
  └─ /centre/<slug>/      (generated from centre records: North York, Scarborough)
Sermons                   (Page landing; future sermon archive/series/speaker/scripture views)
Ministries                (Page; later generated directory)
Connect                   (Page)
Music                     (Page)
Contact                   (Page)
Resources                 (generated archive, not in header)
  └─ /resources/topic/<topic>/   (generated per topic that has content)
Explore                   (Page of generated blocks; footer link)
```

### 3.4 Automatic views (planned)

| View | Generated from | Mechanism |
|---|---|---|
| Centres directory | Published, active centre records | Query Loop (+ block bindings for fields) |
| Centre summaries in New Here, footer, About | Same records | Query Loop with bound blocks (layout may be a theme pattern) — layout reused, data never copied |
| Resource topic directory | Topic terms with published items | Terms Query / Categories-style term blocks |
| Recent resources | Resource records | Query Loop |
| Sermon series / speaker / scripture archives | Future sermon records + taxonomies | Taxonomy archives + Query Loop |
| Ministry directory | Future ministry records (if adopted) | Query Loop |
| Explore | Pages, Main navigation, resource topics, centres | §4.3 |
| XML sitemap | All public content | Core sitemaps (or approved SEO plugin later) |

---

## 4. Capability analysis

All options below keep content in standard WordPress structures (posts, post meta, terms),
so they export via **Tools → Export (WXR)** and survive a theme change. Plugin data
definitions are the portability risk, so each option says where the definitions live.

### 4.1 Centres

| | |
|---|---|
| **Native WordPress option** | Core cannot define a post type, taxonomy or registered fields without code. Nearest no-code form: child Pages under Centres, each holding its details as blocks, listed by Query Loop (parent = Centres). But the facts would be free text in each page, so the footer and New Here could not reuse them without copying — this fails the canonical-data rule. Core *does* provide the display side: Query Loop for custom post types, and **block bindings (`core/post-meta`)** to show registered meta in paragraph/heading/button/image blocks. |
| **Free plugin option(s)** | **Secure Custom Fields** (WordPress.org-maintained fork of ACF free; v6.9.5, tested 7.1, 90k installs, updated 2026-08): registers post types, taxonomies and field groups through a UI, stores values in normal post meta, supports block bindings, can export definitions as JSON/PHP. **Pods** (v3.3.9, tested 7.1, 100k): similar, heavier framework. **Custom Post Type UI** (1M, tested 7.0.4): types/taxonomies only, no fields. **Meta Box** (500k): the free core is developer-oriented; its UI builders are paid. **ACF** (WP Engine, 2M): the free original, overlapping SCF. |
| **Custom option** | Small `cacdemo-content` plugin: `register_post_type('centre')`, `register_taxonomy('centre_tax')` for associations, `register_post_meta` (show_in_rest) for a handful of fields. Definitions live in Git. The editing UI would be core's Custom Fields panel (plain key/value) or bound blocks, unless extra JS is written. |
| **Recommendation** | **Decision needed (see §6).** Leaning: **Secure Custom Fields** for the `centre` post type, its fields and the centre-association taxonomy. Core handles all display (Query Loop + bindings). Definitions are exported to the repository so the model is reproducible. The fallback, if a plugin dependency for the data model is unwanted, is the small `cacdemo-content` plugin. |
| **Why** | The policy prefers a mature free plugin before custom code. SCF is maintained by WordPress.org, gives editors a proper form UI with no custom JS, and keeps data in standard post meta. The model is small either way (one type, one taxonomy, ~6–8 fields). |
| **Portability** | Both options store content as posts/meta/terms, so WXR export works. SCF: if deactivated, the `centre` type and field UI disappear from admin but the data remains in the DB; definitions must be kept in Git (exported JSON/PHP) and re-imported on rebuild — the exact import mechanism must be confirmed at implementation. Custom plugin: definitions are code in Git; deactivation hides the type but keeps the data. |

**Minimum fields now** (only what the verified content needs): name (title), slug,
active status, street address, unit, city, province, postal code, service schedule (day +
time, repeatable or text), map link, phone (optional).

**Future, not now:** centre image, description, directions, parking/accessibility,
children's info, leadership, centre links, calendar ID.

**Uncertain data.** A centre record is published only when its facts are church-confirmed.
Until then it stays **draft** (native status), so generated views never show it. Unconfirmed
fields such as the Scarborough unit are left **empty with an editorial note**, never filled
with one of the conflicting values.

**Association.** A shared non-hierarchical taxonomy (e.g. `centre`) attachable to events,
ministries, resources, sermons and pages. No term = church-wide. It is not required on any
content type.

### 4.2 Resources / Teachings

| | |
|---|---|
| **Native WordPress option** | **Posts + a category branch** (e.g. "Resources › Deliverance / Bible") gives archives, feeds, Query Loop and term blocks with zero code. But Posts will likely also carry news/announcements and possibly sermons, so resources would share one type, one archive and one sitemap bucket, and every Query Loop would need category filtering. Core has no "audience" concept other than the private status. |
| **Free plugin option(s)** | **Secure Custom Fields** or **Pods**: `resource` post type with `resource_topic` (hierarchical) and `audience` taxonomies, plus fields such as source/provenance, author and scripture. Same engine as Centres, so no extra plugin. |
| **Custom option** | `register_post_type('resource')` + two taxonomies in `cacdemo-content`. |
| **Recommendation** | **Defer implementation.** No resource is cleared for publication (all four candidates are under R-06/R-17 review), and only two topics have evidence. When the first item is approved: use a dedicated `resource` type with `resource_topic` and `audience` taxonomies, created with **the same mechanism chosen for Centres**, so the site has one content-modeling approach. |
| **Why** | A dedicated type keeps teachings out of the news/sermon stream, gives `/resources/` and per-topic archives for free, and keeps sensitive items separable. It is premature to build before any item is publishable. |
| **Access-control implications** | The `audience` term is **classification, not protection**. Member/Leader/Restricted items must not be published publicly until real access control exists (§4.4). Until then such items stay draft or private. |
| **Portability** | Posts/meta/terms → WXR. Topic/audience terms export with content. The same definition-in-Git requirement as Centres. |

### 4.3 Human-readable Sitemap / Explore

| | |
|---|---|
| **Native WordPress option** | An "Explore" Page built only from generated core blocks: **Navigation block reusing the Main menu** (the Visit/Church/Watch groupings come from the curated menu rather than a second list), **Page List** (all published pages), **Query Loop** (active centres; recent resources), **Terms Query** (resource topics that have content). It updates automatically, needs no plugin, and the layout can ship as a theme pattern. |
| **Free plugin option(s)** | **Simple Sitemap** (block-based HTML sitemap; 60k, tested 7.1, rating 78). **WP Sitemap Page** (shortcode; 200k, tested 7.1). |
| **Custom option** | Not justified. |
| **Recommendation** | **Native Explore page from core blocks**, built in the design phase once centres/resources exist. XML sitemap: **core `wp-sitemap.xml`** now; an approved free SEO plugin (Yoast or Rank Math, both tested 7.1) only if SEO needs exceed core. **Finding:** locally, core sitemap URLs return correct XML but with HTTP 404 status (same under Twenty Twenty-Five, so not our theme) → investigate before launch (R-18). |

### 4.4 Member / restricted resources (architecture discussion only — do not implement)

| | |
|---|---|
| **Native WordPress capabilities** | Users and roles (subscriber/contributor/author/editor/admin), capabilities, **private** status (readable only by users with `read_private_*` — editors/admins by default), password-protected posts (single shared password; weak). Core cannot restrict by custom role without code or a plugin. |
| **Free plugin candidates** | **Members** (roles/capabilities editor + per-post "content permissions" by role; 300k, rating 98, tested 7.1). **User Role Editor** (roles only; 700k). **Ultimate Member** (full community/profile system; 200k — heavier than needed). **Restrict Content / Kadence Memberships** (9k, rating 62, tested 6.9 — not recommended). **Paid Memberships Pro** is closed in the WordPress.org directory — excluded. |
| **Security requirements** | Enforcement on the server for every output path: single view, archives, Query Loop, search, **REST API**, RSS feeds, XML sitemap, oEmbed, excerpts in listings. Individual user accounts (no shared passwords). Least privilege per role. Restricted **files** must not sit at guessable public `wp-content/uploads` URLs. HTTPS. Never "security by hiding links". Test leak paths when built. |
| **Recommendation for later** | Custom roles (e.g. Member, Leader) plus per-item restriction via **Members**, evaluated against the leak paths above. Restricted documents go in protected storage, not public uploads. Pastoral/restricted material may be better kept off the website entirely. **Not implemented; needs explicit authorization.** |

---

## 5. Media strategy and source of truth

| System | Role | Holds | Rules |
|---|---|---|---|
| **WordPress** | Canonical | Pages, navigation, centres, resources, classification, presentation | |
| **YouTube** | Canonical video host | Sermons, devotionals, teaching, music videos, testimonies | Embedded via core Embed block. No routine video files in Git, uploads, theme or plugin. |
| **WordPress Media Library** | Canonical curated imagery | Logos, heroes, centre/ministry/leader images used by pages | Upload only what pages use, not the whole harvest |
| **External/drop storage** (FTP, Google Drive, other) | Candidate gallery source (future) | High-volume event/activity photos | Future flow: drop → discovered → **draft** gallery → review → publish. Upload never means public. Not built. |
| **Google Calendar** | Future integration — ownership TBD | Church/centre/ministry calendars | Source of truth and sync direction decided before building |
| **Facebook / social** | Distribution / community / supplementary | Posts, activity photos, groups | Never canonical for essential info. No dependency on Facebook CDN image URLs. Group content is not assumed public. No Meta API now. |

No uncontrolled bidirectional sync. Every integration declares its role: canonical source,
synchronized source, publishing destination, embed provider or external archive.

## 6. Decision gate

| # | Decision | Options | Recommendation | Blocks |
|---|---|---|---|---|
| D-1 | How to define structured content (Centres now, Resources later) | (a) Secure Custom Fields · (b) small `cacdemo-content` plugin · (c) Pods | **Resolved 2026-09-14: (a) SCF**, definitions exported to `config/scf/` | — (centre model implemented; records are drafts, see D-3) |
| D-2 | Centre fields now | Minimal list in §4.1 | **Implemented with D-1:** service day/time, street, unit, city, province, postal code, map URL, phone, internal verification notes | — |
| D-3 | Church confirmation of centre facts | R-01, R-02, R-05 | Keep centre records draft until confirmed | Publishing any centre data |
| D-4 | Page content verification | Mission, founding, beliefs (R-03), music wording (R-12), social links (R-09) | Church confirms, then populate | Populating About/Home/New Here/Music/Connect |
| D-5 | Resources model timing | Build now vs at first approved item | At first approved item | Nothing now |
| D-6 | Deliverance, Devo Bible and videos: publication and audience | Church/pastoral decision (R-06, R-17) | — | Any resource publication |
| D-7 | Connect vs Contact split | §3.1 proposal | Accept | Page content scope |

## 7. Cross-cutting requirements

- **Responsive:** every structure must work on desktop, tablet and mobile. No desktop-only
  interactions (hover-only menus, wide tables as the only presentation).
- **Portability:** rebuildable from pristine WordPress + theme + approved plugins +
  (optional) `cacdemo-content` + exported content/configuration. Content never lives in
  theme templates.
- **Plugin safety** (for any future `cacdemo-content`): clean install/activate, safe
  deactivate (never deletes content), safe upgrades, no core or third-party patching.
