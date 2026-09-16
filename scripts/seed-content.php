<?php
/**
 * Seed design content: the nine pages, their block content, the Main navigation menu,
 * centre records and the few curated images the pages use.
 *
 *   wp --path=<WP_ROOT> eval-file scripts/seed-content.php
 *
 * This is DEMO/DESIGN content for local and staging review — not part of bootstrap
 * (clean baseline, CLAUDE.md rule 8). Centre facts and some wording come from the
 * legacy site and are NOT church-confirmed (see docs/content-harvest/REVIEW-NEEDED.md).
 *
 * Idempotent: pages, centres, menu and images are created only if missing (matched by
 * slug / title / file name); page content is REWRITTEN on every run, so do not run it
 * against a site whose pages editors have changed.
 * Requires Secure Custom Fields and the centre definitions (scripts/scf-sync.php import).
 */

if ( ! function_exists( 'update_field' ) || ! post_type_exists( 'centre' ) ) {
	WP_CLI::error( 'Secure Custom Fields and the centre post type are required (run scf-sync.php import).' );
}

// Block content is saved as an administrator so it is not filtered by kses.
$cacdemo_admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
if ( ! $cacdemo_admins ) {
	WP_CLI::error( 'No administrator account found.' );
}
wp_set_current_user( $cacdemo_admins[0]->ID );

function cs_b( $name, $attrs, $html ) {
	$a = $attrs ? ' ' . serialize_block_attributes( $attrs ) : '';
	return "<!-- wp:$name$a -->\n$html\n<!-- /wp:$name -->\n";
}
function cs_self_b( $name, $attrs = array() ) {
	$a = $attrs ? ' ' . serialize_block_attributes( $attrs ) : '';
	return "<!-- wp:$name$a /-->\n";
}
function cs_e( $s ) { return esc_html( $s ); }

