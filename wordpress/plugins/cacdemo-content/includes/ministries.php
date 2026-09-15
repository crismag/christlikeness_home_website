<?php
/**
 * Ministries and Serve roles: queries and derived values for the theme's ministry templates.
 *
 * Model (docs/MINISTRIES-SERVE-DESIGN.md, decision 2026-09-16): a Ministry (what we do and why) has Serve roles
 * (ways to serve). A role's status decides visibility: Ongoing and Needed now are public; Paused and Filled are not.
 * Everything here is public presentation — never internal notes, rosters or private contacts.
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_PUBLIC_ROLE_STATUSES = array( 'ongoing', 'needed' );

/** Public roles of a ministry, in their display order. */
function cacdemo_ministry_roles( $ministry_id ) {
	return get_posts( array(
		'post_type'   => 'serve_role',
		'post_status' => 'publish',
		'numberposts' => -1,
		'orderby'     => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'meta_query'  => array(
			'relation' => 'AND',
			array( 'key' => 'role_ministry', 'value' => (string) $ministry_id ),
			array( 'key' => 'role_status', 'value' => CACDEMO_PUBLIC_ROLE_STATUSES, 'compare' => 'IN' ),
		),
	) );
}

/**
 * Ways to serve: a Query Loop with "cacdemoMinistryRoles": true lists the public roles of the ministry being viewed
 * (or of the ministry in the surrounding loop).
 */
add_filter( 'query_loop_block_query_vars', 'cacdemo_ministry_roles_query', 10, 2 );

function cacdemo_ministry_roles_query( $query, $block ) {
	if ( empty( $block->context['query']['cacdemoMinistryRoles'] ) ) {
		return $query;
	}
	$ministry = is_singular( 'ministry' ) ? get_queried_object_id() : (int) ( $block->context['postId'] ?? 0 );
	return array_merge( $query, array(
		'post_type'  => 'serve_role',
		'orderby'    => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'meta_query' => array(
			'relation' => 'AND',
			array( 'key' => 'role_ministry', 'value' => (string) $ministry ),
			array( 'key' => 'role_status', 'value' => CACDEMO_PUBLIC_ROLE_STATUSES, 'compare' => 'IN' ),
		),
	) );
}

/**
 * Updates: a Query Loop with "cacdemoMinistryUpdates": true lists the published posts linked to the ministry being viewed
 * (SCF field update_ministry), newest first.
 */
add_filter( 'query_loop_block_query_vars', 'cacdemo_ministry_updates_query', 10, 2 );

function cacdemo_ministry_updates_query( $query, $block ) {
	if ( empty( $block->context['query']['cacdemoMinistryUpdates'] ) ) {
		return $query;
	}
	return array_merge( $query, array(
		'post_type'           => 'post',
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'meta_query'          => array( array( 'key' => 'update_ministry', 'value' => (string) ( is_singular( 'ministry' ) ? get_queried_object_id() : 0 ) ) ),
	) );
}

/**
 * Block bindings source "cacdemo/ministry" (for ministry and serve role blocks):
 *   roles             "Audio · Video · Lighting +3" — the ministry's ways to serve, for cards
 *   needed            "Needed now" when any role (ministry) or this role (serve role) is needed now
 *   experience        the role's experience label ("Training available")
 *   invite_heading    the ministry's invitation heading, or "Interested in serving with <ministry>?"
 *   invite_text       the ministry's invitation text, or the default invitation
 *   contact_url       the ministry's public contact link, or the Contact page with the ministry named
 * and for a post (ministry update):
 *   ministry_name, ministry_url   the linked ministry, empty when the post is church-wide
 */
add_action( 'init', 'cacdemo_ministry_register_bindings' );

function cacdemo_ministry_register_bindings() {
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}
	register_block_bindings_source( 'cacdemo/ministry', array(
		'label'              => __( 'Ministry details', 'cacdemo' ),
		'uses_context'       => array( 'postId', 'postType' ),
		'get_value_callback' => 'cacdemo_ministry_binding',
	) );
}

function cacdemo_ministry_binding( $source_args, $block ) {
	$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
	$type    = get_post_type( $post_id );
	if ( ! $post_id || ! in_array( $type, array( 'ministry', 'serve_role', 'post' ), true ) || ! function_exists( 'get_field' ) ) {
		return null;
	}
	$key = $source_args['key'] ?? '';

	if ( 'post' === $type ) {
		$ministry = (int) get_post_meta( $post_id, 'update_ministry', true );
		if ( ! $ministry || 'publish' !== get_post_status( $ministry ) ) {
			return '';
		}
		return 'ministry_url' === $key ? get_permalink( $ministry ) : ( 'ministry_name' === $key ? get_the_title( $ministry ) : null );
	}

	if ( 'serve_role' === $type ) {
		switch ( $key ) {
			case 'needed':
				return 'needed' === get_field( 'role_status', $post_id ) ? __( 'Needed now', 'cacdemo' ) : '';
			case 'experience':
				return (string) get_field( 'role_experience', $post_id );
		}
		return null;
	}

	$name = get_field( 'ministry_short_name', $post_id ) ?: get_the_title( $post_id );
	switch ( $key ) {
		case 'roles':
			$titles = wp_list_pluck( cacdemo_ministry_roles( $post_id ), 'post_title' );
			$shown  = array_slice( $titles, 0, 4 );
			return $titles ? implode( ' · ', $shown ) . ( count( $titles ) > 4 ? ' +' . ( count( $titles ) - 4 ) : '' ) : '';
		case 'needed':
			foreach ( cacdemo_ministry_roles( $post_id ) as $role ) {
				if ( 'needed' === get_field( 'role_status', $role->ID ) ) {
					return __( 'Needed now', 'cacdemo' );
				}
			}
			return '';
		case 'invite_heading':
			/* translators: %s: ministry name */
			return get_field( 'ministry_invite_heading', $post_id ) ?: sprintf( __( 'Interested in serving with %s?', 'cacdemo' ), $name );
		case 'invite_text':
			return get_field( 'ministry_invite_text', $post_id ) ?: __( 'You do not need to know everything before getting involved. Tell us where you are interested, and someone from the ministry will help you with the next step.', 'cacdemo' );
		case 'contact_url':
			$url = get_field( 'ministry_contact_url', $post_id );
			return $url ?: add_query_arg( 'ministry', get_post_field( 'post_name', $post_id ), home_url( '/contact/' ) );
		case 'serve_label':
			/* translators: %s: ministry name */
			return sprintf( __( 'Serve with %s', 'cacdemo' ), $name );
	}
	return null;
}
