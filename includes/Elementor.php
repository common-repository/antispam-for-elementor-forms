<?php
namespace AntispamForElementorForms;

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Classes\Ajax_Handler;

/**
 * Elementor Forms integration.
 */
class Elementor {
	/**
	 * @var self|null Class instance.
	 */
	private static ?self $instance = null;

	/**
	 * @var array|null
	 */
	private ?array $block_list = null;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'elementor_pro/forms/validation', [$this, 'validate_submission'], 100, 2 );
	}

	/**
	 * Validate Elementor Forms submission.
	 *
	 * @param Form_Record $form
	 * @param Ajax_Handler $ajax
	 */
	public function validate_submission( Form_Record $form, Ajax_Handler $ajax ) {
		if( $this->is_enabled() ) {
			$words = $this->get_block_list();

			foreach( $words as $word ) {
				// Do some escaping magic so that '#' chars in the spam words don't break things
				$word = preg_quote( $word, '#' );
				$pattern = "#$word#i";

				foreach( $form->get( 'fields' ) as $field ) {
					if( preg_match( $pattern, $field['value'] ) || preg_match( $pattern, $field['raw_value'] ) ) {
						$ajax->add_error( $field['id'], 'Your message has been rejected.' );
					}
				}
			}
		}
	}

	/**
	 * Check if block list is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 'yes' === get_option( 'asef_enable', 'yes' );
	}

	/**
	 * Return the list of disallowed words.
	 *
	 * @return array
	 */
	public function get_block_list(): array {
		if( null === $this->block_list ) {
			$remote_list = $this->get_single_block_list( 'asef_remote_block_list' );
			$custom_list = $this->get_single_block_list( 'asef_custom_block_list' );
			$allow_list = $this->get_single_block_list( 'asef_allow_list' );

			// Remove allow list items from the remote list
			foreach( $allow_list as $allow_list_word ) {
				$word = preg_quote( $allow_list_word, '#' );
				$pattern = "#$word#i";

				foreach( $remote_list as $remote_list_word_key => $remote_list_word ) {
					if( preg_match( $pattern, $remote_list_word ) ) {
						unset( $remote_list[$remote_list_word_key] );
					}
				}
			}

			// Add the custom list to the remote list
			$this->block_list = array_unique( array_merge( $remote_list, $custom_list ) );
		}

		return $this->block_list ?? [];
	}

	/**
	 * Retrieve and clean up a single block list saved in options table.
	 *
	 * @param string $option_name
	 * @return array
	 */
	public function get_single_block_list( string $option_name ): array {
		return array_unique( array_filter( array_map( 'trim', explode( "\n", get_option( $option_name, '' ) ) ) ) );
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
