<?php
/**
 * Title: Sermon archive
 * Slug: cacdemo/sermon-archive
 * Description: Sermons in the current series, speaker or topic archive, with the view switch. Used by the sermon taxonomy templates.
 * Categories: query
 * Inserter: no
 */

require_once get_theme_file_path( 'inc/sermon-card.php' );
?>
<!-- wp:group {"metadata":{"name":"Sermon archive"},"align":"wide","className":"cacdemo-sermons","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide cacdemo-sermons">
<!-- wp:cacdemo/sermon-filters /-->

<!-- wp:query {"queryId":31,"query":{"perPage":12,"pages":0,"offset":0,"postType":"sermon","order":"desc","orderBy":"date","inherit":true}} -->
<div class="wp-block-query">
<!-- wp:post-template {"className":"cacdemo-sermon-list"} -->
<?php echo cacdemo_sermon_card_markup( 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<!-- /wp:post-template -->

<!-- wp:query-pagination {"className":"cacdemo-pagination","layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:query-pagination-previous {"label":"Newer"} /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next {"label":"Older"} /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p><?php esc_html_e( 'No sermons here yet.', 'cacdemo' ); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->

<!-- wp:cacdemo/sermon-table /-->
</div>
<!-- /wp:group -->
