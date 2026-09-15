<?php
/**
 * Title: Sermon: summary and related resources
 * Slug: cacdemo/sermon-content
 * Description: Starting content for a new sermon: a short summary and a Related resources list for notes, slides, study guides and other shared material.
 * Categories: text
 * Post Types: sermon
 * Block Types: core/post-content
 * Viewport Width: 1200
 */
?>
<!-- wp:paragraph {"fontSize":"lead"} -->
<p class="has-lead-font-size"><?php esc_html_e( 'A two or three sentence summary of the message.', 'cacdemo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"metadata":{"name":"Related resources"},"className":"cacdemo-resources","layout":{"type":"default"}} -->
<div class="wp-block-group cacdemo-resources">
<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Related resources', 'cacdemo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"cacdemo-resource-list"} -->
<ul class="wp-block-list cacdemo-resource-list">
<!-- wp:list-item -->
<li><a href="#"><?php esc_html_e( 'Sermon notes', 'cacdemo' ); ?></a> <?php esc_html_e( 'PDF', 'cacdemo' ); ?></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->
