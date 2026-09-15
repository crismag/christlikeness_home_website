#!/usr/bin/env python3
"""
harvest-youtube.py — harvest Sunday Worship METADATA from the church's YouTube channel.

    /tmp/ytvenv/bin/python scripts/harvest-youtube.py [--refresh]

--refresh   re-read every video's metadata instead of only videos not harvested before

Lists every public item on the channel (Videos, Shorts, Live tabs) plus the channel's playlists
(which can include unlisted uploads), reads each new video's metadata with yt-dlp (no API key,
no OAuth), classifies it, and writes the same item template as harvest-facebook.py:

    content-source/sermon-harvest/youtube/videos.json        classified items (importer input)
    content-source/sermon-harvest/youtube/README.md          review summary
    content-source/sermon-harvest/youtube/stills/YYYY/MM/YYYYMMDD_Title.jpg   one cover still per sermon (git-ignored cache)

YouTube thumbnails carry the church's series poster, so they are the preferred cover when a
sermon has a YouTube source. Descriptions (church boilerplate) are not stored.

Classification (rule from the church, 2026-09-15): a sermon is a worship service video of at
least 20 minutes. Shorts and short videos are never sermons.
    sermon       "Worship Service | TITLE | Month D, YYYY", >= 20 min, available
    duplicate    same service (date + title) already classified as sermon; public preferred
    short        from the Shorts tab
    short-video  under 20 minutes
    review       20+ minutes but not titled as a worship service (e.g. events)
    unavailable  private/removed/blocked
"""

import datetime
import json
import pathlib
import re
import sys
import urllib.request

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from sermon_harvest_common import MIN_SECONDS, organise_stills, series_key, split_part, title_case  # noqa: E402

try:
    import yt_dlp
except ImportError:
    sys.exit("yt-dlp is required: python3 -m venv /tmp/ytvenv && /tmp/ytvenv/bin/pip install yt-dlp")

CHANNEL = "UCdEsFxptBKsb1j6Q9PaY5jQ"
REPO = pathlib.Path(__file__).resolve().parent.parent
OUT = REPO / "content-source" / "sermon-harvest" / "youtube"


def parse_service(title):
    parts = [p.strip() for p in title.split("|")]
    if len(parts) != 3 or not re.fullmatch(r"worship service", parts[0], re.I):
        return None
    try:
        date = datetime.datetime.strptime(parts[2], "%B %d, %Y").date().isoformat()
    except ValueError:
        return None
    name = title_case(parts[1])
    base, part = split_part(name)
    return {"date": date, "title": name, "base_title": base, "part": part, "campus": None}


def ydl(**opts):
    return yt_dlp.YoutubeDL({"quiet": True, "no_warnings": True, "skip_download": True, **opts})


def flat(url):
    try:
        with ydl(extract_flat=True) as y:
            info = y.extract_info(url, download=False)
        return info.get("entries") or []
    except yt_dlp.utils.DownloadError:
        return []  # e.g. the channel has no Live tab


def save_still(video_id, current=None):
    """Save the poster thumbnail (maxres, else hq) to stills/<id>.jpg. Returns the relative path or None."""
    if current and (OUT / current).exists():
        return current  # Already saved (organised under stills/YYYY/MM/).
    still = OUT / "stills" / f"{video_id}.jpg"
    if not still.exists():
        for size in ("maxresdefault", "hqdefault"):
            try:
                data = urllib.request.urlopen(f"https://i.ytimg.com/vi/{video_id}/{size}.jpg", timeout=30).read()
            except OSError:
                continue
            if len(data) > 2000:  # YouTube serves a tiny placeholder for missing sizes
                still.write_bytes(data)
                break
    return f"stills/{video_id}.jpg" if still.exists() else None


