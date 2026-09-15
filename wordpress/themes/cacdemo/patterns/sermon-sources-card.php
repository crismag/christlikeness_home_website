<?php
/**
 * Title: Where we post sermons
 * Slug: cacdemo/sermon-sources-card
 * Description: A small card listing the church's YouTube channel and Facebook group, where sermons are posted and can be watched directly.
 * Categories: text
 * Keywords: sermons, youtube, facebook, watch
 * Viewport Width: 1200
 */
?>
<!-- wp:group {"metadata":{"name":"Where we post sermons"},"align":"wide","className":"cacdemo-sources-card","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide cacdemo-sources-card">
<!-- wp:heading {"level":2,"fontSize":"subheading"} -->
<h2 class="wp-block-heading has-subheading-font-size"><?php esc_html_e( 'Where we post sermons', 'cacdemo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Sunday services are shared on our YouTube channel and in our Facebook group. You can also watch them there directly, and follow along for new messages.', 'cacdemo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"cacdemo-sources-card__list"} -->
<ul class="wp-block-list cacdemo-sources-card__list">
<!-- wp:list-item -->
<li><a href="https://www.youtube.com/channel/UCdEsFxptBKsb1j6Q9PaY5jQ">YouTube</a> <?php esc_html_e( 'Christlikeness channel', 'cacdemo' ); ?></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="https://www.facebook.com/groups/975851515926484/">Facebook</a> <?php esc_html_e( 'Christlikeness Online group', 'cacdemo' ); ?></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->
