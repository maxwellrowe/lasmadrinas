<?php

$post_object = get_sub_field('choose_a_gallery_page');

if( $post_object ): 

	// override $post
	$post = $post_object;
	setup_postdata( $post ); 

	?>
    <div class="gallery-cover-image">
	    <a href="<?php the_permalink(); ?>">
		    <?php the_post_thumbnail('testimonial_image'); ?>
			<h2><?php the_title(); ?></h2>
		</a>
    </div>
    <?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
<?php endif; ?>