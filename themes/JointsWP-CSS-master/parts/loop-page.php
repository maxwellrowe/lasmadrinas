<article id="post-<?php the_ID(); ?>" <?php post_class(''); ?> role="article" itemscope itemtype="http://schema.org/WebPage">
						
	<?php if (get_field('background_image')) { ?>
	
	<?php } else { ?>
	
		<?php if (get_field('show_page_title')) { ?>
			<header class="article-header row">
				<div class="small-12 columns">
					<h1 class="page-title"><?php the_title(); ?></h1>
				<!--/small-12--></div>
			</header> <!-- end article header -->
		<?php } ?>
		
	<?php } ?>
					
    <section class="entry-content" itemprop="articleBody">
	    
	    <?php get_template_part( 'parts/content', 'builder' ); ?>
	    
	    <?php wp_link_pages(); ?>
	</section> <!-- end article section -->
						
	<footer class="article-footer">
		
	</footer> <!-- end article footer -->
						    
	<?php comments_template(); ?>
					
</article> <!-- end article -->