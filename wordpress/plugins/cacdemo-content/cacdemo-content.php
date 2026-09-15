<?php
/**
 * Plugin Name:       Christlikeness Content
 * Description:       Christlikeness-specific content behaviour that core and Secure Custom Fields do not provide: sermon media, browsing and comments; ministry and serve role queries and bindings; section-scoped contextual publishing; the church's social channels.
 * Version:           0.2.0
 * Requires at least: 6.8
 * Requires PHP:      8.1
 * Author:            Christlikeness
 * License:           GPL-2.0-or-later
 * Text Domain:       cacdemo
 *
 * Content types and fields are defined with Secure Custom Fields (config/scf/), not here.
 * Stored data: capabilities on the administrator/editor roles, the Page contributor role and contributor assignments
 * (user meta), all kept on deactivation (includes/publishing.php). Nothing is deleted on deactivation.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/sermon-browse.php';
require_once __DIR__ . '/includes/sermon-comments.php';
require_once __DIR__ . '/includes/ministries.php';
require_once __DIR__ . '/includes/channels.php';
require_once __DIR__ . '/includes/publishing.php';
require_once __DIR__ . '/includes/publishing-admin.php';
require_once __DIR__ . '/includes/manage/manage.php';

add_action( 'init', 'cacdemo_content_register_blocks' );

function cacdemo_content_register_blocks() {
	wp_register_script_module( 'cacdemo-sermon-media', plugins_url( 'blocks/sermon-media/view.js', __FILE__ ), array(), (string) filemtime( __DIR__ . '/blocks/sermon-media/view.js' ) );
	register_block_type( __DIR__ . '/blocks/sermon-media' );
	register_block_type( __DIR__ . '/blocks/sermon-filters' );
	register_block_type( __DIR__ . '/blocks/sermon-table' );
	register_block_type( __DIR__ . '/blocks/publish-actions' );
	wp_register_script_module( 'cacdemo-channels', plugins_url( 'blocks/channels/view.js', __FILE__ ), array(), (string) filemtime( __DIR__ . '/blocks/channels/view.js' ) );
	register_block_type( __DIR__ . '/blocks/channels' );
}

/**
 * Block bindings source "cacdemo/sermon": sermon details derived from stored data, so theme
 * templates can show them in ordinary Paragraph blocks.
 *   location   the linked centre's name
 *   published  when the sermon was first posted on any platform (earliest source)
 *   updated    when the sermon record was last changed
 */
add_action( 'init', 'cacdemo_content_register_bindings' );

function cacdemo_content_register_bindings() {
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}
	register_block_bindings_source( 'cacdemo/sermon', array(
		'label'              => __( 'Sermon details', 'cacdemo' ),
		'uses_context'       => array( 'postId', 'postType' ),
		'get_value_callback' => 'cacdemo_content_sermon_binding',
	) );
}

function cacdemo_content_sermon_binding( $source_args, $block ) {
	$post_id = $block->context['postId'] ?? get_the_ID();
	if ( ! $post_id || 'sermon' !== get_post_type( $post_id ) || ! function_exists( 'get_field' ) ) {
		return null;
	}
	switch ( $source_args['key'] ?? '' ) {
		case 'location':
			$centre = (int) get_field( 'sermon_location', $post_id, false );
			return $centre && 'publish' === get_post_status( $centre ) ? get_the_title( $centre ) : '';
		case 'published':
			$times = array();
			foreach ( (array) get_field( 'sermon_sources', $post_id ) as $row ) {
				if ( ! empty( $row['published'] ) && strtotime( $row['published'] ) ) {
					$times[] = strtotime( $row['published'] );
				}
			}
			return $times ? wp_date( get_option( 'date_format' ), min( $times ) ) : '';
		case 'updated':
			// Only when the record changed after the sermon date (imports set both to the service date).
			$post = get_post( $post_id );
			return strtotime( $post->post_modified ) - strtotime( $post->post_date ) > DAY_IN_SECONDS ? get_the_modified_date( '', $post_id ) : '';
	}
	return null;
}

