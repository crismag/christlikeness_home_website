# Design direction

**Status:** direction and Phase 4C prototype **approved** (2026-09-14; §16, §17). Production
block theme implementation in progress. Centre-driven views wait for content-model decision
D-1 (INFORMATION-ARCHITECTURE §6).

---

## 1. Design objective

Give Christlikeness an original visual language that combines:

- **CEFC-level maturity and editorial discipline**
- **Renew-style personality, readability and simple expressive moments**
- **Christlikeness's own worship, music and community identity**

It must be buildable as a WordPress block theme that church staff can edit without
making design decisions. We design the system; editors supply text, images, video,
dates, classifications and centre relationships.

Not a clone of any reference, not a generic church template, not a generated-looking
SaaS page.

## 2. Reference hierarchy

| Role | Reference | Learn | Do not take |
|---|---|---|---|
| Primary | **CEFC** — cefc.org.sg (home, /alpha/, /whats-happening/), sermonresources.cefc.org.sg | Maturity, editorial discipline, spacing, hierarchy, authentic photography, restraint, structured resources | Amber header bar and amber headline colour, Lato/Karla pairing, carousels, centred-everything, repeated "READ MORE" buttons, 2019-era sermon card UI |
| Primary | **Renew Church OC** — renewchurchoc.com (home, /new-here) | Simplicity, readability, personality, expressive display type, tone-band section transitions, newcomer UX, storytelling, ministry presentation | Stadium/oval photo crops, curved marquee text band, cream + mauve/sage palette, Poppins/General Sans treatment, Instagram-grid footer |
| Supporting UX | **HTB** — htb.org (home) | Newcomer clarity, "one church, many sites" framing, one unmistakable service/location call to action, very short navigation | Ultra-bold GT Walsheim display, black/cream/periwinkle palette, collage compositions |
| Identity source (not visual) | christlikeness.ca harvest | Flame/dove mark, Christlike Worship and Radical Music identities, authentic worship imagery, church language | Any Wix layout, spacing, type or component |

Not used as visual references: Anchor Church, Village Church, ONE Church.

Research limits: HTB's /sundays page was behind a Cloudflare bot check and was not
studied. References were measured in a browser at 1440 px and 390 px. Screenshots stayed
in scratch space and are not in the repository.

## 3. Reference observations

**R1 — Consistent page grammar (CEFC)**
- **Observation:** ministry pages (e.g. /alpha/) repeat one sequence: full-bleed photo with
  title + one-line purpose + one action → question-led section heading → image/text pair →
  three photos with short captions (no boxes) → single call-to-action image → footer.
  Sections are separated by a change of background tone, not borders.
- **Why it works:** every ministry page feels related, and readers learn the rhythm once.
- **Christlikeness application:** define a small set of section patterns and one sequence
  per page family. Separate sections with tone changes.
- **Do not copy:** CEFC's specific section order, its amber styling and centred alignment.

**R2 — Typography does the hierarchy work (CEFC)**
- **Observation:** headings are heavy (800) at 60 px desktop / 42 px mobile with line-height 1.0,
  and body text is a quiet 16 px. Almost no decoration.
- **Why it works:** large type contrast reads as confident and editorial without ornaments.
- **Christlikeness application:** a strong display-to-body contrast (≈ 4:1 on desktop) and
  tight display leading.
- **Do not copy:** Lato 800 in amber. Also avoid CEFC's weaknesses: 14–15 px text and
  ~140-character lines in full-width paragraphs.

**R3 — Structured media metadata (CEFC sermon resources)**
- **Observation:** every sermon shows venue (centre), speaker, scripture and date as labelled
  fields, with consistent typographic series artwork.
- **Why it works:** browsing by speaker, scripture or centre becomes possible, and the list stays calm
  because artwork is uniform.
- **Christlikeness application:** sermon/resource items present the same fields from
  structured data, where "venue" maps to centre association. One artwork system for
  series, not one-off flyers.
