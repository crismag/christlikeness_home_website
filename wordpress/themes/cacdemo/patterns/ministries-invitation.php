<?php
/**
 * Title: Ministries invitation
 * Slug: cacdemo/ministries-invitation
 * Description: Mist band closing the Ministries page: gifts statement with scripture and a way to ask about serving.
 * Categories: call-to-action
 * Keywords: serve, ministries, volunteer, invitation
 * Viewport Width: 1440
 */
?>
<!-- wp:group {"metadata":{"name":"Ministries invitation"},"align":"full","className":"is-style-section-mist","style":{"spacing":{"padding":{"top":"var:preset|spacing|section","bottom":"var:preset|spacing|section"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-mist" style="padding-top:var(--wp--preset--spacing--section);padding-bottom:var(--wp--preset--spacing--section)">
	<!-- wp:columns {"verticalAlignment":"bottom","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70","top":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-bottom">
		<!-- wp:column {"verticalAlignment":"bottom","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:58%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Different gifts, one body', 'cacdemo' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:quote -->
			<blockquote class="wp-block-quote">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Each of you should use whatever gift you have received to serve others, as faithful stewards of God’s grace in its various forms.', 'cacdemo' ); ?></p>
				<!-- /wp:paragraph -->
				<cite>1 Peter 4:10</cite>
			</blockquote>
			<!-- /wp:quote -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"bottom","width":"42%"} -->
		<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:42%">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Not sure where you fit? Tell us a little about yourself and we will help you find a place to serve.', 'cacdemo' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/?topic=serving"><?php esc_html_e( 'Ask about serving', 'cacdemo' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
