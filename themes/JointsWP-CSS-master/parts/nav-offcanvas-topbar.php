<?php
/**
 * The off-canvas menu uses the Off-Canvas Component
 *
 * For more info: http://jointswp.com/docs/off-canvas-menu/
 */
?>

<!--First check if is a page, then if there is a header image, then if that header image includes a Hero statement-->

<?php if(is_page()) { ?>

	<?php if(get_field('turn_on_image_header')) { ?>
	
		<?php if(get_field('header_hero_text')) { ?>
			
			<div class="header-with-hero-image">
		
				<?php if(is_singular( array( 'member', 'debutante', 'stag' ) )) { ?>

				<div class="expanded row">
					
				<?php } else if(is_page('your-profile')) { ?>
				
				<div class="expanded row">
					
				<?php } else { ?>
				
				<div class="row">
				
				<?php } ?>
		
					<div class="small-12">
				
						<div class="top-bar" id="top-bar-menu">
							<div class="top-bar-left float-left">
								<ul class="menu">
									<li><a href="<?php echo home_url(); ?>"><img src="<?php the_field('logo','option'); ?>" /></a></li>
								</ul>
							</div>
							<?php if( current_user_can('member') ) {  ?> 
							
								<div class="top-bar-right show-for-large">
									<?php joints_member_nav(); ?>	
								</div>
								
							<?php } elseif( current_user_can('administrator') ) { ?>
				
								<div class="top-bar-right show-for-large">
									<?php joints_member_nav(); ?>
								</div>
							
							<?php } elseif( current_user_can('debutante') ) { ?>
							
								<div class="top-bar-right show-for-large">
									<?php joints_deb_nav(); ?>	
								</div>
							
							<?php } elseif( current_user_can('stag') ) { ?>
							
								<div class="top-bar-right show-for-large">
									<?php joints_stag_nav(); ?>	
								</div>
							
							<?php } else { ?>
							
								<div class="top-bar-right show-for-large">
									<?php joints_top_nav(); ?>	
								</div>
							
							<?php } ?>
							<div class="top-bar-right float-right hide-for-large">
								<ul class="menu">
									<!-- <li><button class="menu-icon" type="button" data-toggle="off-canvas"></button></li> -->
									<li><a data-toggle="off-canvas" class="mobile-opener">
										<span></span>
										<span></span>
										<span></span>
									</a></li>
								</ul>
							</div>
						</div>
						
					<!--/small-12--></div>
					
				<!--/row--></div> 
				
			<!--/header-hero-with-image--></div>
			
			<?php 
			    $pageImage_id = get_field('header_image');
			    $pageImage_size = "full"; // (thumbnail, medium, large, full or custom size)
			    $pageImage = wp_get_attachment_image_src( $pageImage_id, $pageImage_size );
			?>
			
			<div class="page-header-hero">
				
				<div class="page-header-image" style="background-image:url('<?php echo $pageImage[0]; ?>');">
					
					<div class="page-header-hero-statement">
						
						<h1><?php the_field('header_hero_text'); ?></h1>
						
					<!--/page-header-hero-statement--></div>
					
				</div>
				
			<!--/page-header-image--></div>
		
		<?php } else { ?>
		
			<div class="header-with-image">
			
				<?php if(is_singular( array( 'member', 'debutante', 'stag' ) )) { ?>

				<div class="expanded row">
					
				<?php } else if(is_page('your-profile')) { ?>
				
				<div class="expanded row">
					
				<?php } else { ?>
				
				<div class="row">
				
				<?php } ?>
		
					<div class="small-12">
				
						<div class="top-bar" id="top-bar-menu">
							<div class="top-bar-left float-left">
								<ul class="menu">
									<li><a href="<?php echo home_url(); ?>"><img src="<?php the_field('logo','option'); ?>" /></a></li>
								</ul>
							</div>
							<?php if( current_user_can('member') ) {  ?> 
							
								<div class="top-bar-right show-for-large">
									<?php joints_member_nav(); ?>	
								</div>
								
							<?php } elseif( current_user_can('administrator') ) { ?>
				
								<div class="top-bar-right show-for-large">
									<?php joints_member_nav(); ?>
								</div>
							
							<?php } elseif( current_user_can('debutante') ) { ?>
							
								<div class="top-bar-right show-for-large">
									<?php joints_deb_nav(); ?>	
								</div>
							
							<?php } elseif( current_user_can('stag') ) { ?>
							
								<div class="top-bar-right show-for-large">
									<?php joints_stag_nav(); ?>	
								</div>
							
							<?php } else { ?>
							
								<div class="top-bar-right show-for-large">
									<?php joints_top_nav(); ?>	
								</div>
							
							<?php } ?>
							<div class="top-bar-right float-right hide-for-large">
								<ul class="menu">
									<!-- <li><button class="menu-icon" type="button" data-toggle="off-canvas"></button></li> -->
									<li><a data-toggle="off-canvas" class="mobile-opener">
										<span></span>
										<span></span>
										<span></span>
									</a></li>
								</ul>
							</div>
						</div>
						
					<!--/small-12--></div>
					
				<!--/row--></div> 
			
				<?php 
				    $pageImage_id = get_field('header_image');
				    $pageImage_size = "full"; // (thumbnail, medium, large, full or custom size)
				    $pageImage = wp_get_attachment_image_src( $pageImage_id, $pageImage_size );
				?>
				
			<!--/header-with-image--></div>
				
			<div class="page-header-image" style="background-image:url('<?php echo $pageImage[0]; ?>');"></div>
		
		<?php } ?>

	<?php } else { ?>
	
		<?php if(is_singular( array( 'member', 'debutante', 'stag' ) )) { ?>

		<div class="expanded row">
			
		<?php } else if(is_page('your-profile')) { ?>
		
		<div class="expanded row">
			
		<?php } else { ?>
		
		<div class="row">
		
		<?php } ?>
	
			<div class="small-12">
		
				<div class="top-bar" id="top-bar-menu">
					<div class="top-bar-left float-left">
						<ul class="menu">
							<li><a href="<?php echo home_url(); ?>"><img src="<?php the_field('logo','option'); ?>" /></a></li>
						</ul>
					</div>
					<?php if( current_user_can('member') ) {  ?> 
					
						<div class="top-bar-right show-for-large">
							<?php joints_member_nav(); ?>	
						</div>
						
					<?php } elseif( current_user_can('administrator') ) { ?>
				
						<div class="top-bar-right show-for-large">
							<?php joints_member_nav(); ?>
						</div>	
					
					<?php } elseif( current_user_can('debutante') ) { ?>
					
						<div class="top-bar-right show-for-large">
							<?php joints_deb_nav(); ?>	
						</div>
					
					<?php } elseif( current_user_can('stag') ) { ?>
					
						<div class="top-bar-right show-for-large">
							<?php joints_stag_nav(); ?>	
						</div>
					
					<?php } else { ?>
					
						<div class="top-bar-right show-for-large">
							<?php joints_top_nav(); ?>	
						</div>
					
					<?php } ?>
					<div class="top-bar-right float-right hide-for-large">
						<ul class="menu">
							<!-- <li><button class="menu-icon" type="button" data-toggle="off-canvas"></button></li> -->
							<li><a data-toggle="off-canvas" class="mobile-opener">
								<span></span>
								<span></span>
								<span></span>
							</a></li>
						</ul>
					</div>
				</div>
				
			<!--/small-12--></div>
			
		<!--/row--></div>
	
	<?php } ?>
	
<?php } else { ?>

	<?php if(is_singular( array( 'member', 'debutante', 'stag' ) )) { ?>

	<div class="expanded row">
		
	<?php } else if(is_page('your-profile')) { ?>
	
	<div class="expanded row">
		
	<?php } else { ?>
	
	<div class="row">
	
	<?php } ?>
		
		<div class="small-12">
	
			<div class="top-bar" id="top-bar-menu">
				<div class="top-bar-left float-left">
					<ul class="menu">
						<li><a href="<?php echo home_url(); ?>"><img src="<?php the_field('logo','option'); ?>" /></a></li>
					</ul>
				</div>
				<?php if( current_user_can('member') ) {  ?> 
				
					<div class="top-bar-right show-for-large">
						<?php joints_member_nav(); ?>	
					</div>
					
				<?php } elseif( current_user_can('administrator') ) { ?>
				
					<div class="top-bar-right show-for-large">
						<?php joints_member_nav(); ?>
					</div>
									
				<?php } elseif( current_user_can('debutante') ) { ?>
				
					<div class="top-bar-right show-for-large">
						<?php joints_deb_nav(); ?>	
					</div>
				
				<?php } elseif( current_user_can('stag') ) { ?>
				
					<div class="top-bar-right show-for-large">
						<?php joints_stag_nav(); ?>	
					</div>
				
				<?php } else { ?>
				
					<div class="top-bar-right show-for-large">
						<?php joints_top_nav(); ?>	
					</div>
				
				<?php } ?>
				<div class="top-bar-right float-right hide-for-large">
					<ul class="menu">
						<!-- <li><button class="menu-icon" type="button" data-toggle="off-canvas"></button></li> -->
						<li><a data-toggle="off-canvas" class="mobile-opener">
							<span></span>
							<span></span>
							<span></span>
						</a></li>
					</ul>
				</div>
			</div>
			
		<!--/small-12--></div>
		
	<!--/row--></div>

<?php } ?>

