<?php
/**
 * Sermon browsing: the visitor's filters, sort, grouping and view, read from the URL, applied to every
 * sermon collection view (Grid and List Query Loops, the Table block, series/speaker/topic archives).
 *
 * URL parameters (all optional, all sanitised):
 *   q         keywords (title and content)          series    series slug
 *   speaker   speaker slug                          topic     topic slug
 *   location  centre slug                           yr        four-digit year
 *   sort      new (default) | old | title           group     year (default) | series | speaker | topic  (Table)
 *   view      grid (default) | list | table         spage     Table page number
 * "q" and "yr" are used instead of "s" and "year", which WordPress reserves (a Page URL with them turns into a
 * search or a date archive).
 */

defined( 'ABSPATH' ) || exit;

const CACDEMO_SERMON_TABLE_PER_PAGE = 100;

/** Filters, sort, grouping and view from the URL (sanitised). No queried-object context. */
function cacdemo_sermon_request_raw() {
	// phpcs:disable WordPress.Security.NonceVerification -- read-only public filters.
	$get  = wp_unslash( $_GET );
	$pick = fn( $key, $allowed, $default ) => in_array( $get[ $key ] ?? '', $allowed, true ) ? $get[ $key ] : $default;
	$slug = fn( $key ) => isset( $get[ $key ] ) ? sanitize_title( (string) $get[ $key ] ) : '';
	// phpcs:enable
	return array(
		'q'        => isset( $get['q'] ) ? trim( sanitize_text_field( (string) $get['q'] ) ) : '',
		'series'   => $slug( 'series' ),
		'speaker'  => $slug( 'speaker' ),
		'topic'    => $slug( 'topic' ),
		'location' => $slug( 'location' ),
		'year'     => isset( $get['yr'] ) && preg_match( '/^\d{4}$/', (string) $get['yr'] ) ? (int) $get['yr'] : 0,
		'sort'     => $pick( 'sort', array( 'new', 'old', 'title' ), 'new' ),
		'group'    => $pick( 'group', array( 'year', 'series', 'speaker', 'topic' ), 'year' ),
		'view'     => $pick( 'view', array( 'grid', 'list', 'table' ), 'grid' ),
		'spage'    => max( 1, absint( $get['spage'] ?? 1 ) ),
		'archive'  => null,
	);
}

/** The current browse state. On a series/speaker/topic archive, that term is fixed. Use after the main query. */
function cacdemo_sermon_request() {
	static $request = null;
	if ( null === $request ) {
		$request = cacdemo_sermon_request_raw();
		$map     = array( 'sermon_series' => 'series', 'sermon_speaker' => 'speaker', 'sermon_topic' => 'topic' );
		if ( is_tax( array_keys( $map ) ) ) {
			$term                               = get_queried_object();
			$request[ $map[ $term->taxonomy ] ] = $term->slug;
			$request['archive']                 = $map[ $term->taxonomy ];
		}
	}
	return $request;
}

/** True when any filter narrows the collection (sort and view do not count). */
function cacdemo_sermon_is_filtered( $request ) {
	foreach ( array( 'q', 'series', 'speaker', 'topic', 'location', 'year' ) as $key ) {
		if ( ! empty( $request[ $key ] ) && $key !== $request['archive'] ) {
			return true;
		}
	}
	return false;
}

/** WP_Query arguments for the current filters and sort (no paging). */
function cacdemo_sermon_query_args( $request ) {
	$args = array(
		'post_type'   => 'sermon',
		'post_status' => 'publish',
		'orderby'     => 'title' === $request['sort'] ? 'title' : 'date',
		'order'       => 'new' === $request['sort'] ? 'DESC' : 'ASC',
	);
	if ( '' !== $request['q'] ) {
		$args['s'] = $request['q'];
	}
	$tax = array();
	foreach ( array( 'series' => 'sermon_series', 'speaker' => 'sermon_speaker', 'topic' => 'sermon_topic' ) as $key => $taxonomy ) {
		if ( '' !== $request[ $key ] ) {
			$tax[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $request[ $key ] );
		}
	}
	if ( $tax ) {
		$args['tax_query'] = $tax;
	}
	if ( '' !== $request['location'] ) {
		$centre             = get_page_by_path( $request['location'], OBJECT, 'centre' );
		$args['meta_query'] = array( array( 'key' => 'sermon_location', 'value' => $centre ? (string) $centre->ID : '0' ) );
	}
	if ( $request['year'] ) {
		$args['date_query'] = array( array( 'year' => $request['year'] ) );
	}
	return $args;
}

/**
 * Grid and List: a sermon Query Loop with "cacdemoFilters": true follows the visitor's filters and sort.
 * In the Table view it returns nothing (the Table block lists the sermons instead).
 */
add_filter( 'query_loop_block_query_vars', 'cacdemo_sermon_filter_query_loop', 10, 2 );

