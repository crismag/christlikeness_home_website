<?php
/**
 * Appearance acceptance (writes, then restores): Appearance → Site Theme saving and permissions, and preview for signed-in
 * theme editors over HTTP. Run by: scripts/test.sh --appearance
 *
 *   wp --path=/mnt/ai/workspaces/cacdemo eval-file tests/appearance-admin.php
 */

defined( 'WP_CLI' ) || exit;

$results = array( 'pass' => 0, 'fail' => 0 );
$check   = function ( $label, $ok ) use ( &$results ) {
	$results[ $ok ? 'pass' : 'fail' ]++;
	WP_CLI::log( ( $ok ? "  \033[32mPASS\033[0m " : "  \033[31mFAIL\033[0m " ) . $label );
};

$original = get_option( CACDEMO_SITE_THEME_OPTION, null );
$users    = array();
$posts    = array();
$make     = function ( $role ) use ( &$users ) {
	$id      = wp_insert_user( array( 'user_login' => 'cacdemo-test-appearance-' . $role . '-' . wp_generate_password( 5, false ), 'user_email' => 'cacdemo-test-appearance-' . $role . '-' . wp_generate_password( 5, false ) . '@example.invalid', 'user_pass' => wp_generate_password( 24 ), 'role' => $role ) );
	$users[] = $id;
	return $id;
};
$cookie = fn( $id ) => array( 'Cookie' => LOGGED_IN_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( $id, time() + 900, 'logged_in' ) ) );
$html   = function ( $url, $headers = array() ) {
	$body = wp_remote_retrieve_body( wp_remote_get( $url, array( 'timeout' => 20, 'headers' => $headers ) ) );
	preg_match( '/<html[^>]*>/', $body, $m );
	return array( $m[0] ?? '', $body );
};

