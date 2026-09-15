<?php
/**
 * Title: Ministry directory
 * Slug: cacdemo/ministry-directory
 * Description: Every published ministry in menu order: optional photo, name, purpose and its ways to serve. Generated from ministry records.
 * Categories: text
 * Keywords: ministries, serve, directory
 * Inserter: no
 */
?>
<!-- wp:group {"metadata":{"name":"Ministry directory"},"align":"wide","className":"cacdemo-ministries","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide cacdemo-ministries">
	<!-- wp:heading {"className":"screen-reader-text"} -->
	<h2 class="wp-block-heading screen-reader-text"><?php esc_html_e( 'Our ministries', 'cacdemo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:query {"queryId":41,"query":{"perPage":50,"pages":0,"offset":0,"postType":"ministry","order":"asc","orderBy":"menu_order","inherit":false}} -->
	<div class="wp-block-query">
		<!-- wp:post-template {"className":"cacdemo-ministry-list"} -->
			<!-- wp:group {"className":"cacdemo-ministry-item","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"default"}} -->
			<div class="wp-block-group cacdemo-ministry-item">
				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","className":"cacdemo-ministry-item__image"} /-->

				<!-- wp:post-title {"level":3,"isLink":true,"className":"cacdemo-ministry-item__name","fontSize":"heading"} /-->

				<!-- wp:paragraph {"className":"cacdemo-ministry-item__purpose","metadata":{"bindings":{"content":{"source":"acf/field","args":{"key":"ministry_tagline"}}}}} -->
				<p class="cacdemo-ministry-item__purpose"></p>
				<!-- /wp:paragraph -->

				<!-- wp:group {"className":"cacdemo-ministry-item__meta","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
				<div class="wp-block-group cacdemo-ministry-item__meta">
					<!-- wp:paragraph {"className":"cacdemo-badge cacdemo-badge--needed","metadata":{"bindings":{"content":{"source":"cacdemo/ministry","args":{"key":"needed"}}}}} -->
					<p class="cacdemo-badge cacdemo-badge--needed"></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"cacdemo-ministry-item__roles","metadata":{"bindings":{"content":{"source":"cacdemo/ministry","args":{"key":"roles"}}}}} -->
					<p class="cacdemo-ministry-item__roles"></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		<!-- /wp:post-template -->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph {"className":"cacdemo-meta"} -->
			<p class="cacdemo-meta"><?php esc_html_e( 'Ministry details are being prepared and will appear here soon.', 'cacdemo' ); ?></p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
