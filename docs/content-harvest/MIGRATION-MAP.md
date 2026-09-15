# Migration map

Recommendations only. Nothing has been migrated. Content classes (A core · B resource ·
C member/internal · D historical · E media) and the decision gate are defined in
[INFORMATION-ARCHITECTURE.md](../INFORMATION-ARCHITECTURE.md); the "Content class" column
below uses them. Migration classifications:

**KEEP** · **KEEP BUT RESTRUCTURE** · **REVIEW** · **PROBABLY OBSOLETE** · **DUPLICATE** · **DO NOT MIGRATE**

The destinations are the current WordPress pages (Home, About, New Here, Centres, Sermons,
Ministries, Connect, Music, Contact) plus Global/header/footer, Structured content, Media
Library, External integration, and Unknown.

## Content items

| # | Legacy material | Source | Classification | Destination | Notes |
|---|---|---|---|---|---|
| 1 | Mission: "Our passion is to win souls intentionally for Jesus, express the compassion of God and make disciples like Christ." | `/about` | KEEP | Home, About | Strongest single statement on the site |
| 2 | Founding story + lead pastors (Elijohn and Czarina Payopay, 2017, Toronto, GTA, online, music) | `/about` | KEEP | About | R-16 confirm still current |
| 3 | "LOVE is our highest goal!" | `/about` | KEEP | About (or Home) | |
| 4 | Declaration of Faith (19 titles + references, intro, Ephesians 4:13) | `/about` | KEEP BUT RESTRUCTURE | About (beliefs section) → Structured content | R-03 resolve desktop/mobile; slideshow → native list |
| 5 | Worship service times + two addresses | `/church`, `/rsvp` | KEEP BUT RESTRUCTURE | Structured content: one canonical record per centre (North York, Scarborough), displayed on Centres, Home, New Here, footer, etc. | R-01, R-02 before publishing |
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
| 21 | Volunteer application | `/volunteer` | REVIEW (class C) | Future member/serve area or form capability | Not a public page; process may still be current |
| 22 | Devo Bible description + photos | `/devobible`, `/church` | REVIEW (class B, topic Bible) | Resources candidate | R-06, R-13; publication undecided |
| 23 | Devo Bible videos (7) | `/devobible-*` | REVIEW (class B/E, topic Bible) | Resources candidate; video host YouTube | R-06; rights unknown; not downloaded; redirects if printed links exist |
| 24 | Church background video `trim.mp4` | `/church` | REVIEW (class E) | YouTube or omit; still frame already in source library | No video files in uploads/Git by default (media strategy) |
| 25 | Youth Convention 2024 | `/closed` | PROBABLY OBSOLETE (class D) | Archive only | R-04; youth ministry statement extracted as item 14 |
| 26 | MTE Spring Drive 2024 | `/mte-closed` | PROBABLY OBSOLETE (class D) | Archive only | R-01 evidence; MTE identity extracted as item 16 |
| 27 | 21 Days Prayer & Fasting 2022 | `/paf` | PROBABLY OBSOLETE (class D) | Archive only | Practice may recur (future events) |
| 28 | Psalmists' weekly report, Sunday school checklist, Lifegroup availability, SEED quiz | `/pwr`, `/stc`, `/laf`, `/finalsbatch3` | REVIEW (class C) | Future member/leader resources (requires real access control) | Not public content; never protected by hidden links; church confirms whether still used |
| 29 | Deliverance list, Prayers for deliverance | `/deliverance`, `/deliveranceprayer` | REVIEW (class B, topic Deliverance) | Resources candidate — **publication status: undecided**; audience (public/member/leader/restricted) is a church decision | R-17: provenance, authorship/copyright, pastoral review. Preserved verbatim; not rewritten; not republished |
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

1. **Legacy "Church" → centre records.** Its content is locations and times. These become canonical centre records; the Centres page (and Home, New Here, footer) display them rather than holding the text.
2. **Connect vs Contact overlap.** Legacy Connect was only social links. Consider: Contact = phone/email/addresses/form; Connect = social, groups, serve, next steps.
3. **Sermons has no legacy content.** It will be fed by the YouTube channel and/or Facebook group until sermon records exist.
4. **Ministries needs new content.** Only names exist; the youth ministry and MTE have the most material.
5. **Devo Bible → Resources candidate (topic: Bible)**, publication undecided (R-06).
6. **Beliefs** could be an About section or an About child page ("What we believe") — decide once R-03 is resolved.
7. **Topical teachings are Resources, not Pages.** Deliverance material and Bible resources belong to a future Resources archive with topics derived from actual content (today: Deliverance, Bible). See INFORMATION-ARCHITECTURE §4.2.
8. **Internal/member material is class C**, reserved for a future access-controlled area — not "never", but never public by default and never protected by hiding links.

## Capabilities suggested by the content

For later phases, following **core → free plugin → custom**. Nothing installed.

| Need seen in legacy content | Core WordPress first | If core is not enough |
|---|---|---|
| Social icons in header/footer | Social Icons block | — |
| Spotify artist players, YouTube videos | Embed blocks (oEmbed) | — |
| Service times / centre locations | Canonical structured centre records (one per centre) rendered via Query Loop/blocks wherever shown — not text in pages or synced patterns | Free content-modeling plugin if core alone is insufficient; custom `cacdemo-content` last (see ARCHITECTURE.md → Multi-centre architecture) |
| Maps for centres | Link to map provider; Embed | Free map block plugin |
| RSVP, guest card, contact, volunteer forms | — (core has no forms) | Free established forms plugin — evaluate |
| Events (conventions, drives, prayer & fasting) | Posts + category, Query Loop | Free events/calendar plugin — evaluate, favouring iCal/ICS import-export or a documented Google Calendar path (future Google Calendar integration; source of truth undecided) |
| Sermon archive (speaker, series, scripture, video) | Posts + taxonomies + YouTube embeds, Query Loop | Free sermon-management plugin — evaluate |
| Declaration of Faith as structured list | List/Details (accordion) blocks | Block library only if richer UI needed |
| Ministries directory | Pages hierarchy or Query Loop | — |
| Photo galleries (none currently) | Gallery block | — |
| Newsletter (from staging, not legacy) | — | Provider's free plugin/embed |
| Legacy URL redirects (`/church`, `/devobible*`, …) | — | Free redirection plugin or server rules |
| Video hosting (1.4 GB) | YouTube + Embed | Media offload only if self-hosting |
