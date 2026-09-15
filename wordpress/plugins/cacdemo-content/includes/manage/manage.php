<?php
/**
 * Content Manager: a small, separate place to create and maintain content at /manage/, without wp-admin.
 *
 * Design and decisions: docs/CONTENT-MANAGER.md (and docs/CONTRIBUTOR-PUBLISHING.md for permissions).
 *
 * - Same WordPress: same login, records, media and revisions. Screens are rendered by this plugin in their own layout;
 *   the theme styles them (assets/css/manage.css, via the cacdemo_manage_enqueue action).
 * - Forms are Secure Custom Fields front-end forms (acf_form): SCF signs each form's settings and field list, runs kses
 *   and saves through WordPress. Core parts SCF does not cover (date, speaker/series/topics, cover image, parent page,
 *   ministry link, publish/unpublish) are handled here after the SCF save.
 * - Every screen and every save checks the core capabilities from includes/publishing.php against the actual record
 *   resolved from the address, never from submitted IDs.
 * - Writing is simple formatted text. Content made of designed blocks (groups, patterns…) is not edited here; an
 *   "Open full editor" link is shown instead, so nothing is flattened.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/screens.php';

const CACDEMO_MANAGE_BASE    = 'manage';
const CACDEMO_MANAGE_VERSION = 1;

/* ---------------------------------------------------------------- Routing */

add_action( 'init', 'cacdemo_manage_rewrite' );

function cacdemo_manage_rewrite() {
	add_rewrite_rule( '^' . CACDEMO_MANAGE_BASE . '(?:/(.*?))?/?$', 'index.php?cacdemo_manage=/$matches[1]', 'top' );
	if ( (int) get_option( 'cacdemo_manage_version' ) < CACDEMO_MANAGE_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'cacdemo_manage_version', CACDEMO_MANAGE_VERSION );
	}
}

add_filter( 'query_vars', fn( $vars ) => array_merge( $vars, array( 'cacdemo_manage' ) ) );

add_action( 'wp', 'cacdemo_manage_no_admin_bar' );

/** The admin bar is set up before template_redirect, so it is turned off here. */
function cacdemo_manage_no_admin_bar() {
	if ( cacdemo_is_manage() ) {
		add_filter( 'show_admin_bar', '__return_false' );
	}
}

/** The Content Manager address for a path, e.g. cacdemo_manage_url( 'sermons/12' ). */
function cacdemo_manage_url( $path = '', $args = array() ) {
	$url = home_url( '/' . CACDEMO_MANAGE_BASE . '/' . ( $path ? trim( $path, '/' ) . '/' : '' ) );
	return $args ? add_query_arg( $args, $url ) : $url;
}

function cacdemo_is_manage() {
	return '' !== (string) get_query_var( 'cacdemo_manage' );
}

/* ---------------------------------------------------------------- Sections and access */

/**
 * Sections of the Content Manager the current user may open: [ key => [ label, description ] ].
 * Capabilities come from roles and assignments (includes/publishing.php).
 */
function cacdemo_manage_sections() {
	$sections = array();
	if ( current_user_can( 'edit_sermons' ) ) {
		$sections['sermons'] = array( __( 'Sermons', 'cacdemo' ), __( 'Add Sunday’s sermon, its video or audio links, and keep details up to date.', 'cacdemo' ) );
	}
	if ( cacdemo_manage_user_ministries() ) {
		$sections['ministries'] = array( __( 'Ministries', 'cacdemo' ), __( 'Ministry pages, ways to serve and ministry updates.', 'cacdemo' ) );
	}
	if ( current_user_can( 'edit_others_posts' ) ) {
		$sections['updates'] = array( __( 'News & updates', 'cacdemo' ), __( 'Church-wide news and all ministry updates.', 'cacdemo' ) );
	}
	if ( current_user_can( 'edit_pages' ) ) {
		$sections['pages'] = array( __( 'Pages', 'cacdemo' ), __( 'Create pages and sub-pages, and edit simple page text.', 'cacdemo' ) );
	}
	if ( current_user_can( 'edit_channels' ) ) {
		$sections['channels'] = array( __( 'Social channels', 'cacdemo' ), __( 'The Facebook pages and groups shown on Follow Us, Connect and the home page.', 'cacdemo' ) );
	}
	if ( current_user_can( CACDEMO_MANAGE_CAP ) ) {
		$sections['people'] = array( __( 'People', 'cacdemo' ), __( 'Who can publish in which section.', 'cacdemo' ) );
	}
	return $sections;
}

