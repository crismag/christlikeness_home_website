<?php
/**
 * Title: Centre visit strip
 * Slug: cacdemo/centre-visit
 * Description: Sunday service times and addresses generated from published centre records. Place it right after the hero on Home or New Here. Shows nothing until a centre is published.
 * Categories: featured, text
 * Keywords: centres, locations, service times, visit
 * Viewport Width: 1440
 */
?>
<!-- wp:group {"metadata":{"name":"Centre visit strip"},"align":"full","className":"cacdemo-centres","style":{"spacing":{"padding":{"bottom":"clamp(3rem, 1rem + 5vw, 5.5rem)"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull cacdemo-centres" style="padding-bottom:clamp(3rem, 1rem + 5vw, 5.5rem)">
	<!-- wp:group {"align":"wide","className":"cacdemo-visit","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide cacdemo-visit">
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30","padding":{"top":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
		<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--40)">
			<!-- wp:heading {"fontSize":"subheading"} -->
			<h2 class="wp-block-heading has-subheading-font-size"><?php esc_html_e( 'Sunday services', 'cacdemo' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-text-link"} -->
				<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button" href="/centres/"><?php esc_html_e( 'All centres and directions', 'cacdemo' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

		<!-- wp:query {"queryId":20,"query":{"perPage":20,"pages":0,"offset":0,"postType":"centre","order":"asc","orderBy":"menu_order","inherit":false}} -->
		<div class="wp-block-query">
			<!-- wp:post-template {"className":"cacdemo-visit__list","layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
				<!-- wp:group {"className":"cacdemo-centre cacdemo-visit__item","style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
				<div class="wp-block-group cacdemo-centre cacdemo-visit__item" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
					<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"baseline"}} -->
					<div class="wp-block-group">
						<!-- wp:post-title {"level":3,"className":"cacdemo-centre__name","fontSize":"body"} /-->
						<!-- wp:group {"className":"cacdemo-inline cacdemo-centre__when","style":{"spacing":{"blockGap":"0.3em"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
						<div class="wp-block-group cacdemo-inline cacdemo-centre__when">
							<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_service_day"}}}}} -->
							<p></p>
							<!-- /wp:paragraph -->
							<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_service_time"}}}}} -->
							<p></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"cacdemo-inline cacdemo-inline--comma cacdemo-meta","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
					<div class="wp-block-group cacdemo-inline cacdemo-inline--comma cacdemo-meta">
						<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_street"}}}}} -->
						<p></p>
						<!-- /wp:paragraph -->
						<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_city"}}}}} -->
						<p></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			<!-- /wp:post-template -->
		</div>
		<!-- /wp:query -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
