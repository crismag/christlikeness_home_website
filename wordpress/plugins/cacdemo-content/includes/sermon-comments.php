<?php
/**
 * Sermon comments: core WordPress comments, off unless an admin turns them on.
 *
 * - Site switch: Settings → Discussion → "Sermon comments" (off by default). While off, no sermon shows or
 *   accepts comments.
 * - Per sermon: the native "Allow comments" setting; new sermons start closed.
 * - Comments on sermons publish immediately (church decision, 2026-09-16); spam and disallowed words are still
 *   caught by core.
 * - The theme places the comments in a collapsed "Comments" bar (core Details block with class
 *   "cacdemo-comments"). This file hides that bar when comments are unavailable and adds the count, plus an
 *   invitation to name the preacher when the sermon has no speaker.
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_SERMON_COMMENTS_OPTION = 'cacdemo_sermon_comments';

add_action( 'admin_init', 'cacdemo_sermon_comments_setting' );

function cacdemo_sermon_comments_setting() {
	register_setting( 'discussion', CACDEMO_SERMON_COMMENTS_OPTION, array( 'type' => 'boolean', 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	add_settings_field(
		CACDEMO_SERMON_COMMENTS_OPTION,
		__( 'Sermon comments', 'cacdemo' ),
		function () {
			printf(
				'<label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label><p class="description">%4$s</p>',
				esc_attr( CACDEMO_SERMON_COMMENTS_OPTION ),
				checked( (bool) get_option( CACDEMO_SERMON_COMMENTS_OPTION ), true, false ),
				esc_html__( 'Allow comments on sermons', 'cacdemo' ),
				esc_html__( 'When on, each sermon\'s own "Allow comments" setting decides. When off, no sermon shows or accepts comments. New sermons start with comments closed.', 'cacdemo' )
			);
		},
		'discussion',
		'default'
	);
}

function cacdemo_sermon_comments_enabled() {
	return (bool) get_option( CACDEMO_SERMON_COMMENTS_OPTION, false );
}

add_filter( 'comments_open', 'cacdemo_sermon_comments_open', 10, 2 );

function cacdemo_sermon_comments_open( $open, $post_id ) {
	return 'sermon' === get_post_type( $post_id ) && ! cacdemo_sermon_comments_enabled() ? false : $open;
}

add_filter( 'get_default_comment_status', 'cacdemo_sermon_default_comment_status', 10, 2 );

function cacdemo_sermon_default_comment_status( $status, $post_type ) {
	return 'sermon' === $post_type ? 'closed' : $status;
}

add_filter( 'pre_comment_approved', 'cacdemo_sermon_comment_approved', 10, 2 );

/** Publish sermon comments at once, unless core marked them spam or trash. */
function cacdemo_sermon_comment_approved( $approved, $commentdata ) {
	if ( 0 === $approved && 'sermon' === get_post_type( (int) ( $commentdata['comment_post_ID'] ?? 0 ) ) ) {
		return 1;
	}
	return $approved;
}

add_filter( 'render_block_core/details', 'cacdemo_sermon_comments_bar', 10, 2 );

function cacdemo_sermon_comments_bar( $content, $block ) {
	if ( ! str_contains( $block['attrs']['className'] ?? '', 'cacdemo-comments' ) ) {
		return $content;
	}
	$post_id = get_the_ID();
	if ( ! $post_id || 'sermon' !== get_post_type( $post_id ) || ! cacdemo_sermon_comments_enabled() ) {
		return '';
	}
	$count = (int) get_comments_number( $post_id );
	if ( ! comments_open( $post_id ) && 0 === $count ) {
		return '';
	}
	$summary = sprintf( '<span class="cacdemo-comments__title">%s</span>', esc_html__( 'Comments', 'cacdemo' ) );
	if ( $count ) {
		$summary .= sprintf( ' <span class="cacdemo-comments__count">%d</span>', $count );
	}
	if ( comments_open( $post_id ) && ! has_term( '', 'sermon_speaker', $post_id ) ) {
		$summary .= sprintf( ' <span class="cacdemo-comments__ask">%s</span>', esc_html__( 'Know who preached? Let us know.', 'cacdemo' ) );
	}
	return preg_replace( '#<summary>.*?</summary>#s', '<summary>' . $summary . '</summary>', $content, 1 );
}
