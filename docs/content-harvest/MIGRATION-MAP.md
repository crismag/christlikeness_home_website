# Migration map

Recommendations only. Nothing has been migrated. Classifications:

**KEEP** · **KEEP BUT RESTRUCTURE** · **REVIEW** · **PROBABLY OBSOLETE** · **DUPLICATE** · **DO NOT MIGRATE**

The destinations are the current WordPress pages (Home, About, New Here, Centres, Sermons,
Ministries, Connect, Music, Contact) plus Global/header/footer, Structured content, Media
Library, External integration, and Unknown.

## Content items

| # | Legacy material | Source | Class | Destination | Notes |
|---|---|---|---|---|---|
| 1 | Mission: "Our passion is to win souls intentionally for Jesus, express the compassion of God and make disciples like Christ." | `/about` | KEEP | Home, About | Strongest single statement on the site |
| 2 | Founding story + lead pastors (Elijohn and Czarina Payopay, 2017, Toronto, GTA, online, music) | `/about` | KEEP | About | R-16 confirm still current |
| 3 | "LOVE is our highest goal!" | `/about` | KEEP | About (or Home) | |
| 4 | Declaration of Faith (19 titles + references, intro, Ephesians 4:13) | `/about` | KEEP BUT RESTRUCTURE | About (beliefs section) → Structured content | R-03 resolve desktop/mobile; slideshow → native list |
| 5 | Worship service times + two addresses | `/church`, `/rsvp` | KEEP BUT RESTRUCTURE | Centres; summary on Home and New Here | R-01, R-02 before publishing |
| 6 | "Looking for a Church? … Be our guest. Come as you are." | Home artwork | KEEP BUT RESTRUCTURE | Home, New Here | Rebuild as real text, not text-in-image |
| 7 | Phone 289.212.0807 | Home artwork | REVIEW | Contact, footer | R-05 |
| 8 | Social profiles (Facebook, Instagram, Twitter, YouTube) | Home, Church, Connect | KEEP BUT RESTRUCTURE | Global/footer (Social Icons block), Connect | R-09 |
| 9 | Facebook group "Sermons and church activities" | `/connect` | REVIEW | Connect, Sermons | R-09 |
| 10 | YouTube channel | `/connect` | KEEP | Sermons (latest messages), Connect | Confirm it holds sermons/streams |
| 11 | Christlike Worship description + logo + Spotify | `/music` | KEEP | Music | R-12 dated wording |
| 12 | Radical Music description + logo + Spotify | `/music` | KEEP | Music | |
| 13 | Streaming availability statement | `/music` | KEEP BUT RESTRUCTURE | Music | Add actual platform links (R: obtain) |
| 14 | R.A.D.I.C.A.L youth ministry statement ("We are Radicals!" + desire statement) + youth socials | `/closed`, `/music` | KEEP BUT RESTRUCTURE | Ministries (youth) | Extract from the 2024 event page |
| 15 | Psalmists Ministry | `/music`, `/pwr` | KEEP BUT RESTRUCTURE | Ministries (worship), Music | Needs description |
| 16 | More Than Enough (food pantry & clothes closet) + seal | `/mte-closed`, `/volunteer` | REVIEW | Ministries (outreach/compassion) | R-07; needs description |
| 17 | Ministry team names (10) | `/volunteer` | REVIEW | Ministries, Connect (serve) | R-07 |
| 18 | Lifegroups, Sunday school, SEED discipleship, Prayer & fasting | `/laf`, `/stc`, `/finalsbatch3`, `/paf`, `/rsvp` | REVIEW | Ministries, New Here (kids), Connect (groups) | Evidence only; church to describe |
| 19 | RSVP form (fields, first-time question) | `/rsvp` | KEEP BUT RESTRUCTURE | New Here / Centres (plan-a-visit) | Future form capability |
| 20 | First-time guest welcome + Jeremiah 29:11 | `/vip` | KEEP BUT RESTRUCTURE | New Here | Form = future capability |
| 21 | Volunteer application | `/volunteer` | DO NOT MIGRATE (as form) | Connect (serve) later | Internal process |
| 22 | Devo Bible description + photos | `/devobible`, `/church` | REVIEW | Unknown / needs decision | R-06, R-13 |
| 23 | Devo Bible videos (7) | `/devobible-*` | REVIEW | External integration (YouTube?) | R-06; redirects if printed links exist |
| 24 | Church background video `trim.mp4` | `/church` | REVIEW | Media Library (Home/Centres hero) | 720p copy sufficient |
| 25 | Youth Convention 2024 | `/closed` | PROBABLY OBSOLETE | — (optional past-events archive) | R-04 |
| 26 | MTE Spring Drive 2024 | `/mte-closed` | PROBABLY OBSOLETE | — | R-01 evidence |
| 27 | 21 Days Prayer & Fasting 2022 | `/paf` | PROBABLY OBSOLETE | — | |
| 28 | Psalmists' weekly report, Sunday school checklist, Lifegroup availability, SEED quiz | `/pwr`, `/stc`, `/laf`, `/finalsbatch3` | DO NOT MIGRATE | — | Internal tools, not public content |
| 29 | Deliverance list, Prayers for deliverance | `/deliverance`, `/deliveranceprayer` | REVIEW | Unknown — leadership decision | R-17; do not migrate by default |
| 30 | Home2 landing | `/coming-soon-03` | DUPLICATE | — | Keep its imagery only |
| 31 | Copy of Church | `/hs2` | DUPLICATE | — | |
| 32 | Fullscreen gallery page, Menu popup | `/fullscreen-page`, `/popup-ipify` | DO NOT MIGRATE | — | Empty / platform structure |
| 33 | Site SEO keyword description ("non-profit organization, church, charity…") | Home meta | DO NOT MIGRATE | — | Keyword list, not a description; write new SEO copy |

