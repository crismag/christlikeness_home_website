#!/usr/bin/env bash
#
# test.sh — checks for the local cacdemo environment.
#
#   scripts/test.sh            Essential checks. Fast, read-only, never writes to
#                              the database. Run after ordinary changes.
#
#   scripts/test.sh --interop  Also runs the acceptance test of native WordPress
#                              integration (editor, navigation, submenus, Site
#                              Logo, footer template part, publishing, revisions,
#                              scheduling) using temporary content that is removed.
#                              Only for bootstrap, WordPress/PHP upgrades, major
#                              theme architecture changes, releases, or on request.
#
set -uo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

PASS=0; FAIL=0
pass() { PASS=$((PASS+1)); printf '  \033[32mPASS\033[0m %s\n' "$*"; }
fail() { FAIL=$((FAIL+1)); printf '  \033[31mFAIL\033[0m %s\n' "$*"; }
check() { local desc="$1"; shift; if "$@" >/dev/null 2>&1; then pass "$desc"; else fail "$desc"; fi; }
section() { printf '\n\033[1m%s\033[0m\n' "$*"; }

http_code() { curl -s -o /dev/null -w '%{http_code}' "$1"; }
http_body() { curl -s "$1"; }
renders_theme() {  # 200, theme header + footer present, no PHP errors
    local body code
    body=$(http_body "$1"); code=$(http_code "$1")
    [[ "$code" == "200" ]] && grep -q 'wp-block-site-title' <<<"$body" && grep -q '<footer' <<<"$body" \
        && ! grep -qE "Fatal error|Warning:|Notice:|Deprecated:" <<<"$body"
}

section "WordPress runtime"
check "database connection works" wpc db check
check "core files match WordPress.org checksums" wpc core verify-checksums
[[ "$(wpc option get siteurl)" == "$SITE_URL" ]] && pass "site URL is $SITE_URL" || fail "site URL is $(wpc option get siteurl)"
[[ "$(wpc config get WP_ENVIRONMENT_TYPE)" == "local" ]] && pass "WP_ENVIRONMENT_TYPE is local" || fail "WP_ENVIRONMENT_TYPE is not local"
check "GET / → 200 with theme header and footer, no PHP errors" renders_theme "$SITE_URL/"
[[ "$(http_code "$SITE_URL/wp-admin/")" == "302" ]] && pass "/wp-admin/ reachable (redirects to login)" || fail "/wp-admin/ returned $(http_code "$SITE_URL/wp-admin/")"

section "Theme source"
link="$WP_ROOT/wp-content/themes/$THEME_SLUG"
[[ -L "$link" && "$(readlink -f "$link")" == "$(readlink -f "$THEME_SRC")" ]] \
    && pass "runtime theme is a symlink to $THEME_SRC" || fail "runtime theme is not a symlink to the repository source"
[[ "$(wpc theme list --status=active --field=name)" == "$THEME_SLUG" ]] && pass "theme $THEME_SLUG is active" || fail "active theme is $(wpc theme list --status=active --field=name)"
check "www-data can read the theme through the symlink" sudo -n -u www-data test -r "$link/theme.json"
check "theme.json is valid JSON" php -r 'json_decode(file_get_contents($argv[1]), false, 512, JSON_THROW_ON_ERROR);' "$THEME_SRC/theme.json"
php_errors=$(find "$REPO_ROOT/wordpress" -name '*.php' -print0 2>/dev/null | xargs -0 -r -n1 php -l 2>&1 | grep -v '^No syntax errors' || true)
[[ -z "$php_errors" ]] && pass "PHP in wordpress/ has no syntax errors" || fail "PHP syntax errors: $php_errors"

section "Project boundaries"
repo_files=$(git -C "$REPO_ROOT" ls-files --cached --others --exclude-standard)
if grep -qE "(^|/)(wp-admin|wp-includes|uploads)/|(^|/)wp-config\.php$|\.sql(\.gz)?$|(^|/)local\.env$" <<<"$repo_files"; then
    fail "repository contains WordPress core, wp-config.php, uploads, dumps or local.env"
else
    pass "repository contains no WordPress core, wp-config.php, uploads, dumps or secrets"
fi
if grep -E "^wordpress/plugins/" <<<"$repo_files" | grep -qvE "^wordpress/plugins/cacdemo-content/"; then
    fail "third-party plugin code is in the repository"
else
    pass "no third-party plugin code in the repository"
