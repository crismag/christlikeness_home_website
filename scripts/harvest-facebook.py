#!/usr/bin/env python3
"""
harvest-facebook.py — harvest Sunday Worship METADATA from the Christlikeness Online Facebook Group.

    /tmp/ytvenv/bin/python scripts/harvest-facebook.py [--discover] [--refresh]

--discover  first add the newest group videos to video-ids.json (headless Chrome; for periodic sync)
--refresh   re-read every video's metadata instead of only videos not harvested before

Input:  content-source/sermon-harvest/facebook/video-ids.json
        The group's video list. Facebook renders it only in a browser, so it is collected by scrolling
        https://www.facebook.com/groups/975851515926484/media/videos to the end (logged in as a member
        for the full history) and keeping church-account uploads (see docs/SERMON-SOURCES.md).
Output: content-source/sermon-harvest/facebook/videos.json        classified items (importer input)
        content-source/sermon-harvest/facebook/README.md          review summary
        content-source/sermon-harvest/facebook/stills/YYYY/MM/YYYYMMDD_Title.jpg   one preview still per sermon (git-ignored cache)

Each video's public metadata is read with yt-dlp (no login, no API). Stored, in the same item template
as harvest-youtube.py: video ID and URL, the post's text, publication time, length, and what is parsed
from the text. Not stored: comments, reactions, view counts, member or uploader identifiers, Facebook
image URLs. Post texts are re-parsed on every run, so parser changes apply without re-downloading.

Post patterns (2020–2026):
  "CHRISTLIKENESS • Worship Service • September 13 2026, • TITLE - North York Campus"
  "[CHRISTLIKENESS • Worship Service • 30 June 2024 • **TITLE** • ]"
  "[Christlikeness • Worship Service • 6 December 2020 ——TITLE • At Our Temporary Location: Thornhill, Ontario]"
  "[CHRISTLIKENESS • Worship Service • Radical Service • 20 November 2022 • TITLE • ]"
Rules (church, 2026-09-15/16):
- a sermon is a worship service video of at least 20 minutes, posted by the church;
- a missing or wrong year in the post date is taken from the year it was posted (flagged);
- two services on the same date (two campuses, or a Radical youth service) are separate sermons,
  labelled in the title;
- the same title on different dates is a separate sermon in that title's series;
- special worship services (camp, prayer and fasting) are included.
"""

import datetime
import json
import pathlib
import re
import sys
import unicodedata
import urllib.request

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from sermon_harvest_common import MIN_SECONDS, organise_stills, series_key, split_part, title_case  # noqa: E402

try:
    import yt_dlp
except ImportError:
    sys.exit("yt-dlp is required: python3 -m venv /tmp/ytvenv && /tmp/ytvenv/bin/pip install yt-dlp")

REPO = pathlib.Path(__file__).resolve().parent.parent
DIR = REPO / "content-source" / "sermon-harvest" / "facebook"
CHURCH_UPLOADERS = {"Christlikeness JC", "Christlikeness"}
CAMPUSES = {"north york": "North York", "scarborough": "Scarborough"}
# Obvious typos in post text, corrected for titles/series (flagged; the original text is kept).
TYPOS = {"CCHOOSE": "CHOOSE", "FULLFILMENT": "FULFILMENT", "BUILD- UP": "BUILD-UP", "CHRIST'S CHARACTER": "CHRIST CHARACTER"}
SMALL_CAPS = str.maketrans("ᴀʙᴄᴅᴇꜰɢʜɪᴊᴋʟᴍɴᴏᴘꞯʀꜱᴛᴜᴠᴡʏᴢ", "ABCDEFGHIJKLMNOPQRSTUVWYZ")
MONTHS = "january february march april may june july august september october november december".split()

KEY_ORDER = ["id", "platform", "url", "post_text", "published", "duration_seconds", "posted_by_church", "availability",
             "found_in", "classification", "reason", "sermon", "flags", "still_file"]


