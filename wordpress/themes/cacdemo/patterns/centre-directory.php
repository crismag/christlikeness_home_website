<?php
/**
 * Title: Centre directory
 * Slug: cacdemo/centre-directory
 * Description: One row per published centre: name, service, address and actions, generated from centre records.
 * Categories: text
 * Keywords: centres, locations, directory, addresses
 * Inserter: no
 */
?>
<!-- wp:group {"metadata":{"name":"Centre directory"},"align":"wide","className":"cacdemo-centres","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide cacdemo-centres">
	<!-- wp:query {"queryId":21,"query":{"perPage":50,"pages":0,"offset":0,"postType":"centre","order":"asc","orderBy":"menu_order","inherit":false}} -->
	<div class="wp-block-query">
		<!-- wp:post-template {"className":"cacdemo-directory"} -->
			<!-- wp:columns {"className":"cacdemo-centre","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|70"},"padding":{"top":"clamp(2rem, 1rem + 3vw, 3.5rem)","bottom":"clamp(2rem, 1rem + 3vw, 3.5rem)"}}}} -->
			<div class="wp-block-columns cacdemo-centre" style="padding-top:clamp(2rem, 1rem + 3vw, 3.5rem);padding-bottom:clamp(2rem, 1rem + 3vw, 3.5rem)">
				<!-- wp:column {"width":"25%"} -->
				<div class="wp-block-column" style="flex-basis:25%">
					<!-- wp:post-title {"level":2,"isLink":true,"fontSize":"heading"} /-->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"50%"} -->
				<div class="wp-block-column" style="flex-basis:50%">
					<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|60"}}}} -->
					<div class="wp-block-columns">
						<!-- wp:column {"className":"cacdemo-fact"} -->
						<div class="wp-block-column cacdemo-fact">
							<!-- wp:paragraph {"className":"cacdemo-fact__label"} -->
							<p class="cacdemo-fact__label"><?php esc_html_e( 'Service', 'cacdemo' ); ?></p>
							<!-- /wp:paragraph -->
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
						</div>
						<!-- /wp:column -->

						<!-- wp:column {"className":"cacdemo-fact"} -->
						<div class="wp-block-column cacdemo-fact">
							<!-- wp:paragraph {"className":"cacdemo-fact__label"} -->
							<p class="cacdemo-fact__label"><?php esc_html_e( 'Address', 'cacdemo' ); ?></p>
							<!-- /wp:paragraph -->
							<!-- wp:group {"className":"cacdemo-inline cacdemo-inline--comma","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
							<div class="wp-block-group cacdemo-inline cacdemo-inline--comma">
								<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_street"}}}}} -->
								<p></p>
								<!-- /wp:paragraph -->
								<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_unit"}}}}} -->
								<p></p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
							<!-- wp:group {"className":"cacdemo-inline","style":{"spacing":{"blockGap":"0.3em"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
							<div class="wp-block-group cacdemo-inline">
								<!-- wp:paragraph {"className":"cacdemo-comma-after","metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_city"}}}}} -->
								<p class="cacdemo-comma-after"></p>
								<!-- /wp:paragraph -->
								<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_province"}}}}} -->
								<p></p>
								<!-- /wp:paragraph -->
								<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"centre_postal_code"}}}}} -->
								<p></p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
						</div>
						<!-- /wp:column -->
					</div>
					<!-- /wp:columns -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"25%"} -->
				<div class="wp-block-column" style="flex-basis:25%">
					<!-- wp:buttons {"layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
					<div class="wp-block-buttons">
						<!-- wp:button -->
						<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/new-here/"><?php esc_html_e( 'Plan a visit', 'cacdemo' ); ?></a></div>
						<!-- /wp:button -->
						<!-- wp:button {"className":"is-style-text-link","metadata":{"bindings":{"url":{"source":"acf/field","args":{"key":"centre_map_url"}}}}} -->
						<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Get directions', 'cacdemo' ); ?></a></div>
						<!-- /wp:button -->
					</div>
					<!-- /wp:buttons -->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		<!-- /wp:post-template -->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph {"className":"cacdemo-meta"} -->
			<p class="cacdemo-meta"><?php esc_html_e( 'Centre details are being confirmed and will appear here soon.', 'cacdemo' ); ?></p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
