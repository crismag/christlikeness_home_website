<?php
/**
 * Title: Featured sermons
 * Slug: cacdemo/sermon-featured
 * Description: Up to three sermons an admin marked Featured (Sermon details → Featured), above the sermon collection. Hidden when none are featured or a visitor filters the list.
 * Categories: query
 * Inserter: no
 */

require_once get_theme_file_path( 'inc/sermon-card.php' );
?>
<!-- wp:group {"metadata":{"name":"Featured sermons"},"align":"wide","className":"cacdemo-sermon-featured","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide cacdemo-sermon-featured">
<!-- wp:heading {"fontSize":"subheading"} -->
<h2 class="wp-block-heading has-subheading-font-size"><?php esc_html_e( 'Featured sermons', 'cacdemo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:query {"queryId":33,"query":{"perPage":3,"pages":1,"offset":0,"postType":"sermon","order":"desc","orderBy":"date","inherit":false,"cacdemoFeatured":true}} -->
<div class="wp-block-query">
<!-- wp:post-template {"className":"cacdemo-sermon-list cacdemo-sermon-list--grid3"} -->
<?php echo cacdemo_sermon_card_markup( 3 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->
