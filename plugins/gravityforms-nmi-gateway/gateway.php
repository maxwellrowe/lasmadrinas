<?php
/*
Plugin Name: Gravity Forms NMI Gateway (Basic)
Plugin URI: https://wpgateways.com/products/nmi-gateway-gravity-forms/
Description: Integrates Gravity Forms with Network Merchants (NMI), enabling end users to purchase goods and services through Gravity Forms.
Version: 1.2.3
Author: WP Gateways
Author URI: https://wpgateways.com
Text Domain: gf-nmi
Domain Path: /languages
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Requires Plugins: gravityforms
*/

define( 'GF_NMI_VERSION', '1.2.3' );
define( 'GF_NMI_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'GF_NMI_PLUGIN_URL', plugins_url( plugin_basename( GF_NMI_PLUGIN_DIR ) ) );
define( 'GF_NMI_PLUGIN_PATH', plugin_basename( __FILE__ ) );

class GF_NMI_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			return;
		}

		load_plugin_textdomain( 'gf-nmi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		require_once( 'updates/updates.php' );
		require_once( 'class-gateway.php' );

		GFAddOn::register( 'GF_NMI' );
	}

}
add_action( 'gform_loaded', array( 'GF_NMI_Bootstrap', 'load' ), 5 );

function gf_nmi() {
	return GF_NMI::get_instance();
}