function cs_p( $text, $size = null, $class = '' ) {
	$attrs = array();
	$classes = array();
	if ( $class ) { $attrs['className'] = $class; $classes[] = $class; }
	if ( $size ) { $attrs['fontSize'] = $size; $classes[] = "has-$size-font-size"; }
	$c = $classes ? ' class="' . implode( ' ', $classes ) . '"' : '';
	return cs_b( 'paragraph', $attrs, "<p$c>$text</p>" );
}
function cs_h( $text, $level = 2, $size = null, $extra = array() ) {
	$attrs = $extra;
	if ( 2 !== $level ) { $attrs = array( 'level' => $level ) + $attrs; }
	$classes = array( 'wp-block-heading' );
	if ( ! empty( $extra['align'] ) ) { $classes[] = 'align' . $extra['align']; }
	if ( ! empty( $extra['className'] ) ) { $classes[] = $extra['className']; }
	if ( $size ) { $attrs['fontSize'] = $size; $classes[] = "has-$size-font-size"; }
	$id = ! empty( $extra['anchor'] ) ? ' id="' . $extra['anchor'] . '"' : '';
	return cs_b( 'heading', $attrs, "<h$level class=\"" . implode( ' ', $classes ) . "\"$id>$text</h$level>" );
}
function cs_button( $label, $href, $text_link = false ) {
	$attrs = $text_link ? array( 'className' => 'is-style-text-link' ) : array();
	$cls = $text_link ? ' is-style-text-link' : '';
	return cs_b( 'button', $attrs, "<div class=\"wp-block-button$cls\"><a class=\"wp-block-button__link wp-element-button\" href=\"" . esc_url( $href ) . "\">" . cs_e( $label ) . '</a></div>' );
}
function cs_buttons( $inner, $top = true ) {
	if ( $top ) {
		return cs_b( 'buttons', array( 'style' => array( 'spacing' => array( 'margin' => array( 'top' => 'var:preset|spacing|60' ) ) ) ),
			"<div class=\"wp-block-buttons\" style=\"margin-top:var(--wp--preset--spacing--60)\">\n$inner</div>" );
	}
	return cs_b( 'buttons', array(), "<div class=\"wp-block-buttons\">\n$inner</div>" );
}
function cs_quote( $text, $cite ) {
	return cs_b( 'quote', array( 'style' => array( 'spacing' => array( 'margin' => array( 'top' => 'var:preset|spacing|60' ) ) ) ),
		"<blockquote class=\"wp-block-quote\" style=\"margin-top:var(--wp--preset--spacing--60)\">\n" . cs_p( cs_e( $text ) ) . '<cite>' . cs_e( $cite ) . "</cite></blockquote>" );
}
function cs_image( $id, $class = '', $extra = array() ) {
	$src  = wp_get_attachment_image_url( $id, 'large' );
	$alt  = get_post_meta( $id, '_wp_attachment_image_alt', true );
	$attrs = array( 'id' => $id, 'sizeSlug' => 'large', 'linkDestination' => 'none' ) + $extra;
	$classes = array( 'wp-block-image' );
	if ( ! empty( $extra['align'] ) ) { $classes[] = 'align' . $extra['align']; }
	$classes[] = 'size-large';
	if ( $class ) { $attrs['className'] = $class; $classes[] = $class; }
	return cs_b( 'image', $attrs, '<figure class="' . implode( ' ', $classes ) . '"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . "\" class=\"wp-image-$id\"/></figure>" );
}
function cs_group( $inner, $attrs = array(), $classes = '', $style = '' ) {
	$c = trim( 'wp-block-group ' . $classes );
	$s = $style ? " style=\"$style\"" : '';
	return cs_b( 'group', $attrs, "<div class=\"$c\"$s>\n$inner</div>" );
}
function cs_column( $inner, $width, $valign = null ) {
	$attrs = array();
	$cls = 'wp-block-column';
	if ( $valign ) { $attrs['verticalAlignment'] = $valign; $cls .= " is-vertically-aligned-$valign"; }
	$attrs['width'] = $width;
	return cs_b( 'column', $attrs, "<div class=\"$cls\" style=\"flex-basis:$width\">\n$inner</div>" );
}
function cs_columns( $inner, $valign = null, $wide = true ) {
	$attrs = array();
	$cls = 'wp-block-columns';
	if ( $valign ) { $attrs['verticalAlignment'] = $valign; }
	if ( $wide ) { $attrs['align'] = 'wide'; $cls .= ' alignwide'; }
	if ( $valign ) { $cls .= " are-vertically-aligned-$valign"; }
	$attrs['style'] = array( 'spacing' => array( 'blockGap' => array( 'left' => 'var:preset|spacing|70', 'top' => 'var:preset|spacing|60' ) ) );
	return cs_b( 'columns', $attrs, "<div class=\"$cls\">\n$inner</div>" );
}

/** Full-width band: '' (paper), 'mist' or 'night'. */
function cs_band( $name, $inner, $tone = '', $top = 'var:preset|spacing|section' ) {
	$attrs = array( 'metadata' => array( 'name' => $name ), 'align' => 'full' );
	$classes = 'alignfull';
	if ( $tone ) { $attrs['className'] = "is-style-section-$tone"; $classes .= " is-style-section-$tone"; }
	$attrs['style'] = array( 'spacing' => array( 'padding' => array( 'top' => $top, 'bottom' => 'var:preset|spacing|section' ) ) );
	$attrs['layout'] = array( 'type' => 'constrained' );
	$css = 'padding-top:' . preg_replace( '/^var:preset\|spacing\|(.+)$/', 'var(--wp--preset--spacing--$1)', $top ) . ';padding-bottom:var(--wp--preset--spacing--section)';
	return cs_group( $inner, $attrs, $classes, $css );
}

/** Interior page intro under the template's title: lead + optional text, reading width, left-aligned. */
function cs_intro( $lead, $text = '' ) {
	$inner = cs_p( cs_e( $lead ), 'lead' ) . ( $text ? cs_p( cs_e( $text ) ) : '' );
	return cs_group( $inner, array(
		'metadata' => array( 'name' => 'Intro' ),
		'align'    => 'wide',
		'style'    => array( 'spacing' => array( 'padding' => array( 'bottom' => 'var:preset|spacing|70' ) ) ),
		'layout'   => array( 'type' => 'constrained', 'contentSize' => '38rem', 'justifyContent' => 'left' ),
	), 'alignwide', 'padding-bottom:var(--wp--preset--spacing--70)' );
}

/** Divided rows: [ [title, href|null, description], ... ] */
function cs_rows( $items, $wide = true ) {
	$out = '';
	foreach ( $items as $it ) {
		list( $title, $href, $desc ) = $it;
		$t = $href ? '<a href="' . esc_url( $href ) . '">' . cs_e( $title ) . '</a>' : cs_e( $title );
		$out .= cs_b( 'columns', array(
			'verticalAlignment' => 'top',
			'style' => array( 'spacing' => array( 'blockGap' => array( 'top' => 'var:preset|spacing|20', 'left' => 'var:preset|spacing|70' ), 'margin' => array( 'bottom' => '0' ) ) ),
		), "<div class=\"wp-block-columns are-vertically-aligned-top\" style=\"margin-bottom:0\">\n"
			. cs_b( 'column', array( 'width' => '36%' ), "<div class=\"wp-block-column\" style=\"flex-basis:36%\">\n" . cs_h( $t, 3, 'subheading' ) . '</div>' )
			. cs_b( 'column', array( 'width' => '64%' ), "<div class=\"wp-block-column\" style=\"flex-basis:64%\">\n" . cs_p( cs_e( $desc ) ) . '</div>' )
			. '</div>' );
	}
	$attrs = array( 'className' => 'is-style-rows', 'style' => array( 'spacing' => array( 'margin' => array( 'top' => 'var:preset|spacing|60' ), 'blockGap' => '0' ) ), 'layout' => array( 'type' => 'default' ) );
	$cls = 'is-style-rows';
	if ( $wide ) { $attrs = array( 'align' => 'wide' ) + $attrs; $cls = 'alignwide ' . $cls; }
	return cs_group( $out, $attrs, $cls, 'margin-top:var(--wp--preset--spacing--60)' );
}
function cs_rows_band( $name, $heading, $items, $tone = '' ) {
	return cs_band( $name, cs_h( cs_e( $heading ), 2, null, array( 'align' => 'wide' ) ) . cs_rows( $items ), $tone );
}

/** Stacked rows for a column (title, text, link). */
function cs_stack_rows( $items ) {
	$out = '';
	foreach ( $items as $it ) {
		list( $title, $text, $link_label, $href ) = $it;
		$inner = cs_h( cs_e( $title ), 3, 'subheading' ) . cs_p( cs_e( $text ) );
		if ( $href ) { $inner .= cs_buttons( cs_button( $link_label, $href, true ), false ); }
		$out .= cs_group( $inner, array( 'style' => array( 'spacing' => array( 'blockGap' => 'var:preset|spacing|20' ) ), 'layout' => array( 'type' => 'default' ) ) );
	}
	return cs_group( $out, array( 'className' => 'is-style-rows', 'style' => array( 'spacing' => array( 'margin' => array( 'top' => 'var:preset|spacing|60' ), 'blockGap' => '0' ) ), 'layout' => array( 'type' => 'default' ) ), 'is-style-rows', 'margin-top:var(--wp--preset--spacing--60)' );
}

function cs_embed( $url ) {
	return cs_b( 'embed', array( 'url' => $url, 'type' => 'rich', 'providerNameSlug' => 'spotify', 'responsive' => true ),
		"<figure class=\"wp-block-embed is-type-rich is-provider-spotify wp-block-embed-spotify\"><div class=\"wp-block-embed__wrapper\">\n$url\n</div></figure>" );
}
function cs_pattern( $slug ) { return cs_self_b( 'pattern', array( 'slug' => $slug ) ); }

/** Statement: heading + scripture beside lead + text + actions, in a tone band. */
function cs_statement( $name, $heading, $scripture, $cite, $lead, $texts, $actions, $tone = 'mist' ) {
	$main = cs_p( cs_e( $lead ), 'lead' );
	foreach ( $texts as $t ) { $main .= cs_p( cs_e( $t ) ); }
	if ( $actions ) { $main .= cs_buttons( $actions ); }
	$main = cs_group( $main, array( 'layout' => array( 'type' => 'constrained', 'contentSize' => '38rem', 'justifyContent' => 'left' ) ) );
	return cs_band( $name, cs_columns(
		cs_column( cs_h( cs_e( $heading ) ) . cs_quote( $scripture, $cite ), '40%' ) .
		cs_column( $main, '60%' )
	), $tone );
}

/* ---------------- Structure: pages, images, centres, menu ---------------- */

$page_titles = array(
	'home' => 'Home', 'about' => 'About', 'new-here' => 'New Here', 'centres' => 'Centres', 'sermons' => 'Sermons',
	'ministries' => 'Ministries', 'connect' => 'Connect', 'follow-us' => 'Follow Us', 'music' => 'Music', 'contact' => 'Contact',
);
$ids   = array();
$order = 0;
foreach ( $page_titles as $slug => $title ) {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $existing ) {
		$ids[ $slug ] = $existing->ID;
		continue;
	}
	$ids[ $slug ] = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'menu_order' => $order++ ), true );
	if ( is_wp_error( $ids[ $slug ] ) ) { WP_CLI::error( $ids[ $slug ] ); }
	WP_CLI::log( "created page $slug" );
}
update_option( 'timezone_string', 'America/Toronto' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $ids['home'] );

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$image_dir = dirname( __DIR__ ) . '/content-source/legacy-site/images';
$images    = array(
	'flame'   => array( 'branding/logo-mark-photo-collage-portrait__db3f71fb.png', 'Christlikeness flame mark photo collage', 'The Christlikeness flame mark filled with photos from worship services' ),
	'worship' => array( 'worship/church-page-background-video-frame__f8f846c1.jpg', 'Sunday worship', 'Worship leader and band leading a Sunday service' ),
	'pastors' => array( 'people/about-lead-pastors-section-photo__b55d3b0b.jpg', 'Lead pastors', 'Christlikeness lead pastors' ),
	'cw'      => array( 'music/christlike-worship-logo__064effd0.jpg', 'Christlike Worship logo', 'Christlike Worship logo' ),
	'rm'      => array( 'music/radical-music-logo__128bda72.jpg', 'Radical Music logo', 'Radical Music logo' ),
);
$M = array();
foreach ( $images as $key => $img ) {
	list( $rel, $title, $alt ) = $img;
	$base  = pathinfo( $rel, PATHINFO_FILENAME );
	$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids',
		'meta_query' => array( array( 'key' => '_wp_attached_file', 'value' => $base, 'compare' => 'LIKE' ) ) ) );
	if ( $found ) {
		$M[ $key ] = $found[0];
		continue;
	}
	if ( ! is_readable( "$image_dir/$rel" ) ) { WP_CLI::error( "image source missing: $image_dir/$rel" ); }
	$tmp = wp_tempnam( basename( $rel ) );
	copy( "$image_dir/$rel", $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $rel ), 'tmp_name' => $tmp ), 0, $title );
	if ( is_wp_error( $id ) ) { WP_CLI::error( $id ); }
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	$M[ $key ] = $id;
	WP_CLI::log( "imported image $key (#$id)" );
}