- **Do not copy:** the boxed card UI, ratings/download counts, colours.

**R4 — Service facts in the first screen (Renew)**
- **Observation:** the home hero carries the location, Sunday times and a single "New Here?"
  action beside the headline. Mobile keeps them just below the headline.
- **Why it works:** a visitor's first question is answered without navigation.
- **Christlikeness application:** a home and New Here "visit" block generated from active centre
  records (two centres today), one primary action.
- **Do not copy:** Renew's hero composition with oval photos.

**R5 — Expressive type, used sparingly (Renew)**
- **Observation:** one very large display headline (≈ 92 px, tight negative tracking, one italic
  word) sets the tone; the rest of the page is plain and readable.
- **Why it works:** personality in one place, calm everywhere else.
- **Christlikeness application:** allow one expressive type moment per key page (home hero,
  Music, worship bands) inside an otherwise disciplined system.
- **Do not copy:** Renew's exact italic-word treatment, its typefaces, the curved marquee.

**R6 — Tone bands and plain FAQs (Renew New Here)**
- **Observation:** the page alternates full-width bands (light → sage "visit" band with map,
  times, address, email → light personal note from the pastor → dark FAQ band). FAQ questions
  are large type separated by thin rules, expanding on click.
- **Why it works:** it is scannable, human (a named pastor's note) and needs no cards.
- **Christlikeness application:** New Here = visit band (centre data) + welcome note + FAQ
  (core Details blocks) on a dark band.
- **Do not copy:** Renew's palette and wording.

**R7 — One church, many sites (HTB)**
- **Observation:** navigation has five items. The home states "Ten services, six sites, one
  church" and offers one action, "Find times and locations".
- **Why it works:** multi-site complexity is reduced to one reassuring sentence and one door.
- **Christlikeness application:** frame centres as one church in several places. One
  consistent "Find a centre" action leads to the generated Centres directory. Header stays
  short.
- **Do not copy:** HTB's slogan, typeface and collage style.

**R8 — Authentic, active photography (HTB, CEFC)**
- **Observation:** real congregation moments (worship with raised hands, laughter, meals), shown large.
  Stock-looking images are rare.
- **Why it works:** it signals a real community.
- **Christlikeness application:** use Christlikeness photos only. Where photos are
  inconsistent or low-resolution, unify them with a theme duotone treatment rather than
  hiding them.
- **Do not copy:** their photographs.

## 4. Christlikeness visual principles

1. **Type first, then photography, then whitespace.** Decoration is the last resort.
2. **One expressive moment per page.** Everything around it stays quiet.
3. **Left-aligned reading.** Centre only short statements.
4. **Answer the visitor early.** Where and when to worship is never more than one step away,
   and always comes from centre data.
5. **Sections breathe and change tone** instead of being boxed.
6. **Lists before cards.** Cards only for media items that genuinely have a thumbnail.
7. **Worship energy lives in dark bands and music**, not everywhere.
8. **Mobile is composed, not shrunk.**
9. **Editors never style.** Patterns and templates carry the design.

## 5. Typography direction

All faces are open-licensed (OFL) and self-hosted in the theme through theme.json
`fontFace` (no third-party font CDN).

| Role | Direction A | Direction B |
|---|---|---|
| Display / page titles | Schibsted Grotesk 700–800 | **Bricolage Grotesque** 700–800 (optical-size axis for expressive large sizes) |
| Section headings | Schibsted Grotesk 700 | Schibsted Grotesk 700 |
| Body — long reading (About, resources, sermon notes) | **Source Serif 4** 400, line-height 1.65 | Source Serif 4 400, line-height 1.65 |
| UI: navigation, buttons, metadata, captions | Schibsted Grotesk 500–600 | Schibsted Grotesk 500–600 |

Why these faces:

- **Schibsted Grotesk** is a sturdy editorial grotesque, and echoes the bold grotesque in the
  church's own recent artwork.
- **Source Serif 4** makes teaching and sermon text comfortable to read.
- **Bricolage Grotesque** has warmth and irregularity for worship and music moments without
  becoming a novelty face.

Scale (fluid `clamp()` via theme.json):

| Step | Mobile → desktop |
|---|---|
| Display (hero, one per page) | 44 → 88 px, line-height 1.0, slight negative tracking |
| H1 page title | 36 → 60 px, line-height 1.05 |
| H2 section | 28 → 40 px, line-height 1.15 |
| H3 | 22 → 26 px, line-height 1.25 |
| Body | 17 → 19 px, line-height 1.65 (serif) |
| Lead paragraph | 20 → 24 px |
| Small / metadata | 14 → 15 px, sans |

Rules:

- Reading measure 60–72 characters (content width ≈ 680 px).
- Navigation and buttons in sentence case, not all caps.
- No eyebrow labels above headings.
- No single-word colour or italic accents in headlines.
- Metadata on separate short lines or as labelled pairs ("Scripture: John 13:1–17"), not
  dot-joined strings.

## 6. Layout and spacing direction

| Token | Value |
|---|---|
| Content (reading) width | 680 px |
| Wide width | 1200 px |
| Full-bleed | Photography, tone bands, dark worship bands |
| Side gutter | clamp(20px, 5vw, 64px) |
| Section spacing | clamp(64px, 10vw, 144px) vertical |
| Heading → text | ≈ 0.5 × heading size |
| Image → caption | 12–16 px |
| Grid | 12-column wide container. Common splits: 7/5 image-text, 4/4/4 media, 8/4 content-aside |

- Asymmetric image/text pairs (7/5), alternating sides down a page.
- Spacing scale in theme.json (e.g. 8 steps) so editors never type pixel values.
- Tone bands: paper → tone → paper → dark, at most one dark band per page except Music.

## 7. Photography direction

- Authentic Christlikeness people and worship only; no generic stock for people.
- **Large and few:** one hero image, then 1–3 supporting images per section.
- **Crops:** landscape 3:2 for features, 16:9 for media, 4:5 portrait for people. Focal points
  should keep faces in frame on mobile crops.
- **Overlays:** only a subtle gradient where text sits on a photo, always meeting contrast
  requirements.
- **Consistency tool:** a theme duotone (core Image/Cover duotone filter) for mixed-quality or
  archival photos. Direction A uses it more; B mostly for dark-band images.
- **Current reality:** the harvest provides very few usable photos (one worship stage frame,
  banner collages, logos). A photography collection or shoot is a prerequisite for a
  photo-led design (§16).

## 8. Header and navigation principles

- Flame/dove mark + "Christlikeness" wordmark at left; native Navigation at centre/right; one
  persistent action "Find a centre" at far right (links to the generated Centres directory).
- No addresses or service times in the header.
- Height about 72–88 px desktop, 64 px mobile. Solid background (transparent only over the home
  hero, if at all).
- **Density:** eight menu items (Contact moved to the footer, DD-6) plus Find a centre. Test
  in the prototype whether all fit inline from ≈ 1200 px. Tablet (≈ 600–1199 px) and mobile use
  a full-screen overlay menu with "Find a centre" first. This needs theme CSS because the core
  Navigation overlay breakpoint is fixed at 600 px.
- **Contact** is not in the header menu. It is prominent in the footer and linked contextually.
- **Multi-centre:** one identity. Centres appear through Find a centre, the directory and
  centre views, never as different header styles.

## 9. Footer principles

- **Row 1 — generated centres:** each active centre's name, address, Sunday time and map
  link from centre records (Query Loop). A third centre appears automatically.
- **Row 2:** footer navigation (a separate native Navigation menu) with **Contact** given
  prominence, social icons (core Social Icons block), mark.
- **Row 3:** copyright and legal/privacy link.
- **Mobile:** centres first (the likeliest need), then navigation in two short columns, not an
  endless stacked list (CEFC's mobile footer is a lesson in what to avoid).
- Dark or tone band so the page end is clear. No manually typed addresses in the footer part.

## 10. Component principles

A deliberately small set, built as core blocks, block style variations and locked
patterns.

| Component | Treatment |
|---|---|
| Page intro | Title + optional lead + optional image; left-aligned |
| Visit / centre block | Centre name, Sunday time, address, map link, "Plan your visit". Data from centre records; list layout, not cards |
| Image + text feature | 7/5 asymmetric, alternating, no box |
| Media item (sermon, video, music) | 16:9 image or thumbnail, title, speaker, date, scripture on separate lines. The only place cards are acceptable, and without shadows |
| Directory row (ministries, resources, topics) | Full-width row: title, one-line description, topic; entire row is the link; hairline separators |
| Event row | Large date (day number + month) at left, title, centre, time |
| Scripture / quote | Large serif text, reference beneath in small sans; no decorative quote glyph |
| Call-to-action band | One sentence + one button, on a tone or dark band |
| FAQ | Core Details (or Accordion) blocks; large questions; hairline rules |

- **Buttons:** primary = solid ink, 2 px radius, sentence case. Secondary = underlined text link.
  On dark = light outline. No arrows appended.
- **Avoided on purpose:** carousels/sliders, card grids for non-media content,
  shadows, glassmorphism, gradients as decoration, pills everywhere, icon rows, badges,
  numbered markers on non-sequences, entrance animations on every section.

## 11. Responsive principles

| Aspect | Desktop (≥ 1200) | Tablet (600–1199) | Mobile (< 600) |
|---|---|---|---|
| Navigation | Inline 8 items + Find a centre | Overlay menu | Overlay menu; Find a centre first |
| Display type | Up to 88 px | ≈ 64 px | 44–52 px, left-aligned |
| Image/text | 7/5 split, alternating | 6/6 or stacked for narrow images | Stacked, image first unless the text is the point |
| Media grid | 3 across | 2 across | 1 across, larger thumbnails |
| Directory rows | Title + description on one line | Two lines | Title, then description |
| Visit block | Centres side by side | Side by side if two, else list | Stacked list; map links as full-width tap targets |
| Section spacing | 144 px | 96 px | 64 px |
| Footer | 3 rows, multi-column | 2 columns | Centres, then 2-column nav |

Mobile is designed for thumb reach and scanning: 44 px minimum tap targets, visible focus
states, and `prefers-reduced-motion` respected.

## 12. Page families

| Family | Pages | Composition | WordPress |
|---|---|---|---|
| A. Standard content | About, Contact, parts of New Here | Page intro → reading-width content → optional feature/scripture → CTA band | `page.html` + a few locked patterns |
| B. Landing / directory | Centres, Ministries, Connect, Resources | Intro → generated directory (rows or centre blocks) → CTA band | Page + Query Loop patterns; archive templates for types |
| C. Media / archive | Sermons, Resources archive, Music | Intro → featured item → media grid/rows → filters by series/speaker/topic (taxonomy links) | Archive/taxonomy templates + Query Loop |
| D. Homepage | Home | Hero (one expressive moment) → visit block (centres) → latest sermon → ministries glimpse → worship/music band → next steps → footer | `front-page.html` of patterns; content from queries |
| E. Detail / article | Centre, Resource, Sermon, Ministry | Title + key facts first (times/address, or speaker/scripture/date) → media → body at reading width → related items | `single-{type}.html` templates, block bindings for fields |

## 13. Direction A — "Editorial Sanctuary"

Restrained, editorial, photography-led.

- **Mood:** calm, mature, trustworthy; a well-edited publication about a living church.
- **Palette:**

  | Name | Hex | Use |
  |---|---|---|
  | Paper | `#FAFAF7` | Page background |
  | Ink | `#1E2522` | Text, primary buttons |
  | Stone | `#5F6763` | Secondary text |
  | Mist | `#E8EAE5` | Tone bands |
  | Evergreen | `#1F3A30` | The one dark band |
  | Leaf | `#2F6B4F` | Links, focus |

  Green is drawn from the ivy in the church's own "Come as you are" artwork: growth and life.

  Measured WCAG contrast: Ink on Paper 14.95 · Stone on Paper 5.57 · Stone on Mist 4.81 ·
  Leaf on Paper 6.02 · Paper on Evergreen 11.77 — all pass AA.
- **Typography:** Schibsted Grotesk headings and UI; Source Serif 4 body. No expressive display face.
- **Spacing:** generous; long, quiet pages.
- **Photography:** leads every landing page; full-bleed heroes and 7/5 features; duotone for consistency.
- **Header:** paper background, mark + wordmark, inline nav, Find a centre as a text button.
- **Section composition:** paper/mist alternation; one evergreen band per page.
- **Cards and lists:** rows with hairlines; media cards only for sermons.
- **CTA treatment:** ink button on paper, or light outline on evergreen.
- **Footer:** evergreen with generated centres.
- **Desktop:** wide photo compositions, 7/5 splits.
- **Tablet:** 6/6 splits, overlay nav.
- **Mobile:** stacked, photos first.
- **Strengths:** timeless; ages well; very readable for teachings and resources; least risk of looking trendy.
- **Risks:** depends on strong photography, which Christlikeness does not have yet. Can feel
  reserved for a worship/youth/music church, and could drift toward "institution".
- **Fit:** strong for About, Resources and Centres; weak for Music and youth energy.

## 14. Direction B — "Living Worship"

Direction A's editorial foundation plus deliberate worship and music energy.

- **Mood:** warm, confident and alive: an editorial base with moments that feel like a worship night.
- **Palette:** A's Paper, Ink, Stone and Mist, plus:

  | Name | Hex | Use |
  |---|---|---|
  | Night | `#161B2E` | Dark worship bands, footer; from the church's stage-lighting photography |
  | Stage | `#4A57C8` | Links and focus on Paper/Mist |
  | Stage Light | `#9AA4F5` | Links and focus on Night |
  | Ember | `#F0B23B` | Rare highlight **on Night only**, at most one per page: live/now indicators, the flame mark |

  No gradients; colour comes from photography and bands.

  Measured WCAG contrast:

  | Pair | Ratio | Result |
  |---|---|---|
  | Ink on Paper | 14.95 | Pass |
  | Stone on Paper | 5.57 | AA |
  | Stone on Mist | 4.81 | AA |
  | Stage on Paper | 5.76 | AA |
  | Paper on Night | 16.32 | Pass |
  | Stage Light on Night | 7.32 | AA |
  | Ember on Night | 9.05 | AA |
  | Stage on Night | 2.84 | Fails — do not use |
  | Ember on Paper | 1.80 | Fails — do not use |
- **Typography:** Bricolage Grotesque for the one display moment per page (hero, Music,
  worship band statements) at large sizes with tight leading; Schibsted Grotesk and
  Source Serif 4 for everything else, identical to A.
- **Expressive techniques, simple and cheap:**
  - (1) Oversized display statements on dark bands, e.g. a Christlike Worship lyric or scripture line.
  - (2) An arch crop (rounded top, flat base) for a small number of portrait images, echoing
    the flame mark and sanctuary windows. Used on Home, Music and ministry heroes only.
  - (3) One optional hero reveal on page load, disabled for reduced motion.
- **Spacing:** as A, slightly tighter inside dark bands so type feels bigger.
- **Photography:** worship and community moments; dark bands tolerate lower-quality or
  low-light photos (duotone to Night) — a practical fit for the current photo supply.
- **Header:** paper by default; over the home hero it may sit on Night. Find a centre as a solid button.
- **Section composition:** paper/mist for information; Night bands for worship, music and
  calls to action; at most two Night bands per page (Music excepted).
- **Cards and lists:** as A; music releases as media items with square artwork.
- **CTA treatment:** ink on paper; paper button on Night; Ember never used for buttons.
- **Footer:** Night, generated centres, social icons, both music project links.
- **Desktop:** hero with display type beside or over an arch-cropped worship image; visit
  block directly under.
- **Tablet:** display 64 px, arch images reduced to one.
- **Mobile:**
  - Hero is display type first, then image.
  - Visit block (centres) is immediately after the hero.
  - Dark bands keep large type at 44–52 px.
- **Strengths:**
  - Expresses worship, music and youth identity.
  - Works with limited photography.
  - Distinct from both references.
  - Keeps A's readability for resources.
- **Risks:**
  - The expressive layer can creep. It needs the rule "one display moment per page".
  - A second display face and dark bands add some implementation and contrast testing.
  - Bricolage must be kept to large sizes.
- **Fit:** strongest overall match for a worship-centred church with Christlike Worship and
  Radical Music.

## 15. Recommendation

**Direction B, "Living Worship", built on Direction A's foundation.** Implement A's
system first (tokens, type scale, spacing, templates, directory/visit/media patterns),
then add B's expressive layer as defined block styles and patterns: Night band, display
statement, arch image, optional hero reveal. This keeps the maturity lesson from CEFC and the
readability/personality lesson from Renew, suits a worship/music church, and tolerates the
current shortage of strong photography.

## 16. Decisions

Recorded 2026-09-14.

| # | Decision | Status | Conditions |
|---|---|---|---|
| DD-1 | Direction | **Approved: B "Living Worship" on A's foundation** | Keep the expressive layer sparse. **One expressive moment per page** is a governing constraint |
| DD-2 | Typefaces: Bricolage Grotesque (display), Schibsted Grotesk (headings/UI), Source Serif 4 (reading) | **Provisional** | The prototype must show all three together. If Bricolage and Schibsted compete, reduce to two faces |
| DD-3 | Palette: Paper, Ink, Stone, Mist, Night, Stage, Stage Light, Ember | **Provisional, for prototyping only** | Not frozen into the design system or CLAUDE.md. Judge Night/blue/gold (and evergreen as an alternative) against real content and imagery |
| DD-4 | Logo | **Do not block** | Prototype uses the existing harvested mark (`images/branding/logo-mark-white-transparent__a3b8d0f9.png`). **Production requirement:** vector flame/dove mark and wordmark from the church |
| DD-5 | Photography | **Start collecting now** | Design progress does not wait for the final library; the prototype uses available authentic images and clearly marked placeholders |
| DD-6 | Header density | **Approved: Contact leaves primary navigation** | Header: 8 destinations + Find a centre (more prominent than Contact). Contact prominent in the footer and contextually (New Here, centres, Connect). The live menu changes when the footer carrying Contact exists |
| DD-7 | Hero reveal animation | **Experimental** | Must earn its place in the prototype; if the static version is strong enough, remove it. `prefers-reduced-motion` support is mandatory |
| DD-8 | Visual prototype before theme work | **Approved — next phase** | Homepage + one directory page; disposable preview, not theme code |
| DD-9 | Prototype outcomes (§17) | **Approved 2026-09-14** | Two typefaces (Schibsted Grotesk + Source Serif 4; Bricolage dropped). No reveal animation. Arch kept only as a dark-band accent (one per page). Night kept; Evergreen and Ember dropped. Reading width 38rem; inline navigation from 75rem. Missing images omitted, not placeholdered |

## 17. Prototype findings (Phase 4C, 2026-09-14)

**Prototype:** `design/prototype/` (static HTML/CSS + one small script, no dependencies).
Homepage and Centres directory, reviewed in a browser at 1440, 1200, 1024, 768, 430 and
390 px. Screenshots are in `design/prototype/screenshots/`. How to run it:
`design/prototype/README.md`.

### What worked

- **Editorial foundation.** Paper / Mist / Night tone bands separate sections without
  borders or boxes, and the dark band makes the light sections feel more spacious.
- **Lists before cards (validated).**
  - The visit strip, the Centres directory rows and the "Next steps" rows all read clearly
    with typography, rules and whitespace alone.
  - No content needed a card.
- **One centre source.** One data list rendered the home visit strip, the Centres
  directory and the footer — the canonical-centre model is visually workable.
  Unconfirmed data (Scarborough unit) displays as "pending confirmation" without choosing a
  value.
- **Identity through the church's own artwork.** The flame/dove photo-collage in the hero is
  more recognisably Christlikeness than any photograph.
- **Night palette with real imagery.** The church's violet stage lighting harmonises with Night;
  the band feels worshipful, not nightclub-like.
- **Navigation.**
  - **Desktop (1200 px and up):** eight destinations plus "Find a centre" fit inline with no wrapping.
  - **Below 1200 px:** a full-screen Night menu with Find a centre first and Contact at the foot.
    Keyboard focus moves into it; Escape closes it.
- **Mobile composition.**
  - The flame artwork shrinks to an accent beside the headline.
  - The Sunday centres list starts within the first screen (≈ 460 px).
  - Centre rows stack name → service → address → actions.
  - Footer order: centres, Contact, compact two-column navigation.
- **Accessibility checks passed.**
  - No horizontal overflow at any width.
  - All interactive targets are at least 44 px.
  - Visible focus rings (Stage / Stage Light).
  - Heading order is h1 → h2 → h3.
  - Pending facts use text, not colour.
  - Every colour pair in use passes WCAG AA; lowest is Stone on Mist at 4.81.

### What did not work, and what changed

| Tested | Result | Change |
|---|---|---|
| Duotone (Night) on the worship photo | Muddy and low-energy; exposed the photo's low light | **Rejected as the default.** Use the natural-colour photo. Duotone stays only as a tool for images that clash with the palette |
| Worship photo in the hero arch and again in the dark band | Obvious repetition; exposes how few photos exist | Hero uses the flame photo-collage; the arch moved to the dark band |
| Reading width 680 px | 75–80 characters per line at desktop and tablet | **Reading width 608 px (38rem)**: about 62 characters per line |
| Centre names at page-title size | Hierarchy inverted | Centre names at H2 size |
| Photo slots with "photo needed" placeholders on Centres | Large dead zones dominate the page | **Templates omit missing images** rather than showing placeholders; text-first rows by default |
| Large hero artwork on mobile | Pushed visit information below the first screen | Artwork becomes a small accent on phones |
| Evergreen as the dark colour | Clashes with the violet stage photography | **Rejected**; Night kept |
| Ember gold | Not needed anywhere on either page | Unvalidated; drop unless a real need appears |

### Decision evidence

- **Three fonts (DD-2): did not survive.**
  - At display size, Bricolage Grotesque and Schibsted Grotesk 800 look like near-identical bold
    grotesques. They don't clash, but the difference is too small to justify a third family and
    font download.
  - Scale, weight and tight tracking already create the expressive moment.
  - **Recommend two families:** Schibsted Grotesk (display, headings, UI) + Source Serif 4 (reading).
- **Arch: survived as an accent.** One arch per page, on a worship/people image inside a dark
  band. It reads as sanctuary window / flame rather than a borrowed oval. Not for heroes or
  every image.
- **Palette (DD-3): survived with edits.**
  - Kept: Paper `#FAFAF7`, Ink `#1E2522`, Stone `#5F6763`, Mist `#E8EAE5`, Night `#161B2E`,
    Stage `#4A57C8` (links on light), Stage Light `#9AA4F5` (links on dark).
  - Evergreen rejected; Ember unvalidated.
  - Added supporting tones: Rule `#D5D8D2`, Night Rule `#2C3350`, Night Muted `#B9BED0`.
- **Reveal animation (DD-7): did not earn its place.**
  - The static hero is equally strong.
  - The reveal delays the headline and visit information by ~0.7 s and adds theme code.
  - **Recommend no animation.** The reduced-motion override was implemented and verified present,
    in case it is ever reintroduced.
- **Navigation breakpoint:** inline at **≥ 75rem (1200 px)**, overlay below. Core Navigation's
  fixed 600 px overlay needs a theme CSS override to match.

### Candidate production tokens

| Group | Candidate values |
|---|---|
| Families | Schibsted Grotesk (sans: display/headings/UI) · Source Serif 4 (serif: body/reading) — self-hosted |
| Display (hero, one per page) | clamp(2.75rem → 5.5rem), weight 800, line-height 0.98, tracking −0.03em |
| H1 | clamp(2.25rem → 3.75rem), 700, lh 1.05, −0.015em |
| H2 | clamp(1.75rem → 2.5rem), 700, lh 1.15, −0.01em |
| H3 | clamp(1.3125rem → 1.625rem), 700, lh 1.25 |
| Lead | clamp(1.25rem → 1.5rem), serif, lh 1.4–1.45 |
| Body | clamp(1.0625rem → 1.1875rem), serif, lh 1.65 |
| Small / meta | 0.9375rem sans, lh 1.4, Stone (Night Muted on dark) |
| Navigation / buttons | 1rem sans, 500 (nav) / 600 (buttons), sentence case |
| Colours | Paper, Ink, Stone, Mist, Rule, Night, Night Rule, Night Muted, Stage, Stage Light |
| Spacing scale | 0.25 · 0.5 · 0.75 · 1 · 1.5 · 2.25 · 3.5 rem; section clamp(4rem → 9rem); gutter clamp(1.25rem, 5vw, 4rem) |
| Widths | reading 38rem (608 px) · wide 75rem (1200 px) · full-bleed bands |
| Borders | 1px Rule separators; 2px Ink rule above key lists; no shadows |
| Radii | buttons 2px; images 0; arch = `50% 50% 0 0 / 40% 40% 0 0` on 4:5 images (accent only) |
| Motion | none (reveal not adopted) |
| Breakpoints | 48rem (768) layout splits; 62rem (992) asymmetric grids; 75rem (1200) inline navigation |
| Touch / focus | min 2.75rem (44 px) targets; 3px focus outline, 3px offset |

### WordPress translation (not implemented)

| Prototype piece | Block theme mechanism |
|---|---|
| Tokens (colours, fonts, sizes, spacing, widths) | `theme.json` settings: palette, `fontFamilies` with local `fontFace`, fluid `fontSizes`, `spacingSizes`, `layout.contentSize` 38rem / `wideSize` 75rem |
| Element and core block styling (headings, links, buttons, quote, lists) | `theme.json` `styles.elements` / `styles.blocks` |
| Paper / Mist / Night bands | Group block **section style variations** (theme.json block style variations) |
| Button primary / on-dark; text link | Button block style variations + link element styles |
| Arch image | Image block style variation (CSS border-radius), used in one pattern |
| Header (logo, wordmark, nav, Find a centre) | `parts/header.html`: Site Logo, Site Title, Navigation (overlay CSS at 75rem), Buttons |
| Footer (centres, contact, nav, social) | `parts/footer.html`: Query Loop of active centres + block bindings, Buttons, footer Navigation menu, Social Icons |
| Hero, welcome, worship band, next steps, Centres call to action | Locked block **patterns** (`patterns/*.php`, `templateLock: contentOnly`) so editors change text and images only |
| Visit strip, Centres directory rows | Query Loop over the centre type (after D-1) with Post Title + post-meta **block bindings** for service/address; missing image = omit |
| Homepage | `templates/front-page.html` composed of patterns |
| Centres page | `templates/page-centres.html` or archive template for the centre type |
| Menu overlay | Core Navigation overlay, styled; "Find a centre" as a Buttons block inside the overlay |
| Comparison toolbar, notice bar, `prototype.js` flags | Prototype only — not carried over |

