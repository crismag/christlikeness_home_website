#!/usr/bin/env bash
#
# deploy-staging.sh — deploy the cacdemo theme and approved plugins to Hostinger staging.
#
#   scripts/deploy-staging.sh                      theme + our plugins + approved plugins + SCF definitions
#   scripts/deploy-staging.sh --seed-content       also run seed-content.php (REWRITES page content),
#                                                  seed-ministries.php, seed-channels.php and seed-appearance.php (non-destructive)
#                                                  and import-sermons.php (Facebook + YouTube harvests)
#   scripts/deploy-staging.sh --seed-appearance    only run seed-appearance.php (placeholder art; fills empty image slots only)
#   scripts/deploy-staging.sh --replace-hostinger  one-time: back up, then remove Hostinger plugins,
#                                                  must-use plugins, AI theme and generated content
#
# Deploys only our code (wordpress/themes/cacdemo) and definitions/scripts needed to apply it.
# Never deploys WordPress core, wp-config.php, a database or uploads; never downloads the
# staging database. Plugins are installed from WordPress.org by slug (config/plugins.txt).
#
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

STAGING_SSH="${CACDEMO_STAGING_SSH:-$HOME/bin/hostinger}"
STAGING_PATH="${CACDEMO_STAGING_PATH:-domains/cacdemo.crishub.com/public_html}"
STAGING_URL="${CACDEMO_STAGING_URL:-https://cacdemo.crishub.com}"
KIT="cacdemo-deploy"   # remote working copy of scripts/config/images, outside the web root

SEED=0; APPEARANCE=0; REPLACE=0; YES=0
for arg in "$@"; do
    case "$arg" in
        --seed-content) SEED=1 ;;
        --seed-appearance) APPEARANCE=1 ;;
        --replace-hostinger) REPLACE=1 ;;
        --yes) YES=1 ;;
        *) die "unknown option: $arg" ;;
    esac
done

remote() { "$STAGING_SSH" "set -euo pipefail; cd \"\$HOME/$STAGING_PATH\"; $1"; }

preflight() {
    log "Pre-flight ($STAGING_URL)"
    [[ -x "$STAGING_SSH" ]] || die "SSH wrapper not found: $STAGING_SSH"
    [[ "$(remote 'wp option get siteurl')" == "$STAGING_URL" ]] || die "remote siteurl is not $STAGING_URL"
    php -r 'json_decode(file_get_contents($argv[1]), false, 512, JSON_THROW_ON_ERROR);' "$THEME_SRC/theme.json"
    if [[ -n "$(git -C "$REPO_ROOT" status --porcelain -- wordpress/themes/$THEME_SLUG wordpress/plugins config/scf scripts/seed-content.php scripts/seed-appearance.php)" ]]; then
        warn "deploying uncommitted changes (theme, SCF definitions or seed script)"
    fi
}

backup() {
    log "Backup on the server (~/backups, not downloaded)"
    remote '
        B="$HOME/backups/cacdemo-staging-$(date +%Y%m%d-%H%M%S)"; mkdir -p "$B"; chmod 700 "$HOME/backups" "$B"
        # wp db export fails silently on this host; mysqldump needs --no-tablespaces.
        MYSQL_PWD="$(wp config get DB_PASSWORD)" mysqldump --no-tablespaces --single-transaction \
            -h "$(wp config get DB_HOST)" -u "$(wp config get DB_USER)" "$(wp config get DB_NAME)" > "$B/db.sql"
        chmod 600 "$B/db.sql"
        cp -a .htaccess "$B/htaccess"
        [[ -d wp-content/mu-plugins ]] && cp -a wp-content/mu-plugins "$B/mu-plugins"
        tar -czf "$B/plugins-themes.tgz" wp-content/plugins wp-content/themes
        echo "  $B ($(du -sh "$B" | cut -f1))"
    '
}

confirm_replace() {
    (( YES )) && return 0
    read -r -p "Remove all Hostinger plugins, the AI theme and ALL pages/posts/menus/template overrides on $STAGING_URL? [y/N] " a
    [[ "$a" == [yY] ]] || die "aborted"
}

# Runs after our theme is active, so nothing Hostinger's theme depends on is removed under it.
replace_hostinger() {
    log "Removing Hostinger plugins, must-use plugins, AI theme and generated content"
    remote '
        for f in wp-content/mu-plugins/hostinger-*.php; do if [[ -e "$f" ]]; then rm -f "$f"; echo "  removed must-use $f"; fi; done
        for p in $(wp plugin list --field=name --status=active,inactive | grep -E "^(hostinger|litespeed-cache)"); do
            wp plugin deactivate "$p" --quiet || true
            wp plugin uninstall "$p" --quiet && echo "  uninstalled plugin $p"
        done
        wp theme is-installed hostinger-ai-theme && wp theme delete hostinger-ai-theme --quiet && echo "  deleted theme hostinger-ai-theme"
        ids=$(wp post list --post_type=page,post,wp_navigation,wp_template,wp_template_part,wp_global_styles,wp_block --post_status=any --format=ids)
        [[ -n "$ids" ]] && wp post delete $ids --force --quiet && echo "  deleted content: $ids"
        wp option update wp_page_for_privacy_policy 0 --quiet
    '
}

