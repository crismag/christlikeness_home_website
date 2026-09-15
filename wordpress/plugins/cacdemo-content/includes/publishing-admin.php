<?php
/**
 * Users → Page contributors: who may publish in which section.
 *
 * One small screen for Content Admins (capability cacdemo_manage_contributors): a list, and one form to add or change a
 * person's sections and level. Adding looks up an existing account by email or username first; otherwise it creates a
 * WordPress account with the Page contributor role and sends WordPress's own set-password email. There is no separate
 * invitation store: the assignment is saved on the new account and applies at first sign-in.
 *
 * Only administrators can make someone a Content Admin (core Editor role). Content Admins cannot change administrators or
 * other Content Admins.
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_CONTRIBUTORS_PAGE = 'cacdemo-contributors';

add_action( 'admin_menu', 'cacdemo_contributors_menu' );

function cacdemo_contributors_menu() {
	// Users without list_users have "Profile" as their top-level menu instead of "Users".
	$parent = current_user_can( 'list_users' ) ? 'users.php' : 'profile.php';
	add_submenu_page( $parent, __( 'Page contributors', 'cacdemo' ), __( 'Page contributors', 'cacdemo' ), CACDEMO_MANAGE_CAP, CACDEMO_CONTRIBUTORS_PAGE, 'cacdemo_contributors_screen' );
}

function cacdemo_contributors_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => CACDEMO_CONTRIBUTORS_PAGE ), $args ), admin_url( current_user_can( 'list_users' ) ? 'users.php' : 'profile.php' ) );
}

/** Whether the current user may change this person's assignments. */
function cacdemo_contributors_can_manage( $user ) {
	if ( ! current_user_can( CACDEMO_MANAGE_CAP ) || ! $user instanceof WP_User ) {
		return false;
	}
	if ( current_user_can( 'promote_users' ) ) {
		return ! in_array( 'administrator', $user->roles, true ) || get_current_user_id() === $user->ID;
	}
	// Content Admins manage contributors, not other Content Admins or administrators.
	return ! user_can( $user, CACDEMO_MANAGE_CAP );
}

function cacdemo_contributors_is_content_admin( $user ) {
	return in_array( 'editor', $user->roles, true );
}

/* ---------------------------------------------------------------- Saving */

add_action( 'admin_post_cacdemo_contributor_save', 'cacdemo_contributors_handle_save' );
add_action( 'admin_post_cacdemo_contributor_remove', 'cacdemo_contributors_handle_remove' );

/**
 * Adds or updates a contributor. Returns [ user_id|WP_Error, notice ].
 * Kept separate from the request handler so tests can call it.
 *
 * @param array $input person (email or username), name, scopes [ scope => level ], content_admin (bool), user_id (edit).
 */