/**
 * Files uploaded to a sermon (notes, slides, study guides, stills) are filed and named by the sermon:
 *   uploads/YYYY/MM/YYYYMMDD_Sermon_Title_Original_Name.ext   (YYYY/MM and YYYYMMDD = the sermon date)
 * so the Media Library stays browsable by date and each file says which sermon it belongs to.
 * Applies to uploads made while editing a sermon (block editor REST upload or media modal); other uploads
 * are unchanged. Nothing is renamed after upload.
 */
add_filter( 'wp_handle_upload_prefilter', 'cacdemo_content_sermon_upload' );
add_filter( 'wp_handle_sideload_prefilter', 'cacdemo_content_sermon_upload' );

function cacdemo_content_upload_sermon_id() {
	// phpcs:ignore WordPress.Security.NonceVerification -- read-only; the upload itself is authorised by core.
	$id = absint( $_REQUEST['post'] ?? $_REQUEST['post_id'] ?? 0 );
	return $id && 'sermon' === get_post_type( $id ) ? $id : 0;
}

/** "YYYYMMDD_Title_Words" for a sermon (ASCII, underscores, shortened on a word boundary). */
function cacdemo_content_sermon_file_stem( $post_id, $max = 60 ) {
	$post = get_post( $post_id );
	preg_match_all( '/[A-Za-z0-9]+/', remove_accents( html_entity_decode( $post->post_title, ENT_QUOTES ) ), $m );
	$slug = '';
	foreach ( $m[0] as $word ) {
		$next = '' === $slug ? $word : "{$slug}_{$word}";
		if ( strlen( $next ) > $max ) {
			break;
		}
		$slug = $next;
	}
	return str_replace( '-', '', substr( $post->post_date, 0, 10 ) ) . '_' . ( '' === $slug ? 'Untitled' : $slug );
}

function cacdemo_content_sermon_upload( $file ) {
	$sermon = cacdemo_content_upload_sermon_id();
	if ( ! $sermon || ! empty( $file['error'] ) ) {
		return $file;
	}
	$stem = cacdemo_content_sermon_file_stem( $sermon );
	$ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	$orig = trim( preg_replace( '/[^A-Za-z0-9]+/', '_', remove_accents( pathinfo( $file['name'], PATHINFO_FILENAME ) ) ), '_' );
	if ( ! str_starts_with( $orig, strtok( $stem, '_' ) ) ) {
		$file['name'] = $stem . ( '' !== $orig ? '_' . substr( $orig, 0, 40 ) : '' ) . ( $ext ? ".$ext" : '' );
	}

	// File under the sermon date's YYYY/MM for this upload only.
	$folder = function ( $dirs ) use ( $sermon ) {
		$sub = '/' . substr( get_post( $sermon )->post_date, 0, 4 ) . '/' . substr( get_post( $sermon )->post_date, 5, 2 );
		if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
			$dirs['path']   = $dirs['basedir'] . $sub;
			$dirs['url']    = $dirs['baseurl'] . $sub;
			$dirs['subdir'] = $sub;
		}
		return $dirs;
	};
	add_filter( 'upload_dir', $folder );
	$done = function ( $upload ) use ( &$done, $folder ) {
		remove_filter( 'upload_dir', $folder );
		remove_filter( 'wp_handle_upload', $done );
		return $upload;
	};
	add_filter( 'wp_handle_upload', $done );
	return $file;
}

/**
 * "More in this series": a Query Loop whose query has "cacdemoSameSeries": true lists
 * other sermons that share a series with the sermon being viewed.
 * Core's Query Loop can only filter by fixed terms, not by the current post's terms.
 */
add_filter( 'query_loop_block_query_vars', 'cacdemo_content_same_series_query', 10, 2 );

function cacdemo_content_same_series_query( $query, $block ) {
	if ( empty( $block->context['query']['cacdemoSameSeries'] ) ) {
		return $query;
	}
	$post_id = get_queried_object_id();
	$terms   = $post_id ? wp_get_post_terms( $post_id, 'sermon_series', array( 'fields' => 'ids' ) ) : array();
	if ( is_wp_error( $terms ) || ! $terms ) {
		$query['post__in'] = array( 0 ); // No series: the list renders its empty state.
		return $query;
	}
	$query['post_type']    = 'sermon';
	$query['post__not_in'] = array( $post_id );
	$query['tax_query']    = array(
		array(
			'taxonomy' => 'sermon_series',
			'field'    => 'term_id',
			'terms'    => $terms,
		),
	);
	return $query;
}
