<?php
/**
 * Import sermons from the reviewed source harvests, reconciling sources of the same service.
 *
 *   wp --path=<WP_ROOT> eval-file scripts/import-sermons.php [dry-run] [draft|publish]
 *
 * Inputs (content-source/sermon-harvest/):
 *   facebook/videos.json  (scripts/harvest-facebook.py; stills cached in facebook/stills/)
 *   youtube/videos.json   (scripts/harvest-youtube.py)
 *   decisions.json        manual rulings for items the matcher cannot decide (optional)
 *   enrichment.json       speaker / scripture found as written evidence (poster text, clip titles), filled when empty
 * Writes: content-source/sermon-harvest/import-report.md (when the repository is writable).
 *
 * One sermon per service; each platform upload is a row in the sermon's Sources. For every
 * harvested item marked "sermon":
 *   1. its source ID is already on a sermon            -> nothing to do
 *   2. decisions.json names the sermon it belongs to   -> add it there (or "new" / "skip"; "date" corrects the service date)
 *   3. exactly one confident match among sermons        -> add it as another source
 *        same service date, same campus (or unknown), same series/title, parts equal or one unnumbered; or
 *        within 7 days, same series and part number, lengths within 3 minutes
 *   4. conflicting evidence (e.g. same date and length, different part number) -> review, nothing written
 *   5. no match                                          -> new sermon: title, date, series, location,
 *                                                           source row, one saved still (featured image)
 * Covers: YouTube stills (series posters) replace importer-set Facebook stills, also for known sources.
 * Existing sermons are never overwritten: only empty values are filled and sources appended.
 * Facebook is processed before YouTube because its post dates are the service dates.
 */

$dry_run = in_array( 'dry-run', $args, true );
$status  = in_array( 'draft', $args, true ) ? 'draft' : 'publish';
$base    = dirname( __DIR__ ) . '/content-source/sermon-harvest';

if ( ! post_type_exists( 'sermon' ) || ! function_exists( 'add_row' ) ) {
	WP_CLI::error( 'The sermon post type and Secure Custom Fields are required.' );
}
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
if ( $admins ) {
	wp_set_current_user( $admins[0]->ID );
}

$decisions = is_readable( "$base/decisions.json" ) ? json_decode( file_get_contents( "$base/decisions.json" ), true ) : array();
$decisions = $decisions['items'] ?? array();
$enrichment = is_readable( "$base/enrichment.json" ) ? json_decode( file_get_contents( "$base/enrichment.json" ), true ) : array();
$enrichment = $enrichment['items'] ?? array();

/* ---------- Helpers ---------- */

function cs_key( $text ) {
	return preg_replace( '/[^a-z0-9]+/', '', strtolower( remove_accents( (string) $text ) ) );
}

/** Part number from a title: "Called to Follow X: Relationship…" → 10, "Holy Spirit (North York)" → null. */
function cs_part( $title ) {
	$roman = array( 'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12, 'XIII' => 13, 'XIV' => 14, 'XV' => 15 );
	$main  = preg_replace( '/\s*\([^)]*\)\s*$/', '', (string) $title );
	$main  = preg_match( '/\s[IVX]+:/', $main ) ? strstr( $main, ':', true ) : $main;
	return preg_match( '/\s([IVX]+)$/', trim( $main ), $m ) && isset( $roman[ $m[1] ] ) ? $roman[ $m[1] ] : null;
}

/** Service label in a title, e.g. "(Radical Service)", "(North York)"; '' when none. */
function cs_label( $title ) {
	return preg_match( '/\(([^)]+)\)\s*$/', (string) $title, $m ) ? strtolower( trim( $m[1] ) ) : '';
}

function cs_title_key( $title ) {
	$t = preg_replace( '/\s*\([^)]*\)\s*$/', '', (string) $title );
	$t = preg_replace( '/\s+[IVX]+(?=$|:)/', '', $t );
	return cs_key( $t );
}

function cs_sermon_by_source( $source_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT m.post_id FROM $wpdb->postmeta m JOIN $wpdb->posts p ON p.ID = m.post_id
		 WHERE p.post_type = 'sermon' AND p.post_status <> 'trash' AND m.meta_key LIKE %s AND m.meta_value = %s LIMIT 1",
		$wpdb->esc_like( 'sermon_sources_' ) . '%' . $wpdb->esc_like( '_source_id' ),
		$source_id
	) );
}

