<?php
/**
 * Title: More in this series
 * Slug: cacdemo/sermon-series-more
 * Description: Other sermons in the same series as the sermon being viewed (needs the Christlikeness Content plugin). Hidden when there are none.
 * Categories: query
 * Inserter: no
 */

require_once get_theme_file_path( 'inc/sermon-card.php' );
?>
<!-- wp:group {"metadata":{"name":"More in this series"},"align":"full","className":"is-style-section-mist cacdemo-sermon-more","style":{"spacing":{"padding":{"top":"var:preset|spacing|section","bottom":"var:preset|spacing|section"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-mist cacdemo-sermon-more" style="padding-top:var(--wp--preset--spacing--section);padding-bottom:var(--wp--preset--spacing--section)">
<!-- wp:heading {"align":"wide"} -->
<h2 class="wp-block-heading alignwide"><?php esc_html_e( 'More in this series', 'cacdemo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":32,"query":{"perPage":3,"pages":0,"offset":0,"postType":"sermon","order":"desc","orderBy":"date","inherit":false,"cacdemoSameSeries":true},"align":"wide"} -->
<div class="wp-block-query alignwide">
<!-- wp:post-template {"className":"cacdemo-sermon-list cacdemo-sermon-list--grid3"} -->
<?php echo cacdemo_sermon_card_markup( 3 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->
