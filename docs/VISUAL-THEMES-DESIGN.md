# Seasonal themes, color palettes and imagery — audit and proposed architecture

**Status (2026-09-16):** audit and proposal; decisions 1–3 and 5 in §15 answered, occasions (4) still open. No code has changed.

---

## 1. Audit — what exists today

### 1.1 Theme and styling architecture

| Aspect | Finding (evidence) |
|---|---|
| Stack | WordPress 7.1 **block theme** `cacdemo` (templates, parts, patterns, `theme.json`), no JS framework, no build step. Presentation JS: `assets/js/view-switch.js`, block view modules |
| Tokens | `theme.json` presets: **10 colors** `paper #FAFAF7`, `ink #1E2522`, `stone #5F6763`, `mist #E8EAE5`, `rule #D5D8D2`, `night #161B2E`, `night-rule #2C3350`, `night-muted #B9BED0`, `stage #4A57C8`, `stage-light #9AA4F5`; 2 font families (Schibsted Grotesk, Source Serif 4, self-hosted); 7 fluid font sizes; spacing `10`–`70` + `section`. Custom colors, gradients and duotone are **disabled** for editors |
| Token consumption | Components use core preset variables `--wp--preset--color--*` (~160 uses across `theme.json` CSS, `assets/css/*.css`, patterns/templates attributes). Hard-coded hex only in `manage.css` notices (success/error). **5 published pages** store color-bearing block attributes (`backgroundColor`, `is-style-section-mist/night`) |
| Section styles | `styles/sections/section-mist.json`, `section-night.json` (tone bands); block styles `image-arch`, `button-text-link`, `group-rows` |
| Site-wide style variations (`styles/*.json`) | **None** |
| Existing theme/appearance settings | **None** (no options, theme mods, Customizer or Site Editor global-style overrides in use) |
| Motion | None, and a global `prefers-reduced-motion` reset exists |
| Approved design rules (`docs/DESIGN-SYSTEM.md`) that this brief changes | “**no motion**”; “**missing images are omitted, not placeholdered**”; “**no stock photography**, the church’s own artwork and photos carry identity”; “one expressive moment per page”; “arch image only as a dark-band accent”; “editors use presets, never custom colours” |

### 1.2 Image and media architecture

| Aspect | Finding |
|---|---|
| Storage | Core Media Library only (Rule 14: curated site images; YouTube for video). No parallel store |
| Attachments | 292: **287 sermon cover stills** (JPEG, named `YYYYMMDD_Title`), 5 seed images (flame collage, worship frame, pastors, two logos) |
| Featured images | Supported on page, post, sermon, ministry, centre. **Set on 0 pages, 0 ministries, 0 posts, 0 centres**; set on all 287 sermons |
| Legacy photography available | `content-source/legacy-site/images`: **14 files** (branding 4, events 2, music 2, outreach 2, resources 2, people 1, worship 1) |
| Image sizes | Core defaults only: 150c, 300, 768, 1024, 1536, 2048; big-image threshold 2560. No theme sizes |
| Formats | Local GD supports **WebP and AVIF** output; not enabled. Staging support unverified |
| Rendering | Core Image / Featured Image / Cover blocks with `srcset`; sermon cards via `inc/sermon-card.php` with a typographic Night fallback when no cover |
| Hero today | `patterns/hero.php`: headline + flame photo-collage mark (contained image, not a photographic banner) |

### 1.3 WordPress integration boundary

Everything is in-process WordPress: theme `cacdemo` (presentation) and plugin `cacdemo-content` (domain: sermons, ministries,
publishing, channels, Content Manager), SCF for types/fields (`config/scf/`). Core and SCF verify against checksums; no core
changes; public hooks only (`docs/CONTRIBUTOR-PUBLISHING.md` §1, closure 2026-09-16). This work fits the same boundary: nothing
here needs core changes.

### 1.4 Public page and template structure