deploy_theme() {
    log "Theme $THEME_SLUG"
    tar -C "$THEME_SRC" --exclude='.DS_Store' -czf - . | remote "
        T=wp-content/themes/$THEME_SLUG
        rm -rf \"\$T.new\"; mkdir -p \"\$T.new\"; tar -xzf - -C \"\$T.new\"
        rm -rf \"\$T.old\"; [[ -d \"\$T\" ]] && mv \"\$T\" \"\$T.old\"; mv \"\$T.new\" \"\$T\"; rm -rf \"\$T.old\"
        wp theme activate $THEME_SLUG --quiet
        echo \"  active: \$(wp theme list --status=active --field=name) \$(wp theme get $THEME_SLUG --field=version)\"
    "
}

deploy_own_plugins() {
    local slug
    while read -r slug; do
        log "Plugin $slug"
        tar -C "$REPO_ROOT/wordpress/plugins/$slug" -czf - . | remote "
            P=wp-content/plugins/$slug
            rm -rf \"\$P.new\"; mkdir -p \"\$P.new\"; tar -xzf - -C \"\$P.new\"
            rm -rf \"\$P.old\"; [[ -d \"\$P\" ]] && mv \"\$P\" \"\$P.old\"; mv \"\$P.new\" \"\$P\"; rm -rf \"\$P.old\"
            wp plugin is-active $slug || wp plugin activate $slug --quiet
            echo \"  $slug \$(wp plugin get $slug --field=version) active\"
        "
    done < <(own_plugins)
}

deploy_kit() {
    # scf-sync.php and seed-content.php resolve config/ and content-source/ relative to scripts/.
    local files=(scripts/scf-sync.php scripts/seed-content.php scripts/seed-ministries.php scripts/seed-channels.php scripts/seed-appearance.php scripts/import-sermons.php content-source/sermon-harvest/youtube/videos.json content-source/sermon-harvest/facebook/videos.json content-source/sermon-harvest/decisions.json content-source/sermon-harvest/enrichment.json config/scf config/plugins.txt)
    while read -r rel; do files+=("content-source/legacy-site/images/$rel"); done < <(
        grep -oE "'[a-z]+/[^']+__[0-9a-f]{8}\.(png|jpg)'" "$REPO_ROOT/scripts/seed-content.php" | tr -d "'")
    # Saved stills (git-ignored caches under <platform>/stills/YYYY/MM/) become featured images on import.
    local platform
    for platform in facebook youtube; do
        [[ -d "$REPO_ROOT/content-source/sermon-harvest/$platform/stills" ]] && files+=("content-source/sermon-harvest/$platform/stills")
    done
    tar -C "$REPO_ROOT" -czf - "${files[@]}" | "$STAGING_SSH" "rm -rf \"\$HOME/$KIT\" && mkdir -p \"\$HOME/$KIT\" && tar -xzf - -C \"\$HOME/$KIT\""
}

plugins() {
    log "Approved plugins (config/plugins.txt)"
    local slug
    while read -r slug; do
        remote "wp plugin is-installed $slug || wp plugin install $slug --quiet; wp plugin is-active $slug || wp plugin activate $slug --quiet; echo \"  $slug \$(wp plugin get $slug --field=version) active\"" </dev/null
    done < <(approved_plugins)
    log "SCF definitions"
    remote "wp eval-file \"\$HOME/$KIT/scripts/scf-sync.php\" import | sed 's/^/  /'"
}

seed() {
    log "Seed content (scripts/seed-content.php)"
    remote "wp eval-file \"\$HOME/$KIT/scripts/seed-content.php\" | sed 's/^/  /'"
    # After seed-content (pages and Main menu exist); both only add what is missing and never overwrite edits.
    log "Ministries and ways to serve (scripts/seed-ministries.php)"
    remote "wp eval-file \"\$HOME/$KIT/scripts/seed-ministries.php\" | { grep -v 'created role' || true; } | sed 's/^/  /'"
    log "Social channels (scripts/seed-channels.php)"
    remote "wp eval-file \"\$HOME/$KIT/scripts/seed-channels.php\" | sed 's/^/  /'"
    # After ministries and pages exist: generated placeholder art for heroes and cards, only where no image is set.
    seed_appearance
    log "Sermons from the Facebook and YouTube harvests (scripts/import-sermons.php)"
    remote "wp eval-file \"\$HOME/$KIT/scripts/import-sermons.php\" publish | tail -1 | sed 's/^/  /'; wp rewrite flush --quiet"
}

seed_appearance() {
    log "Placeholder images (scripts/seed-appearance.php)"
    remote "wp eval-file \"\$HOME/$KIT/scripts/seed-appearance.php\" | { grep -Ev 'exists|^created collection' || true; } | sed 's/^/  /'"
}

main() {
    preflight
    if (( REPLACE )); then confirm_replace; backup; fi
    deploy_kit
    plugins
    deploy_own_plugins
    deploy_theme
    if (( REPLACE )); then replace_hostinger; fi
    if (( SEED )); then seed; elif (( APPEARANCE )); then seed_appearance; fi
    remote "wp cache flush --quiet || true; rm -rf \"\$HOME/$KIT\""
    log "Done: $STAGING_URL — run scripts/verify-staging.sh"
}

main
