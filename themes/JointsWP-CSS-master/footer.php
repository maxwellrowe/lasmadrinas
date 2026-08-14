<?php
/**
 * The template for displaying the footer. 
 *
 * Comtains closing divs for header.php.
 *
 * For more info: https://developer.wordpress.org/themes/basics/template-files/#template-partials
 */			
 ?>
					
				<?php if(is_page()) { ?>
				
				<?php 
				    $footerimg_id = get_field('pre_footer_image','option');
				    $footerimg_size = "full"; // (thumbnail, medium, large, full or custom size)
				    $footer_image = wp_get_attachment_image_src( $footerimg_id, $footerimg_size );
				?>
				
					<div class="pre-footer">
						
						<div class="row">
							
							<div class="small-12 columns">
								
								<?php echo do_shortcode('[dec-line-light]'); ?>
								
								<ul>
									<li><?php the_field('pre_footer_text','option'); ?></li>
									<li><img src="<?php echo $footer_image[0]; ?>" /></li>
								</ul>
								
								<hr>
								
							<!--/small-12--></div>
							
						<!--/row--></div>
						
					<!--/pre-footer--></div>
					
				<?php } ?>
				
				<footer class="footer" role="contentinfo">
					
					<?php if(is_singular( array( 'member', 'debutante', 'stag' ) )) { ?>
						
						<div class="inner-footer expanded row">
					
					<?php } else { ?>
					
						<div class="inner-footer row">
					
					<?php } ?>
						
						<div class="small-12 medium-6 large-6 columns">
							<nav role="navigation">
	    						<?php joints_footer_links(); ?>
	    					</nav>
	    				</div>
						
						<div class="small-12 medium-6 large-6 columns">
							<div class="footer-right">
								<div class="footer-social-media">
									<?php
									$social_media = function_exists( 'get_field' ) ? get_field( 'social_media', 'option' ) : array();

									if ( is_array( $social_media ) ) :
										foreach ( $social_media as $social_item ) :
											$icon = isset( $social_item['font_awesome_icon'] ) ? trim( $social_item['font_awesome_icon'] ) : '';
											$link = isset( $social_item['link'] ) ? $social_item['link'] : '';
											$link = is_array( $link ) && isset( $link['url'] ) ? $link['url'] : $link;

											if ( ! $icon || ! $link ) {
												continue;
											}
											?>
											<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><span class="<?php echo esc_attr( $icon ); ?>"></span></a>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>
								<p class="source-org copyright">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>, All Rights Reserved.</p>
							</div>
						</div>
					
					</div> <!-- end #inner-footer -->
				
				</footer> <!-- end .footer -->
			
			</div>  <!-- end .off-canvas-content -->
					
		</div> <!-- end .off-canvas-wrapper -->
		
		<?php the_field('custom_html'); ?>
		
		<?php wp_footer(); ?>
		
	</body>
	
</html> <!-- end page -->