| Page type | Template | Visual weight today |
|---|---|---|
| Home | `front-page.html` → page content from `seed-content.php`: hero (flame collage) → Sunday services → “Our passion” (Mist) → worship band (Night, arch photo) → Stay connected (Mist) → Next steps | Text-led; one photo (arch) |
| Interior pages (About, New Here, Centres, Connect, Music, Contact, Follow Us) | `page.html`, `page-centres.html`, `page-follow-us.html` | Title + intro + bands; almost no imagery |
| Sermons | `page-sermons.html`, `single-sermon.html`, sermon taxonomies | Richest imagery (covers) |
| Ministries | `page-ministries.html`, `single-ministry.html` | Typographic; `post-featured-image` present but empty |
| Posts (updates/news) | `single.html` | Featured image slot, empty |
| Content Manager `/manage/` | own layout (`manage.css`) | Utility; must stay free of seasonal decoration |

Not present: Events, stories/testimonies, church history — so homepage sections for “upcoming events” and “stories” have no data yet.

### 1.5 Content Manager and images

`/manage/` forms already have one image input (JPG/PNG/WebP upload + alt text + remove) stored as the **featured image**: sermon
“Cover image”, update “Image”, ministry “Photo”. Pages, ways to serve and channels have none. There is no banner field, no picker
for existing library images, no focal point.

### 1.6 Caching

No page cache or object cache locally or on staging (LiteSpeed removed). Anything that varies per request is safe today; §9 covers
what changes if a page cache is added later.

---

## 2. Native / plugin / custom (project rule 3)

**Native WordPress:** `theme.json` palettes and section styles (have); **style variations** (`styles/*.json`) switch colors and
typography site-wide from the Site Editor — but one active at a time, admin-only, no scheduling, no imagery/effects, and not
visitor-selectable; Featured Image, Cover block (focal point per block), responsive `srcset`, `image_editor_output_format` for WebP,
attachment taxonomies for grouping.

**Free plugins:** dark-mode / accessibility toolbars (WP Dark Mode, One Click Accessibility) change colors with their own UI and
scripts; seasonal-effect plugins (snowfall scripts) are unscoped, heavy and ignore reduced motion; media-folder plugins (FileBird
free, Real Media Library lite) add parallel folder models. None provides curated packages + scheduling + theme-aware images, and
each brings lock-in or visual noise.

**Recommended:** a small, curated system in the existing theme and plugin, built on core presets, core media and SCF — no new
plugin. Reason and structure below; trade-off: we own a resolver, an admin screen and tests.

---

## 3. Semantic token contract (Phase 1)

Keep the **existing preset slugs** as the token contract — renaming them would break stored block attributes and every pattern —
and define their semantic roles explicitly:

| Preset (unchanged slug) | Semantic role |
|---|---|
| `paper` | page background |
| `ink` | primary text; primary action background |
| `stone` | muted text |
| `mist` | soft surface (Mist bands) |
| `rule` | border / divider |
| `night` | inverse surface (Night bands, footer) |
| `night-rule` | inverse border |
| `night-muted` | inverse muted text |
| `stage` | accent: links, focus, secondary actions |
| `stage-light` | accent on inverse surfaces |

Add a few **new semantic tokens** under `settings.custom` (generated as `--wp--custom--*`), for things that have no preset today:
`hero-overlay` (gradient for text over photos), `highlight` (celebration/gold accents), `decor` (decorative marks), `success`,
`danger` (replace hard-coded hexes in `manage.css`). Components keep consuming variables only.

---

## 4. Composition and precedence

