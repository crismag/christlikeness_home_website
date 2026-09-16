<?php
/**
 * Winter — natural-season site theme. Atmosphere only: a seasonal tint in the hero overlay, a small mark in image heroes,
 * a faint texture on Night bands and a seasonal rule under image heroes (style.css), and
 * optional falling snow in the first image hero (assets/js/effects.js). Colours come from the visitor's
 * palette (recommended: navy); typography, layout and components are unchanged.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'          => 'Winter',
	'order'         => 40,
	'type'          => 'natural-season',
	'description'   => 'Quiet cold light: a cool blue tint over heroes and a small frost mark. The natural season only.',
	'palette'       => 'navy',
	'tokens'        => array(
		'decor'               => 'var(--wp--preset--color--stage-light)',
		'hero-overlay'        => 'linear-gradient(90deg, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #1B2E4A) 90%, transparent) 0%, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #1B2E4A) 64%, transparent) 48%, color-mix(in srgb, #1B2E4A 12%, transparent) 100%)',
		'hero-overlay-mobile' => 'linear-gradient(0deg, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #1B2E4A) 93%, transparent) 0%, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #1B2E4A) 72%, transparent) 55%, color-mix(in srgb, #1B2E4A 22%, transparent) 100%)',
		'texture'             => 'url(' . add_query_arg( 'ver', (string) filemtime( __DIR__ . '/texture.svg' ), get_theme_file_uri( 'site-themes/winter/texture.svg' ) ) . ')',
		'hero-mark'           => 'url(' . add_query_arg( 'ver', (string) filemtime( __DIR__ . '/mark.svg' ), get_theme_file_uri( 'site-themes/winter/mark.svg' ) ) . ')',
	),
	'slots'         => array( 'home-hero', 'ministries-hero', 'sermons-hero', 'fallback' ),
	'effects'       => array( 'snow' => array( 'subtle', 'enhanced' ) ),
	'content_rules' => 'Natural season only: snow, frost, cold light, warmth indoors. No Christmas imagery, symbols or terminology (no trees, stars, ornaments, red-and-green schemes).',
);
