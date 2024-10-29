<?php
namespace AntispamForElementorForms;

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Elementor\Settings_Page;
use Elementor\Utils;

/**
 * Settings page for the plugin.
 */
class Settings {
	/**
	 * @var self|null Class instance.
	 */
	private static ?self $instance = null;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'elementor/admin/after_create_settings/elementor', [$this, 'register_tab'] );
	}

	/**
	 * Register tab and settings in Elementor settings area.
	 *
	 * @param Settings_Page $settings_page
	 */
	public function register_tab( Settings_Page $settings_page ) {
		$settings_page->add_tab( 'asef_settings', [
			'label' => __( 'Antispam for Elementor Forms', 'antispam-for-elementor-forms' ),
			'sections' => [
				'settings' => [
					'fields' => [
						'asef_enable' => [
							'label' => __( 'Enable antispam filtering', 'antispam-for-elementor-forms' ),
							'full_field_id' => 'asef_enable',
							'field_args' => [
								'type' => 'select',
								'options' => [
									'yes' => __( 'Yes', 'antispam-for-elementor-forms' ),
									'no' => __( 'No', 'antispam-for-elementor-forms' ),
								],
							],
						],
						'asef_custom_block_list' => [
							'label' => __( 'Custom block list', 'antispam-for-elementor-forms' ),
							'full_field_id' => 'asef_custom_block_list',
							'field_args' => [
								'label' => __( 'Custom block list', 'antispam-for-elementor-forms' ),
								'desc' => sprintf( __( 'Enter words you\'d like to block in addition to the <a href="%s">list synced from GitHub</a>, one per line.', 'antispam-for-elementor-forms' ), 'https://github.com/splorp/wordpress-comment-blacklist' ),
								'attributes' => [
									'rows' => 6,
									'cols' => 50,
								]
							],
							'render' => [$this, 'render_textarea'],
						],
						'asef_allow_list' => [
							'label' => __( 'Allow list', 'antispam-for-elementor-forms' ),
							'full_field_id' => 'asef_allow_list',
							'field_args' => [
								'label' => __( 'Allow list', 'antispam-for-elementor-forms' ),
								'desc' => sprintf( __( 'The <a href="%s">list of spam words synced from GitHub</a> may contain words which are not relevant to your site. If you\'d like to exclude any words from this list, enter them here, one per line.', 'antispam-for-elementor-forms' ), 'https://github.com/splorp/wordpress-comment-blacklist' ),
								'attributes' => [
									'rows' => 6,
									'cols' => 50,
								]
							],
							'render' => [$this, 'render_textarea'],
						],
					],
				]
			]
		] );
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array $field Field settings.
	 * @return void
	 */
	public function render_textarea( array $field ) { ?>
		<?php if( isset( $field['label'] ) ) : ?>
			<label for="<?php echo esc_attr( $field['id'] ); ?>" class="screen-reader-text"><?php echo esc_attr( $field['label'] ); ?></label>
		<?php endif; ?>

		<textarea id="<?php echo esc_attr( $field['id'] ); ?>" name="<?php echo esc_attr( $field['id'] ); ?>" <?php Utils::print_html_attributes( $field['attributes'] ); ?>><?php echo esc_attr( get_option( $field['id'], $field['std'] ?? '' ) ); ?></textarea>

		<?php if ( ! empty( $field['sub_desc'] ) ) :
			echo wp_kses_post( $field['sub_desc'] );
		endif; ?>

		<?php if ( ! empty( $field['desc'] ) ) : ?>
			<p class="description"><?php echo wp_kses_post( $field['desc'] ); ?></p>
		<?php endif;
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