```text
<html data-site-theme="fall" data-palette="default" data-effects="subtle">
  1. Base design            :root                                   core theme.json variables (paper, ink, …)
  2. Active site theme      :root[data-site-theme="fall"]           atmosphere tokens only (hero-overlay, highlight, decor, textures)
                            + recommends a palette (e.g. fall → "chocolate")
  3. Effective palette      :root[data-palette="chocolate"]         the 10 color presets (+ highlight) for that palette
       effective = visitor choice (if valid) → site theme's recommended palette → "default"
  4. Content imagery        page/ministry/post banner or featured image
  5. Theme imagery          site theme slot image, only where the content has none (explicit fallback chain, §7)
```

- **No combinations**: a palette is one file of variable values; a site theme is one package. Fall + Blue is simply
  `data-site-theme="fall" data-palette="blue"`.
- **Explicit resolution, not specificity wars**: palettes and site themes set **different variables** (palettes: colors; themes:
  atmosphere tokens + imagery + effects), so they never compete. The one overlap — a theme's recommended colors — is expressed as a
  palette choice, not as competing CSS.
- **Graceful failure**: unknown theme → `default`; unknown/removed palette in storage → the theme's recommended palette → `default`;
  missing asset → next fallback; effects script failure → nothing moves. Rendering never depends on JS.

---

## 5. Where things live

| Concern | Location | Why |
|---|---|---|
| Palette definitions | theme `palettes/<slug>.json` (values for the 10 presets + `highlight`), compiled by PHP into one small CSS block | Presentation, curated, versioned, reviewable; contrast-testable |
| Site theme packages | theme `site-themes/<slug>/`: `package.php` (manifest) + optional `style.css` (scoped to `[data-site-theme]`), `effects.js`, small decorative SVG/WebP assets | Curated code, not database CSS; adding a theme = adding a folder |
| Theme resolver | theme `inc/site-theme.php` (active theme, schedule, preview, effective defaults, `<html>` attributes, conditional asset loading) | Presentation state belongs to the theme (Rule 4) |
| Administrator state | one option `cacdemo_site_theme` (base theme, schedule entries, effect intensity, image slot → attachment IDs per theme) | Small, exportable, independent of packages |
| Admin screen | **Appearance → Site Theme** (theme, `edit_theme_options`) | Where WordPress puts appearance; not in the Content Manager (contributors do not change site themes) |
| Image resolver + banner fields | plugin `cacdemo-content` (`includes/media.php`) with a filter the theme uses to add theme-slot fallbacks | Knows content relationships (ministry of an update, page type); theme contributes imagery through the filter |
| Hero rendering | plugin block `cacdemo/page-hero` (server-rendered like `sermon-media`), styled by theme | Resolves image + overlay + focal position per request; templates place it |
| Media categories | SCF taxonomy `media_collection` on attachments (`config/scf/`) | Core Media Library stays the only library |

### 5.1 Package manifest (example)

```php
return array(
	'id'        => 'fall',
	'name'      => 'Fall',
	'type'      => 'natural-season',          // natural-season | church-occasion | biblical-observance | program
	'palette'   => 'chocolate',               // recommended; visitors may still choose another
	'tokens'    => array( 'hero-overlay' => 'linear-gradient(…)', 'highlight' => '#B8863B', 'decor' => '#9A5B2E' ),
	'slots'     => array( 'home-hero', 'page-hero', 'ministries-hero', 'fallback' ),  // images the admin may assign
	'assets'    => array( 'texture' => 'assets/paper-grain.webp', 'mark' => 'assets/leaf.svg' ),
	'effects'   => array( 'leaves' => array( 'subtle', 'enhanced' ) ),
	'dates'     => array( 'suggested' => array( '09-22', '12-20' ) ),   // hint only; the admin schedule decides
	'content_rules' => 'Natural season only. No Christmas imagery or symbolism.',
);
```

`default` is a package too (no overrides). Biblical-observance packages are **not created** until the church names its observances
and their scriptural visual direction (§15).

---

## 6. Administration, scheduling and preview

**Appearance → Site Theme** (one screen):

