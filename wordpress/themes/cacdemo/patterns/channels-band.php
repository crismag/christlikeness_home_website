<?php
/**
 * Title: Stay connected
 * Slug: cacdemo/channels-band
 * Description: Mist band with the church's social pages as icon links (from Social channels) and a link to Follow us.
 * Categories: call-to-action
 * Keywords: facebook, social, follow, connect
 * Viewport Width: 1440
 */
?>
<!-- wp:group {"metadata":{"name":"Stay connected"},"align":"full","className":"cacdemo-channels-band is-style-section-mist","style":{"spacing":{"padding":{"top":"var:preset|spacing|section","bottom":"var:preset|spacing|section"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull cacdemo-channels-band is-style-section-mist" style="padding-top:var(--wp--preset--spacing--section);padding-bottom:var(--wp--preset--spacing--section)">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70","top":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"38%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:38%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Stay connected', 'cacdemo' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Follow our church, our centres and our youth on Facebook.', 'cacdemo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"62%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:62%">
			<!-- wp:cacdemo/channels {"variant":"compact","homeOnly":true} /-->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
				<!-- wp:button {"className":"is-style-text-link"} -->
				<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button" href="/follow-us/"><?php esc_html_e( 'See recent posts', 'cacdemo' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
