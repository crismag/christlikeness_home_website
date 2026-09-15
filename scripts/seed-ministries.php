<?php
/**
 * Seeds the seven ministries, their ways to serve (serve roles) and the Ministries submenu.
 *
 *   wp --path=/mnt/ai/workspaces/cacdemo eval-file scripts/seed-ministries.php [dry-run]
 *
 * Source: the "Ministries & Serve Experience" brief (§5). Its purpose statements and service areas are starting drafts,
 * published locally for design review (church decision, 2026-09-16) and not confirmed copy. Every record created here
 * carries the meta _cacdemo_seed = ministries-brief-draft so it can be found and reviewed.
 *
 * Idempotent and non-destructive: existing ministries and roles are matched by slug / title and never overwritten, so
 * edits made in WordPress survive a re-run. No role is marked "Needed now": there are no confirmed needs.
 */

defined( 'WP_CLI' ) || exit;

$dry_run = in_array( 'dry-run', $args ?? array(), true );
const CACDEMO_SEED_MARK = 'ministries-brief-draft';

$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
if ( ! $admins ) {
	WP_CLI::error( 'No administrator account found.' );
}
wp_set_current_user( $admins[0]->ID );

// name, slug, purpose (brief), ways to serve [ title => one line ], content paragraphs.
// One-line descriptions exist only where the brief wrote them (Productions); the rest stay empty until the church writes them.
$ministries = array(
	array( 'Psalmists', 'psalmists', 'Worship and musical ministry.',
		array( 'Worship Leadership' => '', 'Vocalists' => '', 'Keyboard' => '', 'Guitar' => '', 'Bass' => '', 'Drums' => '', 'Other Instruments' => '', 'Music Preparation' => '' ),
		array() ),
	array( 'Victuals', 'victuals', 'Food, hospitality and fellowship through practical service.',
		array( 'Food Preparation' => '', 'Meal Service' => '', 'Hospitality' => '', 'Kitchen Support' => '', 'Cleanup' => '', 'Special Gatherings' => '' ),
		array() ),
	array( 'Facilities', 'facilities', 'Stewarding and preparing the physical spaces used for ministry.',
		array( 'Setup' => '', 'Teardown' => '', 'Cleaning' => '', 'Maintenance' => '', 'Equipment' => '', 'Room Preparation' => '', 'Logistics' => '' ),
		array() ),
	array( 'Gift and Arrows', 'gift-and-arrows', 'Children’s ministry, discipleship and support for families.',
		array( 'Teachers' => '', 'Classroom Assistants' => '', 'Activities' => '', 'Children’s Worship' => '', 'Welcome / Registration' => '', 'Parent Support' => '', 'Event Support' => '' ),
		array() ),
	array( 'Productions', 'productions', 'Supporting worship, teaching and communication through media and technology.',
		array(
			'Audio'             => 'Help make worship, teaching and events clearly heard.',
			'Video'             => 'Help capture and communicate what is happening.',
			'Camera'            => '',
			'Lighting'          => 'Support the atmosphere and visibility of worship and productions.',
			'Livestream'        => 'Help people participate when they cannot physically attend.',
			'Presentation'      => '',
			'Graphics'          => '',
			'Photography'       => '',
			'Technical Support' => '',
		),
		array( 'You don’t need to be a professional technician to serve in Productions. Many of these roles can be learned by serving alongside the team.' ) ),
	array( 'Events', 'events', 'Planning and supporting church gatherings and special activities.',
		array( 'Event Planning' => '', 'Registration' => '', 'Hosting' => '', 'Setup' => '', 'Logistics' => '', 'Decorations' => '', 'Coordination' => '', 'Event Communications' => '' ),
		array() ),
	array( 'More Than Enough', 'more-than-enough', 'Generosity, practical assistance, benevolence and community care.',
		array( 'Benevolence' => '', 'Resource Distribution' => '', 'Outreach' => '', 'Giving Projects' => '', 'Community Assistance' => '', 'Practical Support' => '', 'Special Needs' => '' ),
		array() ),
);

$log = function ( $message ) use ( $dry_run ) {
	WP_CLI::log( ( $dry_run ? '[dry-run] ' : '' ) . $message );
};