- Cards for available packages (name, type, recommended palette, preview link).
- **Everyday theme**: the base (Default, or a natural season).
- **Scheduled themes**: list of entries `theme · starts · ends · effects (Off/Subtle/Enhanced)`; add/remove. Example: Anniversary
  Oct 12 00:00 → Oct 19 23:59.
- **Images for this theme**: one row per slot (Home hero, Page hero, Ministries hero, Fallback) with “Choose from library”.
- **Effects**: Off / Subtle / Enhanced (only for packages that declare effects).

**Resolution at request time** (site timezone): the first schedule entry whose window contains “now” wins (occasions are listed
before seasons); otherwise the everyday theme. No cron is needed, so a theme **cannot be left on by mistake** and returns to the
previous presentation exactly when its window closes.

**Preview**: `?site_theme_preview=<slug>&palette_preview=<slug>` honored only for users with `edit_theme_options` (sent
`noindex`, `no-cache`), plus a local constant `CACDEMO_SITE_THEME_FORCE` for development and tests — no waiting for Fall to test Fall.

---

## 7. Visitor palette

- **Selector**: small “Colours” control (radio swatches with names) — proposed in the footer and the mobile menu, not primary
  navigation (§15 asks where).
- **Persistence**: `localStorage["cacdemo:palette"]`; no cookie, no account, no server variation (cache-friendly).
- **No flash**: a < 1 KB inline script at the top of `<head>` reads the value, checks it against the allowlist the server prints, and
  sets `data-palette` before first paint. Invalid or removed values fall back silently.
- **Without JavaScript**: the site shows the effective default; the selector is not rendered.
- **Scope**: public pages only; `/manage/` and wp-admin keep the default palette.

### 7.1 Initial palettes and contrast gate

| Palette | Direction | Night (inverse) surface | Accent |
|---|---|---|---|
| Site Default | current Paper/Ink/Night/Stage | Night | Stage blue |
| Rich Chocolate | warm cream paper, espresso ink | deep cocoa | muted bronze |
| Celebrated Blue | ivory paper, deep blue-black ink | strong deep blue | soft blue on inverse, restrained warm accent |
| Graphite | off-white paper, charcoal ink | graphite | steel blue-grey (not pure grey, to keep links recognisable) |
| Rich Gold | warm ivory paper, charcoal ink | charcoal | antique gold **as accent only** (links use a darker bronze for contrast) |
| Forest / Sage | natural cream paper, forest ink | deep forest | sage on inverse, earthy accent |
| Deep Navy | warm white paper, navy ink | deep navy | slate blue |

Values are set in Phase 2. **Gate** (added to `scripts/test.sh`): for every palette compute WCAG ratios for text/paper, muted/paper,
accent(link)/paper, text/mist, paper/ink (primary buttons), inverse text/night, inverse muted/night, accent-on-inverse/night, focus
ring/background — **≥ 4.5:1 for text, ≥ 3:1 for large text, focus and UI boundaries**. A palette that fails does not ship.

---

## 8. Image system

### 8.1 Hierarchy → where each level comes from

| Level | Used on | Source |
|---|---|---|
| 1 Hero / page banner | Home, About, Ministries, Sermons, New Here, major programs (future Events) | `page-hero` block: content **Banner** → theme slot |
| 2 Section / feature | ministry list and cards, featured sermon, CTA bands, updates | Featured image → fallback chain |
| 3 Content | posts, sermons, ministry introductions, pages | core Image/Gallery blocks in content |
| 4 Decorative | textures, marks, dividers, effects | theme package assets (small SVG/WebP in the repo), never content |

Page restraint: full hero only on landing pages; ministry pages a moderate hero; posts an editorial featured image; Contact and
utility pages no hero; `/manage/` none.

### 8.2 Content fields (SCF, `config/scf/`)

