<?php
namespace AntispamForElementorForms;

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Main plugin class.
 */
class Plugin {
	/**
	 * @var self|null Class instance.
	 */
	private static ?self $instance = null;

	/**
	 * @var Settings Settings object.
	 */
	public Settings $Settings;

	/**
	 * @var Block_List_Updater Updater object.
	 */
	public Block_List_Updater $Block_List_Updater;

	/**
	 * @var Elementor Elementor integration object.
	 */
	public Elementor $Elementor;

	/**
	 * Include other files and register hooks.
	 */
	public function __construct() {
		foreach( ['Settings', 'Block_List_Updater', 'Elementor'] as $class ) {
			require_once( plugin_dir_path( ASEF_PLUGIN_FILE ) . 'includes/' . $class . '.php' );
			$this->$class = ('AntispamForElementorForms\\' . $class)::get_instance();
		}

        // Register form fields
        add_action( 'elementor_pro/forms/fields/register', [$this, 'register_form_fields'] );

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_assets'] );
	}

	/**
	 * Run on plugin activation.
	 */
	public static function activation() {
		$now = new \DateTime();
		$time = (new \Datetime())->setTime( 3, 0, 0 );

		if( $time < $now ) {
			$time->add( new \DateInterval( 'P1D' ) );
		}

		wp_schedule_event( $time->format( 'U' ), 'daily', 'asef_cron' );
		wp_schedule_single_event( time(), 'asef_cron_init' );
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivation() {
		wp_unschedule_event( wp_next_scheduled( 'asef_cron' ), 'asef_cron' );
	}

    /**
     * Register Elementor form fields.
     *
     * @param \ElementorPro\Modules\Forms\Registrars\Form_Fields_Registrar $form_fields_registrar
     * @return void
     */
    function register_form_fields( $registrar ) {
        require_once( plugin_dir_path( ASEF_PLUGIN_FILE ) . 'includes/JS_Honeypot_Field.php' );

        $registrar->register( new JS_Honeypot_Field() );
    }

    /**
     * Enqueue plugin assets.
     *
     * @return void
     */
    function enqueue_assets() {
        $version = $this->get_version();

        wp_register_script( 'antispam-for-elementor-forms', plugin_dir_url( ASEF_PLUGIN_FILE ) . 'assets/js/antispam-for-elementor-forms.js', ['jquery', 'elementor-frontend'], $version, [
			'in_footer' => true,
        ] );
    }

    /**
     * Get plugin version.
     *
     * @return string|null
     */
    function get_version(): ?string {
        if( !function_exists( 'get_plugin_data' ) ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $data = get_plugin_data( ASEF_PLUGIN_FILE );

        return $data['Version'] ?? null;
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
