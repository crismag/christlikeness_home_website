<?php
/**
 * Sermon filters: a plain GET form (works without JavaScript) and the Grid / List / Table switch.
 * Every choice lives in the page address, so a filtered view can be shared or bookmarked.
 *
 * @var array    $attributes
 * @var WP_Block $block
 */

$request = cacdemo_sermon_request();

$terms = function ( $taxonomy ) {
	$found = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true, 'orderby' => 'name' ) );
	return is_wp_error( $found ) ? array() : $found;
};

global $wpdb;
$years = $wpdb->get_col( "SELECT DISTINCT YEAR(post_date) FROM $wpdb->posts WHERE post_type = 'sermon' AND post_status = 'publish' ORDER BY 1 DESC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

$centres = array();
foreach ( get_posts( array( 'post_type' => 'centre', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $centre ) {
	$centres[ $centre->post_name ] = $centre->post_title;
}

// Compact controls: the first option names the filter ("All series"), the label is for screen readers.
$select = function ( $name, $label, $options, $current, $any ) {
	if ( ! $options ) {
		return '';
	}
	$html = '';
	foreach ( $options as $value => $text ) {
		$html .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( (string) $current, (string) $value, false ), esc_html( $text ) );
	}
	$id = 'cacdemo-sermon-' . $name;
	return sprintf(
		'<div class="cacdemo-filter%6$s"><label class="screen-reader-text" for="%1$s">%2$s</label><select id="%1$s" name="%3$s"><option value="">%4$s</option>%5$s</select></div>',
		esc_attr( $id ), esc_html( $label ), esc_attr( $name ), esc_html( $any ), $html, '' !== (string) $current ? ' is-active' : ''
	);
};

$search = sprintf(
	'<div class="cacdemo-filter cacdemo-filter--search"><label class="screen-reader-text" for="cacdemo-sermon-q">%s</label><input type="search" id="cacdemo-sermon-q" name="q" value="%s" placeholder="%s"></div>',
	esc_html__( 'Search sermons', 'cacdemo' ), esc_attr( $request['q'] ), esc_attr__( 'Search sermons', 'cacdemo' )
);
$fields = '';
if ( 'series' !== $request['archive'] ) {
	$fields .= $select( 'series', __( 'Series', 'cacdemo' ), wp_list_pluck( $terms( 'sermon_series' ), 'name', 'slug' ), $request['series'], __( 'All series', 'cacdemo' ) );
}
$fields .= $select( 'yr', __( 'Year', 'cacdemo' ), array_combine( $years, $years ), $request['year'] ?: '', __( 'All years', 'cacdemo' ) );
if ( 'speaker' !== $request['archive'] ) {
	$fields .= $select( 'speaker', __( 'Speaker', 'cacdemo' ), wp_list_pluck( $terms( 'sermon_speaker' ), 'name', 'slug' ), $request['speaker'], __( 'All speakers', 'cacdemo' ) );
}
$fields .= $select( 'location', __( 'Location', 'cacdemo' ), $centres, $request['location'], __( 'All locations', 'cacdemo' ) );
$fields .= $select( 'sort', __( 'Sort', 'cacdemo' ), array( 'old' => __( 'Oldest first', 'cacdemo' ), 'title' => __( 'Title A–Z', 'cacdemo' ) ), 'new' === $request['sort'] ? '' : $request['sort'], __( 'Newest first', 'cacdemo' ) );
if ( 'table' === $request['view'] ) {
	$fields .= $select( 'group', __( 'Group by', 'cacdemo' ), array( 'series' => __( 'Group: Series', 'cacdemo' ), 'speaker' => __( 'Group: Speaker', 'cacdemo' ), 'topic' => __( 'Group: Topic', 'cacdemo' ) ), 'year' === $request['group'] ? '' : $request['group'], __( 'Group: Year', 'cacdemo' ) );
}

$active = count( array_filter( array( $request['series'] && 'series' !== $request['archive'], $request['year'], $request['speaker'] && 'speaker' !== $request['archive'], $request['location'] ) ) );

$hidden = 'grid' !== $request['view'] ? sprintf( '<input type="hidden" name="view" value="%s">', esc_attr( $request['view'] ) ) : '';
$clear  = cacdemo_sermon_is_filtered( $request ) || 'new' !== $request['sort']
	? sprintf( '<a class="cacdemo-filters__clear" href="%s">%s</a>', esc_url( add_query_arg( 'grid' !== $request['view'] ? array( 'view' => $request['view'] ) : array(), cacdemo_sermon_base_url() ) ), esc_html__( 'Clear', 'cacdemo' ) )
	: '';

$views = '';
foreach ( array( 'grid' => __( 'Grid', 'cacdemo' ), 'list' => __( 'List', 'cacdemo' ), 'table' => __( 'Table', 'cacdemo' ) ) as $view => $label ) {
	$views .= sprintf(
		'<a class="cacdemo-view-switch__link" data-view="%1$s" href="%2$s"%3$s>%4$s</a>',
		esc_attr( $view ),
		esc_url( cacdemo_sermon_url( array( 'view' => 'grid' === $view ? null : $view, 'group' => 'table' === $view ? $request['group'] : null ) ) ),
		$view === $request['view'] ? ' aria-current="true"' : '',
		esc_html( $label )
	);
}

wp_enqueue_script( 'cacdemo-view-switch' );

// Wide screens: search and dropdowns in one row. Phones: a "Filters" button (shown only with JavaScript) reveals the
// dropdowns, which start hidden unless a filter is active. Dropdowns apply on change; Apply covers typed searches.
printf(
	'<div %1$s><form class="cacdemo-filters" method="get" action="%2$s" role="search" aria-label="%3$s" data-active="%5$d">%4$s<button type="button" class="cacdemo-filters__toggle" aria-expanded="false" aria-controls="cacdemo-sermon-selects">%6$s%7$s</button><div class="cacdemo-filters__selects" id="cacdemo-sermon-selects">%8$s</div>%9$s<div class="cacdemo-filters__actions"><button type="submit" class="cacdemo-filters__apply">%10$s</button>%11$s</div></form><nav class="cacdemo-view-switch" aria-label="%12$s">%13$s</nav></div>',
	get_block_wrapper_attributes( array( 'class' => 'cacdemo-sermon-toolbar' ) ),
	esc_url( cacdemo_sermon_base_url() ),
	esc_attr__( 'Find sermons', 'cacdemo' ),
	$search, // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above.
	$active,
	esc_html__( 'Filters', 'cacdemo' ),
	$active ? sprintf( ' <span class="cacdemo-filters__count">%d</span>', $active ) : '',
	$fields, // phpcs:ignore WordPress.Security.EscapeOutput
	$hidden, // phpcs:ignore WordPress.Security.EscapeOutput
	esc_html__( 'Apply', 'cacdemo' ),
	$clear, // phpcs:ignore WordPress.Security.EscapeOutput
	esc_attr__( 'Sermon view', 'cacdemo' ),
	$views // phpcs:ignore WordPress.Security.EscapeOutput
);
