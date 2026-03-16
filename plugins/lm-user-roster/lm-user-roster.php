<?php
/**
 * Plugin Name: LM User Roster
 * Description: Shortcode [lm-user-roster] - displays users with role "member" with ACF fields and profile picture.
 * Version: 0.1.0
 * Author: You
 */

if ( ! defined('ABSPATH') ) exit;

class LM_User_Roster_Plugin {

	const SHORTCODE = 'lm-user-roster';

	public static function init() {
		add_shortcode(self::SHORTCODE, [__CLASS__, 'shortcode']);
	}

	/**
	 * Shortcode: [lm-user-roster]
	 *
	 * Attributes:
	 * - orderby: display_name|meta_value (default display_name)
	 * - order: ASC|DESC (default ASC)
	 * - per_page: -1 for all (default -1)
	 * - role: member (default member)
	 * - profile_field: ACF field name to use for profile picture (optional; e.g. profile_picture)
	 * - class: wrapper class (optional)
	 */
	public static function shortcode($atts = []) {
		$atts = shortcode_atts([
			'orderby'       => 'display_name',
			'order'         => 'ASC',
			'per_page'      => -1,
			'role'          => 'member',
			'profile_field' => 'profile_image',   // if you have an ACF image field for the profile pic, put its field name here
			'class'         => 'lm-user-roster',
		], $atts, self::SHORTCODE);
			
		self::enqueue_assets_once();

		$args = [
			'role__in' => [ sanitize_text_field($atts['role']) ],
			'number'   => -1, // ALWAYS fetch all
		];

		// If you want to sort by an ACF field, you can pass orderby="meta_value" and meta_key="..."
		// (not enabled as an attr here yet; easy to add if you want it)

		$users = get_users($args);
		
		usort($users, function ($a, $b) {
		
			// Get last names
			$a_last = strtolower(trim(get_user_meta($a->ID, 'contact_last_name', true)));
			$b_last = strtolower(trim(get_user_meta($b->ID, 'contact_last_name', true)));
		
			// Push empty last names to the bottom
			if ($a_last === '' && $b_last !== '') return 1;
			if ($a_last !== '' && $b_last === '') return -1;
		
			// Compare last names
			if ($a_last !== $b_last) {
				return $a_last <=> $b_last;
			}
		
			// Last names are equal → compare first names
			$a_first = strtolower(trim(get_user_meta($a->ID, 'her_first', true)));
			$b_first = strtolower(trim(get_user_meta($b->ID, 'her_first', true)));
		
			return $a_first <=> $b_first;
		});

		if (empty($users)) {
			return '<div class="' . esc_attr($atts['class']) . '"><p>No members found.</p></div>';
		}

		ob_start();

		$template = self::locate_template('lm-user-roster/template.php');

		// Variables available inside the template:
		// $users, $atts
		include $template;

		return ob_get_clean();
	}

	/**
	 * Theme override support:
	 * - yourtheme/lm-user-roster/template.php
	 * Otherwise uses plugin template.
	 */
	private static function locate_template($relative) {
		$theme_path = trailingslashit(get_stylesheet_directory()) . $relative;
		if (file_exists($theme_path)) {
			return $theme_path;
		}
		return plugin_dir_path(__FILE__) . $relative;
	}

	/**
	 * Helper: Get ACF field value for a user.
	 */
	public static function acf_user_field($field_name, $user_id) {
		if (!function_exists('get_field')) return null; // ACF not active
		// ACF user context key format:
		return get_field($field_name, 'user_' . intval($user_id));
	}

	public static function profile_picture_url($user, $profile_field = '') {
		$user_id = is_object($user) ? $user->ID : intval($user);
	
		// 1) ACF image field (array return)
		if ($profile_field && function_exists('get_field')) {
			$img = get_field($profile_field, 'user_' . $user_id);
	
			if (is_array($img)) {
	
				// Preferred: use registered 800x800 crop
				if (!empty($img['sizes']['lm-profile-800'])) {
					return esc_url_raw($img['sizes']['lm-profile-800']);
				}
	
				// Fallbacks
				if (!empty($img['sizes']['large'])) {
					return esc_url_raw($img['sizes']['large']);
				}
	
				if (!empty($img['url'])) {
					return esc_url_raw($img['url']);
				}
			}
		}
	
		// 2) WP avatar fallback
		$avatar = get_avatar_url($user_id, ['size' => 800]);
		return $avatar ? esc_url_raw($avatar) : '';
	}
	
	private static function enqueue_assets_once() {
		static $done = false;
		if ($done) return;
		$done = true;
	
		wp_enqueue_style(
			'lm-user-roster',
			plugin_dir_url(__FILE__) . 'assets/lm-user-roster.css',
			[],
			@filemtime(plugin_dir_path(__FILE__) . 'assets/lm-user-roster.css') ?: '0.1.0'
		);
	
		wp_enqueue_script(
			'lm-user-roster',
			plugin_dir_url(__FILE__) . 'assets/lm-user-roster.js',
			[],
			@filemtime(plugin_dir_path(__FILE__) . 'assets/lm-user-roster.js') ?: '0.1.0',
			true
		);
	}
}

add_action('after_setup_theme', function () {
	add_image_size('lm-profile-800', 800, 800, true); // hard crop
});

LM_User_Roster_Plugin::init();