<?php
/**
 * Appearance → Site Theme: choose the everyday theme, schedule themes for a period, preview themes and palettes, and see
 * which visitor palettes are offered. Curated choices only; no colour or CSS controls (docs/VISUAL-THEMES-DESIGN.md §6).
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_SITE_THEME_PAGE = 'cacdemo-site-theme';

add_action( 'admin_menu', 'cacdemo_site_theme_menu' );

function cacdemo_site_theme_menu() {
	add_theme_page( __( 'Site Theme', 'cacdemo' ), __( 'Site Theme', 'cacdemo' ), 'edit_theme_options', CACDEMO_SITE_THEME_PAGE, 'cacdemo_site_theme_screen' );
}

function cacdemo_site_theme_admin_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => CACDEMO_SITE_THEME_PAGE ), $args ), admin_url( 'themes.php' ) );
}

/* ---------------------------------------------------------------- Saving (also used by tests) */

function cacdemo_site_theme_update_state( $state ) {
	update_option( CACDEMO_SITE_THEME_OPTION, cacdemo_site_theme_sanitize_state( $state ), false );
	cacdemo_site_theme_reset_caches();
}

/** Sets the everyday theme and its effect intensity. */
function cacdemo_site_theme_save_everyday( $theme, $effects ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return new WP_Error( 'forbidden', __( 'You cannot change the site theme.', 'cacdemo' ) );
	}
	if ( ! cacdemo_site_theme_package( $theme ) ) {
		return new WP_Error( 'theme', __( 'Choose one of the available themes.', 'cacdemo' ) );
	}
	$state             = cacdemo_site_theme_state();
	$state['everyday'] = $theme;
	$state['effects']  = in_array( $effects, CACDEMO_EFFECT_INTENSITIES, true ) ? $effects : $state['effects'];
	cacdemo_site_theme_update_state( $state );
	return true;
}

/** Adds a scheduled theme. Times are site-local "Y-m-d\TH:i" (what a datetime-local field sends). */
function cacdemo_site_theme_add_schedule( $theme, $starts, $ends, $effects ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return new WP_Error( 'forbidden', __( 'You cannot change the site theme.', 'cacdemo' ) );
	}
	if ( ! cacdemo_site_theme_package( $theme ) ) {
		return new WP_Error( 'theme', __( 'Choose one of the available themes.', 'cacdemo' ) );
	}
	$start = cacdemo_site_theme_parse_time( $starts );
	$end   = cacdemo_site_theme_parse_time( $ends );
	if ( ! $start || ! $end ) {
		return new WP_Error( 'time', __( 'Enter a start and an end date and time.', 'cacdemo' ) );
	}
	if ( $end <= $start ) {
		return new WP_Error( 'time', __( 'The end must be after the start.', 'cacdemo' ) );
	}
	$entry = cacdemo_site_theme_sanitize_entry( array( 'theme' => $theme, 'starts' => $starts, 'ends' => $ends, 'effects' => $effects ) );
	$state = cacdemo_site_theme_state();
	$state['schedule'][] = $entry;
	usort( $state['schedule'], fn( $a, $b ) => strcmp( $a['starts'], $b['starts'] ) );
	cacdemo_site_theme_update_state( $state );
	return $entry['id'];
}

function cacdemo_site_theme_remove_schedule( $id ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return new WP_Error( 'forbidden', __( 'You cannot change the site theme.', 'cacdemo' ) );
	}
	$state             = cacdemo_site_theme_state();
	$before            = count( $state['schedule'] );
	$state['schedule'] = array_values( array_filter( $state['schedule'], fn( $e ) => $e['id'] !== $id ) );
	if ( count( $state['schedule'] ) === $before ) {
		return new WP_Error( 'missing', __( 'That scheduled theme was not found.', 'cacdemo' ) );
	}
	cacdemo_site_theme_update_state( $state );
	return true;
}