/** Ministries the current user works with: [ ID => 'publisher' | 'contributor' | 'admin' ]. */
function cacdemo_manage_user_ministries() {
	$user = get_current_user_id();
	$list = array();
	$all  = cacdemo_publishing_role_can( $user, 'edit_others_ministries' );
	foreach ( get_posts( array( 'post_type' => 'ministry', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $ministry ) {
		$level = $all ? 'admin' : cacdemo_publishing_level( $user, 'ministry:' . $ministry->ID );
		if ( $level ) {
			$list[ $ministry->ID ] = $level;
		}
	}
	return $list;
}

/* ---------------------------------------------------------------- Request handling */

add_action( 'template_redirect', 'cacdemo_manage_dispatch', 1 );

function cacdemo_manage_dispatch() {
	if ( ! cacdemo_is_manage() ) {
		return;
	}
	nocache_headers();
	if ( ! is_user_logged_in() ) {
		auth_redirect(); // Core login, then back here.
	}
	$segments = array_values( array_filter( explode( '/', (string) get_query_var( 'cacdemo_manage' ) ), 'strlen' ) );
	$section  = sanitize_key( $segments[0] ?? '' );
	$item     = sanitize_key( $segments[1] ?? '' );

	add_filter( 'wp_robots', 'wp_robots_no_robots' );
	add_filter( 'document_title_parts', fn( $parts ) => array( 'title' => __( 'Content Manager', 'cacdemo' ), 'site' => get_bloginfo( 'name' ) ) );

	$sections = cacdemo_manage_sections();
	if ( ! $sections ) {
		cacdemo_manage_render( __( 'No access', 'cacdemo' ), fn() => printf( '<p>%s</p><p><a href="%s">%s</a></p>', esc_html__( 'Your account is not set up to publish content yet. Ask a Content Admin to add you to the sections you help with.', 'cacdemo' ), esc_url( home_url( '/' ) ), esc_html__( 'Back to the website', 'cacdemo' ) ), 403 );
	}
	// Ways to serve and ministry updates are reached from a ministry; their screens check the record.
	$via_ministry = in_array( $section, array( 'roles', 'updates' ), true ) && isset( $sections['ministries'] );
	if ( '' !== $section && ! isset( $sections[ $section ] ) && ! $via_ministry ) {
		cacdemo_manage_render( __( 'Not available', 'cacdemo' ), fn() => printf( '<p>%s</p>', esc_html__( 'This part of the Content Manager is not available to your account.', 'cacdemo' ) ), 403 );
	}

	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['cacdemo_manage_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- each action verifies its own nonce.
		cacdemo_manage_handle_action( sanitize_key( wp_unslash( $_POST['cacdemo_manage_action'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	// SCF form submissions (validated, saved, then redirected by SCF).
	add_filter( 'acf/pre_save_post', 'cacdemo_manage_authorize_save', 1, 2 );
	add_action( 'acf/save_post', 'cacdemo_manage_after_save', 20 ); // Inside SCF's save, so the return address can still change.
	acf_form_head();

	do_action( 'cacdemo_manage_enqueue' );
	cacdemo_manage_route( $section, $item );
}

/**
 * Renders a full Content Manager page and ends the request.
 *
 * @param string   $title  Page heading.
 * @param callable $body   Prints the screen.
 * @param int      $status HTTP status.
 */
function cacdemo_manage_render( $title, $body, $status = 200 ) {
	status_header( $status );
	$sections = cacdemo_manage_sections();
	$current  = sanitize_key( explode( '/', trim( (string) get_query_var( 'cacdemo_manage' ), '/' ) )[0] ?? '' );
	$user     = wp_get_current_user();
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body class="cacdemo-manage">
<a class="screen-reader-text" href="#cm-main"><?php esc_html_e( 'Skip to content', 'cacdemo' ); ?></a>
<header class="cm-header">
	<a class="cm-brand" href="<?php echo esc_url( cacdemo_manage_url() ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> <span><?php esc_html_e( 'Content Manager', 'cacdemo' ); ?></span></a>
	<nav class="cm-nav" aria-label="<?php esc_attr_e( 'Content Manager', 'cacdemo' ); ?>">
		<?php
		foreach ( $sections as $key => $section ) {
			printf( '<a href="%s"%s>%s</a>', esc_url( cacdemo_manage_url( $key ) ), ( $key === $current || ( 'roles' === $current && 'ministries' === $key ) ) ? ' aria-current="page"' : '', esc_html( $section[0] ) );
		}
		?>
	</nav>
	<div class="cm-account">
		<span><?php echo esc_html( $user->display_name ); ?></span>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View site', 'cacdemo' ); ?></a>
		<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Sign out', 'cacdemo' ); ?></a>
	</div>
</header>
<main id="cm-main" class="cm-main">
	<h1 class="cm-title"><?php echo esc_html( $title ); ?></h1>
	<?php
	cacdemo_manage_notices();
	call_user_func( $body );
	?>
</main>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
	exit;
}

function cacdemo_manage_notices() {
	$messages = array(
		'saved'       => __( 'Saved.', 'cacdemo' ),
		'published'   => __( 'Published. It is now on the website.', 'cacdemo' ),
		'scheduled'   => __( 'Scheduled. It will appear on the website on its date.', 'cacdemo' ),
		'unpublished' => __( 'Unpublished. It is saved as a draft and hidden from the website.', 'cacdemo' ),
		'trashed'     => __( 'Moved to the trash. An administrator can restore it.', 'cacdemo' ),
		'removed'     => __( 'Sections removed. The account was kept.', 'cacdemo' ),
		'notallowed'  => __( 'Saved as a draft: you cannot publish this item.', 'cacdemo' ),
	);
	$key = sanitize_key( $_GET['done'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification -- message display only.
	if ( isset( $messages[ $key ] ) ) {
		printf( '<div class="cm-notice" role="status">%s</div>', esc_html( $messages[ $key ] ) );
	}
	if ( ! empty( $_GET['note'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- message display only.
		printf( '<div class="cm-notice" role="status">%s</div>', esc_html( wp_unslash( $_GET['note'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	}
	if ( ! empty( $_GET['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- message display only.
		printf( '<div class="cm-notice is-error" role="alert">%s</div>', esc_html( wp_unslash( $_GET['error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	}
}

function cacdemo_manage_forbidden() {
	cacdemo_manage_render( __( 'Not available', 'cacdemo' ), fn() => printf( '<p>%s</p><p><a href="%s">%s</a></p>', esc_html__( 'You cannot open this item. It may belong to a section you are not assigned to, or it may no longer exist.', 'cacdemo' ), esc_url( cacdemo_manage_url() ), esc_html__( 'Back to the Content Manager', 'cacdemo' ) ), 403 );
}

/* ---------------------------------------------------------------- Actions (unpublish, trash, people) */

function cacdemo_manage_handle_action( $action ) {
	$post_id = absint( $_POST['post_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification -- verified below per action.
	$back    = wp_get_referer() ?: cacdemo_manage_url();

	if ( 'trash' === $action ) {
		check_admin_referer( 'cacdemo_manage_trash_' . $post_id );
		if ( ! get_post( $post_id ) || ! current_user_can( 'delete_post', $post_id ) ) {
			cacdemo_manage_forbidden();
		}
		$section = cacdemo_manage_section_for( get_post( $post_id ) );
		wp_trash_post( $post_id );
		wp_safe_redirect( cacdemo_manage_url( $section, array( 'done' => 'trashed' ) ) );
		exit;
	}
	if ( 'people_save' === $action || 'people_remove' === $action ) {
		check_admin_referer( 'cacdemo_manage_' . $action );
		if ( ! current_user_can( CACDEMO_MANAGE_CAP ) ) {
			cacdemo_manage_forbidden();
		}
		if ( 'people_remove' === $action ) {
			$result = cacdemo_contributors_remove( absint( $_POST['user_id'] ?? 0 ) );
			wp_safe_redirect( cacdemo_manage_url( 'people', is_wp_error( $result ) ? array( 'error' => rawurlencode( $result->get_error_message() ) ) : array( 'done' => 'removed' ) ) );
			exit;
		}
		$input = wp_unslash( $_POST );
		if ( current_user_can( 'promote_users' ) ) {
			$input['content_admin'] = ! empty( $input['content_admin'] );
		} else {
			unset( $input['content_admin'] );
		}
		list( $result, $notice ) = cacdemo_contributors_save( $input );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( cacdemo_manage_url( 'people/' . ( empty( $input['user_id'] ) ? 'new' : absint( $input['user_id'] ) ), array( 'error' => rawurlencode( $result->get_error_message() ) ) ) );
		} else {
			wp_safe_redirect( cacdemo_manage_url( 'people', array( 'note' => rawurlencode( $notice ) ) ) );
		}
		exit;
	}
	wp_safe_redirect( $back );
	exit;
}

/** Which Content Manager section an item belongs to. */
function cacdemo_manage_section_for( $post ) {
	$map = array( 'sermon' => 'sermons', 'ministry' => 'ministries', 'serve_role' => 'roles', 'post' => 'updates', 'page' => 'pages', 'channel' => 'channels' );
	$section = $map[ $post->post_type ] ?? '';
	if ( 'serve_role' === $post->post_type ) {
		return 'ministries/' . (int) get_post_meta( $post->ID, 'role_ministry', true );
	}
	if ( 'post' === $post->post_type && ! current_user_can( 'edit_others_posts' ) ) {
		return 'ministries/' . (int) get_post_meta( $post->ID, 'update_ministry', true );
	}
	return $section;
}

/* ---------------------------------------------------------------- Forms */

/**
 * Prints an SCF form for a content type.
 *
 * @param array $config type (post type), post (WP_Post|null), section (return path), title (bool), content (bool|label),
 *                      fields (SCF field keys), context (trusted values: ministry, section), extras (callable printing inputs
 *                      after the title, before the text), after (callable printing inputs after the SCF fields).
 */
function cacdemo_manage_form( $config ) {
	$post      = $config['post'] ?? null;
	$type      = $config['type'];
	$editable  = cacdemo_manage_content_editable( $post );
	$has_body  = ! empty( $config['content'] );
	$published = $post && in_array( $post->post_status, array( 'publish', 'future' ), true );
	$can_pub   = $post ? current_user_can( 'publish_post', $post->ID ) : current_user_can( get_post_type_object( $type )->cap->publish_posts );

	$buttons = '<div class="cm-actions">';
	if ( $published ) {
		$buttons .= sprintf( '<button type="submit" class="cm-button is-primary" name="cacdemo_intent" value="keep">%s</button>', esc_html__( 'Update', 'cacdemo' ) );
		if ( $can_pub ) { // Unpublishing is a publishing decision (ministry Publishers edit their page but do not hide it).
			$buttons .= sprintf( '<button type="submit" class="cm-button" name="cacdemo_intent" value="draft">%s</button>', esc_html__( 'Unpublish', 'cacdemo' ) );
		}
	} else {
		if ( $can_pub ) {
			$buttons .= sprintf( '<button type="submit" class="cm-button is-primary" name="cacdemo_intent" value="publish">%s</button>', esc_html__( 'Publish', 'cacdemo' ) );
		}
		$buttons .= sprintf( '<button type="submit" class="cm-button%s" name="cacdemo_intent" value="draft">%s</button>', $can_pub ? '' : ' is-primary', esc_html__( 'Save draft', 'cacdemo' ) );
	}
	$buttons .= '</div>';

	ob_start();
	if ( ! empty( $config['title'] ) ) {
		printf(
			'<div class="cm-field cm-title-field"><label class="cm-label" for="cm-post-title">%1$s <span aria-hidden="true">*</span></label><input type="text" id="cm-post-title" name="cacdemo_title" value="%2$s" required maxlength="200"></div>',
			esc_html( ( $GLOBALS['cacdemo_manage_labels'][0] ?? '' ) ?: __( 'Title', 'cacdemo' ) ),
			esc_attr( $post ? $post->post_title : '' )
		);
	}
	if ( ! empty( $config['extras'] ) ) {
		call_user_func( $config['extras'], $post );
	}
	if ( $has_body && ! $editable ) {
		printf(
			'<div class="cm-field cm-locked"><p class="cm-label">%1$s</p><p>%2$s</p>%3$s</div>',
			esc_html( is_string( $config['content'] ) ? $config['content'] : __( 'Text', 'cacdemo' ) ),
			esc_html__( 'This page is built from designed sections, so its text is edited in the full editor to keep the layout intact.', 'cacdemo' ),
			current_user_can( 'edit_post', $post->ID ) ? sprintf( '<p><a class="cm-link" href="%s">%s</a></p>', esc_url( get_edit_post_link( $post->ID, 'url' ) ), esc_html__( 'Open full editor', 'cacdemo' ) ) : ''
		);
	}
	$extras = ob_get_clean();
	ob_start();
	if ( ! empty( $config['after'] ) ) {
		call_user_func( $config['after'], $post );
	}
	$after = ob_get_clean();

	acf_form( array(
		'id'                 => 'cacdemo-manage-' . $type,
		'post_id'            => $post ? $post->ID : 'new_post',
		'new_post'           => array( 'post_type' => $type, 'post_status' => 'draft' ),
		'post_title'         => false, // Our own first input (cacdemo_title), so the title comes before other details.
		'post_content'       => $has_body && $editable,
		'fields'             => $config['fields'] ?? array(),
		// With no field list SCF would add every field group for the type (e.g. an update's ministry, which is set from context).
		'field_groups'       => empty( $config['fields'] ) ? array( 'group_cacdemo_manage_none' ) : array(),
		'form_attributes'    => array( 'class' => 'cm-form', 'enctype' => 'multipart/form-data' ),
		'return'             => cacdemo_manage_url( $config['section'] . '/%post_id%', array( 'done' => 'saved' ) ),
		'html_before_fields' => $extras,
		'html_after_fields'  => $after,
		'html_submit_button' => str_replace( '%', '%%', $buttons ),
		'submit_value'       => '',
		'updated_message'    => false,
		'uploader'           => 'basic',
		'label_placement'    => 'top',
		'instruction_placement' => 'field',
		'kses'               => true,
		'cacdemo'            => $config['context'] ?? array(),
	) );
}

/** Content can be edited as simple text when it is empty, classic HTML, or only simple text blocks. */
function cacdemo_manage_content_editable( $post ) {
	if ( ! $post || '' === trim( $post->post_content ) || ! has_blocks( $post->post_content ) ) {
		return true;
	}
	$simple = array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/quote', 'core/freeform' );
	$check  = function ( $blocks ) use ( &$check, $simple ) {
		foreach ( $blocks as $block ) {
			if ( ( $block['blockName'] && ! in_array( $block['blockName'], $simple, true ) ) || ( $block['innerBlocks'] && ! $check( $block['innerBlocks'] ) ) ) {
				return false;
			}
		}
		return true;
	};
	return $check( parse_blocks( $post->post_content ) );
}

add_filter( 'acf/prepare_field/name=_post_content', 'cacdemo_manage_prepare_content' );

/** Simple formatted text: small toolbar, no media buttons, block markers hidden while editing. */
function cacdemo_manage_prepare_content( $field ) {
	if ( ! cacdemo_is_manage() ) {
		return $field;
	}
	$field['label']        = ( $GLOBALS['cacdemo_manage_labels'][1] ?? '' ) ?: __( 'Text', 'cacdemo' );
	$field['toolbar']      = 'cacdemo_simple';
	$field['media_upload'] = 0;
	$field['tabs']         = 'visual';
	$field['value']        = preg_replace( '/<!-- \/?wp:[^>]*-->\s*/', '', (string) $field['value'] );
	return $field;
}

foreach ( array( 'field_cacdemo_sermon_source_published', 'field_cacdemo_sermon_source_duration', 'field_cacdemo_sermon_source_source_id' ) as $cacdemo_key ) {
	add_filter( "acf/prepare_field/key={$cacdemo_key}", 'cacdemo_manage_hide_import_field' );
}

/**
 * Importer details (posted date, length, platform ID) stay in the form but out of sight: removing them from the form
 * would blank those values on imported sermons when a row is saved. The ID is derived from the link on save.
 */
function cacdemo_manage_hide_import_field( $field ) {
	if ( cacdemo_is_manage() ) {
		$field['wrapper']['class'] = trim( ( $field['wrapper']['class'] ?? '' ) . ' cm-hidden-field' );
	}
	return $field;
}

add_filter( 'acf/fields/wysiwyg/toolbars', 'cacdemo_manage_toolbar' );

function cacdemo_manage_toolbar( $toolbars ) {
	$toolbars['cacdemo_simple'] = array( 1 => array( 'formatselect', 'bold', 'italic', 'bullist', 'numlist', 'blockquote', 'link', 'unlink', 'undo', 'redo' ) );
	return $toolbars;
}

add_filter( 'tiny_mce_before_init', 'cacdemo_manage_block_formats' );

function cacdemo_manage_block_formats( $init ) {
	if ( cacdemo_is_manage() ) {
		$init['block_formats'] = 'Paragraph=p;Heading=h2;Subheading=h3';
	}
	return $init;
}

/** Before SCF saves anything: the user must be allowed to edit this record (or create this type). */
function cacdemo_manage_authorize_save( $post_id, $form ) {
	if ( ! str_starts_with( (string) ( $form['id'] ?? '' ), 'cacdemo-manage-' ) ) {
		return $post_id;
	}
	if ( is_numeric( $post_id ) ) {
		$allowed = get_post( (int) $post_id ) && current_user_can( 'edit_post', (int) $post_id );
	} else {
		$object  = get_post_type_object( $form['new_post']['post_type'] ?? '' );
		$allowed = $object && current_user_can( $object->cap->create_posts );
		if ( $allowed && 'post' === $object->name && ! current_user_can( 'edit_others_posts' ) ) {
			$allowed = ! empty( $form['cacdemo']['ministry'] ) && cacdemo_publishing_level( get_current_user_id(), 'ministry:' . (int) $form['cacdemo']['ministry'] );
		}
		if ( $allowed && 'serve_role' === $object->name ) {
			$allowed = ! empty( $form['cacdemo']['ministry'] ) && current_user_can( 'edit_post', (int) $form['cacdemo']['ministry'] );
		}
	}
	if ( ! $allowed ) {
		wp_die( esc_html__( 'You cannot save this item.', 'cacdemo' ), 403 );
	}
	if ( 'new_post' === $post_id ) {
		// Create the record here, with its title: WordPress refuses to insert a post whose title, text and summary are all
		// empty, and SCF would otherwise insert it before the title (our own input) is saved.
		$title   = sanitize_text_field( wp_unslash( $_POST['cacdemo_title'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification -- SCF verified the form nonce.
		$post_id = wp_insert_post( array(
			'post_type'   => $object->name,
			'post_status' => 'draft',
			'post_title'  => '' !== $title ? $title : __( 'Untitled', 'cacdemo' ),
		), true );
		if ( is_wp_error( $post_id ) ) {
			wp_die( esc_html( $post_id->get_error_message() ), 500 );
		}
	}
	return $post_id;
}

/** After SCF saved title, text and fields: links, core details, then the chosen publish state. */
function cacdemo_manage_after_save( $post_id ) {
	$form = $GLOBALS['acf_form'] ?? array();
	if ( ! str_starts_with( (string) ( $form['id'] ?? '' ), 'cacdemo-manage-' ) || ! is_numeric( $post_id ) || (int) $post_id <= 0 ) {
		return; // Never fall back to get_post( 0 ), which is "the current post" and could be someone else's record.
	}
	$post_id  = (int) $post_id;
	$post     = get_post( $post_id );
	if ( ! $post || (int) $post->ID !== $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$context  = (array) ( $form['cacdemo'] ?? array() );
	$ministry = (int) ( $context['ministry'] ?? 0 );

	// Links fixed by where the item was created (signed in the form, never from inputs).
	if ( 'post' === $post->post_type && $ministry && ! get_post_meta( $post_id, 'update_ministry', true ) ) {
		cacdemo_publishing_start_update( $post_id, $ministry );
	}
	if ( 'serve_role' === $post->post_type && $ministry && ! get_post_meta( $post_id, 'role_ministry', true ) && current_user_can( 'edit_post', $ministry ) ) {
		update_field( 'role_ministry', $ministry, $post_id );
		if ( ! get_post_meta( $post_id, 'role_status', true ) ) {
			update_field( 'role_status', 'ongoing', $post_id );
		}
	}

	do_action( 'cacdemo_manage_save_extras', $post, $context );

	$intent = sanitize_key( wp_unslash( $_POST['cacdemo_intent'] ?? 'draft' ) ); // phpcs:ignore WordPress.Security.NonceVerification -- SCF verified the form nonce.
	$done   = 'saved';
	$post   = get_post( $post_id );
	if ( 'publish' === $intent && ! in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
		if ( current_user_can( 'publish_post', $post_id ) ) {
			wp_publish_post( $post_id );
			$status = get_post_status( $post_id );
			if ( 'publish' !== $status && strtotime( $post->post_date_gmt ) > time() ) {
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'future' ) );
			}
			$status = get_post_status( $post_id );
			$done   = 'publish' === $status ? 'published' : ( 'future' === $status ? 'scheduled' : 'notallowed' );
		} else {
			$done = 'notallowed';
		}
	} elseif ( 'draft' === $intent && in_array( $post->post_status, array( 'publish', 'future' ), true ) && current_user_can( 'publish_post', $post_id ) ) {
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		$done = 'unpublished';
	}

	$args = array( 'done' => $done );
	if ( ! empty( $GLOBALS['cacdemo_manage_upload_error'] ) ) {
		/* translators: %s: upload error */
		$args['error'] = rawurlencode( sprintf( __( 'The image was not added: %s', 'cacdemo' ), $GLOBALS['cacdemo_manage_upload_error'] ) );
	}
	$GLOBALS['acf_form']['return'] = cacdemo_manage_url( ( $form['cacdemo']['section'] ?? cacdemo_manage_section_for( get_post( $post_id ) ) ) . '/' . $post_id, $args );
}

/* ---------------------------------------------------------------- Entry points from the website and wp-admin */

add_action( 'admin_bar_menu', 'cacdemo_manage_admin_bar', 80 );

function cacdemo_manage_admin_bar( $bar ) {
	if ( is_user_logged_in() && cacdemo_manage_sections() ) {
		$bar->add_node( array( 'id' => 'cacdemo-manage', 'title' => __( 'Content Manager', 'cacdemo' ), 'href' => cacdemo_manage_url() ) );
	}
}

/** Small helpers for screens. */
function cacdemo_manage_status_label( $status ) {
	$labels = array( 'publish' => __( 'Published', 'cacdemo' ), 'draft' => __( 'Draft', 'cacdemo' ), 'future' => __( 'Scheduled', 'cacdemo' ), 'pending' => __( 'Pending', 'cacdemo' ), 'private' => __( 'Private', 'cacdemo' ), 'auto-draft' => __( 'Draft', 'cacdemo' ) );
	return $labels[ $status ] ?? $status;
}

function cacdemo_manage_trash_button( $post ) {
	if ( ! $post || ! current_user_can( 'delete_post', $post->ID ) ) {
		return;
	}
	printf( '<form method="post" class="cm-trash" onsubmit="return confirm(%s)">', esc_attr( wp_json_encode( __( 'Move this to the trash? An administrator can restore it.', 'cacdemo' ) ) ) );
	wp_nonce_field( 'cacdemo_manage_trash_' . $post->ID );
	printf( '<input type="hidden" name="cacdemo_manage_action" value="trash"><input type="hidden" name="post_id" value="%d"><button type="submit" class="cm-button is-danger">%s</button></form>', (int) $post->ID, esc_html__( 'Move to trash', 'cacdemo' ) );
}
