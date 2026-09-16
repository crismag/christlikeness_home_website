<?php
/**
 * Page hero. Image: the page's banner (or, for ministries, its featured image), otherwise the active site theme's image for
 * `themeSlot` when set (see includes/media.php). The first hero on a page loads eagerly with high priority (it is usually the
 * largest paint); decorative layers come from site-theme tokens. Without an image, the inner blocks render as a plain band.
 *
 * @var array    $attributes
 * @var string   $content
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$cacdemo_post    = get_post( (int) ( $block->context['postId'] ?? get_queried_object_id() ) );
$cacdemo_variant = in_array( $attributes['variant'] ?? '', array( 'landing', 'moderate' ), true ) ? $attributes['variant'] : 'moderate';
$cacdemo_slot    = sanitize_key( $attributes['themeSlot'] ?? '' );
$cacdemo_image   = cacdemo_resolve_image( 'hero', $cacdemo_post, array( 'theme_slot' => $cacdemo_slot ) );

if ( ! $cacdemo_image ) {
	printf( '<div %s>%s</div>', get_block_wrapper_attributes( array( 'class' => 'cacdemo-hero is-plain is-' . $cacdemo_variant ) ), $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks.
	return;
}

$cacdemo_first = empty( $GLOBALS['cacdemo_hero_rendered'] ); // Only the first hero on a page is a likely largest paint.
$GLOBALS['cacdemo_hero_rendered'] = true;

$cacdemo_img = wp_get_attachment_image( $cacdemo_image['id'], 'full', false, array(
	'class'         => 'cacdemo-hero__image',
	'sizes'         => '100vw',
	'alt'           => '',
	'loading'       => $cacdemo_first ? 'eager' : 'lazy',
	'fetchpriority' => $cacdemo_first ? 'high' : 'auto',
	'decoding'      => $cacdemo_first ? 'sync' : 'async',
) );

$cacdemo_style = '--cacdemo-hero-position:' . $cacdemo_image['position'] . ';' . ( $cacdemo_image['position_mobile'] ? '--cacdemo-hero-position-mobile:' . $cacdemo_image['position_mobile'] . ';' : '' );

printf(
	'<section %1$s><div class="cacdemo-hero__media">%2$s</div><div class="cacdemo-hero__scrim" aria-hidden="true"></div><div class="cacdemo-hero__decor" aria-hidden="true"></div><div class="cacdemo-hero__content">%3$s</div></section>',
	get_block_wrapper_attributes( array(
		'class'            => 'cacdemo-hero has-image is-' . $cacdemo_variant . ' is-image-' . $cacdemo_image['source'],
		'style'            => $cacdemo_style,
		'data-image-source' => $cacdemo_image['source'],
	) ),
	$cacdemo_img, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered inner blocks.
);