def main():
    refresh = "--refresh" in sys.argv
    previous = {}
    if (OUT / "videos.json").exists() and not refresh:
        previous = {i["id"]: i for i in json.loads((OUT / "videos.json").read_text())["items"]}
    (OUT / "stills").mkdir(parents=True, exist_ok=True)

    found = {}

    def add(entry, source):
        if entry and entry.get("id") and entry.get("ie_key") != "YoutubeTab":
            found.setdefault(entry["id"], []).append(source)

    base = f"https://www.youtube.com/channel/{CHANNEL}"
    for tab in ("videos", "shorts", "streams"):
        for e in flat(f"{base}/{tab}"):
            add(e, tab)
    for pl in flat(f"{base}/playlists"):
        for e in flat(f"https://www.youtube.com/playlist?list={pl['id']}"):
            add(e, f"playlist:{pl.get('title')}")

    items = []
    with ydl(sleep_interval_requests=1) as y:
        for vid, sources in found.items():
            old = previous.get(vid)
            if old and "post_text" in old:  # Harvested before in this template: keep, refresh where it was found.
                old["found_in"] = sources
                items.append(old)
                continue
            item = {"id": vid, "platform": "youtube", "url": f"https://www.youtube.com/watch?v={vid}", "found_in": sources}
            try:
                info = y.extract_info(item["url"], download=False)
            except yt_dlp.utils.DownloadError as err:
                item.update({"classification": "unavailable", "reason": str(err).split(":")[-1].strip(), "flags": []})
                items.append(item)
                continue
            if info.get("timestamp"):
                published = datetime.datetime.fromtimestamp(info["timestamp"], datetime.timezone.utc).isoformat()
            elif info.get("upload_date"):
                published = datetime.datetime.strptime(info["upload_date"], "%Y%m%d").date().isoformat()
            else:
                published = None
            item.update({
                "post_text": info.get("title") or "",
                "published": published,
                "duration_seconds": round(info.get("duration") or 0),
                "posted_by_church": info.get("channel_id") == CHANNEL,
                "availability": info.get("availability"),
            })
            items.append(item)

    # Classify (always recomputed, so rule changes apply to every item).
    for item in items:
        if item.get("classification") == "unavailable":
            continue
        item["flags"] = [f for f in item.get("flags", []) if not f.startswith(("date-conflict", "other-channel"))]
        if not item["posted_by_church"]:
            item["flags"].append("other-channel")
        service = parse_service(item["post_text"])
        item.pop("sermon", None)
        previous_still = item.pop("still_file", None)
        if previous_still:
            item["_still"] = previous_still
        if "shorts" in item["found_in"]:
            item["classification"], item["reason"] = "short", "Shorts tab"
        elif item["duration_seconds"] < MIN_SECONDS:
            item["classification"], item["reason"] = "short-video", f"{item['duration_seconds'] // 60} min (< 20)"
        elif service:
            item["classification"], item["reason"], item["sermon"] = "sermon", "worship service, 20+ min", service
        else:
            item["classification"], item["reason"] = "review", "20+ min but not titled as a worship service"

    # Duplicates: same date + title; keep the public upload.
    kept = {}
    for item in sorted(items, key=lambda i: (i.get("availability") != "public", i["id"])):
        if item["classification"] != "sermon":
            continue
        key = (item["sermon"]["date"], item["sermon"]["title"].lower())
        if key in kept:
            item["classification"], item["reason"] = "duplicate", f"same service as {kept[key]}"
            item.pop("sermon", None)
        else:
            kept[key] = item["id"]

    sermons = [i for i in items if i["classification"] == "sermon"]

    # Series: a numbered title, or the same title preached on two or more dates (as harvest-facebook.py).
    dates_by_base = {}
    for i in sermons:
        dates_by_base.setdefault(series_key(i["sermon"]["base_title"]), set()).add(i["sermon"]["date"])
    for i in sermons:
        s = i["sermon"]
        s["series"] = s["base_title"] if s["part"] or len(dates_by_base[series_key(s["base_title"])]) > 1 else None
        s.pop("base_title")
        i["still_file"] = save_still(i["id"], i.pop("_still", None))

    # Two services claiming the same date.
    by_date = {}
    for i in sermons:
        by_date.setdefault(i["sermon"]["date"], []).append(i)
    for same in by_date.values():
        if len(same) > 1:
            for i in same:
                others = ", ".join(o["id"] for o in same if o is not i)
                i["flags"].append(f"date-conflict: same title date as {others}; this video published {(i['published'] or '')[:10]}")

    organise_stills(OUT, sermons)  # stills/YYYY/MM/YYYYMMDD_Title.jpg
    key_order = ["id", "platform", "url", "post_text", "published", "duration_seconds", "posted_by_church", "availability",
                 "found_in", "classification", "reason", "sermon", "flags", "still_file"]
    items = [{k: i[k] for k in key_order if k in i} for i in items]
    items.sort(key=lambda i: (i.get("sermon") or {}).get("date") or (i.get("published") or "")[:10], reverse=True)
    (OUT / "videos.json").write_text(json.dumps({
        "channel_id": CHANNEL,
        "harvested": datetime.date.today().isoformat(),
        "rule": "sermon = worship service video posted by the church, at least 20 minutes; shorts and short videos excluded",
        "items": items,
    }, indent=2, ensure_ascii=False) + "\n")

    counts = {}
    for i in items:
        counts[i["classification"]] = counts.get(i["classification"], 0) + 1
    lines = [
        "# YouTube sermon harvest",
        "",
        f"Channel `{CHANNEL}`, harvested {datetime.date.today().isoformat()} by `scripts/harvest-youtube.py`. Public metadata",
        "only; cover stills are cached in `stills/` (git-ignored) for the importer. Rule: a sermon is a worship service",
        "video of at least 20 minutes. `videos.json` is read by `scripts/import-sermons.php`.",
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
                         f"{i['duration_seconds'] // 60} min | {(i['published'] or '')[:10]} | [{i['id']}]({i['url']}) | {'; '.join(i['flags'])} |")
    lines += ["", "## Not imported", "", "| Classification | Reason | Post text | Video |", "|---|---|---|---|"]
    for i in items:
        if i["classification"] != "sermon":
            text = (i.get("post_text") or "")[:80].replace("|", "/")
            lines.append(f"| {i['classification']} | {i['reason']} | {text} | [{i['id']}]({i['url']}) |")
    (OUT / "README.md").write_text("\n".join(lines) + "\n")
    print(f"{len(items)} items: " + ", ".join(f"{k} {v}" for k, v in sorted(counts.items())))


if __name__ == "__main__":
    main()
