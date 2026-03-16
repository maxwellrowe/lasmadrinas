<?php
function site_scripts() {
  global $wp_styles; // Call global $wp_styles variable to add conditional wrapper around ie stylesheet the WordPress way
    
    // Register Foundation scripts
    wp_enqueue_script( 'foundation-js', get_template_directory_uri() . '/foundation-sites/dist/js/foundation.min.js', array( 'jquery' ), "6.4.1", true );
    
    // Adding aos scripts file in the footer
    wp_enqueue_script( 'aos-js', get_template_directory_uri() . '/assets/scripts/aos.js', array( 'jquery' ), '', true );
    
    // Adding owl carousel scripts file in the footer
    wp_enqueue_script( 'owl-js', get_template_directory_uri() . '/assets/scripts/owl.carousel.min.js', array( 'jquery' ), '', true );
    
    // Adding match height scripts file in the footer
    wp_enqueue_script( 'height-js', get_template_directory_uri() . '/assets/scripts/jquery.matchHeight-min.js', array( 'jquery' ), '', true );
            
    // Register Foundation styles
    wp_enqueue_style( 'foundation-css', get_template_directory_uri() . '/foundation-sites/dist/css/foundation-float.min.css', array(), "6.4.1", 'all' );
     
    // Adding scripts file in the footer
    wp_enqueue_script( 
		'site-js', 
		get_template_directory_uri() . '/assets/scripts/custom-scripts.js', 
		array( 'jquery' ), 
		'1.1.0', // Replace this with your custom version number
		true 
	);
    
    // Register aos stylesheet
    wp_enqueue_style( 'aos-css', get_template_directory_uri() . '/assets/styles/aos.css', array(), '', 'all' );
    
    // Register animate stylesheet
    wp_enqueue_style( 'animate-css', get_template_directory_uri() . '/assets/styles/animate.css', array(), '', 'all' );
    
    // Register owl carousel stylesheet
    wp_enqueue_style( 'owl-css', get_template_directory_uri() . '/assets/styles/owl.carousel.min.css', array(), '', 'all' );
    
    // Register owl carousel theme stylesheet
    wp_enqueue_style( 'owl-theme-css', get_template_directory_uri() . '/assets/styles/owl.theme.default.min.css', array(), '', 'all' );
    
    // Register Hover stylesheet
    wp_enqueue_style( 'hover-css', get_template_directory_uri() . '/assets/styles/hover-min.css', array(), '', 'all' );
       
    // Register main stylesheet
    wp_enqueue_style( 'site-css', get_template_directory_uri() . '/assets/styles/style.css', array(), filemtime(get_template_directory() . '/assets/styles'), 'all' );
    
    // Register responsive stylesheet
    wp_enqueue_style( 'responsive-css', get_template_directory_uri() . '/assets/styles/responsive.css', array(), '', 'all' );

    // Comment reply script for threaded comments
    if ( is_singular() AND comments_open() AND (get_option('thread_comments') == 1)) {
      wp_enqueue_script( 'comment-reply' );
    }
}
add_action('wp_enqueue_scripts', 'site_scripts', 999);