<?php
/**
 * Custom Permalinks Post Types.
 *
 * @package CustomPermalinks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Types Permalinks table class.
 */
final class Custom_Permalinks_Post_Types {
	/** @var Custom_Permalinks_Model */
	private static $custom_permalinks_model;

	/**
	 * Returns the count of records in the database.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return null|int
	 */
	public static function total_permalinks() {
		global $wpdb;

		$total_posts = wp_cache_get( 'total_posts_result', 'custom_permalinks' );
		if ( ! $total_posts ) {
			if ( getenv( 'CUSTOM_PERMALINKS_FORK_ENABLED' ) ) {
				$table_name = self::get_custom_permalinks_model()->table_name;
				$sql_query = "
					SELECT COUNT(p.ID) FROM $wpdb->posts AS p
					LEFT JOIN $table_name AS pm ON (p.ID = pm.post_id)
					WHERE pm.meta_value != ''
				";
			} else {
				$sql_query = "
					SELECT COUNT(p.ID) FROM $wpdb->posts AS p
					LEFT JOIN $wpdb->postmeta AS pm ON (p.ID = pm.post_id)
					WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != ''
				";
			}

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			// Include search in total results.
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				$search_value = ltrim( sanitize_text_field( $_REQUEST['s'] ), '/' );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				if ( getenv( 'CUSTOM_PERMALINKS_FORK_ENABLED' ) ) {
					$total_posts = $wpdb->get_var(
						$wpdb->prepare(
							"$sql_query AND pm.meta_value LIKE %s",
							'%' . $wpdb->esc_like( $search_value ) . '%'
						)
					);
				} else {
					$total_posts = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(p.ID) FROM {$wpdb->posts} AS p
							LEFT JOIN {$wpdb->postmeta} AS pm ON (p.ID = pm.post_id)
							WHERE pm.meta_key = 'custom_permalink'
								AND pm.meta_value != ''
								AND pm.meta_value LIKE %s",
							'%' . $wpdb->esc_like( $search_value ) . '%'
						)
					);
				}
				// phpcs:enable WordPress.Security.NonceVerification.Recommended
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				if ( getenv( 'CUSTOM_PERMALINKS_FORK_ENABLED' ) ) {
					$total_posts = $wpdb->get_var($sql_query);
				} else {
					$total_posts = $wpdb->get_var(
						"SELECT COUNT(p.ID) FROM {$wpdb->posts} AS p
						LEFT JOIN {$wpdb->postmeta} AS pm ON (p.ID = pm.post_id)
						WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != ''"
					);
				}
			}

			wp_cache_set( 'total_posts_result', $total_posts, 'custom_permalinks' );
		}

		return $total_posts;
	}

	/**
	 * Retrieve permalink's data from the database.
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param int $per_page    Maximum Results needs to be shown on the page.
	 * @param int $page_number Current page.
	 *
	 * @return array Title, Post Type and Permalink set using this plugin.
	 */
	public static function get_permalinks( $per_page = 20, $page_number = 1 ) {
		global $wpdb;
		$table_name = self::get_custom_permalinks_model()->table_name;

		$posts = wp_cache_get( 'post_type_results', 'custom_permalinks' );
		if ( ! $posts ) {
			$page_offset = ( $page_number - 1 ) * $per_page;
			$order_by    = 'p.ID';
			$order       = null;

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			// Sort the items.
			switch ( isset( $_REQUEST['orderby'] ) ? strtolower( sanitize_text_field( $_REQUEST['orderby'] ) ) : '' ) {
				case 'title':
					$order_by = 'p.post_title';
					break;

				case 'type':
					$order_by = 'p.post_type';
					break;

				case 'permalink':
					$order_by = 'pm.meta_value';
					break;
			}

			switch ( isset( $_REQUEST['order'] ) ? strtolower( sanitize_text_field( $_REQUEST['order'] ) ) : '' ) {
				case 'asc':
					$order = 'ASC';
					break;

				case 'desc':
				default:
					$order = 'DESC';
					break;
			}

			// Include search in total results.
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				$search_value = ltrim( sanitize_text_field( $_REQUEST['s'] ), '/' );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				if ( getenv( 'CUSTOM_PERMALINKS_FORK_ENABLED' ) ) {
					$posts = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT p.ID, p.post_title, p.post_type, pm.meta_value
								FROM {$wpdb->posts} AS p
								LEFT JOIN $table_name AS pm ON (p.ID = pm.post_id)
								WHERE pm.meta_value != ''
									AND pm.meta_value LIKE %s
								ORDER BY %s %s LIMIT %d, %d",
							'%' . $wpdb->esc_like( $search_value ) . '%',
							$order_by,
							$order,
							$page_offset,
							$per_page
						),
						ARRAY_A
					);
				} else {
					$posts = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT p.ID, p.post_title, p.post_type, pm.meta_value
							FROM {$wpdb->posts} AS p
							LEFT JOIN {$wpdb->postmeta} AS pm ON (p.ID = pm.post_id)
							WHERE pm.meta_key = 'custom_permalink'
								AND pm.meta_value != ''
								AND pm.meta_value LIKE %s
							ORDER BY %s %s LIMIT %d, %d",
							'%' . $wpdb->esc_like( $search_value ) . '%',
							$order_by,
							$order,
							$page_offset,
							$per_page
						),
						ARRAY_A
					);
				}
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				if ( getenv( 'CUSTOM_PERMALINKS_FORK_ENABLED' ) ) {
					$posts = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT p.ID, p.post_title, p.post_type, pm.meta_value
								FROM {$wpdb->posts} AS p
								LEFT JOIN $table_name AS pm ON (p.ID = pm.post_id)
								WHERE pm.meta_value != ''
								ORDER BY %s %s LIMIT %d, %d",
							$order_by,
							$order,
							$page_offset,
							$per_page
						),
						ARRAY_A
					);
				} else {
					$posts = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT p.ID, p.post_title, p.post_type, pm.meta_value
							FROM {$wpdb->posts} AS p
							LEFT JOIN {$wpdb->postmeta} AS pm ON (p.ID = pm.post_id)
							WHERE pm.meta_key = 'custom_permalink' AND pm.meta_value != ''
							ORDER BY %s %s LIMIT %d, %d",
							$order_by,
							$order,
							$page_offset,
							$per_page
						),
						ARRAY_A
					);
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			wp_cache_set( 'post_type_results', $posts, 'custom_permalinks' );
		}

		return $posts;
	}

	private static function get_custom_permalinks_model() {
		if ( self::$custom_permalinks_model === null ) {
			self::$custom_permalinks_model = new Custom_Permalinks_Model();
		}

		return self::$custom_permalinks_model;
	}
}