$centres = array(
	'north-york'  => array( 'North York', 0, array( 'centre_service_day' => 'Sunday', 'centre_service_time' => '10:00 AM', 'centre_street' => '4544 Dufferin St.', 'centre_unit' => 'Unit 210', 'centre_city' => 'North York', 'centre_province' => 'ON', 'centre_postal_code' => 'L4K 5M5', 'centre_map_url' => 'https://www.google.com/maps/search/?api=1&query=4544+Dufferin+St+Unit+210+North+York+ON+L4K+5M5', 'centre_verification_notes' => 'Demo data from the legacy christlikeness.ca site; not church-confirmed. Confirm service day and time (R-02) and phone (R-05).' ) ),
	'scarborough' => array( 'Scarborough', 1, array( 'centre_service_day' => 'Sunday', 'centre_service_time' => '2:00 PM', 'centre_street' => '2220 Midland Ave.', 'centre_city' => 'Scarborough', 'centre_province' => 'ON', 'centre_postal_code' => 'M1P 3E6', 'centre_map_url' => 'https://www.google.com/maps/search/?api=1&query=2220+Midland+Ave+Scarborough+ON+M1P+3E6', 'centre_verification_notes' => 'Demo data from the legacy christlikeness.ca site; not church-confirmed. Unit conflict (R-01): 84 BR vs 102 BR, left empty. Confirm day/time (R-02) and phone (R-05).' ) ),
);
foreach ( $centres as $slug => $c ) {
	list( $title, $menu_order, $fields ) = $c;
	$existing = get_page_by_path( $slug, OBJECT, 'centre' );
	if ( $existing ) {
		$cid = $existing->ID;
	} else {
		$cid = wp_insert_post( array( 'post_type' => 'centre', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'menu_order' => $menu_order ), true );
		if ( is_wp_error( $cid ) ) { WP_CLI::error( $cid ); }
		WP_CLI::log( "created centre $slug" );
	}
	foreach ( $fields as $name => $value ) {
		if ( '' === (string) get_field( $name, $cid, false ) ) { update_field( $name, $value, $cid ); }
	}
}

