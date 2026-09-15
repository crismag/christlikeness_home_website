# Sermon sources

The site keeps **one sermon per service**. Each place the service can be watched or heard
(Facebook, YouTube, an audio host) is a row in that sermon's **Sources**. Sermons store
metadata, links and one preview still. Video and audio stay on their platforms.

## Source of truth and sync direction

| What | Source of truth | Direction | Stored in WordPress |
|---|---|---|---|
| Sermon: title, service date, series, speaker, topics, scripture, location, summary, related resources | **WordPress** | Editors, plus one-way importers | Post (date = service date), terms, `sermon_scripture`, `sermon_location` (a centre), content |
| Video / audio | Facebook, YouTube, audio host | External → WordPress (link only) | `sermon_sources` rows: type (video/audio), platform, link, label, published, length, source ID |
| Preview still | Saved once from the first source that has one | Copied at import | Featured image (Media Library) |
| Related resources | Media Library uploads or external links | — | Blocks in the sermon content |

Importers never write back to a platform. They append sources and fill empty values, and never
overwrite an editor's changes. A source ID (`facebook:<video id>`, `youtube:<video id>`) is
recorded on every row, so the same upload is never imported twice.

## Workflow

```text
harvest (per platform)  →  review summary  →  import-sermons.php (reconcile)  →  import-report.md  →  decisions.json
```

1. **Harvest.** `scripts/harvest-facebook.py` and `scripts/harvest-youtube.py` need yt-dlp in a
   throwaway virtualenv; there is no API key or OAuth. They write
   `content-source/sermon-harvest/<platform>/videos.json` plus a `README.md` summary. Descriptions,
   comments, reactions, view counts and member data are never stored.
2. **Import and reconcile.** `wp eval-file scripts/import-sermons.php [dry-run]` checks each harvested sermon in order:
   1. Its source ID is already on a sermon: nothing to do.
   2. `decisions.json` has a ruling: follow it (`same_as`, `new`, `skip`).
   3. It confidently matches **one** existing sermon: add it as another source. A confident match is either:
      - the same service date, a compatible campus, the same series or title, and part numbers that are equal or missing on one side; or
      - within 7 days, with the same series, the same part number and lengths within 3 minutes.
   4. The evidence conflicts (for example the same date and length but a different part number): list it for review and write nothing.
   5. Nothing matches: create a sermon with title, date, series, location, the source row and a saved still.

   Facebook is processed first, because its post dates are the service dates.
3. **Review** `content-source/sermon-harvest/import-report.md`, then record rulings in `decisions.json`.

Rules from the church (2026-09-15):

- A sermon is a worship service video of at least 20 minutes. Shorts and clips are excluded.
- Special worship services (camp, prayer and fasting) are included.
- Two campuses on the same date are **separate sermons**, with the campus in the title.
- The same title on different dates means separate sermons in the same series.
- A missing or wrong year in a post date comes from the year it was posted.

## Platforms

### Facebook Group "Christlikeness Online": full history harvested 2026-09-16

- The group is public (`groups/975851515926484`). Its video grid renders only in a browser. Signed out it
  lists videos from 2025 onward; **logged in as a member** it lists everything (333 videos). A church member
  logged in, the grid was scrolled to the end, and only **church-account uploads** were kept (`chrstlknss.ad.1`,
  `christlikeness.sz.3`, `ChristlikeInternational`: 322 videos). Videos from members' and pastors' personal
  profiles (11) were not listed. Each video's public metadata is then read with yt-dlp, with no login.
- Post patterns changed over the years, and the parser handles them all:
  - `CHRISTLIKENESS • Worship Service • {Month D, YYYY} • {TITLE} [- {Campus} Campus]` (2025–2026);
  - `[CHRISTLIKENESS • Worship Service • {D Month YYYY} • **{TITLE}** • ]` (2022–2024, sometimes in Unicode small caps);
  - `[Christlikeness • Worship Service • {D Month YYYY} ——{TITLE} • At Our Temporary Location: …]` (2020–2021);
  - `… Worship Service • Radical Service • {date} • {TITLE}` (the Radical youth service).

  Posts give title, service date, series and part (numbers, "Series:", subtitles), campus (2026) and the
  Radical label. **No speaker, scripture or further description.**