fi
unapproved=$(wpc plugin list --fields=name,status --format=csv \
    | awk -F, 'NR>1 && $2 ~ /^(active|active-network|must-use|dropin)$/ {print $1}' \
    | grep -vxF -f <(approved_plugins; echo "__none__") | paste -sd, || true)
[[ -z "$unapproved" ]] && pass "only plugins listed in config/plugins.txt are running" || fail "unapproved plugins running: $unapproved"

if [[ "${1:-}" == "--interop" ]]; then
    require_secret CACDEMO_ADMIN_PASSWORD
    export CACDEMO_ADMIN_PASSWORD
    WORK=$(mktemp -d)
    logo_before=$(wpc option get site_logo 2>/dev/null || true)
    wpc db query "SELECT ID FROM ${DB_PREFIX}posts" --skip-column-names > "$WORK/ids-before"

    cleanup() {
        [[ -f "$WORK/ids-before" ]] || return 0
        if [[ -n "$logo_before" ]]; then wpc option update site_logo "$logo_before" >/dev/null 2>&1; else wpc option delete site_logo >/dev/null 2>&1; fi
        # Every post created during the run: test pages, navigation, media, template
        # parts, plus what WordPress adds itself (revisions, editor auto-drafts, styles).
        wpc db query "SELECT ID FROM ${DB_PREFIX}posts ORDER BY post_parent DESC" --skip-column-names \
            | grep -vxF -f "$WORK/ids-before" | while read -r id; do wpc post delete "$id" --force >/dev/null 2>&1; done
        mv "$WORK/ids-before" "$WORK/ids-before.done"
    }
    trap 'cleanup; rm -rf "$WORK"' EXIT

    section "Interoperability acceptance (temporary content)"

    page_ids=()
    for t in "Interop Page A" "Interop Page B" "Interop Page C"; do
        page_ids+=("$(wpc post create --post_type=page --post_status=publish --post_title="$t" --porcelain)")
    done
    check "page renders through the theme's page template" renders_theme "$(wpc post url "${page_ids[0]}")"

    nav_links() {
        local id
        for id in "$@"; do
            printf '<!-- wp:navigation-link {"label":"%s","type":"page","id":%d,"url":"%s","kind":"post-type"} /-->\n' \
                "$(wpc post get "$id" --field=post_title)" "$id" "$(wpc post url "$id")"
        done
    }
    header_labels() { http_body "$SITE_URL/" | grep -oE 'wp-block-navigation-item__label">[^<]*' | sed 's/.*>//' | paste -sd,; }

    # The header's Navigation block has no ref, so WordPress shows the newest menu.
    nav_links "${page_ids[@]}" > "$WORK/nav.html"
    nav_id=$(wpc post create "$WORK/nav.html" --post_type=wp_navigation --post_status=publish --post_title="Interop Navigation" --porcelain)
    [[ "$(header_labels)" == "Interop Page A,Interop Page B,Interop Page C" ]] \
        && pass "Navigation menu renders in the header" || fail "header navigation: $(header_labels)"

    { nav_links "${page_ids[0]}"
      echo '<!-- wp:navigation-submenu {"label":"Interop Parent","url":"#interop","kind":"custom"} -->'
      nav_links "${page_ids[1]}"
      echo '<!-- /wp:navigation-submenu -->'; } > "$WORK/nav-sub.html"
    wpc post update "$nav_id" "$WORK/nav-sub.html" >/dev/null
    body=$(http_body "$SITE_URL/")
    grep -q 'wp-block-navigation-submenu' <<<"$body" && grep -q 'Interop Page B' <<<"$body" \
        && pass "submenu renders in the header" || fail "submenu not rendered"

    nav_links "${page_ids[2]}" "${page_ids[0]}" > "$WORK/nav-reorder.html"
    wpc post update "$nav_id" "$WORK/nav-reorder.html" >/dev/null
    [[ "$(header_labels)" == "Interop Page C,Interop Page A" ]] \
        && pass "editing/reordering Navigation updates the header" || fail "header after reorder: $(header_labels)"

    php -r '$i=imagecreatetruecolor(64,64); imagefill($i,0,0,imagecolorallocate($i,44,95,111)); imagepng($i,$argv[1]);' "$WORK/logo.png"
    wpc option update site_logo "$(wpc media import "$WORK/logo.png" --title="Interop logo" --porcelain)" >/dev/null
    grep -qE 'wp-block-site-logo.*<img' <<<"$(http_body "$SITE_URL/" | tr -d '\n')" \
        && pass "Site Logo renders in the header" || fail "Site Logo not rendered"

    CACDEMO_FOOTER="$(sed 's#</footer>#<!-- wp:paragraph --><p>Interop Footer Edit</p><!-- /wp:paragraph --></footer>#' "$THEME_SRC/parts/footer.html")"
    export CACDEMO_FOOTER
    # Same record the Site Editor saves when an admin edits the footer.
    footer_id=$(wpc eval '
        $id = wp_insert_post( array( "post_type" => "wp_template_part", "post_status" => "publish",
            "post_name" => "footer", "post_title" => "Footer", "post_content" => getenv( "CACDEMO_FOOTER" ) ) );
        wp_set_object_terms( $id, get_stylesheet(), "wp_theme" );
        wp_set_object_terms( $id, "footer", "wp_template_part_area" );
        echo $id;')
    grep -q 'Interop Footer Edit' <<<"$(http_body "$SITE_URL/")" \
        && pass "Site Editor footer override changes the public footer" || fail "footer override not reflected"
    wpc post delete "$footer_id" --force >/dev/null
    grep -q 'Interop Footer Edit' <<<"$(http_body "$SITE_URL/")" \
        && fail "footer did not revert to the theme part" || pass "removing the override restores the theme footer"

    jar="$WORK/cookies"
    printf '%s' "$CACDEMO_ADMIN_PASSWORD" | curl -s -c "$jar" -b "wordpress_test_cookie=WP%20Cookie%20check" -o /dev/null \
        --data-urlencode "log=$ADMIN_USER" --data-urlencode "pwd@-" \
        --data "wp-submit=Log+In&testcookie=1&redirect_to=$SITE_URL/wp-admin/" "$SITE_URL/wp-login.php"
    editor=$(curl -s -b "$jar" "$SITE_URL/wp-admin/post-new.php?post_type=page")
    grep -q 'block-editor' <<<"$editor" && pass "admin login works and the block editor loads" || fail "block editor did not load"
    nonce=$(grep -oE 'createNonceMiddleware\( "[a-f0-9]+" \)' <<<"$editor" | grep -oE '[a-f0-9]{8,}')

    json_field() { python3 -c 'import json,sys; print(json.load(sys.stdin)[sys.argv[1]])' "$1" 2>/dev/null; }
    rest() { curl -s -b "$jar" -H "X-WP-Nonce: $nonce" -H "Content-Type: application/json" "$@"; }
    page_id=$(rest -X POST "$SITE_URL/wp-json/wp/v2/pages" \
        -d '{"title":"Interop Editor Test","status":"draft","content":"<!-- wp:columns -->\n<div class=\"wp-block-columns\"><!-- wp:column -->\n<div class=\"wp-block-column\"><!-- wp:paragraph -->\n<p>Interop column one</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:column --></div>\n<!-- /wp:columns -->"}' | json_field id)
    if [[ -n "$page_id" ]]; then
        pass "editor save path (REST) creates a draft"
        [[ "$(http_code "$SITE_URL/?page_id=$page_id")" != "200" ]] && pass "draft is not public" || fail "draft is public"
        body=$(http_body "$(rest -X POST "$SITE_URL/wp-json/wp/v2/pages/$page_id" -d '{"status":"publish"}' | json_field link)")
        grep -q 'Interop column one' <<<"$body" && grep -q 'wp-block-columns' <<<"$body" \
            && pass "published core blocks render through the theme" || fail "published blocks not rendered"
        [[ "$(rest "$SITE_URL/wp-json/wp/v2/pages/$page_id/revisions" | python3 -c 'import json,sys; print(len(json.load(sys.stdin)))' 2>/dev/null)" -ge 1 ]] \
            && pass "revisions recorded" || fail "no revisions"
        future=$(date -u -d '+7 days' +%Y-%m-%dT%H:%M:%S)
        [[ "$(rest -X POST "$SITE_URL/wp-json/wp/v2/pages/$page_id" -d "{\"status\":\"future\",\"date_gmt\":\"$future\"}" | json_field status)" == "future" ]] \
            && pass "scheduling works" || fail "scheduling failed"
    else
        fail "editor save path (REST) could not create a draft"
    fi

    cleanup
    left=$(wpc db query "SELECT ID FROM ${DB_PREFIX}posts" --skip-column-names | grep -cvxF -f "$WORK/ids-before.done" || true)
    [[ "$left" == "0" && "$(wpc option get site_logo 2>/dev/null || true)" == "$logo_before" ]] \
        && pass "temporary content removed and Site Logo restored" || fail "$left temporary posts left behind or Site Logo not restored"
fi

printf '\n%d passed, %d failed\n' "$PASS" "$FAIL"
(( FAIL == 0 ))
