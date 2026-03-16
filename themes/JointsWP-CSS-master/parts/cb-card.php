<?php if( get_sub_field('color') == "light-blue") { ?>

	<div class="card card-light card-light-blue">
		
<?php } elseif( get_sub_field('color') == "tan") { ?>

	<div class="card card-light card-tan">
		
<?php } elseif( get_sub_field('color') == "blue") { ?>

	<div class="card card-dark card-blue">
		
<?php } elseif( get_sub_field('color') == "steel-blue") { ?>

	<div class="card card-dark card-steel-blue">
		
<?php } ?>

	<div class="card-border"></div>

	<?php if(get_sub_field('image')) { ?>
	
		<?php if( get_sub_field('image_position') == "right") { ?>
		
			<div class="card-image image-right">
				
				<div class="card-left">
					
					<div class="card-content">
						
						<?php the_sub_field('content'); ?>
						
					<!--/card-content--></div>
					
				<!--/card-left--></div>
				
				<div class="card-right">
					
					<?php 
						$card_right_id = get_sub_field('upload_image');
						$card_right_size = "card_right_image"; // (thumbnail, medium, large, full or custom size)
						$card_right_image = wp_get_attachment_image_src( $card_right_id, $card_right_size );
					?>
					
					<img src="<?php echo $card_right_image[0]; ?>" alt="" />
					
				<!--/card-right--></div>
				
			<!--/card-image--></div>
		
		<?php } else { ?>
		
			<div class="card-image image-top">
				
				<div class="card-top">
					
					<?php 
						$card_top_id = get_sub_field('upload_image');
						$card_top_size = "card_top_image"; // (thumbnail, medium, large, full or custom size)
						$card_top_image = wp_get_attachment_image_src( $card_top_id, $card_top_size );
					?>
					
					<img src="<?php echo $card_top_image[0]; ?>" alt="" />
					
				<!--/card-top--></div>
				
				<div class="card-bottom">
					
					<div class="card-content">
						
						<?php the_sub_field('content'); ?>
						
					<!--/card-content--></div>
					
				<!--/card-bottom--></div>
				
			<!--/card-image--></div>
		
		<?php } ?>
	
	<?php } else { ?>
	
		<div class="card-content">
			
			<?php the_sub_field('content'); ?>
			
		<!--/card-content--></div>
	
	<?php } ?>
	
<!--/card--></div>