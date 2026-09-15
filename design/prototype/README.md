# Phase 4C visual prototype (disposable)

Design evidence for [docs/DESIGN-DIRECTION.md](../../docs/DESIGN-DIRECTION.md). This is
**not** production code and must not become the theme. The production implementation is
a WordPress block theme in `wordpress/themes/cacdemo/`, built after the prototype is approved.

## View it

From the repository root (image paths point into `content-source/`):

```bash
python3 -m http.server 8765 --bind 127.0.0.1
```

- Home: http://127.0.0.1:8765/design/prototype/index.html
- Centres: http://127.0.0.1:8765/design/prototype/centres.html

Comparison switches (also in the small toolbar at bottom right):

| Query | Effect |
|---|---|
| `?fonts=2` | Replace Bricolage Grotesque display with Schibsted Grotesk (two-family test) |
| `?night=evergreen` | Swap Night for the Evergreen alternative |
| `?motion=off` | Disable the experimental hero reveal |
| `?photos=slots` | Centres page only: show image slots with "photo needed" placeholders |
| `?shot` | Hide the toolbar (used for screenshots) |

## Contents

| File | Purpose |
|---|---|
| `index.html` | Homepage: hero, visit strip, welcome, worship band, next steps, footer |
| `centres.html` | Centres directory: intro, generated centre rows, call to action, footer |
| `styles.css` | All styling and candidate tokens |
| `prototype.js` | One centre data list rendered into the visit strip, directory and footer (stands in for centre records + Query Loop); menu overlay; comparison flags |
| `screenshots/` | Four review captures at 1440 px and 390 px |

## Content rules followed

- **Images:** authentic harvested images only, referenced from `content-source/`, none copied.
- **Legacy wording:** mission, founding sentence, Ephesians 4:13, "Looking for a church? / Be our
  guest. Come as you are.", and the music project descriptions (lightly shortened for the
  prototype; the archive is unchanged).
- **Design-only wording:** "Next steps" row descriptions, the Contact prompt and the Centres
  call to action.
- **Centre facts:**
  - Harvested, and marked as unconfirmed on every page by a notice bar.
  - The Scarborough unit is left empty and shown as pending (R-01), never 84 BR or 102 BR.
- **External links:** Google Fonts CSS is loaded from Google for convenience. Production
  self-hosts fonts.
