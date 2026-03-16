<?php if(get_sub_field('images')) { ?>

	<!--IF HAS AN IMAGE-->
	
	<?php if( get_sub_field('image_width') == "half") { ?>
	
		<div class="testimonial-image-wrapper test-image-half">
			
	<?php } elseif( get_sub_field('image_width') == "third") { ?>
		
		<div class="testimonial-image-wrapper test-image-third">
			
	<?php } ?>
	
			<?php if( get_sub_field('color') == "light-blue") { ?>
		
				<div class="testimonial-carousel owl-carousel owl-theme test-light-blue">
					
			<?php } elseif( get_sub_field('color') == "blue") { ?>
			
				<div class="testimonial-carousel owl-carousel owl-theme test-blue">
					
			<?php } elseif( get_sub_field('color') == "white") { ?>
			
				<div class="testimonial-carousel owl-carousel owl-theme test-white">
					
			<?php } ?>
			
				<!--Loop for Images and Testimonials-->
				
				<?php if( have_rows('testimonials') ): while ( have_rows('testimonials') ) : the_row(); ?>
				
					<?php 
						$test_id = get_sub_field('image');
						$test_size = "testimonial_image"; // (thumbnail, medium, large, full or custom size)
						$test_image = wp_get_attachment_image_src( $test_id, $test_size );
					?>
				
			        <div class="testimonial-item item">
					
						<div class="test-image test-left">
							
							<img src="<?php echo $test_image[0]; ?>" alt="" />
							
						<!--/test-image--></div>
						
						<div class="test-right">
							
							<div class="test-border">
								
								<div class="test-content">
									
									<?php the_sub_field('testimonial_content'); ?>
									
								<!--/test-content--></div>
								
							<!--/border--></div>
							
						<!--/test-right--></div>
				
					<!--/testimonial-item--></div>
				
				<?php endwhile; else : endif; ?>
				
			<!--/testimonial-carousel--></div>
			
		<!--/testimonial-image-wrapper--></div>

<?php } else { ?>

	<!--NO IMAGE-->
	
	<div class="testimonial-no-image-wrapper">
	
		<?php if( get_sub_field('color') == "light-blue") { ?>
			
			<div class="testimonial-carousel owl-carousel owl-theme test-light-blue <?php if(get_sub_field('elegant_border')) { ?>test-elegant-border<?php } ?>">
				
		<?php } elseif( get_sub_field('color') == "blue") { ?>
		
			<div class="testimonial-carousel owl-carousel owl-theme test-blue <?php if(get_sub_field('elegant_border')) { ?>test-elegant-border<?php } ?>">
				
		<?php } elseif( get_sub_field('color') == "white") { ?>
		
			<div class="testimonial-carousel owl-carousel owl-theme test-white <?php if(get_sub_field('elegant_border')) { ?>test-elegant-border<?php } ?>">
				
		<?php } ?>
		
			<!--Loop for Images and Testimonials-->
			
			<?php if( have_rows('testimonials') ): while ( have_rows('testimonials') ) : the_row(); ?>
			
		        <div class="testimonial-item item">
					
					<div class="test-right">
						
						<div class="test-border"><!--/border--></div>
							
						<div class="test-content">
							
							<?php the_sub_field('testimonial_content'); ?>
							
						<!--/test-content--></div>
						
					<!--/test-right--></div>
			
				<!--/testimonial-item--></div>
			
			<?php endwhile; else : endif; ?>
			
		<!--/testimonial-carousel--></div>
		
	<!--/testimonial-no-image-wrapper--></div>

<?php } ?>