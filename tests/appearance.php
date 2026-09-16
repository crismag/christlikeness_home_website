<?php
/**
 * Appearance checks that change nothing (run by the routine scripts/test.sh):
 * palettes and contrast, site-theme packages, resolution (everyday, schedule, overlaps, boundaries, preview, fallbacks),
 * state sanitising, and what visitors receive.
 *
 *   wp --path=/mnt/ai/workspaces/cacdemo eval-file tests/appearance.php
 *
 * Fixture packages are added in memory through the cacdemo_site_theme_packages filter; nothing is stored.
 */

defined( 'WP_CLI' ) || exit;

$results = array( 'pass' => 0, 'fail' => 0 );
$check   = function ( $label, $ok ) use ( &$results ) {
	$results[ $ok ? 'pass' : 'fail' ]++;
	WP_CLI::log( ( $ok ? "  \033[32mPASS\033[0m " : "  \033[31mFAIL\033[0m " ) . $label );
};
$at = fn( $value ) => cacdemo_site_theme_parse_time( $value );

WP_CLI::log( 'Palettes and contrast' );
$palettes = cacdemo_palettes();
$files    = glob( get_theme_file_path( 'palettes/*.json' ) );
$check( 'every palette file is valid (' . count( $files ) . ' files)', count( $files ) === count( $palettes ) );
foreach ( $palettes as $id => $palette ) {
	$failures = array_filter( cacdemo_palette_contrast_report( $palette ), fn( $row ) => ! $row[5] );
	$lowest   = min( array_map( fn( $row ) => $row[3] / $row[4], cacdemo_palette_contrast_report( $palette ) ) );
	$check( sprintf( 'palette "%s" passes all %d contrast pairs (closest to its minimum: %.2f×)', $id, count( cacdemo_palette_contrast_pairs() ), $lowest ), ! $failures );
	foreach ( $failures as $row ) {
		WP_CLI::log( sprintf( '         %s: %s on %s = %.2f (needs %.1f)', $row[0], $row[1], $row[2], $row[3], $row[4] ) );
	}
}
$check( 'every palette is offered to visitors', count( cacdemo_selectable_palettes() ) === count( $palettes ) );
$theme_json = json_decode( file_get_contents( get_theme_file_path( 'theme.json' ) ), true );
$presets    = array_column( $theme_json['settings']['color']['palette'], 'color', 'slug' );
$default    = cacdemo_palette( 'default' );
$check( 'the default palette matches theme.json exactly (no drift)', $default
	&& array_map( 'strtoupper', $presets ) === $default['colors']
	&& strtoupper( $theme_json['settings']['custom']['highlight'] ) === $default['custom']['highlight']
	&& strtoupper( $theme_json['settings']['custom']['highlightInverse'] ) === $default['custom']['highlight-inverse'] );
$check( 'palette CSS contains a rule for each non-default palette and none for default', substr_count( cacdemo_palette_css(), ':root[data-palette=' ) === count( $palettes ) - 1 && ! str_contains( cacdemo_palette_css(), 'data-palette="default"' ) );
$check( 'unknown palette ids fall back to default', 'default' === cacdemo_palette_valid_id( 'neon' ) && 'default' === cacdemo_palette_valid_id( null ) && 'navy' === cacdemo_palette_valid_id( 'navy' ) );
$check( 'a palette with a malformed colour is rejected', null === cacdemo_palette_normalize( 'bad', array( 'name' => 'Bad', 'colors' => array_fill_keys( CACDEMO_PALETTE_ROLES, 'red' ), 'custom' => array( 'highlight' => '#000000', 'highlightInverse' => '#FFFFFF' ) ) ) );

