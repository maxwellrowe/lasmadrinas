<div class="section" id="endowments-shortcode">
	<div class="section-inner">
		    	
		<div class="row small-up-1 medium-up-3 large-up-3">
			    	
					<?php query_posts( 'post_type=endowment&posts_per_page=100&offset=1'); ?>
			
						<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
						
							<div class="column column-block">
								
								<div class="endowment-card">
								
									<div class="endowment-image">
										<?php the_post_thumbnail('endowment_cover'); ?>
									</div>
									<div class="endowment-content">
										
										<span class="pre-header"><?php the_field('years'); ?></span>
										
										<?php if(get_field('link_to_pdf_instead_of_endowment_page')) { ?>
										
											<h2><a href="<?php the_field('pdf'); ?>"><?php the_title(); ?></a></h2>
											
										<?php } else { ?>
										
											<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
									
										<?php } ?>	
										
										<span class="endowment-amount"><?php the_field('amount'); ?></span>
										
										<hr>
										
										<?php if(get_field('link_to_pdf_instead_of_endowment_page')) { ?>
										
											<a href="<?php the_field('pdf'); ?>"><?php if(get_field('link_text')) { ?><?php the_field('link_text'); ?><?php } else { ?>Download<?php } ?> <span class="fa fa-file-pdf-o"></span></a>
										
										<?php } else { ?>
										
											<a href="<?php the_permalink(); ?>"><?php if(get_field('link_text')) { ?><?php the_field('link_text'); ?><?php } else { ?>Learn More<?php } ?> <span class="fa fa-long-arrow-right"></span></a>
											
											
										
										<?php } ?>
										
									<!--/endowment-content--></div>
									
								<!--/endowment-card--></div>
								
							<!--/column--></div>
						
						<?php endwhile; // End the loop. Whew. ?>

					<?php wp_reset_query(); ?>
			    	
		<!--/row--></div>
		    	
    <!--/section-inner--></div>
<!--/section--></div>