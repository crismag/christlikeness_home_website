<?php
/**
 * Acceptance test for contextual publishing (docs/CONTRIBUTOR-PUBLISHING.md).
 *
 *   wp --path=/mnt/ai/workspaces/cacdemo eval-file tests/publishing.php      (run by: scripts/test.sh --publishing)
 *
 * Creates temporary users, ministries, sermons, terms and an upload, exercises the permission boundaries through the same
 * paths the editor uses (REST requests in-process, capability checks, SCF field filters, the contributors service, and a
 * logged-in page request), then deletes everything it created. Prints PASS/FAIL lines; exits non-zero on any failure.
 */

defined( 'WP_CLI' ) || exit;

$results = array( 'pass' => 0, 'fail' => 0 );
$created = array( 'posts' => array(), 'users' => array(), 'terms' => array() );

$check = function ( $label, $ok ) use ( &$results ) {
	$results[ $ok ? 'pass' : 'fail' ]++;
	WP_CLI::log( ( $ok ? "  \033[32mPASS\033[0m " : "  \033[31mFAIL\033[0m " ) . $label );
};

$as = function ( $user_id ) {
	wp_set_current_user( (int) $user_id );
};

$rest = function ( $method, $route, $params = array() ) {
	$request = new WP_REST_Request( $method, $route );
	foreach ( $params as $key => $value ) {
		$request->set_param( $key, $value );
	}
	return rest_do_request( $request );
};

$user = function ( $slug, $role, $scopes = array() ) use ( &$created ) {
	$id = wp_insert_user( array( 'user_login' => 'cacdemo-test-' . $slug . '-' . wp_generate_password( 6, false ), 'user_email' => 'cacdemo-test-' . $slug . '-' . wp_generate_password( 6, false ) . '@example.invalid', 'user_pass' => wp_generate_password( 24 ), 'role' => $role ) );
	$created['users'][] = $id;
	if ( $scopes ) {
		update_user_meta( $id, CACDEMO_SCOPES_META, $scopes );
	}
	clean_user_cache( $id );
	return $id;
};

$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) )[0]->ID;