Field group **Page banner** on page, ministry, post (and future event): `banner_image` (image, ID), `banner_position`
(focal preset: centre / top / bottom / left / right, applied as `object-position`, separately for mobile if set),
`banner_text_side` (left / centre) — no overlay controls: the theme's `hero-overlay` token handles readability.
Ministry adds `ministry_fallback_image` (used by its updates and ways to serve when they have no image).

### 8.3 Resolver and fallback chain

`cacdemo_resolve_image( $slot, $post )` returns an attachment ID or none:

```text
hero slot : content banner → content featured image → parent context (update/role → its ministry fallback)
            → active site theme slot image → Default theme slot image → none (hero renders as a typographic band)
card slot : featured image → parent context → active theme "fallback" slot → Default "fallback" → typographic fallback (as sermons today)
```

Theme-specific images are optional overrides of the **generic** slots only; a page's own banner always wins. The Default theme's
slots are filled with real church photography chosen by an administrator, **varied per page type** (not one photo everywhere).

### 8.4 Rendering and performance

- Heroes are `<img>` with `srcset`/`sizes` (not CSS background images, which cannot be responsive), `object-fit: cover`, a fixed
  `aspect-ratio` per breakpoint (no layout shift), `fetchpriority="high"` and no lazy loading **only** for the first hero; everything
  else `loading="lazy"`.
- Sizes: core sizes already cover 768–2560 widths; add one crop-free size `cacdemo-hero-sm` (1200 px) only if testing shows a gap.
- **WebP** generated for new uploads via `image_editor_output_format` (JPEG/PNG → WebP subsizes); originals kept. AVIF later, after
  staging support is verified. Existing 287 stills are unaffected (no regeneration required).
- Text over photos: always the `hero-overlay` gradient on the text side, text in `paper`, minimum 4.5:1 checked against the overlay's
  darkest stop in the contrast gate.

### 8.5 Approved image library

- SCF taxonomy `media_collection` on attachments: Church, Worship, Community, Ministries, Events, Facilities, Historical, Spring,
  Summer, Fall, Winter, Anniversary, Biblical observances, Backgrounds, Textures.
- “Approved” = in at least one collection. The Content Manager picker shows approved images (filter by collection) plus “Upload new”
  (new uploads are unapproved until a Content Admin adds them to a collection — or approve-on-upload, §15).
- Media Library in wp-admin gets a collection filter; no second media system.

### 8.6 Content Manager integration

Add to the forms that exist: **Banner image** (with position) on Pages and Ministries (and News), “Choose from library” next to
the current upload input for Cover / Image / Photo / Banner, and alt text required unless marked decorative. Contributors never see
site-theme controls.

---

## 9. Performance and accessibility risks

| Risk | Mitigation |
|---|---|
| **Too little authentic photography** (14 legacy photos, sermon stills are mostly preaching) — the biggest risk to “lively” | Photography plan and shot list per ministry/page before Phase 3 content; generated/abstract artwork only for decoration (§15) |
| Large hero LCP on mobile | `srcset`/`sizes`, WebP, single high-priority image, 2560 px source cap |
| Flash of wrong palette | inline head script before CSS paint |
| Palette contrast regressions | automated contrast gate per palette + visual check at three widths |
| Text over busy photos | overlay token + focal presets + mobile position; never text on an unoverlaid photo |
| Effects cost, distraction, vestibular issues | CSS transform/opacity only, ≤ ~12 sprites, `pointer-events: none`, `aria-hidden`, paused when the tab is hidden, off for `prefers-reduced-motion` and Save-Data, **Off by default** on phones below a width, Off/Subtle/Enhanced |
| Future page cache vs scheduled themes | attributes are server-rendered; if a cache is added, cap HTML TTL to the next schedule boundary or purge on boundary |
| Site theme hiding the church’s identity | packages may not change typography scale, layout or components; one decorative mark per viewport; content review checklist (incl. Winter ≠ Christmas) |
| Design-system drift | the approved rules in `DESIGN-SYSTEM.md` are updated deliberately (§15), not bypassed |