- Result: **288 worship services, November 29, 2020 to September 13, 2026.** Not imported: 30 non-service
  videos, 3 videos over 20 minutes that are not services (Kingdom Banquet 2024, Mentorship, 5th-year welcome), and 1 not
  posted by the church.
- The sermon page plays Facebook videos through Facebook's embed player.

### YouTube channel: harvested 2026-09-15

- The Videos and Shorts tabs and the playlists, read with yt-dlp. 44 items: 11 sermons, 1 unlisted duplicate, 10 shorts,
  17 short videos, 2 non-service videos longer than 20 minutes, 3 unavailable.
- Titles: `Worship Service | {TITLE} | {date}`. No speaker or scripture.
- Every YouTube sermon is also on Facebook. Ten became extra sources on the Facebook sermons;
  one is waiting for review.

### Audio: to be identified

- Add rows with type **Audio**. A direct audio file plays in the core audio player, and a streaming page embeds
  if the host supports oEmbed; otherwise the row is shown as a link.

## Results (2026-09-16)

- **287 sermons** in 51 series: 276 have Facebook only, 11 have Facebook and YouTube, and none have YouTube only. Every sermon has a cover.
- **Rulings** (`decisions.json`): YouTube "Responsive Obedience VII" (August 23) is Responsive Obedience VI. The two Baal Perazim VIII
  uploads (Facebook 65 min, YouTube 46 min, one trimmed) are the same service.
- **Separate services on one date:** North York and Scarborough (April 12 and May 31, 2026), and main plus Radical Service
  (August 21 and November 20, 2022). The label is in the title, and the matcher never merges services whose labels differ.
- **For review:** none. The two August 27, 2023 "Let's Chat" uploads are one video: imported as Let's Chat V, the copy labelled IV skipped.
- **Ruling:** "God Is All You Need II" was posted with July 16 but is July 24, 2023 (`decisions.json` `date`); both parts are imported.
- **Flags:**
  - Dates had no year or the wrong year, corrected from the year posted.
  - Typos were corrected in titles ("CCHOOSE", "FULLFILMENT", "BUILD- UP").
  - Three services have no title (December 20 and 27, 2020; June 30, 2024) and are titled "Worship Service".
  - Three 2020 services were held at temporary locations (Thornhill, Concord).

## Speakers and scripture from written evidence

Neither platform's post text names the preacher. `content-source/sermon-harvest/enrichment.json` records details found as
**explicit written evidence**, and `import-sermons.php` fills them only where the sermon has none:

- **Stills (poster and title-card text):** OCR (Tesseract) over all 299 saved stills, then a visual check of the 104 that
  carry text.
- **YouTube clip titles:** short clips excluded as sermons still name the preacher of that day's service.
- **Never inferred** from faces or voices.

Result (2026-09-16): 7 sermons have a speaker (Apostle Eljay Payopay 5, Prophet Czarina Payopay 2), and 4 have their main
scripture from "SERMON" title cards.

**Speaker names** show the speaker's **current title** (church, 2026-09-16). The term slug is the name without a title
(`eljay-payopay`), so links and re-imports stay stable when a title changes. Speakers listed so far: Apostle Eljay Payopay,
Prophet Czarina Payopay, and the assigned pastors Pastor Carol Reyes and Pastor Justine Arceo (who appear in filters once a
sermon is theirs). Leadership and people details belong to the future church organization page, not the speaker list. **Unresolved:** a clip "2026 APR 12 | Holy Spirit | Apostle Eljay", because April 12, 2026 has two campus sermons.
Visitors can confirm others through sermon comments once they are turned on.

## Covers and file naming

- **Cover priority:** YouTube stills carry the series poster, so a sermon with a YouTube source uses the YouTube still.
  Otherwise it uses the Facebook still. An importer-set cover is replaced when a better source arrives, and the old image
  is deleted. A featured image chosen by an editor is never touched.
