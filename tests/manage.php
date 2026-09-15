<?php
/**
 * Acceptance test for the Content Manager (/manage/, docs/CONTENT-MANAGER.md).
 *
 *   wp --path=/mnt/ai/workspaces/cacdemo eval-file tests/manage.php      (run by: scripts/test.sh --publishing)
 *
 * Works like a person with a browser: requests screens with temporary accounts' sign-in cookies, reads the rendered forms
 * (including SCF's signed form data) and submits them over HTTP. Checks access per section, saving and publishing,
 * links set from context, tampering and trash permissions. Deletes everything it created. Exits non-zero on failure.
 */

defined( 'WP_CLI' ) || exit;

$results = array( 'pass' => 0, 'fail' => 0 );
$created = array( 'posts' => array(), 'users' => array(), 'terms' => array() );

$check = function ( $label, $ok ) use ( &$results ) {
	$results[ $ok ? 'pass' : 'fail' ]++;
	WP_CLI::log( ( $ok ? "  \033[32mPASS\033[0m " : "  \033[31mFAIL\033[0m " ) . $label );
};

$user = function ( $slug, $role, $scopes = array() ) use ( &$created ) {
	$id = wp_insert_user( array( 'user_login' => 'cacdemo-test-cm-' . $slug . '-' . wp_generate_password( 5, false ), 'user_email' => 'cacdemo-test-cm-' . $slug . '-' . wp_generate_password( 5, false ) . '@example.invalid', 'user_pass' => wp_generate_password( 24 ), 'role' => $role, 'display_name' => 'Test ' . $slug ) );
	$created['users'][] = $id;
	if ( $scopes ) {
		update_user_meta( $id, CACDEMO_SCOPES_META, $scopes );
	}
	return $id;
};

/** HTTP as a user (0 = visitor). Returns [ status, body, location ]. */
$cookies = array();
$http    = function ( $user_id, $path, $post = null ) use ( &$cookies ) {
	$headers = array();
	if ( $user_id ) {
		// One session per person: form nonces are tied to the session.
		$cookies[ $user_id ] ??= LOGGED_IN_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( $user_id, time() + 900, 'logged_in' ) );
		$headers['Cookie']    = $cookies[ $user_id ];
	}
	$url  = str_starts_with( $path, 'http' ) ? $path : home_url( $path );
	$args = array( 'timeout' => 30, 'redirection' => 0, 'headers' => $headers );
	$response = null === $post ? wp_remote_get( $url, $args ) : wp_remote_post( $url, $args + array( 'body' => $post ) );
	return array( (int) wp_remote_retrieve_response_code( $response ), (string) wp_remote_retrieve_body( $response ), (string) wp_remote_retrieve_header( $response, 'location' ) );
};

/** All successful-submission values of the Content Manager form on a page, as a browser would send them. */
$form_values = function ( $html ) {
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8"?>' . $html );
	libxml_clear_errors();
	$xpath  = new DOMXPath( $dom );
	$values = array();
	foreach ( $xpath->query( '//form[contains(@class,"cm-form")]//input | //form[contains(@class,"cm-form")]//select | //form[contains(@class,"cm-form")]//textarea' ) as $el ) {
		$name = $el->getAttribute( 'name' );
		if ( '' === $name || str_contains( $name, 'acfcloneindex' ) || in_array( $el->getAttribute( 'type' ), array( 'submit', 'file' ), true ) ) {
			continue;
		}
		if ( in_array( $el->getAttribute( 'type' ), array( 'checkbox', 'radio' ), true ) && ! $el->hasAttribute( 'checked' ) ) {
			continue;
		}
		if ( 'select' === $el->nodeName ) {
			$value = '';
			foreach ( $xpath->query( './/option', $el ) as $option ) {
				if ( $option->hasAttribute( 'selected' ) || '' === $value ) {
					$value = $option->hasAttribute( 'value' ) ? $option->getAttribute( 'value' ) : $option->textContent;
					if ( $option->hasAttribute( 'selected' ) ) {
						break;
					}
				}
			}
		} else {
			$value = 'textarea' === $el->nodeName ? $el->textContent : $el->getAttribute( 'value' );
		}
		$values[] = array( $name, $value );
	}
	return $values;
};

/** Builds a POST body from form values plus overrides (name => value; a later duplicate name wins for scalars). */
$body = function ( $values, $overrides ) {
	$pairs = array();
	foreach ( $values as $pair ) {
		if ( ! array_key_exists( $pair[0], $overrides ) ) {
			$pairs[] = rawurlencode( $pair[0] ) . '=' . rawurlencode( $pair[1] );
		}
	}
	foreach ( $overrides as $name => $value ) {
		$pairs[] = rawurlencode( $name ) . '=' . rawurlencode( $value );
	}
	return implode( '&', $pairs );
};