$nav = get_posts( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'title' => 'Main', 'numberposts' => 1 ) );
if ( ! $nav ) {
	$links = '';
	foreach ( array( 'home', 'about', 'new-here', 'centres', 'sermons', 'ministries', 'connect', 'music' ) as $slug ) {
		$links .= cs_self_b( 'navigation-link', array( 'label' => $page_titles[ $slug ], 'type' => 'page', 'id' => $ids[ $slug ], 'url' => get_permalink( $ids[ $slug ] ), 'kind' => 'post-type' ) );
	}
	wp_insert_post( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'post_title' => 'Main', 'post_content' => $links ) );
	WP_CLI::log( 'created navigation menu Main' );
}

$MISSION  = 'Our passion is to win souls intentionally for Jesus, express the compassion of God and make disciples like Christ.';
$FOUNDING = 'Our lead pastors, Elijohn and Czarina Payopay, started Christlikeness in 2017 in the city of Toronto, Ontario, Canada. Today, Christlikeness is ministering across the Greater Toronto Area, reaching out beyond through online streaming, social media platforms and the music of Christlike Worship and Radical Music.';
$EPH      = 'We must become like a mature person, growing until we become like Christ and have his perfection.';
$CW       = 'https://open.spotify.com/artist/3uSF4xxA3lBqxWvpWxw7La';
$RM       = 'https://open.spotify.com/artist/50b3uc1Jhiq9yBGd2TRAtt';
$YT       = 'https://www.youtube.com/channel/UCdEsFxptBKsb1j6Q9PaY5jQ';

