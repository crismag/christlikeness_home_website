<?php
/**
 * Title: Hero
 * Slug: cacdemo/hero
 * Description: The page's one expressive moment: display heading, short lead, primary action and a secondary link, with an optional identity image.
 * Categories: banner, featured
 * Keywords: hero, welcome, intro
 * Viewport Width: 1440
 */
?>
<!-- wp:group {"metadata":{"name":"Hero"},"align":"full","className":"cacdemo-hero","templateLock":"contentOnly","style":{"spacing":{"padding":{"top":"clamp(2.5rem, 1rem + 4.5vw, 5rem)","bottom":"clamp(3rem, 1rem + 5vw, 5.5rem)"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull cacdemo-hero" style="padding-top:clamp(2.5rem, 1rem + 4.5vw, 5rem);padding-bottom:clamp(3rem, 1rem + 5vw, 5.5rem)">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
			<!-- wp:heading {"level":1,"className":"cacdemo-display","fontSize":"display"} -->
			<h1 class="wp-block-heading cacdemo-display has-display-font-size"><?php esc_html_e( 'Looking for a church?', 'cacdemo' ); ?></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"cacdemo-hero-lead","fontSize":"lead"} -->
			<p class="cacdemo-hero-lead has-lead-font-size"><?php esc_html_e( 'Be our guest. Come as you are.', 'cacdemo' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--60)">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/centres/"><?php esc_html_e( 'Find a centre', 'cacdemo' ); ?></a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-text-link"} -->
				<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button" href="/new-here/"><?php esc_html_e( 'Plan your first Sunday', 'cacdemo' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"42%","className":"cacdemo-hero-media"} -->
		<div class="wp-block-column is-vertically-aligned-center cacdemo-hero-media" style="flex-basis:42%">
			<!-- wp:image {"sizeSlug":"large","align":"right"} -->
			<figure class="wp-block-image alignright size-large"><img alt=""/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
