<?php
/**
 * Template part for displaying a single post
 */
?>

<div class="section">
	<div class="section-inner">
		
		<div class="row">
		
			<div class="small-12 medium-8 columns">
	
				<article id="post-<?php the_ID(); ?>" <?php post_class(''); ?> role="article" itemscope itemtype="http://schema.org/BlogPosting">
										
					<header class="article-header">	
						<h1 class="entry-title single-title" itemprop="headline"><?php the_title(); ?></h1>
				    </header> <!-- end article header -->
									
				    <section class="entry-contents" itemprop="articleBody">
						
						<div class="endowment-single-content">
							<?php the_content(); ?>
						</div>
					</section> <!-- end article section -->
										
					<footer class="article-footer">
						<?php wp_link_pages( array( 'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'jointswp' ), 'after'  => '</div>' ) ); ?>
						<p class="tags"><?php the_tags('<span class="tags-title">' . __( 'Tags:', 'jointswp' ) . '</span> ', ', ', ''); ?></p>	
					</footer> <!-- end article footer -->
										
					<?php comments_template(); ?>	
																	
				</article> <!-- end article -->
				
			<!--/small-12--></div>
			
			<div class="small-12 medium-4 columns" id="endowment-details-rigth">
				
				<?php the_post_thumbnail('endowment_cover'); ?>
				
				<h2><?php the_field('amount'); ?></h2>
				
				<ul class="endowment-dets">
					
					<?php if(get_field('years')) { ?><li><?php the_field('years'); ?></li><?php } ?>
					<?php

					// check if the repeater field has rows of data
					if( have_rows('doctors') ):
					
					 	// loop through the rows of data
					    while ( have_rows('doctors') ) : the_row(); ?>
					
					        <li><?php the_sub_field('doctor'); ?></li>
					
					    <?php endwhile;
					
					else :
					
					    // no rows found
					
					endif;
					
					?>
					
				</ul>
				
				<?php if(get_field('pdf')) { ?>
	
						<a href="<?php the_field('pdf'); ?>"><?php if(get_field('link_text')) { ?><?php the_field('link_text'); ?><?php } else { ?>Download<?php } ?> <span class="fa fa-file-pdf-o"></span></a>

				<?php } ?>
				
				<hr>
				
				<a class="box-link large-box light-blue" href="<?php the_field('upload_impact_report_pdf','option'); ?>" target="_blank">
				
					<div class="box-link-inner">
					
						<div class="box-link-content">
						
							<p style="text-align: center;"><?php the_field('text_to_be_displayed','option'); ?></p>
						
							<!--/box-link-content--></div>
					
					<!--/box-link-inner--></div>
				
				<!--/box-link--></a>
				
				<a class="box-link large-box blue" href="<?php bloginfo('url'); ?>/support-us" style="margin-top: 2rem;">
				
					<div class="box-link-inner">
					
						<div class="box-link-content">
						
							<p style="text-align: center;">Support Us</p>
						
							<!--/box-link-content--></div>
					
					<!--/box-link-inner--></div>
				
				<!--/box-link--></a>
				
				<?php if (get_field('custom_sidebar_content')) { ?>
				
					<?php the_field('additional_custom_sidebar_content'); ?>
				
				<?php } else { ?>
				
					<a class="box-link large-box seafoam" data-toggle="myvideo" href="#" style="margin-top: 2rem;">
				
						<div class="box-link-inner">
						
							<div class="box-link-content">
							
								<p style="text-align: center;">Watch Video About CHLA</p>
							
								<!--/box-link-content--></div>
						
						<!--/box-link-inner--></div>
					
					<!--/box-link--></a>
					
					<div class="large reveal" id="myvideo" data-reveal data-reset-on-close="true">

				        <iframe width="560" height="315" src="https://www.youtube.com/embed/eHstmyNy9eY" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				
				      <button class="close-button close-reveal-modal" data-close aria-label="Close reveal" type="button">
				        <span aria-hidden="true">&times;</span>
				      </button>
				    </div>
				
				<?php } ?>
				
			<!--/medium-4--></div>
			
		<!--/row--></div>
		
	<!--/section-inner--></div>
<!--/section-inner--></div>