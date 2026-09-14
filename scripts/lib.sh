#!/usr/bin/env bash
# Shared settings for local cacdemo scripts. Sourced, not executed.

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"

if [[ -f "$REPO_ROOT/config/local.env" ]]; then
    set -a
    # shellcheck disable=SC1091
    source "$REPO_ROOT/config/local.env"
    set +a
fi

WP_ROOT="${CACDEMO_WP_ROOT:-/mnt/ai/workspaces/cacdemo}"
SITE_URL="${CACDEMO_URL:-http://cacdemo.local}"
HOSTNAME_LOCAL="${CACDEMO_HOSTNAME:-cacdemo.local}"
WP_VERSION="${CACDEMO_WP_VERSION:-7.1}"
DB_NAME="${CACDEMO_DB_NAME:-u471078694_2oQHk}"
DB_USER="${CACDEMO_DB_USER:-cacdemo_wp}"
DB_PREFIX="${CACDEMO_DB_PREFIX:-wp_}"
ADMIN_USER="${CACDEMO_ADMIN_USER:-cacdemo_admin}"
PHP_FPM_SOCK="${CACDEMO_PHP_FPM_SOCK:-/run/php/php8.4-fpm.sock}"
NGINX_SITE="cacdemo"

THEME_SLUG="cacdemo"
THEME_SRC="$REPO_ROOT/wordpress/themes/$THEME_SLUG"

approved_plugins() { grep -vE '^[[:space:]]*(#|$)' "$REPO_ROOT/config/plugins.txt" | awk '{print $1}'; }

log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m!!\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[1;31mxx\033[0m %s\n' "$*" >&2; exit 1; }

wpc() { wp --path="$WP_ROOT" "$@"; }

require_secret() {
    local name="$1"
    [[ -n "${!name:-}" ]] || die "$name is not set. Put it in config/local.env (see config/local.sample.env) or export it."
}
