<?php
/**
 * Contextual publishing: who may create and edit content in which section.
 *
 * Design: docs/CONTRIBUTOR-PUBLISHING.md. Everything here uses the core capability system, so the same rules apply in the
 * block editor, wp-admin lists, REST requests and uploads. Nothing is trusted from the browser: every check runs against
 * the actual post and its stored section.
 *
 * Levels (user-facing):
 *   Contributor    create and publish in a section; edit, unpublish and trash their own items there
 *   Publisher      the same for anyone's items in the section, and edit the section page (e.g. the ministry record)
 *   Content Admin  core Editor role (all content) + managing contributors; administrators can do everything
 *
 * Sections (scopes) are generated: "sermons", and "ministry:<ID>" for each published ministry. A ministry section covers
 * its updates (core posts linked by the SCF field update_ministry); Publishers also cover the ministry page and its ways
 * to serve.
 * Assignments are user meta (cacdemo_publishing_scopes: scope => level).
 *
 * Stored by this file: capabilities added to the administrator and editor roles and one role, "Page contributor"
 * (read only), created once and kept on deactivation so administrators and editors never lose access to content.
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_PUBLISHING_VERSION = 3;
const CACDEMO_SCOPES_META        = 'cacdemo_publishing_scopes';
const CACDEMO_CONTRIBUTOR_ROLE   = 'page_contributor';
const CACDEMO_MANAGE_CAP         = 'cacdemo_manage_contributors';

/** Primitive capabilities of a post type registered with capability_type [ singular, plural ]. */
function cacdemo_publishing_type_caps( $plural ) {
	$caps = array();
	foreach ( array( 'edit', 'edit_others', 'edit_private', 'edit_published', 'publish', 'read_private', 'delete', 'delete_others', 'delete_private', 'delete_published' ) as $verb ) {
		$caps[] = "{$verb}_{$plural}";
	}
	return $caps;
}

/* ---------------------------------------------------------------- Roles */

add_action( 'init', 'cacdemo_publishing_install_roles', 20 );

function cacdemo_publishing_install_roles() {
	if ( (int) get_option( 'cacdemo_publishing_version' ) >= CACDEMO_PUBLISHING_VERSION ) {
		return;
	}
	foreach ( array( 'administrator', 'editor' ) as $name ) {
		$role = get_role( $name );
		if ( ! $role ) {
			continue;
		}
		foreach ( array( 'sermons', 'ministries', 'serve_roles', 'channels' ) as $plural ) {
			foreach ( cacdemo_publishing_type_caps( $plural ) as $cap ) {
				$role->add_cap( $cap );
			}
		}
		$role->add_cap( 'create_ministries' );
		$role->add_cap( CACDEMO_MANAGE_CAP );
	}
	if ( ! get_role( CACDEMO_CONTRIBUTOR_ROLE ) ) {
		add_role( CACDEMO_CONTRIBUTOR_ROLE, __( 'Page contributor', 'cacdemo' ), array( 'read' => true ) );
	}
	update_option( 'cacdemo_publishing_version', CACDEMO_PUBLISHING_VERSION );
}

add_filter( 'register_post_type_args', 'cacdemo_publishing_post_type_args', 10, 2 );

/**
 * Creating a ministry needs its own capability; otherwise WordPress uses "edit ministries", which ministry Publishers
 * hold to edit their own page. Without this plugin the default applies again, and administrators and editors keep both.
 */
function cacdemo_publishing_post_type_args( $args, $post_type ) {
	if ( 'ministry' === $post_type ) {
		$args['capabilities']['create_posts'] = 'create_ministries';
	}
	return $args;
}

/* ---------------------------------------------------------------- Sections and assignments */

