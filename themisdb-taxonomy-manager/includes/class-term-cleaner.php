<?php
/**
 * Term Cleaner
 *
 * Detects nonsensical categories and tags (dates, numbers, single characters,
 * generic words, duplicates) and provides helpers to bulk-delete or merge them.
 *
 * @package ThemisDB_Taxonomy_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ThemisDB_Term_Cleaner {

	/**
	 * Regex patterns that mark a term name as "nonsensical".
	 * Mirrors the exclude patterns from the Python category extractor.
	 *
	 * @var string[]
	 */
	private $nonsensical_patterns = array(
		'/^\d+$/',                                           // Pure numbers: 123
		'/^\d{4}$/',                                         // 4-digit years: 2026
		'/^\d{1,2}$/',                                       // 1-2 digit numbers: 1, 01
		'/^v?\d+\.\d+/',                                     // Version numbers: v1.0, 2.3
		'/^\d+\s+\d+$/',                                     // Date fragments: "9 2026"
		'/^\d{1,2}[\.\/\-]\d{1,2}[\.\/\-]\d{2,4}$/',        // Dates: 01.02.2026
		'/^(de|en|fr|es|ja|zh|ru|pt|it)$/i',                 // Language codes
		'/^(use|tmp|test|demo|example|sample|draft|untitled)$/i', // Generic noise words
		'/^[a-z]$/i',                                        // Single letters
		'/^[\W_]+$/',                                        // Only special characters / underscores
		'/^(page|post|article|blog|site|web)$/i',            // WordPress generic terms
		'/^(http|https|www|ftp)$/i',                         // URL fragments
		'/^(januar|februar|m[äa]rz|april|mai|juni|juli|august|september|oktober|november|dezember)$/i', // German months
		'/^(january|february|march|april|may|june|july|august|september|october|november|december)$/i', // English months
		'/^(monday|tuesday|wednesday|thursday|friday|saturday|sunday)$/i', // English weekdays
		'/^(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)$/i', // German weekdays
		'/^(jan|feb|mar|apr|jun|jul|aug|sep|oct|nov|dec)$/i', // Month abbreviations
	);

	/**
	 * Minimum character length for a term to be considered meaningful.
	 *
	 * @var int
	 */
	private $min_length = 2;

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Detect nonsensical terms in a taxonomy.
	 *
	 * @param string $taxonomy  'category' or 'post_tag' (or any registered taxonomy).
	 * @return array[]  Each entry: { term_id, name, taxonomy, reason, post_count }
	 */
	public function detect_nonsensical_terms( $taxonomy = 'category' ) {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		) );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$nonsensical = array();

		foreach ( $terms as $term ) {
			$reason = $this->get_nonsensical_reason( $term->name );
			if ( $reason !== '' ) {
				$nonsensical[] = array(
					'term_id'    => $term->term_id,
					'name'       => $term->name,
					'taxonomy'   => $taxonomy,
					'reason'     => $reason,
					'post_count' => (int) $term->count,
				);
			}
		}

		return $nonsensical;
	}

	/**
	 * Find pairs of similar terms within a taxonomy.
	 *
	 * @param string $taxonomy            Taxonomy slug.
	 * @param float  $similarity_threshold Minimum similarity 0–1 (default 0.8).
	 * @return array[]  Each entry: { id1, id2, term1, term2, similarity, post_count }
	 */
	public function get_similar_terms( $taxonomy = 'category', $similarity_threshold = 0.8 ) {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		) );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$suggestions = array();
		$processed   = array();

		foreach ( $terms as $t1 ) {
			if ( in_array( $t1->term_id, $processed, true ) ) {
				continue;
			}

			foreach ( $terms as $t2 ) {
				if ( $t1->term_id === $t2->term_id ) {
					continue;
				}
				if ( in_array( $t2->term_id, $processed, true ) ) {
					continue;
				}

				$similarity = $this->calculate_similarity( $t1->name, $t2->name );

				if ( $similarity >= $similarity_threshold ) {
					$suggestions[] = array(
						'id1'        => $t1->term_id,
						'id2'        => $t2->term_id,
						'term1'      => $t1->name,
						'term2'      => $t2->name,
						'similarity' => $similarity,
						'post_count' => (int) $t1->count + (int) $t2->count,
					);
					$processed[] = $t2->term_id;
				}
			}
		}

		return $suggestions;
	}

	/**
	 * Merge term $remove_id into $keep_id within $taxonomy, then delete $remove_id.
	 *
	 * Works for any taxonomy (category, post_tag, or custom).
	 *
	 * @param int    $keep_id    Term to keep.
	 * @param int    $remove_id  Term to remove (posts are reassigned to $keep_id).
	 * @param string $taxonomy   Taxonomy slug.
	 * @return array { posts_moved: int, success: bool, message: string }
	 */
	public function merge_terms( $keep_id, $remove_id, $taxonomy ) {
		$keep_term   = get_term( $keep_id, $taxonomy );
		$remove_term = get_term( $remove_id, $taxonomy );

		if ( is_wp_error( $keep_term ) || is_wp_error( $remove_term ) ) {
			return array(
				'posts_moved' => 0,
				'success'     => false,
				'message'     => __( 'Invalid term IDs.', 'themisdb-taxonomy-manager' ),
			);
		}

		// Find all posts tagged/categorised with the term to remove.
		$posts = get_posts( array(
			'post_type'      => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'any',
			'tax_query'      => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $remove_id,
				),
			),
		) );

		$posts_moved = 0;
		foreach ( $posts as $post_id ) {
			$current = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $current ) ) {
				continue;
			}

			// Add keep_id, remove remove_id.
			$updated = array_values( array_unique( array_merge(
				array_diff( $current, array( $remove_id ) ),
				array( $keep_id )
			) ) );

			wp_set_object_terms( $post_id, $updated, $taxonomy );
			$posts_moved++;
		}

		// Delete the merged term.
		$result = wp_delete_term( $remove_id, $taxonomy );
		if ( is_wp_error( $result ) ) {
			return array(
				'posts_moved' => $posts_moved,
				'success'     => false,
				'message'     => $result->get_error_message(),
			);
		}

		return array(
			'posts_moved' => $posts_moved,
			'success'     => true,
			/* translators: 1: removed term name, 2: kept term name, 3: post count */
			'message'     => sprintf(
				__( 'Merged "%1$s" into "%2$s" (%3$d posts moved).', 'themisdb-taxonomy-manager' ),
				esc_html( $remove_term->name ),
				esc_html( $keep_term->name ),
				$posts_moved
			),
		);
	}

	/**
	 * Delete a list of terms.
	 *
	 * @param int[]  $term_ids  List of term IDs.
	 * @param string $taxonomy  Taxonomy slug.
	 * @return array { deleted: int, skipped: int, errors: string[] }
	 */
	public function delete_terms( array $term_ids, $taxonomy ) {
		$deleted = 0;
		$skipped = 0;
		$errors  = array();

		foreach ( $term_ids as $term_id ) {
			$term_id = (int) $term_id;
			if ( $term_id <= 0 ) {
				$skipped++;
				continue;
			}

			$result = wp_delete_term( $term_id, $taxonomy );
			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
				$skipped++;
			} elseif ( $result ) {
				$deleted++;
			} else {
				$skipped++;
			}
		}

		return array(
			'deleted' => $deleted,
			'skipped' => $skipped,
			'errors'  => $errors,
		);
	}

	/**
	 * Auto-consolidate similar terms within a taxonomy.
	 *
	 * Mirrors the existing `consolidate_categories()` in the analytics class but
	 * works for any taxonomy.
	 *
	 * @param string $taxonomy            Taxonomy slug.
	 * @param float  $similarity_threshold Minimum similarity 0–1.
	 * @return array { total_merged: int, details: array[] }
	 */
	public function consolidate_taxonomy( $taxonomy, $similarity_threshold = 0.8 ) {
		$suggestions = $this->get_similar_terms( $taxonomy, $similarity_threshold );
		$consolidated = array();

		foreach ( $suggestions as $suggestion ) {
			$term1 = get_term( $suggestion['id1'], $taxonomy );
			$term2 = get_term( $suggestion['id2'], $taxonomy );

			if (
				is_wp_error( $term1 ) ||
				is_wp_error( $term2 ) ||
				! ( $term1 instanceof WP_Term ) ||
				! ( $term2 instanceof WP_Term )
			) {
				continue;
			}

			// Keep the term with more posts; break ties by lower ID.
			if ( $term1->count > $term2->count ) {
				$keep_id = $term1->term_id;
				$remove_id = $term2->term_id;
			} elseif ( $term2->count > $term1->count ) {
				$keep_id = $term2->term_id;
				$remove_id = $term1->term_id;
			} else {
				// Equal counts: keep the term with the lower ID (deterministic tie-break).
				// Lower IDs are typically older, more established terms.
				$keep_id   = min( $term1->term_id, $term2->term_id );
				$remove_id = max( $term1->term_id, $term2->term_id );
			}

			$result = $this->merge_terms( $keep_id, $remove_id, $taxonomy );

			if ( $result['success'] ) {
				$kept_term = get_term( $keep_id, $taxonomy );
				$kept_name = ( $kept_term instanceof WP_Term ) ? $kept_term->name : (string) $keep_id;

				$consolidated[] = array(
					'kept'        => $kept_name,
					'merged'      => $result['message'],
					'posts_moved' => $result['posts_moved'],
				);
			}
		}

		return array(
			'total_merged' => count( $consolidated ),
			'details'      => $consolidated,
		);
	}

	/**
	 * Build a full cleanup preview (dry-run, no changes made).
	 *
	 * @return array {
	 *   nonsensical_categories: array[],
	 *   nonsensical_tags:       array[],
	 *   similar_categories:     array[],
	 *   similar_tags:           array[],
	 * }
	 */
	public function get_cleanup_preview() {
		$threshold = (float) get_option( 'themisdb_taxonomy_similarity_threshold', 0.8 );

		return array(
			'nonsensical_categories' => $this->detect_nonsensical_terms( 'category' ),
			'nonsensical_tags'       => $this->detect_nonsensical_terms( 'post_tag' ),
			'similar_categories'     => $this->get_similar_terms( 'category', $threshold ),
			'similar_tags'           => $this->get_similar_terms( 'post_tag', $threshold ),
		);
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Determine whether a term name is "nonsensical" and return a human-readable
	 * reason, or an empty string when the term is fine.
	 *
	 * @param string $name
	 * @return string Reason description, or '' when name is acceptable.
	 */
	public function get_nonsensical_reason( $name ) {
		$name = trim( $name );

		if ( strlen( $name ) < $this->min_length ) {
			return __( 'Too short (< 2 characters)', 'themisdb-taxonomy-manager' );
		}

		// Majority of characters are digits.
		if ( strlen( $name ) > 0 ) {
			$digit_count = strlen( preg_replace( '/[^0-9]/', '', $name ) );
			if ( $digit_count > 0 && ( $digit_count / strlen( $name ) ) > 0.5 && strlen( $name ) > 2 ) {
				return __( 'Mostly numeric', 'themisdb-taxonomy-manager' );
			}
		}

		foreach ( $this->nonsensical_patterns as $pattern ) {
			if ( preg_match( $pattern, $name ) ) {
				return $this->pattern_reason( $pattern );
			}
		}

		return '';
	}

	/**
	 * Map a regex pattern to a human-readable reason.
	 *
	 * @param string $pattern
	 * @return string
	 */
	private function pattern_reason( $pattern ) {
		$map = array(
			'/^\d+$/'                          => __( 'Pure number', 'themisdb-taxonomy-manager' ),
			'/^\d{4}$/'                        => __( 'Year', 'themisdb-taxonomy-manager' ),
			'/^\d{1,2}$/'                      => __( 'Single/double digit', 'themisdb-taxonomy-manager' ),
			'/^v?\d+\.\d+/'                    => __( 'Version number', 'themisdb-taxonomy-manager' ),
			'/^\d+\s+\d+$/'                    => __( 'Date fragment', 'themisdb-taxonomy-manager' ),
			'/^\d{1,2}[\.\/\-]\d{1,2}[\.\/\-]\d{2,4}$/' => __( 'Date', 'themisdb-taxonomy-manager' ),
			'/^(de|en|fr|es|ja|zh|ru|pt|it)$/i' => __( 'Language code', 'themisdb-taxonomy-manager' ),
			'/^(use|tmp|test|demo|example|sample|draft|untitled)$/i' => __( 'Generic noise word', 'themisdb-taxonomy-manager' ),
			'/^[a-z]$/i'                       => __( 'Single letter', 'themisdb-taxonomy-manager' ),
			'/^[\W_]+$/'                       => __( 'Special characters only', 'themisdb-taxonomy-manager' ),
			'/^(page|post|article|blog|site|web)$/i' => __( 'WordPress generic term', 'themisdb-taxonomy-manager' ),
			'/^(http|https|www|ftp)$/i'        => __( 'URL fragment', 'themisdb-taxonomy-manager' ),
		);

		if ( isset( $map[ $pattern ] ) ) {
			return $map[ $pattern ];
		}

		// Month / weekday patterns all get the same label.
		if ( strpos( $pattern, 'januar' ) !== false || strpos( $pattern, 'january' ) !== false ) {
			return __( 'Month name', 'themisdb-taxonomy-manager' );
		}
		if ( strpos( $pattern, 'monday' ) !== false || strpos( $pattern, 'montag' ) !== false ) {
			return __( 'Weekday name', 'themisdb-taxonomy-manager' );
		}
		if ( strpos( $pattern, 'jan|feb' ) !== false ) {
			return __( 'Month abbreviation', 'themisdb-taxonomy-manager' );
		}

		return __( 'Matches exclude pattern', 'themisdb-taxonomy-manager' );
	}

	/**
	 * Calculate string similarity (0–1) using similar_text + Levenshtein.
	 *
	 * @param string $str1
	 * @param string $str2
	 * @return float
	 */
	private function calculate_similarity( $str1, $str2 ) {
		$a = mb_strtolower( $str1, 'UTF-8' );
		$b = mb_strtolower( $str2, 'UTF-8' );

		// Substring match → high similarity.
		if ( strpos( $a, $b ) !== false || strpos( $b, $a ) !== false ) {
			return 0.9;
		}

		$percent = 0;
		similar_text( $a, $b, $percent );
		$sim_text = $percent / 100;

		// Levenshtein-based similarity (normalised).
		$max_len = max( strlen( $a ), strlen( $b ) );
		$lev_sim = $max_len > 0 ? 1 - ( levenshtein( $a, $b ) / $max_len ) : 1;

		return max( $sim_text, $lev_sim );
	}
}
