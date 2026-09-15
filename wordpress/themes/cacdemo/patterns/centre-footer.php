<?php
/**
 * Title: Footer centres
 * Slug: cacdemo/centre-footer
 * Description: Footer row of published centres (service, address, directions), generated from centre records. Hidden until a centre is published.
 * Categories: footer
 * Inserter: no
 */
?>
<!-- wp:group {"metadata":{"name":"Footer centres"},"align":"wide","className":"cacdemo-centres cacdemo-footer-centres","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|70"},"margin":{"bottom":"var:preset|spacing|70"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide cacdemo-centres cacdemo-footer-centres" style="margin-bottom:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|70","top":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"33.33%"} -->
		<div class="wp-block-column" style="flex-basis:33.33%">
			<!-- wp:heading {"level":2,"fontSize":"subheading"} -->
			<h2 class="wp-block-heading has-subheading-font-size"><?php esc_html_e( 'Sunday services', 'cacdemo' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"66.66%"} -->
		<div class="wp-block-column" style="flex-basis:66.66%">
			<!-- wp:query {"queryId":22,"query":{"perPage":20,"pages":0,"offset":0,"postType":"centre","order":"asc","orderBy":"menu_order","inherit":false}} -->
			<div class="wp-block-query">
				<!-- wp:post-template {"layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
					<!-- wp:group {"className":"cacdemo-centre","style":{"spacing":{"blockGap":"var:preset|spacing|10"},"typography":{"fontSize":"1rem","lineHeight":"1.5"}},"textColor":"night-muted","layout":{"type":"default"}} -->
					<div class="wp-block-group cacdemo-centre has-night-muted-color has-text-color" style="font-size:1rem;line-height:1.5">
						<!-- wp:post-title {"level":3,"isLink":true,"style":{"typography":{"fontSize":"1.125rem"}},"textColor":"paper"} /-->
						<!-- wp:group {"className":"cacdemo-inline","style":{"spacing":{"blockGap":"0.3em"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
						<div class="wp-block-group cacdemo-inline">
							<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_service_day"}}}}} -->
							<p></p>
							<!-- /wp:paragraph -->
							<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_service_time"}}}}} -->
							<p></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->
						<!-- wp:group {"className":"cacdemo-inline cacdemo-inline--comma","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
						<div class="wp-block-group cacdemo-inline cacdemo-inline--comma">
							<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_street"}}}}} -->
							<p></p>
							<!-- /wp:paragraph -->
							<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_unit"}}}}} -->
							<p></p>
							<!-- /wp:paragraph -->
							<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_city"}}}}} -->
							<p></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->
						<!-- wp:buttons -->
						<div class="wp-block-buttons">
							<!-- wp:button {"className":"is-style-text-link","metadata":{"bindings":{"url":{"source":"acf/field","args":{"key":"centre_map_url"}}}}} -->
							<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Directions', 'cacdemo' ); ?></a></div>
							<!-- /wp:button -->
						</div>
						<!-- /wp:buttons -->
					</div>
					<!-- /wp:group -->
				<!-- /wp:post-template -->
			</div>
			<!-- /wp:query -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