try {
	$as( $admin );
	$ministry_a = wp_insert_post( array( 'post_type' => 'ministry', 'post_status' => 'publish', 'post_title' => 'Test Ministry A (temporary)' ) );
	$ministry_b = wp_insert_post( array( 'post_type' => 'ministry', 'post_status' => 'publish', 'post_title' => 'Test Ministry B (temporary)' ) );
	$admin_sermon = wp_insert_post( array( 'post_type' => 'sermon', 'post_status' => 'publish', 'post_title' => 'Test sermon by admin (temporary)' ) );
	$role_b = wp_insert_post( array( 'post_type' => 'serve_role', 'post_status' => 'publish', 'post_title' => 'Test role B (temporary)' ) );
	update_field( 'role_ministry', $ministry_b, $role_b );
	$speaker = wp_insert_term( 'Test Speaker (temporary)', 'sermon_speaker' );
	array_push( $created['posts'], $ministry_a, $ministry_b, $admin_sermon, $role_b );
	$created['terms'][] = array( $speaker['term_id'], 'sermon_speaker' );

	$visitor         = 0;
	$subscriber      = $user( 'subscriber', 'subscriber' );
	$contributor     = $user( 'sermon-contributor', CACDEMO_CONTRIBUTOR_ROLE, array( 'sermons' => 'contributor' ) );
	$publisher       = $user( 'sermon-publisher', CACDEMO_CONTRIBUTOR_ROLE, array( 'sermons' => 'publisher' ) );
	$ministry_pub    = $user( 'ministry-publisher', CACDEMO_CONTRIBUTOR_ROLE, array( 'ministry:' . $ministry_a => 'publisher' ) );
	$ministry_con    = $user( 'ministry-contributor', CACDEMO_CONTRIBUTOR_ROLE, array( 'ministry:' . $ministry_a => 'contributor' ) );
	$content_admin   = $user( 'content-admin', 'editor' );

	WP_CLI::log( "\nAuthorization" );
	$as( $visitor );
	$check( 'visitor cannot create a sermon (REST)', 401 === $rest( 'POST', '/wp/v2/sermon', array( 'title' => 'x', 'status' => 'draft' ) )->get_status() );
	$as( $subscriber );
	$check( 'signed-in user without a section cannot create a sermon', 403 === $rest( 'POST', '/wp/v2/sermon', array( 'title' => 'x', 'status' => 'draft' ) )->get_status() );

	$as( $contributor );
	$response = $rest( 'POST', '/wp/v2/sermon', array( 'title' => 'Test sermon by contributor (temporary)', 'status' => 'publish', 'content' => '<p>Safe</p><script>alert(1)</script>' ) );
	$own      = (int) ( $response->get_data()['id'] ?? 0 );
	$created['posts'][] = $own;
	$check( 'sermon Contributor can create and publish a sermon', 201 === $response->get_status() && 'publish' === get_post_status( $own ) );
	$check( 'sermon Contributor can edit their own published sermon', 200 === $rest( 'POST', '/wp/v2/sermon/' . $own, array( 'title' => 'Test sermon by contributor, edited (temporary)' ) )->get_status() );
	$check( 'sermon Contributor cannot edit someone else’s sermon', 403 === $rest( 'POST', '/wp/v2/sermon/' . $admin_sermon, array( 'title' => 'hijack' ) )->get_status() );
	$check( 'sermon Contributor cannot edit a ministry page', ! current_user_can( 'edit_post', $ministry_a ) );
	$check( 'sermon Contributor cannot create generic posts or pages', ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) );
	$check( 'first publisher is recorded', (int) get_post_meta( $own, '_cacdemo_published_by', true ) === $contributor );

	$as( $publisher );
	$check( 'sermon Publisher can edit anyone’s sermon', 200 === $rest( 'POST', '/wp/v2/sermon/' . $admin_sermon, array( 'excerpt' => 'Edited by publisher' ) )->get_status() );
	$check( 'sermon Publisher cannot edit a ministry (publisher elsewhere is not publisher here)', 403 === $rest( 'POST', '/wp/v2/ministry/' . $ministry_a, array( 'title' => 'x' ) )->get_status() );
	$check( 'sermon Publisher cannot create ways to serve', 403 === $rest( 'POST', '/wp/v2/serve_role', array( 'title' => 'x' ) )->get_status() );

	$as( $ministry_pub );
	$check( 'ministry Publisher can edit their ministry page', 200 === $rest( 'POST', '/wp/v2/ministry/' . $ministry_a, array( 'excerpt' => 'Edited by ministry publisher' ) )->get_status() );
	$rest( 'POST', '/wp/v2/ministry/' . $ministry_a, array( 'status' => 'draft' ) );
	$check( 'ministry Publisher cannot unpublish their ministry page', 'publish' === get_post_status( $ministry_a ) );
	$check( 'ministry Publisher cannot edit another ministry', 403 === $rest( 'POST', '/wp/v2/ministry/' . $ministry_b, array( 'title' => 'x' ) )->get_status() );
	$check( 'ministry Publisher cannot delete or create ministries', ! current_user_can( 'delete_post', $ministry_a ) && 403 === $rest( 'POST', '/wp/v2/ministry', array( 'title' => 'x' ) )->get_status() );
	$check( 'ministry Publisher cannot edit another ministry’s way to serve', ! current_user_can( 'edit_post', $role_b ) );
	$check( 'ministry Publisher cannot create sermons', 403 === $rest( 'POST', '/wp/v2/sermon', array( 'title' => 'x' ) )->get_status() );
	$response = $rest( 'POST', '/wp/v2/serve_role', array( 'title' => 'Test role A (temporary)', 'status' => 'draft' ) );
	$role_a   = (int) ( $response->get_data()['id'] ?? 0 );
	$created['posts'][] = $role_a;
	$check( 'ministry Publisher can start a way to serve', 201 === $response->get_status() );

	WP_CLI::log( "\nContext and tampering" );
	$check( 'choosing an out-of-scope ministry fails validation', true !== apply_filters( 'acf/validate_value/name=role_ministry', true, $ministry_b, array( 'name' => 'role_ministry' ), '' ) );
	$check( 'choosing their ministry passes validation', true === apply_filters( 'acf/validate_value/name=role_ministry', true, $ministry_a, array( 'name' => 'role_ministry' ), '' ) );
	update_field( 'role_ministry', $ministry_a, $role_a );
	$check( 'way to serve saved under their ministry', (int) get_post_meta( $role_a, 'role_ministry', true ) === $ministry_a && current_user_can( 'edit_post', $role_a ) );
	update_field( 'role_ministry', $ministry_b, $role_a );
	$check( 'moving a way to serve to another ministry is refused on save', (int) get_post_meta( $role_a, 'role_ministry', true ) === $ministry_a );
	$check( 'ministry choices are limited to their ministries', array( $ministry_a ) === apply_filters( 'acf/fields/post_object/query/name=role_ministry', array() )['post__in'] );
	$check( 'invalid post IDs are refused safely', ! current_user_can( 'edit_post', PHP_INT_MAX ) && 404 === $rest( 'POST', '/wp/v2/sermon/999999999', array( 'title' => 'x' ) )->get_status() );

	WP_CLI::log( "\nMinistry updates" );
	$as( $admin );
	$admin_update = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Test update by admin (temporary)' ) );
	update_field( 'update_ministry', $ministry_a, $admin_update );
	$church_post = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Test church-wide post (temporary)' ) );
	array_push( $created['posts'], $admin_update, $church_post );

	$as( $ministry_con );
	$update = (int) ( $rest( 'POST', '/wp/v2/posts', array( 'title' => 'Test update by ministry contributor (temporary)', 'status' => 'draft', 'excerpt' => 'A short test update.' ) )->get_data()['id'] ?? 0 );
	$created['posts'][] = $update;
	$check( 'ministry Contributor can link a new update to their ministry', $update && cacdemo_publishing_start_update( $update, $ministry_a ) );
	$check( 'ministry Contributor can publish an update for their ministry', 200 === $rest( 'POST', '/wp/v2/posts/' . $update, array( 'status' => 'publish' ) )->get_status() && 'publish' === get_post_status( $update ) );
	$other = (int) ( $rest( 'POST', '/wp/v2/posts', array( 'title' => 'Test update for another ministry (temporary)', 'status' => 'draft' ) )->get_data()['id'] ?? 0 );
	$created['posts'][] = $other;
	$check( 'linking an update to another ministry is refused', ! cacdemo_publishing_start_update( $other, $ministry_b ) );
	$check( 'an update without their ministry cannot be published (REST)', 403 === $rest( 'POST', '/wp/v2/posts/' . $other, array( 'status' => 'publish' ) )->get_status() );
	wp_update_post( array( 'ID' => $other, 'post_status' => 'publish' ) );
	$check( 'other save paths keep it a draft', 'draft' === get_post_status( $other ) );
	update_field( 'update_ministry', $ministry_b, $update );
	$check( 'moving an update to another ministry is refused on save', (int) get_post_meta( $update, 'update_ministry', true ) === $ministry_a );
	$check( 'ministry Contributor cannot edit someone else’s update', 403 === $rest( 'POST', '/wp/v2/posts/' . $admin_update, array( 'title' => 'x' ) )->get_status() );
	$check( 'ministry Contributor cannot edit church-wide posts', ! current_user_can( 'edit_post', $church_post ) );
	$check( 'ministry Contributor cannot create synced patterns', 403 === $rest( 'POST', '/wp/v2/blocks', array( 'title' => 'x', 'content' => '<p>x</p>', 'status' => 'publish' ) )->get_status() );
	$check( 'ministry Contributor cannot edit the ministry page or its ways to serve', ! current_user_can( 'edit_post', $ministry_a ) && ! current_user_can( 'edit_post', $role_a ) );
	$as( $ministry_pub );
	$check( 'ministry Publisher can edit anyone’s update for their ministry', 200 === $rest( 'POST', '/wp/v2/posts/' . $update, array( 'excerpt' => 'Edited by the ministry publisher.' ) )->get_status() );
	$as( $contributor );
	$check( 'sermon Contributor cannot create updates', 403 === $rest( 'POST', '/wp/v2/posts', array( 'title' => 'x' ) )->get_status() );

	WP_CLI::log( "\nTerms, content and links" );
	$as( $contributor );
	$response = $rest( 'POST', '/wp/v2/sermon_speaker', array( 'name' => 'Test New Speaker (temporary)' ) );
	if ( ! empty( $response->get_data()['id'] ) ) {
		$created['terms'][] = array( (int) $response->get_data()['id'], 'sermon_speaker' );
	}
	$check( 'sermon Contributor can add a new speaker', 201 === $response->get_status() );
	$check( 'sermon Contributor cannot rename an existing speaker', 403 === $rest( 'POST', '/wp/v2/sermon_speaker/' . $speaker['term_id'], array( 'name' => 'Renamed' ) )->get_status() );
	$check( 'sermon Contributor cannot create topics (curated)', 403 === $rest( 'POST', '/wp/v2/sermon_topic', array( 'name' => 'x' ) )->get_status() );
	$content = get_post_field( 'post_content', $own );
	$check( 'script tags are removed from contributor content', ! str_contains( $content, '<script' ) && str_contains( $content, 'Safe' ) );

	$validate = fn( $url, $platform ) => cacdemo_publishing_validate_source_url( true, $url, array(), 'acf[field_cacdemo_sermon_sources][row-0][field_cacdemo_sermon_source_url]' );
	$_POST['acf'] = array( 'field_cacdemo_sermon_sources' => array( 'row-0' => array( 'field_cacdemo_sermon_source_platform' => 'youtube' ) ) );
	$check( 'a YouTube source must be a YouTube link', true !== $validate( 'https://www.facebook.com/watch/?v=123456789', 'youtube' ) && true === $validate( 'https://youtu.be/dQw4w9WgXcQ', 'youtube' ) );
	$check( 'javascript: and other non-web links are refused', true !== $validate( 'javascript:alert(1)', 'youtube' ) && true !== $validate( 'ftp://example.com/a.mp3', 'audio' ) );
	unset( $_POST['acf'] );
	$check( 'video IDs are derived from links', 'dQw4w9WgXcQ' === cacdemo_publishing_source_id( 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ) && '1601182858056451' === cacdemo_publishing_source_id( 'facebook', 'https://www.facebook.com/groups/975851515926484/videos/1601182858056451/' ) );

	$request = new WP_REST_Request( 'POST', '/wp/v2/media' );
	$request->set_header( 'Content-Disposition', 'attachment; filename="test-upload.php"' );
	$request->set_header( 'Content-Type', 'application/x-php' );
	$request->set_body( '<?php echo "no";' );
	$response = rest_do_request( $request );
	if ( ! empty( $response->get_data()['id'] ) ) {
		$created['posts'][] = (int) $response->get_data()['id'];
	}
	$check( 'uploads refuse disallowed file types', $response->is_error() );

	WP_CLI::log( "\nRemoving access" );
	$as( $admin );
	cacdemo_publishing_set_scopes( $contributor, array() );
	clean_user_cache( $contributor );
	$as( $contributor );
	$check( 'removing a section removes write access, even to their own sermon', 403 === $rest( 'POST', '/wp/v2/sermon/' . $own, array( 'title' => 'after removal' ) )->get_status() );

	WP_CLI::log( "\nContributors" );
	$as( $admin );
	$existing_email = get_userdata( $subscriber )->user_email;
	$before         = count_users()['total_users'];
	list( $result ) = cacdemo_contributors_save( array( 'person' => $existing_email, 'scopes' => array( 'sermons' => 'contributor', 'ministry:' . $ministry_b => 'publisher', 'bogus' => 'publisher', 'ministry:' . $admin_sermon => 'publisher' ) ) );
	$check( 'adding an existing person reuses their account', $result === $subscriber && count_users()['total_users'] === $before );
	$check( 'only real sections and levels are stored', array( 'sermons' => 'contributor', 'ministry:' . $ministry_b => 'publisher' ) === cacdemo_publishing_scopes( $subscriber ) );
	$check( 'an existing subscriber becomes a Page contributor', in_array( CACDEMO_CONTRIBUTOR_ROLE, get_userdata( $subscriber )->roles, true ) );

	$new_email = 'cacdemo-test-invite-' . wp_generate_password( 6, false ) . '@example.invalid';
	add_filter( 'pre_wp_mail', '__return_false' ); // No mail leaves the test.
	list( $invited ) = cacdemo_contributors_save( array( 'person' => $new_email, 'name' => 'Test Invitee', 'scopes' => array( 'sermons' => 'publisher' ) ) );
	remove_filter( 'pre_wp_mail', '__return_false' );
	if ( is_int( $invited ) ) {
		$created['users'][] = $invited;
	}
	$check( 'a new person gets one Page contributor account with the assignment', is_int( $invited ) && get_user_by( 'email', $new_email )->ID === $invited && 'publisher' === cacdemo_publishing_level( $invited, 'sermons' ) && get_user_meta( $invited, 'cacdemo_invited', true ) );
	list( $again ) = cacdemo_contributors_save( array( 'person' => $new_email, 'scopes' => array( 'sermons' => 'contributor' ) ) );
	$check( 'adding the same email again does not duplicate the account', $again === $invited );

	$as( $content_admin );
	$check( 'Content Admin can edit any sermon and ministry', current_user_can( 'edit_post', $own ) && current_user_can( 'edit_post', $ministry_b ) );
	list( $denied ) = cacdemo_contributors_save( array( 'user_id' => $admin, 'scopes' => array( 'sermons' => 'contributor' ) ) );
	$check( 'Content Admin cannot change an administrator', is_wp_error( $denied ) );
	list( $no_promote ) = cacdemo_contributors_save( array( 'user_id' => $contributor, 'content_admin' => true, 'scopes' => array() ) );
	$check( 'Content Admin cannot make other Content Admins', ! in_array( 'editor', get_userdata( $contributor )->roles, true ) );
	$as( $subscriber );
	list( $denied ) = cacdemo_contributors_save( array( 'user_id' => $contributor, 'scopes' => array( 'sermons' => 'publisher' ) ) );
	$check( 'people without Content Admin cannot manage contributors', is_wp_error( $denied ) );

	WP_CLI::log( "\nWordPress compatibility" );
	$as( $admin );
	$sermon = get_post( $own );
	$check( 'created sermon is an ordinary sermon record with a working page', $sermon && 'sermon' === $sermon->post_type && 200 === wp_remote_retrieve_response_code( wp_remote_get( get_permalink( $admin_sermon ), array( 'timeout' => 20 ) ) ) );
	$check( 'administrator and editor roles hold sermon, ministry and ways-to-serve capabilities (survive deactivation)', get_role( 'administrator' )->has_cap( 'edit_others_sermons' ) && get_role( 'editor' )->has_cap( 'edit_others_ministries' ) && get_role( 'editor' )->has_cap( 'delete_others_serve_roles' ) );

	// Contextual actions: a signed-in Publisher sees "New sermon"; a visitor does not.
	$cookie_user = $publisher;
	$expiration  = time() + 600;
	$cookie      = LOGGED_IN_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( $cookie_user, $expiration, 'logged_in' ) );
	$signed_in   = wp_remote_retrieve_body( wp_remote_get( home_url( '/sermons/' ), array( 'timeout' => 20, 'headers' => array( 'Cookie' => $cookie ) ) ) );
	$anonymous   = wp_remote_retrieve_body( wp_remote_get( home_url( '/sermons/' ), array( 'timeout' => 20 ) ) );
	$check( 'signed-in sermon Publisher sees New sermon on the Sermons page', (bool) preg_match( '#<nav[^>]*cacdemo-publish-actions.*?/manage/sermons/new/#s', $signed_in ) );
	$check( 'visitors see no publishing actions', ! preg_match( '/<nav[^>]*cacdemo-publish-actions/', $anonymous ) );
	$ministry_page = wp_remote_retrieve_body( wp_remote_get( get_permalink( $ministry_a ), array( 'timeout' => 20, 'headers' => array( 'Cookie' => $cookie ) ) ) );
	$check( 'sermon Publisher sees no actions on a ministry page', ! preg_match( '/<nav[^>]*cacdemo-publish-actions/', $ministry_page ) );
	$con_cookie = LOGGED_IN_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( $ministry_con, $expiration, 'logged_in' ) );
	$page_a     = wp_remote_retrieve_body( wp_remote_get( get_permalink( $ministry_a ), array( 'timeout' => 20, 'headers' => array( 'Cookie' => $con_cookie ) ) ) );
	$page_b     = wp_remote_retrieve_body( wp_remote_get( get_permalink( $ministry_b ), array( 'timeout' => 20, 'headers' => array( 'Cookie' => $con_cookie ) ) ) );
	$check( 'ministry Contributor sees Add update on their ministry only', (bool) preg_match( '#<nav[^>]*cacdemo-publish-actions.*?/manage/updates/new/\?ministry=' . $ministry_a . '#s', $page_a ) && ! preg_match( '/<nav[^>]*cacdemo-publish-actions/', $page_b ) );
	$check( 'the ministry page lists its published updates', str_contains( $anonymous_a = wp_remote_retrieve_body( wp_remote_get( get_permalink( $ministry_a ), array( 'timeout' => 20 ) ) ), 'Test update by ministry contributor (temporary)' ) && ! str_contains( $anonymous_a, 'Test update for another ministry' ) );
	$check( 'an update links back to its ministry', str_contains( wp_remote_retrieve_body( wp_remote_get( get_permalink( $update ), array( 'timeout' => 20 ) ) ), 'href="' . get_permalink( $ministry_a ) . '"' ) );
} catch ( Throwable $e ) {
	$check( 'test run completed without errors: ' . $e->getMessage(), false );
} finally {
	wp_set_current_user( $admin );
	// Also anything the temporary users created that the test did not expect (e.g. a refusal that failed).
	if ( array_filter( $created['users'] ) ) {
		$created['posts'] = array_merge( $created['posts'], get_posts( array( 'post_type' => 'any', 'post_status' => array( 'any', 'auto-draft', 'trash' ), 'author__in' => array_filter( $created['users'] ), 'numberposts' => -1, 'fields' => 'ids' ) ) );
	}
	foreach ( array_unique( array_filter( $created['posts'] ) ) as $id ) {
		'attachment' === get_post_type( $id ) ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
	}
	foreach ( $created['terms'] as $term ) {
		wp_delete_term( $term[0], $term[1] );
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( array_filter( $created['users'] ) as $id ) {
		wp_delete_user( $id );
	}
	$left = get_posts( array( 'post_type' => 'any', 'post_status' => 'any', 'numberposts' => -1, 's' => '(temporary)', 'fields' => 'ids' ) );
	$check( 'temporary users, posts and terms removed', ! $left && ! get_users( array( 'search' => 'cacdemo-test-*', 'search_columns' => array( 'user_login' ) ) ) );
}

WP_CLI::log( sprintf( "\n%d passed, %d failed", $results['pass'], $results['fail'] ) );
if ( $results['fail'] ) {
	WP_CLI::halt( 1 );
}