function cacdemo_contributors_save( $input ) {
	if ( ! current_user_can( CACDEMO_MANAGE_CAP ) ) {
		return array( new WP_Error( 'forbidden', __( 'You cannot manage contributors.', 'cacdemo' ) ), '' );
	}
	$notice  = '';
	$user_id = absint( $input['user_id'] ?? 0 );
	$editing = (bool) $user_id; // The add form never removes a role an existing account already has.
	$user    = $user_id ? get_userdata( $user_id ) : false;

	if ( ! $user ) {
		$person = trim( (string) ( $input['person'] ?? '' ) );
		if ( '' === $person ) {
			return array( new WP_Error( 'person', __( 'Enter an email address or username.', 'cacdemo' ) ), '' );
		}
		$user = is_email( $person ) ? get_user_by( 'email', $person ) : get_user_by( 'login', $person );
		if ( ! $user ) {
			if ( ! is_email( $person ) ) {
				return array( new WP_Error( 'person', __( 'No account has that username. To invite someone new, enter their email address.', 'cacdemo' ) ), '' );
			}
			$name  = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
			$login = sanitize_user( strstr( $person, '@', true ), true ) ?: 'contributor';
			$base  = $login;
			for ( $i = 2; username_exists( $login ); $i++ ) {
				$login = $base . $i;
			}
			$user_id = wp_insert_user( array(
				'user_login'   => $login,
				'user_email'   => $person,
				'user_pass'    => wp_generate_password( 32 ),
				'display_name' => $name ?: $login,
				'first_name'   => $name,
				'role'         => CACDEMO_CONTRIBUTOR_ROLE,
			) );
			if ( is_wp_error( $user_id ) ) {
				return array( $user_id, '' );
			}
			update_user_meta( $user_id, 'cacdemo_invited', time() );
			$sent = false;
			add_action( 'wp_mail_succeeded', function () use ( &$sent ) { $sent = true; } );
			wp_new_user_notification( $user_id, null, 'user' );
			$notice = $sent
				? __( 'Account created. WordPress sent them an email to set their password.', 'cacdemo' )
				/* translators: %s: lost password URL */
				: sprintf( __( 'Account created, but the email could not be sent. Ask them to set a password at %s', 'cacdemo' ), wp_lostpassword_url() );
			$user = get_userdata( $user_id );
		}
	}

	if ( ! cacdemo_contributors_can_manage( $user ) ) {
		return array( new WP_Error( 'forbidden', __( 'You cannot change this person’s access.', 'cacdemo' ) ), '' );
	}

	// Content Admin: administrators only, never for administrators themselves.
	if ( current_user_can( 'promote_users' ) && ! in_array( 'administrator', $user->roles, true ) && isset( $input['content_admin'] ) ) {
		$make = (bool) $input['content_admin'];
		if ( $make && ! cacdemo_contributors_is_content_admin( $user ) ) {
			$user->set_role( 'editor' );
		} elseif ( ! $make && $editing && cacdemo_contributors_is_content_admin( $user ) ) {
			$user->set_role( CACDEMO_CONTRIBUTOR_ROLE );
		}
	}
	// An existing subscriber (or account without a role) becomes a Page contributor; other roles are kept.
	if ( ! array_diff( $user->roles, array( 'subscriber' ) ) ) {
		$user->set_role( CACDEMO_CONTRIBUTOR_ROLE );
	}

	$scopes = array();
	foreach ( (array) ( $input['scopes'] ?? array() ) as $scope => $level ) {
		if ( '' !== $level ) {
			$scopes[ sanitize_text_field( (string) $scope ) ] = sanitize_key( (string) $level );
		}
	}
	cacdemo_publishing_set_scopes( $user->ID, $scopes );
	clean_user_cache( $user->ID );

	return array( $user->ID, $notice ?: __( 'Saved.', 'cacdemo' ) );
}

/** Removes all of a person's sections. The account stays. */
function cacdemo_contributors_remove( $user_id ) {
	$user = get_userdata( absint( $user_id ) );
	if ( ! $user || ! cacdemo_contributors_can_manage( $user ) ) {
		return new WP_Error( 'forbidden', __( 'You cannot change this person’s access.', 'cacdemo' ) );
	}
	cacdemo_publishing_set_scopes( $user->ID, array() );
	return true;
}

function cacdemo_contributors_handle_save() {
	check_admin_referer( 'cacdemo_contributor_save' );
	$input = wp_unslash( $_POST ); // Sanitized field by field in cacdemo_contributors_save().
	$input['content_admin'] = ! empty( $input['content_admin'] );
	if ( ! current_user_can( 'promote_users' ) ) {
		unset( $input['content_admin'] );
	}
	list( $result, $notice ) = cacdemo_contributors_save( $input );
	$args = is_wp_error( $result )
		? array( 'error' => rawurlencode( $result->get_error_message() ), 'action' => empty( $input['user_id'] ) ? 'add' : 'edit', 'user' => absint( $input['user_id'] ?? 0 ) )
		: array( 'notice' => rawurlencode( $notice ) );
	wp_safe_redirect( cacdemo_contributors_url( $args ) );
	exit;
}

function cacdemo_contributors_handle_remove() {
	$user_id = absint( $_POST['user_id'] ?? 0 );
	check_admin_referer( 'cacdemo_contributor_remove_' . $user_id );
	$result = cacdemo_contributors_remove( $user_id );
	wp_safe_redirect( cacdemo_contributors_url( is_wp_error( $result ) ? array( 'error' => rawurlencode( $result->get_error_message() ) ) : array( 'notice' => rawurlencode( __( 'Sections removed. The account was kept.', 'cacdemo' ) ) ) ) );
	exit;
}

add_action( 'wp_login', 'cacdemo_contributors_clear_invited' );
add_action( 'after_password_reset', 'cacdemo_contributors_clear_invited' );

function cacdemo_contributors_clear_invited( $user ) {
	$user = $user instanceof WP_User ? $user : get_user_by( 'login', (string) $user );
	if ( $user ) {
		delete_user_meta( $user->ID, 'cacdemo_invited' );
	}
}

/* ---------------------------------------------------------------- Screen */

