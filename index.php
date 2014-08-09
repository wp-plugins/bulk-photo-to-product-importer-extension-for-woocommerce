<?php
/**
 * Bulk Import
 */
?>

<?php 
global $ptp_importer;
$settings_obj = PTPImporter_Settings::getInstance();
$settings = $settings_obj->get();
$bptpi_category_naming_scheme_value = $settings['bptpi_category_naming_scheme'];
if ( isset($bptpi_category_naming_scheme_value) ) {
	$bptpi_category_naming_scheme = $settings['bptpi_category_naming_scheme'];
} else {
	$bptpi_category_naming_scheme = 'Category';
}

$plugin_name = rawurlencode( 'Photo to Product Importer Extension for WooCommerce' );
$plugin_url = rawurlencode( 'http://goo.gl/sCkeS' );
$plugin_desc = rawurlencode( 'Are you a photographer who wants to sell your products on your WordPress website but you find the native WooCommerce interface to limit your ability to sell your product? Well we created this WordPress Plugin for users whose business model is centric to selling photography in an eCommerce environment...' );
$plugin_desc_twitter = rawurlencode( 'Are you a photographer who wants to sell your products on your WordPress website but you find the native WooCommer...' );
$plugin_image = rawurlencode( $ptp_importer->plugin_uri . '/screenshot-1.png' );

$submit = __( 'Save and Continue', 'ptp' );
$action = 'ptp_product_import';
?>
<div class="icon32" id="ptp-icon32"><br></div>
<h2><?php _e( 'Bulk Import Photos', 'ptp' ); ?> </h2>

<div class="error" style="display:none">
	<p></p>
</div>

<div class="updated" style="display:none">
	<p></p>
</div>

<?php if ( $_GET['ptp_sm_hide'] != 1 && !get_user_meta( get_current_user_id(), 'ptp_hide_sm_box', true ) ) : ?>
<?php echo $ptp_importer->sm_share_buttons->display( array( 'mini' => true ) ); ?>
<?php else: ?>
<?php add_user_meta( get_current_user_id(), 'ptp_hide_sm_box', 1 ); ?>
<?php endif; ?>

<form class="photos-import-form">
	<?php wp_nonce_field( 'ptp_product_import', 'ptp_nonce' ); ?>

	<div id="ptp-cols" style="display:none;">
		<div id="ptp-col-left">
			<div class="wp-box">
				
				<div class="form-field category">
					<span><?php echo ptp_dropdown_categories( array( 'name' => 'term_id', 'show_option_none' => 'Select a ' . $bptpi_category_naming_scheme, 'walker' => new Walker_Without_Children() ) ); ?></span>
					<span class="add-category  dashicons dashicons-plus-alt"></span>
					<div class="quick-add-category-con"></div>
				</div>
				
				<div class="form-field variation">
				
					<p class="dropdown">
					
						<span>
							<?php echo ptp_dropdown_categories( array( 'name' => 'variation_group[]', 'class' => 'variation-group', 'taxonomy' => $ptp_importer->taxonomy, 'show_option_none' => 'Select Variation Group', 'walker' => new Walker_With_Variations() ) ); ?>
						</span>
						
						<a href="<?php echo admin_url() . '/admin.php?page=ptp_variations'; ?>" target="_blank" class="add-variation dashicons dashicons-plus-alt">
						</a>
						
						<!-- <img src="<?php echo $ptp_importer->plugin_uri . '/assets/images/question_mark.png' ?>" class="tooltip-icon" /> -->
						<small class="tooltip-icon"><i class="dashicons dashicons-info"></i></small>
						
					</p>
					
					<div class="tooltip-content" style="display:none"><span class="tooltip-arrow"></span><p>Variation Groups are created by you. They contain Variations of your Products. For example, you may sell a Downloadable version of your Photo, as well as a 10 x 10 Frame and a 12 x 12 Frame - each with their own price. These are called Variations.</p></div>
				</div>

				<div class="form-field">
					<label><?php _e( 'Date of the ' . $bptpi_category_naming_scheme, 'ptp' ) ?></label>
					<p class="date">
						<input type="text" name="date" class="datepicker" value="" placeholder="<?php echo date('m/j/Y'); ?>" />
					</p>
				</div>
				
				<?php if ( ptp_is_active() ) : ?>

				<?php do_action( 'ptp_after_date_field' ); ?>

				<?php endif; ?>

			</div>

		</div>

		<div id="ptp-col-right">	

			<div class="wp-box">
				<div class="attachment-area hint">
				   	<div id="upload-container">
						<div class="upload-filelist">
						</div>

						<p class="import"><a class="browser button button-hero" id="upload-pickfiles" href="#">Select Photos</a></p>
					</div>
				</div>
				
				<div class="form-field">
				    <input type="hidden" name="action" value="<?php echo $action; ?>" />
				    <input type="submit" id="import_photos" class="ptp-button-primary" value="<?php echo esc_attr( $submit ); ?>">
				</div>	
			</div>

		</div>

		<div class="clear"></div>

	</div>
</form>