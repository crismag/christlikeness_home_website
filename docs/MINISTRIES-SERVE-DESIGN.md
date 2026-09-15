# Ministries & Serve — reference analysis and design proposal

**Status:** proposal reviewed 2026-09-16. Decisions: **model B** (Ministry → Serve role + "Needed now"), the name is **Gift and Arrows**,
**the seven ministries only** for now, and the brief's draft copy is **published locally for design review** (not deployed until confirmed). Phase 1 is implemented locally (see §11).
**Scope:** the public `/ministries/` experience (Phase 1) and the later `/serve/` experience (Phase 2).
**Platform note:** this site is a WordPress block theme. The brief's "components/routes" map to templates, patterns,
blocks, Secure Custom Fields (SCF) content types and core Navigation, and follow the existing sermon and centre patterns.

---

## 1. Live reference inspection

Both sites were inspected in a rendered browser at desktop 1440px and at mobile 390px where noted, following internal
links. Neither blocked automated browsing. Nothing on either site was changed.

### 1.1 Anchor Church Tacoma — `/serve` (visited 2026-09-16)

| Aspect | Observed |
|---|---|
| Page hierarchy | Hero ("SERVE TEAMS / Jesus served us, so we serve others") → two-column statement ("We come alive when we serve" + one paragraph) → dark band "Serve Opportunities" with **four broad teams** (Worship + Production, Anchor Kids, Admin + Maintenance, Hospitality), each a heading, 1–2 short paragraphs and "Join the Team" → "Take your next step at Anchor" carousel. About 3,400px tall. |
| Taxonomy | Only **team level**. No sub-roles, commitments or criteria on the page. Descriptions say in prose what is involved ("tech teams, as a vocalist, or as an instrumentalist"; "office projects… yard work"). |
| Action | All four "Join the Team" links go to **one** Planning Center form, "Serve at Anchor!". It asks for name, email, phone, phone type and campus. It **does not ask which team**, so follow-up is a conversation. |
| Relationship to ministries | Ministry pages (e.g. `/kids`) speak to **participants** (families), not volunteers. Serving appears only as a generic "Start Serving" card in the shared next-steps carousel. Ministry and serving are separate perspectives. |
| Navigation | Hamburger overlay: Locations · Quick links · a flat "More" list (Team, Mission, Groups, Serve, Anchor Kids, Students…). No grouped mega-menu. |
| Visual | Very large uppercase geometric sans (H1 ~125px, H2 56px), dark/cream bands, rounded photo, one image beside the team list on desktop. Mobile: teams become a **horizontal swipe carousel** of tall photo cards with scroll-reveal. |
| Tone | Warm, invitational, not a job board. It stresses the benefits of serving (friendships, growth), not requirements. |

### 1.2 Covenant EFC Singapore — `/recruitment/` and linked pages (visited 2026-09-16)

| Aspect | Observed |
|---|---|
| Concepts (verified) | The **Serve** menu keeps them separate: **Serving Opportunities** (the Step-Up system), **Recruitment** (Staff Recruitment on `/recruitment/`, plus New Life Recruitment linking to a separate careers site), **Student Internship**, **Our Ministries**, **Volunteers' Code of Conduct** (PDF). |
| `/recruitment/` page | Hero "SERVE WITH US" → a **paid staff role** ("Ministry Executive – The Next Generation", expandable) → "Step Up To Serve" accordion with volunteer roles (Disciplemaker, Tech Crew, Worship Musician), linking out to Step-Up → "More Step-Up opportunities". One page mixes staff hiring and volunteer roles. |
| Step-Up system (`step-up.cefc.org.sg`) | "Ministry Registration Dashboard": **about 104 standing volunteer roles in 31 ministry groups** (Audio-Video, Hospitality, Worship, TNG Pre-school…), shown as ministry cards with scrolling role lists. Some roles are location-specific ("@ BPJ Centre"). These are **standing roles, not dated vacancies**. |
| Role detail | Title ("Audio Crew - Audio-Video") → **Description** → **Criteria** ("Love for music and a willingness to learn", "Available to rehearse on Saturdays"; *Note: Training will be provided*) → **Commitment** ("Minimum 2-year commitment", "Serve 1-2 sessions a month") → **Apply Now** (member login). The Worship Musician role adds spiritual and relational criteria ("Christian", "regularly attending CG for at least 6 months", "Faithful, available and teachable") and an **audition**. |
| "Our Ministries" | A grid of 10 photo tiles (Worship, Prayer, Outreach, TNG…) → long editorial ministry pages (purpose, theology, scripture). Ways to serve appear as **one prose sentence** plus "If you would like to get involved, click here" linking to Step-Up. |
| 4Cs | "Calling, Character, Competence, Chemistry" appear on the **Student Internship** page as internship criteria. They are **not** the volunteer framework. Volunteer roles use Criteria + Commitment. |
| Visual | Orange/grey corporate palette, photo hero, accordions, and a plain functional Step-Up app. Informative but administrative; the look is the weakest part. |

