<?php
/**
 * Site themes: curated presentation packages chosen by administrators (docs/VISUAL-THEMES-DESIGN.md §4–6).
 *
 * A package is a folder site-themes/<id>/ with package.php (manifest) and optional style.css, effects and small decorative
 * assets. "default" is a package like any other. Nothing outside this layer knows individual themes: templates and
 * components read tokens (CSS variables), resolved images and the <html> attributes printed here.
 *
 * Resolution for a request (cacdemo_site_theme_resolve()):
 *   1. preview  ?site_theme_preview=<id> — only for users who can edit theme options; never cached
 *   2. schedule an entry whose window contains "now"; when windows overlap, the one that started last wins
 *   3. everyday the administrator's everyday theme
 *   4. fallback "default" (also used when a stored theme no longer exists)
 * Palette: visitor choice (browser storage) → the theme's recommended palette → "default". The site theme never sets colours.
 *
 * Stored state (option cacdemo_site_theme): everyday theme and effects, schedule entries, image slots per theme.
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_SITE_THEME_OPTION   = 'cacdemo_site_theme';
const CACDEMO_SITE_THEME_TYPES    = array( 'base', 'natural-season', 'church-occasion', 'biblical-observance', 'program' );
const CACDEMO_SITE_THEME_TOKENS   = array( 'decor', 'hero-overlay', 'hero-overlay-mobile', 'texture', 'hero-mark' );
const CACDEMO_EFFECT_INTENSITIES  = array( 'off', 'subtle', 'enhanced' );

/* ---------------------------------------------------------------- Packages */

/** All packages keyed by id. Invalid manifests are skipped; "default" always exists. */
function cacdemo_site_theme_packages() {
	if ( isset( $GLOBALS['cacdemo_site_theme_packages'] ) ) {
		return $GLOBALS['cacdemo_site_theme_packages'];
	}
	$packages = array();
	foreach ( glob( get_theme_file_path( 'site-themes/*/package.php' ) ) ?: array() as $file ) {
		$id       = basename( dirname( $file ) );
		$manifest = include $file;
		$package  = is_array( $manifest ) ? cacdemo_site_theme_normalize( $id, $manifest, dirname( $file ) ) : null;
		if ( $package ) {
			$packages[ $id ] = $package;
		}
	}
	uasort( $packages, fn( $a, $b ) => array( $a['order'], $a['name'] ) <=> array( $b['order'], $b['name'] ) );
	$packages = apply_filters( 'cacdemo_site_theme_packages', $packages );
	if ( ! isset( $packages['default'] ) ) {
		$packages = array( 'default' => cacdemo_site_theme_normalize( 'default', array( 'name' => 'Default', 'type' => 'base' ), '' ) ) + $packages;
	}
	return $GLOBALS['cacdemo_site_theme_packages'] = $packages;
}

/** Clears the per-request caches (tests register fixture packages through the filter). */
function cacdemo_site_theme_reset_caches() {
	$GLOBALS['cacdemo_site_theme_current']  = null;
	unset( $GLOBALS['cacdemo_site_theme_packages'] );
}

/**
 * A manifest with safe values only, or null.
 * Keys: name, type, description, palette, tokens, slots, effects ( effect => intensities ), content_rules, order (lists).
 */
function cacdemo_site_theme_normalize( $id, $manifest, $dir ) {
	if ( ! preg_match( '/^[a-z][a-z0-9-]*$/', $id ) || empty( $manifest['name'] ) || ! in_array( $manifest['type'] ?? '', CACDEMO_SITE_THEME_TYPES, true ) ) {
		return null;
	}
	$tokens = array();
	foreach ( (array) ( $manifest['tokens'] ?? array() ) as $token => $value ) {
		// Values are curated code, but still kept to a safe character set (no braces, semicolons or tags).
		if ( in_array( $token, CACDEMO_SITE_THEME_TOKENS, true ) && is_string( $value ) && ! preg_match( '/[{};<>]/', $value ) ) {
			$tokens[ $token ] = $value;
		}
	}
	$effects = array();
	foreach ( (array) ( $manifest['effects'] ?? array() ) as $effect => $intensities ) {
		$allowed = array_values( array_intersect( (array) $intensities, array( 'subtle', 'enhanced' ) ) );
		if ( preg_match( '/^[a-z]+$/', (string) $effect ) && $allowed ) {
			$effects[ $effect ] = $allowed;
		}
	}
	$style = $dir && is_readable( $dir . '/style.css' ) ? $dir . '/style.css' : '';
	return array(
		'id'            => $id,
		'name'          => (string) $manifest['name'],
		'type'          => $manifest['type'],
		'description'   => (string) ( $manifest['description'] ?? '' ),
		'palette'       => cacdemo_palette_valid_id( $manifest['palette'] ?? 'default' ),
		'tokens'        => $tokens,
		'slots'         => array_values( array_filter( (array) ( $manifest['slots'] ?? array() ), fn( $s ) => is_string( $s ) && preg_match( '/^[a-z][a-z0-9-]*$/', $s ) ) ),
		'effects'       => $effects,
		'content_rules' => (string) ( $manifest['content_rules'] ?? '' ),
		'order'         => (int) ( $manifest['order'] ?? 50 ),
		'style'         => $style,
		'dir'           => $dir,
	);
}