function cacdemo_contributors_screen() {
	if ( ! current_user_can( CACDEMO_MANAGE_CAP ) ) {
		wp_die( esc_html__( 'You cannot manage contributors.', 'cacdemo' ) );
	}
	$action = sanitize_key( $_GET['action'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification -- view state only.
	echo '<div class="wrap">';
	printf( '<h1 class="wp-heading-inline">%s</h1>', esc_html__( 'Page contributors', 'cacdemo' ) );
	if ( 'add' !== $action && 'edit' !== $action ) {
		printf( ' <a href="%s" class="page-title-action">%s</a>', esc_url( cacdemo_contributors_url( array( 'action' => 'add' ) ) ), esc_html__( 'Add contributor', 'cacdemo' ) );
	}
	echo '<hr class="wp-header-end">';

	foreach ( array( 'notice' => 'success', 'error' => 'error' ) as $key => $type ) {
		if ( ! empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- message display only.
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( wp_unslash( $_GET[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		}
	}

	if ( 'add' === $action || 'edit' === $action ) {
		cacdemo_contributors_form( 'edit' === $action ? get_userdata( absint( $_GET['user'] ?? 0 ) ) : null ); // phpcs:ignore WordPress.Security.NonceVerification
	} else {
		cacdemo_contributors_list();
	}
	echo '</div>';
}

function cacdemo_contributors_list() {
	$sections = cacdemo_publishing_sections();
	$levels   = cacdemo_publishing_levels();
	$users    = get_users( array(
		'orderby'  => 'display_name',
		'meta_key' => CACDEMO_SCOPES_META, // phpcs:ignore WordPress.DB.SlowDBQuery -- small list.
	) );
	$ids   = wp_list_pluck( $users, 'ID' );
	$users = array_merge( $users, array_filter( get_users( array( 'role' => 'editor', 'orderby' => 'display_name' ) ), fn( $u ) => ! in_array( $u->ID, $ids, true ) ) );

	printf( '<p>%s</p>', esc_html__( 'People who can add and edit content from the website. Contributors publish and edit their own items in their sections; Publishers can edit anyone’s items there and the section page. Content Admins can edit everything and manage this list.', 'cacdemo' ) );

	if ( ! $users ) {
		printf( '<p><strong>%s</strong></p>', esc_html__( 'No contributors yet.', 'cacdemo' ) );
		return;
	}
	echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
	foreach ( array( __( 'Name', 'cacdemo' ), __( 'Sections', 'cacdemo' ), __( 'Account', 'cacdemo' ), '' ) as $heading ) {
		printf( '<th scope="col">%s</th>', esc_html( $heading ) );
	}
	echo '</tr></thead><tbody>';
	foreach ( $users as $user ) {
		$lines = array();
		if ( cacdemo_contributors_is_content_admin( $user ) ) {
			$lines[] = '<strong>' . esc_html__( 'Content Admin — entire website', 'cacdemo' ) . '</strong>';
		}
		foreach ( cacdemo_publishing_scopes( $user->ID ) as $scope => $level ) {
			$lines[] = esc_html( ( $sections[ $scope ] ?? get_the_title( (int) substr( $scope, 9 ) ) ) . ' — ' . $levels[ $level ] );
		}
		$account = get_user_meta( $user->ID, 'cacdemo_invited', true ) ? __( 'Invited — has not signed in yet', 'cacdemo' ) : __( 'Active', 'cacdemo' );

		echo '<tr>';
		printf( '<td><strong>%s</strong><br><span class="description">%s</span></td>', esc_html( $user->display_name ), esc_html( $user->user_email ) );
		printf( '<td>%s</td><td>%s</td><td>', implode( '<br>', $lines ) ?: '—', esc_html( $account ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- lines escaped above.
		if ( cacdemo_contributors_can_manage( $user ) ) {
			printf( '<a class="button" href="%s">%s</a> ', esc_url( cacdemo_contributors_url( array( 'action' => 'edit', 'user' => $user->ID ) ) ), esc_html__( 'Change', 'cacdemo' ) );
			if ( cacdemo_publishing_scopes( $user->ID ) ) {
				printf( '<form method="post" action="%s" style="display:inline">', esc_url( admin_url( 'admin-post.php' ) ) );
				wp_nonce_field( 'cacdemo_contributor_remove_' . $user->ID );
				printf( '<input type="hidden" name="action" value="cacdemo_contributor_remove"><input type="hidden" name="user_id" value="%d"><button type="submit" class="button-link button-link-delete">%s</button></form>', (int) $user->ID, esc_html__( 'Remove sections', 'cacdemo' ) );
			}
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

function cacdemo_contributors_form( $user ) {
	if ( $user && ! cacdemo_contributors_can_manage( $user ) ) {
		printf( '<p>%s</p>', esc_html__( 'You cannot change this person’s access.', 'cacdemo' ) );
		return;
	}
	$scopes = $user ? cacdemo_publishing_scopes( $user->ID ) : array();
	$levels = cacdemo_publishing_levels();

	printf( '<form method="post" action="%s">', esc_url( admin_url( 'admin-post.php' ) ) );
	wp_nonce_field( 'cacdemo_contributor_save' );
	echo '<input type="hidden" name="action" value="cacdemo_contributor_save">';
	echo '<table class="form-table" role="presentation"><tbody>';

	if ( $user ) {
		printf( '<input type="hidden" name="user_id" value="%d">', (int) $user->ID );
		printf( '<tr><th scope="row">%s</th><td><strong>%s</strong><br><span class="description">%s</span></td></tr>', esc_html__( 'Person', 'cacdemo' ), esc_html( $user->display_name ), esc_html( $user->user_email ) );
	} else {
		printf(
			'<tr><th scope="row"><label for="cacdemo-person">%1$s</label></th><td><input type="text" class="regular-text" id="cacdemo-person" name="person" required autocomplete="off"><p class="description">%2$s</p></td></tr>',
			esc_html__( 'Email or username', 'cacdemo' ),
			esc_html__( 'An existing account is used when one matches. Otherwise, enter their email address and an account is created with an email to set a password.', 'cacdemo' )
		);
		printf( '<tr><th scope="row"><label for="cacdemo-name">%1$s</label></th><td><input type="text" class="regular-text" id="cacdemo-name" name="name" autocomplete="off"><p class="description">%2$s</p></td></tr>', esc_html__( 'Name', 'cacdemo' ), esc_html__( 'Only used for a new account.', 'cacdemo' ) );
	}

	if ( current_user_can( 'promote_users' ) && ! ( $user && in_array( 'administrator', $user->roles, true ) ) ) {
		printf(
			'<tr><th scope="row">%1$s</th><td><label><input type="checkbox" name="content_admin" value="1" %2$s> %3$s</label></td></tr>',
			esc_html__( 'Content Admin', 'cacdemo' ),
			checked( $user && cacdemo_contributors_is_content_admin( $user ), true, false ),
			esc_html__( 'Can edit all content on the website and manage contributors', 'cacdemo' )
		);
	}

	printf( '<tr><th scope="row">%1$s</th><td><fieldset><legend class="screen-reader-text">%1$s</legend><table class="widefat striped" style="max-width:32rem"><tbody>', esc_html__( 'Can contribute to', 'cacdemo' ) );
	$group = '';
	foreach ( cacdemo_publishing_sections() as $scope => $label ) {
		$this_group = str_starts_with( $scope, 'ministry:' ) ? __( 'Ministries', 'cacdemo' ) : '';
		if ( $this_group && $this_group !== $group ) {
			printf( '<tr><th colspan="2"><strong>%s</strong></th></tr>', esc_html( $this_group ) );
		}
		$group = $this_group;
		$id    = 'cacdemo-scope-' . sanitize_html_class( str_replace( ':', '-', $scope ) );
		printf( '<tr><td><label for="%1$s">%2$s</label></td><td><select id="%1$s" name="scopes[%3$s]"><option value="">%4$s</option>', esc_attr( $id ), esc_html( $label ), esc_attr( $scope ), esc_html__( 'No access', 'cacdemo' ) );
		foreach ( $levels as $level => $level_label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $level ), selected( $scopes[ $scope ] ?? '', $level, false ), esc_html( $level_label ) );
		}
		echo '</select></td></tr>';
	}
	printf(
		'</tbody></table><p class="description">%s</p></fieldset></td></tr>',
		esc_html__( 'Contributor: add and publish, and edit their own items. Publisher: also edit anyone’s items in that section and, for a ministry, its page and ways to serve. Contributor on a ministry: add updates for it and edit their own.', 'cacdemo' )
	);
	echo '</tbody></table>';
	submit_button( $user ? __( 'Save changes', 'cacdemo' ) : __( 'Add contributor', 'cacdemo' ) );
	printf( '<a href="%s">%s</a></form>', esc_url( cacdemo_contributors_url() ), esc_html__( 'Cancel', 'cacdemo' ) );
}