WP_CLI::log( "\nPackages" );
$packages = cacdemo_site_theme_packages();
$check( 'Default is a real package (type base, default palette, image slots, no effects)', isset( $packages['default'] ) && 'base' === $packages['default']['type'] && 'default' === $packages['default']['palette'] && in_array( 'home-hero', $packages['default']['slots'], true ) && ! $packages['default']['effects'] );
$check( 'manifests with an unknown type are rejected', null === cacdemo_site_theme_normalize( 'x', array( 'name' => 'X', 'type' => 'christmas' ), '' ) );
$unsafe = cacdemo_site_theme_normalize( 'x', array( 'name' => 'X', 'type' => 'program', 'palette' => 'gone', 'tokens' => array( 'decor' => 'red}body{display:none', 'texture' => 'none', 'color' => '#000' ), 'effects' => array( 'leaves' => array( 'wild', 'subtle' ) ) ), '' );
$check( 'unsafe token values, unknown tokens, unknown intensities and missing palettes are dropped', $unsafe && array( 'texture' => 'none' ) === $unsafe['tokens'] && array( 'leaves' => array( 'subtle' ) ) === $unsafe['effects'] && 'default' === $unsafe['palette'] );

$offered = (array) apply_filters( 'cacdemo_theme_image_slots', array() );
$check( 'editors can point a page hero at any theme image slot, by name', array( 'home-hero', 'page-hero', 'ministries-hero', 'sermons-hero', 'fallback' ) === array_keys( $offered ) && 'Home page hero' === $offered['home-hero'] );
$seasons = array( 'spring', 'summer', 'fall', 'winter' );
$check( 'Spring, Summer, Fall and Winter are natural-season packages, listed in season order after Default', array( 'default', 'spring', 'summer', 'fall', 'winter' ) === array_slice( array_keys( $packages ), 0, 5 ) && ! array_filter( $seasons, fn( $id ) => 'natural-season' !== $packages[ $id ]['type'] ) );
$complete = true;
foreach ( $seasons as $id ) {
	$pk        = $packages[ $id ];
	$complete  = $complete && 5 === count( $pk['tokens'] ) && $pk['style'] && $pk['palette'] !== 'default' && cacdemo_palette_passes( cacdemo_palette( $pk['palette'] ) )
		&& is_readable( $pk['dir'] . '/mark.svg' ) && is_readable( $pk['dir'] . '/texture.svg' ) && array( 'home-hero', 'ministries-hero', 'sermons-hero', 'fallback' ) === $pk['slots'];
}
$check( 'each season has all five atmosphere tokens, a scoped style, its mark and texture, image slots and a recommended palette that passes contrast', $complete );
$winter_text = strtolower( $packages['winter']['name'] . ' ' . $packages['winter']['description'] . ' ' . file_get_contents( $packages['winter']['dir'] . '/mark.svg' ) . ' ' . file_get_contents( $packages['winter']['dir'] . '/texture.svg' ) . ' ' . file_get_contents( $packages['winter']['dir'] . '/style.css' ) );
$check( 'Winter is the natural season only: no Christmas words in what visitors see, and its rules say so', ! preg_match( '/christmas|xmas|nativity|santa|ornament|holiday|star/', $winter_text ) && str_contains( $packages['winter']['content_rules'], 'No Christmas' ) );
// Hero text contrast, worst case: a white image under the overlay's text-side stop (night mixed with the season tint, 90%; phones 93%).
$mix = function ( $a, $b, $t ) {
	$a = array_map( 'hexdec', str_split( ltrim( $a, '#' ), 2 ) );
	$b = array_map( 'hexdec', str_split( ltrim( $b, '#' ), 2 ) );
	return sprintf( '#%02X%02X%02X', ...array_map( fn( $i ) => (int) round( $a[ $i ] * $t + $b[ $i ] * ( 1 - $t ) ), array( 0, 1, 2 ) ) );
};
$worst = 99;
foreach ( $seasons as $id ) {
	preg_match( '/night\) 82%, (#[0-9A-F]{6})\) 90%/', $packages[ $id ]['tokens']['hero-overlay'], $tint );
	foreach ( cacdemo_palettes() as $palette ) {
		$under = $mix( $mix( $palette['colors']['night'], $tint[1] ?? '#FFFFFF', 0.82 ), '#FFFFFF', 0.90 );
		$worst = min( $worst, cacdemo_contrast_ratio( $palette['colors']['paper'], $under ) );
	}
}
$check( sprintf( 'hero text stays readable over any image in every season and palette (worst case %.1f:1, needs 4.5)', $worst ), $worst >= 4.5 );

