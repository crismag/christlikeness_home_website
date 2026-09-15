<?php
/**
 * Title: Call to action band
 * Slug: cacdemo/cta-band
 * Description: Mist band with one sentence and one primary action, plus an optional text link.
 * Categories: call-to-action
 * Keywords: cta, contact, visit, next step
 * Viewport Width: 1440
 */
?>
<!-- wp:group {"metadata":{"name":"Call to action band"},"align":"full","className":"is-style-section-mist","templateLock":"contentOnly","style":{"spacing":{"padding":{"top":"var:preset|spacing|section","bottom":"var:preset|spacing|section"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-mist" style="padding-top:var(--wp--preset--spacing--section);padding-bottom:var(--wp--preset--spacing--section)">
	<!-- wp:columns {"verticalAlignment":"bottom","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70","top":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-bottom">
		<!-- wp:column {"verticalAlignment":"bottom","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:58%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Section heading', 'cacdemo' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'One sentence that explains the next step.', 'cacdemo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"bottom","width":"42%"} -->
		<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:42%">
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Primary action', 'cacdemo' ); ?></a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-text-link"} -->
				<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button" href="/contact/"><?php esc_html_e( 'Contact us', 'cacdemo' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