/** What each image slot is used for (shown in Appearance → Site Theme). */
function cacdemo_site_theme_slot_labels() {
	return array(
		'home-hero'       => array( __( 'Home page hero', 'cacdemo' ), __( 'Behind the welcome at the top of the home page.', 'cacdemo' ) ),
		'page-hero'       => array( __( 'General page hero', 'cacdemo' ), __( 'For page heroes set to use the general image. Pages such as Contact stay plain unless they have their own banner.', 'cacdemo' ) ),
		'ministries-hero' => array( __( 'Ministries hero', 'cacdemo' ), __( 'The Ministries page, and ministry pages without their own image.', 'cacdemo' ) ),
		'sermons-hero'    => array( __( 'Sermons hero', 'cacdemo' ), __( 'The top of the Sermons page.', 'cacdemo' ) ),
		'fallback'        => array( __( 'Fallback image', 'cacdemo' ), __( 'Used for any hero or card above that has no image of its own.', 'cacdemo' ) ),
	);
}

add_filter( 'cacdemo_theme_image_slots', 'cacdemo_site_theme_image_slots' );

/** The image slots any theme offers, for pickers outside this screen (the page-hero block). */
function cacdemo_site_theme_image_slots( $slots ) {
	$labels = cacdemo_site_theme_slot_labels();
	foreach ( cacdemo_site_theme_packages() as $package ) {
		foreach ( $package['slots'] as $slot ) {
			$slots[ $slot ] = $labels[ $slot ][0] ?? $slot;
		}
	}
	return $slots;
}

/**
 * Sets a theme's slot images. $images is slot => attachment ID (0 clears the slot). Only the package's own slots are
 * accepted, and only image attachments.
 */
function cacdemo_site_theme_save_images( $theme, $images ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return new WP_Error( 'forbidden', __( 'You cannot change the site theme.', 'cacdemo' ) );
	}
	$package = cacdemo_site_theme_package( $theme );
	if ( ! $package ) {
		return new WP_Error( 'theme', __( 'Choose one of the available themes.', 'cacdemo' ) );
	}
	$state = cacdemo_site_theme_state();
	foreach ( (array) $images as $slot => $attachment ) {
		$attachment = absint( $attachment );
		if ( ! in_array( $slot, $package['slots'], true ) ) {
			return new WP_Error( 'slot', __( 'That image slot does not belong to this theme.', 'cacdemo' ) );
		}
		if ( $attachment && ! wp_attachment_is_image( $attachment ) ) {
			return new WP_Error( 'image', __( 'Choose an image from the Media Library.', 'cacdemo' ) );
		}
		if ( $attachment ) {
			$state['images'][ $theme ][ $slot ] = $attachment;
		} else {
			unset( $state['images'][ $theme ][ $slot ] );
		}
	}
	cacdemo_site_theme_update_state( $state );
	return true;
}

add_action( 'admin_post_cacdemo_site_theme', 'cacdemo_site_theme_handle' );

