<?php
/**
 * Seeds the visual-theme system's media: media collections, generated placeholder artwork, and which image each site
 * theme slot, ministry and landing page uses until the church chooses real photographs.
 *
 *   wp --path=/mnt/ai/workspaces/cacdemo eval-file scripts/seed-appearance.php [dry-run] [regenerate]
 *
 * Placeholders are generated here (PHP GD), so any environment can recreate them without copying image files. They are
 * abstract and non-photographic (arches, light, colour fields), never people, worship services or events. Each attachment
 * is titled "Placeholder — …", carries meta _cacdemo_placeholder = <key>, and is in the Placeholder collection, so every
 * one can be found and replaced.
 *
 * Non-destructive: an image is only assigned where nothing is set (a slot, a ministry's featured image, a page banner);
 * choices made in Appearance → Site Theme, the Content Manager or wp-admin are never overwritten. `regenerate` replaces
 * the placeholder files themselves (same attachments), for design iteration.
 */

defined( 'WP_CLI' ) || exit;

$dry_run    = in_array( 'dry-run', $args ?? array(), true );
$regenerate = in_array( 'regenerate', $args ?? array(), true );
$log        = function ( $message ) use ( $dry_run ) {
	WP_CLI::log( ( $dry_run ? '[dry-run] ' : '' ) . $message );
};

if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'cacdemo_add_to_collection' ) || ! function_exists( 'cacdemo_site_theme_state' ) ) {
	WP_CLI::error( 'Needs PHP GD, the cacdemo-content plugin and the cacdemo theme.' );
}
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
wp_set_current_user( $admins[0]->ID ?? 0 );

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/* ---------------------------------------------------------------- Collections */

foreach ( cacdemo_media_collections() as $slug => $name ) {
	if ( ! term_exists( $slug, 'media_collection' ) ) {
		if ( ! $dry_run ) {
			wp_insert_term( $name, 'media_collection', array( 'slug' => $slug ) );
		}
		$log( "created collection $name" );
	}
}

/* ---------------------------------------------------------------- Artwork */

/** A colour from "#RRGGBB" with GD alpha (0 opaque … 127 transparent). */
function cacdemo_art_color( $im, $hex, $alpha = 0 ) {
	$hex = ltrim( $hex, '#' );
	return imagecolorallocatealpha( $im, hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ), max( 0, min( 127, (int) $alpha ) ) );
}

function cacdemo_art_mix( $a, $b, $t ) {
	$a = array_map( 'hexdec', str_split( ltrim( $a, '#' ), 2 ) );
	$b = array_map( 'hexdec', str_split( ltrim( $b, '#' ), 2 ) );
	return array_map( fn( $i ) => (int) round( $a[ $i ] + ( $b[ $i ] - $a[ $i ] ) * $t ), array( 0, 1, 2 ) );
}

/**
 * Renders one placeholder: a diagonal three-stop colour field, soft glows and shapes (blurred), then crisp outline
 * motifs and a fine grain. Coordinates are fractions of width/height.
 *
 * @param array $spec stops[3], glows[[x,y,r,hex,alpha]], shapes[[type,…]], outlines[[type,…]], grain.
 */
function cacdemo_art_render( $spec, $w = 2400, $h = 1350 ) {
	$sw = (int) ( $w / 4 );
	$sh = (int) ( $h / 4 );
	$im = imagecreatetruecolor( $sw, $sh );
	imagealphablending( $im, true );

	list( $s0, $s1, $s2 ) = $spec['stops'];
	for ( $y = 0; $y < $sh; $y++ ) {
		for ( $x = 0; $x < $sw; $x++ ) {
			$t   = ( $x / $sw ) * 0.62 + ( $y / $sh ) * 0.38;
			$rgb = $t < 0.5 ? cacdemo_art_mix( $s0, $s1, $t * 2 ) : cacdemo_art_mix( $s1, $s2, ( $t - 0.5 ) * 2 );
			imagesetpixel( $im, $x, $y, ( $rgb[0] << 16 ) | ( $rgb[1] << 8 ) | $rgb[2] );
		}
	}
	foreach ( $spec['glows'] ?? array() as $g ) {
		list( $gx, $gy, $gr, $hex, $alpha ) = $g;
		for ( $i = 10; $i >= 1; $i-- ) {
			$r = $gr * $sw * ( $i / 10 );
			imagefilledellipse( $im, (int) ( $gx * $sw ), (int) ( $gy * $sh ), (int) ( $r * 2 ), (int) ( $r * 2 ), cacdemo_art_color( $im, $hex, 127 - ( 127 - $alpha ) * ( 1 - $i / 11 ) / 3 ) );
		}
	}
	foreach ( $spec['shapes'] ?? array() as $shape ) {
		cacdemo_art_shape( $im, $shape, $sw, $sh, 1 );
	}
	for ( $i = 0; $i < ( $spec['blur'] ?? 14 ); $i++ ) {
		imagefilter( $im, IMG_FILTER_GAUSSIAN_BLUR );
	}

	$out = imagecreatetruecolor( $w, $h );
	imagecopyresampled( $out, $im, 0, 0, 0, 0, $w, $h, $sw, $sh );
	imagedestroy( $im );
	imagealphablending( $out, true );
	imageantialias( $out, true );
	foreach ( $spec['outlines'] ?? array() as $shape ) {
		cacdemo_art_shape( $out, $shape, $w, $h, 4 );
	}

	// Fine grain from a small tiled noise tile, so large flat areas do not band.
	$tile = imagecreatetruecolor( 256, 256 );
	mt_srand( 7 );
	for ( $y = 0; $y < 256; $y++ ) {
		for ( $x = 0; $x < 256; $x++ ) {
			$v = mt_rand( 96, 160 );
			imagesetpixel( $tile, $x, $y, ( $v << 16 ) | ( $v << 8 ) | $v );
		}
	}
	for ( $y = 0; $y < $h; $y += 256 ) {
		for ( $x = 0; $x < $w; $x += 256 ) {
			imagecopymerge( $out, $tile, $x, $y, 0, 0, 256, 256, $spec['grain'] ?? 5 );
		}
	}
	imagedestroy( $tile );
	return $out;
}