try {
	$admin  = $make( 'administrator' );
	$editor = $make( 'editor' );

	WP_CLI::log( 'Saving' );
	wp_set_current_user( $editor );
	$check( 'people without theme options (Content Admins) cannot change the site theme', is_wp_error( cacdemo_site_theme_save_everyday( 'default', 'off' ) ) && is_wp_error( cacdemo_site_theme_add_schedule( 'default', '2030-01-01T00:00', '2030-01-02T00:00', 'off' ) ) );

	wp_set_current_user( $admin );
	delete_option( CACDEMO_SITE_THEME_OPTION );
	cacdemo_site_theme_reset_caches();
	$check( 'saving an unknown everyday theme is refused', is_wp_error( cacdemo_site_theme_save_everyday( 'no-such-theme', 'subtle' ) ) );
	$check( 'the everyday theme and effects are saved', true === cacdemo_site_theme_save_everyday( 'default', 'enhanced' ) && 'enhanced' === cacdemo_site_theme_state()['effects'] );
	$check( 'a schedule whose end is not after its start is refused', is_wp_error( cacdemo_site_theme_add_schedule( 'default', '2030-01-02T00:00', '2030-01-01T00:00', 'subtle' ) ) );
	$check( 'a schedule with an unreadable date is refused', is_wp_error( cacdemo_site_theme_add_schedule( 'default', 'soon', '2030-01-01T00:00', 'subtle' ) ) );
	$entry = cacdemo_site_theme_add_schedule( 'default', '2030-03-01T08:00', '2030-03-08T08:00', 'off' );
	$state = cacdemo_site_theme_state();
	$check( 'a valid schedule is stored with its times in site time', is_string( $entry ) && 1 === count( $state['schedule'] ) && '2030-03-01T08:00' === $state['schedule'][0]['starts'] );
	$check( 'the stored schedule resolves at the right moment', 'schedule' === cacdemo_site_theme_resolve( $state, cacdemo_site_theme_parse_time( '2030-03-05T12:00' ) )['source'] );
	$check( 'a scheduled theme can be removed', true === cacdemo_site_theme_remove_schedule( $entry ) && ! cacdemo_site_theme_state()['schedule'] && is_wp_error( cacdemo_site_theme_remove_schedule( $entry ) ) );
	update_option( CACDEMO_SITE_THEME_OPTION, array( 'everyday' => 'ghost', 'effects' => 'loud', 'schedule' => array( array( 'theme' => 'default', 'starts' => 'x', 'ends' => 'y' ) ) ) );
	cacdemo_site_theme_reset_caches();
	$state = cacdemo_site_theme_state();
	$check( 'broken stored settings are cleaned when read and the site still renders Default', 'subtle' === $state['effects'] && ! $state['schedule'] && 'default' === cacdemo_site_theme_resolve( $state, cacdemo_site_theme_now() )['theme']
		&& str_contains( $html( home_url( '/' ) )[0], 'data-site-theme="default"' ) );

	WP_CLI::log( "\nTheme images" );
	delete_option( CACDEMO_SITE_THEME_OPTION );
	cacdemo_site_theme_reset_caches();
	$image = (int) ( get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'numberposts' => 1, 'fields' => 'ids' ) )[0] ?? 0 );
	$page  = (int) ( get_posts( array( 'post_type' => 'page', 'numberposts' => 1, 'fields' => 'ids' ) )[0] ?? 0 );
	wp_set_current_user( $editor );
	$check( 'people without theme options cannot set theme images', is_wp_error( cacdemo_site_theme_save_images( 'default', array( 'home-hero' => $image ) ) ) );
	wp_set_current_user( $admin );
	$check( 'a slot the theme does not have is refused', is_wp_error( cacdemo_site_theme_save_images( 'default', array( 'made-up-slot' => $image ) ) ) );
	$check( 'something that is not an image is refused', is_wp_error( cacdemo_site_theme_save_images( 'default', array( 'home-hero' => $page ) ) ) );
	$check( 'an image is saved to its slot and answers the resolver', true === cacdemo_site_theme_save_images( 'default', array( 'home-hero' => $image ) ) && $image === (int) apply_filters( 'cacdemo_theme_image', 0, 'home-hero', null ) );
	$check( 'removing the image clears the slot', true === cacdemo_site_theme_save_images( 'default', array( 'home-hero' => 0 ) ) && empty( cacdemo_site_theme_state()['images']['default']['home-hero'] ) );

	WP_CLI::log( "\nContent Manager images" );
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( 'cacdemo-test.jpg' );
	$gd  = imagecreatetruecolor( 1200, 700 );
	imagefill( $gd, 0, 0, imagecolorallocate( $gd, 40, 70, 120 ) );
	imagejpeg( $gd, $tmp, 80 );
	$upload = media_handle_sideload( array( 'name' => 'cacdemo-test-image.jpg', 'tmp_name' => $tmp ), 0, 'cacdemo test image' );
	$posts[] = $upload;
	$check( 'a new upload joins the General collection', ! is_wp_error( $upload ) && in_array( 'general', wp_get_object_terms( $upload, 'media_collection', array( 'fields' => 'slugs' ) ), true ) );
	$request = new WP_REST_Request( 'GET', '/wp/v2/media' );
	$request->set_query_params( array( 'media_collection' => get_term_by( 'slug', 'general', 'media_collection' )->term_id, 'per_page' => 100 ) );
	$check( 'the library dialog can list a collection through the REST API', in_array( $upload, wp_list_pluck( rest_do_request( $request )->get_data(), 'id' ), true ) );
	$page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'cacdemo test banner page' ) );
	$posts[] = $page_id;
	$_POST   = array( 'cacdemo_banner_library' => (string) $upload );
	$check( 'a library image becomes the banner, stored as the SCF field', $upload === cacdemo_manage_save_image( $page_id, 'banner' ) && 'field_cacdemo_banner_image' === get_post_meta( $page_id, '_banner_image', true ) && 'banner' === ( cacdemo_resolve_image( 'hero', get_post( $page_id ) )['source'] ?? '' ) );
	$_POST = array( 'cacdemo_cover_library' => (string) $page_id );
	unset( $GLOBALS['cacdemo_manage_upload_error'] );
	$check( 'choosing something that is not an image is refused with a message', null === cacdemo_manage_save_image( $page_id, 'cover' ) && ! empty( $GLOBALS['cacdemo_manage_upload_error'] ) && ! get_post_thumbnail_id( $page_id ) );
	$_POST = array( 'cacdemo_cover_library' => (string) $upload );
	$check( 'a library image becomes the featured image', $upload === cacdemo_manage_save_image( $page_id, 'cover' ) && $upload === (int) get_post_thumbnail_id( $page_id ) );
	$_POST = array( 'cacdemo_banner_remove' => '1' );
	$check( 'removing the banner clears it', 0 === cacdemo_manage_save_image( $page_id, 'banner' ) && ! get_post_meta( $page_id, 'banner_image', true ) );
	$subscriber = $make( 'subscriber' );
	wp_set_current_user( $subscriber );
	$_POST = array( 'cacdemo_banner_library' => (string) $upload );
	$check( 'people who cannot upload cannot pick library images', null === cacdemo_manage_save_image( $page_id, 'banner' ) );
	wp_set_current_user( $admin );
	$_POST = array();
	$manage_cookie = array( 'Cookie' => LOGGED_IN_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( $admin, time() + 900, 'logged_in' ) ) );
	$form = wp_remote_retrieve_body( wp_remote_get( home_url( '/manage/pages/' . $page_id . '/' ), array( 'timeout' => 20, 'headers' => $manage_cookie ) ) );
	$check( 'the Content Manager page form offers a banner with focal points and the library dialog', str_contains( $form, 'data-cm-library="banner"' ) && str_contains( $form, 'name="cacdemo_banner_position_mobile"' ) && str_contains( $form, 'cacdemoManageMedia' ) && str_contains( $form, '<title>Edit page — ' ) );

	WP_CLI::log( "\nPreview" );
	delete_option( CACDEMO_SITE_THEME_OPTION );
	$url = add_query_arg( array( 'site_theme_preview' => 'default', 'palette_preview' => 'chocolate', 'effects_preview' => 'off' ), home_url( '/about/' ) );
	list( $tag, $body ) = $html( $url, $cookie( $admin ) );
	$check( 'an administrator previews a palette: <html> carries it and marks it forced', str_contains( $tag, 'data-palette="chocolate"' ) && str_contains( $tag, 'data-palette-forced="1"' ) );
	$check( 'a preview is not indexed and says so in the admin bar', str_contains( $body, 'noindex' ) && str_contains( $body, 'Previewing: Default · Rich chocolate' ) );
	$seasons_ok = true;
	foreach ( array( 'spring' => 'sage', 'summer' => 'blue', 'fall' => 'chocolate', 'winter' => 'navy' ) as $season => $palette ) {
		list( $tag, $body ) = $html( add_query_arg( 'site_theme_preview', $season, home_url( '/' ) ), $cookie( $admin ) );
		$seasons_ok = $seasons_ok && str_contains( $tag, 'data-site-theme="' . $season . '"' ) && str_contains( $tag, 'data-palette-default="' . $palette . '"' )
			&& str_contains( $body, ':root[data-site-theme="' . $season . '"]{--wp--custom--decor' ) && str_contains( $body, 'site-themes/' . $season . '/style.css' );
	}
	$check( 'each season previews with its palette, tokens and scoped style', $seasons_ok );
	list( $tag, $body ) = $html( add_query_arg( array( 'site_theme_preview' => 'winter', 'effects_preview' => 'enhanced' ), home_url( '/' ) ), $cookie( $admin ) );
	list( $tag_off, $body_off ) = $html( add_query_arg( array( 'site_theme_preview' => 'winter', 'effects_preview' => 'off' ), home_url( '/' ) ), $cookie( $admin ) );
	$check( 'Winter previews snow at Enhanced with the effects script; Off loads nothing', str_contains( $tag, 'data-effect="snow"' ) && str_contains( $tag, 'data-effects="enhanced"' ) && str_contains( $body, 'assets/js/effects.js' ) && str_contains( $tag_off, 'data-effects="off"' ) && ! str_contains( $tag_off, 'data-effect=' ) && ! str_contains( $body_off, 'effects.js' ) );
	list( $tag ) = $html( add_query_arg( 'palette_preview', 'not-a-palette', home_url( '/about/' ) ), $cookie( $admin ) );
	$check( 'previewing an unknown palette is ignored', ! str_contains( $tag, 'data-palette-forced' ) && str_contains( $tag, 'data-palette="default"' ) );
	list( $tag ) = $html( $url, $cookie( $editor ) );
	$check( 'a signed-in Content Admin (no theme options) cannot preview', ! str_contains( $tag, 'data-palette-forced' ) );
	$screen = wp_remote_retrieve_body( wp_remote_get( admin_url( 'themes.php?page=cacdemo-site-theme' ), array( 'timeout' => 20, 'redirection' => 0, 'headers' => array( 'Cookie' => AUTH_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( $admin, time() + 900, 'auth' ) ) . '; ' . LOGGED_IN_COOKIE . '=' . rawurlencode( wp_generate_auth_cookie( $admin, time() + 900, 'logged_in' ) ) ) ) ) );
	$check( 'Appearance → Site Theme renders for administrators', str_contains( $screen, 'Showing now' ) && str_contains( $screen, 'Everyday theme' ) && str_contains( $screen, 'Scheduled themes' ) && str_contains( $screen, 'Theme images' ) && str_contains( $screen, 'data-slot-choose' ) && substr_count( $screen, 'Passes contrast checks' ) === count( cacdemo_palettes() ) );
} catch ( Throwable $e ) {
	$check( 'test run completed without errors: ' . $e->getMessage(), false );
} finally {
	if ( null === $original ) {
		delete_option( CACDEMO_SITE_THEME_OPTION );
	} else {
		update_option( CACDEMO_SITE_THEME_OPTION, $original, false );
	}
	$_POST = array();
	foreach ( array_filter( $posts, 'is_int' ) as $id ) {
		wp_delete_post( $id, true ); // Attachments too: files and sizes are removed.
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $users as $id ) {
		wp_delete_user( $id );
	}
	$check( 'stored settings restored and temporary users removed', get_option( CACDEMO_SITE_THEME_OPTION, null ) === $original && ! get_users( array( 'search' => 'cacdemo-test-appearance-*', 'search_columns' => array( 'user_login' ) ) ) );
}

WP_CLI::log( sprintf( "\n%d passed, %d failed", $results['pass'], $results['fail'] ) );
if ( $results['fail'] ) {
	WP_CLI::halt( 1 );
}
