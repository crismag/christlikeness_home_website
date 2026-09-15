<?php
/**
 * Social channels: the Facebook pages and groups (and later other platforms) the church manages.
 *
 * Records are SCF post type "channel" (config/scf/). They hold links and short details only; posts stay on the platform.
 * Nothing is fetched from Facebook by the server and no Facebook images are stored. The Follow us page may show
 * Facebook's own page widget: an iframe the visitor's browser loads from Facebook when the card scrolls near
 * (blocks/channels/view.js), with no Facebook SDK script on our pages.
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_CHANNEL_HOSTS = array(
	'facebook_page'  => array( 'facebook.com', 'fb.com' ),
	'facebook_group' => array( 'facebook.com', 'fb.com' ),
	'instagram'      => array( 'instagram.com' ),
	'youtube'        => array( 'youtube.com', 'youtu.be' ),
	'spotify'        => array( 'spotify.com' ),
	'x'              => array( 'x.com', 'twitter.com' ),
);

/**
 * Published channels in display order.
 *
 * @param array $args platform: '' | 'facebook' | a platform key; home: only channels marked for the home page.
 * @return array[] name, short_name, platform, url, purpose, centre (name or ''), embed (bool), id
 */
function cacdemo_channels( $args = array() ) {
	$args  = wp_parse_args( $args, array( 'platform' => '', 'home' => false ) );
	$posts = get_posts( array( 'post_type' => 'channel', 'post_status' => 'publish', 'numberposts' => 100, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	$list  = array();
	foreach ( $posts as $post ) {
		$platform = (string) get_post_meta( $post->ID, 'channel_platform', true );
		$url      = (string) get_post_meta( $post->ID, 'channel_url', true );
		if ( ! $url || ! cacdemo_channel_url_valid( $platform, $url ) ) {
			continue;
		}
		if ( $args['platform'] && $platform !== $args['platform'] && ! ( 'facebook' === $args['platform'] && str_starts_with( $platform, 'facebook_' ) ) ) {
			continue;
		}
		if ( $args['home'] && '0' === (string) get_post_meta( $post->ID, 'channel_home', true ) ) {
			continue;
		}
		$centre = (int) get_post_meta( $post->ID, 'channel_centre', true );
		$list[] = array(
			'id'         => $post->ID,
			'name'       => $post->post_title,
			'short_name' => (string) get_post_meta( $post->ID, 'channel_short_name', true ) ?: $post->post_title,
			'platform'   => $platform,
			'url'        => $url,
			'purpose'    => (string) get_post_meta( $post->ID, 'channel_purpose', true ),
			'centre'     => $centre && 'publish' === get_post_status( $centre ) ? get_the_title( $centre ) : '',
			'embed'      => 'facebook_page' === $platform && '0' !== (string) get_post_meta( $post->ID, 'channel_embed', true ),
		);
	}
	return $list;
}

function cacdemo_channel_url_valid( $platform, $url ) {
	$parts = wp_parse_url( $url );
	if ( empty( $parts['host'] ) || 'https' !== strtolower( $parts['scheme'] ?? '' ) || ! isset( CACDEMO_CHANNEL_HOSTS[ $platform ] ) ) {
		return false;
	}
	$host = strtolower( $parts['host'] );
	foreach ( CACDEMO_CHANNEL_HOSTS[ $platform ] as $domain ) {
		if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) {
			return true;
		}
	}
	return false;
}

add_filter( 'acf/validate_value/name=channel_url', 'cacdemo_channel_validate_url', 10, 4 );

/** The link must be https and on the chosen platform's site. */
function cacdemo_channel_validate_url( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === (string) $value ) {
		return $valid;
	}
	$platform = sanitize_key( wp_unslash( $_POST['acf']['field_cacdemo_channel_platform'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification -- SCF verifies its nonce before validation.
	return cacdemo_channel_url_valid( $platform, (string) $value ) ? $valid : __( 'Enter the https:// link to this page on the chosen platform.', 'cacdemo' );
}

/** Platform label for screen readers and link text. */
function cacdemo_channel_platform_label( $platform ) {
	$labels = array(
		'facebook_page'  => 'Facebook',
		'facebook_group' => __( 'Facebook group', 'cacdemo' ),
		'instagram'      => 'Instagram',
		'youtube'        => 'YouTube',
		'spotify'        => 'Spotify',
		'x'              => 'X',
	);
	return $labels[ $platform ] ?? '';
}