function cacdemo_site_theme_package( $id ) {
	return cacdemo_site_theme_packages()[ $id ] ?? null;
}

/** Image slots every theme may fill; a package can add its own. */
function cacdemo_site_theme_base_slots() {
	return array( 'home-hero', 'page-hero', 'ministries-hero', 'sermons-hero', 'fallback' );
}

/* ---------------------------------------------------------------- Stored state */

function cacdemo_site_theme_default_state() {
	return array( 'everyday' => 'default', 'effects' => 'subtle', 'schedule' => array(), 'images' => array() );
}

/** The stored state with every value validated (unknown themes and broken entries are kept out of resolution). */
function cacdemo_site_theme_state() {
	return cacdemo_site_theme_sanitize_state( get_option( CACDEMO_SITE_THEME_OPTION, array() ) );
}

function cacdemo_site_theme_sanitize_state( $state ) {
	$state = is_array( $state ) ? $state : array();
	$clean = cacdemo_site_theme_default_state();

	$clean['everyday'] = is_string( $state['everyday'] ?? null ) ? sanitize_key( $state['everyday'] ) : 'default';
	$clean['effects']  = in_array( $state['effects'] ?? '', CACDEMO_EFFECT_INTENSITIES, true ) ? $state['effects'] : 'subtle';

	foreach ( (array) ( $state['schedule'] ?? array() ) as $entry ) {
		$entry = cacdemo_site_theme_sanitize_entry( $entry );
		if ( $entry ) {
			$clean['schedule'][] = $entry;
		}
	}
	foreach ( (array) ( $state['images'] ?? array() ) as $theme => $slots ) {
		foreach ( (array) $slots as $slot => $attachment ) {
			if ( preg_match( '/^[a-z][a-z0-9-]*$/', (string) $theme ) && preg_match( '/^[a-z][a-z0-9-]*$/', (string) $slot ) && absint( $attachment ) ) {
				$clean['images'][ $theme ][ $slot ] = absint( $attachment );
			}
		}
	}
	return $clean;
}

/** A schedule entry: id, theme, starts, ends ("Y-m-d\TH:i", site time), effects. Null if unusable. */
function cacdemo_site_theme_sanitize_entry( $entry ) {
	if ( ! is_array( $entry ) ) {
		return null;
	}
	$starts = cacdemo_site_theme_parse_time( $entry['starts'] ?? '' );
	$ends   = cacdemo_site_theme_parse_time( $entry['ends'] ?? '' );
	$theme  = sanitize_key( (string) ( $entry['theme'] ?? '' ) );
	if ( ! $starts || ! $ends || $ends <= $starts || '' === $theme ) {
		return null;
	}
	return array(
		'id'      => preg_match( '/^[a-z0-9]{6,20}$/', (string) ( $entry['id'] ?? '' ) ) ? $entry['id'] : strtolower( wp_generate_password( 10, false ) ),
		'theme'   => $theme,
		'starts'  => $starts->format( 'Y-m-d\TH:i' ),
		'ends'    => $ends->format( 'Y-m-d\TH:i' ),
		'effects' => in_array( $entry['effects'] ?? '', CACDEMO_EFFECT_INTENSITIES, true ) ? $entry['effects'] : 'subtle',
	);
}

/** "Y-m-d\TH:i" or "Y-m-d H:i" in the site timezone → DateTimeImmutable, or null. */
function cacdemo_site_theme_parse_time( $value ) {
	$value = str_replace( ' ', 'T', trim( (string) $value ) );
	$time  = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, wp_timezone() );
	return $time && $time->format( 'Y-m-d\TH:i' ) === $value ? $time : null;
}

/* ---------------------------------------------------------------- Resolution */

