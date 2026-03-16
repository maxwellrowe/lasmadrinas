<?php
/**
 * The template part for displaying offcanvas content
 *
 * For more info: http://jointswp.com/docs/off-canvas-menu/
 */
?>

<?php if( current_user_can('member') ) {  ?> 
				
	<div class="off-canvas position-right" id="off-canvas" data-off-canvas>
		<?php joints_mem_off_canvas_nav(); ?>
	</div>
	
<?php } elseif( current_user_can('administrator') ) { ?>

	<div class="off-canvas position-right" id="off-canvas" data-off-canvas>
		<?php joints_mem_off_canvas_nav(); ?>
	</div>
					
<?php } elseif( current_user_can('debutante') ) { ?>

	<div class="off-canvas position-right" id="off-canvas" data-off-canvas>
		<?php joints_deb_off_canvas_nav(); ?>	
	</div>

<?php } elseif( current_user_can('stag') ) { ?>

	<div class="off-canvas position-right" id="off-canvas" data-off-canvas>
		<?php joints_stag_off_canvas_nav(); ?>	
	</div>

<?php } else { ?>

	<div class="off-canvas position-right" id="off-canvas" data-off-canvas>
		<?php joints_off_canvas_nav(); ?>	
	</div>

<?php } ?>