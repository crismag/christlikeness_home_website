# Asset inventory

Church-owned media found on christlikeness.ca, harvested 2026-09-14.

- Paths are relative to `content-source/legacy-site/`.
- Remote originals are `https://static.wixstatic.com/media/<Wix media ID>`.
- Machine-readable details, including full SHA-256 hashes, are in `metadata/asset-manifest.json`.

## Summary

| | Count | Size |
|---|---|---|
| Church images discovered (Wix media prefix `2484cf_`) | 25 | 40.3 MB |
| **Kept (original, unaltered)** | **14** | **27.3 MB** |
| Excluded — blank video posters | 6 | 0.15 MB |
| Excluded — decorative textures | 3 | 14.7 MB |
| Not retrievable (403 from Wix) | 2 | — |
| Wix-hosted church videos (not downloaded) | 8 | 1.47 GB at 1080p |
| Platform noise ignored (Wix stock social icons, clip-art SVGs, Wix logos) | 21 | — |

The kept set is comfortable for Git. The two largest files are the Youth Convention
banner (9.9 MB) and the Devo Bible photos (~3.8 MB each).

## Kept assets

Filenames describe how the image was used, not who is in it. "Migration" is a
recommendation only.

| Local file | Format · size · bytes | Source page & use | Wix original name / alt | Subject (observed) | Proposed use | Migration |
|---|---|---|---|---|---|---|
| `images/branding/logo-mark-white-transparent__a3b8d0f9.png` | PNG · 528×530 · 14,616 | `/church` — image beside Devo Bible promo | alt "g1.png" | White flame/dove-shaped mark on transparent background | Site logo candidate (needs dark background) | KEEP — confirm official logo files with church |
| `images/branding/site-favicon-34px__d2836239.png` | PNG · 34×34 · 4,151 | Site favicon on every page | — | Same mark, white on grey circle | Reference only (too small for reuse) | KEEP as reference |
| `images/branding/logo-mark-photo-collage-landscape__31263352.jpg` | JPEG · 2400×1800 · 171,022 | `/coming-soon-03` (Home2) — referenced in served HTML only, likely page background | — | Flame/dove mark filled with a greyscale photo collage | Branding / hero candidate | REVIEW |
| `images/branding/logo-mark-photo-collage-portrait__db3f71fb.png` | PNG · 2394×3358 · 2,926,469 | `/coming-soon-03` — main image, links to `/church` | alt "Christlikeness" | Portrait version of the mark with photo collage | Branding / hero candidate | REVIEW |
| `images/music/christlike-worship-logo__064effd0.jpg` | JPEG · 4000×4000 · 206,499 | `/music` — Christlike Worship section | "cw logo copy.JPG" | "CW" logo, white on black, with flame/dove mark | Music page | KEEP |
| `images/music/radical-music-logo__128bda72.jpg` | JPEG · 4000×4000 · 634,754 | `/music` — Radical Music section | "rm.JPG" | "RADICAL music" wordmark, black on white | Music page | KEEP |
| `images/people/about-lead-pastors-section-photo__b55d3b0b.jpg` | JPEG · 1836×3264 · 3,631,401 | `/about` — directly under "LEAD PASTORS" | "20210917_143141.jpg" | Informal photo of a man and a woman | About page (pastors) | REVIEW — identity uncaptioned; consider a newer/professional photo |
| `images/worship/church-page-background-video-frame__f8f846c1.jpg` | JPEG · 1920×1080 · 208,420 | `/church` — poster frame of background video `trim.mp4` | Wix-generated poster | Worship service on stage with blue/purple lighting, congregation silhouettes | Home / New Here / Centres imagery | KEEP (frame quality limited to 1080p) |
| `images/outreach/looking-for-a-church-banner-landscape__7bc424be.jpg` | JPEG · 3081×1605 · 780,012 | `/` (Home) — desktop hero | "web1.jpg" | "LOOKING FOR A CHURCH?" invitation with phone and North York address | Source of text/photo only; don't reuse text-in-image | KEEP BUT RESTRUCTURE |
| `images/outreach/looking-for-a-church-banner-portrait__b8948580.jpg` | JPEG · 1080×1920 · 554,452 | `/` (Home) — mobile hero | — | Portrait variant: "BE OUR GUEST. COME AS YOU ARE." | New Here messaging source | KEEP BUT RESTRUCTURE |
| `images/events/youth-convention-2024-counter-culture-banner__a952e9b8.jpg` | JPEG · 3200×1800 · 9,934,517 | `/closed` — top banner | — | "YOUTH CONVENTION COUNTER CULTURE", Nov 9, 5100 Yonge St | Past event / youth ministry history | PROBABLY OBSOLETE |
| `images/events/mte-spring-drive-2024-food-pantry-clothes-closet__dc86feaa.jpg` | JPEG · 3840×2160 · 605,941 | `/mte-closed` — top image | — | MTE seal; "Spring Drive Food Pantry & Clothes Closet", April 20, 2024 | MTE ministry identity (seal) | PROBABLY OBSOLETE (event); seal → REVIEW |
| `images/resources/devo-bible-product-photo-covers__8e4236be.jpg` | JPEG · 2268×2684 · 3,928,761 | `/devobible` — slideshow background, slide 1 | "l1.jpg" | Spiral-bound DEVO BIBLE books with a succulent | Devo Bible resource | REVIEW |
| `images/resources/devo-bible-product-photo-interior__a93d6386.jpg` | JPEG · 2268×2684 · 3,737,438 | `/devobible` — slideshow background, slide 2 | "l2.jpg" | Open Devo Bible: Genesis/Pentateuch page and book list | Devo Bible resource | REVIEW |

