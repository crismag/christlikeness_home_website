<?php
/**
 * Default site theme: the everyday Christlikeness presentation. A package like every other, with no overrides:
 * base design tokens, the site default palette, and image slots an administrator fills with church photography
 * (placeholders until photos are chosen).
 */

defined( 'ABSPATH' ) || exit;

return array(
	'name'          => 'Default',
	'order'         => 0,
	'type'          => 'base',
	'description'   => 'The everyday Christlikeness presentation.',
	'palette'       => 'default',
	'tokens'        => array(),
	'slots'         => array( 'home-hero', 'page-hero', 'ministries-hero', 'sermons-hero', 'fallback' ),
	'effects'       => array(),
	'content_rules' => 'Real church photography first; placeholders are non-photographic and never show people or church life.',
);
