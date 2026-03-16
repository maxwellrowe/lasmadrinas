<div class="fancy-list calendar-and-events-component">
	
	<div class="fancy-list-top row">
		
		<div class="small-12 medium-8 large-9 columns">
			
			<?php the_field('deb_calendar_and_event_title','option'); ?>
			
		<!--/medium-8--></div>
		
		<div class="small-12 medium-4 large-3 columns">
			
			<?php if(get_field('deb_upload_calendar_pdf','option')) { ?>
			
				<a href="<?php the_field('deb_upload_calendar_pdf','option'); ?>"><?php the_field('deb_calendar_pdf_link_text','option'); ?></a>
				
			<?php } ?>
			
		<!--/medium-4--></div>
		
		<div class="small-12 columns"><hr></div>
		
	<!--/fancy-list-top--></div>
	
	<div class="fancy-list-items">
		
		<ul>
			
			<li class="fancy-list-title">
			
				<div class="fl-left">
					Event
				<!--/fl-left--></div>
				
				<div class="fl-right">
					Date
				<!--/fl-right--></div>
				<div style="clear:both;"></div>
			</li>
			
			<?php

			// check if the repeater field has rows of data
			if( have_rows('deb_events','option') ):
			
			 	// loop through the rows of data
			    while ( have_rows('deb_events','option') ) : the_row(); ?>
			
			        <li class="fancy-list-item">
			
						<div class="fl-left">
							<span class="ce-title"><?php the_sub_field('title','option'); ?></span>
							<span class="ce-location"><?php the_sub_field('location','option'); ?></span>
						<!--/fl-left--></div>
						
						<div class="fl-right">
							<span class="ce-date"><?php the_sub_field('date','option'); ?></span>
							<span class="ce-time"><?php the_sub_field('time','option'); ?></span>
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