def clean(text):
    """Plain text for parsing: NFKC, small caps → capitals, no markdown emphasis or headings."""
    text = unicodedata.normalize("NFKC", text or "").translate(SMALL_CAPS)
    text = text.split("\n")[0]
    text = re.sub(r"[*#]+", "", text)
    return re.sub(r"\s+", " ", text).strip()


def parse_date(raw, published):
    """'September 13 2026,' / '30 June 2024' / '7 Aug 2022' / 'March 29' → (date, flags)."""
    raw = raw.strip(" ,")
    m = re.fullmatch(r"(?:(?P<d1>\d{1,2})\s+(?P<m1>[A-Za-z]+)|(?P<m2>[A-Za-z]+)\s+(?P<d2>\d{1,2}))\s*,?\s*(?P<y>\d{4})?", raw)
    if not m:
        return None, []
    month_name = (m.group("m1") or m.group("m2")).lower()
    month = next((i + 1 for i, name in enumerate(MONTHS) if name.startswith(month_name[:3])), None)
    if not month:
        return None, []
    day, year, flags = int(m.group("d1") or m.group("d2")), int(m.group("y")) if m.group("y") else None, []
    try:
        date = datetime.date(year or published.year, month, day)
    except ValueError:
        return None, []
    if year is None:
        flags.append(f"date had no year; used the year posted ({published.year})")
    elif year != published.year and abs((published.date() - date).days) > 60:
        flags.append(f"date year {year} corrected to the year posted ({published.year})")
        date = date.replace(year=published.year)
    return date, flags


def parse_post(text, published):
    """Returns (sermon dict or None, flags)."""
    line = clean(text).strip("[] ")
    m = re.search(r"Worship\s+Service\s*(?:•|—+|-)\s*(?P<rest>.*)$", line, re.I)
    if not m:
        return None, []
    rest = m.group("rest")
    label = None
    rl = re.match(r"Radical\s+Service\s*•\s*", rest, re.I)
    if rl:
        label, rest = "Radical Service", rest[rl.end():]
    dm = re.match(r"(?P<date>[A-Za-z]+\s+\d{1,2}(?:\s*,?\s*\d{4})?|\d{1,2}\s+[A-Za-z]+(?:\s*,?\s*\d{4})?)\s*,?\s*", rest)
    if not dm:
        return None, ["unparseable date"]
    date, flags = parse_date(dm.group("date"), published)
    if not date:
        return None, ["unparseable date"]
    rest = rest[dm.end():]
    rest = rest.split("]")[0].replace("[", "")        # the post's closing bracket ends the title; later text is commentary
    rest = re.sub(r"^\s*(?:•|—+|-)\s*", "", rest)
    rest = re.sub(r"[\s•\-—\]\[]+$", "", rest).strip()

    location = re.search(r"\s*(?:•|—+|-)?\s*At Our Temporary Location\s*[:\-]?\s*(?P<place>.+)$", rest, re.I)
    if location:
        flags.append(f"temporary location: {location.group('place').strip()}")
        rest = rest[: location.start()].strip()
    campus = None
    cm = re.search(r"[\s\-–(]*\(?\s*(North York|Scarborough)\s+Campus\s*\)?\s*$", rest, re.I)
    if cm:
        campus, rest = CAMPUSES[cm.group(1).lower()], rest[: cm.start()]
    rest = re.sub(r"[\s\-–—•]+$", "", rest).strip()

    for wrong, right in TYPOS.items():
        if wrong in rest.upper():
            rest = re.sub(wrong, right, rest, flags=re.I)
            flags.append(f"typo corrected: {wrong} -> {right}")

    # "Series: NAME — TITLE", "Series: NAME: TITLE", "Series •NAME"
    explicit_series = None
    sm = re.match(r"Series\s*(?::|•)\s*(?P<body>.+)$", rest, re.I)
    if sm:
        body = sm.group("body").strip()
        parts = re.split(r"\s*(?:—+|•)\s*", body, maxsplit=1)
        if len(parts) == 2 and parts[1]:
            explicit_series, rest = parts[0], f"{parts[0]}: {parts[1]}"
        else:
            rest = body
            if ":" in body:
                explicit_series = body.split(":")[0]
    else:
        # "Build Up in Christ Character— ULTIMATE GOAL" style (series name before an em dash)
        em = re.match(r"(?P<series>[^—•]+?)\s*—+\s*(?P<title>.+)$", rest)
        if em and re.search(r"Series\s*:", line, re.I):
            explicit_series = em.group("series")
    rest = re.sub(r"^.*?Series\s*:\s*", "", rest, flags=re.I) if re.search(r"Series\s*:", rest, re.I) else rest
    rest = re.sub(r"\s*(?:—+|•)\s*|\s+-\s+|(?<=\w)-\s+", ": ", rest)  # remaining separators become subtitles
    rest = re.sub(r"\bPart\s+([IVX]+)\b", r"\1", rest, flags=re.I)
    rest = re.sub(r"(\b[IVX]+)-$", r"\1", rest).strip(" :")

    if not rest:
        flags.append("post has no sermon title")
        title = "Worship Service"
    else:
        title = title_case(rest)
    base, part = split_part(title)
    if explicit_series:
        base = title_case(explicit_series.strip(" :"))
    return {"date": date.isoformat(), "title": title, "base_title": base, "part": part, "campus": campus, "label": label}, flags