---

## 10. Likely files affected

- **Theme:** `theme.json` (custom tokens), new `inc/site-theme.php`, `inc/palettes.php`, `palettes/*.json`, `site-themes/*/`,
  `functions.php` (enqueue/wiring), `parts/header.html`/`footer.html` (palette control), `templates/front-page.html`, `page.html`,
  `page-ministries.html`, `single-ministry.html`, `single.html`, `page-sermons.html` (hero placement), new `assets/css/hero.css`,
  `assets/js/palette.js`, `assets/js/effects.js`, `manage.css` (tokens instead of hexes), `docs/DESIGN-SYSTEM.md`.
- **Plugin:** new `includes/media.php` (resolver, WebP output, collections filter), block `blocks/page-hero/`, Content Manager
  `includes/manage/screens.php` (banner + library picker), `publishing.php` untouched.
- **Config:** `config/scf/field-groups/page-banner.json`, `config/scf/taxonomies/media_collection.json`, ministry fallback field.
- **Seed/tests/docs:** `scripts/seed-content.php` (Home composition), `scripts/test.sh` (contrast gate, resolver checks),
  `tests/site-theme.php` (resolution, schedule, preview, fallbacks), `docs/CONTENT-MODEL.md`, `docs/ARCHITECTURE.md`.

## 11. Migration

None destructive: preset slugs and stored content unchanged; new SCF definitions are additive; existing featured images keep working
as cards; the 287 sermon covers are untouched. The Home page's content changes only through `seed-content.php` once a photographic
hero exists.

---

## 12. Proposed phases (adjusted after the audit)

| Phase | Scope | Notes |
|---|---|---|
| **1 Foundation** | Token contract + custom tokens; resolver with **schedule data model and preview from the start**; package registry with `default`; `<html>` attributes; palette infrastructure (head script, allowlist) with Site Default only; contrast gate; minimal Appearance → Site Theme (everyday theme + preview) | Scheduling logic is cheap here; UI later |
| **2 Palettes** | Chocolate, Blue, Graphite, Gold, Sage, Navy; footer/mobile selector | Contrast gate must pass |
| **3 Image system + Content Manager media** | Banner fields, `media_collection`, resolver + fallbacks, `page-hero` block, WebP, heroes on Home/About/Ministries/Sermons/New Here, ministry images, Manager banner + library picker | Merges brief phases 3–4: fields and forms ship together. **Needs photos** |
| **4 Homepage rhythm** | Photographic opening → latest sermon → ministries → updates/community → visit/contact | Events/stories sections wait for their content models |
| **5 Natural seasons** | Spring, Summer, Fall, Winter packages (tokens, textures, slot images, recommended palette), schedule UI | Restrained; no effects yet; Winter review checklist |
| **6 Effects** | Leaves (Fall), snow (Winter); Off/Subtle/Enhanced | Reduced-motion and mobile rules |
| **7 Church Anniversary** | Package, mark, gold highlight, history/current photos, scheduled window | Needs anniversary date, mark and historical photos |
| **8 Biblical observances** | Package type exists from Phase 1; individual packages only after the church defines them | No assumed calendar |
| **9 Scheduling polish** | Admin conveniences (suggested dates, conflicts warning) | Core scheduling already works from Phase 1 |

## 13. Tests per phase (summary)

Resolution (default, each package, schedule windows and boundaries, preview permission, invalid theme/palette/asset); palette
contrast gate; responsive screenshots at 390/768/1280 for Home, a ministry, a post, a utility page; keyboard/focus; reduced motion
(effects absent); hero with/without banner/featured/theme image; Lighthouse-style checks for LCP/CLS on Home; WordPress media
operations and core checksums unchanged.

## 14. Non-goals

Website builder, visitor CSS, palette combinations files, theme marketplace, particle framework, parallel media manager, per-season
templates, theme conditionals inside components.