$pages = array();

/* ---------------- Home ---------------- */
$hero = cs_b( 'cacdemo/page-hero', array( 'variant' => 'landing', 'themeSlot' => 'home-hero', 'align' => 'full' ), cs_group(
	cs_columns(
		cs_column(
			cs_h( 'Looking for a church?', 1, 'display', array( 'className' => 'cacdemo-display' ) ) .
			cs_p( 'Be our guest. Come as you are.', 'lead', 'cacdemo-hero-lead' ) .
			cs_buttons( cs_button( 'Find a centre', '/centres/' ) . cs_button( 'Plan your first Sunday', '/new-here/', true ) ),
			'58%', 'center'
		) .
		cs_b( 'column', array( 'verticalAlignment' => 'center', 'width' => '42%', 'className' => 'cacdemo-hero-media' ),
			"<div class=\"wp-block-column is-vertically-aligned-center cacdemo-hero-media\" style=\"flex-basis:42%\">\n" . cs_image( $M['flame'], '', array( 'align' => 'right' ) ) . '</div>' ),
		'center'
	),
	array( 'metadata' => array( 'name' => 'Hero' ), 'align' => 'full', 'className' => 'cacdemo-hero',
		'style' => array( 'spacing' => array( 'padding' => array( 'top' => 'clamp(2.5rem, 1rem + 4.5vw, 5rem)', 'bottom' => 'clamp(3rem, 1rem + 5vw, 5.5rem)' ) ) ),
		'layout' => array( 'type' => 'constrained' ) ),
	'alignfull cacdemo-hero', 'padding-top:clamp(2.5rem, 1rem + 4.5vw, 5rem);padding-bottom:clamp(3rem, 1rem + 5vw, 5.5rem)'
) );

