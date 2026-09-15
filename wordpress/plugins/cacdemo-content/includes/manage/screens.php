<?php
/**
 * Content Manager screens. Each screen resolves its record from the address, checks capabilities, then prints.
 * Shared form handling and layout: manage.php.
 */

defined( 'ABSPATH' ) || exit;

function cacdemo_manage_route( $section, $item ) {
	$new = 'new' === $item;
	$id  = ctype_digit( (string) $item ) ? (int) $item : 0;

	switch ( $section ) {
		case '':
			return cacdemo_manage_home();
		case 'sermons':
			return $new || $id ? cacdemo_manage_sermon( $id ) : cacdemo_manage_sermons();
		case 'ministries':
			return $id ? cacdemo_manage_ministry( $id ) : cacdemo_manage_ministries();
		case 'roles':
			return $new || $id ? cacdemo_manage_role( $id ) : cacdemo_manage_forbidden();
		case 'updates':
			return $new || $id ? cacdemo_manage_update( $id ) : cacdemo_manage_updates();
		case 'pages':
			return $new || $id ? cacdemo_manage_page( $id ) : cacdemo_manage_pages();
		case 'channels':
			return $new || $id ? cacdemo_manage_channel( $id ) : cacdemo_manage_channels();
		case 'people':
			return $new || $id ? cacdemo_manage_person( $id ) : cacdemo_manage_people();
	}
	cacdemo_manage_forbidden();
}

/** A record of the expected type the user may edit, or the "not available" page. */
function cacdemo_manage_record( $id, $type ) {
	$post = get_post( $id );
	if ( ! $post || $type !== $post->post_type || 'trash' === $post->post_status || ! current_user_can( 'edit_post', $post->ID ) ) {
		cacdemo_manage_forbidden();
	}
	return $post;
}

/* ---------------------------------------------------------------- Shared pieces */

function cacdemo_manage_toolbar_links( $links ) {
	echo '<div class="cm-toolbar">';
	foreach ( $links as $link ) {
		printf( '<a class="cm-button%s" href="%s">%s</a>', empty( $link[2] ) ? '' : ' is-primary', esc_url( $link[1] ), esc_html( $link[0] ) );
	}
	echo '</div>';
}

function cacdemo_manage_back( $label, $url ) {
	printf( '<p class="cm-back"><a href="%s">← %s</a></p>', esc_url( $url ), esc_html( $label ) );
}

/**
 * Prints a list table.
 *
 * @param array  $columns Headings.
 * @param array  $rows    Rows of already-escaped cell HTML.
 * @param string $empty   Message when there are no rows.
 */
