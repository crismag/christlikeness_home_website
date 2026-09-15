# Design system

**Status: direction and prototype approved (2026-09-14); production theme in progress.**
[DESIGN-DIRECTION.md](DESIGN-DIRECTION.md) has the research, decisions and prototype findings
(§17). The theme still uses a placeholder palette and default typography.

## Approved design rules (validated by the Phase 4C prototype)

Approved tokens: two families (Schibsted Grotesk, Source Serif 4); palette Paper, Ink, Stone,
Mist, Rule, Night, Night Rule, Night Muted, Stage, Stage Light; no motion. Details and values:
DESIGN-DIRECTION §17.

- **One expressive moment per page.** Everything else stays quiet.
- **Tone bands separate sections** (light editorial, subtle alternate, dark worship). Items
  inside sections are not boxed.
- **Lists before cards.**
  - Centre listings, visit information and directory rows use typography, rules and whitespace.
  - No shadows.
- **Canonical centre data renders every centre view** (visit strip, directory, footer).
  Unconfirmed facts show as pending, never as a chosen value.
- **Missing images are omitted, not placeholdered.**
- **Reading width ≈ 60–65 characters** (about 38rem at body size).
- **Header:** eight destinations + "Find a centre" inline from 1200 px; below that, a
  full-screen menu with Find a centre first and Contact at the foot. Contact is not a
  primary navigation item.
- **Footer order:** centres first, then Contact, then navigation and social links.
- **Mobile is recomposed, not shrunk.** Visitor information stays in the first screen.
- **Accessibility floor:** 44 px targets, visible focus, WCAG AA contrast, meaning never by
  colour alone.
- **The church's own artwork and photos carry identity;** no stock photography.

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
