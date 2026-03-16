<?php
	
// Enable shortcodes in text widgets
add_filter('widget_text','do_shortcode');

function wpb_mce_buttons_2($buttons) {
	array_unshift($buttons, 'styleselect');
	return $buttons;
}
add_filter('mce_buttons_2', 'wpb_mce_buttons_2');

/*
* Callback function to filter the MCE settings
*/

function my_mce_before_init_insert_formats( $init_array ) {  

// Define the style_formats array

	$style_formats = array(  
/*
* Each array child is a format with it's own settings
* Notice that each array has title, block, classes, and wrapper arguments
* Title is the label which will be visible in Formats menu
* Block defines whether it is a span, div, selector, or inline style
* Classes allows you to define CSS classes
* Wrapper whether or not to add a new block-level element around any selected elements
*/
		array(  
			'title' => 'Pre-Header',  
			'block' => 'span',  
			'classes' => 'pre-header',
			'wrapper' => false,
			
		),
		array(  
			'title' => 'H1 Pre-Header',  
			'block' => 'h1',  
			'classes' => 'h1-pre-header',
			'wrapper' => false,
			
		),
		array(  
			'title' => 'Small Text',  
			'block' => 'span',  
			'classes' => 'small-text',
			'wrapper' => true,
		),
		array(  
			'title' => 'Big Lead',  
			'block' => 'span',  
			'classes' => 'big-lead',
			'wrapper' => true,
		),
		array(  
			'title' => 'Big Text',  
			'block' => 'span',  
			'classes' => 'big-text',
			'wrapper' => true,
		),
		array(  
			'title' => 'Medium Text',  
			'block' => 'span',  
			'classes' => 'medium-text',
			'wrapper' => true,
		),
		array(  
			'title' => 'Blue Text',  
			'block' => 'span',  
			'classes' => 'blue-text',
			'wrapper' => true,
		),
		array(  
			'title' => 'Dark Blue Text',  
			'block' => 'span',  
			'classes' => 'dark-blue-text',
			'wrapper' => true,
		),
		array(  
			'title' => 'Lighter Blue Text',  
			'block' => 'span',  
			'classes' => 'lighter-blue-text',
			'wrapper' => true,
		),
		array(  
			'title' => 'Snell Font',  
			'block' => 'span',  
			'classes' => 'snell',
			'wrapper' => true,
		),
		array(  
			'title' => 'Libre Font',  
			'block' => 'span',  
			'classes' => 'libre-font',
			'wrapper' => true,
		),
		array(  
			'title' => 'White Text',  
			'block' => 'span',  
			'classes' => 'white-text',
			'wrapper' => true,
		),
		array(  
			'title' => 'Button',  
			'selector' => 'a',  
			'classes' => 'button',
		),
		array(  
			'title' => 'Button Small',  
			'selector' => 'a',  
			'classes' => 'button small',
		),
		array(  
			'title' => 'Button Block',  
			'selector' => 'a',  
			'classes' => 'button expanded',
		),
		array(  
			'title' => 'Hollow Button',  
			'selector' => 'a',  
			'classes' => 'hollow button',
		),
		array(  
			'title' => 'Hollow Button Small',  
			'selector' => 'a',  
			'classes' => 'hollow button small',
		),
		array(  
			'title' => 'Hollow Button Block',  
			'selector' => 'a',  
			'classes' => 'hollow button expanded',
		),
		array(  
			'title' => 'Large Margin Bottom',    
			'classes' => 'p-large-margin-bottom',
			'block' => 'p',
			'wrapper' => false,
		),
		array(  
			'title' => 'Medium Margin Bottom',    
			'classes' => 'p-medium-margin-bottom',
			'block' => 'p',
			'wrapper' => false,
		),
		array(  
			'title' => 'Small Margin Bottom',    
			'classes' => 'p-small-margin-bottom',
			'block' => 'p',
			'wrapper' => false,
		),
		array(  
			'title' => 'No Margin Bottom',    
			'classes' => 'p-no-margin-bottom',
			'block' => 'p',
			'wrapper' => false,
		),
		array(  
			'title' => 'Large Margin Top',    
			'classes' => 'p-large-margin-top',
			'block' => 'p',
			'wrapper' => false,
		),
		array(  
			'title' => 'Heading 1',  
			'block' => 'p',  
			'classes' => 'h1',
			'wrapper' => false,
		),
		array(  
			'title' => 'Heading 2',  
			'block' => 'p',  
			'classes' => 'h2',
			'wrapper' => false,
		),
		array(  
			'title' => 'Heading 3',  
			'block' => 'p',  
			'classes' => 'h3',
			'wrapper' => false,
		),
		array(  
			'title' => 'Heading 4',  
			'block' => 'p',  
			'classes' => 'h4',
			'wrapper' => false,
		),

	);  
	// Insert the array, JSON ENCODED, into 'style_formats'
	$init_array['style_formats'] = json_encode( $style_formats );  
	
	return $init_array;  
  
} 
// Attach callback to 'tiny_mce_before_init' 
add_filter( 'tiny_mce_before_init', 'my_mce_before_init_insert_formats' );

