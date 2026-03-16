<?php if( get_sub_field('size') == "small") { ?>

	<?php if(get_sub_field('custom_link')) { ?>
	
		<a class="box-link small-box" href="<?php the_sub_field('custom_link'); ?>">
	
	<?php } else { ?>
	
		<a class="box-link small-box" href="<?php the_sub_field('link'); ?>">
	
	<?php } ?>
		
		<div class="box-link-inner">
			
			<div class="box-link-content">
				
				<?php the_sub_field('content_small'); ?>
				
			<!--/box-link-content--></div>
			
		<!--/box-link-inner--></div>
		
	<!--/box-link--></a>
	
<?php } else { ?>

	<?php if(get_sub_field('custom_link')) { ?>
	
		<a class="box-link large-box <?php the_sub_field('color'); ?>" href="<?php the_sub_field('custom_link'); ?>">
	
	<?php } else { ?>
	
		<a class="box-link large-box <?php the_sub_field('color'); ?>" href="<?php the_sub_field('link'); ?>">
	
	<?php } ?>
		
		<div class="box-link-inner">
			
			<div class="box-link-content">
				
				<?php the_sub_field('content_large'); ?>
				
			<!--/box-link-content--></div>
			
		<!--/box-link-inner--></div>
		
	<!--/box-link--></a>

<?php } ?>