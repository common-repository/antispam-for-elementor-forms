<?php
namespace AntispamForElementorForms;

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Retrieve block list from GitHub and save it in WordPress.
 */
class Block_List_Updater {
	/**
	 * @var self|null Class instance.
	 */
	private static ?self $instance = null;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'asef_cron', [$this, 'get_data'] );
		add_action( 'asef_cron_init', [$this, 'get_data'] );
	}

	/**
	 * Get latest block list and store it in the database.
	 */
	public function get_data() {
		$etag = get_option( 'asef_etag', null );

		$response = wp_remote_get(
			'https://raw.githubusercontent.com/splorp/wordpress-comment-blacklist/master/blacklist.txt',
			$etag ? [
				'headers' => [
					'If-None-Match' => $etag
				],
			] : [],
		);

		if( !is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			update_option( 'asef_remote_block_list', wp_remote_retrieve_body( $response ) );
			update_option( 'asef_etag', sanitize_text_field( wp_remote_retrieve_header( $response, 'etag' ) ) );
		}
	}

	/**
	 * Get class instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