function cacdemo_manage_table( $columns, $rows, $empty ) {
	if ( ! $rows ) {
		printf( '<p class="cm-empty">%s</p>', esc_html( $empty ) );
		return;
	}
	echo '<div class="cm-table-wrap"><table class="cm-table"><thead><tr>';
	foreach ( $columns as $column ) {
		printf( '<th scope="col">%s</th>', esc_html( $column ) );
	}
	echo '</tr></thead><tbody>';
	foreach ( $rows as $row ) {
		echo '<tr>';
		foreach ( $row as $i => $cell ) {
			printf( '<td data-label="%s">%s</td>', esc_attr( $columns[ $i ] ?? '' ), $cell ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cells are escaped by callers.
		}
		echo '</tr>';
	}
	echo '</tbody></table></div>';
}

function cacdemo_manage_title_cell( $post, $url ) {
	$title = get_the_title( $post ) ?: __( '(no title)', 'cacdemo' );
	return current_user_can( 'edit_post', $post->ID ) ? sprintf( '<a class="cm-row-title" href="%s">%s</a>', esc_url( $url ), esc_html( $title ) ) : sprintf( '<span class="cm-row-title">%s</span>', esc_html( $title ) );
}

function cacdemo_manage_status_cell( $post ) {
	return sprintf( '<span class="cm-status is-%s">%s</span>', esc_attr( $post->post_status ), esc_html( cacdemo_manage_status_label( $post->post_status ) ) );
}

function cacdemo_manage_view_link( $post ) {
	if ( 'publish' !== $post->post_status || ! is_post_type_viewable( $post->post_type ) ) {
		return '';
	}
	return sprintf( '<p class="cm-view"><a href="%s">%s</a></p>', esc_url( get_permalink( $post ) ), esc_html__( 'View on the website', 'cacdemo' ) );
}

function cacdemo_manage_pagination( $query, $base_args ) {
	if ( $query->max_num_pages < 2 ) {
		return;
	}
	$page = max( 1, (int) ( $_GET['pg'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification -- navigation.
	echo '<nav class="cm-pages" aria-label="' . esc_attr__( 'Pages', 'cacdemo' ) . '">';
	if ( $page > 1 ) {
		printf( '<a href="%s">%s</a>', esc_url( add_query_arg( array_merge( $base_args, array( 'pg' => $page - 1 ) ) ) ), esc_html__( 'Previous', 'cacdemo' ) );
	}
	/* translators: 1: page, 2: pages */
	printf( '<span>%s</span>', esc_html( sprintf( __( 'Page %1$d of %2$d', 'cacdemo' ), $page, $query->max_num_pages ) ) );
	if ( $page < $query->max_num_pages ) {
		printf( '<a href="%s">%s</a>', esc_url( add_query_arg( array_merge( $base_args, array( 'pg' => $page + 1 ) ) ) ), esc_html__( 'Next', 'cacdemo' ) );
	}
	echo '</nav>';
}

/** Cover image input: current image, replace, remove, and alt text. */
function cacdemo_manage_cover_input( $post, $label ) {
	$thumb = $post ? get_post_thumbnail_id( $post ) : 0;
	echo '<div class="cm-field cm-cover">';
	printf( '<p class="cm-label">%s</p>', esc_html( $label ) );
	if ( $thumb ) {
		echo wp_get_attachment_image( $thumb, 'medium', false, array( 'class' => 'cm-cover__image' ) );
		printf( '<label class="cm-check"><input type="checkbox" name="cacdemo_cover_remove" value="1"> %s</label>', esc_html__( 'Remove this image', 'cacdemo' ) );
	}
	if ( current_user_can( 'upload_files' ) ) {
		printf(
			'<label class="cm-sub" for="cacdemo-cover">%1$s</label><input type="file" id="cacdemo-cover" name="cacdemo_cover" accept="image/jpeg,image/png,image/webp"><label class="cm-sub" for="cacdemo-cover-alt">%2$s</label><input type="text" id="cacdemo-cover-alt" name="cacdemo_cover_alt" value="%3$s"><p class="cm-help">%4$s</p>',
			esc_html( $thumb ? __( 'Replace with a new image', 'cacdemo' ) : __( 'Choose an image (JPG, PNG or WebP)', 'cacdemo' ) ),
			esc_html__( 'Describe the image for people who cannot see it', 'cacdemo' ),
			esc_attr( $thumb ? (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) : '' ),
			esc_html__( 'Leave the description empty if the image is only decoration.', 'cacdemo' )
		);
	}
	echo '</div>';
}

function cacdemo_manage_text_input( $name, $label, $value, $help = '', $type = 'text', $attrs = '' ) {
	printf(
		'<div class="cm-field"><label class="cm-label" for="%1$s">%2$s</label><input type="%3$s" id="%1$s" name="%4$s" value="%5$s" %6$s>%7$s</div>',
		esc_attr( 'cm-' . $name ),
		esc_html( $label ),
		esc_attr( $type ),
		esc_attr( $name ),
		esc_attr( $value ),
		$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed attribute strings from callers.
		$help ? '<p class="cm-help">' . esc_html( $help ) . '</p>' : ''
	);
}

function cacdemo_manage_textarea( $name, $label, $value, $help = '' ) {
	printf(
		'<div class="cm-field"><label class="cm-label" for="%1$s">%2$s</label><textarea id="%1$s" name="%3$s" rows="3">%4$s</textarea>%5$s</div>',
		esc_attr( 'cm-' . $name ),
		esc_html( $label ),
		esc_attr( $name ),
		esc_textarea( $value ),
		$help ? '<p class="cm-help">' . esc_html( $help ) . '</p>' : ''
	);
}

/* ---------------------------------------------------------------- Saving the extras */

add_action( 'cacdemo_manage_save_extras', 'cacdemo_manage_save_extras', 10, 2 );

/** Saves the core details entered outside SCF fields, each checked against the user's capabilities. */
function cacdemo_manage_save_extras( $post, $context ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- SCF verified the form nonce before saving.
	if ( ! $post instanceof WP_Post || ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}
	$id   = $post->ID;
	$data = array( 'ID' => $id );

	if ( isset( $_POST['cacdemo_title'] ) ) {
		$title              = sanitize_text_field( wp_unslash( $_POST['cacdemo_title'] ) );
		$data['post_title'] = '' !== $title ? $title : __( 'Untitled', 'cacdemo' );
		if ( 'auto-draft' === $post->post_status || '' === $post->post_name ) {
			$data['post_name'] = sanitize_title( $data['post_title'] );
		}
	}

	if ( isset( $_POST['cacdemo_date'] ) && in_array( $post->post_type, array( 'sermon', 'post' ), true ) ) {
		$date = sanitize_text_field( wp_unslash( $_POST['cacdemo_date'] ) );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && wp_checkdate( (int) substr( $date, 5, 2 ), (int) substr( $date, 8, 2 ), (int) substr( $date, 0, 4 ), $date ) ) {
			$time                  = substr( $post->post_date, 11 ) ?: '10:00:00';
			$data['post_date']     = $date . ' ' . ( '00:00:00' === $time ? '10:00:00' : $time );
			$data['post_date_gmt'] = get_gmt_from_date( $data['post_date'] );
			$data['edit_date']     = true;
		}
	}
	if ( isset( $_POST['cacdemo_excerpt'] ) && in_array( $post->post_type, array( 'post', 'serve_role' ), true ) ) {
		$data['post_excerpt'] = sanitize_textarea_field( wp_unslash( $_POST['cacdemo_excerpt'] ) );
	}
	if ( 'page' === $post->post_type && isset( $_POST['cacdemo_parent'] ) ) {
		$parent = absint( $_POST['cacdemo_parent'] );
		if ( ! $parent || ( 'page' === get_post_type( $parent ) && $parent !== $id && ! in_array( $id, get_post_ancestors( $parent ), true ) && current_user_can( 'edit_post', $parent ) ) ) {
			$data['post_parent'] = $parent;
		}
		$data['menu_order'] = absint( $_POST['cacdemo_order'] ?? 0 );
	}
	if ( count( $data ) > 1 ) {
		wp_update_post( wp_slash( $data ) );
	}

	if ( 'sermon' === $post->post_type ) {
		foreach ( array( 'sermon_speaker' => 'cacdemo_speaker', 'sermon_series' => 'cacdemo_series' ) as $taxonomy => $input ) {
			if ( ! isset( $_POST[ $input ] ) || ! current_user_can( get_taxonomy( $taxonomy )->cap->assign_terms ) ) {
				continue;
			}
			$name = sanitize_text_field( wp_unslash( $_POST[ $input ] ) );
			if ( '' === $name ) {
				wp_set_object_terms( $id, array(), $taxonomy );
				continue;
			}
			$term = get_term_by( 'name', $name, $taxonomy );
			if ( ! $term && current_user_can( get_taxonomy( $taxonomy )->cap->edit_terms ) ) {
				$created = wp_insert_term( $name, $taxonomy );
				$term    = is_wp_error( $created ) ? null : get_term( $created['term_id'], $taxonomy );
			}
			if ( $term ) {
				wp_set_object_terms( $id, array( (int) $term->term_id ), $taxonomy );
			}
		}
		if ( isset( $_POST['cacdemo_topics_sent'] ) && current_user_can( get_taxonomy( 'sermon_topic' )->cap->assign_terms ) ) {
			$valid  = wp_list_pluck( get_terms( array( 'taxonomy' => 'sermon_topic', 'hide_empty' => false ) ), 'term_id' );
			$chosen = array_intersect( array_map( 'absint', (array) wp_unslash( $_POST['cacdemo_topics'] ?? array() ) ), $valid );
			wp_set_object_terms( $id, array_values( $chosen ), 'sermon_topic' );
		}
	}

	if ( post_type_supports( $post->post_type, 'thumbnail' ) ) {
		if ( ! empty( $_POST['cacdemo_cover_remove'] ) ) {
			delete_post_thumbnail( $id );
		}
		if ( ! empty( $_FILES['cacdemo_cover']['name'] ) && current_user_can( 'upload_files' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$images   = array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );
			$uploaded = media_handle_upload( 'cacdemo_cover', $id, array(), array( 'test_form' => false, 'mimes' => $images ) );
			if ( ! is_wp_error( $uploaded ) ) {
				set_post_thumbnail( $id, $uploaded );
			} else {
				$GLOBALS['cacdemo_manage_upload_error'] = $uploaded->get_error_message();
			}
		}
		$thumb = get_post_thumbnail_id( $id );
		if ( $thumb && isset( $_POST['cacdemo_cover_alt'] ) && current_user_can( 'edit_post', $thumb ) ) {
			update_post_meta( $thumb, '_wp_attachment_image_alt', sanitize_text_field( wp_unslash( $_POST['cacdemo_cover_alt'] ) ) );
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/* ---------------------------------------------------------------- Home */

function cacdemo_manage_home() {
	cacdemo_manage_render( __( 'Content Manager', 'cacdemo' ), function () {
		$sections = cacdemo_manage_sections();
		$new      = array(
			'sermons'  => array( __( 'New sermon', 'cacdemo' ), cacdemo_manage_url( 'sermons/new' ) ),
			'updates'  => array( __( 'New update', 'cacdemo' ), cacdemo_manage_url( 'updates/new' ) ),
			'pages'    => array( __( 'New page', 'cacdemo' ), cacdemo_manage_url( 'pages/new' ) ),
			'channels' => array( __( 'Add a channel', 'cacdemo' ), cacdemo_manage_url( 'channels/new' ) ),
			'people'   => array( __( 'Add a person', 'cacdemo' ), cacdemo_manage_url( 'people/new' ) ),
		);
		/* translators: %s: first name or display name */
		printf( '<p class="cm-lead">%s</p>', esc_html( sprintf( __( 'Hello %s. Choose what you would like to work on.', 'cacdemo' ), wp_get_current_user()->first_name ?: wp_get_current_user()->display_name ) ) );
		echo '<ul class="cm-cards">';
		foreach ( $sections as $key => $section ) {
			printf( '<li class="cm-card"><h2><a href="%s">%s</a></h2><p>%s</p>', esc_url( cacdemo_manage_url( $key ) ), esc_html( $section[0] ), esc_html( $section[1] ) );
			if ( isset( $new[ $key ] ) ) {
				printf( '<a class="cm-button" href="%s">+ %s</a>', esc_url( $new[ $key ][1] ), esc_html( $new[ $key ][0] ) );
			}
			echo '</li>';
		}
		echo '</ul>';
	} );
}

/* ---------------------------------------------------------------- Sermons */

function cacdemo_manage_sermons() {
	cacdemo_manage_render( __( 'Sermons', 'cacdemo' ), function () {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- search and filters.
		$search = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
		$status = sanitize_key( $_GET['status'] ?? '' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$args = array(
			'post_type'      => 'sermon',
			'post_status'    => in_array( $status, array( 'publish', 'draft', 'future' ), true ) ? $status : array( 'publish', 'draft', 'future', 'pending', 'private' ),
			'posts_per_page' => 25,
			'paged'          => max( 1, (int) ( $_GET['pg'] ?? 1 ) ), // phpcs:ignore WordPress.Security.NonceVerification
			's'              => $search,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( ! current_user_can( 'edit_others_sermons' ) ) {
			$args['author'] = get_current_user_id();
		}
		$query = new WP_Query( $args );

		cacdemo_manage_toolbar_links( array( array( __( '+ New sermon', 'cacdemo' ), cacdemo_manage_url( 'sermons/new' ), true ) ) );
		printf(
			'<form class="cm-filters" method="get" action="%1$s"><label class="screen-reader-text" for="cm-q">%2$s</label><input type="search" id="cm-q" name="q" value="%3$s" placeholder="%2$s"><label class="screen-reader-text" for="cm-status">%4$s</label><select id="cm-status" name="status"><option value="">%5$s</option><option value="publish" %6$s>%7$s</option><option value="draft" %8$s>%9$s</option><option value="future" %10$s>%11$s</option></select><button class="cm-button" type="submit">%12$s</button></form>',
			esc_url( cacdemo_manage_url( 'sermons' ) ),
			esc_attr__( 'Search sermons', 'cacdemo' ),
			esc_attr( $search ),
			esc_html__( 'Status', 'cacdemo' ),
			esc_html__( 'All', 'cacdemo' ),
			selected( $status, 'publish', false ),
			esc_html__( 'Published', 'cacdemo' ),
			selected( $status, 'draft', false ),
			esc_html__( 'Drafts', 'cacdemo' ),
			selected( $status, 'future', false ),
			esc_html__( 'Scheduled', 'cacdemo' ),
			esc_html__( 'Show', 'cacdemo' )
		);
		if ( ! current_user_can( 'edit_others_sermons' ) ) {
			printf( '<p class="cm-help">%s</p>', esc_html__( 'Showing the sermons you added.', 'cacdemo' ) );
		}
		$rows = array();
		foreach ( $query->posts as $post ) {
			$rows[] = array(
				cacdemo_manage_title_cell( $post, cacdemo_manage_url( 'sermons/' . $post->ID ) ),
				esc_html( get_the_date( '', $post ) ),
				esc_html( implode( ', ', wp_list_pluck( get_the_terms( $post, 'sermon_speaker' ) ?: array(), 'name' ) ) ),
				esc_html( implode( ', ', wp_list_pluck( get_the_terms( $post, 'sermon_series' ) ?: array(), 'name' ) ) ),
				cacdemo_manage_status_cell( $post ),
			);
		}
		cacdemo_manage_table( array( __( 'Title', 'cacdemo' ), __( 'Date', 'cacdemo' ), __( 'Speaker', 'cacdemo' ), __( 'Series', 'cacdemo' ), __( 'Status', 'cacdemo' ) ), $rows, $search ? __( 'No sermons match that search.', 'cacdemo' ) : __( 'No sermons yet.', 'cacdemo' ) );
		cacdemo_manage_pagination( $query, array( 'q' => $search, 'status' => $status ) );
	} );
}

function cacdemo_manage_sermon( $id ) {
	$post = $id ? cacdemo_manage_record( $id, 'sermon' ) : null;
	if ( ! $post && ! current_user_can( get_post_type_object( 'sermon' )->cap->create_posts ) ) {
		cacdemo_manage_forbidden();
	}
	$GLOBALS['cacdemo_manage_labels'] = array( __( 'Sermon title', 'cacdemo' ), __( 'Summary', 'cacdemo' ) );
	cacdemo_manage_render( $post ? __( 'Edit sermon', 'cacdemo' ) : __( 'New sermon', 'cacdemo' ), function () use ( $post ) {
		cacdemo_manage_back( __( 'Sermons', 'cacdemo' ), cacdemo_manage_url( 'sermons' ) );
		if ( $post ) {
			echo cacdemo_manage_view_link( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		}
		$fields = array( 'field_cacdemo_sermon_scripture', 'field_cacdemo_sermon_language', 'field_cacdemo_sermon_location', 'field_cacdemo_sermon_sources' );
		if ( current_user_can( 'edit_others_sermons' ) ) {
			$fields[] = 'field_cacdemo_sermon_featured';
		}
		cacdemo_manage_form( array(
			'type'    => 'sermon',
			'post'    => $post,
			'section' => 'sermons',
			'title'   => true,
			'content' => __( 'Summary', 'cacdemo' ),
			'fields'  => $fields,
			'context' => array( 'section' => 'sermons' ),
			'extras'  => 'cacdemo_manage_sermon_extras',
			'after'   => 'cacdemo_manage_sermon_after',
		) );
		cacdemo_manage_trash_button( $post );
	} );
}

function cacdemo_manage_sermon_extras( $post ) {
	cacdemo_manage_text_input( 'cacdemo_date', __( 'Date preached', 'cacdemo' ), $post ? substr( $post->post_date, 0, 10 ) : wp_date( 'Y-m-d' ), '', 'date', 'required' );

	foreach ( array( 'sermon_speaker' => array( 'cacdemo_speaker', __( 'Speaker', 'cacdemo' ), __( 'Pick a name from the list or type a new one. Leave empty if not known.', 'cacdemo' ) ), 'sermon_series' => array( 'cacdemo_series', __( 'Series', 'cacdemo' ), __( 'Leave empty if this sermon is not part of a series.', 'cacdemo' ) ) ) as $taxonomy => $input ) {
		$terms   = $post ? wp_get_object_terms( $post->ID, $taxonomy ) : array();
		$options = '';
		foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name' ) ) as $term ) {
			$options .= '<option value="' . esc_attr( $term->name ) . '"></option>';
		}
		printf(
			'<div class="cm-field"><label class="cm-label" for="cm-%1$s">%2$s</label><input type="text" id="cm-%1$s" name="%1$s" list="cm-%1$s-list" value="%3$s" autocomplete="off"><datalist id="cm-%1$s-list">%4$s</datalist><p class="cm-help">%5$s</p></div>',
			esc_attr( $input[0] ),
			esc_html( $input[1] ),
			esc_attr( $terms ? $terms[0]->name : '' ),
			$options, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			esc_html( $input[2] )
		);
	}

}

function cacdemo_manage_sermon_after( $post ) {
	$topics = get_terms( array( 'taxonomy' => 'sermon_topic', 'hide_empty' => false, 'orderby' => 'name' ) );
	if ( $topics ) {
		$chosen = $post ? wp_get_object_terms( $post->ID, 'sermon_topic', array( 'fields' => 'ids' ) ) : array();
		printf( '<fieldset class="cm-field cm-topics"><legend class="cm-label">%s</legend><input type="hidden" name="cacdemo_topics_sent" value="1">', esc_html__( 'Topics', 'cacdemo' ) );
		foreach ( $topics as $topic ) {
			printf( '<label class="cm-check"><input type="checkbox" name="cacdemo_topics[]" value="%d" %s> %s</label>', (int) $topic->term_id, checked( in_array( $topic->term_id, $chosen, true ), true, false ), esc_html( $topic->name ) );
		}
		echo '</fieldset>';
	}
	cacdemo_manage_cover_input( $post, __( 'Cover image', 'cacdemo' ) );
}

/* ---------------------------------------------------------------- Ministries, ways to serve, updates */

function cacdemo_manage_level_label( $level ) {
	$labels = array( 'admin' => __( 'Content Admin', 'cacdemo' ), 'publisher' => __( 'Publisher', 'cacdemo' ), 'contributor' => __( 'Contributor', 'cacdemo' ) );
	return $labels[ $level ] ?? '';
}

function cacdemo_manage_ministries() {
	cacdemo_manage_render( __( 'Ministries', 'cacdemo' ), function () {
		$rows = array();
		foreach ( cacdemo_manage_user_ministries() as $id => $level ) {
			$ministry = get_post( $id );
			$updates  = count( get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'future' ), 'numberposts' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => 'update_ministry', 'value' => (string) $id ) ) ) ) );
			$rows[]   = array(
				sprintf( '<a class="cm-row-title" href="%s">%s</a>', esc_url( cacdemo_manage_url( 'ministries/' . $id ) ), esc_html( $ministry->post_title ) ),
				esc_html( cacdemo_manage_level_label( $level ) ),
				esc_html( (string) count( cacdemo_ministry_roles( $id ) ) ),
				esc_html( (string) $updates ),
			);
		}
		cacdemo_manage_table( array( __( 'Ministry', 'cacdemo' ), __( 'Your access', 'cacdemo' ), __( 'Ways to serve', 'cacdemo' ), __( 'Updates', 'cacdemo' ) ), $rows, __( 'No ministries are assigned to you.', 'cacdemo' ) );
	} );
}

function cacdemo_manage_ministry( $id ) {
	$ministries = cacdemo_manage_user_ministries();
	$ministry   = get_post( $id );
	if ( ! $ministry || 'ministry' !== $ministry->post_type || ! isset( $ministries[ $id ] ) ) {
		cacdemo_manage_forbidden();
	}
	$GLOBALS['cacdemo_manage_labels'] = array( '', __( 'Introduction', 'cacdemo' ) );
	cacdemo_manage_render( $ministry->post_title, function () use ( $ministry ) {
		cacdemo_manage_back( __( 'Ministries', 'cacdemo' ), cacdemo_manage_url( 'ministries' ) );
		echo cacdemo_manage_view_link( $ministry ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		$can_edit = current_user_can( 'edit_post', $ministry->ID );

		// Updates first: the thing most people come to do.
		printf( '<section class="cm-section"><h2>%s</h2>', esc_html__( 'Updates', 'cacdemo' ) );
		cacdemo_manage_toolbar_links( array( array( __( '+ Add update', 'cacdemo' ), cacdemo_manage_url( 'updates/new', array( 'ministry' => $ministry->ID ) ), true ) ) );
		$rows = array();
		foreach ( get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'future', 'pending', 'private' ), 'numberposts' => 50, 'meta_query' => array( array( 'key' => 'update_ministry', 'value' => (string) $ministry->ID ) ) ) ) as $post ) {
			$rows[] = array( cacdemo_manage_title_cell( $post, cacdemo_manage_url( 'updates/' . $post->ID ) ), esc_html( get_the_date( '', $post ) ), esc_html( get_the_author_meta( 'display_name', $post->post_author ) ), cacdemo_manage_status_cell( $post ) );
		}
		cacdemo_manage_table( array( __( 'Title', 'cacdemo' ), __( 'Date', 'cacdemo' ), __( 'By', 'cacdemo' ), __( 'Status', 'cacdemo' ) ), $rows, __( 'No updates yet.', 'cacdemo' ) );
		echo '</section>';

		if ( ! $can_edit ) {
			return;
		}

		printf( '<section class="cm-section"><h2>%s</h2>', esc_html__( 'Ways to serve', 'cacdemo' ) );
		cacdemo_manage_toolbar_links( array( array( __( '+ Add a way to serve', 'cacdemo' ), cacdemo_manage_url( 'roles/new', array( 'ministry' => $ministry->ID ) ) ) ) );
		$status_labels = array( 'ongoing' => __( 'Ongoing', 'cacdemo' ), 'needed' => __( 'Needed now', 'cacdemo' ), 'paused' => __( 'Paused', 'cacdemo' ), 'filled' => __( 'Filled', 'cacdemo' ) );
		$rows          = array();
		foreach ( get_posts( array( 'post_type' => 'serve_role', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'meta_query' => array( array( 'key' => 'role_ministry', 'value' => (string) $ministry->ID ) ) ) ) as $role ) {
			$rows[] = array( cacdemo_manage_title_cell( $role, cacdemo_manage_url( 'roles/' . $role->ID ) ), esc_html( $status_labels[ get_post_meta( $role->ID, 'role_status', true ) ] ?? '' ), cacdemo_manage_status_cell( $role ) );
		}
		cacdemo_manage_table( array( __( 'Way to serve', 'cacdemo' ), __( 'Shown as', 'cacdemo' ), __( 'Status', 'cacdemo' ) ), $rows, __( 'No ways to serve yet.', 'cacdemo' ) );
		echo '</section>';

		printf( '<section class="cm-section"><h2>%s</h2>', esc_html__( 'Page details', 'cacdemo' ) );
		cacdemo_manage_form( array(
			'type'    => 'ministry',
			'post'    => $ministry,
			'section' => 'ministries',
			'title'   => false,
			'content' => __( 'Introduction', 'cacdemo' ),
			'fields'  => array( 'field_cacdemo_ministry_tagline', 'field_cacdemo_ministry_contact_label', 'field_cacdemo_ministry_contact_url', 'field_cacdemo_ministry_invite_heading', 'field_cacdemo_ministry_invite_text' ),
			'context' => array( 'section' => 'ministries' ),
			'after'   => fn( $post ) => cacdemo_manage_cover_input( $post, __( 'Photo', 'cacdemo' ) ),
		) );
		echo '</section>';
	} );
}

function cacdemo_manage_role( $id ) {
	if ( $id ) {
		$role     = cacdemo_manage_record( $id, 'serve_role' );
		$ministry = (int) get_post_meta( $role->ID, 'role_ministry', true );
	} else {
		$role     = null;
		$ministry = absint( $_GET['ministry'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification -- checked below and signed into the form.
		if ( 'ministry' !== get_post_type( $ministry ) || ! current_user_can( 'edit_post', $ministry ) || ! current_user_can( get_post_type_object( 'serve_role' )->cap->create_posts ) ) {
			cacdemo_manage_forbidden();
		}
	}
	$GLOBALS['cacdemo_manage_labels'] = array( __( 'Way to serve', 'cacdemo' ), '' );
	cacdemo_manage_render( $role ? __( 'Edit way to serve', 'cacdemo' ) : __( 'New way to serve', 'cacdemo' ), function () use ( $role, $ministry ) {
		cacdemo_manage_back( get_the_title( $ministry ), cacdemo_manage_url( 'ministries/' . $ministry ) );
		cacdemo_manage_form( array(
			'type'    => 'serve_role',
			'post'    => $role,
			'section' => 'roles',
			'title'   => true,
			'content' => false,
			'fields'  => array( 'field_cacdemo_role_status', 'field_cacdemo_role_experience' ),
			'context' => array( 'ministry' => $ministry, 'section' => 'roles' ),
			'extras'  => fn( $post ) => cacdemo_manage_textarea( 'cacdemo_excerpt', __( 'One line about it', 'cacdemo' ), $post ? $post->post_excerpt : '', __( 'For example: “Help make worship, teaching and events clearly heard.”', 'cacdemo' ) ),
		) );
		cacdemo_manage_trash_button( $role );
	} );
}

function cacdemo_manage_updates() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_safe_redirect( cacdemo_manage_url( 'ministries' ) );
		exit;
	}
	cacdemo_manage_render( __( 'News & updates', 'cacdemo' ), function () {
		cacdemo_manage_toolbar_links( array( array( __( '+ New update', 'cacdemo' ), cacdemo_manage_url( 'updates/new' ), true ) ) );
		$query = new WP_Query( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'future', 'pending', 'private' ), 'posts_per_page' => 25, 'paged' => max( 1, (int) ( $_GET['pg'] ?? 1 ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$rows  = array();
		foreach ( $query->posts as $post ) {
			$ministry = (int) get_post_meta( $post->ID, 'update_ministry', true );
			$rows[]   = array( cacdemo_manage_title_cell( $post, cacdemo_manage_url( 'updates/' . $post->ID ) ), esc_html( $ministry ? get_the_title( $ministry ) : __( 'Church-wide', 'cacdemo' ) ), esc_html( get_the_date( '', $post ) ), cacdemo_manage_status_cell( $post ) );
		}
		cacdemo_manage_table( array( __( 'Title', 'cacdemo' ), __( 'For', 'cacdemo' ), __( 'Date', 'cacdemo' ), __( 'Status', 'cacdemo' ) ), $rows, __( 'No news or updates yet.', 'cacdemo' ) );
		cacdemo_manage_pagination( $query, array() );
	} );
}

function cacdemo_manage_update( $id ) {
	$user = get_current_user_id();
	if ( $id ) {
		$post     = cacdemo_manage_record( $id, 'post' );
		$ministry = (int) get_post_meta( $post->ID, 'update_ministry', true );
	} else {
		$post     = null;
		$ministry = absint( $_GET['ministry'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification -- checked below and signed into the form.
		$allowed  = $ministry
			? 'ministry' === get_post_type( $ministry ) && ( current_user_can( 'edit_others_posts' ) || cacdemo_publishing_level( $user, 'ministry:' . $ministry ) )
			: current_user_can( 'edit_others_posts' );
		if ( ! $allowed ) {
			$mine = cacdemo_publishing_user_ministries( $user );
			if ( ! $ministry && 1 === count( $mine ) ) {
				wp_safe_redirect( cacdemo_manage_url( 'updates/new', array( 'ministry' => $mine[0] ) ) );
				exit;
			}
			cacdemo_manage_forbidden();
		}
	}
	$back = $ministry && ! current_user_can( 'edit_others_posts' ) ? array( get_the_title( $ministry ), cacdemo_manage_url( 'ministries/' . $ministry ) ) : array( __( 'News & updates', 'cacdemo' ), cacdemo_manage_url( 'updates' ) );
	$GLOBALS['cacdemo_manage_labels'] = array( __( 'Title', 'cacdemo' ), __( 'Text', 'cacdemo' ) );
	cacdemo_manage_render( $post ? __( 'Edit update', 'cacdemo' ) : __( 'New update', 'cacdemo' ), function () use ( $post, $ministry, $back ) {
		cacdemo_manage_back( $back[0], $back[1] );
		if ( $post ) {
			echo cacdemo_manage_view_link( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		}
		/* translators: %s: ministry name */
		printf( '<p class="cm-context">%s</p>', esc_html( $ministry ? sprintf( __( 'For %s. It will appear in that ministry’s Updates.', 'cacdemo' ), get_the_title( $ministry ) ) : __( 'Church-wide news (not linked to a ministry).', 'cacdemo' ) ) );
		cacdemo_manage_form( array(
			'type'    => 'post',
			'post'    => $post,
			'section' => 'updates',
			'title'   => true,
			'content' => __( 'Text', 'cacdemo' ),
			'fields'  => array(),
			'context' => array( 'ministry' => $ministry, 'section' => 'updates' ),
			'after'   => function ( $post ) {
				cacdemo_manage_textarea( 'cacdemo_excerpt', __( 'Short summary', 'cacdemo' ), $post ? $post->post_excerpt : '', __( 'Shown in lists. Leave empty to use the start of the text.', 'cacdemo' ) );
				cacdemo_manage_cover_input( $post, __( 'Image', 'cacdemo' ) );
			},
		) );
		cacdemo_manage_trash_button( $post );
	} );
}

/* ---------------------------------------------------------------- Pages */

function cacdemo_manage_pages() {
	cacdemo_manage_render( __( 'Pages', 'cacdemo' ), function () {
		cacdemo_manage_toolbar_links( array( array( __( '+ New page', 'cacdemo' ), cacdemo_manage_url( 'pages/new' ), true ) ) );
		$pages = get_pages( array( 'post_status' => array( 'publish', 'draft', 'future', 'private' ), 'sort_column' => 'menu_order,post_title', 'hierarchical' => true ) );
		$rows  = array();
		$walk  = function ( $parent, $depth ) use ( &$walk, &$rows, $pages ) {
			foreach ( $pages as $page ) {
				if ( (int) $page->post_parent !== $parent ) {
					continue;
				}
				$rows[] = array(
					str_repeat( '<span class="cm-indent" aria-hidden="true">— </span>', $depth ) . cacdemo_manage_title_cell( $page, cacdemo_manage_url( 'pages/' . $page->ID ) ),
					esc_html( (int) get_option( 'page_on_front' ) === (int) $page->ID ? '/ ' . __( '(home page)', 'cacdemo' ) : '/' . get_page_uri( $page ) . '/' ),
					cacdemo_manage_status_cell( $page ),
					current_user_can( 'publish_pages' ) ? sprintf( '<a href="%s">%s</a>', esc_url( cacdemo_manage_url( 'pages/new', array( 'parent' => $page->ID ) ) ), esc_html__( '+ Sub-page', 'cacdemo' ) ) : '',
				);
				$walk( (int) $page->ID, $depth + 1 );
			}
		};
		$walk( 0, 0 );
		cacdemo_manage_table( array( __( 'Page', 'cacdemo' ), __( 'Address', 'cacdemo' ), __( 'Status', 'cacdemo' ), '' ), $rows, __( 'No pages yet.', 'cacdemo' ) );
		printf( '<p class="cm-help">%s</p>', esc_html__( 'New pages are not added to the main menu automatically; they appear in the footer’s page list once published. Ask an administrator to add a page to the menu.', 'cacdemo' ) );
	} );
}

function cacdemo_manage_page( $id ) {
	$page = $id ? cacdemo_manage_record( $id, 'page' ) : null;
	if ( ! $page && ! current_user_can( get_post_type_object( 'page' )->cap->create_posts ) ) {
		cacdemo_manage_forbidden();
	}
	$GLOBALS['cacdemo_manage_labels'] = array( __( 'Page title', 'cacdemo' ), __( 'Text', 'cacdemo' ) );
	cacdemo_manage_render( $page ? __( 'Edit page', 'cacdemo' ) : __( 'New page', 'cacdemo' ), function () use ( $page ) {
		cacdemo_manage_back( __( 'Pages', 'cacdemo' ), cacdemo_manage_url( 'pages' ) );
		if ( $page ) {
			echo cacdemo_manage_view_link( $page ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		}
		cacdemo_manage_form( array(
			'type'    => 'page',
			'post'    => $page,
			'section' => 'pages',
			'title'   => true,
			'content' => __( 'Text', 'cacdemo' ),
			'fields'  => array(),
			'context' => array( 'section' => 'pages' ),
			'after'   => 'cacdemo_manage_page_extras',
		) );
		cacdemo_manage_trash_button( $page );
	} );
}

function cacdemo_manage_page_extras( $page ) {
	$parent  = $page ? (int) $page->post_parent : absint( $_GET['parent'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification -- preselects a choice; checked on save.
	$pages   = get_pages( array( 'post_status' => array( 'publish', 'draft', 'private' ), 'sort_column' => 'menu_order,post_title' ) );
	$exclude = $page ? array_merge( array( $page->ID ), wp_list_pluck( get_page_children( $page->ID, $pages ), 'ID' ) ) : array(); // A page cannot go under itself or its sub-pages.
	$options = sprintf( '<option value="0">%s</option>', esc_html__( 'None (top level)', 'cacdemo' ) );
	foreach ( $pages as $candidate ) {
		if ( in_array( $candidate->ID, $exclude, true ) || ! current_user_can( 'edit_post', $candidate->ID ) ) {
			continue;
		}
		$depth    = count( get_post_ancestors( $candidate ) );
		$options .= sprintf( '<option value="%d" %s>%s%s</option>', (int) $candidate->ID, selected( $parent, $candidate->ID, false ), esc_html( str_repeat( '— ', $depth ) ), esc_html( $candidate->post_title ) );
	}
	printf(
		'<div class="cm-field"><label class="cm-label" for="cm-parent">%1$s</label><select id="cm-parent" name="cacdemo_parent">%2$s</select><p class="cm-help">%3$s</p></div>',
		esc_html__( 'Place under', 'cacdemo' ),
		$options, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		esc_html__( 'A sub-page’s address starts with its parent’s, e.g. /about/our-story/.', 'cacdemo' )
	);
	cacdemo_manage_text_input( 'cacdemo_order', __( 'Order among its neighbours', 'cacdemo' ), $page ? (string) $page->menu_order : '0', __( 'Lower numbers come first.', 'cacdemo' ), 'number', 'min="0" step="1"' );
}

/* ---------------------------------------------------------------- Social channels */

function cacdemo_manage_channels() {
	cacdemo_manage_render( __( 'Social channels', 'cacdemo' ), function () {
		cacdemo_manage_toolbar_links( array( array( __( '+ Add a channel', 'cacdemo' ), cacdemo_manage_url( 'channels/new' ), true ) ) );
		$rows = array();
		foreach ( get_posts( array( 'post_type' => 'channel', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) as $channel ) {
			$url    = (string) get_post_meta( $channel->ID, 'channel_url', true );
			$rows[] = array(
				cacdemo_manage_title_cell( $channel, cacdemo_manage_url( 'channels/' . $channel->ID ) ),
				esc_html( cacdemo_channel_platform_label( (string) get_post_meta( $channel->ID, 'channel_platform', true ) ) ),
				$url ? sprintf( '<a href="%s" rel="noopener" target="_blank">%s</a>', esc_url( $url ), esc_html( wp_parse_url( $url, PHP_URL_HOST ) . wp_parse_url( $url, PHP_URL_PATH ) ) ) : '',
				cacdemo_manage_status_cell( $channel ),
			);
		}
		cacdemo_manage_table( array( __( 'Name', 'cacdemo' ), __( 'Platform', 'cacdemo' ), __( 'Link', 'cacdemo' ), __( 'Status', 'cacdemo' ) ), $rows, __( 'No channels yet.', 'cacdemo' ) );
		printf( '<p class="cm-help">%s</p>', esc_html__( 'Published channels appear on Follow Us, Connect and (when chosen) the home page, in this order.', 'cacdemo' ) );
	} );
}

function cacdemo_manage_channel( $id ) {
	$channel = $id ? cacdemo_manage_record( $id, 'channel' ) : null;
	if ( ! $channel && ! current_user_can( get_post_type_object( 'channel' )->cap->create_posts ) ) {
		cacdemo_manage_forbidden();
	}
	$GLOBALS['cacdemo_manage_labels'] = array( __( 'Full name, as on the platform', 'cacdemo' ), '' );
	cacdemo_manage_render( $channel ? __( 'Edit channel', 'cacdemo' ) : __( 'Add a channel', 'cacdemo' ), function () use ( $channel ) {
		cacdemo_manage_back( __( 'Social channels', 'cacdemo' ), cacdemo_manage_url( 'channels' ) );
		cacdemo_manage_form( array(
			'type'    => 'channel',
			'post'    => $channel,
			'section' => 'channels',
			'title'   => true,
			'content' => false,
			'fields'  => array( 'field_cacdemo_channel_short_name', 'field_cacdemo_channel_platform', 'field_cacdemo_channel_url', 'field_cacdemo_channel_purpose', 'field_cacdemo_channel_centre', 'field_cacdemo_channel_embed', 'field_cacdemo_channel_home' ),
			'context' => array( 'section' => 'channels' ),
		) );
		cacdemo_manage_trash_button( $channel );
	} );
}

/* ---------------------------------------------------------------- People */

function cacdemo_manage_people() {
	cacdemo_manage_render( __( 'People', 'cacdemo' ), function () {
		cacdemo_manage_toolbar_links( array( array( __( '+ Add a person', 'cacdemo' ), cacdemo_manage_url( 'people/new' ), true ) ) );
		printf( '<p class="cm-lead">%s</p>', esc_html__( 'Contributors add and publish in their sections and edit their own items. Publishers can also edit anyone’s items there and, for a ministry, its page and ways to serve. Content Admins can edit everything and manage this list.', 'cacdemo' ) );
		$sections = cacdemo_publishing_sections();
		$levels   = cacdemo_publishing_levels();
		$users    = get_users( array( 'orderby' => 'display_name', 'meta_key' => CACDEMO_SCOPES_META ) ); // phpcs:ignore WordPress.DB.SlowDBQuery -- small list.
		$ids      = wp_list_pluck( $users, 'ID' );
		$users    = array_merge( $users, array_filter( get_users( array( 'role' => 'editor', 'orderby' => 'display_name' ) ), fn( $u ) => ! in_array( $u->ID, $ids, true ) ) );
		$rows     = array();
		foreach ( $users as $user ) {
			$lines = cacdemo_contributors_is_content_admin( $user ) ? array( '<strong>' . esc_html__( 'Content Admin — entire website', 'cacdemo' ) . '</strong>' ) : array();
			foreach ( cacdemo_publishing_scopes( $user->ID ) as $scope => $level ) {
				$lines[] = esc_html( ( $sections[ $scope ] ?? '' ) . ' — ' . $levels[ $level ] );
			}
			$name   = cacdemo_contributors_can_manage( $user ) ? sprintf( '<a class="cm-row-title" href="%s">%s</a>', esc_url( cacdemo_manage_url( 'people/' . $user->ID ) ), esc_html( $user->display_name ) ) : '<span class="cm-row-title">' . esc_html( $user->display_name ) . '</span>';
			$rows[] = array(
				$name . '<br><span class="cm-muted">' . esc_html( $user->user_email ) . '</span>',
				implode( '<br>', $lines ) ?: '—',
				esc_html( get_user_meta( $user->ID, 'cacdemo_invited', true ) ? __( 'Invited — not signed in yet', 'cacdemo' ) : __( 'Active', 'cacdemo' ) ),
			);
		}
		cacdemo_manage_table( array( __( 'Person', 'cacdemo' ), __( 'Sections', 'cacdemo' ), __( 'Account', 'cacdemo' ) ), $rows, __( 'No contributors yet.', 'cacdemo' ) );
	} );
}

function cacdemo_manage_person( $id ) {
	$user = $id ? get_userdata( $id ) : null;
	if ( $id && ( ! $user || ! cacdemo_contributors_can_manage( $user ) ) ) {
		cacdemo_manage_forbidden();
	}
	cacdemo_manage_render( $user ? $user->display_name : __( 'Add a person', 'cacdemo' ), function () use ( $user ) {
		cacdemo_manage_back( __( 'People', 'cacdemo' ), cacdemo_manage_url( 'people' ) );
		$scopes = $user ? cacdemo_publishing_scopes( $user->ID ) : array();
		echo '<form method="post" class="cm-form cm-people-form">';
		wp_nonce_field( 'cacdemo_manage_people_save' );
		echo '<input type="hidden" name="cacdemo_manage_action" value="people_save">';
		if ( $user ) {
			printf( '<input type="hidden" name="user_id" value="%d"><p class="cm-context">%s</p>', (int) $user->ID, esc_html( $user->user_email ) );
		} else {
			cacdemo_manage_text_input( 'person', __( 'Email or username', 'cacdemo' ), '', __( 'An existing account is used when one matches. Otherwise enter their email address: an account is created and WordPress emails them a link to set a password.', 'cacdemo' ), 'text', 'required autocomplete="off"' );
			cacdemo_manage_text_input( 'name', __( 'Name', 'cacdemo' ), '', __( 'Only used for a new account.', 'cacdemo' ), 'text', 'autocomplete="off"' );
		}
		if ( current_user_can( 'promote_users' ) && ! ( $user && in_array( 'administrator', $user->roles, true ) ) ) {
			printf( '<div class="cm-field"><label class="cm-check"><input type="checkbox" name="content_admin" value="1" %s> %s</label><p class="cm-help">%s</p></div>', checked( $user && cacdemo_contributors_is_content_admin( $user ), true, false ), esc_html__( 'Content Admin', 'cacdemo' ), esc_html__( 'Can edit all content on the website and manage people.', 'cacdemo' ) );
		}
		printf( '<fieldset class="cm-field cm-scopes"><legend class="cm-label">%s</legend>', esc_html__( 'Can contribute to', 'cacdemo' ) );
		$group = '';
		foreach ( cacdemo_publishing_sections() as $scope => $label ) {
			$this_group = str_starts_with( $scope, 'ministry:' ) ? __( 'Ministries', 'cacdemo' ) : '';
			if ( $this_group && $this_group !== $group ) {
				printf( '<p class="cm-sub">%s</p>', esc_html( $this_group ) );
			}
			$group = $this_group;
			$field = 'cm-scope-' . sanitize_html_class( str_replace( ':', '-', $scope ) );
			printf( '<div class="cm-scope"><label for="%1$s">%2$s</label><select id="%1$s" name="scopes[%3$s]"><option value="">%4$s</option>', esc_attr( $field ), esc_html( $label ), esc_attr( $scope ), esc_html__( 'No access', 'cacdemo' ) );
			foreach ( cacdemo_publishing_levels() as $level => $level_label ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $level ), selected( $scopes[ $scope ] ?? '', $level, false ), esc_html( $level_label ) );
			}
			echo '</select></div>';
		}
		echo '</fieldset>';
		printf( '<div class="cm-actions"><button type="submit" class="cm-button is-primary">%s</button></div></form>', esc_html( $user ? __( 'Save', 'cacdemo' ) : __( 'Add person', 'cacdemo' ) ) );

		if ( $user && $scopes ) {
			echo '<form method="post" class="cm-trash">';
			wp_nonce_field( 'cacdemo_manage_people_remove' );
			printf( '<input type="hidden" name="cacdemo_manage_action" value="people_remove"><input type="hidden" name="user_id" value="%d"><button type="submit" class="cm-button is-danger">%s</button></form>', (int) $user->ID, esc_html__( 'Remove all sections', 'cacdemo' ) );
		}
	} );
}
