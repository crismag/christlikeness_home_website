<?php
/**
 * Fall — natural-season site theme. Atmosphere only: a seasonal tint in the hero overlay, a small mark in image heroes,
 * a faint texture on Night bands and a seasonal rule under image heroes (style.css), and
 * optional falling leaves in the first image hero (assets/js/effects.js). Colours come from the visitor's
 * palette (recommended: chocolate); typography, layout and components are unchanged.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'          => 'Fall',
	'order'         => 30,
	'type'          => 'natural-season',
	'description'   => 'Harvest colour and thankful rest: a warm amber tint over heroes and a small leaf mark.',
	'palette'       => 'chocolate',
	'tokens'        => array(
		'decor'               => 'var(--wp--custom--highlight-inverse)',
		'hero-overlay'        => 'linear-gradient(90deg, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #4A2410) 90%, transparent) 0%, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #4A2410) 64%, transparent) 48%, color-mix(in srgb, #4A2410 12%, transparent) 100%)',
		'hero-overlay-mobile' => 'linear-gradient(0deg, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #4A2410) 93%, transparent) 0%, color-mix(in srgb, color-mix(in srgb, var(--wp--preset--color--night) 82%, #4A2410) 72%, transparent) 55%, color-mix(in srgb, #4A2410 22%, transparent) 100%)',
		'texture'             => 'url(' . add_query_arg( 'ver', (string) filemtime( __DIR__ . '/texture.svg' ), get_theme_file_uri( 'site-themes/fall/texture.svg' ) ) . ')',
		'hero-mark'           => 'url(' . add_query_arg( 'ver', (string) filemtime( __DIR__ . '/mark.svg' ), get_theme_file_uri( 'site-themes/fall/mark.svg' ) ) . ')',
	),
	'slots'         => array( 'home-hero', 'ministries-hero', 'sermons-hero', 'fallback' ),
	'effects'       => array( 'leaves' => array( 'subtle', 'enhanced' ) ),
	'content_rules' => 'Natural season only: leaves, harvest, thankfulness, warm light. No Halloween or other holiday imagery.',
);
