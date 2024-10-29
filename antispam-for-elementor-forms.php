<?php
/**
 * Plugin Name: Antispam for Elementor Forms
 * Plugin URI: https://github.com/MadeByGreyhound/antispam-for-elementor-forms
 * Description: Check contents of Elementor Forms for spam.
 * Version: 2.2.2
 * Requires at least: 5.2
 * Requires PHP: 8.0
 * Author: Greyhound Studio
 * Author URI: https://greyhound.studio/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: elementor
 */

if( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use AntispamForElementorForms\Plugin;

// Load main plugin file
require __DIR__ . '/includes/Plugin.php';

// Define constants
const ASEF_PLUGIN_FILE = __FILE__;

/**
 * Utility function to retrieve main plugin class instance.
 *
 * @return Plugin
 */
function ASEF(): Plugin {
	return Plugin::get_instance();
}

// Instantiate plugin
add_action( 'plugins_loaded', 'ASEF' );

// Activation hooks
register_activation_hook( ASEF_PLUGIN_FILE, ['AntispamForElementorForms\Plugin', 'activation'] );
register_deactivation_hook( ASEF_PLUGIN_FILE, ['AntispamForElementorForms\Plugin', 'deactivation'] );