/**
 * Which theme presents the site at $now. Pure: no globals, requests or database access, so it is directly testable.
 *
 * @param array             $state   Sanitized state (cacdemo_site_theme_sanitize_state()).
 * @param DateTimeInterface $now     The moment to resolve for.
 * @param string|null       $preview A theme id to preview (caller has already checked permission).
 * @param string|null       $effects_preview An intensity to preview.
 * @return array theme, source (preview|schedule|everyday|fallback), entry, until (DateTimeImmutable|null), effects, palette.
 */
function cacdemo_site_theme_resolve( $state, $now, $preview = null, $effects_preview = null ) {
	$packages = cacdemo_site_theme_packages();
	$result   = array( 'theme' => 'default', 'source' => 'fallback', 'entry' => null, 'until' => null, 'effects' => $state['effects'] ?? 'subtle' );

	if ( $preview && isset( $packages[ $preview ] ) ) {
		$result = array_merge( $result, array( 'theme' => $preview, 'source' => 'preview' ) );
	} else {
		$active = null;
		foreach ( $state['schedule'] ?? array() as $entry ) {
			if ( ! isset( $packages[ $entry['theme'] ] ) ) {
				continue; // A scheduled theme that no longer exists is ignored.
			}
			$starts = cacdemo_site_theme_parse_time( $entry['starts'] );
			$ends   = cacdemo_site_theme_parse_time( $entry['ends'] );
			if ( ! $starts || ! $ends || $now < $starts || $now >= $ends ) {
				continue;
			}
			// Overlaps: the entry that started last wins (an anniversary week inside a season); ties go to the shorter window.
			if ( ! $active || $starts > $active[1] || ( $starts == $active[1] && $ends < $active[2] ) ) { // phpcs:ignore Universal.Operators.StrictComparisons -- DateTime comparison.
				$active = array( $entry, $starts, $ends );
			}
		}
		if ( $active ) {
			$result = array_merge( $result, array( 'theme' => $active[0]['theme'], 'source' => 'schedule', 'entry' => $active[0], 'until' => $active[2], 'effects' => $active[0]['effects'] ) );
		} elseif ( isset( $packages[ $state['everyday'] ?? '' ] ) ) {
			$result = array_merge( $result, array( 'theme' => $state['everyday'], 'source' => 'everyday' ) );
		}
	}

	if ( $effects_preview && in_array( $effects_preview, CACDEMO_EFFECT_INTENSITIES, true ) ) {
		$result['effects'] = $effects_preview;
	}
	// The effect the package declares (one per package), at an intensity it supports; otherwise nothing moves.
	$declared         = $packages[ $result['theme'] ]['effects'];
	$result['effect'] = $declared ? (string) array_key_first( $declared ) : '';
	if ( ! $declared || ( 'off' !== $result['effects'] && ! in_array( $result['effects'], reset( $declared ), true ) ) ) {
		$result['effects'] = $declared && 'off' !== $result['effects'] ? reset( $declared )[0] : 'off';
	}
	if ( 'off' === $result['effects'] ) {
		$result['effect'] = '';
	}
	$result['palette'] = $packages[ $result['theme'] ]['palette'];
	return $result;
}

/** The current moment for resolution (a fixed moment can be forced locally with CACDEMO_SITE_THEME_NOW). */
function cacdemo_site_theme_now() {
	if ( defined( 'CACDEMO_SITE_THEME_NOW' ) && ( $forced = cacdemo_site_theme_parse_time( CACDEMO_SITE_THEME_NOW ) ) ) {
		return $forced;
	}
	return new DateTimeImmutable( 'now', wp_timezone() );
}

/** Whether this request may preview themes and palettes. */
function cacdemo_site_theme_can_preview() {
	return is_user_logged_in() && current_user_can( 'edit_theme_options' );
}

