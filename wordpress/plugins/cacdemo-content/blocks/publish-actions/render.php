<?php
/**
 * Publishing actions for the current section (see includes/publishing.php). Renders nothing unless the viewer may act here.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$cacdemo_actions = function_exists( 'cacdemo_publishing_actions' ) ? cacdemo_publishing_actions() : array();
if ( ! $cacdemo_actions ) {
	return;
}

$cacdemo_links = '';
foreach ( $cacdemo_actions as $cacdemo_action ) {
	list( $cacdemo_label, $cacdemo_url, $cacdemo_primary ) = $cacdemo_action;
	$cacdemo_links .= sprintf(
		'<a class="cacdemo-publish-actions__link%1$s" href="%2$s">%3$s</a>',
		$cacdemo_primary ? ' is-primary' : '',
		esc_url( $cacdemo_url ),
		esc_html( $cacdemo_label )
	);
}

printf(
	'<nav %1$s aria-label="%2$s">%3$s</nav>',
	get_block_wrapper_attributes( array( 'class' => 'cacdemo-publish-actions' ) ),
	esc_attr__( 'Publishing', 'cacdemo' ),
	$cacdemo_links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
);