function cs_centre_id( $campus ) {
	if ( ! $campus ) {
		return 0;
	}
	$centre = get_page_by_path( sanitize_title( $campus ), OBJECT, 'centre' );
	return $centre ? $centre->ID : 0;
}

/** What the matcher knows about an existing sermon. */
function cs_profile( $post ) {
	$series    = wp_get_post_terms( $post->ID, 'sermon_series', array( 'fields' => 'names' ) );
	$location  = (int) get_field( 'sermon_location', $post->ID, false );
	$durations = array();
	foreach ( get_field( 'sermon_sources', $post->ID ) ?: array() as $row ) {
		if ( ! empty( $row['duration'] ) ) {
			$durations[] = (int) $row['duration'];
		}
	}
	return array(
		'id'        => $post->ID,
		'title'     => $post->post_title,
		'date'      => substr( $post->post_date, 0, 10 ),
		'series'    => $series ? cs_key( $series[0] ) : '',
		'title_key' => cs_title_key( $post->post_title ),
		'part'      => cs_part( $post->post_title ),
		'campus'    => $location ? get_the_title( $location ) : '',
		'durations' => $durations,
	);
}

function cs_close_length( $profile, $seconds, $tolerance ) {
	foreach ( $profile['durations'] as $d ) {
		if ( abs( $d - $seconds ) <= $tolerance ) {
			return true;
		}
	}
	return false;
}

/** Returns array( 'match' => [profiles], 'review' => [ [profile, reason] ] ). */
function cs_candidates( $item ) {
	$s    = $item['sermon'];
	$date = new DateTimeImmutable( $s['date'] );
	$near = get_posts( array(
		'post_type'   => 'sermon',
		'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'numberposts' => -1,
		'date_query'  => array( array( 'after' => $date->modify( '-7 days' )->format( 'Y-m-d' ), 'before' => $date->modify( '+7 days' )->format( 'Y-m-d 23:59:59' ), 'inclusive' => true ) ),
	) );
	$out  = array( 'match' => array(), 'review' => array() );
	$key  = $s['series'] ? cs_key( $s['series'] ) : cs_title_key( $s['title'] );
	$part = $s['part'] ?? null;
	$len  = (int) ( $item['duration_seconds'] ?? 0 );

	foreach ( $near as $post ) {
		$p          = cs_profile( $post );
		$same_title = $key && ( $key === $p['series'] || $key === $p['title_key'] );
		$parts_ok   = null === $part || null === $p['part'] || $part === $p['part'];
		$campus_ok  = ( empty( $s['campus'] ) || '' === $p['campus'] || $s['campus'] === $p['campus'] )
			&& cs_label( $s['title'] ) === cs_label( $p['title'] ); // A labelled service (e.g. Radical Service) is a different service.
		$length_ok  = $len && cs_close_length( $p, $len, 180 );

		if ( $p['date'] === $s['date'] ) {
			if ( ! $campus_ok ) {
				continue; // Two campuses, or a main and a Radical service, on one date are separate sermons.
			}
			if ( $same_title && $parts_ok ) {
				$out['match'][] = $p;
			} elseif ( $length_ok ) {
				$out['review'][] = array( $p, 'same date and length (' . round( $len / 60 ) . ' min) but title/part differ: "' . $s['title'] . '" vs "' . $p['title'] . '"' );
			}
		} elseif ( $same_title && null !== $part && $part === $p['part'] && $length_ok && $campus_ok ) {
			$out['match'][] = $p + array( 'date_differs' => true );
		}
	}
	return $out;
}

