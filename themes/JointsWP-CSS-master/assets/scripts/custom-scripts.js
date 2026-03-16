jQuery(document).foundation();
/*
These functions make sure WordPress
and Foundation play nice together.
*/
jQuery(document).ready(function(){// Remove empty P tags created by WP inside of Accordion and Orbit
jQuery('.accordion p:empty, .orbit p:empty').remove();// Adds Flex Video to YouTube and Vimeo Embeds
jQuery('iframe[src*="youtube.com"], iframe[src*="vimeo.com"]').each(function(){if(jQuery(this).innerWidth()/jQuery(this).innerHeight()>1.5){jQuery(this).wrap("<div class='widescreen responsive-embed'/>");}else{jQuery(this).wrap("<div class='responsive-embed'/>");}});});

/*
Insert Custom JS Below
*/

jQuery( window ).load(function() {
    jQuery('body').removeClass('fade-out');
    jQuery('.page-header-image').addClass('phi-loaded');
});

/* Asos animations */

 AOS.init();

jQuery(document).ready(function() {
	jQuery('.popup-youtube').magnificPopup({
    type: 'iframe'
  });
});
 

/* Remove Empty P Tags 
	
	NEED TO FIGURE OUT WHY NOT WORKING WITH IMAGES

jQuery('.entry-content p').each(function() {
  if (jQuery(this).text() === '') {
    jQuery(this).remove();
  }
});

*/

jQuery('.entry-content p').each(function() {
    var $this = jQuery(this);
    if($this.html().replace(/\s|&nbsp;/g, '').length == 0)
        $this.remove();
});

/* Open all PDFs in new window */
jQuery(document).ready(function(){
    jQuery('a[href$=".pdf"]').prop('target', '_blank');
});

/* Donation Amount for 3 percent addition within Gravity Forms */

jQuery(document).ready(function(){
	var bla = jQuery('input#input_1_6').val();
	var value = jQuery('li.donation_price_amount').find('input[type="text"]').val();
	console.log(bla);
	jQuery("li.donation_price_amount_total label").text(function () {
	    return jQuery(this).text().replace("DAMOUNT", value); 
	});
});

/* If Sidebar has Active Item, show sub items */

jQuery(document).ready(function(){
	jQuery('#private-sidebar-menu .accordion-menu li.current-menu-parent ul').show();
});

/* Sidebar Toggle for Menu */

jQuery(document).ready(function(){
	jQuery('.sidebar-mobile-opener').click(function() {
		jQuery('.sidebar-mobile-nav').slideToggle();
		jQuery(this).toggleClass('sidebar-mobile-nav-open');
	});
});

/* Owl Carousel Testimonials */
jQuery(document).ready(function(){
	jQuery(".testimonial-carousel").each(function(){
	    jQuery(this).owlCarousel({
	      loop:true,
	        margin:5,
	        nav:false,
	        autoplay:true,
			autoplayTimeout:5000,
			autoplayHoverPause:false,
			autoplaySpeed: 500,
			dots: false,
	        responsive:{
	            0:{
	                items:1
	            },
	            600:{
	                items:1
	            },
	            1000:{
	                items:1
	            }
	        }
	    });
	  });
});
  
/* Match Height */

jQuery(function() {
	jQuery('.testimonial-no-image-wrapper .test-right').matchHeight({
        property: 'height',
    });
});

jQuery(function() {
	jQuery('.endowment-content h2').matchHeight({
        byRow: 'true',
    });
    jQuery('.endowment-card').matchHeight({
        byRow: 'true',
    });
});

/* Owl Carousel for Logos Homepage */
jQuery(document).ready(function() {
  jQuery('.logo-slider').owlCarousel({
    loop:true,
    margin:20,
    autoplay: true,
    autoplayTimeout: 4000,
    autoplayHoverPause: true,
    autoplayHoverPause: true,
    autoHeight: false, 
    nav: false,
    dots: false,
    animateIn: 'slideInRight',
    animateOut: 'slideOutLeft',
    responsiveClass:true,
    responsive:{
        0:{
            items:2,
            autoplay: true,
            nav: false
        },
        600:{
            items:3,
            autoplay: true,
            nav: false
        },
        1000:{
            items:5,
            nav: false,
            dots: false
        }
    }
  })
});

/* New user */
jQuery(document).ready(function() {
	if (window.location.search.indexOf('newuser=yes') > -1) {
	    jQuery('#welcome-mem-message').show();
	} else {
	    jQuery('#welcome-mem-message').hide();
	}
});

/* Mobile Menu */

/*jQuery(document).ready(function() {
	jQuery( "a.mobile-opener" ).click(function() {
      jQuery(this).addClass( "mopen flipInX");
    });
    jQuery(".js-off-canvas-overlay.is-overlay-fixed").click(function() {
	    jQuery("a.mobile-opener").removeClass("mopen flipInX");
    });
});*/

/* Match height for top homepage*/