// In-memory fixtures (removed below).
$fixtures = function ( $packages ) {
	$packages['test-season']   = cacdemo_site_theme_normalize( 'test-season', array( 'name' => 'Test season', 'type' => 'natural-season', 'palette' => 'sage', 'effects' => array( 'leaves' => array( 'subtle', 'enhanced' ) ) ), '' );
	$packages['test-occasion'] = cacdemo_site_theme_normalize( 'test-occasion', array( 'name' => 'Test occasion', 'type' => 'church-occasion', 'palette' => 'gold' ), '' );
	return $packages;
};
add_filter( 'cacdemo_site_theme_packages', $fixtures );
cacdemo_site_theme_reset_caches();

WP_CLI::log( "\nResolution" );
$state = cacdemo_site_theme_sanitize_state( array() );
$r     = cacdemo_site_theme_resolve( $state, $at( '2026-10-01T12:00' ) );
$check( 'no stored state resolves to Default as the everyday theme', 'default' === $r['theme'] && 'everyday' === $r['source'] && 'default' === $r['palette'] && 'off' === $r['effects'] );

$state = cacdemo_site_theme_sanitize_state( array(
	'everyday' => 'test-season',
	'effects'  => 'enhanced',
	'schedule' => array(
		array( 'id' => 'occasion01', 'theme' => 'test-occasion', 'starts' => '2026-10-12T00:00', 'ends' => '2026-10-20T00:00', 'effects' => 'off' ),
		array( 'id' => 'season0001', 'theme' => 'default', 'starts' => '2026-10-01T00:00', 'ends' => '2026-11-01T00:00', 'effects' => 'subtle' ),
		array( 'id' => 'ghost00001', 'theme' => 'removed-theme', 'starts' => '2026-12-01T00:00', 'ends' => '2027-01-01T00:00' ),
		array( 'id' => 'broken0001', 'theme' => 'default', 'starts' => '2026-12-10T00:00', 'ends' => '2026-12-01T00:00' ),
	),
) );
$check( 'sanitising drops entries whose end is not after their start', 3 === count( $state['schedule'] ) );
$r = cacdemo_site_theme_resolve( $state, $at( '2026-09-30T23:59' ) );
$check( 'outside every window the everyday theme shows, with its palette and effects', 'test-season' === $r['theme'] && 'everyday' === $r['source'] && 'sage' === $r['palette'] && 'enhanced' === $r['effects'] );
$r = cacdemo_site_theme_resolve( $state, $at( '2026-10-01T00:00' ) );
$check( 'a scheduled window starts exactly at its start time', 'default' === $r['theme'] && 'schedule' === $r['source'] && 'off' === $r['effects'] );
$r = cacdemo_site_theme_resolve( $state, $at( '2026-10-15T09:00' ) );
$check( 'overlapping windows: the one that started later wins, until its end', 'test-occasion' === $r['theme'] && 'occasion01' === $r['entry']['id'] && '2026-10-20T00:00' === $r['until']->format( 'Y-m-d\TH:i' ) && 'gold' === $r['palette'] );
$r = cacdemo_site_theme_resolve( $state, $at( '2026-10-20T00:00' ) );
$check( 'at the exact end of the inner window the outer scheduled window shows again', 'default' === $r['theme'] && 'season0001' === $r['entry']['id'] );
$r = cacdemo_site_theme_resolve( $state, $at( '2026-11-01T00:00' ) );
$check( 'after every window ends the site returns to the everyday theme by itself', 'test-season' === $r['theme'] && 'everyday' === $r['source'] );
$r = cacdemo_site_theme_resolve( $state, $at( '2026-12-15T00:00' ) );
$check( 'a scheduled theme that no longer exists is ignored', 'test-season' === $r['theme'] && 'everyday' === $r['source'] );
$r = cacdemo_site_theme_resolve( array_merge( $state, array( 'everyday' => 'removed-theme', 'schedule' => array() ) ), $at( '2026-12-15T00:00' ) );
$check( 'a missing everyday theme falls back to Default', 'default' === $r['theme'] && 'fallback' === $r['source'] );
$r = cacdemo_site_theme_resolve( $state, $at( '2026-10-15T09:00' ), 'test-season', 'subtle' );
$check( 'preview overrides the schedule and can set the effect intensity', 'test-season' === $r['theme'] && 'preview' === $r['source'] && 'subtle' === $r['effects'] );
$r = cacdemo_site_theme_resolve( $state, $at( '2026-10-15T09:00' ), 'not-a-theme' );
$check( 'previewing an unknown theme is ignored', 'test-occasion' === $r['theme'] && 'schedule' === $r['source'] );
$r = cacdemo_site_theme_resolve( $state, $at( '2026-10-15T09:00' ), 'test-occasion', 'enhanced' );
$check( 'effects are always off for a package that has none', 'off' === $r['effects'] );
$check( 'times are read in the site timezone (' . wp_timezone_string() . ')', $at( '2026-10-12T00:00' )->getTimezone()->getName() === wp_timezone()->getName() && null === $at( '2026-13-45T25:00' ) && null === $at( 'next friday' ) );
$fx_state = array( 'everyday' => 'test-season', 'effects' => 'enhanced', 'schedule' => array() );
$r        = cacdemo_site_theme_resolve( $fx_state, $at( '2026-10-15T09:00' ) );
$check( 'a theme with an effect names it and keeps a supported intensity', 'leaves' === $r['effect'] && 'enhanced' === $r['effects'] );
$check( 'effects set to Off name no effect', '' === cacdemo_site_theme_resolve( array( 'effects' => 'off' ) + $fx_state, $at( '2026-10-15T09:00' ) )['effect'] );
remove_filter( 'cacdemo_site_theme_packages', $fixtures );
cacdemo_site_theme_reset_caches();