function cs_source_row( $item, $platform ) {
	return array(
		'media'     => 'video',
		'platform'  => $platform,
		'url'       => $item['url'],
		'label'     => '',
		// Stored in the site's timezone, like every other date field.
		'published' => ! empty( $item['published'] ) ? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $item['published'] ) ) ) : '',
		'duration'  => (int) ( $item['duration_seconds'] ?? 0 ),
		'source_id' => "$platform:{$item['id']}",
	);
}

/**
 * Cover still: one saved image per sermon (featured image), from the harvest's stills/ cache.
 * YouTube stills carry the series poster, so they win over Facebook stills. A cover the importer set
 * earlier is replaced by a better-ranked source (and the old image deleted); a featured image an
 * editor chose is never touched. Returns true when the cover changed.
 */
/** "YYYYMMDD_Title_Words" from the sermon record (ASCII, underscores, shortened on a word boundary). */
function cs_file_stem( $post_id, $max = 60 ) {
	$post  = get_post( $post_id );
	preg_match_all( '/[A-Za-z0-9]+/', remove_accents( html_entity_decode( $post->post_title, ENT_QUOTES ) ), $m );
	$slug = '';
	foreach ( $m[0] as $word ) {
		$next = '' === $slug ? $word : "{$slug}_{$word}";
		if ( strlen( $next ) > $max ) {
			break;
		}
		$slug = $next;
	}
	return str_replace( '-', '', substr( $post->post_date, 0, 10 ) ) . '_' . ( '' === $slug ? 'Untitled' : $slug );
}

function cs_still_rank( $platform ) {
	return array( 'youtube' => 2, 'facebook' => 1 )[ $platform ] ?? 0;
}

function cs_attach_still( $post_id, $item, $platform, $base ) {
	$file = ! empty( $item['still_file'] ) ? "$base/$platform/{$item['still_file']}" : '';
	if ( ! $file || ! is_readable( $file ) ) {
		return false;
	}
	$current = (int) get_post_thumbnail_id( $post_id );
	if ( $current ) {
		$from = (string) get_post_meta( $current, '_cacdemo_still_source', true );
		if ( '' === $from && preg_match( '/-(facebook|youtube)-([\w-]+)\.jpg$/', (string) get_post_meta( $current, '_wp_attached_file', true ), $m ) ) {
			$from = "{$m[1]}:{$m[2]}"; // Imported before the source was recorded on the attachment.
		}
		if ( '' === $from ) {
			return false; // Chosen by an editor.
		}
		if ( cs_still_rank( strstr( $from, ':', true ) ) >= cs_still_rank( $platform ) ) {
			return false;
		}
	}
	$tmp = wp_tempnam( basename( $file ) );
	copy( $file, $tmp );
	$name = cs_file_stem( $post_id ) . '.' . pathinfo( $file, PATHINFO_EXTENSION ); // Named from the sermon record; filed under its date's YYYY/MM.
	$id   = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $post_id, get_the_title( $post_id ) );
	if ( is_wp_error( $id ) ) {
		return false;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', '' );
	update_post_meta( $id, '_cacdemo_still_source', "$platform:{$item['id']}" );
	set_post_thumbnail( $post_id, $id );
	if ( $current ) {
		wp_delete_attachment( $current, true );
	}
	return true;
}

/**
 * Rename an importer-set cover to YYYYMMDD_Title_Words.jpg from the sermon record (so a corrected title or
 * date renames it), with its resized copies, inside the sermon date's YYYY/MM upload folder.
 * Editor-chosen images are never renamed.
 */
