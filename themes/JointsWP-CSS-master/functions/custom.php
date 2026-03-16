<?php /* Add Editor Styles */
/**
 * Registers an editor stylesheet for the theme.
 */
function wpdocs_theme_add_editor_styles() {
    add_editor_style( 'custom-editor-style.css' );
}
add_action( 'admin_init', 'wpdocs_theme_add_editor_styles' );

/* Custom Functions */

/*ACF Add Options Pages*/

if( function_exists('acf_add_options_page') ) {

	acf_add_options_page(array(
		'page_title' 	=> 'Site Options',
		'menu_title'	=> 'Site Options',
		'menu_slug' 	=> 'site-options',
		'capability'	=> 'edit_posts'
	));

}
/* Add all image sizes */

add_image_size( 'testimonial_image', 800, 800, true );
add_image_size( 'card_top_image', 800, 300, true );
add_image_size( 'card_right_image', 400, 800, true );
add_image_size( 'endowment_cover', 450, 338, true );

/* Get Current User Role */
function get_current_user_role() {
	global $wp_roles;
	$current_user = wp_get_current_user();
	$roles = $current_user->roles;
	$role = array_shift($roles);
	return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role] ) : false;
}

/* PayPal Add Logo */

add_filter( 'gform_paypal_query', 'add_image_url', 10, 3 );
function add_image_url( $query_string, $form, $entry ) {
    // Update 1 to your form id. Remove the if statement if you want to add the image to all forms.
    if ( $form['id'] == 8 ) { 
        $query_string .= '&image_url=https://www.lasmadrinas.org/wp-content/uploads/2018/02/paypal-lm.jpg'; // Size of the image must be 150 x 150 pixels
    }
  
    return $query_string;
}

/* Mail From For WP */

// Function to change email address
 
function wpb_sender_email( $original_email_address ) {
    return 'noreply@lasmadrinas.org';
}
 
// Function to change sender name
function wpb_sender_name( $original_email_from ) {
    return 'Las Madrinas';
}
 
// Hooking up our functions to WordPress filters 
add_filter( 'wp_mail_from', 'wpb_sender_email' );
add_filter( 'wp_mail_from_name', 'wpb_sender_name' );

/* If New User Add Message */

function function_new_user($user_id) { 
   add_user_meta( $user_id, '_new_user', '1' );
}
add_action( 'user_register', 'function_new_user');

function function_check_login_redirect($user_login, $user) {
   $logincontrol = get_user_meta($user->ID, '_new_user', 'TRUE');
   if ( $logincontrol ) {
      //set the user to old
      update_user_meta( $user->ID, '_new_user', '0' );
	  
      //Do the redirects or whatever you need to do for the first login
      //wp_redirect( 'https://www.lasmadrinas.org/member/home/?newuser=yes', 302 ); exit;
   }
}
add_action('wp_login', 'function_check_login_redirect', 10, 2);