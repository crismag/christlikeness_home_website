<?php
/**
 * Social channels block. Variants:
 *   compact  icon + short name links in a row (home band, footers)
 *   list     icon, name, what's posted there and centre, one row per channel (Connect)
 *   feeds    one card per channel; Facebook pages that allow it show Facebook's page widget (Follow us)
 * Renders nothing when there are no channels.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$cacdemo_variant  = in_array( $attributes['variant'] ?? '', array( 'compact', 'list', 'feeds' ), true ) ? $attributes['variant'] : 'compact';
$cacdemo_channels = cacdemo_channels( array( 'platform' => sanitize_key( $attributes['platform'] ?? '' ), 'home' => ! empty( $attributes['homeOnly'] ) ) );
if ( ! $cacdemo_channels ) {
	return;
}

$cacdemo_items = '';
foreach ( $cacdemo_channels as $cacdemo_channel ) {
	$cacdemo_platform = cacdemo_channel_platform_label( $cacdemo_channel['platform'] );
	$cacdemo_icon     = sprintf( '<span class="cacdemo-channel__icon" data-platform="%s" aria-hidden="true"></span>', esc_attr( strtok( $cacdemo_channel['platform'], '_' ) ) );

	if ( 'compact' === $cacdemo_variant ) {
		$cacdemo_items .= sprintf(
			'<li class="cacdemo-channel"><a class="cacdemo-channel__link" href="%1$s" rel="noopener" target="_blank">%2$s<span class="cacdemo-channel__name">%3$s</span><span class="screen-reader-text"> (%4$s, %5$s)</span></a></li>',
			esc_url( $cacdemo_channel['url'] ),
			$cacdemo_icon,
			esc_html( $cacdemo_channel['short_name'] ),
			esc_html( $cacdemo_platform ),
			esc_html__( 'opens in a new tab', 'cacdemo' )
		);
		continue;
	}

	$cacdemo_meta = array_filter( array( $cacdemo_channel['purpose'], $cacdemo_channel['centre'] ? sprintf( /* translators: %s: centre name */ __( '%s centre', 'cacdemo' ), $cacdemo_channel['centre'] ) : '' ) );
	$cacdemo_body = sprintf(
		'<div class="cacdemo-channel__head">%1$s<div><h3 class="cacdemo-channel__title">%2$s</h3>%3$s</div></div><a class="cacdemo-channel__follow" href="%4$s" rel="noopener" target="_blank">%5$s<span class="screen-reader-text"> %6$s (%7$s)</span></a>',
		$cacdemo_icon,
		esc_html( $cacdemo_channel['name'] ),
		$cacdemo_meta ? '<p class="cacdemo-channel__meta">' . esc_html( implode( ' · ', $cacdemo_meta ) ) . '</p>' : '',
		esc_url( $cacdemo_channel['url'] ),
		esc_html( 'facebook_group' === $cacdemo_channel['platform'] ? __( 'Join the group', 'cacdemo' ) : sprintf( /* translators: %s: platform */ __( 'Follow on %s', 'cacdemo' ), $cacdemo_platform ) ),
		esc_html( $cacdemo_channel['name'] ),
		esc_html__( 'opens in a new tab', 'cacdemo' )
	);
	if ( 'feeds' === $cacdemo_variant && $cacdemo_channel['embed'] ) {
		$cacdemo_body .= sprintf(
			'<div class="cacdemo-channel__feed" data-href="%1$s" data-title="%2$s"><noscript><a href="%3$s">%4$s</a></noscript></div>',
			esc_attr( $cacdemo_channel['url'] ),
			/* translators: %s: page name */
			esc_attr( sprintf( __( 'Recent Facebook posts from %s', 'cacdemo' ), $cacdemo_channel['name'] ) ),
			esc_url( $cacdemo_channel['url'] ),
			esc_html__( 'See recent posts on Facebook', 'cacdemo' )
		);
	}
	$cacdemo_items .= '<li class="cacdemo-channel">' . $cacdemo_body . '</li>';
}

printf(
	'<div %1$s><ul class="cacdemo-channels__list">%2$s</ul></div>',
	get_block_wrapper_attributes( array( 'class' => 'cacdemo-channels is-' . $cacdemo_variant ) ),
	$cacdemo_items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
);
