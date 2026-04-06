<?php
/**
 * Plugin Name: Gravity Forms Button Spinner
 * Description: Adds a simple white spinner to Gravity Forms submit buttons while AJAX submissions are in progress.
 * Version: 1.0.0
 * Author: Las Madrinas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lm_gf_button_spinner_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	if ( ! class_exists( 'GFCommon' ) ) {
		return;
	}

	$plugin_url  = plugin_dir_url( __FILE__ );
	$plugin_path = plugin_dir_path( __FILE__ );

	wp_enqueue_style(
		'lm-gf-button-spinner',
		$plugin_url . 'assets/gf-button-spinner.css',
		array(),
		filemtime( $plugin_path . 'assets/gf-button-spinner.css' )
	);

	wp_enqueue_script(
		'lm-gf-button-spinner',
		$plugin_url . 'assets/gf-button-spinner.js',
		array( 'jquery' ),
		filemtime( $plugin_path . 'assets/gf-button-spinner.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'lm_gf_button_spinner_enqueue_assets', 1001 );

