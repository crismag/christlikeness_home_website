<?php
/**
 * Sermon media: the sermon's Sources as alternatives (tabs), each played from its platform.
 * YouTube → core oEmbed; Facebook → Facebook's video embed; audio → core audio player for a file,
 * oEmbed for a streaming page, otherwise a link. Every source also gets an "Open on …" link.
 * Renders nothing when a sermon has no sources (the template's cover still shows instead).
 *
 * @var array    $attributes
 * @var WP_Block $block
 */

$post_id = $block->context['postId'] ?? get_the_ID();
if ( ! $post_id || 'sermon' !== get_post_type( $post_id ) || ! function_exists( 'get_field' ) ) {
	return;
}

$rows = array_values( array_filter( (array) get_field( 'sermon_sources', $post_id ), fn( $r ) => ! empty( $r['url'] ) ) );
if ( ! $rows ) {
	return;
}

// Video before audio; YouTube first (the most dependable embed), then Facebook, then the rest in the editor's order.
$rank = fn( $r ) => ( 'audio' === ( $r['media'] ?? '' ) ? 10 : 0 ) + ( array( 'youtube' => 0, 'facebook' => 1 )[ $r['platform'] ?? '' ] ?? 2 );
uasort( $rows, fn( $a, $b ) => $rank( $a ) <=> $rank( $b ) );
$rows = array_values( $rows );

$names = array( 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'audio' => __( 'Audio', 'cacdemo' ), 'other' => __( 'Video', 'cacdemo' ) );
// "Watch on YouTube", but "Open the audio" / "Open the video" when the platform has no name.
$open_names = array( 'youtube' => 'YouTube', 'facebook' => 'Facebook' );

$player = function ( $row ) {
	$url  = $row['url'];
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( 'audio' === ( $row['media'] ?? '' ) ) {
		if ( preg_match( '/\.(mp3|m4a|ogg|wav|aac)(\?|$)/i', $url ) ) {
			return wp_audio_shortcode( array( 'src' => $url ) );
		}
		$embed = wp_oembed_get( $url );
		return $embed ?: '';
	}
	if ( preg_match( '/(^|\.)(youtube\.com|youtu\.be)$/', $host ) ) {
		$embed = wp_oembed_get( $url, array( 'width' => 1200 ) );
		return $embed ? '<div class="cacdemo-sermon-media__frame">' . $embed . '</div>' : '';
	}
	if ( preg_match( '/(^|\.)facebook\.com$/', $host ) ) {
		$src = add_query_arg( array( 'href' => rawurlencode( $url ), 'show_text' => 'false', 'width' => 1280 ), 'https://www.facebook.com/plugins/video.php' );
		return sprintf(
			'<div class="cacdemo-sermon-media__frame"><iframe src="%s" title="%s" loading="lazy" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen></iframe></div>',
			esc_url( $src ),
			esc_attr__( 'Sermon video on Facebook', 'cacdemo' )
		);
	}
	$embed = wp_oembed_get( $url );
	return $embed ? '<div class="cacdemo-sermon-media__frame">' . $embed . '</div>' : '';
};

$uid     = wp_unique_id( 'cacdemo-sermon-media-' );
$tabs    = '';
$panels  = '';
$links   = '';
foreach ( $rows as $i => $row ) {
	$platform = $row['platform'] ?? 'other';
	$name     = $names[ $platform ] ?? $names['other'];
	$label    = trim( ( 'audio' === ( $row['media'] ?? '' ) && 'audio' !== $platform ? __( 'Audio', 'cacdemo' ) . ' · ' : '' ) . $name . ( ! empty( $row['label'] ) ? ' · ' . $row['label'] : '' ) );
	$html     = $player( $row );
	$is_audio = 'audio' === ( $row['media'] ?? '' );
	$link     = isset( $open_names[ $platform ] )
		/* translators: %s: platform name, e.g. YouTube */
		? sprintf( $is_audio ? __( 'Listen on %s', 'cacdemo' ) : __( 'Watch on %s', 'cacdemo' ), $open_names[ $platform ] )
		: ( $is_audio ? __( 'Open the audio', 'cacdemo' ) : __( 'Open the video', 'cacdemo' ) );

	$tabs   .= sprintf(
		'<button type="button" role="tab" id="%1$s-tab-%2$d" aria-controls="%1$s-panel-%2$d" aria-selected="%3$s" tabindex="%4$s" class="cacdemo-sermon-media__tab">%5$s</button>',
		esc_attr( $uid ), $i, 0 === $i ? 'true' : 'false', 0 === $i ? '0' : '-1', esc_html( $label )
	);
	$panels .= sprintf(
		'<div role="tabpanel" id="%1$s-panel-%2$d" aria-labelledby="%1$s-tab-%2$d" class="cacdemo-sermon-media__panel"%3$s>%4$s<p class="cacdemo-sermon-media__open"><a href="%5$s" rel="noopener">%6$s</a></p></div>',
		esc_attr( $uid ), $i, 0 === $i ? '' : ' hidden', $html, esc_url( $row['url'] ), esc_html( $link )
	);
}

wp_enqueue_script_module( 'cacdemo-sermon-media' );

printf(
	'<div %s>%s%s</div>',
	get_block_wrapper_attributes( array( 'class' => count( $rows ) > 1 ? 'has-tabs' : 'has-single-source' ) ),
	count( $rows ) > 1 ? '<div role="tablist" aria-label="' . esc_attr__( 'Where to watch or listen', 'cacdemo' ) . '" class="cacdemo-sermon-media__tabs">' . $tabs . '</div>' : '',
	$panels // Embeds come from core oEmbed / wp_audio_shortcode or are escaped above.
);
