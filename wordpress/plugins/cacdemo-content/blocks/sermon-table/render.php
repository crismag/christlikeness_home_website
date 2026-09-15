<?php
/**
 * Sermon table: the filtered collection as compact rows (Title, Date, Speaker, Scripture / notes),
 * grouped under headings by year, series, speaker or topic, 100 rows per page. Renders only in the
 * Table view. Column headers link to the matching sort. Columns with no values on the page are left out.
 *
 * @var array    $attributes
 * @var WP_Block $block
 */

$request = cacdemo_sermon_request();
if ( 'table' !== $request['view'] ) {
	return;
}

$ids = get_posts( array_merge( cacdemo_sermon_query_args( $request ), array( 'numberposts' => -1, 'fields' => 'ids', 'no_found_rows' => true ) ) );
$total = count( $ids );

// Group key and label for each sermon.
$group_of = function ( $id ) use ( $request ) {
	if ( 'year' === $request['group'] ) {
		$year = get_the_date( 'Y', $id );
		return array( $year, $year );
	}
	$taxonomy = array( 'series' => 'sermon_series', 'speaker' => 'sermon_speaker', 'topic' => 'sermon_topic' )[ $request['group'] ];
	$names    = wp_get_post_terms( $id, $taxonomy, array( 'fields' => 'names' ) );
	if ( is_wp_error( $names ) || ! $names ) {
		$none = array(
			'series'  => __( 'Not part of a series', 'cacdemo' ),
			'speaker' => __( 'Speaker not recorded', 'cacdemo' ),
			'topic'   => __( 'No topic assigned', 'cacdemo' ),
		)[ $request['group'] ];
		return array( "\xFF", $none ); // Sorts after every name.
	}
	return array( strtolower( $names[0] ), $names[0] );
};

// Keep the chosen sort inside each group; order groups by year (following the sort) or by name.
$rows = array();
foreach ( $ids as $position => $id ) {
	list( $key, $label ) = $group_of( $id );
	$rows[] = array( 'id' => $id, 'key' => $key, 'label' => $label, 'position' => $position );
}
usort( $rows, function ( $a, $b ) use ( $request ) {
	if ( $a['key'] !== $b['key'] ) {
		if ( 'year' === $request['group'] ) {
			return 'old' === $request['sort'] ? strcmp( $a['key'], $b['key'] ) : strcmp( $b['key'], $a['key'] );
		}
		return strcmp( $a['key'], $b['key'] );
	}
	return $a['position'] <=> $b['position'];
} );

$pages = max( 1, (int) ceil( $total / CACDEMO_SERMON_TABLE_PER_PAGE ) );
$page  = min( $request['spage'], $pages );
$slice = array_slice( $rows, ( $page - 1 ) * CACDEMO_SERMON_TABLE_PER_PAGE, CACDEMO_SERMON_TABLE_PER_PAGE );

// Cell values; drop columns with nothing to show on this page.
$cells = array();
foreach ( $slice as $row ) {
	$id       = $row['id'];
	$speakers = wp_get_post_terms( $id, 'sermon_speaker', array( 'fields' => 'names' ) );
	$note     = function_exists( 'get_field' ) ? (string) get_field( 'sermon_scripture', $id ) : '';
	if ( '' === $note && has_excerpt( $id ) ) {
		$note = wp_trim_words( get_the_excerpt( $id ), 14 );
	}
	$cells[ $id ] = array(
		'speaker' => is_wp_error( $speakers ) ? '' : implode( ', ', $speakers ),
		'note'    => $note,
	);
}
$has_speaker = (bool) array_filter( wp_list_pluck( $cells, 'speaker' ) );
$has_note    = (bool) array_filter( wp_list_pluck( $cells, 'note' ) );

$sort_link = function ( $label, $sort_asc, $sort_desc = null ) use ( $request ) {
	$current = in_array( $request['sort'], array_filter( array( $sort_asc, $sort_desc ) ), true );
	$next    = $sort_desc && $request['sort'] === $sort_desc ? $sort_asc : ( $sort_desc ?: $sort_asc );
	$aria    = $current ? sprintf( ' aria-sort="%s"', 'old' === $request['sort'] || 'title' === $request['sort'] ? 'ascending' : 'descending' ) : '';
	return sprintf( '<th scope="col"%s><a href="%s">%s</a></th>', $aria, esc_url( cacdemo_sermon_url( array( 'sort' => 'new' === $next ? null : $next ) ) ), esc_html( $label ) );
};

