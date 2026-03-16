<?php
/*
Template Name: Member Profile
*/

get_header(); ?>

<?php if( current_user_can('administrator') || current_user_can('member') ) {  ?>

<?php } else { ?>

	<script type="text/javascript">
		jQuery( document ).ready(function() {
			jQuery('#blockuser').foundation('open');
		});
	</script>
	
	<div id="blockuser" class="reveal reveal-user-blocker" data-close-on-click="false" data-reveal data-animation-in="fade-in" data-animation-out="fade-out">
		
		<?php the_field('non-member_message','option'); ?>
	
	<!--blockuser--></div>

<?php } ?>
	
	<div class="content member-area">
	
		<div class="inner-content">
	
		    <main class="main" role="main">
			    
			    <div class="expanded row">
				    
				    <div class="small-12 medium-12 large-3 columns" id="private-sidebar-menu">
					    
					    <div class="padder">
						    
						    <ul class="menu hide-for-large">
								<!-- <li><button class="menu-icon" type="button" data-toggle="off-canvas"></button></li> -->
								<li>
									<a class="sidebar-mobile-opener">
										Member Menu <span class="fa fa-angle-down"></span><span class="fa fa-angle-up"></span>
									</a>
								</li>
								<li class="mobile-logout"><a href="<?php echo wp_logout_url('/') ?>">Log Out</a></li>
							</ul>
					    
					    	<div class="sidebar-mobile-nav">
						    	<?php joints_side_member_nav(); ?>
						    </div>
					    	
						<!--/padder--></div>
					    
					<!--/sidebar-menu--></div>
					
					<div class="small-12 medium-12 large-9 columns" id="private-main-content">
						
						<div class="private-header row expanded">
							
							<div class="small-12 medium-6 columns" id="private-page-title">
								
								<h1 class="snell"><?php the_title(); ?></h1>
							
							<!--/private-page-title--></div>
							
							<div class="small-12 medium-6 columns" id="private-header-navigation">
								
								<ul class="menu show-for-large-only">
									
									<?php /*<li class="member-search">
										<form role="search" method="get" class="search-form" action="<?php echo home_url( '/' ); ?>">
											<label>
												<input type="search" class="search-field" placeholder="<?php echo esc_attr_x( 'Search...', 'jointswp' ) ?>" value="<?php echo get_search_query() ?>" name="s" title="<?php echo esc_attr_x( 'Search for:', 'jointswp' ) ?>" />
											</label>
											<button type="submit" class="search-submit button"><span class="fa fa-search"></span></button>
										</form>
									</li> */ ?>
									
									<?php

									// check if the repeater field has rows of data
									if( have_rows('member_header_menu','option') ):
									
									 	// loop through the rows of data
									    while ( have_rows('member_header_menu','option') ) : the_row(); ?>
									    
									        <li><a href="<?php the_sub_field('link'); ?>"><?php the_sub_field('link_text'); ?></a></li>
									
									    <?php endwhile;
									
									else :
									
									    // no rows found
									
									endif;
									
									?>
									
									<li><a href="<?php echo wp_logout_url('/') ?>">Log Out</a></li>
									
								<!--/menu--></ul>
								
							<!--/private-header-navigation--></div>
							
							<div class="clearfix"></div>
							
						<!--/private-header--></div>
						
						<!--Header Image?-->
						
						<?php if(get_field('turn_on_image_header')) { ?>
						
							<?php 
							    $pageImage_id = get_field('header_image');
							    $pageImage_size = "full"; // (thumbnail, medium, large, full or custom size)
							    $pageImage = wp_get_attachment_image_src( $pageImage_id, $pageImage_size );
							?>
							
							<div class="page-header-image" style="background-image:url('<?php echo $pageImage[0]; ?>');"></div>
						
						<?php } ?>
						
						<div class="padder">
				
							<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			
						    	<?php get_template_part( 'parts/loop', 'page' ); ?>
						    
						    <?php endwhile; endif; ?>
						    
						<!--/padder--></div>	
					    
					<!--/private-main-content--></div>						
			    					
			</main> <!-- end #main -->
		    
		</div> <!-- end #inner-content -->

	</div> <!-- end #content -->

<?php get_footer(); ?>