WP_CLI::log( "\nImages" );
$state_now = cacdemo_site_theme_state();
$slot_imgs = $state_now['images']['default'] ?? array();
$img_a     = (int) ( $slot_imgs['home-hero'] ?? 0 );
$img_b     = (int) ( $slot_imgs['fallback'] ?? 0 );
$about     = get_page_by_path( 'about' );
$ministry  = get_page_by_path( 'psalmists', OBJECT, 'ministry' );
if ( ! $img_a || ! $img_b || ! $about || ! $ministry ) {
	$check( 'image fixtures exist (run scripts/seed-appearance.php and seed-content/seed-ministries)', false );
} else {
	$fake_state = array( 'images' => array( 'default' => array( 'home-hero' => $img_a, 'fallback' => $img_b, 'sermons-hero' => $about->ID ) ) );
	$opt        = function () use ( &$fake_state ) {
		return $fake_state;
	};
	add_filter( 'pre_option_' . CACDEMO_SITE_THEME_OPTION, $opt );
	$check( 'a theme slot with an image returns it', $img_a === cacdemo_site_theme_slot_image( 0, 'home-hero' ) );
	$check( 'an empty slot uses the theme\'s fallback image', $img_b === cacdemo_site_theme_slot_image( 0, 'ministries-hero' ) );
	$check( 'a slot holding something that is not an image is skipped', $img_b === cacdemo_site_theme_slot_image( 0, 'sermons-hero' ) );

	$meta = array();
	$mf   = function ( $value, $id, $key ) use ( &$meta ) {
		return isset( $meta[ $id ][ $key ] ) ? array( $meta[ $id ][ $key ] ) : $value;
	};
	add_filter( 'get_post_metadata', $mf, 10, 3 );
	$meta[ $about->ID ] = array( 'banner_image' => $img_a, 'banner_position' => 'right', 'banner_position_mobile' => 'top', '_thumbnail_id' => $img_b );
	$r = cacdemo_resolve_image( 'hero', $about, array( 'theme_slot' => 'page-hero' ) );
	$check( 'a page banner wins over everything, with its focal points', $r && $img_a === $r['id'] && 'banner' === $r['source'] && '85% 50%' === $r['position'] && '50% 15%' === $r['position_mobile'] );
	$meta[ $about->ID ]['banner_image'] = 999999999;
	$r = cacdemo_resolve_image( 'hero', $about, array( 'theme_slot' => 'page-hero' ) );
	$check( 'a deleted banner moves on (a page\'s featured image is not a hero; the theme image is used)', $r && 'theme' === $r['source'] && $img_b === $r['id'] );
	$check( 'without a theme slot, a page with no banner has no hero image', null === cacdemo_resolve_image( 'hero', $about, array( 'theme_slot' => '' ) ) );
	$check( 'a card uses the featured image first', ( cacdemo_resolve_image( 'card', $about )['source'] ?? '' ) === 'featured' );
	$meta[ $about->ID ] = array();
	$check( 'a ministry hero uses the ministry\'s featured image', 'featured' === ( cacdemo_resolve_image( 'hero', $ministry, array( 'theme_slot' => 'ministries-hero' ) )['source'] ?? '' ) );
	$update = get_posts( array( 'post_type' => 'post', 'numberposts' => 1, 'post_status' => 'any' ) );
	if ( $update ) {
		$meta[ $update[0]->ID ] = array( 'update_ministry' => $ministry->ID, '_thumbnail_id' => 0 );
		$check( 'an update without an image uses its ministry\'s image', 'parent' === ( cacdemo_resolve_image( 'card', $update[0] )['source'] ?? '' ) );
	}

	$hero = '<!-- wp:cacdemo/page-hero {"variant":"moderate","themeSlot":"%s"} --><h2>T</h2><!-- /wp:cacdemo/page-hero -->';
	$GLOBALS['cacdemo_hero_rendered'] = false;
	$html_hero = render_block( parse_blocks( sprintf( $hero, 'home-hero' ) )[0] );
	$check( 'the page hero renders the image behind its content, eager and high priority when first', str_contains( $html_hero, 'cacdemo-hero has-image is-moderate' ) && str_contains( $html_hero, 'fetchpriority="high"' ) && str_contains( $html_hero, 'class="cacdemo-hero__content"><h2>T</h2>' ) && str_contains( $html_hero, 'alt=""' ) );
	$html_hero = render_block( parse_blocks( sprintf( $hero, 'home-hero' ) )[0] );
	$check( 'later heroes on the same page load lazily', str_contains( $html_hero, 'loading="lazy"' ) && ! str_contains( $html_hero, 'fetchpriority="high"' ) );
	$fake_state = array();
	$html_hero  = render_block( parse_blocks( sprintf( $hero, 'home-hero' ) )[0] );
	$check( 'with no image anywhere the hero is a plain band with its content', str_contains( $html_hero, 'cacdemo-hero is-plain' ) && str_contains( $html_hero, '<h2>T</h2>' ) && ! str_contains( $html_hero, '<img' ) );
	remove_filter( 'get_post_metadata', $mf, 10 );
	remove_filter( 'pre_option_' . CACDEMO_SITE_THEME_OPTION, $opt );
	cacdemo_site_theme_reset_caches();
	unset( $GLOBALS['cacdemo_hero_rendered'] );
}