$worship = cs_band( 'Worship band', cs_columns(
	cs_column( cs_image( $M['worship'], 'is-style-arch' ), '45%', 'center' ) .
	cs_column(
		cs_h( 'The music of Christlike Worship and Radical Music' ) .
		cs_stack_rows( array(
			array( 'Christlike Worship', 'The manifest worship experience of the Psalmists Ministry of Christlikeness Church.', 'Listen on Spotify', $CW ),
			array( 'Radical Music', 'A collaborative project between Christlike Worship and the Psalmists of R.A.D.I.C.A.L, the youth ministry arm of Christlikeness Church.', 'Listen on Spotify', $RM ),
		) ),
		'55%', 'center'
	),
	'center'
), 'night' );

$pages['home'] = $hero
	. cs_pattern( 'cacdemo/centre-visit' )
	. cs_statement( 'Our passion', 'Our passion', $EPH, 'Ephesians 4:13', $MISSION, array( $FOUNDING ),
		cs_button( 'About Christlikeness', '/about/' ) . cs_button( 'What we believe', '/about/#beliefs', true ) )
	. $worship
	. cs_pattern( 'cacdemo/channels-band' )
	. cs_rows_band( 'Next steps', 'Next steps', array(
		array( 'New Here', '/new-here/', 'What to expect on your first Sunday, and how to plan a visit.' ),
		array( 'Sermons', '/sermons/', 'Watch recent messages.' ),
		array( 'Ministries', '/ministries/', 'Find a place to belong and to serve.' ),
		array( 'Connect', '/connect/', 'Stay in touch online and through groups.' ),
	) );

/* ---------------- About ---------------- */
$beliefs = array(
	array( 'The Holy Scriptures', '2 Timothy 3:16-17' ), array( 'The Eternal Godhead', '1 John 5:7' ),
	array( 'The Fall of Man', 'Romans 5:12' ), array( 'The Plan of Redemption', 'John 3:16' ),
	array( 'Totality of Salvation', 'Eph. 2:8, Phil. 2:12, Acts 16:31, Heb. 9:28' ), array( 'Repentance and Acceptance', '1 John 1:9' ),
	array( 'New Birth', 'John 3:3' ), array( 'Progressive Sanctification', '1 Thessalonians 4:3' ),
	array( 'Water Baptism', 'Matthew 28:19' ), array( 'The Lord’s Supper', '1 Corinthians 11:28' ),
	array( 'Baptism of the Holy Spirit', 'Acts 2:4' ), array( 'Spirit-Filled Life', 'Galatians 5:16' ),
	array( 'Five-Fold Ministry Gifts', 'Ephesians 4:11' ), array( 'Gifts & Fruit of the Spirit', '1 Corinthians 12:1-11, Galatians 5:22-23' ),
	array( 'Divine Healing', 'Mark 16:17-18' ), array( 'Second Coming of Christ', '1 Thessalonians 4:16-17' ),
	array( 'The Final Judgement', '2 Corinthians 5:10' ), array( 'Evangelism', 'James 5:20' ),
	array( 'Tithes and Offering', 'Malachi 3:10' ),
);
$li = '';
foreach ( $beliefs as $bl ) {
	$li .= cs_b( 'list-item', array(), '<li><strong>' . cs_e( $bl[0] ) . '</strong><br>' . cs_e( $bl[1] ) . '</li>' );
}
$belief_list = cs_b( 'list', array( 'ordered' => true, 'align' => 'wide', 'className' => 'cacdemo-beliefs' ), "<ol class=\"wp-block-list alignwide cacdemo-beliefs\">\n$li</ol>" );

