<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Custom_Permalinks_Model
{
	/** @var hyperdb|QM_DB|string|wpdb */
	protected $wpdb;

	/** @var string */
	public $table_name;

	public function __construct() {
		global $wpdb;
		global $customPermalinksModel;

		$this->wpdb = $wpdb;
		$this->table_name = "{$this->wpdb->prefix}custom_permalinks";
		$customPermalinksModel = $this;
	}

	public function get_permalink( $post_id ) {
		if ( getenv( 'CUSTOM_PERMALINKS_FORK_ENABLED' ) ) {
			return $this->wpdb->get_var(
				$this->wpdb->prepare(
					"select meta_value from $this->table_name where post_id = %d",
					$post_id
				)
			);
		} else {
			return get_post_meta( $post_id, 'custom_permalink', true );
		}
	}

	public function get_permalink_by_url( $url ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"
					SELECT *
					FROM $this->table_name
					AND meta_value = %s
				",
				$url
			)
		);
	}

	public function update_permalink( $post_id, $permalink, $language_code = null ) {
		if ( getenv( 'CUSTOM_PERMALINKS_FORK_ENABLED' ) ) {
			return $this->wpdb->query(
				$this->wpdb->prepare(
					"insert into $this->table_name (meta_value, post_id, language_code) values (%s, %d, %s)
                        on duplicate key update meta_value = %s, post_id = %d, language_code = %s",
					$permalink,
					$post_id,
					$language_code,
					$permalink,
					$post_id,
					$language_code
				)
			);
		} else {
			return update_post_meta( $post_id, 'custom_permalink', $permalink );
		}
	}

	public function delete_language_permalinks( $post_id ) {
		$this->wpdb->query(
			$this->wpdb->prepare(
				"delete from $this->table_name where post_id = %d and language_code != ''",
				$post_id
			)
		);
	}

	/**
	 * Delete Post Permalink.
	 *
	 * @access public
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function delete_permalink( $post_id ) {
		$this->wpdb->query(
			$this->wpdb->prepare(
				"delete from $this->table_name where post_id = %d and language_code = ''",
				$post_id
			)
		);
	}
}
