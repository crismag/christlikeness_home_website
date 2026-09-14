#!/usr/bin/env bash
#
# reset-local.sh — DESTROY the local cacdemo runtime and database, then rebuild
# it with bootstrap-local.sh.
#
# Deletes: $WP_ROOT (WordPress core, wp-config.php, uploads) and database $DB_NAME.
# Keeps:   the Git repository, the nginx vhost, /etc/hosts, the DB user.
#
# Usage: scripts/reset-local.sh [--yes]
#
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

[[ "$(basename "$WP_ROOT")" == "cacdemo" ]] || die "refusing: WP_ROOT basename is not 'cacdemo' ($WP_ROOT)"
case "$WP_ROOT" in
    "$REPO_ROOT"|"$REPO_ROOT"/*|/|/home|/home/*/|/mnt|/mnt/ai|/mnt/ai/workspaces) die "refusing to delete $WP_ROOT" ;;
esac
[[ "$DB_NAME" == "u471078694_2oQHk" ]] || die "refusing: unexpected DB_NAME $DB_NAME"

cat <<EOF
This will permanently delete:
  runtime:  $WP_ROOT
  database: $DB_NAME
and then rebuild both from scratch.
EOF

if [[ "${1:-}" != "--yes" ]]; then
    read -r -p "Type 'cacdemo' to continue: " answer
    [[ "$answer" == "cacdemo" ]] || die "aborted"
fi

log "Dropping database $DB_NAME"
sudo -n mysql -e "DROP DATABASE IF EXISTS \`$DB_NAME\`"

log "Removing $WP_ROOT"
# The theme is a symlink into the repo; remove it first so nothing can follow it.
rm -f "$WP_ROOT/wp-content/themes/$THEME_SLUG" "$WP_ROOT/wp-content/plugins/cacdemo-content"
# sudo: uploads written by PHP-FPM are owned by www-data.
sudo -n rm -rf --one-file-system -- "$WP_ROOT"

exec "$(dirname "${BASH_SOURCE[0]}")/bootstrap-local.sh"