/** Shapes: arch, circle, ray, ellipse (rotated leaf/petal), wave (concentric arcs), dots. Sizes scale with $k (line width). */
function cacdemo_art_shape( $im, $shape, $w, $h, $k ) {
	$type = array_shift( $shape );
	switch ( $type ) {
		case 'arch': // [x, y (base), width, height, hex, alpha, lines]
			list( $x, $y, $aw, $ah, $hex, $alpha, $lines ) = $shape + array( 6 => 1 );
			$color = cacdemo_art_color( $im, $hex, $alpha );
			$cx    = $x * $w;
			$base  = $y * $h;
			$r     = $aw * $w / 2;
			$top   = $base - $ah * $h + $r;
			for ( $l = 0; $l < $lines * $k; $l++ ) {
				$rr = $r - $l;
				imagearc( $im, (int) $cx, (int) $top, (int) ( $rr * 2 ), (int) ( $rr * 2 ), 180, 360, $color );
				imageline( $im, (int) ( $cx - $rr ), (int) $top, (int) ( $cx - $rr ), (int) $base, $color );
				imageline( $im, (int) ( $cx + $rr ), (int) $top, (int) ( $cx + $rr ), (int) $base, $color );
			}
			break;
		case 'filled-arch': // [x, y (base), width, height, hex, alpha]
			list( $x, $y, $aw, $ah, $hex, $alpha ) = $shape;
			$color = cacdemo_art_color( $im, $hex, $alpha );
			$r     = $aw * $w / 2;
			$top   = $y * $h - $ah * $h + $r;
			imagefilledarc( $im, (int) ( $x * $w ), (int) $top, (int) ( $r * 2 ), (int) ( $r * 2 ), 180, 360, $color, IMG_ARC_PIE );
			imagefilledrectangle( $im, (int) ( $x * $w - $r ), (int) $top, (int) ( $x * $w + $r ), (int) ( $y * $h ), $color );
			break;
		case 'circle': // [x, y, r (of width), hex, alpha]
			list( $x, $y, $r, $hex, $alpha ) = $shape;
			imagefilledellipse( $im, (int) ( $x * $w ), (int) ( $y * $h ), (int) ( $r * $w * 2 ), (int) ( $r * $w * 2 ), cacdemo_art_color( $im, $hex, $alpha ) );
			break;
		case 'ring': // [x, y, r, hex, alpha, lines]
			list( $x, $y, $r, $hex, $alpha, $lines ) = $shape + array( 5 => 1 );
			$color = cacdemo_art_color( $im, $hex, $alpha );
			for ( $l = 0; $l < $lines * $k; $l++ ) {
				imageellipse( $im, (int) ( $x * $w ), (int) ( $y * $h ), (int) ( $r * $w * 2 - $l * 2 ), (int) ( $r * $w * 2 - $l * 2 ), $color );
			}
			break;
		case 'ray': // [x, y (source), angle, spread, length, hex, alpha]
			list( $x, $y, $angle, $spread, $len, $hex, $alpha ) = $shape;
			$a1 = deg2rad( $angle - $spread / 2 );
			$a2 = deg2rad( $angle + $spread / 2 );
			$l  = $len * $w;
			imagefilledpolygon( $im, array( (int) ( $x * $w ), (int) ( $y * $h ), (int) ( $x * $w + cos( $a1 ) * $l ), (int) ( $y * $h + sin( $a1 ) * $l ), (int) ( $x * $w + cos( $a2 ) * $l ), (int) ( $y * $h + sin( $a2 ) * $l ) ), cacdemo_art_color( $im, $hex, $alpha ) );
			break;
		case 'ellipse': // [x, y, rx, ry (of width), rotation, hex, alpha]
			list( $x, $y, $rx, $ry, $rot, $hex, $alpha ) = $shape;
			$points = array();
			$rad    = deg2rad( $rot );
			for ( $i = 0; $i < 36; $i++ ) {
				$t        = 2 * M_PI * $i / 36;
				$px       = cos( $t ) * $rx * $w;
				$py       = sin( $t ) * $ry * $w;
				$points[] = (int) ( $x * $w + $px * cos( $rad ) - $py * sin( $rad ) );
				$points[] = (int) ( $y * $h + $px * sin( $rad ) + $py * cos( $rad ) );
			}
			imagefilledpolygon( $im, $points, cacdemo_art_color( $im, $hex, $alpha ) );
			break;
		case 'wave': // [x, y, r, count, gap, hex, alpha] — concentric half rings opening upward (sound, overflow)
			list( $x, $y, $r, $count, $gap, $hex, $alpha ) = $shape;
			$color = cacdemo_art_color( $im, $hex, $alpha );
			for ( $c = 0; $c < $count; $c++ ) {
				for ( $l = 0; $l < $k * 2; $l++ ) {
					$rr = ( $r + $c * $gap ) * $w * 2 - $l * 2;
					imagearc( $im, (int) ( $x * $w ), (int) ( $y * $h ), (int) $rr, (int) $rr, 200, 340, $color );
				}
			}
			break;
		case 'dots': // [seed, count, min r, max r, hex, alpha, x0, x1, y0, y1]
			list( $seed, $count, $rmin, $rmax, $hex, $alpha, $x0, $x1, $y0, $y1 ) = $shape;
			mt_srand( $seed );
			$color = cacdemo_art_color( $im, $hex, $alpha );
			for ( $i = 0; $i < $count; $i++ ) {
				$r = ( $rmin + ( $rmax - $rmin ) * mt_rand() / mt_getrandmax() ) * $w;
				imagefilledellipse( $im, (int) ( ( $x0 + ( $x1 - $x0 ) * mt_rand() / mt_getrandmax() ) * $w ), (int) ( ( $y0 + ( $y1 - $y0 ) * mt_rand() / mt_getrandmax() ) * $h ), (int) ( $r * 2 ), (int) ( $r * 2 ), $color );
			}
			break;
	}
}