WP_CLI::log( "\nWhat visitors receive" );
$stored  = cacdemo_site_theme_resolve( cacdemo_site_theme_state(), cacdemo_site_theme_now() );
$visitor = wp_remote_retrieve_body( wp_remote_get( home_url( '/' ), array( 'timeout' => 20 ) ) );
preg_match( '/<html[^>]*>/', $visitor, $html );
$check( 'the page carries the resolved theme, palette and effects on <html>', ! empty( $html[0] ) && str_contains( $html[0], 'data-site-theme="' . $stored['theme'] . '"' ) && str_contains( $html[0], 'data-palette-default="' . $stored['palette'] . '"' ) && str_contains( $html[0], 'data-effects="' . $stored['effects'] . '"' ) );
$cache  = wp_remote_retrieve_header( wp_remote_head( home_url( '/about/' ), array( 'timeout' => 20 ) ), 'cache-control' );
$check( 'public pages ask browsers to revalidate (schedules and edits show up immediately)', str_contains( (string) $cache, 'no-cache' ) && str_contains( (string) $cache, 'max-age=0' ) );
$head   = substr( $visitor, 0, (int) strpos( $visitor, '</head>' ) );
$script = strpos( $head, 'id="cacdemo-palette-init"' );
$first  = min( array_filter( array( strpos( $head, '<link rel=\'stylesheet\'' ), strpos( $head, '<link rel="stylesheet"' ), strpos( $head, '<style' ) ), 'is_int' ) ?: array( PHP_INT_MAX ) );
$check( 'the saved-palette script runs before any stylesheet (no flash of the default palette)', false !== $script && $script < $first );
$check( 'the head script only accepts palettes that exist', (bool) preg_match( '/var k="cacdemo:palette",v=window\.localStorage\.getItem\(k\),a=\[[^\]]*"chocolate"[^\]]*\]/', $head ) );
$check( 'the visitor colour picker is rendered (hidden until the script confirms storage)', (bool) preg_match( '/<div class="cacdemo-palette-picker" data-palette-picker hidden>/', $visitor ) && substr_count( $visitor, 'name="cacdemo-palette"' ) === count( cacdemo_selectable_palettes() ) );
$preview = wp_remote_retrieve_body( wp_remote_get( add_query_arg( array( 'site_theme_preview' => 'default', 'palette_preview' => 'chocolate' ), home_url( '/' ) ), array( 'timeout' => 20 ) ) );
preg_match( '/<html[^>]*>/', $preview, $preview_html );
$check( 'preview parameters are ignored for visitors', ! empty( $preview_html[0] ) && ! str_contains( $preview_html[0], 'data-palette-forced' ) && str_contains( $preview_html[0], 'data-site-theme="' . $stored['theme'] . '"' ) && ! str_contains( $preview, "content='noindex, nofollow'" ) );
$manage = wp_remote_retrieve_body( wp_remote_get( home_url( '/wp-login.php' ), array( 'timeout' => 20 ) ) );
$contact = wp_remote_retrieve_body( wp_remote_get( home_url( '/contact/' ), array( 'timeout' => 20 ) ) );
$check( 'the home page opens with one image hero; Contact keeps its plain title band', 1 === substr_count( $visitor, 'fetchpriority="high"' ) && str_contains( $visitor, 'cacdemo-hero has-image is-landing' ) && str_contains( $contact, 'cacdemo-hero is-plain' ) );
$check( 'the effects script loads only with an effect to show', ( '' === $stored['effect'] ) === ! str_contains( $visitor, 'assets/js/effects.js' ) && ( '' === $stored['effect'] ) === ! str_contains( $html[0], 'data-effect=' ) );
$check( 'Fall declares leaves and Winter snow; Spring and Summer stay still', array( 'leaves' ) === array_keys( $packages['fall']['effects'] ) && array( 'snow' ) === array_keys( $packages['winter']['effects'] ) && ! $packages['spring']['effects'] && ! $packages['summer']['effects'] );
$check( 'the sign-in screen gets no palette or theme attributes', ! str_contains( $manage, 'data-site-theme' ) && ! str_contains( $manage, 'cacdemo-palette-init' ) );

WP_CLI::log( sprintf( "\n%d passed, %d failed", $results['pass'], $results['fail'] ) );
if ( $results['fail'] ) {
	WP_CLI::halt( 1 );
}