## 15. Decisions

Answered 2026-09-16:

- **Design-system rules — approved changes:** intentional **fallback images** (curated, varied by page type) instead of omitting;
  **opt-in atmospheric effects** (reduced-motion aware, Off/Subtle/Enhanced); **photographic heroes** on landing pages (moderate on
  ministry pages, none on utility pages). `docs/DESIGN-SYSTEM.md` is updated in Phase 1 to record them.
- **Palette selector:** footer + mobile menu.
- **Imagery:** real church photos (to be supplied; the church will select from the images on its own Facebook pages and/or take new
  ones) and generated artwork for decorative layers. Until real photos exist, **placeholder images are created** for heroes and
  fallbacks.
  - Placeholders are generated, non-photographic, and never depict people or church life, so they cannot be mistaken for real
    events. They go in a `Placeholder` media collection so every one can be found and replaced.
  - Replacing a slot's image in Appearance → Site Theme or on the content itself swaps it everywhere. No template change is needed.
  - Facebook photos are downloaded by the church from its own pages and uploaded to the Media Library. The site never links
    Facebook image URLs (Rule 12).
- **Content Manager uploads:** approved automatically. Every upload joins the shared library: `General` collection by default,
  re-filed by a Content Admin.

Still open (not blocking Phases 1–6): the church anniversary date/week and mark, and which biblical observances to theme.

Original questions (for the record):

1. **Design-system rule changes**: allow (a) intentional **fallback images** (real curated photos) where rules now say “omit”,
   (b) **opt-in atmospheric motion** where rules say “no motion”, (c) **photographic heroes** on landing pages.
2. **Photography**: who supplies church photos, and may a Content Admin approve them into collections? Is **generated/abstract
   artwork** acceptable for decorative layers (never for people or church life)?
3. **Palette selector placement**: footer + mobile menu (proposed), or a small header control.
4. **Occasions**: the church anniversary date/week and mark; which biblical observances (if any) the church wants themed, with their
   scriptural direction. No packages are created without this.
5. **Uploads in the Content Manager**: new images approved automatically or reviewed by a Content Admin before they are offered to others.

## 16. As built (Phases 1–6, 2026-09-15): deviations from this proposal

| Proposal | Built | Why |
|---|---|---|
| One `highlight` token | `highlight` (on Paper/Mist) and `highlight-inverse` (on Night) | No single gold passed contrast on both grounds in every palette |
| Overlapping schedules: most specific wins | The window that started later wins; ties go to the shorter window | Predictable without a specificity model; shown in the admin table |
| `ministry_fallback_image` field | A ministry's featured image is the parent fallback for its updates and ways to serve | One image to maintain; no extra field |
| Atmosphere tokens `decor`, `hero-overlay`, `hero-overlay-mobile`, `texture` | Plus `hero-mark` (the season's small decorative mark) | Marks must stay token-driven, not tested by theme id |
| Pages cached as usual | Public HTML sends `Cache-Control: no-cache` and a past `Expires` | Hostinger's `.htaccess` cached HTML for a week, hiding schedules and edits |
| Library picker = wp.media | A light dialog on the REST media endpoint (collections, search, upload) | The Content Manager stays free of wp-admin scripts; works on phones |
| Theme images: separate pieces | `page-hero` slot exists, but `page.html` passes no slot, so ordinary pages without a banner stay typographic | Keeps Contact and Connect plain (page restraint, §8.1) |
| Effects: Off / Subtle / Enhanced | Also a visible **Pause animation** control on the hero, remembered per browser | WCAG 2.2.2 (Pause, Stop, Hide) for motion lasting over 5 seconds |
| `tests/site-theme.php` | `tests/appearance.php` (routine) and `tests/appearance-admin.php` (`--appearance`) | Matches the existing test layout |

Not built (by instruction): Phase 7 Anniversary, Phase 8 biblical observances, Phase 9 extras.