<?php if(is_page()) { ?>

	<?php 
	   global $post;    
	   $children = get_pages( array( 'child_of' => $post->ID ) );
	   if ( is_page() && ($post->post_parent || count( $children ) > 0  )) {
	?>
	
	<div class="child-navigation">
		
		<div class="row">
			
			<div class="small-12 medium-6 columns">
				
				<h1 class="child-navigation-title"><?php the_title(); ?></h1>
				
			<!--/small-12--></div>
			
			<div class="small-12 medium-6 columns">
				
				<ul class="text-right">
					
					<?php
				    //GET CHILD PAGES IF THERE ARE ANY
				    $children = get_pages('child_of='.$post->ID);
				 
				    //GET PARENT PAGE IF THERE IS ONE
				    $parent = $post->post_parent;
				 
				    //DO WE HAVE SIBLINGS?
				    $siblings =  get_pages('child_of='.$parent);
				 
				    if( count($children) != 0) {
				       $args = array(
				         'depth' => 1,
				         'title_li' => '',
						 'exclude' => '3178',
				         'child_of' => $post->ID
				       );
				 
				    } elseif($parent != 0) {
				        $args = array(
				             'depth' => 1,
				             'title_li' => '',
							 'exclude' => '3178',
				             'child_of' => $parent
				           );
				    }
				    //Show pages if this page has more than one sibling 
				    // and if it has children 
				    if(count($siblings) > 1 && !is_null($args))   
				    {?>
				    <li><?php wp_list_pages($args);  ?></li>
				     </div>
				    <?php } ?>
					
				<!--/ul--></ul>
				
			<!--/small-12--></div>
			
		<!--/row--></div>
		
	<!--/child-navigation--></div>
	
	<?php } ?>

<?php } ?>