/** Assignable sections: [ scope => label ], generated from content. */
function cacdemo_publishing_sections() {
	$sections = array( 'sermons' => __( 'Sermons', 'cacdemo' ) );
	$ministries = get_posts( array( 'post_type' => 'ministry', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
	foreach ( $ministries as $ministry ) {
		$sections[ 'ministry:' . $ministry->ID ] = $ministry->post_title;
	}
	return $sections;
}

function cacdemo_publishing_levels() {
	return array(
		'contributor' => __( 'Contributor', 'cacdemo' ),
		'publisher'   => __( 'Publisher', 'cacdemo' ),
	);
}

/** A user's assignments, limited to valid scopes and levels. */
function cacdemo_publishing_scopes( $user_id ) {
	$stored = get_user_meta( (int) $user_id, CACDEMO_SCOPES_META, true );
	$scopes = array();
	foreach ( is_array( $stored ) ? $stored : array() as $scope => $level ) {
		if ( ! isset( cacdemo_publishing_levels()[ $level ] ) ) {
			continue;
		}
		if ( 'sermons' === $scope || ( preg_match( '/^ministry:(\d+)$/', $scope, $m ) && 'ministry' === get_post_type( (int) $m[1] ) ) ) {
			$scopes[ $scope ] = $level;
		}
	}
	return $scopes;
}

/** Saves assignments; unknown scopes and levels are dropped. */
function cacdemo_publishing_set_scopes( $user_id, $scopes ) {
	$sections = cacdemo_publishing_sections();
	$clean    = array();
	foreach ( (array) $scopes as $scope => $level ) {
		if ( isset( $sections[ $scope ], cacdemo_publishing_levels()[ $level ] ) ) {
			$clean[ $scope ] = $level;
		}
	}
	if ( $clean ) {
		update_user_meta( (int) $user_id, CACDEMO_SCOPES_META, $clean );
	} else {
		delete_user_meta( (int) $user_id, CACDEMO_SCOPES_META );
	}
	return $clean;
}

function cacdemo_publishing_level( $user_id, $scope ) {
	return cacdemo_publishing_scopes( $user_id )[ $scope ] ?? '';
}

/** IDs of the ministries a user is assigned to (any level, or Publisher only). */
function cacdemo_publishing_user_ministries( $user_id, $publisher_only = false ) {
	$ids = array();
	foreach ( cacdemo_publishing_scopes( $user_id ) as $scope => $level ) {
		if ( str_starts_with( $scope, 'ministry:' ) && ( ! $publisher_only || 'publisher' === $level ) ) {
			$ids[] = (int) substr( $scope, 9 );
		}
	}
	return $ids;
}

/** Whether posts (updates) are governed by assignments for this user: assigned, and no role that edits posts. */
function cacdemo_publishing_updates_scoped( $user_id ) {
	return $user_id && ! cacdemo_publishing_role_can( $user_id, 'edit_posts' ) && cacdemo_publishing_user_ministries( $user_id );
}

/** Whether the user may edit (or publish) this ministry update. New drafts without a ministry are their own to finish. */
function cacdemo_publishing_can_update( $user_id, $post, $publishing = false ) {
	$ministry = (int) get_post_meta( $post->ID, 'update_ministry', true );
	$level    = $ministry ? cacdemo_publishing_level( $user_id, 'ministry:' . $ministry ) : '';
	$own      = (int) $post->post_author === (int) $user_id;
	if ( $level ) {
		return $own || 'publisher' === $level;
	}
	return ! $publishing && ! $ministry && $own && in_array( $post->post_status, array( 'auto-draft', 'draft' ), true );
}

/** Whether the user's roles alone (not assignments) grant a capability. Safe inside capability filters. */
function cacdemo_publishing_role_can( $user, $cap ) {
	$user = $user instanceof WP_User ? $user : get_userdata( (int) $user );
	if ( ! $user ) {
		return false;
	}
	foreach ( $user->roles as $name ) {
		$role = get_role( $name );
		if ( $role && $role->has_cap( $cap ) ) {
			return true;
		}
	}
	return false;
}

/* ---------------------------------------------------------------- Capabilities from assignments */

add_filter( 'user_has_cap', 'cacdemo_publishing_grant_caps', 10, 4 );

/**
 * Adds the primitive capabilities an assignment needs. Primitive capabilities only open the door (lists, "new" screens,
 * uploads); cacdemo_publishing_map_meta_cap() decides per post.
 */
function cacdemo_publishing_grant_caps( $allcaps, $caps, $args, $user ) {
	$scopes = cacdemo_publishing_scopes( $user->ID );
	if ( ! $scopes ) {
		return $allcaps;
	}
	$grant = array( 'upload_files' );

	if ( isset( $scopes['sermons'] ) ) {
		$grant = array_merge( $grant, array( 'edit_sermons', 'publish_sermons', 'edit_published_sermons', 'delete_sermons', 'delete_published_sermons' ) );
		if ( 'publisher' === $scopes['sermons'] ) {
			$grant = array_merge( $grant, array( 'edit_others_sermons', 'delete_others_sermons', 'edit_private_sermons', 'delete_private_sermons', 'read_private_sermons' ) );
		}
	}
	if ( cacdemo_publishing_user_ministries( $user->ID ) ) {
		// Ministry updates (core posts). Which posts is decided per post below; no edit_others_posts.
		$grant = array_merge( $grant, array( 'edit_posts', 'publish_posts', 'edit_published_posts', 'delete_posts', 'delete_published_posts' ) );
	}
	if ( in_array( 'publisher', array_diff_key( $scopes, array( 'sermons' => 1 ) ), true ) ) {
		// Ministry Publishers: their ministry record and its ways to serve (checked per post below).
		$grant = array_merge( $grant, array( 'edit_ministries', 'edit_published_ministries', 'edit_serve_roles', 'publish_serve_roles', 'edit_published_serve_roles', 'delete_serve_roles', 'delete_published_serve_roles' ) );
	}
	foreach ( $grant as $cap ) {
		$allcaps[ $cap ] = true;
	}
	return $allcaps;
}

add_filter( 'map_meta_cap', 'cacdemo_publishing_map_meta_cap', 10, 4 );

/** Per-post decisions for assignment-based access. Users whose roles already cover the type are left to core. */
function cacdemo_publishing_map_meta_cap( $caps, $cap, $user_id, $args ) {
	$post_caps = array( 'edit_post', 'delete_post', 'publish_post', 'edit_post_meta', 'delete_post_meta', 'add_post_meta' );
	$term_caps = array( 'edit_term', 'delete_term' );

	if ( in_array( $cap, $term_caps, true ) && ! empty( $args[0] ) ) {
		// Assigned users may add speakers and series but not rename or delete existing ones (that changes every sermon).
		$term = get_term( (int) $args[0] );
		if ( $term && ! is_wp_error( $term ) && str_starts_with( $term->taxonomy, 'sermon_' ) && ! cacdemo_publishing_role_can( $user_id, 'manage_categories' ) ) {
			return array( 'do_not_allow' );
		}
		return $caps;
	}
	if ( ! in_array( $cap, $post_caps, true ) || empty( $args[0] ) ) {
		return $caps;
	}
	$post = get_post( (int) $args[0] );
	if ( ! $post ) {
		return $caps;
	}

	if ( 'ministry' === $post->post_type ) {
		if ( cacdemo_publishing_role_can( $user_id, 'edit_others_ministries' ) ) {
			return $caps;
		}
		if ( in_array( $cap, array( 'delete_post', 'publish_post' ), true ) ) {
			return array( 'do_not_allow' ); // Ministries are created, published and removed by Content Admins.
		}
		return 'publisher' === cacdemo_publishing_level( $user_id, 'ministry:' . $post->ID ) ? array( 'edit_ministries' ) : array( 'do_not_allow' );
	}

	if ( 'serve_role' === $post->post_type ) {
		if ( cacdemo_publishing_role_can( $user_id, 'edit_others_serve_roles' ) ) {
			return $caps;
		}
		$ministry = (int) get_post_meta( $post->ID, 'role_ministry', true );
		$allowed  = $ministry
			? 'publisher' === cacdemo_publishing_level( $user_id, 'ministry:' . $ministry )
			: ( (int) $post->post_author === (int) $user_id && in_array( $post->post_status, array( 'auto-draft', 'draft' ), true ) );
		if ( ! $allowed ) {
			return array( 'do_not_allow' );
		}
		return array( 'delete_post' === $cap ? 'delete_serve_roles' : 'edit_serve_roles' );
	}

	if ( 'post' === $post->post_type && cacdemo_publishing_updates_scoped( $user_id ) ) {
		if ( ! cacdemo_publishing_can_update( $user_id, $post, 'publish_post' === $cap ) ) {
			return array( 'do_not_allow' );
		}
		return array( 'delete_post' === $cap ? 'delete_posts' : ( 'publish_post' === $cap ? 'publish_posts' : 'edit_posts' ) );
	}

	if ( 'wp_block' === $post->post_type && cacdemo_publishing_updates_scoped( $user_id ) ) {
		return array( 'do_not_allow' ); // Synced patterns are site-wide; not part of a ministry section.
	}

	return $caps;
}

/* ---------------------------------------------------------------- Ministry updates */

add_filter( 'rest_pre_insert_post', 'cacdemo_publishing_rest_guard_update', 10, 2 );

/**
 * The editor publishes through REST, which only checks the general "publish posts" capability. An assigned user may publish
 * (or schedule, or make private) only an update that belongs to one of their ministries.
 */
function cacdemo_publishing_rest_guard_update( $prepared, $request ) {
	$user = get_current_user_id();
	if ( ! cacdemo_publishing_updates_scoped( $user ) ) {
		return $prepared;
	}
	$status = $prepared->post_status ?? ( ! empty( $prepared->ID ) ? get_post_status( $prepared->ID ) : 'draft' );
	if ( ! in_array( $status, array( 'publish', 'future', 'private' ), true ) ) {
		return $prepared;
	}
	$post = ! empty( $prepared->ID ) ? get_post( $prepared->ID ) : null;
	if ( ! $post || ! cacdemo_publishing_can_update( $user, $post, true ) ) {
		return new WP_Error( 'cacdemo_update_ministry', __( 'Updates are published from a ministry you are responsible for. Use “Add update” on that ministry’s page.', 'cacdemo' ), array( 'status' => 403 ) );
	}
	return $prepared;
}

add_filter( 'wp_insert_post_data', 'cacdemo_publishing_guard_ministry_status', 10, 2 );

/** Ministry Publishers edit their ministry page but cannot unpublish, schedule or privately hide it (any save path). */
function cacdemo_publishing_guard_ministry_status( $data, $postarr ) {
	$user = get_current_user_id();
	if ( 'ministry' !== ( $data['post_type'] ?? '' ) || empty( $postarr['ID'] ) || ! $user || cacdemo_publishing_role_can( $user, 'publish_ministries' ) ) {
		return $data;
	}
	$data['post_status'] = get_post_status( (int) $postarr['ID'] );
	return $data;
}

add_filter( 'wp_insert_post_data', 'cacdemo_publishing_guard_update_status', 10, 2 );

/** The same rule for every other save path (quick edit, classic forms): an unauthorised publish is saved as a draft. */
function cacdemo_publishing_guard_update_status( $data, $postarr ) {
	$user = get_current_user_id();
	if ( 'post' !== ( $data['post_type'] ?? '' ) || ! in_array( $data['post_status'] ?? '', array( 'publish', 'future', 'private' ), true ) || ! cacdemo_publishing_updates_scoped( $user ) ) {
		return $data;
	}
	$post = ! empty( $postarr['ID'] ) ? get_post( (int) $postarr['ID'] ) : null;
	if ( ! $post || ! cacdemo_publishing_can_update( $user, $post, true ) ) {
		$data['post_status'] = 'draft';
	}
	return $data;
}

add_filter( 'rest_pre_insert_wp_block', 'cacdemo_publishing_block_patterns_guard' );

function cacdemo_publishing_block_patterns_guard( $prepared ) {
	return cacdemo_publishing_updates_scoped( get_current_user_id() ) ? new WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to create patterns.', 'cacdemo' ), array( 'status' => 403 ) ) : $prepared;
}

add_action( 'wp_insert_post', 'cacdemo_publishing_prefill_update', 10, 3 );

/** "Add update" on a ministry page opens a new post already linked to that ministry. */
function cacdemo_publishing_prefill_update( $post_id, $post, $update ) {
	$ministry = isset( $_GET['cacdemo_ministry'] ) ? absint( $_GET['cacdemo_ministry'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- linking is authorised in cacdemo_publishing_start_update().
	if ( ! $update && $ministry && 'post' === $post->post_type && 'auto-draft' === $post->post_status ) {
		cacdemo_publishing_start_update( $post_id, $ministry );
	}
}

/** Links a new update to a ministry the current user may post to. */
function cacdemo_publishing_start_update( $post_id, $ministry ) {
	$user = get_current_user_id();
	if ( 'ministry' !== get_post_type( $ministry ) || ! ( cacdemo_publishing_role_can( $user, 'edit_others_posts' ) || cacdemo_publishing_level( $user, 'ministry:' . $ministry ) ) ) {
		return false;
	}
	return (bool) update_field( 'update_ministry', $ministry, $post_id );
}

add_filter( 'acf/fields/post_object/query/name=update_ministry', 'cacdemo_publishing_update_ministry_choices' );

function cacdemo_publishing_update_ministry_choices( $args ) {
	if ( cacdemo_publishing_updates_scoped( get_current_user_id() ) ) {
		$args['post__in'] = cacdemo_publishing_user_ministries( get_current_user_id() ) ?: array( 0 );
	}
	return $args;
}

add_filter( 'acf/validate_value/name=update_ministry', 'cacdemo_publishing_validate_update_ministry', 10, 2 );

function cacdemo_publishing_validate_update_ministry( $valid, $value ) {
	if ( true !== $valid || ! cacdemo_publishing_updates_scoped( get_current_user_id() ) ) {
		return $valid;
	}
	return in_array( (int) $value, cacdemo_publishing_user_ministries( get_current_user_id() ), true ) ? $valid : __( 'Choose a ministry you are responsible for.', 'cacdemo' );
}

add_filter( 'acf/update_value/name=update_ministry', 'cacdemo_publishing_guard_update_ministry', 10, 2 );

/** An out-of-scope ministry is never stored, whatever the form sent. */
function cacdemo_publishing_guard_update_ministry( $value, $post_id ) {
	$user = get_current_user_id();
	if ( ! cacdemo_publishing_updates_scoped( $user ) || in_array( (int) $value, cacdemo_publishing_user_ministries( $user ), true ) ) {
		return $value;
	}
	return get_post_meta( (int) $post_id, 'update_ministry', true );
}

add_filter( 'post_type_labels_post', 'cacdemo_publishing_update_labels' );

/** Page contributors see posts as what they are to them: updates. */
function cacdemo_publishing_update_labels( $labels ) {
	if ( ! did_action( 'set_current_user' ) || ! cacdemo_publishing_updates_scoped( get_current_user_id() ) ) {
		return $labels;
	}
	foreach ( array( 'name' => 'Updates', 'singular_name' => 'Update', 'menu_name' => 'Updates', 'all_items' => 'All updates', 'add_new_item' => 'Add update', 'edit_item' => 'Edit update', 'new_item' => 'New update', 'view_item' => 'View update' ) as $key => $label ) {
		$labels->$key = $label;
	}
	return $labels;
}

/* ---------------------------------------------------------------- Ways to serve: ministry must be in scope */

add_filter( 'acf/fields/post_object/query/name=role_ministry', 'cacdemo_publishing_ministry_choices' );

/** Ministry Publishers only see (and can pick) their own ministries. */
function cacdemo_publishing_ministry_choices( $args ) {
	if ( cacdemo_publishing_role_can( get_current_user_id(), 'edit_others_serve_roles' ) ) {
		return $args;
	}
	$ids = array();
	foreach ( cacdemo_publishing_scopes( get_current_user_id() ) as $scope => $level ) {
		if ( 'publisher' === $level && str_starts_with( $scope, 'ministry:' ) ) {
			$ids[] = (int) substr( $scope, 9 );
		}
	}
	$args['post__in'] = $ids ?: array( 0 );
	return $args;
}

add_action( 'pre_get_posts', 'cacdemo_publishing_scope_admin_lists' );

/** In wp-admin, assigned users' lists of updates and ways to serve show only their ministries' entries. */
function cacdemo_publishing_scope_admin_lists( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( in_array( $query->get( 'post_type' ), array( '', 'post' ), true ) && cacdemo_publishing_updates_scoped( get_current_user_id() ) ) {
		$ids = cacdemo_publishing_user_ministries( get_current_user_id() );
		$query->set( 'meta_query', array( array( 'key' => 'update_ministry', 'value' => array_map( 'strval', $ids ), 'compare' => 'IN' ) ) );
		return;
	}
	if ( 'serve_role' !== $query->get( 'post_type' ) || cacdemo_publishing_role_can( get_current_user_id(), 'edit_others_serve_roles' ) ) {
		return;
	}
	$ids = cacdemo_publishing_ministry_choices( array() )['post__in'];
	$query->set( 'meta_query', array( array( 'key' => 'role_ministry', 'value' => array_map( 'strval', $ids ), 'compare' => 'IN' ) ) );
}

add_filter( 'acf/validate_value/name=role_ministry', 'cacdemo_publishing_validate_role_ministry', 10, 2 );

function cacdemo_publishing_validate_role_ministry( $valid, $value ) {
	if ( true !== $valid || ! is_user_logged_in() || cacdemo_publishing_role_can( get_current_user_id(), 'edit_others_serve_roles' ) ) {
		return $valid;
	}
	return 'publisher' === cacdemo_publishing_level( get_current_user_id(), 'ministry:' . (int) $value ) ? $valid : __( 'Choose a ministry you are responsible for.', 'cacdemo' );
}

add_filter( 'acf/update_value/name=role_ministry', 'cacdemo_publishing_guard_role_ministry', 10, 2 );

/** Final guard (runs even if validation was skipped): an out-of-scope ministry is never stored. */
function cacdemo_publishing_guard_role_ministry( $value, $post_id ) {
	$user = get_current_user_id();
	if ( ! $user || cacdemo_publishing_role_can( $user, 'edit_others_serve_roles' ) || 'publisher' === cacdemo_publishing_level( $user, 'ministry:' . (int) $value ) ) {
		return $value;
	}
	return get_post_meta( (int) $post_id, 'role_ministry', true );
}

add_filter( 'acf/load_value/name=role_ministry', 'cacdemo_publishing_prefill_role_ministry', 10, 2 );

/** "Add a way to serve" from a ministry page opens the editor with that ministry already chosen (if in scope). */
function cacdemo_publishing_prefill_role_ministry( $value, $post_id ) {
	$ministry = isset( $_GET['cacdemo_ministry'] ) ? absint( $_GET['cacdemo_ministry'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- only preselects a choice; saving is checked above.
	if ( $value || ! $ministry || 'ministry' !== get_post_type( $ministry ) || 'auto-draft' !== get_post_status( (int) $post_id ) ) {
		return $value;
	}
	return current_user_can( 'edit_post', $ministry ) ? $ministry : $value;
}

/* ---------------------------------------------------------------- Audit */

add_action( 'transition_post_status', 'cacdemo_publishing_record_publisher', 10, 3 );

/** Records who first published an item (core already records the creator and each revision's author). */
function cacdemo_publishing_record_publisher( $new, $old, $post ) {
	if ( 'publish' !== $new || 'publish' === $old || ! get_current_user_id() || get_post_meta( $post->ID, '_cacdemo_published_by', true ) ) {
		return;
	}
	if ( in_array( $post->post_type, array( 'sermon', 'ministry', 'serve_role', 'post', 'page' ), true ) ) {
		update_post_meta( $post->ID, '_cacdemo_published_by', get_current_user_id() );
	}
}

/* ---------------------------------------------------------------- Sermon sources: safe links, derived IDs */

add_filter( 'acf/validate_value/key=field_cacdemo_sermon_source_url', 'cacdemo_publishing_validate_source_url', 10, 4 );

/** Video and audio links must be http(s); YouTube and Facebook sources must point at those sites. */
function cacdemo_publishing_validate_source_url( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === (string) $value ) {
		return $valid;
	}
	$parts = wp_parse_url( (string) $value );
	if ( empty( $parts['host'] ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) ) {
		return __( 'Enter a web link starting with https://', 'cacdemo' );
	}
	// The platform select is a sibling in the same repeater row: …[row][field_…_url] → …[row][field_…_platform].
	$platform = '';
	if ( preg_match( '/^(.*)\[field_cacdemo_sermon_source_url\]$/', (string) $input, $m ) ) {
		$row = $_POST; // phpcs:ignore WordPress.Security.NonceVerification -- SCF verifies its nonce before validation.
		foreach ( preg_split( '/\]\[|\[|\]/', $m[1], -1, PREG_SPLIT_NO_EMPTY ) as $key ) {
			$row = is_array( $row ) && isset( $row[ $key ] ) ? $row[ $key ] : array();
		}
		$platform = is_array( $row ) ? (string) ( $row['field_cacdemo_sermon_source_platform'] ?? '' ) : '';
	}
	$hosts = array(
		'youtube'  => array( 'youtube.com', 'youtu.be' ),
		'facebook' => array( 'facebook.com', 'fb.watch' ),
	);
	if ( isset( $hosts[ $platform ] ) && ! cacdemo_publishing_host_matches( $parts['host'], $hosts[ $platform ] ) ) {
		/* translators: %s: platform name */
		return sprintf( __( 'This link is not a %s link.', 'cacdemo' ), 'youtube' === $platform ? 'YouTube' : 'Facebook' );
	}
	return $valid;
}

function cacdemo_publishing_host_matches( $host, $domains ) {
	$host = strtolower( $host );
	foreach ( $domains as $domain ) {
		if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) {
			return true;
		}
	}
	return false;
}

/** The platform's own ID for a video link (used by the importer to recognise sources it already has). */
function cacdemo_publishing_source_id( $platform, $url ) {
	$parts = wp_parse_url( (string) $url );
	if ( empty( $parts['host'] ) ) {
		return '';
	}
	parse_str( $parts['query'] ?? '', $query );
	$path = trim( $parts['path'] ?? '', '/' );
	if ( 'youtube' === $platform ) {
		if ( ! empty( $query['v'] ) ) {
			return preg_replace( '/[^\w-]/', '', $query['v'] );
		}
		if ( preg_match( '#^(?:shorts/|live/|embed/)?([\w-]{11})$#', $path, $m ) ) {
			return $m[1];
		}
	}
	if ( 'facebook' === $platform ) {
		if ( ! empty( $query['v'] ) && ctype_digit( (string) $query['v'] ) ) {
			return $query['v'];
		}
		if ( preg_match( '#(?:videos|reel)/(?:[^/]+/)?(\d{6,})#', $path, $m ) ) {
			return $m[1];
		}
	}
	return '';
}

add_action( 'acf/save_post', 'cacdemo_publishing_fill_source_ids', 20 );

function cacdemo_publishing_fill_source_ids( $post_id ) {
	if ( ! is_numeric( $post_id ) || 'sermon' !== get_post_type( (int) $post_id ) ) {
		return;
	}
	$rows    = get_field( 'sermon_sources', (int) $post_id );
	$changed = false;
	foreach ( is_array( $rows ) ? $rows : array() as $i => $row ) {
		if ( empty( $row['source_id'] ) && ! empty( $row['url'] ) ) {
			$id = cacdemo_publishing_source_id( (string) $row['platform'], (string) $row['url'] );
			if ( $id ) {
				$rows[ $i ]['source_id'] = $id;
				$changed                 = true;
			}
		}
	}
	if ( $changed ) {
		update_field( 'sermon_sources', $rows, (int) $post_id );
	}
}

/* ---------------------------------------------------------------- A smaller wp-admin for Page contributors */

add_action( 'admin_menu', 'cacdemo_publishing_trim_admin_menu', 999 );

/** People who only have assignments see their sections, Media and Profile. Access is still decided by capabilities. */
function cacdemo_publishing_trim_admin_menu() {
	$user = wp_get_current_user();
	if ( ! in_array( CACDEMO_CONTRIBUTOR_ROLE, $user->roles, true ) || count( $user->roles ) > 1 ) {
		return;
	}
	foreach ( array( 'index.php', 'edit-comments.php', 'tools.php' ) as $slug ) {
		remove_menu_page( $slug );
	}
}

add_filter( 'login_redirect', 'cacdemo_publishing_login_redirect', 10, 3 );

/** Page contributors start from the website, where the section actions are. */
function cacdemo_publishing_login_redirect( $redirect_to, $requested, $user ) {
	if ( $user instanceof WP_User && in_array( CACDEMO_CONTRIBUTOR_ROLE, $user->roles, true ) && ( ! $requested || admin_url() === $requested ) ) {
		return home_url( '/' );
	}
	return $redirect_to;
}

/* ---------------------------------------------------------------- Contextual actions */

/**
 * Actions for the current page, filtered by what the viewer may do: [ [ label, url, primary? ], … ].
 * Empty for visitors and for users without access, so nothing renders.
 */
function cacdemo_publishing_actions() {
	if ( ! is_user_logged_in() ) {
		return array();
	}
	$actions = array();
	if ( is_page( 'sermons' ) || is_tax( array( 'sermon_series', 'sermon_speaker', 'sermon_topic' ) ) ) {
		if ( current_user_can( get_post_type_object( 'sermon' )->cap->create_posts ) ) {
			$actions[] = array( __( 'New sermon', 'cacdemo' ), admin_url( 'post-new.php?post_type=sermon' ), true );
		}
	} elseif ( is_singular( 'sermon' ) ) {
		if ( current_user_can( 'edit_post', get_queried_object_id() ) ) {
			$actions[] = array( __( 'Edit sermon', 'cacdemo' ), get_edit_post_link( get_queried_object_id(), 'url' ), false );
		}
		if ( current_user_can( get_post_type_object( 'sermon' )->cap->create_posts ) ) {
			$actions[] = array( __( 'New sermon', 'cacdemo' ), admin_url( 'post-new.php?post_type=sermon' ), false );
		}
	} elseif ( is_singular( 'post' ) ) {
		if ( current_user_can( 'edit_post', get_queried_object_id() ) ) {
			$actions[] = array( __( 'Edit update', 'cacdemo' ), get_edit_post_link( get_queried_object_id(), 'url' ), false );
		}
	} elseif ( is_singular( 'ministry' ) ) {
		$ministry = get_queried_object_id();
		$user     = get_current_user_id();
		if ( cacdemo_publishing_role_can( $user, 'edit_others_posts' ) || cacdemo_publishing_level( $user, 'ministry:' . $ministry ) ) {
			$actions[] = array( __( 'Add update', 'cacdemo' ), admin_url( 'post-new.php?cacdemo_ministry=' . $ministry ), true );
		}
		if ( current_user_can( 'edit_post', $ministry ) ) {
			$actions[] = array( __( 'Edit page', 'cacdemo' ), get_edit_post_link( $ministry, 'url' ), false );
			$actions[] = array( __( 'Add a way to serve', 'cacdemo' ), admin_url( 'post-new.php?post_type=serve_role&cacdemo_ministry=' . $ministry ), false );
			if ( ! cacdemo_publishing_role_can( get_current_user_id(), 'edit_others_serve_roles' ) ) {
				$actions[] = array( __( 'Ways to serve', 'cacdemo' ), admin_url( 'edit.php?post_type=serve_role' ), false );
			}
		}
	}
	return $actions;
}
