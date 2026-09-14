# External media and integrations

External services referenced by christlikeness.ca, recorded as identifiers and
destinations, not Wix embed code. External sites were **not visited**, so whether each
link is still active is unverified (REVIEW-NEEDED R-09).

The recommendations below are for later phases and follow the project order: WordPress
core → free plugin → custom.

## Social profiles

| Provider | Destination (verbatim) | Identifier | Source pages | Purpose | Recommendation |
|---|---|---|---|---|---|
| Facebook | http://www.facebook.com/christlikecanada (also `http://facebook.com/christlikecanada`) | `christlikecanada` | Home, Church, Connect | Church page | KEEP → core **Social Icons** block (header/footer), normalise to https |
| Instagram | http://instagram.com/christlikeness_/ | `christlikeness_` | Home, Church, Connect | Church account | KEEP → Social Icons block |
| Twitter / X | http://twitter.com/christlikeness_ (Connect: `Christlikeness_`) | `christlikeness_` | Home, Church, Connect | Church account | VERIFY still active; if kept → Social Icons block (X) |
| YouTube | https://www.youtube.com/channel/UCdEsFxptBKsb1j6Q9PaY5jQ | channel `UCdEsFxptBKsb1j6Q9PaY5jQ` | Connect | Channel; likely online streaming/sermons (not stated) | KEEP → Social Icons; candidate source for Sermons (core YouTube embed) |
| Facebook group | https://www.facebook.com/groups/christlikenessonline | group `christlikenessonline` | Connect | "SERMONS AND CHURCH ACTIVITIES" | VERIFY; link from Connect/Sermons |
| Facebook (youth) | https://www.facebook.com/radicalym | `radicalym` | `/closed` | R.A.D.I.C.A.L youth ministry | VERIFY; Ministries (youth) |
| Instagram (youth) | https://www.instagram.com/radical_ym | `radical_ym` | `/closed` | R.A.D.I.C.A.L youth ministry | VERIFY; Ministries (youth) |

## Music streaming

| Provider | Destination | Identifier | Source | Purpose | Recommendation |
|---|---|---|---|---|---|
| Spotify (embed) | https://open.spotify.com/artist/3uSF4xxA3lBqxWvpWxw7La | artist `3uSF4xxA3lBqxWvpWxw7La` — **Christlike Worship** (Wix Spotify widget setting `spotify:artist:3uSF…`) | `/music` | Artist player | KEEP → core **Embed block** (Spotify is a WordPress oEmbed provider) |
| Spotify (embed) | https://open.spotify.com/artist/50b3uc1Jhiq9yBGd2TRAtt | artist `50b3uc1Jhiq9yBGd2TRAtt` — **Radical Music** | `/music` | Artist player | KEEP → core Embed block |
| Spotify (link) | `https://open.spotify.com/artist/3uSF4xxA3lBqxWvpWxw7La?si=…&fbclid=…&nd=1` | same CW artist, with tracking parameters | Home, Church social icons | Social icon | KEEP without the `si`/`fbclid` parameters |
| Apple Music, YouTube Music, Amazon Music | — no links on site — | — | `/music` text | Named as available | REVIEW: obtain artist URLs from the church |

## Forms and registration (Wix Forms)

All forms are native Wix Forms (app `14ce1214-b278-a7e4-1373-00cebd1bef7c`). They stop
working when Wix is retired. No submissions were accessed.

| Form | URL | Status | Recommendation |
|---|---|---|---|
| Sunday Worship Service RSVP | `/rsvp` | Active (linked from Home/Church) | Future requirement: visit RSVP / plan-a-visit form |
| VIP (first-time guest) | `/vip` | Hidden | Future requirement: guest connection card |
| Volunteer worker application | `/volunteer` | Hidden | Future requirement (internal) |
| Youth Convention 2024 registration | `/closed` | Closed | Do not migrate |
| MTE Spring Drive 2024 sign-up | `/mte-closed` | Past | Do not migrate |
| 21 Days Prayer & Fasting 2022 | `/paf` | Past | Do not migrate |
| Lifegroup availability, Psalmists' weekly report, Sunday school checklist, SEED quiz | `/laf`, `/pwr`, `/stc`, `/finalsbatch3` | Internal | Do not migrate to the public site; confirm whether still used |

## Maps

No Google Maps embeds or map links exist on the site. Locations are plain text.
Recommendation: later, link each centre address to a map (plain link or free map block).

## Wix-hosted videos (church-uploaded)

Not downloaded (1.47 GB total at 1080p). Each Wix video is available at
`https://video.wixstatic.com/video/<video id>/<quality>/mp4/file.mp4` (1080p, 720p, 480p).
The descriptions stored in Wix are placeholders; titles come from the uploaded filenames.

| Video file title | Duration | Wix video ID | 1080p bytes | 720p bytes | Source page | Stored description |
|---|---|---|---|---|---|---|
| Joshua.mp4 | 8:48 | `2484cf_446ae029d4544967be6fdcb6431734b0` | 226,903,357 | 119,944,568 | `/devobible-vid-joshua` | "Book of Joshua Overview" |
| 3D Vid Tabernacle.mp4 | 1:32 | `2484cf_832ebd3be1d44e89b0a0d78eeb42e146` | 49,800,586 | 25,706,148 | `/devobible-3d-tabernacle` | "Tabernacle 3D" |
| Ezra-Nehemiah.mp4 | 8:36 | `2484cf_1eeb278443b14545b5fa595f371e5f1d` | 254,589,851 | 130,246,881 | `/devobible-ezra-nehemiah` | "Book of Joshua Overview" (placeholder) |
| Job.mp4 | 11:01 | `2484cf_4d2cff33f7b54d5e81172fa311e31bff` | 318,400,710 | 162,531,127 | `/devobible-job` | "Book of Joshua Overview" (placeholder) |
| Isaiah.mp4 | 8:11 | `2484cf_8575607df45a4e8881f471be7927b507` | 207,330,832 | 112,811,596 | `/devobible-isaiah` | "Book of Joshua Overview" (placeholder) |
| Ezekiel 1.mp4 | 7:23 | `2484cf_bf9b18c5495d412aa7e0229b4488d9d9` | 202,227,264 | 108,837,086 | `/devobible-ezek1` | "Book of Joshua Overview" (placeholder) |
| Ezekiel 2.mp4 | 7:12 | `2484cf_db721faea1a24d67bf04bfa6ee1972c4` | 152,812,422 | 88,016,291 | `/devobible-ezek2` | "Book of Joshua Overview" (placeholder) |
| trim.mp4 | 1:25 | `2484cf_f8f846c1a8c640729dc2e843a91d9514` | 61,099,178 | 31,090,561 | `/church` (background, also `/hs2`) | — |

Recommendations:

- **Decision needed before download:** where videos should live. Options for later:
  YouTube (church channel, core embed block, no hosting cost), or the WordPress Media
  Library/offloaded storage. The project should not hold 1.4 GB in Git.
- **Rights:** it is not stated who produced the Bible-overview videos. Confirm the church
  may re-host them before uploading anywhere (R-06).
- `trim.mp4` is a short worship montage used as a page background. If wanted, a 720p
  copy (31 MB) is enough for a WordPress Cover block background.
- Wix retains these only while the Wix site exists. If they matter, download them before
  the Wix plan is cancelled.

## Embedded Wix apps (not reproducible, recorded for completeness)

| App | Where | Note |
|---|---|---|
| Wix Pro Gallery | `/fullscreen-page` | Empty (no items in rendered page) |
| Wix Spotify widget | `/music` ×2 | Replace with core Embed block |
| Wix Forms | 12 forms | See above |
