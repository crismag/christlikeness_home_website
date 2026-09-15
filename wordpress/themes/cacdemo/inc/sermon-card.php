<?php
/**
 * Block markup for one sermon inside a Query Loop's Post Template, shared by the sermon
 * patterns. (A nested pattern block would render without the loop's post context, so the
 * card is shared as PHP instead.)
 *
 * The same markup serves both views: grid shows cover, series · date and title; list adds
 * the facts. Cover: the featured image (the sermon's saved still, or a series poster), otherwise a
 * typographic fallback. Empty facts collapse (theme CSS).
 */

defined( 'ABSPATH' ) || exit;

function cacdemo_sermon_fact( $label, $value_block ) {
	return '<!-- wp:group {"className":"cacdemo-sermon__fact","layout":{"type":"default"}} -->
<div class="wp-block-group cacdemo-sermon__fact">
<!-- wp:paragraph {"className":"cacdemo-sermon__label"} -->
<p class="cacdemo-sermon__label">' . esc_html( $label ) . '</p>
<!-- /wp:paragraph -->
' . $value_block . '
</div>
<!-- /wp:group -->';
}

function cacdemo_sermon_card_markup( $heading_level = 3 ) {
	$facts = cacdemo_sermon_fact( __( 'Series', 'cacdemo' ), '<!-- wp:post-terms {"term":"sermon_series"} /-->' )
		. cacdemo_sermon_fact( __( 'Speaker', 'cacdemo' ), '<!-- wp:post-terms {"term":"sermon_speaker"} /-->' )
		. cacdemo_sermon_fact( __( 'Scripture', 'cacdemo' ), '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"sermon_scripture"}}}}} -->
<p></p>
<!-- /wp:paragraph -->' )
		. cacdemo_sermon_fact( __( 'Location', 'cacdemo' ), '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"cacdemo/sermon","args":{"key":"location"}}}}} -->
<p></p>
<!-- /wp:paragraph -->' )
		. cacdemo_sermon_fact( __( 'Date', 'cacdemo' ), '<!-- wp:post-date /-->' );

	return '<!-- wp:group {"className":"cacdemo-sermon","layout":{"type":"default"}} -->
<div class="wp-block-group cacdemo-sermon">
<!-- wp:group {"className":"cacdemo-sermon__cover","layout":{"type":"default"}} -->
<div class="wp-block-group cacdemo-sermon__cover">
<!-- wp:post-featured-image {"aspectRatio":"16/9","className":"cacdemo-sermon__poster"} /-->
<!-- wp:group {"className":"cacdemo-sermon__fallback","layout":{"type":"default"}} -->
<div class="wp-block-group cacdemo-sermon__fallback">
<!-- wp:post-terms {"term":"sermon_series","className":"cacdemo-sermon__fallback-series"} /-->
<!-- wp:post-title {"level":0,"className":"cacdemo-sermon__fallback-title"} /-->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
<!-- wp:group {"className":"cacdemo-sermon__body","layout":{"type":"default"}} -->
<div class="wp-block-group cacdemo-sermon__body">
<!-- wp:group {"className":"cacdemo-sermon__kicker","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group cacdemo-sermon__kicker">
<!-- wp:post-terms {"term":"sermon_series"} /-->
<!-- wp:post-date /-->
</div>
<!-- /wp:group -->
<!-- wp:post-title {"level":' . (int) $heading_level . ',"isLink":true,"className":"cacdemo-sermon__title"} /-->
<!-- wp:group {"className":"cacdemo-sermon__facts","layout":{"type":"default"}} -->
<div class="wp-block-group cacdemo-sermon__facts">
' . $facts . '
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->';
}