- **Harvest stills** (git-ignored caches): `content-source/sermon-harvest/<platform>/stills/YYYY/MM/YYYYMMDD_Title_Words.jpg`.
  Names use the source's own title, as ASCII words joined by underscores and shortened on a word boundary; the video ID is
  added only on a collision. `videos.json` records the path in `still_file`.
- **Media Library covers:** `uploads/YYYY/MM/YYYYMMDD_Title_Words.jpg`, named from the sermon record, so a corrected title
  renames them on the next import.
- **Files uploaded to a sermon** (notes, slides, study guides) follow the same pattern, through the `cacdemo-content`
  plugin: `uploads/YYYY/MM/YYYYMMDD_Sermon_Title_Original_Name.ext`, filed by the sermon's date rather than the upload month.

## Display

- **`/sermons/`** has a compact filter bar in one row on desktop; on phones the dropdowns sit behind a "Filters" button. Dropdowns apply as soon as they change, and the bar also works without JavaScript. Every choice is in the address, so views can be shared:
  keyword search (`q`), series, year (`yr`), speaker (shown once speakers exist), location, and sort (newest, oldest,
  title A–Z).
- **Views:** **Grid** (default, posters), **List** (cover + details) and **Table** (up to 100 rows per page, grouped by year,
  series, speaker or topic; columns Title, Date, then Speaker and Scripture/notes when any row has them; Title and Date
  headers re-sort).
- **"Where we post sermons" card** at the bottom links the YouTube channel and the Facebook group. It is a pattern in the
  Sermons template, so links are editable in the Site Editor.
- **Featured sermons:** an admin ticks Sermon details → Featured, and the three most recent featured sermons show below the
  filter bar. The row is hidden when none are featured or once a visitor filters or pages the list.
- **Archives:** `/series/<term>/`, `/speaker/<term>/` and `/topic/<term>/` use the same filters and views.
- **Sermon page:** details (date, series, speaker, scripture, language, topics, location, published, last updated), Sources
  as tabs (video players; audio-only sermons show the cover above the audio player), summary, Related resources, an
  optional comments bar, and More in this series. **Every empty item is hidden:** rows without a value, Related resources
  without real links (including the starter placeholder), the series band without other sermons, and comments when off.
- **Comments** (core WordPress, off by default): the site switch is Settings → Discussion → "Sermon comments", then "Allow
  comments" on each sermon, which starts closed. Comments publish immediately; core still catches spam. They sit in a
  collapsed bar below the sermon, "Comments (n)", which invites visitors to name the preacher when the speaker is empty.
- **Language:** English, Tagalog / Filipino, or Taglish. Imported sermons default to English; editors correct them.
- **Deferred:** "Published by". Direction: the platform account for harvested sermons, and the editor's entry for manual ones.

## Synchronization (manual; not scheduled)

`scripts/sync-sermons.sh [--dry-run]` runs the whole pipeline. **No cron job is configured.**

1. **Discover.** `harvest-facebook.py --discover` loads the group's video grid in headless Chrome.
   Without scrolling, Facebook shows the ~8 newest videos, and new links are added to
   `facebook/video-ids.json`. If *all* visible videos are new, it warns that older ones may have been
   missed. List the grid fully in a browser instead, and run sync at least every few weeks.
2. **Harvest.** Only videos not harvested before are read (`--refresh` re-reads all). YouTube is re-listed
   in full, because the channel is small.
3. **Import.** The same reconciliation as the historical import applies. Known sources are skipped,
   matches become extra sources, and new services become sermons. Ambiguous items stay in the report's
   "For review" on every run until `decisions.json` rules on them, so duplicates never accumulate.

Needs yt-dlp in a virtualenv (`CACDEMO_YTDLP_PYTHON`, default `/tmp/ytvenv/bin/python`) and Chrome. It runs
on this machine or another host, not on Hostinger shared hosting. Staging receives the reviewed
harvests through `scripts/deploy-staging.sh --seed-content`.

First dry run (2026-09-15): 8 visible, 0 new; 97 sources already known; 1 for review, since resolved.
