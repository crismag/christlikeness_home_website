<?php
/**
 * Christlikeness theme: presentation-only PHP. Content types live in plugins/SCF.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'cacdemo_setup' );

function cacdemo_setup() {
	add_editor_style( array( 'assets/css/sermons.css', 'assets/css/ministries.css', 'assets/css/channels.css' ) );
}

add_action( 'init', 'cacdemo_register_assets' );

function cacdemo_register_assets() {
	// Versioned by file modification time, so browsers never keep an outdated copy after a change.
	$version = fn( $file ) => (string) filemtime( get_theme_file_path( $file ) );

	// Sermon cards, toolbar and sermon page; loaded only where a Query Loop or sermon media renders.
	foreach ( array( 'core/query', 'cacdemo/sermon-media', 'cacdemo/sermon-filters', 'cacdemo/sermon-table' ) as $block ) {
		wp_enqueue_block_style( $block, array(
			'handle' => 'cacdemo-sermons',
			'src'    => get_theme_file_uri( 'assets/css/sermons.css' ),
			'path'   => get_theme_file_path( 'assets/css/sermons.css' ),
			'ver'    => $version( 'assets/css/sermons.css' ),
		) );
	}

	// Ministry list and ministry page; both render Query Loops.
	wp_enqueue_block_style( 'core/query', array(
		'handle' => 'cacdemo-ministries',
		'src'    => get_theme_file_uri( 'assets/css/ministries.css' ),
		'path'   => get_theme_file_path( 'assets/css/ministries.css' ),
		'ver'    => $version( 'assets/css/ministries.css' ),
	) );

	// Social channels (home band, Connect, Follow us).
	wp_enqueue_block_style( 'cacdemo/channels', array(
		'handle' => 'cacdemo-channels',
		'src'    => get_theme_file_uri( 'assets/css/channels.css' ),
		'path'   => get_theme_file_path( 'assets/css/channels.css' ),
		'ver'    => $version( 'assets/css/channels.css' ),
	) );

	wp_register_script( 'cacdemo-view-switch', get_theme_file_uri( 'assets/js/view-switch.js' ), array(), $version( 'assets/js/view-switch.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
}
