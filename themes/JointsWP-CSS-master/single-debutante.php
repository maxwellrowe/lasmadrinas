<?php 
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 */

get_header(); ?>

<?php if( current_user_can('administrator') || current_user_can('member') || current_user_can('debutante') ) {  ?>

<?php } else { ?>

	<script type="text/javascript">
		jQuery( document ).ready(function() {
			jQuery('#blockuser').foundation('open');
		});
	</script>
	
	<div id="blockuser" class="reveal reveal-user-blocker" data-close-on-click="false" data-reveal data-animation-in="fade-in" data-animation-out="fade-out">
		
		<?php the_field('non-debutante_message','option'); ?>
	
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
										Debutante Menu <span class="fa fa-angle-down"></span><span class="fa fa-angle-up"></span>
									</a>
								</li>
								<li class="mobile-logout"><a href="<?php echo wp_logout_url('/') ?>">Log Out</a></li>
							</ul>
					    
					    	<div class="sidebar-mobile-nav">
						    	<?php joints_side_deb_nav(); ?>
						    </div>
					    	
						<!--/padder--></div>
					    
					<!--/sidebar-menu--></div>
					
					<div class="small-12 medium-12 large-9 columns <?php the_field('custom_page_class'); ?>" id="private-main-content">
						
						<div class="private-header row expanded">
							
							<div class="small-12 medium-4 columns" id="private-page-title">
								
									<?php if(get_field('is_this_a_gallery')) { ?>
									
										<a href="<?php site_url(); ?>/debutante/galleries/" style="display:block; margin-top: .5rem;"><span class="fa fa-long-arrow-left"></span> <strong>View All Galleries</strong></a>
									
									<?php } else { ?>
									
										<h1 class="snell"><?php the_title(); ?></h1>
									
									<?php } ?>
							
							<!--/private-page-title--></div>
							
							<div class="small-12 medium-8 columns" id="private-header-navigation">
								
								<ul class="menu show-for-large">
									
									<?php

									// check if the repeater field has rows of data
									if( have_rows('debutante_header_menu','option') ):
									
									 	// loop through the rows of data
									    while ( have_rows('debutante_header_menu','option') ) : the_row(); ?>
									    
									        <li><a href="<?php the_sub_field('link'); ?>"><?php the_sub_field('link_text'); ?></a></li>
									
									    <?php endwhile;
									
									else :
									
									    // no rows found
									
									endif;
									
									?>
									
									<?php /*<li><a href="<?php echo wp_logout_url('/') ?>">Log Out</a></li>*/ ?>
									
								<!--/menu--></ul>
								
							<!--/private-header-navigation--></div>
							
							<div class="clearfix"></div>
							
						<!--/private-header--></div>
						
						<!--Header Image?-->
						
						<!--Header Image?-->
						
						<?php if(get_field('turn_on_image_header')) { ?>
						
							<?php 
							    $pageImage_id = get_field('header_image');
							    $pageImage_size = "full"; // (thumbnail, medium, large, full or custom size)
							    $pageImage = wp_get_attachment_image_src( $pageImage_id, $pageImage_size );
							?>
							
							<?php if(get_field('is_this_a_gallery')) { ?>
								
							<?php } else { ?>
							
								<div class="page-header-image" style="background-image:url('<?php echo $pageImage[0]; ?>');"></div>
							
							<?php } ?>
							
						
						<?php } ?>
						
						<?php if(get_field('is_this_a_gallery')) { ?>
						
						<?php if(get_field('turn_on_image_header')) { ?>
						
							<div class="padder gallery-header-image" style="background-image:url('<?php echo $pageImage[0]; ?>');">
								
						<?php } else { ?>
						
							<div class="padder no-padder gallery-header-image" style="">
						
						<?php } ?>
							
							<div class="gallery-header-image-inner">
								
								<h1><?php the_title(); ?></h1>
								
								<?php the_content(); ?>
								
								<?php if(get_field('image_gallery')) { ?>
									<?php the_field('image_gallery'); ?>
								<? } ?>
						
						<?php } else { ?>
						
						<div class="padder">
						
						<?php } ?>
				
							<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			
						    	<?php get_template_part( 'parts/loop', 'page' ); ?>
						    
						    <?php endwhile; endif; ?>
						    
							<?php if(get_field('is_this_a_gallery')) { ?>
						    
						    <!--/gallery-header-image-inner--></div>
						    
						    <?php } ?>
						    
						<!--/padder--></div>	
					    
					<!--/private-main-content--></div>						
			    					
			</main> <!-- end #main -->
		    
		</div> <!-- end #inner-content -->

	</div> <!-- end #content -->

<?php get_footer(); ?>