/** Resolution for the current request, including preview parameters and the local force constant. */
function cacdemo_site_theme_current() {
	if ( ! empty( $GLOBALS['cacdemo_site_theme_current'] ) ) {
		return $GLOBALS['cacdemo_site_theme_current'];
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only preview switches, limited to theme editors.
	$preview         = null;
	$effects_preview = null;
	$palette_preview = null;
	if ( defined( 'CACDEMO_SITE_THEME_FORCE' ) && is_string( CACDEMO_SITE_THEME_FORCE ) ) {
		$preview = sanitize_key( CACDEMO_SITE_THEME_FORCE );
	}
	if ( isset( $_GET['site_theme_preview'] ) || isset( $_GET['palette_preview'] ) || isset( $_GET['effects_preview'] ) ) {
		if ( cacdemo_site_theme_can_preview() ) {
			$preview         = isset( $_GET['site_theme_preview'] ) ? sanitize_key( wp_unslash( $_GET['site_theme_preview'] ) ) : $preview;
			$effects_preview = isset( $_GET['effects_preview'] ) ? sanitize_key( wp_unslash( $_GET['effects_preview'] ) ) : null;
			$palette_preview = isset( $_GET['palette_preview'] ) ? sanitize_key( wp_unslash( $_GET['palette_preview'] ) ) : null;
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$resolved = cacdemo_site_theme_resolve( cacdemo_site_theme_state(), cacdemo_site_theme_now(), $preview, $effects_preview );
	$resolved['palette_forced'] = $palette_preview && isset( cacdemo_selectable_palettes()[ $palette_preview ] ) ? $palette_preview : null;
	$resolved['previewing']     = 'preview' === $resolved['source'] || $resolved['palette_forced'] || $effects_preview;
	return $GLOBALS['cacdemo_site_theme_current'] = $resolved;
}

/* ---------------------------------------------------------------- Images */

add_filter( 'cacdemo_theme_image', 'cacdemo_site_theme_slot_image', 10, 2 );

/**
 * Answers the plugin's image resolver (includes/media.php) for a slot: the active theme's image for that slot, then the
 * Default theme's, then each one's "fallback" slot. Images are Media Library attachments chosen in Appearance → Site Theme.
 */
function cacdemo_site_theme_slot_image( $id, $slot ) {
	if ( $id ) {
		return $id;
	}
	$state   = cacdemo_site_theme_state();
	$theme   = cacdemo_site_theme_applies() ? cacdemo_site_theme_current()['theme'] : cacdemo_site_theme_resolve( $state, cacdemo_site_theme_now() )['theme'];
	$chain   = array( array( $theme, $slot ), array( 'default', $slot ), array( $theme, 'fallback' ), array( 'default', 'fallback' ) );
	foreach ( $chain as $step ) {
		$attachment = (int) ( $state['images'][ $step[0] ][ $step[1] ] ?? 0 );
		if ( $attachment && wp_attachment_is_image( $attachment ) ) {
			return $attachment;
		}
	}
	return 0;
}

/* ---------------------------------------------------------------- Public output */

/** Public site pages only: not wp-admin, the login screen, the Content Manager, feeds or REST. */
function cacdemo_site_theme_applies() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() || did_action( 'login_init' ) ) {
		return false;
	}
	return ! ( function_exists( 'cacdemo_is_manage' ) && cacdemo_is_manage() );
}

add_filter( 'language_attributes', 'cacdemo_site_theme_html_attributes', 10, 2 );

function cacdemo_site_theme_html_attributes( $output, $doctype ) {
	if ( 'html' !== $doctype || ! did_action( 'wp' ) || ! cacdemo_site_theme_applies() ) {
		return $output;
	}
	$current = cacdemo_site_theme_current();
	$palette = $current['palette_forced'] ?: $current['palette'];
	$attrs   = array(
		'data-site-theme'      => $current['theme'],
		'data-palette'         => $palette,
		'data-palette-default' => $current['palette'],
		'data-effects'         => $current['effects'],
	);
	if ( $current['effect'] ) {
		$attrs['data-effect'] = $current['effect'];
	}
	if ( $current['palette_forced'] ) {
		$attrs['data-palette-forced'] = '1';
	}
	foreach ( $attrs as $name => $value ) {
		$output .= sprintf( ' %s="%s"', $name, esc_attr( $value ) );
	}
	return $output;
}

add_action( 'wp_head', 'cacdemo_site_theme_head', 0 );

/**
 * Before any stylesheet: apply the visitor's saved palette (so the default never flashes first), then the palette and
 * theme token rules. Invalid or removed saved values are cleared and the theme's recommended palette stays.
 */
function cacdemo_site_theme_head() {
	if ( ! cacdemo_site_theme_applies() ) {
		return;
	}
	$current  = cacdemo_site_theme_current();
	$allowed  = array_keys( cacdemo_selectable_palettes() );
	$script   = '(function(r){try{if(r.hasAttribute("data-palette-forced"))return;var k="cacdemo:palette",v=window.localStorage.getItem(k),a=' . wp_json_encode( $allowed ) . ';'
		. 'if(!v)return;if(a.indexOf(v)<0){window.localStorage.removeItem(k);return;}r.setAttribute("data-palette",v);}catch(e){}})(document.documentElement);';
	printf( "<script id=\"cacdemo-palette-init\">%s</script>\n", $script ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static script with JSON-encoded ids.

	$css     = cacdemo_palette_css();
	$package = cacdemo_site_theme_package( $current['theme'] );
	if ( $package['tokens'] ) {
		$vars = array();
		foreach ( $package['tokens'] as $token => $value ) {
			$vars[] = "--wp--custom--{$token}:{$value}";
		}
		$css .= ':root[data-site-theme="' . $package['id'] . '"]{' . implode( ';', $vars ) . '}';
	}
	if ( $css ) {
		printf( "<style id=\"cacdemo-appearance-tokens\">%s</style>\n", wp_strip_all_tags( $css ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS built from validated palette/package values.
	}
}

add_action( 'wp_enqueue_scripts', 'cacdemo_site_theme_assets' );

function cacdemo_site_theme_assets() {
	if ( ! cacdemo_site_theme_applies() ) {
		return;
	}
	$current = cacdemo_site_theme_current();
	$package = cacdemo_site_theme_package( $current['theme'] );
	if ( $package['style'] ) {
		$rel = 'site-themes/' . $package['id'] . '/style.css';
		wp_enqueue_style( 'cacdemo-site-theme', get_theme_file_uri( $rel ), array(), (string) filemtime( $package['style'] ) );
	}
	if ( $current['effect'] ) { // Only pages that can show an effect load it.
		wp_enqueue_script( 'cacdemo-effects', get_theme_file_uri( 'assets/js/effects.js' ), array(), (string) filemtime( get_theme_file_path( 'assets/js/effects.js' ) ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_enqueue_style( 'cacdemo-effects', get_theme_file_uri( 'assets/css/effects.css' ), array(), (string) filemtime( get_theme_file_path( 'assets/css/effects.css' ) ) );
		wp_localize_script( 'cacdemo-effects', 'cacdemoEffects', array(
			'pause'  => __( 'Pause animation', 'cacdemo' ),
			'resume' => __( 'Play animation', 'cacdemo' ),
		) );
	}
	wp_enqueue_script( 'cacdemo-appearance', get_theme_file_uri( 'assets/js/appearance.js' ), array(), (string) filemtime( get_theme_file_path( 'assets/js/appearance.js' ) ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_enqueue_style( 'cacdemo-appearance', get_theme_file_uri( 'assets/css/appearance.css' ), array(), (string) filemtime( get_theme_file_path( 'assets/css/appearance.css' ) ) );
}

add_action( 'send_headers', 'cacdemo_site_theme_freshness_headers' );

/**
 * Public HTML must be revalidated: the presentation is resolved per request (schedules end by themselves, content changes)
 * and some hosts add a long default expiry to every response (Hostinger's .htaccess has `ExpiresDefault "access plus 1
 * weeks"`). Apache's mod_expires leaves responses alone when the application already set Expires, so sending both headers
 * here wins without editing server files. Static assets keep their long cache (they are versioned by file time).
 */
function cacdemo_site_theme_freshness_headers() {
	if ( is_admin() || headers_sent() ) {
		return;
	}
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
}

add_action( 'template_redirect', 'cacdemo_site_theme_preview_headers' );

/** Previews are never cached or indexed. */
function cacdemo_site_theme_preview_headers() {
	if ( cacdemo_site_theme_applies() && cacdemo_site_theme_current()['previewing'] ) {
		nocache_headers();
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
	}
}

add_action( 'admin_bar_menu', 'cacdemo_site_theme_admin_bar', 90 );

/** While previewing, the admin bar says so and offers a way out. */
function cacdemo_site_theme_admin_bar( $bar ) {
	if ( is_admin() || ! cacdemo_site_theme_applies() || ! cacdemo_site_theme_can_preview() ) {
		return;
	}
	$current = cacdemo_site_theme_current();
	if ( ! $current['previewing'] ) {
		return;
	}
	$label = cacdemo_site_theme_package( $current['theme'] )['name'];
	if ( $current['palette_forced'] ) {
		$label .= ' · ' . cacdemo_palette( $current['palette_forced'] )['name'];
	}
	/* translators: %s: theme (and palette) being previewed */
	$bar->add_node( array( 'id' => 'cacdemo-site-theme-preview', 'title' => esc_html( sprintf( __( 'Previewing: %s', 'cacdemo' ), $label ) ), 'href' => admin_url( 'themes.php?page=cacdemo-site-theme' ) ) );
	$bar->add_node( array( 'id' => 'cacdemo-site-theme-exit', 'parent' => 'cacdemo-site-theme-preview', 'title' => esc_html__( 'Exit preview', 'cacdemo' ), 'href' => remove_query_arg( array( 'site_theme_preview', 'palette_preview', 'effects_preview' ) ) ) );
}