/* Add Styles to Editor */

function my_theme_add_editor_styles() {
    add_editor_style( 'custom-editor-style.css' );
}
add_action( 'init', 'my_theme_add_editor_styles' );
	
/* Remove Empty P Tags */

function cf_shortcode_empty_paragraph_fix( $content ) {

        $array = array (
		    '<p>[' => '[',
		    ']</p>' => ']',
		    ']<br />' => ']',
		    '<p><div' => '[',
		    '</div></p>' => ']',
		    '</div><br />' => ']'
		);

        $content = strtr( $content, $array );

        return $content;
    }

add_filter( 'acf_the_content', 'cf_shortcode_empty_paragraph_fix' );	

/* Endowments Shortcode */
function my_ads_shortcode( $attr ) {
    ob_start();
    get_template_part( 'endowments' );
    return ob_get_clean();
}
add_shortcode( 'past-endowments', 'my_ads_shortcode' );	

/* Press Shortcode */
function pressShortcode( $attr ) {
    ob_start();
    get_template_part( 'press' );
    return ob_get_clean();
}
add_shortcode( 'press', 'pressShortcode' );	

/* Las Madrinas Ball Press Shortcode */
function lmbPressShortcode( $attr ) {
	ob_start();
	get_template_part( 'press-lmb' );
	return ob_get_clean();
}
add_shortcode( 'lmb-press', 'lmbPressShortcode' );	

/* Member Calendar Shortcode */
function mem_calendar_shortcode( $attr ) {
    ob_start();
    get_template_part( 'mem-calendar' );
    return ob_get_clean();
}
add_shortcode( 'mem-calendar', 'mem_calendar_shortcode' );

/* Member Calendar Shortcode */
function deb_calendar_shortcode( $attr ) {
    ob_start();
    get_template_part( 'deb-calendar' );
    return ob_get_clean();
}
add_shortcode( 'deb-calendar', 'deb_calendar_shortcode' );	

/* Clear */
	