def discover(listing):
    """Add the newest group videos to the listing by loading the video grid in headless Chrome.

    Without scrolling, Facebook renders only the ~8 newest videos, which is enough for a periodic
    sync. If none of them is already known, older new videos may be missed: list the grid in a
    browser by scrolling to the end instead (see docs/SERMON-SOURCES.md).
    """
    import shutil
    import subprocess
    import tempfile

    browser = next((b for b in ("google-chrome", "google-chrome-stable", "chromium", "chromium-browser") if shutil.which(b)), None)
    if not browser:
        sys.exit("--discover needs Chrome or Chromium")
    url = f"https://www.facebook.com/groups/{listing['group_id']}/media/videos"
    with tempfile.TemporaryDirectory() as profile:
        dom = subprocess.run(
            [browser, "--headless=new", "--disable-gpu", "--no-first-run", f"--user-data-dir={profile}",
             "--virtual-time-budget=15000", "--window-size=1440,4000", "--dump-dom", url],
            capture_output=True, text=True, timeout=180,
        ).stdout
    seen, found = set(), []
    for owner, vid in re.findall(r"facebook\.com/([A-Za-z0-9._-]+)/videos/(\d+)", dom):
        if vid not in seen:
            seen.add(vid)
            found.append({"owner": owner, "id": vid, "url": f"https://www.facebook.com/{owner}/videos/{vid}/"})
    if not found:
        sys.exit("discover: no videos found on the group page (layout change or blocked); nothing changed")
    known = {v["id"] for v in listing["videos"]}
    new = [v for v in found if v["id"] not in known]
    if len(new) == len(found):
        print(f"discover: WARNING all {len(found)} visible videos are new; older ones may be missing, list the grid fully")
    listing["videos"] = new + listing["videos"]
    listing["discovered"] = datetime.date.today().isoformat()
    (DIR / "video-ids.json").write_text(json.dumps(listing, indent=2) + "\n")
    print(f"discover: {len(found)} visible, {len(new)} new")


def save_still(ydl, item):
    """Save the largest thumbnail to stills/<id>.jpg (Facebook image URLs expire, so they are never stored)."""
    if item.get("still_file") and (DIR / item["still_file"]).exists():
        return item["still_file"]  # Already saved (organised under stills/YYYY/MM/).
    still = DIR / "stills" / f"{item['id']}.jpg"
    if not still.exists():
        try:
            info = ydl.extract_info(item["url"], download=False)
            thumbs = sorted((t for t in info.get("thumbnails") or [] if t.get("url")), key=lambda t: (t.get("width") or 0) * (t.get("height") or 0))
            if thumbs:
                req = urllib.request.Request(thumbs[-1]["url"], headers={"User-Agent": "Mozilla/5.0"})
                still.write_bytes(urllib.request.urlopen(req, timeout=30).read())
        except (OSError, yt_dlp.utils.DownloadError) as err:
            item["flags"].append(f"still not saved: {err}")
    return f"stills/{item['id']}.jpg" if still.exists() else None


