<div class="fancy-list">
	
	<div class="fancy-list-top row">
		
		<div class="small-12 medium-8 large-9 columns">
			
			<?php the_sub_field('title'); ?>
			
		<!--/medium-8--></div>
		
		<div class="small-12 medium-4 large-3 columns">
			
			<?php if(get_sub_field('right_link')) { ?>
			
				<a href="<?php the_sub_field('right_link'); ?>"><?php the_sub_field('right_link_text'); ?></a>
				
			<?php } ?>
			
		<!--/medium-4--></div>
		
		<div class="small-12 columns"><hr></div>
		
	<!--/fancy-list-top--></div>
	
	<div class="fancy-list-items">
		
		<ul>
			
			<li class="fancy-list-title">
			
				<div class="fl-left">
					<?php the_sub_field('column_1_heading'); ?>
				<!--/fl-left--></div>
				
				<div class="fl-right">
					<?php the_sub_field('column_2_heading'); ?>
				<!--/fl-right--></div>
				<div style="clear:both;"></div>
			</li>
			
			<?php

			// check if the repeater field has rows of data
			if( have_rows('items') ):
			
			 	// loop through the rows of data
			    while ( have_rows('items') ) : the_row(); ?>
			
			        <li class="fancy-list-item">
			
						<div class="fl-left">
							<?php if(get_sub_field('link')) { ?>
								<a href="<?php the_sub_field('link'); ?>"><?php the_sub_field('left'); ?></a>
							<?php } else { ?>
								<?php the_sub_field('left'); ?>
							<?php } ?>
						<!--/fl-left--></div>
						
						<div class="fl-right">
							<?php if(get_sub_field('link')) { ?>
								<a href="<?php the_sub_field('link'); ?>"><?php the_sub_field('right'); ?></a>
							<?php } else { ?>
								<?php the_sub_field('right'); ?>
							<?php } ?>
						<!--/fl-right--></div>
						<div style="clear:both;"></div>
					</li>
			
				<? endwhile;
			
			else :
			
			    // no rows found
			
			endif;
			
			?>
			
		</ul>
		
	<!--/fancy-list-items--></div>
	
<!--/fancy-list--></div>