$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) )[0]->ID;
wp_set_current_user( $admin );

try {
	$ministry_a   = wp_insert_post( array( 'post_type' => 'ministry', 'post_status' => 'publish', 'post_title' => 'CM Test Ministry A (temporary)' ) );
	$ministry_b   = wp_insert_post( array( 'post_type' => 'ministry', 'post_status' => 'publish', 'post_title' => 'CM Test Ministry B (temporary)' ) );
	$admin_sermon = wp_insert_post( array( 'post_type' => 'sermon', 'post_status' => 'publish', 'post_title' => 'CM test sermon by admin (temporary)' ) );
	$parent_page  = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'CM test parent page (temporary)' ) );
	array_push( $created['posts'], $ministry_a, $ministry_b, $admin_sermon, $parent_page );

	$sermon_con   = $user( 'sermon', CACDEMO_CONTRIBUTOR_ROLE, array( 'sermons' => 'contributor' ) );
	$ministry_con = $user( 'ministry', CACDEMO_CONTRIBUTOR_ROLE, array( 'ministry:' . $ministry_a => 'contributor' ) );
	$editor       = $user( 'editor', 'editor' );
	$nobody       = $user( 'nobody', 'subscriber' );
	wp_set_current_user( 0 );

	WP_CLI::log( "\nAccess" );
	list( $status, , $location ) = $http( 0, '/manage/' );
	$check( 'visitors are sent to sign in', 302 === $status && str_contains( $location, 'wp-login.php' ) );
	list( $status, $html ) = $http( $nobody, '/manage/' );
	$check( 'a signed-in person without sections sees "No access"', 403 === $status && str_contains( $html, 'not set up to publish' ) );
	list( $status, $html ) = $http( $sermon_con, '/manage/' );
	$check( 'a sermon Contributor sees only Sermons', 200 === $status && str_contains( $html, '/manage/sermons/' ) && ! str_contains( $html, '/manage/pages/' ) && ! str_contains( $html, '/manage/people/' ) );
	$check( 'a sermon Contributor cannot open someone else’s sermon', 403 === $http( $sermon_con, '/manage/sermons/' . $admin_sermon . '/' )[0] );
	$check( 'a sermon Contributor cannot open pages, channels or people', 403 === $http( $sermon_con, '/manage/pages/' )[0] && 403 === $http( $sermon_con, '/manage/channels/' )[0] && 403 === $http( $sermon_con, '/manage/people/new/' )[0] );
	$check( 'a ministry Contributor can open their ministry but not another', 200 === $http( $ministry_con, '/manage/ministries/' . $ministry_a . '/' )[0] && 403 === $http( $ministry_con, '/manage/ministries/' . $ministry_b . '/' )[0] );
	$check( 'a ministry Contributor cannot add updates or ways to serve elsewhere', 403 === $http( $ministry_con, '/manage/updates/new/?ministry=' . $ministry_b )[0] && 403 === $http( $ministry_con, '/manage/roles/new/?ministry=' . $ministry_a )[0] );
	$check( 'a Content Admin can open every section', 200 === $http( $editor, '/manage/people/' )[0] && 200 === $http( $editor, '/manage/pages/new/?parent=' . $parent_page )[0] && 200 === $http( $editor, '/manage/channels/new/' )[0] );

	WP_CLI::log( "\nSaving" );
	list( , $html ) = $http( $sermon_con, '/manage/sermons/new/' );
	$values         = $form_values( $html );
	list( $status, , $location ) = $http( $sermon_con, '/manage/sermons/new/', $body( $values, array(
		'cacdemo_title'   => 'CM test sermon by contributor (temporary)',
		'cacdemo_date'    => '2026-09-13',
		'cacdemo_speaker' => 'CM Test Speaker (temporary)',
		'cacdemo_intent'  => 'publish',
		'acf[_post_content]' => '<p>Summary <script>alert(1)</script>text</p>',
		'acf[field_cacdemo_sermon_scripture]' => 'Romans 12:1-2',
	) ) );
	$new_sermon = (int) ( preg_match( '#/manage/sermons/(\d+)/#', $location, $m ) ? $m[1] : 0 );
	$created['posts'][] = $new_sermon;
	$saved = $new_sermon ? get_post( $new_sermon ) : null;
	$check( 'a sermon Contributor publishes a new sermon from the form', 302 === $status && str_contains( $location, 'done=published' ) && $saved && 'publish' === $saved->post_status );
	$check( 'title, date, speaker, scripture and summary are saved; scripts removed', $saved && 'CM test sermon by contributor (temporary)' === $saved->post_title && str_starts_with( $saved->post_date, '2026-09-13' ) && 'Romans 12:1-2' === get_field( 'sermon_scripture', $new_sermon ) && has_term( 'CM Test Speaker (temporary)', 'sermon_speaker', $new_sermon ) && str_contains( $saved->post_content, 'Summary' ) && ! str_contains( $saved->post_content, '<script' ) );
	foreach ( wp_get_object_terms( $new_sermon, 'sermon_speaker' ) as $term ) {
		$created['terms'][] = array( $term->term_id, 'sermon_speaker' );
	}
	$check( 'the Contributor is recorded as author and publisher', $saved && (int) $saved->post_author === $sermon_con && (int) get_post_meta( $new_sermon, '_cacdemo_published_by', true ) === $sermon_con );

	list( , $html ) = $http( $sermon_con, '/manage/sermons/' . $new_sermon . '/' );
	$values         = $form_values( $html );
	$http( $sermon_con, '/manage/sermons/' . $new_sermon . '/', $body( $values, array( 'cacdemo_intent' => 'draft' ) ) );
	clean_post_cache( $new_sermon ); // Saved by another process.
	$check( 'Unpublish returns the sermon to draft', 'draft' === get_post_status( $new_sermon ) );

	$tampered = array();
	foreach ( $values as $pair ) {
		$tampered[] = '_acf_form' === $pair[0] ? array( $pair[0], substr( $pair[1], 0, -8 ) . 'AAAAAAAA' ) : $pair;
	}
	$http( $sermon_con, '/manage/sermons/' . $new_sermon . '/', $body( $tampered, array( 'cacdemo_title' => 'Tampered title', 'cacdemo_intent' => 'draft' ) ) );
	clean_post_cache( $new_sermon );
	$check( 'a form with altered signed data saves nothing', 'CM test sermon by contributor (temporary)' === get_post_field( 'post_title', $new_sermon ) );

	list( , $html ) = $http( $sermon_con, '/manage/sermons/' . $new_sermon . '/' );
	preg_match( '/name="_wpnonce" value="([^"]+)"/', $html, $nonce );
	$http( $sermon_con, '/manage/sermons/' . $new_sermon . '/', array( 'cacdemo_manage_action' => 'trash', 'post_id' => $admin_sermon, '_wpnonce' => $nonce[1] ?? '' ) );
	clean_post_cache( $admin_sermon );
	$check( 'trashing someone else’s sermon is refused', 'publish' === get_post_status( $admin_sermon ) );

	list( , $html ) = $http( $ministry_con, '/manage/updates/new/?ministry=' . $ministry_a );
	list( $status, , $location ) = $http( $ministry_con, '/manage/updates/new/?ministry=' . $ministry_a, $body( $form_values( $html ), array( 'cacdemo_title' => 'CM test update (temporary)', 'cacdemo_intent' => 'publish', 'acf[_post_content]' => '<p>Update text</p>', 'cacdemo_excerpt' => 'Short summary' ) ) );
	$update = (int) ( preg_match( '#/manage/updates/(\d+)/#', $location, $m ) ? $m[1] : 0 );
	$created['posts'][] = $update;
	$check( 'a ministry Contributor publishes an update linked to their ministry', $update && 'publish' === get_post_status( $update ) && (int) get_post_meta( $update, 'update_ministry', true ) === $ministry_a && 'Short summary' === get_post_field( 'post_excerpt', $update ) );

	// Regression: a new item with only a title (no text or summary) must be created as its own record; it must never
	// change the newest existing post (WordPress refuses empty inserts, and get_post( 0 ) means "the current post").
	$newest_before = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1 ) )[0] ?? null;
	list( , $html ) = $http( $editor, '/manage/updates/new/' );
	list( $status, , $location ) = $http( $editor, '/manage/updates/new/', $body( $form_values( $html ), array( 'cacdemo_title' => 'CM test title-only news (temporary)', 'cacdemo_intent' => 'publish' ) ) );
	$news = (int) ( preg_match( '#/manage/updates/(\d+)/#', $location, $m ) ? $m[1] : 0 );
	$created['posts'][] = $news;
	if ( $newest_before ) {
		clean_post_cache( $newest_before->ID );
	}
	$check( 'title-only church news is created as its own record and changes no other post', $news > 0 && 'CM test title-only news (temporary)' === get_post_field( 'post_title', $news ) && 'publish' === get_post_status( $news ) && ! get_post_meta( $news, 'update_ministry', true ) && ( ! $newest_before || get_post_field( 'post_title', $newest_before->ID ) === $newest_before->post_title ) );

	list( , $html ) = $http( $editor, '/manage/pages/new/?parent=' . $parent_page );
	list( $status, , $location ) = $http( $editor, '/manage/pages/new/?parent=' . $parent_page, $body( $form_values( $html ), array( 'cacdemo_title' => 'CM test sub-page (temporary)', 'cacdemo_intent' => 'draft', 'acf[_post_content]' => '<p>Page text</p>' ) ) );
	$page = (int) ( preg_match( '#/manage/pages/(\d+)/#', $location, $m ) ? $m[1] : 0 );
	$created['posts'][] = $page;
	$check( 'a Content Admin creates a draft sub-page under the chosen page', $page && 'draft' === get_post_status( $page ) && (int) get_post_field( 'post_parent', $page ) === $parent_page );

	list( , $html ) = $http( $editor, '/manage/pages/' . $parent_page . '/' );
	$check( 'designed pages are not flattened: their text opens in the full editor', str_contains( $http( $editor, '/manage/pages/' . get_page_by_path( 'connect' )->ID . '/' )[1], 'Open full editor' ) && ! str_contains( $html, 'Open full editor' ) );

	list( , $html ) = $http( $editor, '/manage/people/new/' );
	preg_match( '/name="_wpnonce" value="([^"]+)"/', $html, $nonce );
	$email = 'cacdemo-test-cm-invite-' . wp_generate_password( 5, false ) . '@example.invalid';
	$http( $editor, '/manage/people/new/', array( 'cacdemo_manage_action' => 'people_save', '_wpnonce' => $nonce[1] ?? '', 'person' => $email, 'name' => 'CM Invitee', 'scopes[sermons]' => 'publisher' ) );
	$invited = get_user_by( 'email', $email );
	if ( $invited ) {
		$created['users'][] = $invited->ID;
	}
	$check( 'a Content Admin adds a new person with a section from People', $invited && 'publisher' === cacdemo_publishing_level( $invited->ID, 'sermons' ) );
	$http( $sermon_con, '/manage/people/new/', array( 'cacdemo_manage_action' => 'people_save', '_wpnonce' => $nonce[1] ?? '', 'user_id' => $sermon_con, 'scopes[sermons]' => 'publisher' ) );
	$check( 'people without Content Admin cannot change sections (even with a copied form)', 'contributor' === cacdemo_publishing_level( $sermon_con, 'sermons' ) );

	WP_CLI::log( "\nWebsite links" );
	list( , $html ) = $http( $sermon_con, '/sermons/' );
	$check( 'the Sermons page “New sermon” link opens the Content Manager', (bool) preg_match( '#cacdemo-publish-actions.*?/manage/sermons/new/#s', $html ) );
} catch ( Throwable $e ) {
	$check( 'test run completed without errors: ' . $e->getMessage(), false );
} finally {
	wp_set_current_user( $admin );
	if ( array_filter( $created['users'] ) ) {
		$created['posts'] = array_merge( $created['posts'], get_posts( array( 'post_type' => 'any', 'post_status' => array( 'any', 'auto-draft', 'trash' ), 'author__in' => array_filter( $created['users'] ), 'numberposts' => -1, 'fields' => 'ids' ) ) );
	}
	foreach ( array_unique( array_filter( $created['posts'] ) ) as $id ) {
		foreach ( get_children( array( 'post_parent' => $id, 'post_type' => 'attachment', 'fields' => 'ids' ) ) as $attachment ) {
			wp_delete_attachment( $attachment, true );
		}
		wp_delete_post( $id, true );
	}
	foreach ( $created['terms'] as $term ) {
		wp_delete_term( $term[0], $term[1] );
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( array_filter( $created['users'] ) as $id ) {
		wp_delete_user( $id );
	}
	$left = get_posts( array( 'post_type' => 'any', 'post_status' => 'any', 'numberposts' => -1, 's' => '(temporary)', 'fields' => 'ids' ) );
	$check( 'temporary users, content and terms removed', ! $left && ! get_users( array( 'search' => 'cacdemo-test-cm-*', 'search_columns' => array( 'user_login' ) ) ) );
}

WP_CLI::log( sprintf( "\n%d passed, %d failed", $results['pass'], $results['fail'] ) );
if ( $results['fail'] ) {
	WP_CLI::halt( 1 );
}