def main():
    listing = json.loads((DIR / "video-ids.json").read_text())
    if "--discover" in sys.argv:
        discover(listing)
    (DIR / "stills").mkdir(parents=True, exist_ok=True)
    refresh = "--refresh" in sys.argv
    previous = {}
    if (DIR / "videos.json").exists() and not refresh:
        previous = {i["id"]: i for i in json.loads((DIR / "videos.json").read_text())["items"]}
    items = []

    with yt_dlp.YoutubeDL({"quiet": True, "no_warnings": True, "skip_download": True, "sleep_interval_requests": 2}) as ydl:
        for video in listing["videos"]:
            old = previous.get(video["id"])
            if old and old.get("classification") != "unavailable" and "post_text" in old:
                item = {k: old[k] for k in ("id", "url", "post_text", "published", "duration_seconds", "posted_by_church", "still_file") if k in old}
            else:
                item = {"id": video["id"], "url": video["url"]}
                try:
                    info = ydl.extract_info(video["url"], download=False)
                except yt_dlp.utils.DownloadError as err:
                    item.update({"platform": "facebook", "classification": "unavailable", "reason": str(err).split(":")[-1].strip(), "flags": []})
                    items.append(item)
                    continue
                published = datetime.datetime.fromtimestamp(info["timestamp"], datetime.timezone.utc) if info.get("timestamp") else None
                item.update({
                    "post_text": unicodedata.normalize("NFKC", info.get("description") or "").strip(),
                    "published": published.isoformat() if published else None,
                    "duration_seconds": round(info.get("duration") or 0),
                    "posted_by_church": info.get("uploader") in CHURCH_UPLOADERS,
                })
            item.update({"platform": "facebook", "availability": "public", "found_in": [f"group:{listing['group_id']}"]})

            # Classify from the stored post text (re-parsed every run).
            published = datetime.datetime.fromisoformat(item["published"]) if item.get("published") else None
            parsed, flags = parse_post(item["post_text"], published) if published else (None, ["no publication time"])
            item["flags"] = flags
            if not item["posted_by_church"]:
                item["classification"], item["reason"] = "not-church", "not posted by the church"
            elif parsed and item["duration_seconds"] < MIN_SECONDS:
                item["classification"], item["reason"] = "short-video", f"{item['duration_seconds'] // 60} min (< 20)"
            elif parsed:
                item["classification"], item["reason"], item["sermon"] = "sermon", "worship service, 20+ min", parsed
                item["still_file"] = save_still(ydl, item)
            elif item["duration_seconds"] >= MIN_SECONDS:
                item["classification"], item["reason"] = "review", "20+ min but not posted as a worship service"
            else:
                item["classification"], item["reason"] = "not-service", "not a worship service"
            if item["classification"] != "sermon":
                item.pop("still_file", None)
            items.append(item)

    sermons = [i for i in items if i["classification"] == "sermon"]

    # Series: a numbered title, an explicit "Series:", or the same title preached on two or more dates.
    dates_by_base = {}
    for i in sermons:
        dates_by_base.setdefault(series_key(i["sermon"]["base_title"]), set()).add(i["sermon"]["date"])
    for i in sermons:
        s = i["sermon"]
        numbered_or_named = s["part"] or series_key(s["base_title"]) != series_key(s["title"])
        repeated = len(dates_by_base[series_key(s["base_title"])]) > 1
        s["series"] = s["base_title"] if (numbered_or_named or repeated) and s["title"] != "Worship Service" else None

    # "Series Name: Subtitle", or an unnumbered first part ("God Is All You Need" before "… II"), joins that series.
    bases = {series_key(i["sermon"]["series"]): i["sermon"]["series"] for i in sermons if i["sermon"]["series"]}
    for i in sermons:
        s = i["sermon"]
        if not s["series"] and series_key(s["base_title"]) in bases and s["title"] != "Worship Service":
            s["series"] = bases[series_key(s["base_title"])]
        if ":" in s["title"]:
            head = series_key(re.sub(r"\s+[IVX]+$", "", s["title"].split(":")[0]))
            if head in bases and head != series_key(s["series"] or ""):
                s["series"] = bases[head]

    # One display name per series (spelling variants share a key): the most common, then the earliest.
    names = {}
    for i in sorted(sermons, key=lambda x: x["sermon"]["date"]):
        if i["sermon"]["series"]:
            names.setdefault(series_key(i["sermon"]["series"]), []).append(i["sermon"]["series"])
    for i in sermons:
        if i["sermon"]["series"]:
            variants = names[series_key(i["sermon"]["series"])]
            i["sermon"]["series"] = max(dict.fromkeys(variants), key=variants.count)

    # Several services on one date: separate sermons, labelled in the title; otherwise flag the conflict.
    by_date = {}
    for i in sermons:
        by_date.setdefault(i["sermon"]["date"], []).append(i)
    for same in by_date.values():
        if len(same) < 2:
            continue
        tags = [i["sermon"]["campus"] or i["sermon"]["label"] for i in same]
        labelled = len(set(tags)) == len(same)  # every service distinguishable (a campus, or one unlabelled main service plus Radical)
        for i, tag in zip(same, tags):
            if labelled:
                if tag:
                    i["sermon"]["title"] = f"{i['sermon']['title']} ({tag})"
            else:
                others = ", ".join(o["id"] for o in same if o is not i)
                i["flags"].append(f"date-conflict: same service date as {others}; this video posted {i['published'][:10]}")

    for i in items:
        if "sermon" in i:
            i["sermon"].pop("base_title", None)
            i["sermon"].pop("label", None)
    organise_stills(DIR, sermons)  # stills/YYYY/MM/YYYYMMDD_Title.jpg, named from the final titles
    items = [{k: i[k] for k in KEY_ORDER if k in i} for i in items]
    items.sort(key=lambda i: (i.get("sermon") or {}).get("date") or (i.get("published") or "")[:10], reverse=True)
    (DIR / "videos.json").write_text(json.dumps({
        "group_id": listing["group_id"],
        "harvested": datetime.date.today().isoformat(),
        "rule": "sermon = worship service video posted by the church, at least 20 minutes",
        "items": items,
    }, indent=2, ensure_ascii=False) + "\n")

    counts = {}
    for i in items:
        counts[i["classification"]] = counts.get(i["classification"], 0) + 1
    lines = [
        "# Facebook sermon harvest",
        "",
        f"Group `{listing['group_id']}` (Christlikeness Online), harvested {datetime.date.today().isoformat()} by "
        "`scripts/harvest-facebook.py`. Public metadata only; stills are cached in `stills/` (git-ignored) for the importer.",
        "",
        "Counts: " + ", ".join(f"{k} {v}" for k, v in sorted(counts.items())),
        "",
        "| Service date | Title | Series | Part | Campus | Length | Posted | Video | Flags |",
        "|---|---|---|---|---|---|---|---|---|",
    ]
    for i in items:
        if i["classification"] == "sermon":
            s = i["sermon"]
            lines.append(f"| {s['date']} | {s['title']} | {s['series'] or ''} | {s['part'] or ''} | {s['campus'] or ''} | "
                         f"{i['duration_seconds'] // 60} min | {i['published'][:10]} | [{i['id']}]({i['url']}) | {'; '.join(i['flags'])} |")
    lines += ["", "## Not imported", "", "| Classification | Reason | Post text | Video |", "|---|---|---|---|"]
    for i in items:
        if i["classification"] != "sermon":
            text = clean(i.get("post_text") or "")[:80].replace("|", "/")
            lines.append(f"| {i['classification']} | {i['reason']} | {text} | [{i['id']}]({i['url']}) |")
    (DIR / "README.md").write_text("\n".join(lines) + "\n")
    print(f"{len(items)} items: " + ", ".join(f"{k} {v}" for k, v in sorted(counts.items())))


if __name__ == "__main__":
    main()
