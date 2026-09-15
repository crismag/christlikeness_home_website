<?php
/**
 * Title: Worship band
 * Slug: cacdemo/worship-band
 * Description: Night band with one arch-cropped worship photo beside a heading and divided rows (e.g. music projects). Use at most one arch per page.
 * Categories: featured, media
 * Keywords: worship, music, dark, night
 * Viewport Width: 1440
 */
?>
<!-- wp:group {"metadata":{"name":"Worship band"},"align":"full","className":"is-style-section-night","templateLock":"contentOnly","style":{"spacing":{"padding":{"top":"var:preset|spacing|section","bottom":"var:preset|spacing|section"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-night" style="padding-top:var(--wp--preset--spacing--section);padding-bottom:var(--wp--preset--spacing--section)">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70","top":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
			<!-- wp:image {"sizeSlug":"large","className":"is-style-arch","style":{"layout":{"selfStretch":"fit"}}} -->
			<figure class="wp-block-image size-large is-style-arch"><img alt=""/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Section heading', 'cacdemo' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:group {"className":"is-style-rows","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"},"blockGap":"0"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group is-style-rows" style="margin-top:var(--wp--preset--spacing--60)">
				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"fontSize":"subheading"} -->
					<h3 class="wp-block-heading has-subheading-font-size"><?php esc_html_e( 'Item title', 'cacdemo' ); ?></h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph -->
					<p><?php esc_html_e( 'One or two sentences.', 'cacdemo' ); ?></p>
					<!-- /wp:paragraph -->
					<!-- wp:buttons -->
					<div class="wp-block-buttons">
						<!-- wp:button {"className":"is-style-text-link"} -->
						<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Link text', 'cacdemo' ); ?></a></div>
						<!-- /wp:button -->
					</div>
					<!-- /wp:buttons -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"fontSize":"subheading"} -->
					<h3 class="wp-block-heading has-subheading-font-size"><?php esc_html_e( 'Item title', 'cacdemo' ); ?></h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph -->
					<p><?php esc_html_e( 'One or two sentences.', 'cacdemo' ); ?></p>
					<!-- /wp:paragraph -->
					<!-- wp:buttons -->
					<div class="wp-block-buttons">
						<!-- wp:button {"className":"is-style-text-link"} -->
						<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Link text', 'cacdemo' ); ?></a></div>
						<!-- /wp:button -->
					</div>
					<!-- /wp:buttons -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