/**
 * Placeholder definitions: key => [ title, collections, spec ]. Bright motifs sit to the right, where heroes carry no text.
 * Colours are chosen to sit well under the dark hero overlay of every palette.
 */
function cacdemo_placeholder_specs() {
	$gold  = '#E2B45C';
	$cream = '#F4EBDD';
	return apply_filters( 'cacdemo_placeholder_specs', array(
		'default-home-hero' => array( 'Home hero (Default)', array( 'placeholder', 'backgrounds' ), array(
			'stops'    => array( '#141A33', '#2B2F63', '#6B4A6E' ),
			'glows'    => array( array( 0.74, 0.46, 0.34, '#F2C46D', 40 ), array( 0.9, 0.15, 0.2, '#8C9BF0', 70 ) ),
			'shapes'   => array( array( 'ray', 0.78, -0.1, 100, 22, 1.2, '#FBE3A8', 112 ), array( 'ray', 0.66, -0.1, 80, 12, 1.1, '#FBE3A8', 118 ), array( 'filled-arch', 0.74, 1.05, 0.3, 0.72, '#F7D38A', 110 ) ),
			'outlines' => array( array( 'arch', 0.74, 1.02, 0.3, 0.72, $gold, 64, 1 ), array( 'arch', 0.74, 1.02, 0.4, 0.84, $gold, 94, 1 ), array( 'arch', 0.74, 1.02, 0.5, 0.96, $cream, 112, 1 ) ),
		) ),
		'default-page-hero' => array( 'Page hero (Default)', array( 'placeholder', 'backgrounds' ), array(
			'stops'    => array( '#17213A', '#33405F', '#7A6A5C' ),
			'glows'    => array( array( 0.8, 0.55, 0.3, '#E9C07A', 50 ) ),
			'shapes'   => array( array( 'filled-arch', 0.8, 1.05, 0.24, 0.95, '#EACB8F', 104 ) ),
			'outlines' => array( array( 'arch', 0.8, 1.02, 0.3, 1.02, $gold, 70, 1 ) ),
		) ),
		'default-ministries-hero' => array( 'Ministries hero (Default)', array( 'placeholder', 'backgrounds', 'ministries' ), array(
			'stops'    => array( '#1B1C36', '#3E3563', '#86606A' ),
			'glows'    => array( array( 0.72, 0.62, 0.36, '#F0BE78', 54 ) ),
			'shapes'   => array( array( 'filled-arch', 0.74, 1.0, 0.26, 0.6, '#F3CF93', 112 ) ),
			'outlines' => array( array( 'arch', 0.56, 1.0, 0.2, 0.46, $cream, 96, 1 ), array( 'arch', 0.74, 1.0, 0.26, 0.6, $gold, 76, 1 ), array( 'arch', 0.92, 1.0, 0.2, 0.46, $cream, 96, 1 ) ),
		) ),
		'default-sermons-hero' => array( 'Sermons hero (Default)', array( 'placeholder', 'backgrounds' ), array(
			'stops'    => array( '#10142A', '#1F2A55', '#3A3F7E' ),
			'glows'    => array( array( 0.76, 0.72, 0.28, '#9AA8FF', 56 ), array( 0.76, 0.2, 0.18, '#F6DDA6', 80 ) ),
			'shapes'   => array( array( 'ray', 0.76, -0.2, 90, 26, 1.3, '#DCE3FF', 108 ) ),
			'outlines' => array( array( 'ring', 0.76, 0.72, 0.12, '#C9D1FF', 88, 1 ), array( 'ring', 0.76, 0.72, 0.18, '#C9D1FF', 104, 1 ) ),
		) ),
		'default-fallback' => array( 'General fallback (Default)', array( 'placeholder', 'backgrounds' ), array(
			'stops'    => array( '#262A3F', '#48506A', '#8A8578' ),
			'glows'    => array( array( 0.7, 0.5, 0.32, '#D9C29A', 70 ) ),
			'outlines' => array( array( 'arch', 0.7, 1.02, 0.28, 0.9, $cream, 96, 1 ) ),
		) ),
		'page-about-banner' => array( 'About banner', array( 'placeholder', 'church' ), array(
			'stops'    => array( '#1C2440', '#3E4470', '#B98A66' ),
			'glows'    => array( array( 0.8, 0.62, 0.34, '#F5C07B', 44 ), array( 0.56, 0.2, 0.22, '#8C86C8', 80 ) ),
			'shapes'   => array( array( 'filled-arch', 0.8, 1.04, 0.2, 0.62, '#F7D8A0', 104 ) ),
			'outlines' => array( array( 'arch', 0.8, 1.04, 0.25, 0.72, $gold, 70, 1 ), array( 'arch', 0.8, 1.04, 0.31, 0.84, $cream, 104, 1 ) ),
		) ),
		'page-new-here-banner' => array( 'New Here banner', array( 'placeholder', 'church' ), array(
			'stops'    => array( '#182338', '#2E4A5E', '#B9936C' ),
			'glows'    => array( array( 0.76, 0.66, 0.26, '#FFE1A0', 30 ) ),
			'shapes'   => array( array( 'filled-arch', 0.76, 1.02, 0.28, 0.74, '#FFE7B5', 92 ), array( 'ray', 0.76, 1.0, 270, 40, 0.9, '#FFE7B5', 114 ) ),
			'outlines' => array( array( 'arch', 0.76, 1.02, 0.32, 0.8, $gold, 62, 1 ), array( 'arch', 0.76, 1.02, 0.4, 0.9, $cream, 100, 1 ) ),
		) ),
		'ministry-psalmists' => array( 'Psalmists (ministry)', array( 'placeholder', 'ministries' ), array(
			'stops'    => array( '#1A1638', '#3C2E6E', '#7C4E8C' ),
			'glows'    => array( array( 0.68, 0.62, 0.3, '#C9A3FF', 60 ) ),
			'outlines' => array( array( 'wave', 0.68, 0.78, 0.05, 6, 0.045, '#F1D9FF', 70 ) ),
		) ),
		'ministry-victuals' => array( 'Victuals (ministry)', array( 'placeholder', 'ministries' ), array(
			'stops'    => array( '#2A1A14', '#6A3A22', '#C98A4B' ),
			'glows'    => array( array( 0.66, 0.56, 0.3, '#FFC27A', 50 ) ),
			'shapes'   => array( array( 'circle', 0.66, 0.58, 0.16, '#F7B267', 96 ), array( 'circle', 0.86, 0.36, 0.08, '#FFD9A0', 104 ) ),
			'outlines' => array( array( 'ring', 0.66, 0.58, 0.2, '#FFE2B8', 90, 1 ), array( 'ring', 0.66, 0.58, 0.24, '#FFE2B8', 108, 1 ) ),
		) ),
		'ministry-facilities' => array( 'Facilities (ministry)', array( 'placeholder', 'ministries', 'facilities' ), array(
			'stops'    => array( '#17222B', '#2F4A55', '#7C8F8A' ),
			'glows'    => array( array( 0.7, 0.4, 0.3, '#CFE4DD', 70 ) ),
			'outlines' => array( array( 'arch', 0.5, 1.0, 0.12, 0.62, '#E2EFEA', 90, 1 ), array( 'arch', 0.64, 1.0, 0.12, 0.62, '#E2EFEA', 80, 1 ), array( 'arch', 0.78, 1.0, 0.12, 0.62, '#E2EFEA', 70, 1 ), array( 'arch', 0.92, 1.0, 0.12, 0.62, '#E2EFEA', 80, 1 ) ),
		) ),
		'ministry-gift-and-arrows' => array( 'Gift and Arrows (ministry)', array( 'placeholder', 'ministries' ), array(
			'stops'    => array( '#15283A', '#2D6076', '#E0A857' ),
			'glows'    => array( array( 0.74, 0.3, 0.26, '#FFE3A3', 50 ) ),
			'shapes'   => array( array( 'dots', 11, 26, 0.006, 0.016, '#FFF1CC', 70, 0.45, 1.0, 0.1, 0.9 ) ),
			'outlines' => array( array( 'ray', 0.4, 1.1, -40, 3, 0.9, '#FFF1CC', 70 ), array( 'ray', 0.5, 1.1, -45, 3, 0.9, '#FFF1CC', 85 ), array( 'ray', 0.6, 1.1, -50, 3, 0.9, '#FFF1CC', 95 ) ),
		) ),
		'ministry-productions' => array( 'Productions (ministry)', array( 'placeholder', 'ministries' ), array(
			'stops'    => array( '#0E1026', '#232B63', '#3F55B6' ),
			'glows'    => array( array( 0.72, 0.84, 0.3, '#8FA2FF', 50 ) ),
			'shapes'   => array( array( 'ray', 0.6, -0.1, 70, 10, 1.3, '#E6EBFF', 104 ), array( 'ray', 0.78, -0.1, 95, 10, 1.3, '#E6EBFF', 100 ), array( 'ray', 0.95, -0.1, 118, 10, 1.3, '#E6EBFF', 106 ) ),
		) ),
		'ministry-events' => array( 'Events (ministry)', array( 'placeholder', 'ministries', 'events' ), array(
			'stops'    => array( '#201834', '#4F2F5E', '#B85C5C' ),
			'glows'    => array( array( 0.72, 0.5, 0.34, '#FFB98A', 56 ) ),
			'shapes'   => array( array( 'dots', 23, 40, 0.004, 0.014, '#FFE0C2', 60, 0.42, 1.0, 0.05, 0.95 ) ),
			'outlines' => array( array( 'ring', 0.74, 0.5, 0.14, '#FFE0C2', 86, 1 ) ),
		) ),
		'ministry-more-than-enough' => array( 'More Than Enough (ministry)', array( 'placeholder', 'ministries', 'community' ), array(
			'stops'    => array( '#12261E', '#2C5A45', '#B39A55' ),
			'glows'    => array( array( 0.7, 0.68, 0.34, '#F4D78A', 50 ) ),
			'outlines' => array( array( 'wave', 0.7, 0.92, 0.06, 7, 0.04, '#F7E8BD', 66 ) ),
		) ),

		/* Natural seasons: motifs to the right, colours that stay calm under any palette's overlay. Winter is the season only. */
		'spring-home-hero' => array( 'Home hero (Spring)', array( 'placeholder', 'spring', 'backgrounds' ), array(
			'stops'    => array( '#14302A', '#2F5E4E', '#9DBF8E' ),
			'glows'    => array( array( 0.76, 0.3, 0.3, '#FFF1B8', 40 ), array( 0.9, 0.8, 0.26, '#F2C7C9', 70 ) ),
			'shapes'   => array( array( 'ellipse', 0.66, 0.62, 0.05, 0.018, -35, '#DDEFC4', 96 ), array( 'ellipse', 0.8, 0.46, 0.06, 0.02, 25, '#E9F5D6', 92 ), array( 'ellipse', 0.9, 0.7, 0.045, 0.016, -60, '#F6D9DA', 96 ), array( 'ellipse', 0.72, 0.84, 0.05, 0.017, 10, '#DDEFC4', 100 ) ),
			'outlines' => array( array( 'dots', 11, 26, 0.002, 0.005, '#FFF6D8', 70, 0.55, 1.0, 0.1, 0.95 ) ),
		) ),
		'spring-ministries-hero' => array( 'Ministries hero (Spring)', array( 'placeholder', 'spring', 'backgrounds' ), array(
			'stops'    => array( '#16322E', '#355F58', '#B7C99A' ),
			'glows'    => array( array( 0.74, 0.5, 0.34, '#F7F0C0', 50 ) ),
			'shapes'   => array( array( 'ellipse', 0.62, 0.7, 0.07, 0.024, -20, '#D6EBBF', 98 ), array( 'ellipse', 0.76, 0.58, 0.08, 0.026, 30, '#E4F2D0', 94 ), array( 'ellipse', 0.9, 0.72, 0.07, 0.024, -45, '#D6EBBF', 98 ) ),
			'outlines' => array( array( 'arch', 0.76, 1.02, 0.26, 0.62, '#EAF3DA', 90, 1 ) ),
		) ),
		'spring-sermons-hero' => array( 'Sermons hero (Spring)', array( 'placeholder', 'spring', 'backgrounds' ), array(
			'stops'    => array( '#12282C', '#27505A', '#7FA79A' ),
			'glows'    => array( array( 0.76, 0.24, 0.24, '#FFF3C4', 50 ) ),
			'shapes'   => array( array( 'ray', 0.76, -0.2, 95, 22, 1.3, '#F4F7DF', 110 ) ),
			'outlines' => array( array( 'ring', 0.76, 0.7, 0.12, '#E3F0D4', 90, 1 ), array( 'ring', 0.76, 0.7, 0.18, '#E3F0D4', 106, 1 ) ),
		) ),
		'spring-fallback' => array( 'General fallback (Spring)', array( 'placeholder', 'spring', 'backgrounds' ), array(
			'stops'    => array( '#1E332E', '#46645A', '#A7B99A' ),
			'glows'    => array( array( 0.7, 0.45, 0.3, '#F1EBC2', 60 ) ),
			'shapes'   => array( array( 'ellipse', 0.72, 0.6, 0.08, 0.026, -30, '#DDEBC8', 96 ) ),
		) ),

		'summer-home-hero' => array( 'Home hero (Summer)', array( 'placeholder', 'summer', 'backgrounds' ), array(
			'stops'    => array( '#0F2A4A', '#2D6A8E', '#E7B96A' ),
			'glows'    => array( array( 0.76, 0.42, 0.36, '#FFD98A', 30 ) ),
			'shapes'   => array( array( 'circle', 0.76, 0.44, 0.1, '#FFE3A3', 70 ), array( 'ray', 0.76, 0.44, 200, 16, 0.5, '#FFF0C8', 112 ), array( 'ray', 0.76, 0.44, 340, 16, 0.5, '#FFF0C8', 112 ), array( 'ellipse', 0.7, 1.08, 0.6, 0.12, 0, '#1D4F6E', 60 ) ),
			'outlines' => array( array( 'wave', 0.76, 1.18, 0.14, 5, 0.035, '#FFF4D6', 80 ) ),
		) ),
		'summer-ministries-hero' => array( 'Ministries hero (Summer)', array( 'placeholder', 'summer', 'backgrounds' ), array(
			'stops'    => array( '#12304C', '#3A7597', '#E9C27E' ),
			'glows'    => array( array( 0.72, 0.36, 0.3, '#FFE2A0', 40 ) ),
			'shapes'   => array( array( 'circle', 0.74, 0.36, 0.08, '#FFE7B0', 76 ) ),
			'outlines' => array( array( 'arch', 0.74, 1.02, 0.28, 0.6, '#FFF1D2', 84, 1 ), array( 'arch', 0.74, 1.02, 0.38, 0.74, '#FFF1D2', 104, 1 ) ),
		) ),
		'summer-sermons-hero' => array( 'Sermons hero (Summer)', array( 'placeholder', 'summer', 'backgrounds' ), array(
			'stops'    => array( '#0E2440', '#255C80', '#8FB8C8' ),
			'glows'    => array( array( 0.78, 0.3, 0.26, '#FFE6A8', 44 ) ),
			'outlines' => array( array( 'wave', 0.78, 0.98, 0.08, 7, 0.04, '#FFF3D0', 76 ) ),
		) ),
		'summer-fallback' => array( 'General fallback (Summer)', array( 'placeholder', 'summer', 'backgrounds' ), array(
			'stops'    => array( '#1A3550', '#4B7E98', '#DDBF8A' ),
			'glows'    => array( array( 0.7, 0.4, 0.3, '#FFE3A6', 50 ) ),
			'shapes'   => array( array( 'circle', 0.72, 0.42, 0.07, '#FFE9B8', 84 ) ),
		) ),

		'fall-home-hero' => array( 'Home hero (Fall)', array( 'placeholder', 'fall', 'backgrounds' ), array(
			'stops'    => array( '#2A160E', '#6B3418', '#D08A3C' ),
			'glows'    => array( array( 0.78, 0.66, 0.34, '#FFB864', 36 ) ),
			'shapes'   => array( array( 'ellipse', 0.62, 0.3, 0.05, 0.024, 40, '#E9A04A', 80 ), array( 'ellipse', 0.74, 0.5, 0.06, 0.028, -25, '#F2C26A', 76 ), array( 'ellipse', 0.88, 0.34, 0.05, 0.022, 70, '#C9642E', 70 ), array( 'ellipse', 0.84, 0.78, 0.055, 0.025, 15, '#E9A04A', 80 ), array( 'ellipse', 0.66, 0.84, 0.045, 0.02, -60, '#C9642E', 76 ) ),
			'outlines' => array( array( 'arch', 0.76, 1.02, 0.34, 0.8, '#F7D9A8', 92, 1 ) ),
			'blur'     => 10,
		) ),
		'fall-ministries-hero' => array( 'Ministries hero (Fall)', array( 'placeholder', 'fall', 'backgrounds' ), array(
			'stops'    => array( '#2B1810', '#6E3A1E', '#C9924E' ),
			'glows'    => array( array( 0.74, 0.58, 0.34, '#FFC377', 44 ) ),
			'shapes'   => array( array( 'filled-arch', 0.74, 1.02, 0.26, 0.62, '#F4C585', 108 ), array( 'ellipse', 0.9, 0.3, 0.05, 0.022, 40, '#E39A48', 84 ), array( 'ellipse', 0.58, 0.36, 0.045, 0.02, -30, '#C8662F', 84 ) ),
			'outlines' => array( array( 'arch', 0.74, 1.0, 0.26, 0.62, '#F7D9A8', 80, 1 ) ),
			'blur'     => 10,
		) ),
		'fall-sermons-hero' => array( 'Sermons hero (Fall)', array( 'placeholder', 'fall', 'backgrounds' ), array(
			'stops'    => array( '#24140E', '#553020', '#9A6440' ),
			'glows'    => array( array( 0.76, 0.7, 0.28, '#FFBE78', 50 ), array( 0.76, 0.2, 0.18, '#F8DDB0', 80 ) ),
			'shapes'   => array( array( 'ray', 0.76, -0.2, 90, 24, 1.3, '#FBE2BC', 110 ) ),
			'outlines' => array( array( 'ring', 0.76, 0.7, 0.12, '#F4CFA0', 88, 1 ), array( 'ring', 0.76, 0.7, 0.18, '#F4CFA0', 104, 1 ) ),
		) ),
		'fall-fallback' => array( 'General fallback (Fall)', array( 'placeholder', 'fall', 'backgrounds' ), array(
			'stops'    => array( '#30201A', '#6A4630', '#B98A5A' ),
			'glows'    => array( array( 0.7, 0.5, 0.3, '#F2BF7E', 56 ) ),
			'shapes'   => array( array( 'ellipse', 0.72, 0.48, 0.06, 0.026, -25, '#E6A65A', 88 ) ),
			'blur'     => 10,
		) ),

		'winter-home-hero' => array( 'Home hero (Winter)', array( 'placeholder', 'winter', 'backgrounds' ), array(
			'stops'    => array( '#0E1A2E', '#2E4466', '#A9BCD3' ),
			'glows'    => array( array( 0.8, 0.3, 0.28, '#E8F0FF', 50 ) ),
			'shapes'   => array( array( 'ellipse', 0.66, 1.16, 0.46, 0.14, -4, '#DCE6F2', 70 ), array( 'ellipse', 0.98, 1.2, 0.4, 0.14, 6, '#C7D5E6', 64 ) ),
			'outlines' => array( array( 'dots', 23, 70, 0.0012, 0.004, '#FFFFFF', 60, 0.45, 1.0, 0.02, 0.9 ) ),
		) ),
		'winter-ministries-hero' => array( 'Ministries hero (Winter)', array( 'placeholder', 'winter', 'backgrounds' ), array(
			'stops'    => array( '#101C30', '#34496B', '#9FB2CA' ),
			'glows'    => array( array( 0.74, 0.5, 0.34, '#F3E7D2', 58 ) ),
			'shapes'   => array( array( 'filled-arch', 0.74, 1.02, 0.26, 0.6, '#F1DEBF', 104 ), array( 'ellipse', 0.74, 1.14, 0.5, 0.1, 0, '#DCE6F2', 70 ) ),
			'outlines' => array( array( 'arch', 0.74, 1.0, 0.26, 0.6, '#EEF3FA', 84, 1 ), array( 'dots', 31, 40, 0.0012, 0.0035, '#FFFFFF', 64, 0.5, 1.0, 0.02, 0.7 ) ),
		) ),
		'winter-sermons-hero' => array( 'Sermons hero (Winter)', array( 'placeholder', 'winter', 'backgrounds' ), array(
			'stops'    => array( '#0C1526', '#22365A', '#6D84A8' ),
			'glows'    => array( array( 0.76, 0.72, 0.28, '#CFDDF7', 56 ), array( 0.76, 0.2, 0.18, '#F2F6FF', 80 ) ),
			'shapes'   => array( array( 'ray', 0.76, -0.2, 90, 24, 1.3, '#E6EEFF', 108 ) ),
			'outlines' => array( array( 'ring', 0.76, 0.72, 0.12, '#DCE6F7', 88, 1 ), array( 'ring', 0.76, 0.72, 0.18, '#DCE6F7', 104, 1 ) ),
		) ),
		'winter-fallback' => array( 'General fallback (Winter)', array( 'placeholder', 'winter', 'backgrounds' ), array(
			'stops'    => array( '#1C2638', '#46566F', '#AAB6C6' ),
			'glows'    => array( array( 0.7, 0.4, 0.3, '#EEF2F8', 60 ) ),
			'shapes'   => array( array( 'ellipse', 0.7, 1.12, 0.5, 0.12, 0, '#DCE3EC', 72 ) ),
		) ),
	) );
}

