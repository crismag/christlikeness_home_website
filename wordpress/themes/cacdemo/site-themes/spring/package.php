<?php
/**
 * Spring — natural-season site theme. Atmosphere only: a seasonal tint in the hero overlay, a small mark in image heroes,
 * a faint texture on Night bands and a seasonal rule under image heroes (style.css). Colours come from the visitor's
 * palette (recommended: sage); typography, layout and components are unchanged.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'          => 'Spring',
	'order'         => 10,
	'type'          => 'natural-season',
	'description'   => 'Fresh light and new growth: a soft green tint over heroes and a small sprig mark.',
	'palette'       => 'sage',
	'tokens'        => array(
		'decor'               => 'var(--wp--preset--color--stage-light)',
		'hero-overlay'        => 'linear-gradient(90deg, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #1F4A3A) 90%, transparent) 0%, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #1F4A3A) 64%, transparent) 48%, color-mix(in srgb, #1F4A3A 12%, transparent) 100%)',
		'hero-overlay-mobile' => 'linear-gradient(0deg, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #1F4A3A) 93%, transparent) 0%, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #1F4A3A) 72%, transparent) 55%, color-mix(in srgb, #1F4A3A 22%, transparent) 100%)',
		'texture'             => 'url(' . add_query_arg( 'ver', (string) filemtime( __DIR__ . '/texture.svg' ), get_theme_file_uri( 'site-themes/spring/texture.svg' ) ) . ')',
		'hero-mark'           => 'url(' . add_query_arg( 'ver', (string) filemtime( __DIR__ . '/mark.svg' ), get_theme_file_uri( 'site-themes/spring/mark.svg' ) ) . ')',
	),
	'slots'         => array( 'home-hero', 'ministries-hero', 'sermons-hero', 'fallback' ),
	'effects'       => array(),
	'content_rules' => 'Natural season only: new growth, light, fresh air. No holiday symbols. Photos of church life in spring first.',
);