function cs_normalise_cover( $post_id, $item, $platform, $base ) {
	$att = (int) get_post_thumbnail_id( $post_id );
	if ( ! $att || "$platform:{$item['id']}" !== (string) get_post_meta( $att, '_cacdemo_still_source', true ) ) {
		if ( ! $att || ! preg_match( "/-{$platform}-" . preg_quote( $item['id'], '/' ) . '\.jpg$/', (string) get_post_meta( $att, '_wp_attached_file', true ) ) ) {
			return false;
		}
		update_post_meta( $att, '_cacdemo_still_source', "$platform:{$item['id']}" );
	}
	$want = cs_file_stem( $post_id );
	$file = get_attached_file( $att );
	if ( ! $file || ! is_file( $file ) || pathinfo( $file, PATHINFO_FILENAME ) === $want ) {
		return false;
	}
	$date    = get_post( $post_id )->post_date;
	$uploads = wp_upload_dir( $date );
	wp_mkdir_p( $uploads['path'] );
	$ext     = pathinfo( $file, PATHINFO_EXTENSION );
	$new     = $uploads['path'] . '/' . wp_unique_filename( $uploads['path'], "$want.$ext" );
	$old_dir = dirname( $file );
	$old_stem = pathinfo( $file, PATHINFO_FILENAME );
	$new_stem = pathinfo( $new, PATHINFO_FILENAME );
	$meta    = wp_get_attachment_metadata( $att ) ?: array();
	if ( ! rename( $file, $new ) ) {
		return false;
	}
	foreach ( $meta['sizes'] ?? array() as $size => $info ) {
		$renamed = str_replace( $old_stem, $new_stem, $info['file'] );
		if ( is_file( "$old_dir/{$info['file']}" ) ) {
			rename( "$old_dir/{$info['file']}", $uploads['path'] . "/$renamed" );
		}
		$meta['sizes'][ $size ]['file'] = $renamed;
	}
	if ( ! empty( $meta['original_image'] ) && is_file( "$old_dir/{$meta['original_image']}" ) ) {
		$original = str_replace( $old_stem, $new_stem, $meta['original_image'] );
		rename( "$old_dir/{$meta['original_image']}", $uploads['path'] . "/$original" );
		$meta['original_image'] = $original;
	}
	update_attached_file( $att, $new );
	$meta['file'] = _wp_relative_upload_path( $new );
	wp_update_attachment_metadata( $att, $meta );
	wp_update_post( array( 'ID' => $att, 'post_name' => sanitize_title( $new_stem ) ) );
	return true;
}

/**
 * Details found as written evidence (enrichment.json): speaker and scripture, filled only when empty.
 * Returns what was filled.
 */
function cs_enrich( $post_id, $source_id, $enrichment ) {
	$found  = $enrichment[ $source_id ] ?? array();
	$filled = array();
	if ( ! empty( $found['speaker'] ) && ! has_term( '', 'sermon_speaker', $post_id ) ) {
		// The slug is the name without a title, so a later title change does not create a second speaker.
		$slug = $found['speaker_slug'] ?? sanitize_title( $found['speaker'] );
		$term = get_term_by( 'slug', $slug, 'sermon_speaker' );
		if ( ! $term ) {
			$made = wp_insert_term( $found['speaker'], 'sermon_speaker', array( 'slug' => $slug ) );
			$term = is_wp_error( $made ) ? null : get_term( $made['term_id'], 'sermon_speaker' );
		}
		if ( $term ) {
			wp_set_object_terms( $post_id, (int) $term->term_id, 'sermon_speaker' );
		}
		$filled[] = "speaker {$found['speaker']}";
	}
	if ( ! empty( $found['scripture'] ) && '' === (string) get_field( 'sermon_scripture', $post_id, false ) ) {
		update_field( 'sermon_scripture', $found['scripture'], $post_id );
		$filled[] = "scripture {$found['scripture']}";
	}
	return $filled;
}

/* ---------- Run ---------- */

$report  = array( 'created' => array(), 'attached' => array(), 'known' => 0, 'review' => array(), 'flags' => array(), 'covers' => array(), 'renamed' => 0, 'enriched' => array() );
$sources = array( 'facebook' => "$base/facebook/videos.json", 'youtube' => "$base/youtube/videos.json" );