$pages['about'] = cs_intro( $MISSION, $FOUNDING )
	. cs_band( 'Lead pastors', cs_columns(
		cs_column( cs_image( $M['pastors'], 'cacdemo-portrait' ), '40%', 'center' ) .
		cs_column(
			cs_group(
				cs_h( 'Lead pastors' ) .
				cs_p( 'Elijohn and Czarina Payopay', 'lead' ) .
				cs_p( 'Our lead pastors started Christlikeness in 2017 in the city of Toronto, Ontario, Canada.' ) .
				cs_h( 'LOVE is our highest goal!', 3, 'heading', array( 'className' => 'cacdemo-accent-line' ) ),
				array( 'layout' => array( 'type' => 'constrained', 'contentSize' => '32rem', 'justifyContent' => 'left' ) )
			),
			'60%', 'center'
		),
		'center'
	), 'mist' )
	. cs_band( 'Declaration of Faith',
		cs_group(
			cs_columns(
				cs_column( cs_h( 'Declaration of Faith', 2, null, array( 'anchor' => 'beliefs' ) ), '40%' ) .
				cs_column( cs_p( 'These are the major doctrines that serve as the foundation of Christlikeness. These doctrines are the bases of all spiritual and theological principles that guide all operations, activities, policies and programs of the Church.' ), '60%' )
			),
			array( 'align' => 'wide', 'layout' => array( 'type' => 'default' ) ), 'alignwide'
		)
		. $belief_list
	)
	. cs_statement( 'Growing like Christ', 'Growing like Christ', $EPH, 'Ephesians 4:13', 'Come and grow with us.', array( 'Christlikeness gathers every Sunday across the Greater Toronto Area, and online through streaming and social media.' ),
		cs_button( 'Find a centre', '/centres/' ) . cs_button( 'Plan your first Sunday', '/new-here/', true ) );

/* ---------------- New Here ---------------- */
$pages['new-here'] = cs_intro( 'Be our guest. Come as you are.', 'Please know that you are always welcome, and we would love to spend more time with you.' )
	. cs_pattern( 'cacdemo/centre-visit' )
	. cs_rows_band( 'Your first Sunday', 'Your first Sunday', array(
		array( 'Choose a centre', '/centres/', 'Pick the centre and service time that suit you, and get directions.' ),
		array( 'Let us know you’re coming', '/contact/', 'Tell us how many are joining you, including children, so we can welcome you.' ),
		array( 'Worship with us', '/music/', 'Sunday services are led by the Psalmists, the ministry behind Christlike Worship.' ),
		array( 'Take a next step', '/connect/', 'Groups, serving and ways to stay in touch after your visit.' ),
	) )
	. cs_statement( 'Who we are', 'Who we are', 'For I know the plans I have for you,” declares the Lord, “plans to prosper you and not to harm you, plans to give you hope and a future.', 'Jeremiah 29:11', $MISSION, array( 'Christlikeness is ministering across the Greater Toronto Area, reaching out beyond through online streaming, social media platforms and music.' ),
		cs_button( 'About Christlikeness', '/about/' ) . cs_button( 'What we believe', '/about/#beliefs', true ) );

/* ---------------- Centres (template supplies the directory) ---------------- */
$pages['centres'] = cs_p( 'One church, gathering across the Greater Toronto Area. Every centre shares the same worship, teaching and welcome.', 'lead', 'cacdemo-page-lead' );

/* ---------------- Sermons (template supplies the collection) ---------------- */
$pages['sermons'] = cs_p( 'Messages from Sunday services across our centres. Watch, listen and find the notes and resources shared with each sermon.', 'lead', 'cacdemo-page-lead' );

/* ---------------- Ministries (template supplies the ministry list; records come from seed-ministries.php) ---------------- */
$pages['ministries'] = cs_p( 'Serving Christ by serving one another.', 'lead', 'cacdemo-page-lead' )
	. cs_p( 'Different gifts, abilities and experiences all contribute to the life and mission of the church. Find the ministry where yours can help.', null, 'cacdemo-page-lead' );