## Assets

| Asset | Class | Destination |
|---|---|---|
| Logo mark (white PNG), favicon | KEEP (pending official files, R-11) | Global/header (Site Logo), Site Icon |
| Logo photo-collage (landscape, portrait) | REVIEW | Home/About hero candidates |
| Christlike Worship logo, Radical Music logo | KEEP | Music → Media Library |
| Lead pastors section photo | REVIEW (R-10) | About → Media Library |
| Worship video frame | KEEP | Home / New Here / Centres imagery |
| Looking-for-a-Church banners | KEEP BUT RESTRUCTURE | Source for photos/message only |
| Youth Convention banner, MTE Spring Drive artwork | PROBABLY OBSOLETE | Archive / MTE seal reference |
| Devo Bible photos | REVIEW (R-06) | Unknown |

## New Here — supporting material

The old site had no "New Here" page. Material that could support it:

| Need | Available legacy material | Gap |
|---|---|---|
| Welcome message | "BE OUR GUEST. COME AS YOU ARE." (artwork); "LOOKING FOR A CHURCH?"; `/vip`: "Thank you so much for visiting our church! Please know that you are always welcome, and we would love to spend more time with you." (post-visit wording, adapt carefully); `/rsvp`: "We are excited to see you on Sunday!" | — |
| Who we are | Mission statement; founding sentence; "LOVE is our highest goal!" | — |
| When & where | Sunday 10:00 AM North York, 2:00 PM Scarborough, addresses | R-01, R-02 |
| Plan a visit / RSVP | `/rsvp` form fields incl. "If it's your first time, how did you find us?" | Form capability |
| Kids | RSVP counts "Children Attending 3-10 years old"; Sunday school checklist exists | No public description of children's programming |
| What to expect in worship | Worship video frame (visual only) | No description of service style, length, dress, parking, accessibility |
| Next steps | Lifegroups, SEED discipleship, volunteer teams, Facebook group | Descriptions needed |
| Contact | Phone in artwork; social profiles | No email (R-05) |
| Beliefs (short) | Declaration of Faith titles | R-03 |

## Recommended information-architecture adjustments (not applied)

1. **Legacy "Church" → Centres.** Its content is locations and times; the new Centres page is the natural home.
2. **Connect vs Contact overlap.** Legacy Connect was only social links. Consider: Contact = phone/email/addresses/form; Connect = social, groups, serve, next steps.
3. **Sermons has no legacy content.** It will be fed by the YouTube channel and/or Facebook group until sermon records exist.
4. **Ministries needs new content.** Only names exist; the youth ministry and MTE have the most material.
5. **Devo Bible has no destination.** Decide whether it becomes a resource page, a Music/Resources sub-page, or is retired.
6. **Beliefs** could be an About section or an About child page ("What we believe") — decide once R-03 is resolved.

## Capabilities suggested by the content

For later phases, following **core → free plugin → custom**. Nothing installed.

| Need seen in legacy content | Core WordPress first | If core is not enough |
|---|---|---|
| Social icons in header/footer | Social Icons block | — |
| Spotify artist players, YouTube videos | Embed blocks (oEmbed) | — |
| Service times / centre locations | Pages + patterns (synced pattern for reuse on Home/New Here) | Content-modeling plugin only if centres multiply |
| Maps for centres | Link to map provider; Embed | Free map block plugin |
| RSVP, guest card, contact, volunteer forms | — (core has no forms) | Free established forms plugin — evaluate |
| Events (conventions, drives, prayer & fasting) | Posts + category, Query Loop | Free events/calendar plugin — evaluate |
| Sermon archive (speaker, series, scripture, video) | Posts + taxonomies + YouTube embeds, Query Loop | Free sermon-management plugin — evaluate |
| Declaration of Faith as structured list | List/Details (accordion) blocks | Block library only if richer UI needed |
| Ministries directory | Pages hierarchy or Query Loop | — |
| Photo galleries (none currently) | Gallery block | — |
| Newsletter (from staging, not legacy) | — | Provider's free plugin/embed |
| Legacy URL redirects (`/church`, `/devobible*`, …) | — | Free redirection plugin or server rules |
| Video hosting (1.4 GB) | YouTube + Embed | Media offload only if self-hosting |
