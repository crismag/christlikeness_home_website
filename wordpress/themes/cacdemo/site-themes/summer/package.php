<?php
/**
 * Summer — natural-season site theme. Atmosphere only: a seasonal tint in the hero overlay, a small mark in image heroes,
 * a faint texture on Night bands and a seasonal rule under image heroes (style.css). Colours come from the visitor's
 * palette (recommended: blue); typography, layout and components are unchanged.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'          => 'Summer',
	'order'         => 20,
	'type'          => 'natural-season',
	'description'   => 'Long bright days: a clear sky tint over heroes and a small sun mark.',
	'palette'       => 'blue',
	'tokens'        => array(
		'decor'               => 'var(--wp--custom--highlight-inverse)',
		'hero-overlay'        => 'linear-gradient(90deg, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #123A5A) 90%, transparent) 0%, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #123A5A) 64%, transparent) 48%, color-mix(in srgb, #123A5A 12%, transparent) 100%)',
		'hero-overlay-mobile' => 'linear-gradient(0deg, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #123A5A) 93%, transparent) 0%, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #123A5A) 72%, transparent) 55%, color-mix(in srgb, #123A5A 22%, transparent) 100%)',
		'texture'             => 'url(' . add_query_arg( 'ver', (string) filemtime( __DIR__ . '/texture.svg' ), get_theme_file_uri( 'site-themes/summer/texture.svg' ) ) . ')',
		'hero-mark'           => 'url(' . add_query_arg( 'ver', (string) filemtime( __DIR__ . '/mark.svg' ), get_theme_file_uri( 'site-themes/summer/mark.svg' ) ) . ')',
	),
	'slots'         => array( 'home-hero', 'ministries-hero', 'sermons-hero', 'fallback' ),
	'effects'       => array(),
	'content_rules' => 'Natural season only: sun, water, open air, gatherings outdoors. No holiday symbols.',
);
