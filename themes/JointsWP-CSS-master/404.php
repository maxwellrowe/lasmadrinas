<?php
/**
 * The template for displaying 404 (page not found) pages.
 *
 * For more info: https://codex.wordpress.org/Creating_an_Error_404_Page
 */

get_header(); ?>
			
	<div class="content">

		<div class="inner-content">
	
			<main class="main" role="main">
				
				<div class="section">
					
					<div class="section-inner">
						
						<div class="row">
							
							<div class="small-12 columns">

								<article class="content-not-found">
								
									<header class="article-header">
										<h1><em><?php _e( 'Page Not Found', 'jointswp' ); ?></em></h1>
									</header> <!-- end article header -->
							
									<section class="entry-content">
										<p><?php _e( 'This page does not exist, has been removed, or may be protected.', 'jointswp' ); ?></p>
									</section> <!-- end article section -->
				
									<section class="search">
									    <p>If you are a member, please <strong><a href="<?php site_url(); ?>/login/">log in</a></strong> or <strong><a href="<?php site_url(); ?>">Return to Home</a></strong></p>
									</section> <!-- end search section -->
									
									<div style="display:block; height: 20rem;"></div>
							
								</article> <!-- end article -->
								
							</div>
							
						</div>
						
					</div>
					
				</div>
	
			</main> <!-- end #main -->

		</div> <!-- end #inner-content -->

	</div> <!-- end #content -->

<?php get_footer(); ?>