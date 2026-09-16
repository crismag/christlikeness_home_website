<?php
/**
 * Visitor colour palettes (docs/VISUAL-THEMES-DESIGN.md §7, DESIGN-SYSTEM.md → Themes and palettes).
 *
 * A palette is one file in palettes/<id>.json giving values for the ten colour presets (the semantic roles) plus the
 * palette-owned custom tokens (highlight, highlightInverse). It never changes structure, imagery or effects.
 * Palettes are applied with :root[data-palette="<id>"], so they override theme.json values without specificity tricks.
 * Every selectable palette must pass cacdemo_palette_contrast_report() (tested in scripts/test.sh).
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_PALETTE_ROLES  = array( 'paper', 'ink', 'stone', 'mist', 'rule', 'night', 'night-rule', 'night-muted', 'stage', 'stage-light' );
const CACDEMO_PALETTE_CUSTOM = array( 'highlight' => 'highlight', 'highlightInverse' => 'highlight-inverse' );

/**
 * Colour pairs the design actually uses, with the WCAG minimum each must meet.
 * [ foreground role, background role, minimum ratio, what it is ]
 */
function cacdemo_palette_contrast_pairs() {
	return array(
		array( 'ink', 'paper', 4.5, 'Body text on the page' ),
		array( 'stone', 'paper', 4.5, 'Muted text on the page' ),
		array( 'stage', 'paper', 4.5, 'Links and focus ring on the page' ),
		array( 'ink', 'mist', 4.5, 'Text on Mist bands' ),
		array( 'stone', 'mist', 4.5, 'Muted text on Mist bands' ),
		array( 'stage', 'mist', 4.5, 'Links on Mist bands' ),
		array( 'paper', 'ink', 4.5, 'Primary button text' ),
		array( 'paper', 'stage', 4.5, 'Text on accent buttons and hover states' ),
		array( 'paper', 'night', 4.5, 'Text on Night bands and the footer' ),
		array( 'night-muted', 'night', 4.5, 'Muted text on Night bands' ),
		array( 'stage-light', 'night', 4.5, 'Links and focus ring on Night bands' ),
		array( 'highlight', 'paper', 3.0, 'Highlight accents on the page (non-text)' ),
		array( 'highlight-inverse', 'night', 3.0, 'Highlight accents on Night bands (non-text)' ),
	);
}

/** All palettes, keyed by id, in display order. Invalid files are skipped (and reported by the contrast test). */
function cacdemo_palettes() {
	static $palettes = null;
	if ( null !== $palettes ) {
		return $palettes;
	}
	$palettes = array();
	foreach ( glob( get_theme_file_path( 'palettes/*.json' ) ) ?: array() as $file ) {
		$id   = basename( $file, '.json' );
		$data = json_decode( (string) file_get_contents( $file ), true );
		$palette = is_array( $data ) ? cacdemo_palette_normalize( $id, $data ) : null;
		if ( $palette ) {
			$palettes[ $id ] = $palette;
		}
	}
	uasort( $palettes, fn( $a, $b ) => $a['order'] <=> $b['order'] );
	return apply_filters( 'cacdemo_palettes', $palettes );
}

/** A palette definition with every role present as a #RRGGBB value, or null. */
function cacdemo_palette_normalize( $id, $data ) {
	if ( ! preg_match( '/^[a-z][a-z0-9-]*$/', $id ) || empty( $data['name'] ) ) {
		return null;
	}
	$colors = array();
	foreach ( CACDEMO_PALETTE_ROLES as $role ) {
		$value = strtoupper( (string) ( $data['colors'][ $role ] ?? '' ) );
		if ( ! preg_match( '/^#[0-9A-F]{6}$/', $value ) ) {
			return null;
		}
		$colors[ $role ] = $value;
	}
	$custom = array();
	foreach ( CACDEMO_PALETTE_CUSTOM as $key => $role ) {
		$value = strtoupper( (string) ( $data['custom'][ $key ] ?? '' ) );
		if ( ! preg_match( '/^#[0-9A-F]{6}$/', $value ) ) {
			return null;
		}
		$custom[ $role ] = $value;
	}
	return array(
		'id'          => $id,
		'name'        => (string) $data['name'],
		'description' => (string) ( $data['description'] ?? '' ),
		'order'       => (int) ( $data['order'] ?? 100 ),
		'colors'      => $colors,
		'custom'      => $custom,
	);
}

function cacdemo_palette( $id ) {
	return cacdemo_palettes()[ $id ] ?? null;
}

/** A valid palette id: the given one if it exists, otherwise "default". */
function cacdemo_palette_valid_id( $id ) {
	return is_string( $id ) && cacdemo_palette( $id ) ? $id : 'default';
}

/* ---------------------------------------------------------------- Contrast */

function cacdemo_relative_luminance( $hex ) {
	$hex      = ltrim( $hex, '#' );
	$channels = array_map( fn( $i ) => hexdec( substr( $hex, $i, 2 ) ) / 255, array( 0, 2, 4 ) );
	$linear   = array_map( fn( $c ) => $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4, $channels );
	return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
}

function cacdemo_contrast_ratio( $a, $b ) {
	$la = cacdemo_relative_luminance( $a );
	$lb = cacdemo_relative_luminance( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/**
 * Contrast results for a palette: [ [ label, foreground, background, ratio, minimum, pass ], … ].
 */
function cacdemo_palette_contrast_report( $palette ) {
	$values = $palette['colors'] + $palette['custom'];
	$report = array();
	foreach ( cacdemo_palette_contrast_pairs() as $pair ) {
		list( $fg, $bg, $min, $label ) = $pair;
		$ratio    = cacdemo_contrast_ratio( $values[ $fg ], $values[ $bg ] );
		$report[] = array( $label, $fg, $bg, round( $ratio, 2 ), $min, $ratio >= $min );
	}
	return $report;
}

function cacdemo_palette_passes( $palette ) {
	return ! in_array( false, array_column( cacdemo_palette_contrast_report( $palette ), 5 ), true );
}

/** Palettes offered to visitors: every palette that passes its contrast checks. */
function cacdemo_selectable_palettes() {
	return array_filter( cacdemo_palettes(), 'cacdemo_palette_passes' );
}

/* ---------------------------------------------------------------- CSS */

/** One rule per non-default palette. Values come only from the palette files (no stored or visitor CSS). */
function cacdemo_palette_css() {
	$css = '';
	foreach ( cacdemo_selectable_palettes() as $id => $palette ) {
		if ( 'default' === $id ) {
			continue; // theme.json already defines these values.
		}
		$vars = array();
		foreach ( $palette['colors'] as $role => $value ) {
			$vars[] = "--wp--preset--color--{$role}:{$value}";
		}
		foreach ( $palette['custom'] as $role => $value ) {
			$vars[] = "--wp--custom--{$role}:{$value}";
		}
		$css .= ':root[data-palette="' . $id . '"]{' . implode( ';', $vars ) . '}';
	}
	return $css;
}