/* ---------------- Connect ---------------- */
$pages['connect'] = cs_intro( 'Stay in touch online, and find people to grow with.' )
	. cs_band( 'Facebook', cs_h( 'Facebook', 2, null, array( 'align' => 'wide' ) )
		. cs_self_b( 'cacdemo/channels', array( 'variant' => 'list', 'platform' => 'facebook', 'align' => 'wide' ) )
		. cs_b( 'buttons', array( 'align' => 'wide', 'style' => array( 'spacing' => array( 'margin' => array( 'top' => 'var:preset|spacing|50' ) ) ) ),
			"<div class=\"wp-block-buttons alignwide\" style=\"margin-top:var(--wp--preset--spacing--50)\">\n" . cs_button( 'See recent posts', '/follow-us/', true ) . '</div>' ), '', '0' )
	. cs_band( 'Online', cs_h( 'More online', 2, null, array( 'align' => 'wide' ) ) . cs_rows( array(
		array( 'Instagram', 'https://www.instagram.com/christlikeness_/', 'Photos and stories from our centres. @christlikeness_' ),
		array( 'YouTube', $YT, 'Sermons and worship.' ),
		array( 'Spotify', $CW, 'Christlike Worship and Radical Music.' ),
		array( 'X', 'https://twitter.com/christlikeness_', 'Updates. @christlikeness_' ),
	) ), '', '0' )
	. cs_rows_band( 'Groups', 'Groups', array(
		array( 'Radical youth', 'https://www.instagram.com/radical_ym', 'The youth ministry of Christlikeness. @radical_ym' ),
		array( 'Lifegroups and ministries', '/ministries/', 'Find a place to belong and to serve.' ),
	), 'mist' );

/* ---------------- Follow Us (template supplies the channel cards; records come from seed-channels.php) ---------------- */
$pages['follow-us'] = cs_p( 'The Facebook pages and group run by Christlikeness, our centres and our youth ministry. Follow the ones closest to you.', 'lead', 'cacdemo-page-lead' );

/* ---------------- Music ---------------- */
$pages['music'] = cs_intro( 'All tracks are now available for download and streaming on Spotify, Apple Music, YouTube Music, Amazon Music and other digital music stores and streaming services.' )
	. cs_band( 'Christlike Worship', cs_columns(
		cs_column(
			cs_group( cs_image( $M['cw'], 'cacdemo-project-logo' ) .
				cs_h( 'Christlike Worship' ) .
				cs_p( 'Christlike Worship is the manifest worship experience of the Psalmists Ministry of Christlikeness Church.', 'lead' ) .
				cs_p( 'Based in Toronto, Canada, launched during the early months of 2020 in the midst of a global catastrophe, CW’s music is a response of faith, hope and love with a heart that seeks to glorify God.' ),
				array( 'layout' => array( 'type' => 'constrained', 'contentSize' => '34rem', 'justifyContent' => 'left' ) ) ),
			'50%'
		) .
		cs_column( cs_embed( $CW ), '50%' )
	), '', '0' )
	. cs_band( 'Radical Music', cs_columns(
		cs_column( cs_image( $M['worship'], 'is-style-arch' ), '40%', 'center' ) .
		cs_column(
			cs_image( $M['rm'], 'cacdemo-project-logo' ) .
			cs_h( 'Radical Music' ) .
			cs_p( 'Radical Music is a collaborative project between Christlike Worship and the Psalmists of R.A.D.I.C.A.L - the youth ministry arm of Christlikeness Church based in Toronto, Canada.', 'lead' ) .
			cs_p( 'RM is manifest of the creative and passionate desire of the Radical Psalmists to glorify God in scriptural lyrics and music.' ) .
			cs_embed( $RM ),
			'60%', 'center'
		),
		'center'
	), 'night' );

/* ---------------- Contact ---------------- */
$pages['contact'] = cs_intro( 'Questions, prayer requests or planning a visit? We would love to hear from you.' )
	. cs_band( 'Reach us', cs_h( 'Reach us', 2, null, array( 'align' => 'wide' ) ) . cs_rows( array(
		array( '289.212.0807', 'tel:+12892120807', 'Call the church office.' ),
		array( 'Facebook', 'https://www.facebook.com/christlikecanada', 'Send us a message. @christlikecanada' ),
		array( 'Instagram', 'https://www.instagram.com/christlikeness_/', 'Send us a message. @christlikeness_' ),
	) ), '', '0' )
	. cs_pattern( 'cacdemo/centre-visit' );

foreach ( $pages as $slug => $content ) {
	$r = wp_update_post( array( 'ID' => $ids[ $slug ], 'post_content' => $content ), true );
	echo is_wp_error( $r ) ? "FAIL $slug: " . $r->get_error_message() . "\n" : "updated $slug (#{$ids[$slug]})\n";
}