function cacdemo_site_theme_handle() {
	check_admin_referer( 'cacdemo_site_theme' );
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
	$do     = sanitize_key( wp_unslash( $_POST['do'] ?? '' ) );
	$result = new WP_Error( 'unknown', __( 'Nothing was changed.', 'cacdemo' ) );
	if ( 'everyday' === $do ) {
		$result = cacdemo_site_theme_save_everyday( sanitize_key( wp_unslash( $_POST['everyday'] ?? '' ) ), sanitize_key( wp_unslash( $_POST['effects'] ?? '' ) ) );
		$done   = __( 'Everyday theme saved.', 'cacdemo' );
	} elseif ( 'schedule' === $do ) {
		$result = cacdemo_site_theme_add_schedule( sanitize_key( wp_unslash( $_POST['theme'] ?? '' ) ), sanitize_text_field( wp_unslash( $_POST['starts'] ?? '' ) ), sanitize_text_field( wp_unslash( $_POST['ends'] ?? '' ) ), sanitize_key( wp_unslash( $_POST['effects'] ?? '' ) ) );
		$done   = __( 'Theme scheduled.', 'cacdemo' );
	} elseif ( 'unschedule' === $do ) {
		$result = cacdemo_site_theme_remove_schedule( sanitize_key( wp_unslash( $_POST['entry'] ?? '' ) ) );
		$done   = __( 'Scheduled theme removed.', 'cacdemo' );
	} elseif ( 'images' === $do ) {
		$images = array_map( 'absint', (array) wp_unslash( $_POST['images'] ?? array() ) );
		$result = cacdemo_site_theme_save_images( sanitize_key( wp_unslash( $_POST['theme'] ?? '' ) ), $images );
		$done   = __( 'Theme images saved.', 'cacdemo' );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	do_action( 'cacdemo_site_theme_handle', $do );
	$args = is_wp_error( $result ) ? array( 'error' => rawurlencode( $result->get_error_message() ) ) : array( 'done' => rawurlencode( $done ) );
	wp_safe_redirect( cacdemo_site_theme_admin_url( $args ) );
	exit;
}

/* ---------------------------------------------------------------- Screen */

add_action( 'admin_enqueue_scripts', 'cacdemo_site_theme_admin_assets' );

function cacdemo_site_theme_admin_assets( $hook ) {
	if ( 'appearance_page_' . CACDEMO_SITE_THEME_PAGE === $hook ) {
		wp_enqueue_style( 'cacdemo-site-theme-admin', get_theme_file_uri( 'assets/css/appearance-admin.css' ), array(), (string) filemtime( get_theme_file_path( 'assets/css/appearance-admin.css' ) ) );
		wp_enqueue_media();
		wp_enqueue_script( 'cacdemo-site-theme-admin', get_theme_file_uri( 'assets/js/appearance-admin.js' ), array( 'jquery', 'media-editor' ), (string) filemtime( get_theme_file_path( 'assets/js/appearance-admin.js' ) ), true );
		do_action( 'cacdemo_site_theme_admin_assets' );
	}
}

function cacdemo_site_theme_effects_label( $intensity ) {
	$labels = array( 'off' => __( 'Off', 'cacdemo' ), 'subtle' => __( 'Subtle', 'cacdemo' ), 'enhanced' => __( 'Enhanced', 'cacdemo' ) );
	return $labels[ $intensity ] ?? $intensity;
}

function cacdemo_site_theme_type_label( $type ) {
	$labels = array( 'base' => __( 'Everyday', 'cacdemo' ), 'natural-season' => __( 'Natural season', 'cacdemo' ), 'church-occasion' => __( 'Church occasion', 'cacdemo' ), 'biblical-observance' => __( 'Biblical observance', 'cacdemo' ), 'program' => __( 'Church program', 'cacdemo' ) );
	return $labels[ $type ] ?? $type;
}

function cacdemo_palette_swatch( $palette ) {
	$c = $palette['colors'];
	return sprintf(
		'<span class="cacdemo-swatch" aria-hidden="true"><i style="background:%s"></i><i style="background:%s"></i><i style="background:%s"></i><i style="background:%s"></i></span>',
		esc_attr( $c['paper'] ),
		esc_attr( $c['night'] ),
		esc_attr( $c['stage'] ),
		esc_attr( $palette['custom']['highlight'] )
	);
}

function cacdemo_site_theme_preview_url( $args ) {
	return add_query_arg( $args, home_url( '/' ) );
}

function cacdemo_site_theme_format_time( $value ) {
	$time = cacdemo_site_theme_parse_time( $value );
	return $time ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time->getTimestamp() ) : $value;
}

