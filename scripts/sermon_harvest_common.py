"""Shared helpers for the sermon harvest scripts (harvest-youtube.py, harvest-facebook.py)."""

import re

MIN_SECONDS = 20 * 60  # Church rule: a worship service video is at least 20 minutes.

SMALL = {"a", "an", "and", "as", "at", "but", "by", "for", "from", "in", "of", "on", "or", "over", "the", "to", "with"}
ROMAN = {"I": 1, "II": 2, "III": 3, "IV": 4, "V": 5, "VI": 6, "VII": 7, "VIII": 8, "IX": 9, "X": 10,
         "XI": 11, "XII": 12, "XIII": 13, "XIV": 14, "XV": 15}


def title_case(text):
    """'BREAKDOWN TO BUILD-UP II' -> 'Breakdown to Build-up II'; 'WALK ON: SUSTAIN PROGRESS' -> 'Walk On: Sustain Progress'.

    All-caps words are converted; mixed-case words are kept. Small words stay lowercase except at the start,
    at the end, or right before/after a colon. The part after a hyphen in an all-caps word stays lowercase.
    """
    tokens = re.split(r"(\s+)", text.strip())
    words = [i for i, t in enumerate(tokens) if t.strip()]
    out = list(tokens)
    for n, i in enumerate(words):
        token = tokens[i]
        bare = token.strip(":,.!?")
        edge = n == 0 or n == len(words) - 1 or token.endswith(":") or tokens[words[n - 1]].endswith(":")
        if bare in ROMAN and n > 0:
            continue
        if token.isupper() and re.search(r"[A-Z]{2,}", token):
            pieces = token.lower().split("-")
            first = pieces[0]
            if first.strip(":,.!?") in SMALL and not edge:
                out[i] = "-".join(pieces)
            else:
                out[i] = "-".join([first[:1].upper() + first[1:]] + pieces[1:])
        elif bare.lower() in SMALL and not edge and bare[:1].isupper() and (not bare.isupper() or len(bare) == 1):
            out[i] = token.lower()
    return "".join(out)


def split_part(title):
    """'Called to Follow X: Relationship of a Disciple' -> ('Called to Follow', 10)."""
    main = title.split(":")[0] if re.search(r"\s[IVX]+:", title) else title
    m = re.search(r"\s+([IVX]+)$", main.strip())
    if m and m.group(1) in ROMAN:
        return main[: m.start()].strip(), ROMAN[m.group(1)]
    return title.strip(), None


def series_key(name):
    """Loose key for comparing series names across sources ('BAAL PERAZIM GOD OF…' == 'Baal Perazim: God of…')."""
    return re.sub(r"[^a-z0-9]+", "", (name or "").lower())


def file_stem(date_iso, title, max_len=60):
    """'2026-08-23', 'Responsive Obedience VI' -> '20260823_Responsive_Obedience_VI' (ASCII, shortened on a word boundary)."""
    import unicodedata

    words = re.findall(r"[A-Za-z0-9]+", unicodedata.normalize("NFKD", title or "").encode("ascii", "ignore").decode())
    slug = ""
    for word in words:
        candidate = f"{slug}_{word}" if slug else word
        if len(candidate) > max_len:
            break
        slug = candidate
    return f"{date_iso.replace('-', '')}_{slug or 'Untitled'}"


def organise_stills(root, sermons):
    """Move each sermon's still to stills/YYYY/MM/YYYYMMDD_Title[_videoid].jpg, update still_file, drop unreferenced files.

    root: the platform harvest directory; sermons: items classified "sermon" with a "still_file".
    """
    import pathlib

    root = pathlib.Path(root)
    taken = set()
    for item in sorted(sermons, key=lambda i: (i["sermon"]["date"], i["id"])):
        current = root / item["still_file"] if item.get("still_file") else None
        if not current or not current.exists():
            item["still_file"] = None
            continue
        date = item["sermon"]["date"]
        stem = file_stem(date, item["sermon"]["title"])
        target = f"stills/{date[:4]}/{date[5:7]}/{stem}{current.suffix}"
        if target in taken:
            target = f"stills/{date[:4]}/{date[5:7]}/{stem}_{item['id']}{current.suffix}"
        taken.add(target)
        if str(current.relative_to(root)) != target:
            (root / target).parent.mkdir(parents=True, exist_ok=True)
            current.rename(root / target)
        item["still_file"] = target
    for path in (root / "stills").rglob("*"):
        if path.is_file() and str(path.relative_to(root)) not in taken:
            path.unlink()
    for path in sorted((root / "stills").rglob("*"), reverse=True):
        if path.is_dir() and not any(path.iterdir()):
            path.rmdir()
