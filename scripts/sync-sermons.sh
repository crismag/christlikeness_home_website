#!/usr/bin/env bash
#
# sync-sermons.sh — find newly posted Sunday Worship videos and add them to the sermon collection.
#
#   scripts/sync-sermons.sh [--dry-run]
#
# 1. Facebook: discover the newest group videos, read metadata for new ones only.
# 2. YouTube: re-list the channel (small; metadata only).
# 3. Import: every new source goes through the same matching as the historical import —
#    known source → nothing; matches an existing sermon → added as another source;
#    no match → new sermon; ambiguous → import-report.md "For review", nothing written.
#
# Not scheduled. Run by hand until results are reliable; see docs/SERMON-SOURCES.md.
# Needs: yt-dlp in a virtualenv (CACDEMO_YTDLP_PYTHON, default /tmp/ytvenv/bin/python) and Chrome.
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

PY="${CACDEMO_YTDLP_PYTHON:-/tmp/ytvenv/bin/python}"
[[ -x "$PY" ]] || die "yt-dlp virtualenv not found: python3 -m venv /tmp/ytvenv && /tmp/ytvenv/bin/pip install yt-dlp"
DRY=""
[[ "${1:-}" == "--dry-run" ]] && DRY="dry-run"

log "Facebook group"
"$PY" "$REPO_ROOT/scripts/harvest-facebook.py" --discover
log "YouTube channel"
"$PY" "$REPO_ROOT/scripts/harvest-youtube.py" 2>/dev/null
log "Import${DRY:+ (dry run)}"
wpc eval-file "$REPO_ROOT/scripts/import-sermons.php" $DRY publish | grep -E '^(REVIEW|FLAGS|ATTACHED|Success)' || true
log "Report: content-source/sermon-harvest/import-report.md"
