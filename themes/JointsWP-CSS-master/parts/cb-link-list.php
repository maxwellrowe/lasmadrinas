<ul class="link-list">
	<?php
	
	// check if the repeater field has rows of data
	if( have_rows('add_links') ):
	
	 	// loop through the rows of data
	    while ( have_rows('add_links') ) : the_row(); ?>
	
	        <?php if( get_sub_field('type') == "link") { ?>
	        
	        	<li>
	        		<a href="<?php the_sub_field('link_url'); ?>"><?php the_sub_field('link_text'); ?> <span class="fa fa-download"></span></a>
	        	</li>
	        
	        <?php } elseif( get_sub_field('type') == "file") { ?>
	        
	        	<li><a href="<?php the_sub_field('upload_file'); ?>"><?php the_sub_field('link_text'); ?> <span class="fa fa-download"></span></a></li>
	        
	        <?php } elseif( get_sub_field('type') == "page") { ?>
	        
	        	<li><a href="<?php the_sub_field('select_page'); ?>"><?php the_sub_field('link_text'); ?> <span class="fa fa-long-arrow-right"></span></a></li>
	        
			<?php } ?>
	
	    <?php endwhile;
	
	else :
	
	    // no rows found
	
	endif;
	
	?>
</ul>