function cacdemo_site_theme_screen() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You cannot change the site theme.', 'cacdemo' ) );
	}
	$state    = cacdemo_site_theme_state();
	$packages = cacdemo_site_theme_packages();
	$now      = cacdemo_site_theme_now();
	$current  = cacdemo_site_theme_resolve( $state, $now );
	$active   = $packages[ $current['theme'] ];
	$effects_themes = array_filter( $packages, fn( $p ) => $p['effects'] );

	echo '<div class="wrap cacdemo-site-theme">';
	printf( '<h1>%s</h1>', esc_html__( 'Site Theme', 'cacdemo' ) );
	printf( '<p class="cacdemo-lead">%s</p>', esc_html__( 'Choose how the website presents itself through the year. Visitors can still pick their own colours; themes change the atmosphere, images and seasonal details.', 'cacdemo' ) );

	foreach ( array( 'done' => 'success', 'error' => 'error' ) as $key => $type ) {
		if ( ! empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- message only.
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( wp_unslash( $_GET[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
		}
	}

	// Showing now.
	$source = array(
		'schedule' => $current['until'] ? sprintf( /* translators: %s: end date and time */ __( 'Scheduled, until %s', 'cacdemo' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $current['until']->getTimestamp() ) ) : __( 'Scheduled', 'cacdemo' ),
		'everyday' => __( 'Everyday theme', 'cacdemo' ),
		'fallback' => __( 'Default (the saved theme is not available)', 'cacdemo' ),
	);
	echo '<div class="cacdemo-card cacdemo-now">';
	printf( '<h2>%s</h2>', esc_html__( 'Showing now', 'cacdemo' ) );
	printf(
		'<p class="cacdemo-now__theme">%1$s %2$s</p><p>%3$s · %4$s: %5$s · %6$s: %7$s</p>',
		cacdemo_palette_swatch( cacdemo_palette( $current['palette'] ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
		esc_html( $active['name'] ),
		esc_html( $source[ $current['source'] ] ?? '' ),
		esc_html__( 'Colours', 'cacdemo' ),
		esc_html( cacdemo_palette( $current['palette'] )['name'] ),
		esc_html__( 'Effects', 'cacdemo' ),
		esc_html( cacdemo_site_theme_effects_label( $current['effects'] ) )
	);
	echo '</div>';

	// Everyday theme.
	printf( '<form method="post" action="%s" class="cacdemo-card">', esc_url( admin_url( 'admin-post.php' ) ) );
	wp_nonce_field( 'cacdemo_site_theme' );
	echo '<input type="hidden" name="action" value="cacdemo_site_theme"><input type="hidden" name="do" value="everyday">';
	printf( '<h2>%s</h2><p class="description">%s</p>', esc_html__( 'Everyday theme', 'cacdemo' ), esc_html__( 'Used whenever no scheduled theme is running.', 'cacdemo' ) );
	echo '<fieldset class="cacdemo-themes"><legend class="screen-reader-text">' . esc_html__( 'Everyday theme', 'cacdemo' ) . '</legend>';
	foreach ( $packages as $id => $package ) {
		if ( ! in_array( $package['type'], array( 'base', 'natural-season' ), true ) ) {
			continue; // Occasions are scheduled, not everyday.
		}
		printf(
			'<label class="cacdemo-theme"><input type="radio" name="everyday" value="%1$s" %2$s><span class="cacdemo-theme__body"><strong>%3$s %4$s</strong><span>%5$s</span><span class="cacdemo-theme__meta">%6$s · %7$s: %8$s%9$s</span><a href="%10$s" target="_blank" rel="noopener">%11$s</a></span></label>',
			esc_attr( $id ),
			checked( $state['everyday'], $id, false ),
			cacdemo_palette_swatch( cacdemo_palette( $package['palette'] ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $package['name'] ),
			esc_html( $package['description'] ),
			esc_html( cacdemo_site_theme_type_label( $package['type'] ) ),
			esc_html__( 'Colours', 'cacdemo' ),
			esc_html( cacdemo_palette( $package['palette'] )['name'] ),
			$package['effects'] ? esc_html( ' · ' . __( 'Effects available', 'cacdemo' ) ) : '',
			esc_url( cacdemo_site_theme_preview_url( array( 'site_theme_preview' => $id ) ) ),
			esc_html__( 'Preview', 'cacdemo' )
		);
	}
	echo '</fieldset>';
	if ( $effects_themes ) {
		printf( '<p><label for="cacdemo-everyday-effects">%s</label> <select id="cacdemo-everyday-effects" name="effects">', esc_html__( 'Effects for themes that have them', 'cacdemo' ) );
		foreach ( CACDEMO_EFFECT_INTENSITIES as $intensity ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $intensity ), selected( $state['effects'], $intensity, false ), esc_html( cacdemo_site_theme_effects_label( $intensity ) ) );
		}
		printf( '</select> <span class="description">%s</span></p>', esc_html__( 'Visitors who ask their device to reduce motion never see effects.', 'cacdemo' ) );
	}
	submit_button( __( 'Save everyday theme', 'cacdemo' ) );
	echo '</form>';

	// Schedule.
	echo '<div class="cacdemo-card">';
	printf( '<h2>%s</h2><p class="description">%s</p>', esc_html__( 'Scheduled themes', 'cacdemo' ), esc_html__( 'A scheduled theme runs from its start until its end, then the site returns to the everyday theme by itself. When two overlap, the one that started later is shown.', 'cacdemo' ) );
	if ( $state['schedule'] ) {
		printf( '<table class="widefat striped"><thead><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th></th></tr></thead><tbody>', esc_html__( 'Theme', 'cacdemo' ), esc_html__( 'Starts', 'cacdemo' ), esc_html__( 'Ends', 'cacdemo' ), esc_html__( 'Effects', 'cacdemo' ), esc_html__( 'Status', 'cacdemo' ) );
		foreach ( $state['schedule'] as $entry ) {
			$package = $packages[ $entry['theme'] ] ?? null;
			$starts  = cacdemo_site_theme_parse_time( $entry['starts'] );
			$ends    = cacdemo_site_theme_parse_time( $entry['ends'] );
			$status  = ! $package ? __( 'Theme not installed — ignored', 'cacdemo' ) : ( $now >= $ends ? __( 'Ended', 'cacdemo' ) : ( $now >= $starts ? ( $current['entry'] && $current['entry']['id'] === $entry['id'] ? __( 'Showing now', 'cacdemo' ) : __( 'Running (another scheduled theme is shown)', 'cacdemo' ) ) : __( 'Upcoming', 'cacdemo' ) ) );
			printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>', esc_html( $package['name'] ?? $entry['theme'] ), esc_html( cacdemo_site_theme_format_time( $entry['starts'] ) ), esc_html( cacdemo_site_theme_format_time( $entry['ends'] ) ), esc_html( $package && $package['effects'] ? cacdemo_site_theme_effects_label( $entry['effects'] ) : '—' ), esc_html( $status ) );
			printf( '<form method="post" action="%s">', esc_url( admin_url( 'admin-post.php' ) ) );
			wp_nonce_field( 'cacdemo_site_theme' );
			printf( '<input type="hidden" name="action" value="cacdemo_site_theme"><input type="hidden" name="do" value="unschedule"><input type="hidden" name="entry" value="%s"><button type="submit" class="button-link button-link-delete">%s</button></form></td></tr>', esc_attr( $entry['id'] ), esc_html__( 'Remove', 'cacdemo' ) );
		}
		echo '</tbody></table>';
	} else {
		printf( '<p>%s</p>', esc_html__( 'Nothing is scheduled.', 'cacdemo' ) );
	}
	printf( '<form method="post" action="%s" class="cacdemo-schedule-form">', esc_url( admin_url( 'admin-post.php' ) ) );
	wp_nonce_field( 'cacdemo_site_theme' );
	echo '<input type="hidden" name="action" value="cacdemo_site_theme"><input type="hidden" name="do" value="schedule">';
	printf( '<h3>%s</h3><p class="cacdemo-fields">', esc_html__( 'Schedule a theme', 'cacdemo' ) );
	printf( '<label>%s <select name="theme" required>', esc_html__( 'Theme', 'cacdemo' ) );
	foreach ( $packages as $id => $package ) {
		printf( '<option value="%s">%s (%s)</option>', esc_attr( $id ), esc_html( $package['name'] ), esc_html( cacdemo_site_theme_type_label( $package['type'] ) ) );
	}
	echo '</select></label>';
	printf( '<label>%s <input type="datetime-local" name="starts" required></label>', esc_html__( 'Starts', 'cacdemo' ) );
	printf( '<label>%s <input type="datetime-local" name="ends" required></label>', esc_html__( 'Ends', 'cacdemo' ) );
	printf( '<label>%s <select name="effects">', esc_html__( 'Effects', 'cacdemo' ) );
	foreach ( CACDEMO_EFFECT_INTENSITIES as $intensity ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $intensity ), selected( 'subtle', $intensity, false ), esc_html( cacdemo_site_theme_effects_label( $intensity ) ) );
	}
	/* translators: %s: timezone name */
	printf( '</select></label></p><p class="description">%s</p>', esc_html( sprintf( __( 'Times are in the site timezone (%s).', 'cacdemo' ), wp_timezone_string() ) ) );
	submit_button( __( 'Schedule theme', 'cacdemo' ), 'secondary' );
	echo '</form></div>';

	// Images.
	$labels = cacdemo_site_theme_slot_labels();
	echo '<div class="cacdemo-card">';
	printf( '<h2>%s</h2><p class="description">%s</p>', esc_html__( 'Theme images', 'cacdemo' ), esc_html__( 'Images shown when a page, ministry or update has none of its own. Content images always come first. A theme without an image for a slot uses the Default theme\'s.', 'cacdemo' ) );
	foreach ( $packages as $id => $package ) {
		if ( ! $package['slots'] ) {
			continue;
		}
		printf( '<form method="post" action="%s" class="cacdemo-images">', esc_url( admin_url( 'admin-post.php' ) ) );
		wp_nonce_field( 'cacdemo_site_theme' );
		printf( '<input type="hidden" name="action" value="cacdemo_site_theme"><input type="hidden" name="do" value="images"><input type="hidden" name="theme" value="%s">', esc_attr( $id ) );
		printf( '<h3>%s</h3><ul class="cacdemo-slots">', esc_html( $package['name'] ) );
		foreach ( $package['slots'] as $slot ) {
			$attachment = (int) ( $state['images'][ $id ][ $slot ] ?? 0 );
			$attachment = $attachment && wp_attachment_is_image( $attachment ) ? $attachment : 0;
			$label      = $labels[ $slot ] ?? array( $slot, '' );
			printf(
				'<li class="cacdemo-slot" data-slot><div class="cacdemo-slot__preview" data-slot-preview>%1$s</div><div class="cacdemo-slot__body"><strong id="cacdemo-slot-%2$s-%3$s">%4$s</strong><span class="description">%5$s</span><span class="cacdemo-slot__name" data-slot-name>%6$s</span><input type="hidden" name="images[%3$s]" value="%7$s" data-slot-input><span class="cacdemo-slot__actions"><button type="button" class="button" data-slot-choose aria-describedby="cacdemo-slot-%2$s-%3$s">%8$s</button> <button type="button" class="button-link button-link-delete" data-slot-clear %9$s aria-describedby="cacdemo-slot-%2$s-%3$s">%10$s</button></span></div></li>',
				$attachment ? wp_get_attachment_image( $attachment, 'medium', false, array( 'alt' => '' ) ) : '<span>' . esc_html__( 'No image', 'cacdemo' ) . '</span>',
				esc_attr( $id ),
				esc_attr( $slot ),
				esc_html( $label[0] ),
				esc_html( $label[1] ),
				$attachment ? esc_html( get_the_title( $attachment ) ) : '',
				esc_attr( (string) $attachment ),
				esc_html__( 'Choose image', 'cacdemo' ),
				$attachment ? '' : 'hidden',
				esc_html__( 'Remove', 'cacdemo' )
			);
		}
		echo '</ul>';
		/* translators: %s: site theme name */
		submit_button( sprintf( __( 'Save %s images', 'cacdemo' ), $package['name'] ), 'secondary' );
		echo '</form>';
	}
	echo '</div>';

	do_action( 'cacdemo_site_theme_screen_sections', $state, $packages );

	// Visitor colours.
	echo '<div class="cacdemo-card">';
	printf( '<h2>%s</h2><p class="description">%s</p>', esc_html__( 'Visitor colours', 'cacdemo' ), esc_html__( 'Visitors choose one of these in the footer or the phone menu; the choice stays in their browser. "Site default" follows the theme shown. A palette is offered only when it passes the contrast checks.', 'cacdemo' ) );
	echo '<ul class="cacdemo-palettes">';
	foreach ( cacdemo_palettes() as $id => $palette ) {
		$passes = cacdemo_palette_passes( $palette );
		printf(
			'<li>%1$s <strong>%2$s</strong> <span class="description">%3$s</span> <span class="cacdemo-pass %4$s">%5$s</span> <a href="%6$s" target="_blank" rel="noopener">%7$s</a></li>',
			cacdemo_palette_swatch( $palette ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $palette['name'] ),
			esc_html( $palette['description'] ),
			$passes ? 'is-pass' : 'is-fail',
			$passes ? esc_html__( 'Passes contrast checks', 'cacdemo' ) : esc_html__( 'Fails contrast checks — not offered', 'cacdemo' ),
			esc_url( cacdemo_site_theme_preview_url( array( 'palette_preview' => $id ) ) ),
			esc_html__( 'Preview', 'cacdemo' )
		);
	}
	echo '</ul></div></div>';
}
