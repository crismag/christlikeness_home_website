# Design system

**Status: direction and prototype approved (2026-09-14); production theme in progress.**
[DESIGN-DIRECTION.md](DESIGN-DIRECTION.md) has the research, decisions and prototype findings
(§17). The theme still uses a placeholder palette and default typography.

## Approved design rules (validated by the Phase 4C prototype)

Approved tokens: two families (Schibsted Grotesk, Source Serif 4); palette roles Paper, Ink, Stone,
Mist, Rule, Night, Night Rule, Night Muted, Stage, Stage Light (values per visitor palette, below).
Details and original values: DESIGN-DIRECTION §17. Visual themes, palettes and imagery:
[VISUAL-THEMES-DESIGN.md](VISUAL-THEMES-DESIGN.md).

**Rule changes approved 2026-09-16** (visual themes): intentional fallback images (curated, varied by page type)
instead of omitting; opt-in atmospheric motion for site themes that declare it (Off / Subtle / Enhanced, never
with reduced motion); photographic heroes on landing pages. Placeholder artwork is non-photographic and never
depicts people or church life.

- **One expressive moment per page.** Everything else stays quiet.
- **Tone bands separate sections** (light editorial, subtle alternate, dark worship). Items
  inside sections are not boxed.
- **Lists before cards.**
  - Centre listings, visit information and directory rows use typography, rules and whitespace.
  - No shadows.
- **Canonical centre data renders every centre view** (visit strip, directory, footer).
  Unconfirmed facts show as pending, never as a chosen value.
- **Missing images fall back deliberately:** content image → parent context (e.g. its ministry) → the active
  theme's slot image → the Default theme's → a typographic fallback. Never a grey box or broken image.
- **Reading width ≈ 60–65 characters** (about 38rem at body size).
- **Header:** eight destinations + "Find a centre" inline from 1200 px; below that, a
  full-screen menu with Find a centre first and Contact at the foot. Contact is not a
  primary navigation item.
- **Footer order:** centres first, then Contact, then navigation and social links.
- **Mobile is recomposed, not shrunk.** Visitor information stays in the first screen.
- **Accessibility floor:** 44 px targets, visible focus, WCAG AA contrast, meaning never by
  colour alone.
- **The church's own artwork and photos carry identity;** no stock photography. Until photos are chosen,
  generated placeholder art (Placeholder collection) stands in and is replaced through the Media Library.
- **Motion is atmospheric and opt-in only:** effects belong to site-theme packages, stay out of the way of
  content and pointer, and never run for `prefers-reduced-motion`.

## Implemented in the theme (v0.2.0, 2026-09-14)

| Piece | Where | Notes |
|---|---|---|
| Tokens | `theme.json` | Palette (10 colours, no custom colours), Schibsted Grotesk + Source Serif 4 self-hosted from `assets/fonts` (OFL), fluid sizes `small`, `body`, `lead`, `subheading`, `heading`, `title`, `display` (no custom sizes), spacing `10`–`70` + `section`, content 38rem / wide 75rem, root padding gutter |
| Section styles | `styles/sections/section-mist.json`, `section-night.json` | Group block styles "Mist band", "Night band" (Night recolours headings, links, buttons) |
| Block styles | `styles/blocks/image-arch.json`, `button-text-link.json`, `group-rows.json` | Arch image (accent, one per page), text-link button, divided rows |
| Header | `parts/header.html` | Site Logo (appears once a logo is set), Site Title, native Navigation (menu overlay below 1200px via theme CSS), Find a centre button (hidden below 480px, where the hero carries it) |
| Footer | `parts/footer.html` | Night band: generated centres row (`cacdemo/centre-footer`, hidden until a centre is published), Contact heading + Contact us button, generated Page List, site title. Social icons deferred until links are verified (R-09) |
| Templates | `templates/front-page.html`, `page.html`, `page-centres.html`, `single.html`, `index.html`, `404.html` | Front page renders the Home page's content; pages show the title then content at reading width |
| Patterns (locked `contentOnly`) | `patterns/hero.php`, `statement.php`, `worship-band.php`, `directory-rows.php`, `cta-band.php` | Editors change text, links and images only |
| Sermon views (generated) | `templates/page-sermons.html` + `patterns/sermon-collection.php`; `taxonomy-sermon_{series,speaker,topic}.html` + `sermon-archive.php`; `single-sermon.html` + `sermon-series-more.php`; starter `sermon-content.php`; shared markup `inc/sermon-card.php`, `inc/sermon-toolbar.php`; styles `assets/css/sermons.css`; `assets/js/view-switch.js` | One card markup, two views: **Grid** (minimal: 16:9 cover, series · date, title) and **List** (detailed: cover beside title + Series/Speaker/Scripture/Date facts). Switch works without JS (`?view=list`), remembers the choice. Cover: featured image (the saved preview still, or a series poster), otherwise a typographic Night fallback. Sermon page: details (Date, Series, Speaker, Scripture, Location, Topics, Published, Last updated; empty ones collapse) beside the Sources as tabs (YouTube, Facebook, Audio), then content and More in this series |
| Centre views (generated) | `patterns/centre-directory.php` (Centres page template), `centre-footer.php` (footer), `centre-visit.php` (insertable visit strip for Home/New Here) | Query Loops over published `centre` records with `acf/field` bindings; centre facts are never typed into pages. Empty fields collapse, Directions hides without a map URL, the whole section hides when no centre is published |

