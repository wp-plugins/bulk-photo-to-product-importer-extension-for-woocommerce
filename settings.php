<?php
/**
 *  Settings page
 */
?>

<?php

global $ptp_importer;
$settings_obj = PTPImporter_Settings::getInstance();
$settings = $settings_obj->get();

?>

<div class="icon32" id="ptp-icon32"><br></div>
<h2><?php _e( 'Settings' ); ?></h2>

<div class="error" style="display:none">
	<p></p>
</div>

<div class="updated" style="display:none">
	<p></p>
</div>

<div id="ptp-cols" style="display:none;">
	
	<div id="ptp-col-right">
		<div class="wp-box">
			<div class="inner">
				<h3 class="h2"><?php _e( $ptp_importer->plugin_name, 'ptp' ) ?></h3>
				<p> You might be interested in our other plugins: </p>
				<p>
					<ul>
						<li><a href="http://www.theportlandcompany.com/shop/custom-web-applications/custom-pointers-plugin-for-wordpress/" title="Custom Pointers Plugin for WordPress" target="_blank">Custom Pointers Plugin for WordPress</a></li>
					</ul>
				</p>
				<?php echo $ptp_importer->sm_share_buttons->display( array( 'mini' => false ) ); ?>
			</div>
		</div>
	</div>

	<div id="ptp-col-left">
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<div class="wp-box">
			<?php 
			$status = get_option( '_ptp_status' );
			$sk_input_value = get_option( '_ptp_sk' );

			if ( $status == 'active' ) {
				$status_icon = '<span class="checked">Checked</span>';
				$sk_input_disabled = 'readonly';
				$sk_input_class = 'serial-key-disabled';
				$activate_button_label = 'Deactivate';
				$licence_form_class = 'license-form-deactivate';
				$licence_form_action = 'ptp_deactivate';
			} else if ( $status == 'inactive' || !$status ) {
				$status_icon = '<span class="failed">Failed</span>';
				$sk_input_disabled = '';
				$sk_input_class = '';
				$activate_button_label = 'Activate';
				$licence_form_class = 'license-form-activate';
				$licence_form_action = 'ptp_activate';
			}

			if ( !$status ) {
				$status_icon = '';
			}

			?>
			<div class="title"><h3><?php _e( 'License', 'ptp' ); ?></h3></div>
			<form id="license-form" class="<?php echo $licence_form_class; ?>">
				<input type="hidden" name="action" value="<?php echo $licence_form_action; ?>" />

				<table class="ptp-input widefat">
					<tbody>
						<?php if ( !ptp_is_active() ) : ?>
						<tr>
							<td class="label">
								<label><?php _e( 'Premium Features', 'ptp' ); ?></label>
							</td>
							<td>
								<ul id="premium-features">
									<li><b>Private Events and Photos</b> - We understand that sometimes photo’s need to be kept private. That’s why you can give Users access to specific Events, preventing all other Users from being able to view the photo’s within that event or assign a generic password to that event for anyone to use to view them.</li>
									<li><b>Widgets</b> - We’ve also created an advanced Search by Event or Search by Date WordPress Widget.</li>
									<li><b>Custom Watermark</b> - You can upload your own custom image that is dynamically embedded onto your images as a watermark prior to purchase.</li>
								</ul>
								<p><a class="ptp-button-primary" href="http://www.theportlandcompany.com/shop/custom-web-applications/bulk-photo-to-product-importer-extension-for-woocommerce/" title="<?php echo $ptp_importer->plugin_name; ?>" target="_blank">Upgrade to premium version &raquo;</a></p>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td class="label">
								<label><?php _e( 'Serial Key', 'ptp' ); ?></label>
							</td>
							<td>
								<input id="serial-key" class="<?php echo $sk_input_class; ?>" type="text" name="serial_key" maxlength="32" placeholder="Serial Key" value="<?php echo $sk_input_value; ?>" <?php echo $sk_input_disabled; ?> /> <?php echo $status_icon; ?>
								<p class="ptp-subtext"><?php _e( 'The serial key provided to you via email after purchase.' ); ?></p>
							</td>
						</tr>
						<tr>
							<td class="label">
							</td>
							<td>
								<input type="submit" id="ptp-activate" class="ptp-button-primary" name="activate" value="<?php _e( $activate_button_label, 'ptp'); ?>" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>
		<?php endif; ?>
		
		<div class="wp-box">
			<?php 

			$settings_action = 'ptp_settings_save';
			$hide_variations = $settings['hide_variations'];
			$interval = $settings['interval'];
			$submit_button_label = 'Save';

			$variation_migrate = new PTPImporter_Variation_Migrate();
			?>
			<div class="title"><h3><?php _e( 'General', 'ptp' ); ?></h3></div>
			<form id="general-settings-form">

				<?php wp_nonce_field( 'ptp_settings_save', 'ptp_nonce' ); ?>
				<input type="hidden" name="action" value="<?php echo $settings_action; ?>" />

				<table class="ptp-input widefat">
					<tbody>
						
						<?php if ( ptp_is_active() && class_exists( 'BPTPI_Premium' ) ) : ?>
						<?php do_action( 'ptp_before_hide_variations_general_settings', $settings ); ?>
						<?php endif; ?>
						
						<tr>
							<td class="label">
								<label><?php _e( 'Time interval for each product creation', 'ptp' ); ?></label>
							</td>
							<td>
								<input type="text" id="interval" class="general-settings-input" name="interval" value="<?php echo $interval; ?>" />
								<p class="ptp-subtext" for="interval"><?php _e( 'This will be used as the length of time the plugin will sleep after creating each product. This prevents the server from timing out. Increase it as needed.' ); ?></p>
							</td>
						</tr>

						<tr>
							<td class="label">
								<label><?php _e( 'Hide Variations', 'ptp' ); ?></label>
							</td>
							<td>
								<input type="checkbox" id="hide-variations" class="general-settings-input" name="hide_variations" value="0" <?php checked( $hide_variations, 1 ); ?> />
								<label class="ptp-subtext" for="hide-variations"><?php _e( 'Check this if you want to hide Variations from the products list.' ); ?></label>
							</td>
						</tr>
						<?php if ( $variation_migrate->groups() ) : ?>
						<tr>
							<td class="label">
								<label><?php _e( 'Migrate', 'ptp' ); ?></label>
							</td>
							<td>
								<input type="button" id="migrate-variations" class="ptp-button-secondary" value="<?php _e( 'Migrate', 'ptp'); ?>" />
								<p class="ptp-subtext"><?php _e( 'Migrate old variatons.' ); ?></p>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td class="label">
							</td>
							<td>
								<input type="submit" class="ptp-button-primary" value="<?php _e( $submit_button_label, 'ptp'); ?>" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>
		
		<?php if ( ptp_is_active() && class_exists( 'BPTPI_Premium' ) ) : ?>
		<div class="wp-box">
            <?php 

            $settings_action = 'ptp_settings_save';
            $submit_button_label = 'Save';

            ?>
            <div class="title"><h3><?php _e( 'Categories', 'ptp' ); ?></h3></div>
            <form id="categories-settings-form">

                <?php wp_nonce_field( 'ptp_settings_save', 'ptp_nonce' ); ?>
                <input type="hidden" name="action" value="<?php echo $settings_action; ?>" />

                <table class="ptp-input widefat">
                    <tbody>

                        <?php do_action( 'ptp_before_submit_categories_settings', $settings ); ?>

                        <tr>
                            <td class="label">
                            </td>
                            <td>
                                <input type="submit" class="ptp-button-primary" value="<?php _e( $submit_button_label, 'ptp'); ?>" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
		<?php endif; ?>

	</div>

	<div class="clear"></div>

</div>