function cacdemo_sermon_filter_query_loop( $query, $block ) {
	if ( empty( $block->context['query']['cacdemoFilters'] ) ) {
		return $query;
	}
	$request = cacdemo_sermon_request();
	if ( 'table' === $request['view'] ) {
		return array_merge( $query, array( 'post__in' => array( 0 ) ) );
	}
	$args = cacdemo_sermon_query_args( $request );
	unset( $args['post_status'] );
	return array_merge( $query, $args );
}

/**
 * Featured sermons: a Query Loop with "cacdemoFeatured": true lists sermons an admin marked Featured, newest
 * first. It lists nothing once a visitor filters, pages or switches to the Table (the section then hides).
 */
add_filter( 'query_loop_block_query_vars', 'cacdemo_sermon_featured_query', 10, 2 );

function cacdemo_sermon_featured_query( $query, $block ) {
	if ( empty( $block->context['query']['cacdemoFeatured'] ) ) {
		return $query;
	}
	$request = cacdemo_sermon_request();
	// phpcs:ignore WordPress.Security.NonceVerification -- read-only paging parameter of the main collection.
	$paged = absint( $_GET['query-30-page'] ?? 1 ) > 1;
	if ( cacdemo_sermon_is_filtered( $request ) || 'table' === $request['view'] || $paged ) {
		return array_merge( $query, array( 'post__in' => array( 0 ) ) );
	}
	return array_merge( $query, array(
		'post_type'  => 'sermon',
		'orderby'    => 'date',
		'order'      => 'DESC',
		'meta_query' => array( array( 'key' => 'sermon_featured', 'value' => '1' ) ),
	) );
}

/** Series, speaker and topic archives (inherited main query): the same filters and sort. */
add_action( 'pre_get_posts', 'cacdemo_sermon_filter_archive' );

function cacdemo_sermon_filter_archive( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_tax( array( 'sermon_series', 'sermon_speaker', 'sermon_topic' ) ) ) {
		return;
	}
	$request = cacdemo_sermon_request_from_query( $query );
	$args    = cacdemo_sermon_query_args( $request );
	foreach ( array( 's', 'orderby', 'order', 'meta_query', 'date_query' ) as $key ) {
		if ( isset( $args[ $key ] ) ) {
			$query->set( $key, $args[ $key ] );
		}
	}
	if ( isset( $args['tax_query'] ) ) {
		$query->set( 'tax_query', $args['tax_query'] );
	}
}

/** Like cacdemo_sermon_request(), before the queried object exists (pre_get_posts). */
function cacdemo_sermon_request_from_query( $query ) {
	$request = cacdemo_sermon_request_raw();
	foreach ( array( 'sermon_series' => 'series', 'sermon_speaker' => 'speaker', 'sermon_topic' => 'topic' ) as $taxonomy => $key ) {
		$value = $query->get( $taxonomy );
		if ( $value ) {
			$request[ $key ] = sanitize_title( $value );
		}
	}
	return $request;
}

/** Server-side view class, so the view is right before JavaScript runs (and without it). */
add_filter( 'body_class', 'cacdemo_sermon_view_body_class' );

function cacdemo_sermon_view_body_class( $classes ) {
	if ( is_page( 'sermons' ) || is_tax( array( 'sermon_series', 'sermon_speaker', 'sermon_topic' ) ) ) {
		$view = cacdemo_sermon_request()['view'];
		if ( 'grid' !== $view ) {
			$classes[] = "cacdemo-view-$view";
		}
	}
	return $classes;
}

/** Current collection URL with the given parameters changed (null removes one); spage resets unless given. */
function cacdemo_sermon_url( $changes = array() ) {
	$request = cacdemo_sermon_request();
	$params  = array();
	foreach ( array( 'q', 'series', 'speaker', 'topic', 'location', 'year', 'sort', 'group', 'view' ) as $key ) {
		if ( $key === $request['archive'] ) {
			continue;
		}
		$default = array( 'sort' => 'new', 'group' => 'year', 'view' => 'grid' )[ $key ] ?? '';
		if ( ! empty( $request[ $key ] ) && (string) $request[ $key ] !== $default ) {
			$params[ 'year' === $key ? 'yr' : $key ] = $request[ $key ];
		}
	}
	foreach ( $changes as $key => $value ) {
		if ( null === $value || '' === $value ) {
			unset( $params[ $key ] );
		} else {
			$params[ $key ] = $value;
		}
	}
	return add_query_arg( array_map( 'rawurlencode', $params ), cacdemo_sermon_base_url() );
}

function cacdemo_sermon_base_url() {
	if ( is_tax() ) {
		return get_term_link( get_queried_object() );
	}
	return get_permalink( get_queried_object_id() ) ?: home_url( '/sermons/' );
}