Not yet built: centre visit strip / directory / footer centres (need D-1), media and sermon
templates, FAQ pattern, New Here page composition, Site Logo asset (vector required).

## Tokens, palettes and site themes

### Semantic tokens

Components use only CSS variables. The ten colour presets keep their slugs (stored content uses them) and mean:

| Preset | Role | Custom token (`theme.json settings.custom`) | Role |
|---|---|---|---|
| `paper` | page background | `highlight` | celebration/accent marks on light surfaces (≥ 3:1) |
| `ink` | text; primary button background | `highlightInverse` | the same on Night surfaces (≥ 3:1) |
| `stone` | muted text | `decor` | decorative marks (site themes may set it) |
| `mist` | soft surface (Mist bands) | `heroOverlay`, `heroOverlayMobile` | gradients that keep text readable over photos |
| `rule` | borders and dividers | `texture`, `heroMark` | faint Night-band texture and the hero's seasonal mark (site themes) |
| `night` | inverse surface (Night bands, footer, menu) | `success`, `successSurface` | confirmations (Content Manager) |
| `night-rule` | borders on Night | `danger`, `dangerSurface` | errors and destructive actions |
| `night-muted` | muted text on Night | | |
| `stage` | links, focus ring, accent actions | | |
| `stage-light` | links and focus on Night | | |

Never hard-code a colour in theme CSS; add a role if one is missing.

### Layers and precedence

`<html data-site-theme="…" data-palette="…" data-palette-default="…" data-effects="off|subtle|enhanced" data-effect="snow|leaves">`

1. **Base design** — `theme.json` (`:root`).
2. **Site theme** (administrator) — `:root[data-site-theme="<id>"]` sets only atmosphere tokens (`decor`, `hero-overlay`,
   `hero-overlay-mobile`, `texture`, `hero-mark`), plus its own scoped `style.css`, image slots and effects. It never sets colours;
   it *recommends* a palette.
3. **Palette** — `:root[data-palette="<id>"]` sets the ten presets plus `highlight`/`highlight-inverse`.
   Effective palette = visitor choice → the site theme's recommended palette → `default`.
4. **Content imagery** beats theme imagery (theme slot images are fallbacks only).

Layers set different variables, so there are no specificity contests and no per-combination files.

### Visitor palettes (`wordpress/themes/cacdemo/palettes/*.json`)

Site default · Rich chocolate · Celebrated blue · Graphite · Rich gold · Forest and sage · Deep navy.

- Chosen in the footer picker or its copy at the foot of the phone menu (`cacdemo/palette-picker`,
  `assets/js/appearance.js`), saved in the browser (`localStorage["cacdemo:palette"]`); no account, cookie or server
  variation. "Site default" clears the choice.
- A < 1 KB script at the very top of `<head>` applies the saved choice before any stylesheet, so the default never
  flashes; unknown or removed values are cleared. `/manage/`, wp-admin and the sign-in screen stay on the default.
