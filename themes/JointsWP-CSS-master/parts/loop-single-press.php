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
						
						<div class="endowment-single-content"><?php the_content(); ?></div>
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
				
			<!--/medium-4--></div>
			
		<!--/row--></div>
		
	<!--/section-inner--></div>
<!--/section-inner--></div>