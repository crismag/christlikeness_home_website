<?php
/**
 * Images for public pages: which image a hero or card shows, and how uploads are prepared.
 * Design: docs/VISUAL-THEMES-DESIGN.md §8 and DESIGN-SYSTEM.md → Imagery.
 *
 * Resolution (cacdemo_resolve_image()):
 *   hero : content banner → content featured image (ministries) → parent context → site theme slot → Default theme slot
 *   card : featured image → parent context (an update's or a way to serve's ministry) → site theme "fallback" slot
 * Site-theme slots come through the filter `cacdemo_theme_image` (the theme answers it); content always wins.
 * Only image attachments are returned; a missing or deleted image simply moves to the next source.
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_IMAGE_POSITIONS = array( 'center' => '50% 50%', 'top' => '50% 15%', 'bottom' => '50% 85%', 'left' => '15% 50%', 'right' => '85% 50%' );

function cacdemo_is_image_attachment( $id ) {
	return $id && 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id );
}

/**
 * Resolves the image for a slot.
 *
 * @param string       $slot       'hero' or 'card'.
 * @param WP_Post|null $post       The content being shown (null for archives).
 * @param array        $args       theme_slot: which theme slot to fall back to ('' = no theme fallback);
 *                                 content_only: ignore theme images.
 * @return array{id:int,source:string,position:string,position_mobile:string}|null
 */
function cacdemo_resolve_image( $slot, $post = null, $args = array() ) {
	$args     = wp_parse_args( $args, array( 'theme_slot' => 'hero' === $slot ? 'page-hero' : 'fallback' ) );
	$post     = $post ? get_post( $post ) : null;
	$position = 'center';
	$mobile   = '';
	$found    = null;

	if ( $post ) {
		$banner = (int) get_post_meta( $post->ID, 'banner_image', true );
		if ( 'hero' === $slot && cacdemo_is_image_attachment( $banner ) ) {
			$found    = array( $banner, 'banner' );
			$position = (string) get_post_meta( $post->ID, 'banner_position', true ) ?: 'center';
			$mobile   = (string) get_post_meta( $post->ID, 'banner_position_mobile', true );
		}
		$featured = (int) get_post_thumbnail_id( $post );
		if ( ! $found && ( 'card' === $slot || 'ministry' === $post->post_type ) && cacdemo_is_image_attachment( $featured ) ) {
			$found = array( $featured, 'featured' );
		}
		if ( ! $found ) {
			$parent = cacdemo_image_parent_context( $post );
			if ( $parent && cacdemo_is_image_attachment( (int) get_post_thumbnail_id( $parent ) ) ) {
				$found = array( (int) get_post_thumbnail_id( $parent ), 'parent' );
			}
		}
	}
	if ( ! $found && $args['theme_slot'] ) {
		$theme = (int) apply_filters( 'cacdemo_theme_image', 0, $args['theme_slot'], $post );
		if ( cacdemo_is_image_attachment( $theme ) ) {
			$found = array( $theme, 'theme' );
		}
	}
	if ( ! $found ) {
		return null;
	}
	return array(
		'id'              => $found[0],
		'source'          => $found[1],
		'position'        => CACDEMO_IMAGE_POSITIONS[ $position ] ?? CACDEMO_IMAGE_POSITIONS['center'],
		'position_mobile' => CACDEMO_IMAGE_POSITIONS[ $mobile ] ?? '',
	);
}

/** The ministry an update or a way to serve belongs to. */
function cacdemo_image_parent_context( $post ) {
	$ministry = 0;
	if ( 'post' === $post->post_type ) {
		$ministry = (int) get_post_meta( $post->ID, 'update_ministry', true );
	} elseif ( 'serve_role' === $post->post_type ) {
		$ministry = (int) get_post_meta( $post->ID, 'role_ministry', true );
	}
	return $ministry && 'publish' === get_post_status( $ministry ) ? get_post( $ministry ) : null;
}

/* ---------------------------------------------------------------- Cards: featured image fallbacks */

add_filter( 'render_block_core/post-featured-image', 'cacdemo_card_image_fallback', 10, 3 );

/**
 * A Featured Image block that would render nothing on a ministry or update card shows the resolved fallback instead,
 * in the block's own wrapper, so layout and aspect ratio stay the same. Other content types are left alone.
 */