### 1.3 What the references teach

1. **Ministry and serving are separate views in both sites.** This supports the Ministries vs Serve split.
2. **Neither site has a separate "service area" and "opportunity".** Anchor stops at team level. CEFC's standing *role*
   (Audio Crew) is both the stable area and the thing you apply for. Needs are implicit: a role is listed while its team wants people.
3. **Detail that helps volunteers:** description, criteria (including "willingness to learn"), training note,
   commitment, and extra steps (audition, attendance). CEFC shows this depth in a plain form.
4. **Interest flows range** from Anchor's 5-field form (no team choice) to CEFC's login and application. A light
   form with the role preselected sits between them.
5. **Staff recruitment and internships are different products.** Keep them out of Serve if they are ever needed.
6. **Navigation stays simple.** Neither site uses a grouped mega-menu.

---

## 2. Current Christlikeness state

| Area | Today |
|---|---|
| `/ministries/` | An ordinary Page (#68, default template) built by `scripts/seed-content.php`. It has an intro, a dark "We are Radicals!" youth band, rows (Psalmists → `/music/`, More Than Enough, Lifegroups, Sunday School, SEED, Prayer and Fasting), and a "Serve with us" statement listing the 10 legacy volunteer teams with a Contact link. **These descriptions were design copy, not church-provided.** |
| Ministry data | None structured. Evidence in the legacy archive: the volunteer form's teams (More Than Enough, Facilities, Victuals, Events, Guest Services, Prayer, Productions, Creatives, Field Ministry, **Gifts** and Arrows), Psalmists / Christlike Worship, R.A.D.I.C.A.L youth and its music, and the MTE Spring Drive artwork. |
| Content types | SCF: `centre`, `sermon` (+ series/speaker/topic). The pattern is established: SCF definitions in `config/scf/`, records in WordPress, display through Query Loops and block bindings, and small helpers in the `cacdemo-content` plugin. |
| Navigation | A flat native `wp_navigation` "Main" menu (no submenus). Core Navigation supports submenus; the theme turns the menu into an overlay below 1200px. |
| Components available | Theme patterns: hero, statement, worship band, directory rows, CTA band, tone bands (Mist/Night), arch image, rows; sermon card, filter bar and view switch ideas; `cacdemo/sermon` bindings; centre association. |
| Forms | **No forms capability.** Contact is a page, and no form plugin is approved. |
| Events | **No events system.** |
| Images | No ministry photography. Available: the worship frame, the MTE seal artwork, the Radical youth banner, Christlike Worship and Radical Music logos, and 299 sermon video stills (mostly preaching). |
| Oikonomia | Not referenced anywhere in the repository. The public site has no integration to protect or remove. |

---

## 3. Proposed information architecture

```text
/ministries/                 Ministries landing (purpose, all ministries, invitation)
/ministries/<ministry>/      Ministry page — "what we do and why"
/serve/                      Serve landing — "where can I participate?" (Phase 2)
/serve/<role>/               Serve role detail (Phase 2)
```

### 3.1 Model choice (decision needed)

The brief proposes **Ministry → Service Area → Service Opportunity**. The references suggest a simpler, equally
expressive model:

| | **A. Brief's model (3 levels)** | **B. Recommended: Ministry → Serve role (+ "needed now")** |
|---|---|---|
| Stable area ("Audio") | Service Area (repeater on the ministry) | Serve role, status *Ongoing* |
| Current need ("Camera operator needed") | Separate Opportunity record | Same role, status *Needed now* (optional note + date) |
| Detail (criteria, commitment, training) | On the Opportunity only | On the role, always available |
| Editors maintain | Two things per area; they drift apart | One record per way to serve |
| Matches references | Neither | CEFC Step-Up (role = area + detail) and Anchor's invitation tone |
| Risk | Empty "areas" with no detail; duplicate wording | "Needed now" must never be set just to fill the layout |

In B, **Ministry and Serve role stay separate objects**, as the brief requires. A ministry page's *Ways to serve* lists
its roles, and `/serve/` lists the same roles across ministries. "Audio" stays part of Productions when nobody is
needed. If time-limited openings with their own dates are ever required (e.g. "Christmas production crew"), they can
be roles with an end date, without a third model.

### 3.2 Scope boundaries

- **Not in Serve:** staff hiring, internships, internal rosters and reports (the legacy Psalmists' weekly report is class C).
- **Groups and discipleship** (Lifegroups, SEED, Sunday School, prayer and fasting) and **R.A.D.I.C.A.L youth** are not in the
  seven ministries. The church needs to say whether they are ministries, programs or Connect content (§8).
- **Unlisted legacy teams:** Guest Services, Prayer, Creatives and Field Ministry are on the old volunteer form but not in the seven (§8).

---

## 4. Desktop layout

### 4.1 `/ministries/`

```text
┌ Header ─ Ministries ▾ (submenu) ───────────────────────────────────────────┐
│ Ministries                                   [Find a place to serve]         │  compact title band (no full-screen hero)
│ Serving Christ by serving one another.        Explore ministries ↓          │
├─────────────────────────────────────────────────────────────────────────────┤
│ Our ministries                                                               │
│ ┌────────────┬───────────────────────────┐ ┌────────────┬──────────────────┐ │  2-up editorial rows: photo or
│ │ photo/type │ Psalmists                 │ │ photo/type │ Productions      │ │  typographic panel + name, purpose,
│ │            │ Worship and music…        │ │            │ Supporting…      │ │  up to 4 area labels, "Needed now"
│ │            │ Vocals · Keys · Drums     │ │            │ Audio · Video…   │ │  when true
│ └────────────┴───────────────────────────┘ └────────────┴──────────────────┘ │
│  … (order and grouping from ministry records; 1–N items, no dummy cards)     │
├ Mist band ──────────────────────────────────────────────────────────────────┤
│ "Different gifts, one body" statement + scripture       [Find a place to serve]
└─────────────────────────────────────────────────────────────────────────────┘
```

- The first ministry row is visible above the fold on a 900px-tall window.
- Cards are editorial rows rather than a SaaS icon grid: one photo or typographic panel, no icons, no shadows.
- Missing photo: a Night or Mist typographic panel with the ministry name, so no placeholder image is needed.

### 4.2 `/ministries/<ministry>/` (one template, sections switch off when empty)

```text
A  Hero: name · purpose · [Serve with Psalmists]            photo right (or none)
   Local nav: Overview · What we do · Ways to serve · Ministry life   (anchors; hidden if < 3 sections)
B  Introduction (who / why / whom we serve)                  reading width, editor content
C  What we do                                                image + text or rows (editor blocks, no icon grid)
D  Ways to serve                                             rows: role · one line · commitment · "Training available" · "Needed now"
E  (Phase 2) Current needs                                   only roles marked Needed now; empty state text otherwise
F  Ministry life                                             core Gallery / text in content; absent when empty
G  Final invitation (Mist band)                              "You don't need to know everything…"  [I'm interested] [Ask a question]
```

---

## 5. Mobile layout (390px)

- Title band, then ministry rows stacked: image on top (16:9, capped height), then text. No carousel; long lists are easier to scan stacked.
- Header "Ministries" becomes an expandable item in the existing overlay menu (core Navigation submenu, tap to open).
- Ministry page: the local anchor nav becomes a single horizontally scrollable line or is hidden. CTAs are inline, never sticky.
- Ways to serve: each row stacks title, then line, then badges. No horizontal scrolling (checked as with Sermons).
- `/serve/` filters (Phase 2) reuse the Sermons pattern: search + a "Filters" button.

---

## 6. Navigation

- **Phase 1:** a core Navigation **submenu** under "Ministries": the seven ministries (in record order), then "All ministries" and
  "Find a place to serve" (added in Phase 2). It is keyboard and screen-reader accessible out of the box (core submenu toggle
  button, `aria-expanded`), opens on click as well as hover, and stays editable in the Site Editor.
- **Grouped columns** (Worship & Creative / Care & Service / Family / Church Life) are not native to core Navigation. Recommend
  deferring them until there are more than about 10 ministries. If needed, grouping comes from a `ministry_group` taxonomy and a
  small dropdown panel block, not hard-coded markup.
- The submenu is maintained in the menu; the ministry *pages* are generated. Adding a ministry means adding one menu item, the way
  core menus work. An automatic list could replace it later.

---

## 7. Data and content (Phase 1 and the Phase 2 hooks)

Follows the existing SCF pattern (definitions in `config/scf/`, records in WordPress, display through Query Loops and bindings).

### 7.1 Ministry (post type `ministry`, URL `/ministries/<slug>/`)

| Field | Type | Notes |
|---|---|---|
| Title, content, excerpt, featured image | core | Name; intro / what we do / ministry life as editor blocks in a starter pattern; excerpt = card summary; featured image = hero/card (alt text in Media Library, focal crop via image settings) |
| `ministry_tagline` | text (bindable) | Short purpose statement |
| `ministry_short_name` | text | Menu/card label if different |
| `ministry_contact_label`, `ministry_contact_email`/`_url` | text/url | **Only information explicitly meant to be public** |
| `ministry_invite_heading`, `ministry_invite_text` | text | Final invitation override (defaults provided) |
| Order | core menu order | Display order |
| Visibility | core status | Publish = shown; Draft/Private = hidden. Inactive ministries stay stored |
| `ministry_group` | taxonomy (optional) | Future menu grouping / filters |
| Centre association | (existing plan) | Optional, later |
| SEO | core title/excerpt now; SEO plugin decision later | Readable slugs |

### 7.2 Serve role (post type `serve_role`, URL `/serve/<slug>/`) — model B

| Field | Type | Notes |
|---|---|---|
| Title, excerpt, content | core | Role name · one-line invite · "What you'll do" / "Why this matters" |
| `role_ministry` | post object → ministry (required) | The relationship |
| `role_status` | select | Ongoing · **Needed now** · Paused · Filled · (Hidden = draft) — public views show Ongoing/Needed now |
| `role_need_note`, `role_need_until` | text, date | Only for Needed now |
| `role_good_fit` | textarea/list | "You may be a good fit if…" (interests, character — not a qualification list) |
| `role_experience` | select | No experience needed · Training available · Experience helpful |
| `role_commitment`, `role_schedule` | select + text | Occasional · Rotation · Weekly · Event-based; free text ("1 Sunday a month") |
| `role_requirements` | text | Audition, minimum age, safeguarding/background check, orientation — shown only when present |
| `role_location` | post object → centre (optional) | Campus-specific roles |
| `role_interest` | taxonomy | Music · Children · Hospitality · Technology · Events · Practical service · Community care |
| `role_featured` | true/false | For `/serve/` |

Phase 1 needs only the Ministry type plus **Ways to serve**. With model B, Phase 1 creates simple Serve roles
(title, ministry, one line, status Ongoing) so the ministry pages show real structure. Role detail pages, filters and
"Needed now" come in Phase 2.

### 7.3 Events ministry

No events system exists. The Events ministry page explains the ministry and its roles only. When an events model is
adopted, it gets an "Upcoming events" Query Loop from those records, so events are never copied into the ministry.

### 7.4 Public/internal boundary (Oikonomia)

Ministry and role records hold public content only: no leader notes, schedules, member lists or private contacts. A later
Oikonomia integration receives expressions of interest through an explicit API or webhook. The site never reads internal records
into public pages.

---

## 8. Content and decisions needed from the church

1. **Model:** A (area + opportunity) or **B (role + "needed now")**.
2. **Names:** the legacy form says "**Gifts** and Arrows"; the brief says "Gift and Arrows". Which is correct? (Not renamed.)
3. **Scope:** do Guest Services, Prayer, Creatives, Field Ministry, R.A.D.I.C.A.L youth, Lifegroups, Sunday School and SEED
   become ministries now, later, or not at all (for example Connect/Groups)?
4. **Ministry copy:** the brief's purpose statements and service areas are starting drafts. Publish them as drafts for review, and
   leave out anything unconfirmed (schedules, age groups, requirements) until confirmed.
5. **Photos:** a short shot list per ministry. Until then, typographic panels are used.
6. **Contacts:** a public contact for each ministry, or all to one church address?
7. **Interest form (Phase 2):** needs a free form plugin decision (evaluate core-friendly options against the plugin checklist).
   Until then, the CTA links to Contact with the ministry or role in the subject/query.

---

## 9. What Phase 1 can do without backend changes

Everything in Phase 1 uses what exists (SCF, block theme, core Navigation, Query Loop, bindings). No new plugin is needed:

- SCF `ministry` type + fields (+ minimal `serve_role` if model B), exported to `config/scf/`.
- Templates: `archive`-style `/ministries/` landing (page template + Query Loop), `single-ministry.html`; patterns: ministry row card,
  ministry hero, ways-to-serve list, invitation band, ministry starter content.
- Seven ministry records from the brief's drafts, **status Draft** until the church confirms the copy (viewable locally for design).
- Core Navigation submenu under Ministries.
- Empty states: no roles → "There aren't any specific openings listed right now, but we'd still be glad to hear from you." + Ask about serving.
- Accessibility: heading order, anchor nav, focus states, contrast (theme tokens), alt text, no icon-only meaning.

## 10. Deferred to Serve / Opportunity work (Phase 2+)

- `/serve/` landing, role detail pages, filters (reusing the sermon filter bar), "Needed now" and statuses.
- The expression-of-interest form (plugin decision; role preselected; minimal fields; no application-style form).
- Grouped mega-menu, ministry maintainers' permissions, galleries workflow, campus-specific roles.
- Events integration (needs an events model), SEO plugin, Oikonomia integration.

---

## Acceptance check (Phase 1)

- All seven ministries are discoverable from the menu and the landing page; the first ministry is visible without a full scroll.
- Adding, reordering, hiding or renaming a ministry is done in WordPress, not in code.
- Every ministry page uses one template; empty sections are hidden.
- Each ministry lists its ways to serve; no fake needs or vacancies are shown.
- No internal or private data appears on public pages.
- Desktop and mobile are verified in the browser, with no horizontal scroll.

---

## 11. Phase 1 implementation (2026-09-16, local)

| Piece | Where |
|---|---|
| Content types | `config/scf/post-types/ministry.json`, `serve_role.json`; field groups `ministry-details.json`, `serve-role-details.json` |
| Queries and derived values | `wordpress/plugins/cacdemo-content/includes/ministries.php`: Query Loop flag `cacdemoMinistryRoles` (public roles of the viewed ministry, menu order) and bindings source `cacdemo/ministry` (`roles`, `needed`, `experience`, `serve_label`, `invite_heading`, `invite_text`, `contact_url`) |
| Ministries page | `templates/page-ministries.html` = title + page content (intro) + `patterns/ministry-directory.php` (ruled two-column list: optional photo, name, purpose, up to four ways to serve, "Needed now") + `patterns/ministries-invitation.php` (Mist band, 1 Peter 4:10, "Ask about serving") |
| Ministry page | `templates/single-ministry.html`: hero (Ministries link, name, purpose, "Serve with <name>" → Ways to serve, featured image when set) → introduction (content; hidden when empty) → Ways to serve (rows with one line and badges; empty state + "Ask about serving") → invitation band ("I'm interested" → contact link, "Ask a question") |
| Styles | `assets/css/ministries.css` (loaded with Query Loops); header submenu dropdown and overlay rules in `theme.json` |
| Records and menu | `scripts/seed-ministries.php [dry-run]`: seven ministries (menu order as the brief), 52 serve roles (all Ongoing, one-line descriptions only where the brief wrote them), and the Ministries submenu (All ministries + the seven). Non-destructive; records marked `_cacdemo_seed = ministries-brief-draft` |
| Ministries page intro | `scripts/seed-content.php` (old Radical band, invented Lifegroups/Sunday School/SEED rows and the serving statement removed) |

Verified in the browser at 1440px and 390px: no horizontal scroll; the first ministry is visible on first load; the submenu opens from
its keyboard toggle and shows as an indented list in the mobile overlay; empty introduction and empty Ways to serve behave as designed;
"Needed now" appears only for a role with that status (tested, then reverted).

Not deployed: `deploy-staging.sh` does not run `seed-ministries.php`, so staging has no ministry records until the church confirms the copy. Contact links carry
`?ministry=<slug>` for a future form; the Contact page does not read it yet.

Open for the church: confirm purpose lines and ways to serve; write one-line descriptions; the Productions sentence "Many of these roles
can be learned by serving alongside the team" (draft, from the brief's instruction); photos; public contacts; the §8 scope questions.