/** Creates or (with regenerate) refreshes a placeholder attachment; returns its ID. */
add_filter( 'cacdemo_default_collection', '__return_empty_string' ); // Placeholders get their own collections below.

function cacdemo_placeholder_attachment( $key, $title, $collections, $spec, $regenerate, $dry_run, $log ) {
	$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_cacdemo_placeholder', 'meta_value' => $key ) );
	if ( $existing && ! $regenerate ) {
		return (int) $existing[0];
	}
	if ( $dry_run ) {
		$log( ( $existing ? 'would regenerate' : 'would create' ) . " placeholder $key" );
		return $existing ? (int) $existing[0] : 0;
	}
	$image = cacdemo_art_render( $spec );
	$tmp   = wp_tempnam( 'placeholder-' . $key . '.jpg' );
	imagejpeg( $image, $tmp, 86 );
	imagedestroy( $image );

	$id = media_handle_sideload( array( 'name' => 'placeholder-' . $key . '.jpg', 'tmp_name' => $tmp ), 0, 'Placeholder — ' . $title, array(
		'post_content' => 'Generated placeholder artwork (not a photograph). Replace it with a church photo through the Media Library or Appearance → Site Theme.',
	) );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "placeholder $key: " . $id->get_error_message() );
		return 0;
	}
	if ( $existing ) {
		cacdemo_placeholder_replace_references( (int) $existing[0], (int) $id );
		wp_delete_attachment( (int) $existing[0], true );
		$log( "regenerated placeholder $key (#{$existing[0]} → #$id)" );
	}
	update_post_meta( $id, '_cacdemo_placeholder', $key );
	update_post_meta( $id, '_wp_attachment_image_alt', '' ); // Decorative artwork: empty alt.
	foreach ( $collections as $collection ) {
		cacdemo_add_to_collection( $id, $collection );
	}
	if ( ! $existing ) {
		$log( "created placeholder $key (#$id)" );
	}
	return $id;
}

