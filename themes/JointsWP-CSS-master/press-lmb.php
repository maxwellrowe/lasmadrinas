<div class="section" id="endowments-shortcode">
	<div class="section-inner">
				
		<div class="row small-up-1 medium-up-3 large-up-3">
					<?php 
						$press_args = array(
							'post_type'    => 'press',
							'posts_per_page' => '100',
							'showposts'    => -1,
							'post_status'  => 'publish',
							'parent' => 0,
							'hide_empty' => true,
							'tax_query' => array(
								array(
									'taxonomy' => 'press_category',
									'field'    => 'slug',
									'terms'    => array( 'las-madrinas-ball' ),
									'operator' => 'IN',
							   )
							),
						);
					?>
					
					<?php $the_query = new WP_Query( $press_args ); ?>
			
						<?php if ( $the_query->have_posts() ) while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
						
							<div class="column column-block">
								
								<div class="endowment-card">
								
									<div class="endowment-image">
										<?php the_post_thumbnail('endowment_cover'); ?>
									</div>
									<div class="endowment-content">
										
										<span class="pre-header"><?php the_date('m/d/Y'); ?></span>
										
										<?php if(get_field('link_to_press_item')) { ?>
										
											<h2><a href="<?php the_field('link_to_press_item'); ?>"><?php the_title(); ?></a></h2>
											
										<?php } else { ?>
										
											<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
									
										<?php } ?>	
										
										<span class="endowment-amount"><?php the_field('publication'); ?></span>
										
										<hr>
										
										<?php if(get_field('link_to_press_item')) { ?>
										
											<a href="<?php the_field('link_to_press_item'); ?>" target="_blank"><?php if(get_field('link_text')) { ?><?php the_field('link_text'); ?><?php } else { ?>View<?php } ?> <span class="fa fa-long-arrow-right"></span></a>
										
										<?php } else { ?>
										
											<a href="<?php the_permalink(); ?>"><?php if(get_field('link_text')) { ?><?php the_field('link_text'); ?><?php } else { ?>Read On<?php } ?> <span class="fa fa-long-arrow-right"></span></a>
											
											
										
										<?php } ?>
										
									<!--/endowment-content--></div>
									
								<!--/endowment-card--></div>
								
							<!--/column--></div>
						
						<?php endwhile; // End the loop. Whew. ?>

					<?php wp_reset_query(); ?>
					
		<!--/row--></div>
				
	<!--/section-inner--></div>
<!--/section--></div>