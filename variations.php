<?php
/**
 * Variations
 */
?>

<?php global $ptp_importer; ?>

<div class="icon32" id="ptp-icon32"><br></div>
<h2><?php _e( 'Add Variation Groups', 'ptp' ); ?> </h2>

<?php if ( $_GET['ptp_sm_hide'] != 1 && !get_user_meta( get_current_user_id(), 'ptp_hide_sm_box', true ) ) : ?>
<?php echo $ptp_importer->sm_share_buttons->display( array( 'mini' => true ) ); ?>
<?php else: ?>
<?php add_user_meta( get_current_user_id(), 'ptp_hide_sm_box', 1 ); ?>
<?php endif; ?>

<div class="add-manage-wrap" id="col-container" style="display:none;">
	<div class="manage" id="col-right">
		<div class="col-wrap">
			<div class="tablenav top">
				<div class="alignleft actions">
					<select name="action">
						<option value="-1" selected="selected"><?php _e( 'Bulk Actions', 'ptp' ) ?></option>
						<option value="delete" ><?php _e( 'Delete', 'ptp' ) ?></option>
					</select>
					<input type="button" id="doaction" class="button action" value="Apply" />
				</div>
				<div class="tablenav-pages one-page">
				</div>
			</div>
			<table class="wp-list-table widefat fixed tags" cellspacing="0">
				<thead>
					<tr>
						<th scope="col" id="cb" class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-1"> <?php _e( 'Select All', 'ptp' ) ?> </label>
							<input id="cb-select-all-1" type="checkbox" />
						</th>
						<th scope="col" id="name" class="manage-column column-name sortable desc">
							<a><span href=""> <?php _e( 'Name', 'ptp' ) ?> </span><span class="sorting-indicator"></span></a>
						</th>
						<th scope="col" id="description" class="manage-column column-description sortable desc">
							<a><span href=""> <?php _e( 'Description', 'ptp' ) ?> </span><span class="sorting-indicator"></span></a>
						</th>
						<th scope="col" id="variations" class="manage-column column-variations num sortable desc" >
							<a><span href=""> <?php _e( 'Variations', 'ptp' ) ?> </span><span class="sorting-indicator"></span></a>
						</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th scope="col" class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-1"> <?php _e( 'Select All', 'ptp' ) ?> </label>
							<input id="cb-select-all-1" type="checkbox" />
						</th>
						<th scope="col" class="manage-column column-name sortable desc">
							<a><span href=""> <?php _e( 'Name', 'ptp' ) ?> </span><span class="sorting-indicator"></span></a>
						</th>
						<th scope="col" class="manage-column column-description sortable desc">
							<a><span href=""> <?php _e( 'Description', 'ptp' ) ?> </span><span class="sorting-indicator"></span></a>
						</th>
						<th scope="col" class="manage-column column-variations num sortable desc">
							<a><span href=""> <?php _e( 'Variations', 'ptp' ) ?> </span><span class="sorting-indicator"></span></a>
						</th>
					</tr>
				</tfoot>
				<tbody id="the-list">
					<?php 
					$variations_obj = PTPImporter_Variation_Group::getInstance();
    				$groups = $variations_obj->all();
    				$count = 0;
					?>
					
					<?php if ( !$groups ) : ?>
					<tr class="no-variation-groups">
						<td colspan="4">
							<p> <?php _e( 'You have not added any variation groups yet.', 'ptp' ) ?> </p>
						</td>
					</tr>
					<?php endif; ?>
					
					<?php foreach( $groups as $group ) : ++$count; ?>
					<?php $alternate = $count % 2 == 0 ? '' : 'class="alternate"'; ?>
					<tr <?php echo $alternate; ?>>
						<th scope="row" class="check-column">
							<label class="screen-reader-text" for="cb-select-<?php echo $group->term_id; ?>" > <?php _e( 'Select ' . $group->name, 'ptp' ) ?> </label>
							<input type="checkbox" name="delete_variations_groups[]" value="<?php echo $group->term_id; ?>"  for="cb-select-<?php echo $group->term_id; ?>" />
						</th>
						<td class="name column-name">
							<strong><a href="#"><?php echo $group->name; ?></a></strong>
							<div class="row-actions">
								<span class="inline hide-if-no-js"> <a href="#" class="quick-edit-variations-group" data-id="<?php echo $group->term_id; ?>"> <?php _e( 'Edit Variation Group', 'ptp' ) ?> </a> &#124; </span>
								<span class="delete"> <a href="#" class="delete-variations-group" data-id="<?php echo $group->term_id; ?>"> <?php _e( 'Delete', 'ptp' ) ?> </a> </span>
							</div>
						</td>
						<td class="description column-description"> <span><?php echo $group->description; ?> </span></td>
						<td class="variations column-variations"> <span><?php echo sizeof( $group->variations ) ?> </span></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div class="tablenav bottom">
				<div class="alignleft actions">
					<select name="action">
						<option value="-1" selected="selected"><?php _e( 'Bulk Actions', 'ptp' ) ?></option>
						<option value="delete" ><?php _e( 'Delete', 'ptp' ) ?></option>
					</select>
					<input type="button" id="doaction2" class="button action" value="Apply" />
				</div>
				<div class="tablenav-pages one-page">
				</div>
			</div>
		</div>
	</div>

	<div class="add" id="col-left">
		<div class="col-wrap">
			<div class="form-wrap">
				
				<p class="page-desc">BPTPI allows you to create Variations of your products and then Group them together so you may associate them with specific Events during the bulk import process. An example of a Variation Group would be Sizes, which contains three Variations: Small, Medium and Large. Each with their own price which the user must choose prior to adding the Product to their Cart.</p>

				<?php 
				$add_submit = __( 'Add Variation Group', 'ptp' );
				$add_action = 'ptp_variations_group_add';
				$add_args = array(
					'echo'  => 1,
				    'hierarchical' => 1, 
				    'name' => 'term_id',
				    'class' => 'ptp_importer_term',
				    'depth' => 1,
				    'taxonomy' => 'product_cat',
				    'hide_empty' => 0, 
				);
				?>
			
				<form class="variations-form">
					<?php wp_nonce_field( 'ptp_variations_group_add', 'ptp_nonce' ); ?>
					
					<input type="hidden" name="new" value="yes" />
					
					<div class="form-field">
						<label for="group-name"><?php _e( 'Group Name', 'ptp' ) ?></label>
						<span><input type="text" id="group-name" name="group_name" value="" placeholder="Variation Group Name" /></span>
					</div>
					
					<div class="form-field">
						<label for="variations" ><?php _e( 'Variations', 'ptp' ) ?></label> 
						<div class="variations"></div>
					</div>

					<div class="form-field">
						<label for="group-description"><?php _e( 'Description', 'ptp' ) ?></label>
						<textarea name="description" id="group-description" rows="5" cols="40" placeholder="Describe this Variation Group here."></textarea>
					</div>

					<div class="submit clear">
				    	<input type="hidden" name="action" value="<?php echo $add_action; ?>" />
						<input type="hidden" name="variation_count" class="variation-count" value="0" />

				        <input type="submit" id="add-variation-group" class="ptp-button-primary" value="<?php echo esc_attr( $add_submit ); ?>">
					</div>

				</form>
			</div>
		</div>
	</div>
</div>