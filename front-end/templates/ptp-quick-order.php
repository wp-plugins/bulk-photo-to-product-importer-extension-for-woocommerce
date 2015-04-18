<?php

/**
 * Displays quick order dialog
 */

$variations = ptp_variations();
?>

<div class="quick-order-dialog" title="<?php _e( 'Select a variation...', 'ptp' ); ?>">    
	<form class="quick-order-form">
            
	    <?php wp_nonce_field( 'ptp_products_add_to_cart', 'ptp_nonce' ); ?>

	    <input type="hidden" name="action" value="ptp_products_add_to_cart" />
	            
	    <?php if ( $variations ) : ?>
			
			<div class="elements-wrap">

		    	<select name="variation" class="quick-order-variation"> 
		        
		        <?php foreach ( $variations as $variation ) : ?>
		        	<option value="<?php echo $variation['name']; ?>"><?php echo $variation['name']; ?></option>
		        <?php endforeach; ?>            
		        </select>

		        <input type="submit" id="quick-order-submit" value="Add to Cart" />

		        <input type="button" id="quick-order-cancel" value="Cancel" />

		        <div class="queue-empty">
		        	<p>You didn't select any products... </p>

		        	<input type="button" id="empty-okay" class="okay" value="Okay" />
		        </div>

		        <div class="products-added">
		        	<p>Products have been successfully added to cart... </p>

		        	<input type="button" id="added-okay" class="okay" value="Okay" />
		        </div>

	    	</div>

	    <?php else : ?>
	        
	    <div class="no-variations">
			<p> We didn't find any variations... </p>

		    <input type="button" id="no-variations" class="okay" value="Okay" />
		</div>

	    <?php endif; ?>

   	</form>
</div> 