### Metadata notes

- `about-lead-pastors-section-photo` has camera EXIF (Samsung SM-N950W, 2021-09-17 14:31:41) and GPS fields set to 0/0 (no real location).
- Both Devo Bible photos have camera EXIF (Samsung SM-F707W, 2021-12-01). No GPS.
- Originals are unaltered. Strip EXIF when optimising for WordPress later.

## Duplicates and variants

| Relationship | Files | Decision |
|---|---|---|
| Same campaign, two crops | Home banner landscape ↔ portrait | Keep both (different text and composition) |
| Same mark, different formats | Logo mark white PNG ↔ favicon (grey circle) ↔ two photo-collage versions | Keep all; white PNG is the most reusable |
| Byte-identical video posters | 4 posters (Joshua, Ezra-Nehemiah, Job, Isaiah) share SHA-256 `49da0cb5…`, all pure white | Excluded |
| Page duplicate | `/hs2` "Copy of Church" reuses the Church background video | Not harvested separately |
| Responsive derivatives | Wix serves resized `/v1/fill/...` versions of every image | Only originals downloaded |

Detection used: Wix media IDs, SHA-256 hashes, dimensions, pixel statistics (mean/min/max
to find blank frames) and visual review of contact sheets.

## Excluded assets

| Wix media ID | Where used | Reason |
|---|---|---|
| `2484cf_446ae029…f000.jpg`, `2484cf_1eeb2784…f000.jpg`, `2484cf_4d2cff33…f000.jpg`, `2484cf_8575607d…f000.jpg` | Devo Bible video poster frames | Pure white, byte-identical — no content |
| `2484cf_db721fae…f000.jpg` | Ezekiel 2 video poster | Pure white frame |
| `2484cf_832ebd3b…f000.jpg` | 3D Tabernacle video poster | Pure black frame |
| `2484cf_bf9b18c5…f000.jpg` (1920×1080, 401,750 B) | Ezekiel 1 video poster | Plain paper texture frame; derived from video |
| `2484cf_4bd3c83275e44e1790cd15768859a000~mv2.jpg` (7000×5000, 4.5 MB) | `/devobible` background ("BG.jpg") | Decorative watercolour texture, not church content |
| `2484cf_5584d6a3efdf4ea0a9acb3c686f94ef8~mv2.jpg` (3200×1800, 9.8 MB) | `/closed` ("yc1.jpg") | Plain dark grain texture |
| `2484cf_5f12ceaaed4043b08eda36c617559824~mv2.png` (400×562) | Background on Church, Connect, Music, Devo Bible | **Not retrievable** — Wix returns 403 for original and resized URLs (likely deleted from media library) |
| `2484cf_a2f0ca6cabd245ee86ea451821935ec5~mv2.png` (2500×1330) | Site-wide social sharing image (og:image) | **Not retrievable** — 403 |
| 14 social icons (`11062b_…`, unprefixed IDs) | Facebook/Instagram/Twitter/Spotify/YouTube icons | Wix stock media |
| 7 SVG shapes (`a3c153_…`, `bb573f1d…`, `a8f3b431…`, `e05ef45d…`, `5ba2c42b…`, `11062b_e7be0a5f…`, `b816fa4c…`) | Various | Wix clip-art (smiley, alarm clock, laptop, arrows, chevrons, hamburger) — not church branding |

## Videos (inventoried, not downloaded)

See [EXTERNAL-MEDIA.md](EXTERNAL-MEDIA.md#wix-hosted-videos-church-uploaded) for IDs, URLs,
durations and sizes. Total: 8 files, 1.47 GB at 1080p / 0.78 GB at 720p.

## Gaps

No official logo source files (SVG/AI/EPS), no high-resolution wordmark for
"CHRISTLIKENESS", no photos of the centres' buildings, no ministry photos, and no
individual leader portraits exist on the site. Request these from the church.
