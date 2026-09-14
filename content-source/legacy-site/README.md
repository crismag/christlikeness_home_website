# Legacy site source library — christlikeness.ca (Wix)

Church-owned content harvested read-only from https://www.christlikeness.ca/ on
2026-09-14, for populating the new WordPress site later. This is **source material**,
not the new site: no Wix layout, CSS, JavaScript or component configuration is kept.

Analysis and decisions live in [docs/content-harvest/](../../docs/content-harvest/README.md).

## Layout

```text
pages/        One file per meaningful legacy page.
              "SOURCE CONTENT" = verbatim wording; "HARVEST NOTES" = our analysis.
images/       Original-resolution church images, unaltered, by category:
  branding/   logo mark, favicon, logo photo collages
  music/      Christlike Worship and Radical Music logos
  people/     photo from the About page's Lead Pastors section
  worship/    worship frame from the Church page background video
  outreach/   "Looking for a Church?" invitation banners
  events/     2024 event artwork (Youth Convention, MTE Spring Drive)
  resources/  Devo Bible product photos
metadata/
  site-pages.json          all 30 Wix pages (visible/hidden) and the visible menu
  extracted-content.json   structured extraction of every page (text, links, images, forms, media)
  asset-manifest.json      kept images: source URL, dimensions, bytes, SHA-256
```

No downloadable documents were found on the site, so there is no `downloads/` folder.

## Conventions

- Image filenames describe **where/how an image was used**, never who is pictured,
  and end with `__<first 8 hex of the Wix media ID>` for traceability.
- Images are byte-identical to the Wix originals (SHA-256 in `asset-manifest.json`);
  optimisation happens later, in WordPress.
- In page files, lines in `[BRACKETS]` and `<!-- comments -->` are harvest annotations,
  not source wording.
- Form pages record field labels only (as requirements evidence). No form submissions
  or personal data were accessed.

## Not stored here

- 8 Wix-hosted videos (~1.4 GB at 1080p) — URLs and sizes in
  [EXTERNAL-MEDIA.md](../../docs/content-harvest/EXTERNAL-MEDIA.md); storage needs a decision.
- Raw Wix HTML/JSON, platform icons, clip-art, blank video posters and textures —
  see exclusions in [ASSET-INVENTORY.md](../../docs/content-harvest/ASSET-INVENTORY.md).

## How it was harvested

1. `robots.txt` → `sitemap.xml` → `pages-sitemap.xml` (27 URLs).
2. Each page's served HTML (1 request/second) for titles, SEO meta and the Wix viewer model.
3. The viewer model's router `pagesMap` (30 pages incl. 3 not in the sitemap) gave each page's
   data file, fetched from `https://static.wixstatic.com/sites/<pageJsonFileName>.json.z?v=3`.
   This recovers content the served HTML omits (slideshow slides, mobile-only text,
   video IDs, Spotify widget settings, form definitions, hidden pages).
4. Original images from `https://static.wixstatic.com/media/<media id>`; video sizes by HEAD only.
5. Rendered spot-check of the Church page and the hidden gallery page in a headless browser.