- **Adding a palette:** copy a JSON file, give every role and custom token a `#RRGGBB` value, set `order`, run
  `scripts/test.sh`. The contrast gate (`inc/appearance/palettes.php` → `cacdemo_palette_contrast_pairs()`) checks 13
  pairs the design uses (text, muted text and links on Paper and Mist; button text; text, muted text and links on Night;
  highlights). A palette that fails is not offered to visitors. `default.json` must equal `theme.json` (tested).

### Site-theme packages (`wordpress/themes/cacdemo/site-themes/<id>/`)

- `package.php` returns: `name`, `type` (`base` · `natural-season` · `church-occasion` · `biblical-observance` ·
  `program`), `description`, recommended `palette`, `tokens` (only the four atmosphere tokens), `slots` (image slots),
  `effects` (`effect => [subtle, enhanced]`), `content_rules`. Optional `style.css` (scoped to
  `:root[data-site-theme="<id>"]`) and small decorative assets.
- `default` is a package like any other.
- **Adding a package:** create the folder and manifest; it appears in Appearance → Site Theme automatically. Components
  never test for a theme id.
- Occasion and biblical-observance packages are created only once the church defines them; Winter is the natural
  season (no Christmas imagery or terminology).
- Lists follow the manifest `order` (Default 0, Spring 10, Summer 20, Fall 30, Winter 40).

### Natural seasons (Spring, Summer, Fall, Winter)

| Season | Recommended palette | Atmosphere |
|---|---|---|
| Spring | Forest and sage | green tint in the hero overlay, sprig mark, faint leaf texture on Night bands, Stage Light rule under image heroes |
| Summer | Celebrated blue | sky tint, sun-and-water mark, faint wave texture, highlight rule |
| Fall | Rich chocolate | amber tint, leaf mark, faint leaf texture, highlight rule |
| Winter | Deep navy | cool blue tint, snow-drift mark, faint snow texture, Stage Light rule |

- Each package sets only the five atmosphere tokens. The overlay mixes the palette's Night with a seasonal tint (text-side
  stop about 90% opaque), so any palette works. The worst-case contrast for hero text over a white image is tested
  (`tests/appearance.php`). The mark (`mark.svg`, cream strokes at 55%) sits bottom right on wide screens and top right on
  phones; the texture (`texture.svg`) is at 4–6% opacity. `style.css` adds the 4px rule in `decor` under image heroes.
- Images: slots `home-hero`, `ministries-hero`, `sermons-hero`, `fallback`. Generated seasonal placeholders go in the
  Placeholder and season collections; a season without an image for a slot uses the Default theme's.
- Review before scheduling: `?site_theme_preview=<season>` (optionally `&palette_preview=<palette>`), or preview links in
  Appearance → Site Theme. Winter checklist: no trees, stars, ornaments, gifts or red-and-green; snow, frost and cold light
  only.

### Administration, scheduling and preview (Appearance → Site Theme)

- **Everyday theme** (base or natural season) and an effects intensity; **scheduled themes** with start and end in
  the site timezone. Resolution happens per request (`cacdemo_site_theme_resolve()`): preview → the scheduled window
  containing now (overlaps: the one that started later) → everyday → Default. No cron; a window ends by itself.
- **Preview** for users who can edit theme options: `?site_theme_preview=<id>&palette_preview=<id>&effects_preview=<intensity>`
  (not cached, not indexed, labelled in the admin bar). Locally, `CACDEMO_SITE_THEME_FORCE` and `CACDEMO_SITE_THEME_NOW`
  (wp-config) force a theme or a moment.
- Stored in the option `cacdemo_site_theme` (everyday, effects, schedule, image slots per theme); invalid values are
  cleaned when read and never stop the site from rendering.
- Tests: `tests/appearance.php` (routine, read-only) and `tests/appearance-admin.php` (`scripts/test.sh --appearance`).

### Effects (Fall leaves, Winter snow)

- Declared by the package (`'effects' => array( 'leaves' => array( 'subtle', 'enhanced' ) )`); the intensity is chosen with
  the everyday theme or each schedule entry (Off / Subtle / Enhanced) and previewed with `&effects_preview=`. `<html>`
  carries `data-effect` and `data-effects`; `assets/js/effects.js` and `assets/css/effects.css` load only when there is an
  effect to show.
- Inside the first image hero only, behind its text: an `aria-hidden`, `pointer-events: none` layer of 7–36 particles
  (half on screens under 782px), animated with transform and opacity only. Leaves take palette colours (highlight inverse,
  Stage Light, Night Muted).
