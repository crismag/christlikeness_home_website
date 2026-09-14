# Review needed

Items the harvest could not or should not resolve. Each lists every conflicting source
verbatim. **Nothing has been corrected.** Decisions belong to the church.

Priority: **P1** blocks accurate visitor information · **P2** affects what is published ·
**P3** housekeeping.

## Factual conflicts

### R-01 · P1 · Scarborough unit number — VERIFY WITH CHURCH

| Source | Wording |
|---|---|
| `/church` text | "2220 Midland Ave. / UNIT 84 BR / Scarborough, ON / M1P 3E6" |
| `/rsvp` text | "2220 Midland Ave. UNIT 84BR, Scarborough" |
| MTE Spring Drive artwork (April 20, 2024) | "2220 MIDLAND AVE. / UNIT 102 BR / SCARBOROUGH" |

The artwork is newer-looking but event-specific; the move date or event venue is unknown.

### R-02 · P1 · Service times and centre pairing — VERIFY WITH CHURCH

- `/church` lists "10:00 AM" and "2:00 PM" and two addresses **without saying which goes where**, and names no day.
- `/rsvp` pairs them: "10:00 AM - North York", "2:00 PM - Scarborough", on "Sunday".
- The home banner lists only North York. Are both centres still active, and are times current?

### R-03 · P2 · Declaration of Faith: desktop vs mobile differ — VERIFY WITH CHURCH

| # | Desktop | Mobile |
|---|---|---|
| 01 | THE HOLY SCRIPTURES — 2 Timothy 3:16-17 | THE HOLY SCRIPTURE — II Timothy 3:16 – 17 |
| 05 | TOTALITY OF SALVATION — Eph. 2:8, Phil. 2:12, Acts 16:31, Heb. 9:28 | TOTALITY OF SALVATION — Ephesians 2:8 |
| 14 | GIFTS & FRUIT OF THE SPIRIT — 1 Corinthians 12:1-11, Galatians 5:22-23 | THE GIFTS AND FRUIT OF THE HOLY SPIRIT — I Corinthians 12:1- 11 |
| 19 | TITHES AND OFFERING | TITHES AND OFFERINGS |

Minor title differences also in 07, 12, 13, 16 (see STRUCTURED-CONTENT). Which list is
authoritative? Is there an official full Statement of Faith with doctrinal text?

### R-04 · P3 · Youth Convention 2024 address — historical

| Source | Wording |
|---|---|
| `/closed` text | "North York Memorial Community Hall - 5110 Yonge St., North York (beside Mel Lastman Square)" |
| Banner artwork | "5100 YONGE ST NORTH YORK" |

Past event; only matters if an events archive is created.

## Missing or incomplete information

### R-05 · P1 · Contact details

- **Phone** 289.212.0807 appears only inside the home banner image. Is it current and the main church number?
- **No email address** is published anywhere on the site (confirmation messages mention "our email" but never give it).
- No postal/office address separate from worship locations.

### R-06 · P2 · Devo Bible and its videos

- Is DEVO BIBLE still available? "ORDER NOW" leads to a page with no ordering method, price or contact.
- "TESTI MONIAL S" heading has no testimonials.
- Who produced the seven Bible-overview videos? May the church re-host them? Every video's stored description is a copy-pasted "Book of Joshua Overview".
- Do printed Devo Bibles link to the `/devobible-*` URLs (e.g. QR codes)? If so, those URLs may need **redirects** after migration.

### R-07 · P2 · Ministries have names but no descriptions

Volunteer form teams: More Than Enough, Facilities, Victuals, Events, Guest Services,
Prayer, Productions, Creatives, Field Ministry, Gifts and Arrows. Also Lifegroups, Sunday
school, SEED, Psalmists, R.A.D.I.C.A.L. Which are current and public-facing, what does
each do, and who leads them?

### R-08 · P3 · Unexplained internal terms

"CHAT Posting", "SWS", "RLS", "Psalmist's Fellowship", "Skills Training" (`/pwr`);
"Gifts and Arrows", "Victuals" (`/volunteer`). Do not publish or guess meanings.

### R-09 · P2 · External links unverified

Facebook (`christlikecanada`), Instagram (`christlikeness_`), Twitter (`christlikeness_`),
YouTube channel `UCdEsFxptBKsb1j6Q9PaY5jQ`, Facebook group `christlikenessonline`, youth
Facebook `radicalym` and Instagram `radical_ym`. Confirm each is current and still
church-controlled. Twitter/X presence may be dormant.

### R-10 · P2 · Lead pastors photo

The About photo (`images/people/about-lead-pastors-section-photo__b55d3b0b.jpg`, taken
2021-09-17) sits under "LEAD PASTORS" without a caption. Confirm who is pictured and whether
a newer or official photo should be used.

### R-11 · P2 · Official brand assets

Only web-resolution logo marks exist (largest clean mark: 528×530 PNG). Request source
logo files (vector), the CHRISTLIKENESS wordmark, and any brand guidance.

## Date-sensitive or likely obsolete

| ID | Content | Source | Why |
|---|---|---|---|
| R-12 | "launched during the early months of 2020 in the midst of a global catastrophe" | `/music` | Time-anchored wording |
| R-13 | "DEVO BIBLE is now available!" | `/church` | "now" is dated (photos from Dec 2021) |
| R-14 | Youth Convention 2024, MTE Spring Drive 2024, Prayer & Fasting 2022 | `/closed`, `/mte-closed`, `/paf` | Past events |
| R-15 | "Registration is closed" pages still indexed | `/closed`, `/mte-closed` | Old events remain crawlable |
| R-16 | "started Christlikeness in 2017" + "Today, Christlikeness is ministering across the Greater Toronto Area" | `/about` | Confirm still accurate |

## Sensitive content — leadership decision

### R-17 · P2 · Deliverance pages

`/deliverance` (110-item list) and `/deliveranceprayer` (prayers). Both hidden, both
uncredited, and written in a first-person voice suggesting another author ("the things I
have listed here").

- **Authorship/copyright:** confirm the source and whether the church may republish.
- **Pastoral sensitivity:** items link spirits or demonization to, among others,
  "homosexual or lesbian" lifestyles, being "chronically depressed", being "diagnosed as
  manic depressant or schizophrenic", "learning disabilities", "handicaps from childhood",
  being "asthmatic, have sinus problems, or epilepsy", "miscarriages or are barren",
  being "tattooed or have multiple piercings", "martial arts" and "yoga" (quoted from the list).
  Publishing on a public site is a church leadership decision.
- Recommendation: DO NOT MIGRATE by default; keep in the source library for the church's review.

## Structural observations

- Wix hides most pages; several are still indexable (`indexable: true`) and in the sitemap.
  Plan **301 redirects** for any legacy URLs that are printed or shared (at least `/about`,
  `/church`, `/connect`, `/music`, `/rsvp`, `/devobible*`).
- The site header's CHRISTLIKENESS link points to a page ID that no longer exists.
- Legacy "Church" (locations/times) maps to the new **Centres** page, not About.
- The site-wide social sharing image and a recurring page background can no longer be downloaded from Wix (HTTP 403).
