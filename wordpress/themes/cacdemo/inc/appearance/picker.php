<?php
/**
 * Visitor colour picker: block cacdemo/palette-picker (placed in the footer part; assets/js/appearance.js also shows it in
 * the phone menu). Radio buttons, so it is keyboard- and screen-reader-friendly without custom widgets. Rendered hidden:
 * the choice needs browser storage, so the script reveals it only when that works.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'cacdemo_palette_picker_register' );

function cacdemo_palette_picker_register() {
	wp_register_script( 'cacdemo-palette-picker-editor', get_theme_file_uri( 'assets/js/palette-picker-editor.js' ), array( 'wp-blocks', 'wp-element', 'wp-block-editor' ), (string) filemtime( get_theme_file_path( 'assets/js/palette-picker-editor.js' ) ), true );
	register_block_type( 'cacdemo/palette-picker', array(
		'api_version'     => 3,
		'title'           => __( 'Visitor colours', 'cacdemo' ),
		'category'        => 'theme',
		'description'     => __( 'Lets visitors choose one of the approved colour palettes.', 'cacdemo' ),
		'supports'        => array( 'html' => false, 'multiple' => false ),
		'editor_script'   => 'cacdemo-palette-picker-editor',
		'render_callback' => 'cacdemo_palette_picker_render',
	) );
}

function cacdemo_palette_picker_render() {
	if ( ! function_exists( 'cacdemo_site_theme_applies' ) || ! cacdemo_site_theme_applies() ) {
		return '';
	}
	$current  = cacdemo_site_theme_current();
	$palettes = cacdemo_selectable_palettes();
	if ( count( $palettes ) < 2 ) {
		return '';
	}
	$options  = cacdemo_palette_picker_option( '', __( 'Site default', 'cacdemo' ), cacdemo_palette( $current['palette'] ) );
	foreach ( $palettes as $id => $palette ) {
		if ( 'default' !== $id ) {
			$options .= cacdemo_palette_picker_option( $id, $palette['name'], $palette );
		}
	}
	return sprintf(
		'<div class="cacdemo-palette-picker" data-palette-picker hidden><fieldset><legend class="cacdemo-palette-picker__legend">%1$s</legend><div class="cacdemo-palette-picker__options">%2$s</div></fieldset><p class="cacdemo-palette-picker__note" hidden>%3$s</p></div>',
		esc_html__( 'Colours', 'cacdemo' ),
		$options,
		esc_html__( 'A colour preview is showing, so this choice is paused.', 'cacdemo' )
	);
}

function cacdemo_palette_picker_option( $value, $label, $palette ) {
	$c = $palette['colors'];
	return sprintf(
		'<label class="cacdemo-palette-option"><input type="radio" name="cacdemo-palette" value="%1$s"><span class="cacdemo-palette-option__swatch" aria-hidden="true" style="--sw-paper:%2$s;--sw-night:%3$s;--sw-stage:%4$s;--sw-highlight:%5$s"></span><span class="cacdemo-palette-option__name">%6$s</span></label>',
		esc_attr( $value ),
		esc_attr( $c['paper'] ),
		esc_attr( $c['night'] ),
		esc_attr( $c['stage'] ),
		esc_attr( $palette['custom']['highlight'] ),
		esc_html( $label )
	);
}