function sClear( $atts, $content = null ) {
		return '<div class="clearfix">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('clear', 'sClear');
	
/* Button */
	
function sButton( $atts, $content = null ) {
		return '<span class="button-sc hvr-forward">' . do_shortcode( $content ) . '</span>';
	}
	add_shortcode('button', 'sButton');
	
/* Hallow White Button */

function sWhiteButton( $atts, $content = null ) {
		return '<span class="button-sc button-sc-hollow hvr-forward">' . do_shortcode( $content ) . '</span>';
	}
	add_shortcode('button-hollow', 'sWhiteButton');
	
/* Hallow White Button */

function sHollowButton( $atts, $content = null ) {
		return '<span class="button-sc button-hollow hvr-forward">' . do_shortcode( $content ) . '</span>';
	}
	add_shortcode('hollow-button', 'sHollowButton');
	
/* Columns */

/* One Half */

function sHalf( $atts, $content = null ) {
		return '<div class="large-6 medium-6 small-12 columns">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('half', 'sHalf');
	
/* One Half Last */

function sHalfLast( $atts, $content = null ) {
		return '<div class="large-6 medium-6 small-12 columns last-child">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('half-last', 'sHalfLast');
	
/* One Third */

function sThird( $atts, $content = null ) {
		return '<div class="large-4 medium-4 small-12 columns">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('third', 'sThird');
	
/* One Third Last */

function sThirdLast( $atts, $content = null ) {
		return '<div class="large-4 medium-4 small-12 columns last-child">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('third-last', 'sThirdLast');
	
/* Two Thirds */

function sTwoThirds( $atts, $content = null ) {
		return '<div class="large-8 medium-8 small-12 columns">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('two-thirds', 'sTwoThirds');
	
/* Two Thirds Last */

function sTwoThirdsLast( $atts, $content = null ) {
		return '<div class="large-8 medium-8 small-12 columns last-child">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('two-thirds-last', 'sTwoThirdsLast');
	
/* Fourth */

function sFourth( $atts, $content = null ) {
		return '<div class="large-3 medium-3 small-12 columns">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('fourth', 'sFourth');
	
/* Fourth Last */

function sFourthLast( $atts, $content = null ) {
		return '<div class="large-3 medium-3 small-12 columns last-child">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('fourth-last', 'sFourthLast');
	
/* Three Fourth Last */

function sThreeFourths ( $atts, $content = null ) {
		return '<div class="large-9 medium-9 small-12 columns">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('three-fourths', 'sThreeFourths');
	
/* Three Fourth Last */

function sThreeFourthsLast ( $atts, $content = null ) {
		return '<div class="large-9 medium-9 small-12 columns last-child">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('three-fourths-last', 'sThreeFourthsLast');

/* Well - Callout */

function sWell ( $atts, $content = null ) {
		return '<div class="callout">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('well', 'sWell');
	
function sWellWhite ( $atts, $content = null ) {
		return '<div class="callout light-callout">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('callout', 'sWellWhite');
	
function sContent ( $atts, $content = null ) {
		return '<span class="widget_p_helper">' . do_shortcode( $content ) . '</span>';
	}
	add_shortcode('content', 'sContent');

/* Row */

function sRow ( $atts, $content = null ) {
		return '<div class="row">' . do_shortcode( $content ) . '</div>';
	}
	add_shortcode('row', 'sRow');
	
// Title Border
function sTitleBorder ( $atts, $content = null ) {
	return '<div class="title-border"></div><div class="title-border-title"><span>' . do_shortcode( $content ) . '</span></div>';
}
add_shortcode('title-border', 'sTitleBorder');

// Title Border
function sTitleBorderTan ( $atts, $content = null ) {
	return '<div class="title-border"></div><div class="title-border-title title-border-title-tan"><span>' . do_shortcode( $content ) . '</span></div>';
}
add_shortcode('title-border-tan', 'sTitleBorderTan');

// Add Shortcode
function dec_line_dark() {
	return '<div class="swirl-liner"><ul class="swirl-line-dark"><li class="swirl-line-left"><span class="swirl-line">&nbsp;</span></li><li class="swirl-line-middle"><span class="the-swirl"></span></li><li class="swirl-line-right"><span class="swirl-line">&nbsp;</span></li></ul></div>';
}
add_shortcode( 'dec-line-dark', 'dec_line_dark' );

function dec_line_light() {
	return '<div class="swirl-liner"><ul class="swirl-line-light"><li class="swirl-line-left"><span class="swirl-line">&nbsp;</span></li><li class="swirl-line-middle"><span class="the-swirl"></span></li><li class="swirl-line-right"><span class="swirl-line">&nbsp;</span></li></ul></div>';
}
add_shortcode( 'dec-line-light', 'dec_line_light' );
	
/* Margin */
	
function sMargin($atts, $content = null)
	{
	    // do something to $content
	 
	    // always return
	    return '<div class="the-margin-helper"></div>';
	}
	add_shortcode('margin', 'sMargin');
	
/* YouTube Video Shortcode */

function video_shortcode($atts) {
    extract(shortcode_atts(array(
        'id' => 'id',
        'image' => 'image'
    ), $atts));
    
    // check what type user entered
    return '<a class="video-modal" data-open="' . esc_attr($id) . '"><img src="' . esc_attr($image) . '" alt="" /><span class="video-play-button"></span></a><div class="reveal" id="' . esc_attr($id) . '" data-reveal data-reset-on-close="true" data-animation-in="fade-in" data-animation-out="fade-out"><iframe width="560" height="315" src="https://www.youtube.com/embed/' . esc_attr($id) . '?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe><button class="close-button" data-close aria-label="Close modal" type="button"><span aria-hidden="true">&times;</span></button></div>';
}
add_shortcode('video-lightbox', 'video_shortcode');