/** Points theme slots, featured images and banners that used an old placeholder at its replacement. */
function cacdemo_placeholder_replace_references( $old, $new ) {
	$state = get_option( CACDEMO_SITE_THEME_OPTION, array() );
	if ( is_array( $state ) && ! empty( $state['images'] ) ) {
		array_walk_recursive( $state['images'], function ( &$value ) use ( $old, $new ) {
			$value = (int) $value === $old ? $new : $value;
		} );
		update_option( CACDEMO_SITE_THEME_OPTION, $state, false );
	}
	global $wpdb;
	foreach ( array( '_thumbnail_id', 'banner_image' ) as $meta_key ) {
		$wpdb->update( $wpdb->postmeta, array( 'meta_value' => (string) $new ), array( 'meta_key' => $meta_key, 'meta_value' => (string) $old ) ); // phpcs:ignore WordPress.DB.SlowDBQuery -- one-off design iteration.
	}
	wp_cache_flush();
}

$ids = array();
foreach ( cacdemo_placeholder_specs() as $key => $definition ) {
	list( $title, $collections, $spec ) = $definition;
	$ids[ $key ] = cacdemo_placeholder_attachment( $key, $title, $collections, $spec, $regenerate, $dry_run, $log );
}

/* ---------------------------------------------------------------- Assignments (only where nothing is set) */

