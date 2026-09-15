<?php
/**
 * Sync Secure Custom Fields definitions between config/scf/*.json and the database.
 *
 *   wp --path=<WP_ROOT> eval-file scripts/scf-sync.php import   # JSON in Git -> WordPress
 *   wp --path=<WP_ROOT> eval-file scripts/scf-sync.php export   # WordPress   -> JSON in Git
 *
 * Only definitions (post types, taxonomies, field groups) are synced — never content.
 * Import is idempotent: existing definitions with the same key are updated in place, and
 * fields no longer in a field group's JSON are removed.
 */

if ( ! function_exists( 'acf_import_field_group' ) ) {
	WP_CLI::error( 'Secure Custom Fields is not active.' );
}

$mode = $args[0] ?? '';
$dir  = dirname( __DIR__ ) . '/config/scf';

$kinds = array(
	'post-types'   => 'acf-post-type',
	'taxonomies'   => 'acf-taxonomy',
	'field-groups' => 'acf-field-group',
);

if ( 'import' === $mode ) {
	foreach ( $kinds as $sub => $post_type ) {
		foreach ( glob( "$dir/$sub/*.json" ) ?: array() as $file ) {
			$definition = json_decode( file_get_contents( $file ), true );
			if ( ! is_array( $definition ) || empty( $definition['key'] ) ) {
				WP_CLI::error( "Invalid definition: $file" );
			}
			if ( 'acf-field-group' === $post_type ) {
				$existing = acf_get_field_group( $definition['key'] );
				if ( $existing ) {
					$definition['ID'] = $existing['ID'];
				}
				acf_import_field_group( $definition );
				// Fields removed from the definition are removed from WordPress too (values stay in post meta).
				$wanted = array();
				$walk   = function ( $fields ) use ( &$walk, &$wanted ) {
					foreach ( $fields as $field ) {
						$wanted[ $field['key'] ] = true;
						$walk( $field['sub_fields'] ?? array() );
					}
				};
				$walk( $definition['fields'] ?? array() );
				$stale = function ( $parent ) use ( &$stale, $wanted ) {
					foreach ( acf_get_fields( $parent ) ?: array() as $field ) {
						if ( ! isset( $wanted[ $field['key'] ] ) ) {
							acf_delete_field( $field['ID'] );
							WP_CLI::log( "  removed field {$field['name']}" );
						} elseif ( ! empty( $field['sub_fields'] ) ) {
							$stale( $field );
						}
					}
				};
				$stale( acf_get_field_group( $definition['key'] ) );
			} else {
				$existing = acf_get_internal_post_type( $definition['key'], $post_type );
				if ( $existing ) {
					$definition['ID'] = $existing['ID'];
				}
				acf_import_internal_post_type( $definition, $post_type );
			}
			WP_CLI::log( "imported $sub/" . basename( $file ) );
		}
	}
	flush_rewrite_rules( false );
	WP_CLI::success( 'SCF definitions imported.' );
} elseif ( 'export' === $mode ) {
	foreach ( $kinds as $sub => $post_type ) {
		wp_mkdir_p( "$dir/$sub" );
		if ( 'acf-field-group' === $post_type ) {
			$items = array_filter( acf_get_field_groups(), fn( $g ) => ! empty( $g['ID'] ) );
			foreach ( $items as $group ) {
				$group['fields'] = acf_get_fields( $group );
				$export          = acf_prepare_field_group_for_export( $group );
				$name            = sanitize_title( $group['title'] );
				file_put_contents( "$dir/$sub/$name.json", wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
				WP_CLI::log( "exported $sub/$name.json" );
			}
		} else {
			foreach ( acf_get_internal_post_type_posts( $post_type ) as $item ) {
				if ( empty( $item['ID'] ) ) {
					continue;
				}
				$export = acf_prepare_internal_post_type_for_export( $item, $post_type );
				$name   = sanitize_title( 'acf-post-type' === $post_type ? $item['post_type'] : $item['taxonomy'] );
				file_put_contents( "$dir/$sub/$name.json", wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
				WP_CLI::log( "exported $sub/$name.json" );
			}
		}
	}
	WP_CLI::success( "SCF definitions exported to $dir." );
} else {
	WP_CLI::error( 'Usage: wp eval-file scripts/scf-sync.php import|export' );
}