- Never runs with `prefers-reduced-motion` or Save-Data, or without an image hero. It pauses off screen and in hidden
  tabs, and a **Pause animation** button on the hero stops it (remembered in the browser, `cacdemo:motion`).
- Measured locally on a 390px viewport at 4× CPU slowdown with Winter Enhanced: 60 fps, LCP 197 ms, CLS 0.

### Imagery (heroes, cards, placeholders)

- **Hero block** `cacdemo/page-hero` (plugin, `blocks/page-hero/`; styles `assets/css/hero.css`) wraps a page's opening
  blocks. With an image it becomes a dark band: the image (`object-fit: cover`, focal point from the content), the
  site theme's `hero-overlay` scrim, the `hero-mark` decoration, and paper-coloured text with inverse buttons. Without
  one it renders its inner blocks unchanged (`is-plain`). Variants: `landing` (tall) and `moderate`.
  Height and the theme image slot are set in the block's settings panel in the editor, so a template or page can point a
  hero at any slot without code.
- **Where:** Home (`landing`, slot `home-hero`), pages via `page.html` (`landing`, no slot: only a page with its own
  banner gets an image, so Contact and Connect stay typographic), Ministries (`landing`, `ministries-hero`), a ministry
  (`moderate`, `ministries-hero`), Sermons (`moderate`, `sermons-hero`).
- **Resolution** (`includes/media.php` → `cacdemo_resolve_image()`): hero = Banner image (field group *Page banner* on
  pages, ministries and updates, with desktop and phone focal point) → a ministry's featured image → the parent
  ministry's (updates, ways to serve) → the active theme's slot image → the Default theme's → the fallback slot → none.
  Card = featured image → parent ministry's → fallback slot; applied to the core Featured Image block on ministry,
  update and way-to-serve cards, keeping the block's frame.
- **Theme images:** Appearance → Site Theme → *Theme images* chooses a Media Library image per slot per theme
  (one collapsible group per theme, the one showing now open, each labelled with how many slots are filled)
  (`home-hero`, `page-hero`, `ministries-hero`, `sermons-hero`, `fallback`).
- **Performance:** the first hero on a page loads eagerly with `fetchpriority="high"`, later ones lazily; `srcset` with
  `sizes="100vw"`; generated sizes of JPEG and PNG uploads are saved as WebP.
- **Media collections** (taxonomy `media_collection` on attachments; filter in the Media Library list view): General,
  Placeholder, Church, Worship, Community, Ministries, Events, Facilities, Historical, the four seasons, Anniversary,
  Biblical observances, Backgrounds, Textures.
- **Placeholders** (`scripts/seed-appearance.php [dry-run] [regenerate]`): non-photographic generated art (gradients,
  soft light, arches, rings; PHP GD) titled "Placeholder — …", in the Placeholder collection, with empty alt text. It
  fills only empty slots, featured images and banners, never replacing a chosen image. Replace a placeholder by
  choosing a church photo in the same place; `regenerate` redraws the art and repoints existing references.

## Principles

- The theme `wordpress/themes/cacdemo/` owns the Christlikeness design:
  typography, color, spacing, visual language, templates, header/footer
  appearance, responsive presentation.
- Design tokens live in `theme.json`, not hardcoded CSS.
- Style core blocks through theme.json `styles.blocks` and block style variations
  so ordinary Gutenberg content matches the design.
- Reusable sections are block patterns (synced patterns where content should stay in step).
  Structured facts such as centre addresses and service times are not pattern content —
  they come from canonical centre records (see ARCHITECTURE.md → Multi-centre architecture).
- Header/footer are template parts that keep the native Site Logo, Site Title and
  Navigation blocks editable by administrators.

## Richer components

Use core blocks and patterns first. For components core lacks (accordions, tabs,
advanced galleries, sliders/carousels, timelines, icon+text, advanced layout
controls), adopt **one** reputable free block library rather than building blocks
ourselves or stacking overlapping suites. Evaluate it with the plugin checklist in
[ARCHITECTURE.md](ARCHITECTURE.md), then style its output from the theme — never
patch the plugin.

A plugin provides capability; the theme provides Christlikeness design.

Deferred: final visual identity, homepage, header/footer styling, block library choice.