function cacdemo_card_image_fallback( $content, $block, $instance ) {
	if ( '' !== trim( $content ) || is_admin() ) {
		return $content;
	}
	$post_id = (int) ( $instance->context['postId'] ?? 0 );
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || ! in_array( $post->post_type, array( 'ministry', 'post', 'serve_role' ), true ) ) {
		return $content;
	}
	$image = cacdemo_resolve_image( 'card', $post );
	if ( ! $image ) {
		return $content;
	}
	$attrs  = $block['attrs'] ?? array();
	$ratio  = ! empty( $attrs['aspectRatio'] ) ? 'aspect-ratio:' . esc_attr( $attrs['aspectRatio'] ) . ';' : '';
	$img    = wp_get_attachment_image( $image['id'], 'large', false, array(
		'class'   => 'cacdemo-fallback-image',
		'style'   => 'width:100%;height:100%;object-fit:cover;object-position:' . esc_attr( $image['position'] ),
		'sizes'   => '(min-width: 48rem) 36rem, 100vw',
		'loading' => 'lazy',
		'alt'     => '',
	) );
	$inner  = ! empty( $attrs['isLink'] ) ? sprintf( '<a href="%s" tabindex="-1" aria-hidden="true">%s</a>', esc_url( get_permalink( $post ) ), $img ) : $img;
	$wrapper = get_block_wrapper_attributes( array(
		'class' => trim( ( $attrs['className'] ?? '' ) . ' is-fallback-image' ),
		'style' => $ratio,
	) );
	return sprintf( '<figure %s>%s</figure>', $wrapper, $inner );
}

/* ---------------------------------------------------------------- Uploads */

add_filter( 'image_editor_output_format', 'cacdemo_webp_subsizes' );

/** Generated sizes of JPEG and PNG uploads are saved as WebP (smaller); originals are kept as uploaded. */
function cacdemo_webp_subsizes( $formats ) {
	if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return $formats;
	}
	$formats['image/jpeg'] = 'image/webp';
	$formats['image/png']  = 'image/webp';
	return $formats;
}

/* ---------------------------------------------------------------- Media collections */

/** Collections every site starts with (created by scripts/seed-appearance.php; Content Admins add more). */
function cacdemo_media_collections() {
	return array(
		'general'              => 'General',
		'placeholder'          => 'Placeholder',
		'church'               => 'Church',
		'worship'              => 'Worship',
		'community'            => 'Community',
		'ministries'           => 'Ministries',
		'events'               => 'Events',
		'facilities'           => 'Facilities',
		'historical'           => 'Historical',
		'spring'               => 'Spring',
		'summer'               => 'Summer',
		'fall'                 => 'Fall',
		'winter'               => 'Winter',
		'anniversary'          => 'Anniversary',
		'biblical-observances' => 'Biblical observances',
		'backgrounds'          => 'Backgrounds',
		'textures'             => 'Textures',
	);
}

/** Puts an image in a collection (by slug), creating the collection if needed. */
function cacdemo_add_to_collection( $attachment_id, $slug ) {
	$names = cacdemo_media_collections();
	if ( ! term_exists( $slug, 'media_collection' ) ) {
		wp_insert_term( $names[ $slug ] ?? ucfirst( $slug ), 'media_collection', array( 'slug' => $slug ) );
	}
	return ! is_wp_error( wp_add_object_terms( $attachment_id, $slug, 'media_collection' ) );
}

add_action( 'add_attachment', 'cacdemo_media_default_collection' );

/**
 * New images start in the General collection (Content Admins file them further). Anything that already has a collection,
 * or code that opts out through the `cacdemo_default_collection` filter (placeholder generation), is left alone.
 */
function cacdemo_media_default_collection( $attachment_id ) {
	$slug = (string) apply_filters( 'cacdemo_default_collection', 'general', $attachment_id );
	if ( '' === $slug || ! taxonomy_exists( 'media_collection' ) || ! wp_attachment_is_image( $attachment_id ) || wp_get_object_terms( $attachment_id, 'media_collection', array( 'fields' => 'ids' ) ) ) {
		return;
	}
	cacdemo_add_to_collection( $attachment_id, $slug );
}

/** An image someone may pick from the Media Library for content: an image attachment, and they can upload files. */
function cacdemo_can_use_library_image( $attachment_id ) {
	return current_user_can( 'upload_files' ) && cacdemo_is_image_attachment( $attachment_id ) && current_user_can( 'read_post', $attachment_id );
}

add_action( 'restrict_manage_posts', 'cacdemo_media_collection_filter' );

/** Media Library (list view): filter by collection. */
function cacdemo_media_collection_filter( $post_type ) {
	if ( 'attachment' !== $post_type || ! taxonomy_exists( 'media_collection' ) ) {
		return;
	}
	wp_dropdown_categories( array(
		'taxonomy'        => 'media_collection',
		'name'            => 'media_collection',
		'value_field'     => 'slug',
		'show_option_all' => __( 'All collections', 'cacdemo' ),
		'selected'        => sanitize_key( wp_unslash( $_GET['media_collection'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- list filter.
		'hide_empty'      => false,
	) );
}
