#!/usr/bin/env bash
#
# bootstrap-local.sh — build (or converge) the local cacdemo WordPress runtime.
#
# Idempotent: safe to re-run. Never deletes data. Stops if it finds a database
# or runtime it did not create. Secrets come from config/local.env or the
# environment; nothing secret is passed on a command line or printed.
#
# Requires: php, mariadb/mysql, wp (WP-CLI), nginx, passwordless sudo.
#
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

preflight() {
    log "Pre-flight"
    for cmd in php wp mysql nginx sudo curl tar sha1sum; do
        command -v "$cmd" >/dev/null || die "missing required command: $cmd"
    done
    sudo -n true 2>/dev/null || die "passwordless sudo is required (nginx, /etc/hosts, MariaDB root)"
    systemctl is-active --quiet mariadb || die "MariaDB is not running: sudo systemctl start mariadb"
    systemctl is-active --quiet nginx || die "nginx is not running"
    [[ -S "$PHP_FPM_SOCK" ]] || die "PHP-FPM socket not found: $PHP_FPM_SOCK"
    require_secret CACDEMO_DB_PASSWORD
    require_secret CACDEMO_ADMIN_PASSWORD
    require_secret CACDEMO_ADMIN_EMAIL
    export CACDEMO_DB_PASSWORD CACDEMO_ADMIN_PASSWORD CACDEMO_ADMIN_EMAIL
    [[ -f "$THEME_SRC/style.css" ]] || die "theme source missing: $THEME_SRC"
    case "$WP_ROOT" in
        "$REPO_ROOT"|"$REPO_ROOT"/*) die "WP_ROOT must be outside the Git repository" ;;
    esac

    local other
    other=$(grep -RlE"server_name[^;]*\b${HOSTNAME_LOCAL//./\\.}\b" /etc/nginx/sites-enabled/ 2>/dev/null \
        | xargs -r -n1 readlink -f | grep -v "/sites-available/${NGINX_SITE}$" || true)
    [[ -z "$other" ]] || die "another nginx site already serves $HOSTNAME_LOCAL: $other"
}

sql_escape() { printf '%s' "${1//\'/\'\'}"; }

database() {
    log "Database $DB_NAME"
    local exists tables
    exists=$(sudo -n mysql -N -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$DB_NAME'")
    if [[ "$exists" == "1" ]]; then
        tables=$(sudo -n mysql -N -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB_NAME'")
        if [[ "$tables" != "0" && ! -f "$WP_ROOT/wp-config.php" ]]; then
            die "database $DB_NAME already exists with $tables tables but no runtime at $WP_ROOT — ownership unknown, refusing to touch it"
        fi
        log "  exists ($tables tables)"
    else
        sudo -n mysql -e "CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        log "  created"
    fi
    sudo -n mysql <<SQL
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$(sql_escape "$CACDEMO_DB_PASSWORD")';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL
}

core() {
    log "WordPress core $WP_VERSION at $WP_ROOT"
    mkdir -p "$WP_ROOT"
    chgrp www-data "$WP_ROOT"
    chmod 750 "$WP_ROOT"
    local fresh=0
    if [[ ! -f "$WP_ROOT/wp-includes/version.php" ]]; then
        download_core
        fresh=1
    fi
    local have
    have=$(wpc core version)
    [[ "$have" == "$WP_VERSION" ]] || warn "runtime has WordPress $have, expected $WP_VERSION"
    if wpc core verify-checksums >/dev/null 2>&1; then
        log "  core verifies against WordPress.org checksums"
    elif (( fresh )); then
        die "freshly downloaded core does not verify against WordPress.org checksums"
    else
        warn "existing core does NOT verify against WordPress.org checksums (see: wp --path=$WP_ROOT core verify-checksums)"
    fi
}

# `wp core download` extracts with PharData, which truncates tar paths over 100
# characters (WordPress 7.1's wp-includes/php-ai-client has longer ones). Use the
# official tarball, verify its SHA-1, and extract with GNU tar instead.
download_core() {
    local tmp url="https://wordpress.org/wordpress-$WP_VERSION.tar.gz"
    tmp=$(mktemp -d)
    log "  downloading $url"
    curl -sSfL -o "$tmp/wordpress.tar.gz" "$url"
    [[ "$(curl -sSfL "$url.sha1")" == "$(sha1sum "$tmp/wordpress.tar.gz" | cut -d' ' -f1)" ]] \
        || { rm -rf "$tmp"; die "SHA-1 mismatch for $url"; }
    tar -xzf "$tmp/wordpress.tar.gz" -C "$tmp"
    cp -a "$tmp/wordpress/." "$WP_ROOT/"
    rm -rf "$tmp"
    chmod 750 "$WP_ROOT"  # cp -a copied the tarball root's 755 onto WP_ROOT
}

config() {
    if [[ -f "$WP_ROOT/wp-config.php" ]]; then
        log "wp-config.php exists — leaving it alone"
        return
    fi
    log "Creating wp-config.php"
    wpc config create --skip-check \
        --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass=__CACDEMO_DB_PASSWORD__ \
        --dbhost=localhost --dbprefix="$DB_PREFIX" --dbcharset=utf8mb4 \
        --extra-php <<'PHP' >/dev/null
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
PHP
    # Inject the password from the environment so it never appears in argv.
    php -r '
        $f = $argv[1];
        $pw = var_export(getenv("CACDEMO_DB_PASSWORD"), true);
        $c = str_replace("'"'"'__CACDEMO_DB_PASSWORD__'"'"'", $pw, file_get_contents($f));
        file_put_contents($f, $c);
    ' "$WP_ROOT/wp-config.php"
    grep -q "__CACDEMO_DB_PASSWORD__" "$WP_ROOT/wp-config.php" && die "failed to set DB_PASSWORD"
    # WP-CLI's template adds this; it only matters for persistent object caches, which we don't use.
    wpc config delete WP_CACHE_KEY_SALT >/dev/null 2>&1 || true
    wpc db check >/dev/null || die "cannot connect to $DB_NAME with the configured credentials"
}

permissions() {
    log "Runtime permissions (cris:www-data, wp-content group-writable)"
    chgrp -R -P www-data "$WP_ROOT" 2>/dev/null || sudo -n chgrp -R -P www-data "$WP_ROOT"
    mkdir -p "$WP_ROOT/wp-content/uploads"
    find "$WP_ROOT/wp-content" -path "$WP_ROOT/wp-content/themes/$THEME_SLUG" -prune -o -type d -print0 \
        | xargs -0 chmod g+ws 2>/dev/null || true
    find "$WP_ROOT/wp-content" -path "$WP_ROOT/wp-content/themes/$THEME_SLUG" -prune -o -type f -print0 \
        | xargs -0 chmod g+w 2>/dev/null || true
    chmod 640 "$WP_ROOT/wp-config.php"
}

webserver() {
    log "nginx vhost $NGINX_SITE ($HOSTNAME_LOCAL)"
    local rendered target="/etc/nginx/sites-available/$NGINX_SITE" changed=0
    rendered=$(mktemp)
    sed -e "s#__HOSTNAME__#$HOSTNAME_LOCAL#g" \
        -e "s#__WP_ROOT__#$WP_ROOT#g" \
        -e "s#__PHP_FPM_SOCK__#$PHP_FPM_SOCK#g" \
        "$REPO_ROOT/config/nginx/cacdemo.conf.template" > "$rendered"

    if ! sudo -n cmp -s "$rendered" "$target" 2>/dev/null; then
        [[ -f "$target" ]] && sudo -n cp "$target" "$target.bak.$(date +%Y%m%d-%H%M%S)"
        sudo -n install -m 644 "$rendered" "$target"
        changed=1
    fi
    rm -f "$rendered"

    if [[ "$(readlink -f "/etc/nginx/sites-enabled/$NGINX_SITE" 2>/dev/null)" != "$target" ]]; then
        sudo -n ln -sfn "$target" "/etc/nginx/sites-enabled/$NGINX_SITE"
        changed=1
    fi

    if (( changed )); then
        sudo -n nginx -t 2>&1 | sed 's/^/  /'
        sudo -n systemctl reload nginx
        log "  nginx reloaded"
    else
        log "  unchanged"
    fi

    if ! grep -qE "^[^#]*[[:space:]]${HOSTNAME_LOCAL//./\\.}([[:space:]]|$)" /etc/hosts; then
        echo "127.0.0.1 $HOSTNAME_LOCAL" | sudo -n tee -a /etc/hosts >/dev/null
        log "  added $HOSTNAME_LOCAL to /etc/hosts"
    fi
}

install_site() {
    if wpc core is-installed 2>/dev/null; then
        log "WordPress already installed"
    else
        log "Installing WordPress"
        # Throwaway generated password (output discarded), replaced from the environment below.
        wpc core install --url="$SITE_URL" --title="Christlikeness" \
            --admin_user="$ADMIN_USER" --admin_email="$CACDEMO_ADMIN_EMAIL" --skip-email >/dev/null
        CACDEMO_ADMIN_USER="$ADMIN_USER" wpc eval '
            $u = get_user_by( "login", getenv( "CACDEMO_ADMIN_USER" ) );
            wp_set_password( getenv( "CACDEMO_ADMIN_PASSWORD" ), $u->ID );
        ' >/dev/null
        # Clean baseline: remove WordPress's sample post, sample page, draft privacy
        # policy page and sample comment. Real content comes later.
        wpc post list --post_type=post,page --post_status=any --field=ID | xargs -r wp --path="$WP_ROOT" post delete --force >/dev/null
        wpc comment list --field=comment_ID | xargs -r wp --path="$WP_ROOT" comment delete --force >/dev/null
        wpc option update wp_page_for_privacy_policy 0 >/dev/null
    fi

    log "Site settings"
    wpc option update blogname "Christlikeness" >/dev/null
    wpc option update permalink_structure '/%postname%/' >/dev/null
    wpc rewrite flush >/dev/null
}

theme() {
    log "Theme $THEME_SLUG (source: $THEME_SRC)"
    local link="$WP_ROOT/wp-content/themes/$THEME_SLUG"
    if [[ -e "$link" && ! -L "$link" ]]; then
        die "$link is a real directory — the runtime must symlink to the repository, not hold a copy"
    fi
    # THEME_SRC is a physical path (pwd -P). Linking through /home/cris/... breaks
    # because PHP-FPM (www-data) cannot traverse /home/cris.
    ln -sfn "$THEME_SRC" "$link"
    sudo -n -u www-data test -r "$link/style.css" \
        || die "www-data cannot read the theme through $link — check directory permissions on the path to $THEME_SRC"
    [[ "$(wpc theme list --status=active --field=name)" == "$THEME_SLUG" ]] || wpc theme activate "$THEME_SLUG" >/dev/null
}

plugins() {
    log "Approved plugins (config/plugins.txt)"
    local slug
    while read -r slug; do
        if wpc plugin is-installed "$slug"; then
            wpc plugin is-active "$slug" || wpc plugin activate "$slug" >/dev/null
        else
            wpc plugin install "$slug" --activate >/dev/null
        fi
        log "  $slug $(wpc plugin get "$slug" --field=version) active"
    done < <(approved_plugins)
}

own_plugins_step() {
    local slug link
    while read -r slug; do
        log "Plugin $slug (source: wordpress/plugins/$slug)"
        link="$WP_ROOT/wp-content/plugins/$slug"
        [[ -e "$link" && ! -L "$link" ]] && die "$link is a real directory — the runtime must symlink to the repository"
        ln -sfn "$REPO_ROOT/wordpress/plugins/$slug" "$link"
        sudo -n -u www-data test -r "$link/$slug.php" || die "www-data cannot read $link/$slug.php"
        wpc plugin is-active "$slug" || wpc plugin activate "$slug" >/dev/null
    done < <(own_plugins)
}

scf_definitions() {
    wpc plugin is-active secure-custom-fields || return 0
    log "Secure Custom Fields definitions (config/scf)"
    wpc eval-file "$REPO_ROOT/scripts/scf-sync.php" import | sed 's/^/  /'
}

main() {
    preflight
    database
    core
    config
    permissions
    webserver
    install_site
    theme
    plugins
    scf_definitions
    own_plugins_step
    permissions
    log "Done: $SITE_URL  (admin: $SITE_URL/wp-admin/, user $ADMIN_USER)"
    log "Run scripts/test.sh to verify."
}

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
    main "$@"
fi
