<!-- The Content Builder -->

<!--
	First we run through the Repeater fields and then using flexible content build out the content sections. 
	The Repeater allows us to add options to a section, ie background color and then the flexible content allows 
	us to add components to each of those Repeater sections, this might include text, html, modals, etc.
-->

<?php 
	
	if (have_rows('section')) {
	  while (have_rows('section')) {
	    the_row(); ?>

			<?php if( get_sub_field('section_background') == "white") { ?>

				<div class="section <?php the_sub_field('add_a_custom_class'); ?>" <?php if(get_sub_field('padding')) { ?>style="padding: <?php the_sub_field('padding'); ?>;"<?php } ?> <?php if(get_sub_field('fade_in_animation')) { ?>data-aos="fade-up" data-aos-easing="linear"<?php } ?>>
					
			<?php } elseif ( get_sub_field('section_background') == "light-blue") { ?>
			
				<div class="section section-light-blue <?php the_sub_field('add_a_custom_class'); ?>" <?php if(get_sub_field('padding')) { ?>style="padding: <?php the_sub_field('padding'); ?>;"<?php } ?> <?php if(get_sub_field('fade_in_animation')) { ?>data-aos="fade-up" data-aos-easing="linear"<?php } ?>>
					
			<?php } elseif ( get_sub_field('section_background') == "light-gray") { ?>
			
				<div class="section section-light-gray <?php the_sub_field('add_a_custom_class'); ?>" <?php if(get_sub_field('padding')) { ?>style="padding: <?php the_sub_field('padding'); ?>;"<?php } ?> <?php if(get_sub_field('fade_in_animation')) { ?>data-aos="fade-up" data-aos-easing="linear"<?php } ?>>
					
			<?php } elseif ( get_sub_field('section_background') == "image") { ?>
			
				<?php 
					$sectionbg_id = get_sub_field('background_image');
					$sectionbgsize = "section-bg-desktop"; // (thumbnail, medium, large, full or custom size)
					$sectionbg = wp_get_attachment_image_src( $sectionbg_id, $sectionbgsize );
				?>
			
				<div class="section section-image <?php the_sub_field('add_a_custom_class'); ?>" style="background-image:url(<?php echo $sectionbg[0]; ?>);" <?php if(get_sub_field('fade_in_animation')) { ?>data-aos="fade-up" data-aos-easing="linear"<?php } ?>>
					<?php if(get_sub_field('turn_off_background_image_overlay')) { ?>
						<div class="section-image-inner section-image-no-overlay" <?php if(get_sub_field('padding')) { ?>style="padding: <?php the_sub_field('padding'); ?>;"<?php } ?>>
					<?php } else { ?>
						<div class="section-image-inner" <?php if(get_sub_field('padding')) { ?>style="padding: <?php the_sub_field('padding'); ?>;"<?php } ?>>
					<?php } ?>
					
			<?php } else { ?>
			
				<div class="section <?php the_sub_field('add_a_custom_class'); ?>" <?php if(get_sub_field('fade_in_animation')) { ?>data-aos="fade-up" data-aos-easing="linear"<?php } ?>>
					
			<?php } ?>
			
					<div class="section-inner">
			
						<!-- COUNT THE NUMBER OF ROWS TRANSLATE TO COLUMNS-->
				
						<?php $rows = get_sub_field('columns');
						if( have_rows('columns') ){
						$count = count($rows); ?>
						
						<?php if ( $count == 5 ) { ?>
					
							<div class="row small-up-12 medium-up-5 large-up-5">
								
						<?php } else { ?>
						
							<div class="row">
						
						<?php } ?>
						
						<!--CONTENT ABOVE -->
						
						<?php if(get_sub_field('content_above_columns')) { ?>
							<div class="small-12 columns">
								<?php the_sub_field('above_columns_content'); ?>
							<!--/small-12--></div>
							<div class="clearfix"></div>
						<?php } ?>
						
						<!-- THE LOOP FOR COLUMNS-->
						
						<?php while (have_rows('columns')) {
							the_row(); 
							?>
							    
							    <?php if (get_sub_field('custom_width')) { ?>
							    	<div class="small-12 <?php the_sub_field('custom_width'); ?> columns" <?php if(get_sub_field('custom_styles')) { ?>style="<?php the_sub_field('custom_styles'); ?>;"<?php } ?>>
								<?php } else { ?>
									<?php if ( $count == 1 ) { ?>
										<div class="small-12 columns" <?php if(get_sub_field('custom_styles')) { ?>style="<?php the_sub_field('custom_styles'); ?>;"<?php } ?>>
									<?php } elseif ( $count == 2 ) { ?>
										<div class="small-12 medium-6 columns" <?php if(get_sub_field('custom_styles')) { ?>style="<?php the_sub_field('custom_styles'); ?>;"<?php } ?>>
									<?php } elseif ( $count == 3 ) { ?>
										<div class="small-12 medium-4 columns" <?php if(get_sub_field('custom_styles')) { ?>style="<?php the_sub_field('custom_styles'); ?>;"<?php } ?>>
									<?php } elseif ( $count == 4 ) { ?>
										<div class="small-12 medium-3 columns" <?php if(get_sub_field('custom_styles')) { ?>style="<?php the_sub_field('custom_styles'); ?>;"<?php } ?>>
									<?php } elseif ( $count == 5 ) { ?>
										<div class="column column-block" <?php if(get_sub_field('custom_styles')) { ?>style="<?php the_sub_field('custom_styles'); ?>;"<?php } ?>>
									<?php } ?>		
								<?php } ?>
									    
								    <?php // check if the flexible content field has rows of data
										if( have_rows('component') ):
							
										// loop through the rows of data
										while ( have_rows('component') ) : the_row(); ?>
						        
									        <!--THE COMPONENTS-->
									        
									        <!-- HTML -->
								
									        <?php if( get_row_layout() == 'html' ): ?>
									
									        	<?php get_template_part( 'parts/cb', 'html' ); ?>
									        	
									        <!-- BASIC CONTENT -->
								
									        <?php elseif( get_row_layout() == 'basic_content' ): ?>
									
									        	<?php get_template_part( 'parts/cb', 'basic-content' ); ?>
									        	
									        <?php elseif( get_row_layout() == 'testimonial_carousel' ): ?>
									
									        	<?php get_template_part( 'parts/cb', 'testimonial' ); ?>
									        	
									        <?php elseif( get_row_layout() == 'card' ): ?>
									
									        	<?php get_template_part( 'parts/cb', 'card' ); ?>
									        	
									        <?php elseif( get_row_layout() == 'spacer' ): ?>
									
									        	<?php get_template_part( 'parts/cb', 'spacer' ); ?>
									        
									        <?php elseif( get_row_layout() == 'box_link' ): ?>
									
									        	<?php get_template_part( 'parts/cb', 'box' ); ?>
									        	
									        <?php elseif( get_row_layout() == 'link_list' ): ?>
									
									        	<?php get_template_part( 'parts/cb', 'link-list' ); ?>
									        	
									        <?php elseif( get_row_layout() == 'gallery_cover' ): ?>
									
									        	<?php get_template_part( 'parts/cb', 'gallery' ); ?>
									        	
									        <?php elseif( get_row_layout() == 'fancy_list' ): ?>
									
									        	<?php get_template_part( 'parts/cb', 'fancy-list' ); ?>
								        
								       <?php endif;
							
									    endwhile;
									
										else :
									
									    // no layouts found
									
										endif;
										
										?>
										
									<!--/columns--></div>
										
								<?php /*END OF COLUMNS*/
									 }
									
									} 
								?>
							
							<div class="clearfix"></div>
						
						<div class="clearfix"></div>
						
					<!--/row--></div>
				
				<!--/section-inner--></div>
				
			<?php if ( get_sub_field('section_background') == "image") { ?>
				<!--/section-image-inner--></div>
			<?php } ?>
			    
			<!--/section--></div>
	    
	    
	  <?php } // end first while have rows
	} // end first if have rows 

?>