#!/usr/bin/env bash
#
# verify-staging.sh — read-only checks of the Hostinger staging site after a deploy.
#
set -uo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

STAGING_SSH="${CACDEMO_STAGING_SSH:-$HOME/bin/hostinger}"
STAGING_PATH="${CACDEMO_STAGING_PATH:-domains/cacdemo.crishub.com/public_html}"
STAGING_URL="${CACDEMO_STAGING_URL:-https://cacdemo.crishub.com}"

PASS=0; FAIL=0
pass() { PASS=$((PASS+1)); printf '  \033[32mPASS\033[0m %s\n' "$*"; }
fail() { FAIL=$((FAIL+1)); printf '  \033[31mFAIL\033[0m %s\n' "$*"; }
remote() { "$STAGING_SSH" "cd \"\$HOME/$STAGING_PATH\" && $1" 2>/dev/null; }

printf '\n\033[1m%s\033[0m\n' "Staging WordPress ($STAGING_URL)"
[[ "$(remote 'wp theme list --status=active --field=name')" == "$THEME_SLUG" ]] && pass "theme $THEME_SLUG is active" || fail "theme $THEME_SLUG is not active"
remote 'wp core verify-checksums' >/dev/null && pass "core matches WordPress.org checksums" || fail "core checksums differ"
active=$(remote "wp plugin list --status=active,must-use,dropin --field=name" | sort | paste -sd,)
expected=$( { approved_plugins; own_plugins; } | sort | paste -sd,)
[[ "$active" == "$expected" ]] && pass "only approved plugins are running ($active)" || fail "running plugins: $active (approved: $expected)"
[[ "$(remote "wp eval 'echo post_type_exists(\"centre\") ? 1 : 0;'")" == "1" ]] && pass "centre post type is registered" || fail "centre post type missing"
diff -q <(remote "wp theme get $THEME_SLUG --field=version") <(sed -nE 's/^Version:[[:space:]]*//p' "$THEME_SRC/style.css") >/dev/null \
    && pass "deployed theme version matches the repository" || fail "deployed theme version differs from the repository"

printf '\n\033[1m%s\033[0m\n' "Public pages"
for path in / /about/ /new-here/ /centres/ /sermons/ /ministries/ /connect/ /music/ /contact/; do
    body=$(curl -s -m 30 "$STAGING_URL$path?nocache=$RANDOM"); code=$(curl -s -o /dev/null -m 30 -w '%{http_code}' "$STAGING_URL$path")
    if [[ "$code" == "200" ]] && grep -q 'wp-block-site-title' <<<"$body" && grep -q '<footer' <<<"$body" \
        && ! grep -qiE "Fatal error|Warning:|Notice:|hostinger" <<<"$body"; then
        pass "GET $path → 200, theme header/footer, no PHP errors or Hostinger output"
    else
        fail "GET $path → $code"
    fi
done
[[ "$(curl -s -o /dev/null -m 30 -w '%{http_code}' "$STAGING_URL/wp-admin/")" == "302" ]] && pass "/wp-admin/ redirects to login" || fail "/wp-admin/ not redirecting"

printf '\n%d passed, %d failed\n' "$PASS" "$FAIL"
(( FAIL == 0 ))