foreach ( $sources as $platform => $file ) {
	if ( ! is_readable( $file ) ) {
		WP_CLI::log( "no $platform harvest ($file)" );
		continue;
	}
	$items = array_filter( json_decode( file_get_contents( $file ), true )['items'] ?? array(), fn( $i ) => 'sermon' === ( $i['classification'] ?? '' ) );
	usort( $items, fn( $a, $b ) => strcmp( $a['sermon']['date'], $b['sermon']['date'] ) );

	foreach ( $items as $item ) {
		$source_id = "$platform:{$item['id']}";
		$s         = $item['sermon'];
		$s['campus'] = $s['campus'] ?? null;
		// A ruling can correct the service date the post gave (e.g. a part posted with the previous week's date).
		if ( ! empty( $decisions[ $source_id ]['date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $decisions[ $source_id ]['date'] ) ) {
			$s['date'] = $decisions[ $source_id ]['date'];
		}
		$item['sermon'] = $s;
		$label     = "{$s['date']} {$s['title']} [$source_id]";

		$known = cs_sermon_by_source( $source_id );
		if ( $known ) {
			++$report['known'];
			if ( ! $dry_run && ( $filled = cs_enrich( $known, $source_id, $enrichment ) ) ) {
				$report['enriched'][] = "#$known {$s['title']} — " . implode( ', ', $filled ) . " ($source_id)";
			}
			if ( ! $dry_run && cs_attach_still( $known, $item, $platform, $base ) ) {
				$report['covers'][] = "#$known {$s['title']} — cover now from $source_id";
			} elseif ( ! $dry_run && cs_normalise_cover( $known, $item, $platform, $base ) ) {
				++$report['renamed'];
			}
			continue;
		}

		$decision = $decisions[ $source_id ] ?? null;
		$target   = 0;
		if ( is_array( $decision ) && ! empty( $decision['same_as'] ) ) {
			$target = cs_sermon_by_source( $decision['same_as'] );
			if ( ! $target ) {
				$report['review'][] = "$label — decision says same as {$decision['same_as']}, which is not imported yet";
				continue;
			}
		} elseif ( is_array( $decision ) && 'skip' === ( $decision['action'] ?? '' ) ) {
			continue;
		} elseif ( ! ( is_array( $decision ) && 'new' === ( $decision['action'] ?? '' ) ) ) {
			$conflict = array_values( array_filter( $item['flags'] ?? array(), fn( $f ) => str_starts_with( $f, 'date-conflict' ) ) );
			if ( $conflict ) {
				// Two uploads claim the same service date and cannot be told apart: a person decides.
				$report['review'][] = "$label — {$conflict[0]}";
				continue;
			}
			$c = cs_candidates( $item );
			if ( count( $c['match'] ) === 1 ) {
				$target = $c['match'][0]['id'];
				if ( ! empty( $c['match'][0]['date_differs'] ) ) {
					$report['flags'][] = "$label — matched #{$target} \"{$c['match'][0]['title']}\" dated {$c['match'][0]['date']}; date kept";
				}
				foreach ( $c['match'][0]['durations'] as $d ) {
					if ( abs( $d - (int) $item['duration_seconds'] ) > 300 ) {
						$report['flags'][] = "$label — matched #{$target} although lengths differ (" . round( $d / 60 ) . ' vs ' . round( $item['duration_seconds'] / 60 ) . ' min)';
						break;
					}
				}
			} elseif ( count( $c['match'] ) > 1 ) {
				$report['review'][] = "$label — matches several sermons: " . implode( ', ', array_map( fn( $p ) => "#{$p['id']} {$p['date']} {$p['title']}", $c['match'] ) );
				continue;
			} elseif ( $c['review'] ) {
				$report['review'][] = "$label — " . implode( '; ', array_map( fn( $r ) => "#{$r[0]['id']}: {$r[1]}", $c['review'] ) );
				continue;
			}
		}

		if ( $dry_run ) {
			$report[ $target ? 'attached' : 'created' ][] = $target ? "$label → #$target" : $label;
			continue;
		}

		if ( $target ) {
			add_row( 'sermon_sources', cs_source_row( $item, $platform ), $target );
			if ( ! get_field( 'sermon_location', $target, false ) && cs_centre_id( $s['campus'] ) ) {
				update_field( 'sermon_location', cs_centre_id( $s['campus'] ), $target );
			}
			if ( ! empty( $s['series'] ) && ! has_term( '', 'sermon_series', $target ) ) {
				wp_set_object_terms( $target, $s['series'], 'sermon_series' );
			}
			if ( cs_attach_still( $target, $item, $platform, $base ) && has_post_thumbnail( $target ) ) {
				$report['covers'][] = "#$target {$s['title']} — cover now from $source_id";
			}
			cs_enrich( $target, $source_id, $enrichment );
			$report['attached'][] = "$label → #$target";
		} else {
			$id = wp_insert_post( array(
				'post_type'   => 'sermon',
				'post_status' => $status,
				'post_title'  => $s['title'],
				'post_date'   => sprintf( '%s 10:%02d:00', $s['date'], min( 59, (int) ( $s['part'] ?? 0 ) ) ),
			), true );
			if ( is_wp_error( $id ) ) {
				WP_CLI::warning( "$label — " . $id->get_error_message() );
				continue;
			}
			add_row( 'sermon_sources', cs_source_row( $item, $platform ), $id );
			update_field( 'sermon_language', 'English', $id ); // Default; editors correct Tagalog / Taglish services.
			if ( cs_centre_id( $s['campus'] ) ) {
				update_field( 'sermon_location', cs_centre_id( $s['campus'] ), $id );
			}
			if ( ! empty( $s['series'] ) ) {
				$existing = null;
				foreach ( get_terms( array( 'taxonomy' => 'sermon_series', 'hide_empty' => false ) ) as $term ) {
					if ( cs_key( $term->name ) === cs_key( $s['series'] ) ) {
						$existing = $term;
						break;
					}
				}
				wp_set_object_terms( $id, $existing ? (int) $existing->term_id : $s['series'], 'sermon_series' );
			}
			cs_attach_still( $id, $item, $platform, $base );
			cs_enrich( $id, $source_id, $enrichment );
			$report['created'][] = "$label → #$id";
		}
		foreach ( $item['flags'] ?? array() as $flag ) {
			$report['flags'][] = "$label — $flag";
		}
	}
}

$summary = sprintf( '%s%d created, %d sources added to existing sermons, %d already known, %d covers updated, %d cover files renamed, %d sermons given details, %d for review, %d flags.',
	$dry_run ? 'DRY RUN: ' : '', count( $report['created'] ), count( $report['attached'] ), $report['known'], count( $report['covers'] ), $report['renamed'], count( $report['enriched'] ), count( $report['review'] ), count( $report['flags'] ) );

$md = array( '# Sermon import report', '', gmdate( 'Y-m-d H:i' ) . ' UTC — ' . $summary, '' );
foreach ( array( 'review' => 'For review (nothing written)', 'flags' => 'Flags', 'covers' => 'Covers updated', 'enriched' => 'Details from written evidence', 'attached' => 'Sources added to existing sermons', 'created' => 'Created' ) as $k => $h ) {
	$md[] = "## $h";
	$md[] = '';
	foreach ( $report[ $k ] ?: array( '(none)' ) as $line ) {
		$md[] = "- $line";
	}
	$md[] = '';
}
if ( ! $dry_run && is_writable( $base ) && ( $report['created'] || $report['attached'] || $report['covers'] || $report['enriched'] || $report['flags'] || ! is_file( "$base/import-report.md" ) ) ) {
	file_put_contents( "$base/import-report.md", implode( "\n", $md ) );
}
foreach ( array( 'review', 'flags', 'covers', 'enriched', 'attached' ) as $k ) {
	foreach ( $report[ $k ] as $line ) {
		WP_CLI::log( strtoupper( $k ) . ": $line" );
	}
}
WP_CLI::success( $summary );