$ministry_ids = array();
$role_slugs   = array();
foreach ( $ministries as $order => $m ) {
	list( $name, $slug, $purpose, $roles, $paragraphs ) = $m;

	$existing = get_page_by_path( $slug, OBJECT, 'ministry' );
	if ( $existing ) {
		$mid = $existing->ID;
		$log( "ministry $slug exists (#$mid)" );
	} elseif ( $dry_run ) {
		$mid = 0;
		$log( "would create ministry $slug" );
	} else {
		$content = '';
		foreach ( $paragraphs as $text ) {
			$content .= "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}
		$mid = wp_insert_post( array(
			'post_type'    => 'ministry',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_name'    => $slug,
			'post_excerpt' => $purpose,
			'post_content' => $content,
			'menu_order'   => $order,
		), true );
		if ( is_wp_error( $mid ) ) {
			WP_CLI::error( $mid );
		}
		update_post_meta( $mid, '_cacdemo_seed', CACDEMO_SEED_MARK );
		$log( "created ministry $slug (#$mid)" );
	}
	if ( $mid && '' === (string) get_field( 'ministry_tagline', $mid, false ) ) {
		update_field( 'ministry_tagline', $purpose, $mid );
	}
	$ministry_ids[ $slug ] = $mid;

	$role_order = 0;
	foreach ( $roles as $title => $line ) {
		$role_order++;
		$role_slug = sanitize_title( $title );
		if ( isset( $role_slugs[ $role_slug ] ) ) {
			$role_slug = $slug . '-' . $role_slug; // Setup, Logistics… exist in more than one ministry.
		}
		$role_slugs[ $role_slug ] = true;

		$found = $mid ? get_posts( array(
			'post_type'   => 'serve_role',
			'post_status' => 'any',
			'numberposts' => 1,
			'title'       => $title,
			'meta_query'  => array( array( 'key' => 'role_ministry', 'value' => (string) $mid ) ),
		) ) : array();
		if ( $found ) {
			continue;
		}
		if ( $dry_run ) {
			$log( "  would create role $title" );
			continue;
		}
		$rid = wp_insert_post( array(
			'post_type'    => 'serve_role',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $role_slug,
			'post_excerpt' => $line,
			'menu_order'   => $role_order,
		), true );
		if ( is_wp_error( $rid ) ) {
			WP_CLI::error( $rid );
		}
		update_field( 'role_ministry', $mid, $rid );
		update_field( 'role_status', 'ongoing', $rid );
		update_post_meta( $rid, '_cacdemo_seed', CACDEMO_SEED_MARK );
		$log( "  created role $title (#$rid)" );
	}
}

/* Main menu: "Ministries" becomes a submenu with the seven ministries and "All ministries". */
$nav  = get_posts( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'title' => 'Main', 'numberposts' => 1 ) );
$page = get_page_by_path( 'ministries', OBJECT, 'page' );
if ( ! $nav || ! $page ) {
	WP_CLI::warning( 'Main menu or Ministries page not found; submenu not added.' );
} elseif ( str_contains( $nav[0]->post_content, '<!-- wp:navigation-submenu {"label":"Ministries"' ) ) {
	$log( 'Ministries submenu already in the Main menu' );
} else {
	$blocks  = parse_blocks( $nav[0]->post_content );
	$replace = false;
	foreach ( $blocks as $i => $block ) {
		if ( 'core/navigation-link' === $block['blockName'] && (int) ( $block['attrs']['id'] ?? 0 ) === $page->ID ) {
			$replace = $i;
		}
	}
	if ( false === $replace ) {
		WP_CLI::warning( 'No Ministries link in the Main menu; submenu not added.' );
	} else {
		$link  = fn( $attrs ) => array( 'blockName' => 'core/navigation-link', 'attrs' => $attrs, 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() );
		$inner = array( $link( array( 'label' => 'All ministries', 'type' => 'page', 'id' => $page->ID, 'url' => get_permalink( $page ), 'kind' => 'post-type' ) ) );
		foreach ( $ministry_ids as $slug => $mid ) {
			if ( $mid ) {
				$inner[] = $link( array( 'label' => get_the_title( $mid ), 'type' => 'ministry', 'id' => $mid, 'url' => get_permalink( $mid ), 'kind' => 'post-type' ) );
			}
		}
		$blocks[ $replace ] = array(
			'blockName'    => 'core/navigation-submenu',
			'attrs'        => array( 'label' => 'Ministries', 'type' => 'page', 'id' => $page->ID, 'url' => get_permalink( $page ), 'kind' => 'post-type' ),
			'innerBlocks'  => $inner,
			'innerHTML'    => '',
			'innerContent' => array_fill( 0, count( $inner ), null ),
		);
		if ( $dry_run ) {
			$log( 'would add the Ministries submenu (' . count( $inner ) . ' items)' );
		} else {
			wp_update_post( array( 'ID' => $nav[0]->ID, 'post_content' => serialize_blocks( $blocks ) ) );
			$log( 'added the Ministries submenu to the Main menu (' . count( $inner ) . ' items)' );
		}
	}
}
