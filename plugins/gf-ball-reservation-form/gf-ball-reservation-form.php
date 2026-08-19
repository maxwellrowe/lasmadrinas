<?php
/**
 * Plugin Name: GF Ball Reservation Form
 * Description: Ball Reservation-specific settings and address normalization for Gravity Forms.
 * Version: 1.1.0
 * Author: Las Madrinas
 * Text Domain: gf-ball-reservation-form
 * Requires Plugins: gravityforms
 *
 * @package GFBallReservationForm
 */

defined( 'ABSPATH' ) || exit;

define( 'GF_BALL_RESERVATION_FORM_VERSION', '1.1.0' );
define( 'GF_BALL_RESERVATION_FORM_FILE', __FILE__ );

/**
 * Loads the Gravity Forms Add-On after Gravity Forms is available.
 *
 * @return void
 */
function gf_ball_reservation_form_load() {
	if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
		return;
	}

	GFForms::include_addon_framework();
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gf-ball-reservation-settings.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gf-ball-reservation-api.php';

	GFAddOn::register( 'GF_Ball_Reservation_Settings' );
	GF_Ball_Reservation_API::register();
}
add_action( 'gform_loaded', 'gf_ball_reservation_form_load', 5 );

/**
 * Gets the Ball Reservation Add-On instance.
 *
 * @return GF_Ball_Reservation_Settings|null
 */
function gf_ball_reservation_form() {
	return class_exists( 'GF_Ball_Reservation_Settings' ) ? GF_Ball_Reservation_Settings::get_instance() : null;
}
