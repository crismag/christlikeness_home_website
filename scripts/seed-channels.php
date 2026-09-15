<?php
/**
 * Seeds the social channels the church manages and puts "Follow Us" under Connect in the Main menu.
 *
 *   wp --path=/mnt/ai/workspaces/cacdemo eval-file scripts/seed-channels.php [dry-run]
 *
 * Source: the church's list of Facebook pages and groups (2026-09-16); names as shown on Facebook. Purpose lines only where
 * the old website described the channel. Idempotent and non-destructive: channels are matched by link and never
 * overwritten, so edits made in WordPress survive a re-run. Run scripts/seed-content.php first (it creates the Follow Us page).
 */

defined( 'WP_CLI' ) || exit;

$dry_run = in_array( 'dry-run', $args ?? array(), true );
$log     = function ( $message ) use ( $dry_run ) {
	WP_CLI::log( ( $dry_run ? '[dry-run] ' : '' ) . $message );
};

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
if ( ! $admins ) {
	WP_CLI::error( 'No administrator account found.' );
}
wp_set_current_user( $admins[0]->ID );

// name, short name, platform, link, purpose, centre slug, show Facebook's page widget.
// christlikecanada: Facebook shows no public details for it (likely age or country restrictions), so its widget renders blank.
$channels = array(
	array( 'Christlikeness', 'Christlikeness', 'facebook_page', 'https://www.facebook.com/christlikecanada', '', '', false ),
	array( 'Christlikeness Online', 'Christlikeness Online', 'facebook_group', 'https://www.facebook.com/groups/christlikenessonline', 'Sermons and church activities', '', false ),
	array( 'Christlikeness - North York Campus', 'North York', 'facebook_page', 'https://www.facebook.com/profile.php?id=61574677416187', '', 'north-york', true ),
	array( 'Christlikeness - Scarborough Campus', 'Scarborough', 'facebook_page', 'https://www.facebook.com/ChristlikenessScarborough', '', 'scarborough', true ),
	array( 'Radical YM', 'Radical YM', 'facebook_page', 'https://www.facebook.com/radicalym', 'R.A.D.I.C.A.L youth ministry', '', true ),
);

foreach ( $channels as $order => $c ) {
	list( $name, $short, $platform, $url, $purpose, $centre_slug, $embed ) = $c;
	$found = get_posts( array( 'post_type' => 'channel', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'channel_url', 'value' => $url ) ) ) );
	if ( $found ) {
		$log( "channel exists: $name (#{$found[0]})" );
		continue;
	}
	if ( $dry_run ) {
		$log( "would create channel: $name" );
		continue;
	}
	$id = wp_insert_post( array( 'post_type' => 'channel', 'post_status' => 'publish', 'post_title' => $name, 'menu_order' => $order ), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id );
	}
	update_field( 'channel_short_name', $short, $id );
	update_field( 'channel_platform', $platform, $id );
	update_field( 'channel_url', $url, $id );
	update_field( 'channel_purpose', $purpose, $id );
	update_field( 'channel_embed', $embed ? 1 : 0, $id );
	update_field( 'channel_home', 1, $id );
	$centre = $centre_slug ? get_page_by_path( $centre_slug, OBJECT, 'centre' ) : null;
	if ( $centre ) {
		update_field( 'channel_centre', $centre->ID, $id );
	}
	$log( "created channel: $name (#$id)" );
}

/* Main menu: "Connect" becomes a submenu with Connect and Follow Us. */
$nav     = get_posts( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'title' => 'Main', 'numberposts' => 1 ) );
$connect = get_page_by_path( 'connect', OBJECT, 'page' );
$follow  = get_page_by_path( 'follow-us', OBJECT, 'page' );
if ( ! $nav || ! $connect || ! $follow ) {
	WP_CLI::warning( 'Main menu, Connect or Follow Us page not found; menu not changed (run seed-content.php first).' );
	return;
}
if ( str_contains( $nav[0]->post_content, '"id":' . $follow->ID . ',' ) || str_contains( $nav[0]->post_content, '"id":' . $follow->ID . '}' ) ) {
	$log( 'Follow Us is already in the Main menu' );
	return;
}
$blocks = parse_blocks( $nav[0]->post_content );
$link   = fn( $label, $page ) => array( 'blockName' => 'core/navigation-link', 'attrs' => array( 'label' => $label, 'type' => 'page', 'id' => $page->ID, 'url' => get_permalink( $page ), 'kind' => 'post-type' ), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() );
foreach ( $blocks as $i => $block ) {
	if ( 'core/navigation-link' === $block['blockName'] && (int) ( $block['attrs']['id'] ?? 0 ) === $connect->ID ) {
		$inner        = array( $link( 'Connect', $connect ), $link( 'Follow Us', $follow ) );
		$blocks[ $i ] = array(
			'blockName'    => 'core/navigation-submenu',
			'attrs'        => array( 'label' => 'Connect', 'type' => 'page', 'id' => $connect->ID, 'url' => get_permalink( $connect ), 'kind' => 'post-type' ),
			'innerBlocks'  => $inner,
			'innerHTML'    => '',
			'innerContent' => array( null, null ),
		);
		if ( $dry_run ) {
			$log( 'would put Follow Us under Connect in the Main menu' );
		} else {
			wp_update_post( array( 'ID' => $nav[0]->ID, 'post_content' => serialize_blocks( $blocks ) ) );
			$log( 'put Follow Us under Connect in the Main menu' );
		}
		return;
	}
}
WP_CLI::warning( 'No Connect link in the Main menu; Follow Us not added.' );