$state   = cacdemo_site_theme_state();
$changed = false;
foreach ( cacdemo_site_theme_packages() as $theme => $package ) {
	foreach ( $package['slots'] as $slot ) {
		$key = $theme . '-' . $slot;
		if ( empty( $state['images'][ $theme ][ $slot ] ) && ! empty( $ids[ $key ] ) ) {
			$state['images'][ $theme ][ $slot ] = $ids[ $key ];
			$changed = true;
			$log( "theme $theme: slot $slot uses placeholder #{$ids[ $key ]}" );
		}
	}
}
if ( $changed && ! $dry_run ) {
	update_option( CACDEMO_SITE_THEME_OPTION, cacdemo_site_theme_sanitize_state( $state ), false );
}

foreach ( get_posts( array( 'post_type' => 'ministry', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $ministry ) {
	$key = 'ministry-' . $ministry->post_name;
	if ( ! has_post_thumbnail( $ministry ) && ! empty( $ids[ $key ] ) ) {
		if ( ! $dry_run ) {
			set_post_thumbnail( $ministry, $ids[ $key ] );
		}
		$log( "ministry {$ministry->post_name}: featured image is placeholder #{$ids[ $key ]}" );
	}
}

foreach ( array( 'about' => 'page-about-banner', 'new-here' => 'page-new-here-banner' ) as $slug => $key ) {
	$page = get_page_by_path( $slug );
	if ( $page && ! get_post_meta( $page->ID, 'banner_image', true ) && ! empty( $ids[ $key ] ) ) {
		if ( ! $dry_run ) {
			update_field( 'banner_image', $ids[ $key ], $page->ID );
			update_field( 'banner_position', 'right', $page->ID );
		}
		$log( "page $slug: banner is placeholder #{$ids[ $key ]}" );
	}
}
