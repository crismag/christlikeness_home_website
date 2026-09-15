<?php
/**
 * Title: Directory rows
 * Slug: cacdemo/directory-rows
 * Description: A heading and full-width divided rows, each a title with a one-line description. Lists before cards.
 * Categories: text
 * Keywords: next steps, links, directory, list
 * Viewport Width: 1440
 */
?>
<!-- wp:group {"metadata":{"name":"Directory rows"},"align":"full","templateLock":"contentOnly","style":{"spacing":{"padding":{"top":"var:preset|spacing|section","bottom":"var:preset|spacing|section"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--section);padding-bottom:var(--wp--preset--spacing--section)">
	<!-- wp:heading {"align":"wide"} -->
	<h2 class="wp-block-heading alignwide"><?php esc_html_e( 'Section heading', 'cacdemo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"align":"wide","className":"is-style-rows","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"},"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide is-style-rows" style="margin-top:var(--wp--preset--spacing--60)">
		<?php for ( $i = 0; $i < 4; $i++ ) : ?>
		<!-- wp:columns {"verticalAlignment":"top","isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|70"},"margin":{"bottom":"0"}}}} -->
		<div class="wp-block-columns are-vertically-aligned-top" style="margin-bottom:0">
			<!-- wp:column {"width":"36%"} -->
			<div class="wp-block-column" style="flex-basis:36%">
				<!-- wp:heading {"level":3,"fontSize":"subheading"} -->
				<h3 class="wp-block-heading has-subheading-font-size"><a href="#"><?php esc_html_e( 'Row title', 'cacdemo' ); ?></a></h3>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"64%"} -->
			<div class="wp-block-column" style="flex-basis:64%">
				<!-- wp:paragraph -->
				<p><?php esc_html_e( 'A one-line description.', 'cacdemo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
		<?php endfor; ?>
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
