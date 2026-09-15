<?php
/**
 * Title: Statement with scripture
 * Slug: cacdemo/statement
 * Description: Mist band with a section heading and scripture on one side and a lead statement, supporting text and actions on the other.
 * Categories: text, featured
 * Keywords: about, mission, welcome, scripture
 * Viewport Width: 1440
 */
?>
<!-- wp:group {"metadata":{"name":"Statement with scripture"},"align":"full","className":"is-style-section-mist","templateLock":"contentOnly","style":{"spacing":{"padding":{"top":"var:preset|spacing|section","bottom":"var:preset|spacing|section"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-mist" style="padding-top:var(--wp--preset--spacing--section);padding-bottom:var(--wp--preset--spacing--section)">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70","top":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"width":"40%"} -->
		<div class="wp-block-column" style="flex-basis:40%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Section heading', 'cacdemo' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:quote {"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
			<blockquote class="wp-block-quote" style="margin-top:var(--wp--preset--spacing--60)">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Scripture text.', 'cacdemo' ); ?></p>
				<!-- /wp:paragraph -->
				<cite><?php esc_html_e( 'Reference', 'cacdemo' ); ?></cite>
			</blockquote>
			<!-- /wp:quote -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"60%"} -->
		<div class="wp-block-column" style="flex-basis:60%">
			<!-- wp:group {"style":{"dimensions":{"minHeight":""}},"layout":{"type":"constrained","contentSize":"38rem","justifyContent":"left"}} -->
			<div class="wp-block-group">
				<!-- wp:paragraph {"fontSize":"lead"} -->
				<p class="has-lead-font-size"><?php esc_html_e( 'A short lead statement.', 'cacdemo' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'Supporting text.', 'cacdemo' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
				<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--60)">
					<!-- wp:button -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/about/"><?php esc_html_e( 'Primary action', 'cacdemo' ); ?></a></div>
					<!-- /wp:button -->

					<!-- wp:button {"className":"is-style-text-link"} -->
					<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Secondary link', 'cacdemo' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