$head  = '<tr>' . $sort_link( __( 'Title', 'cacdemo' ), 'title' ) . $sort_link( __( 'Date', 'cacdemo' ), 'old', 'new' );
$head .= $has_speaker ? '<th scope="col">' . esc_html__( 'Speaker', 'cacdemo' ) . '</th>' : '';
$head .= $has_note ? '<th scope="col">' . esc_html__( 'Scripture / notes', 'cacdemo' ) . '</th>' : '';
$head .= '</tr>';

$groups  = '';
$current = null;
$body    = '';
$count   = array_count_values( array_column( $rows, 'key' ) );
$flush   = function () use ( &$groups, &$body, &$current, $head, $count ) {
	if ( null === $current ) {
		return;
	}
	$groups .= sprintf(
		'<section class="cacdemo-sermon-table__group"><h2 class="cacdemo-sermon-table__heading">%s <span>%s</span></h2><div class="cacdemo-sermon-table__scroll"><table><thead>%s</thead><tbody>%s</tbody></table></div></section>',
		esc_html( $current['label'] ),
		/* translators: %d: number of sermons in the group */
		esc_html( sprintf( _n( '%d sermon', '%d sermons', $count[ $current['key'] ], 'cacdemo' ), $count[ $current['key'] ] ) ),
		$head,
		$body
	);
	$body = '';
};
foreach ( $slice as $row ) {
	if ( null === $current || $current['key'] !== $row['key'] ) {
		$flush();
		$current = $row;
	}
	$id    = $row['id'];
	$body .= '<tr>';
	$body .= sprintf( '<th scope="row"><a href="%s">%s</a></th>', esc_url( get_permalink( $id ) ), esc_html( get_the_title( $id ) ) );
	$body .= sprintf( '<td class="cacdemo-sermon-table__date"><time datetime="%s">%s</time></td>', esc_attr( get_the_date( 'Y-m-d', $id ) ), esc_html( get_the_date( 'M j, Y', $id ) ) );
	$body .= $has_speaker ? '<td>' . esc_html( $cells[ $id ]['speaker'] ) . '</td>' : '';
	$body .= $has_note ? '<td>' . esc_html( $cells[ $id ]['note'] ) . '</td>' : '';
	$body .= '</tr>';
}
$flush();

$summary = $total
	/* translators: 1: first row number, 2: last row number, 3: total sermons */
	? sprintf( __( 'Showing %1$d–%2$d of %3$d sermons', 'cacdemo' ), ( $page - 1 ) * CACDEMO_SERMON_TABLE_PER_PAGE + 1, ( $page - 1 ) * CACDEMO_SERMON_TABLE_PER_PAGE + count( $slice ), $total )
	: __( 'No sermons match these filters.', 'cacdemo' );

$nav = '';
if ( $pages > 1 ) {
	$nav = sprintf(
		'<nav class="cacdemo-sermon-table__pages" aria-label="%s">%s<span>%s</span>%s</nav>',
		esc_attr__( 'Table pages', 'cacdemo' ),
		$page > 1 ? sprintf( '<a href="%s">%s</a>', esc_url( cacdemo_sermon_url( array( 'spage' => $page - 1 > 1 ? $page - 1 : null ) ) ), esc_html__( 'Previous', 'cacdemo' ) ) : '<span></span>',
		/* translators: 1: current page, 2: number of pages */
		esc_html( sprintf( __( 'Page %1$d of %2$d', 'cacdemo' ), $page, $pages ) ),
		$page < $pages ? sprintf( '<a href="%s">%s</a>', esc_url( cacdemo_sermon_url( array( 'spage' => $page + 1 ) ) ), esc_html__( 'Next', 'cacdemo' ) ) : '<span></span>'
	);
}

printf(
	'<div %s><p class="cacdemo-sermon-table__summary" role="status">%s</p>%s%s</div>',
	get_block_wrapper_attributes( array( 'class' => 'cacdemo-sermon-table' ) ),
	esc_html( $summary ),
	$groups, // phpcs:ignore WordPress.Security.EscapeOutput -- escaped when built.
	$nav // phpcs:ignore WordPress.Security.EscapeOutput
);
