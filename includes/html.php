<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns markup for uploaded item
 * 
 * @return string.
 */
function ptp_uploaded_item_html( $file ) {
    ob_start(); ?>

        <a href="#" class="item-delete" data-id="<?php echo $file['id']; ?>" class="ptp-importer-delete-file button"> x </a>
        
        <fieldset>
            
            <input type="hidden" name="attachments[]" value="<?php echo $file['id']; ?>" /> 
            <input type="text" class="item-title" name="titles[<?php echo $file['id']; ?>]" value="" placeholder="Product Title" />
			
			
			
        </fieldset>

        <a href="<?php echo $file['url']; ?>" class="item-link" target="_blank"><img src="<?php echo $file['thumb']; ?>" alt="<?php echo esc_attr( $file['name'] ); ?>" /></a>
			<br><br>
			<?php if ( ptp_is_active() && class_exists( 'BPTPI_Premium' ) ) : ?>
			<?php do_action( 'ptp_add_user_selection', $file['id'] ); ?>
			<?php endif; ?>
		
        <?php

    return ob_get_clean(); 
}

/**
 * Returns markup for variations list item
 *
 * @param object $group
 * @return string
 */
function ptp_variations_list_item( $group ) {
    ob_start(); ?>

    <tr style="display:none;" id="variation-group-row-<?php echo $group->term_id; ?>">
        <th scope="row" class="check-column">
            <label class="screen-reader-text" for="cb-select-<?php echo $group->term_id; ?>" > <?php _e( 'Select ' . $group->name, 'ptp' ) ?> </label>
            <input type="checkbox" name="delete_variations_groups[]" value="<?php echo $group->term_id; ?>"  for="cb-select-<?php echo $group->term_id; ?>" />
        </th>
        <td class="name column-name">
            <a href="#"><strong>
                <?php for( $i = 0; $i < count(get_term_parents( $group->term_id )); $i++ ) echo '&mdash;'; ?>
                <?php echo $group->name; ?>
            </strong></a>

            <div class="row-actions">
                <span class="inline hide-if-no-js"> <a href="#" class="quick-edit-variations-group" data-id="<?php echo $group->term_id; ?>"> <?php _e( 'Edit Variation Group', 'ptp' ) ?> </a> &#124; </span>
                <span class="delete"> <a href="#" class="delete-variations-group" data-id="<?php echo $group->term_id; ?>"> <?php _e( 'Delete', 'ptp' ) ?> </a> </span>
            </div>
        </td>
        <td class="description column-description"> <span><?php echo $group->description; ?> </span></td>
        <td class="variations column-variations"> <span><?php echo sizeof( $group->variations ) ?> </span></td>
    </tr>

    <?php
    return ob_get_clean();
}

/**
 * Returns markup for variations quick edit
 *
 * @param object $group
 * @return string
 */
function ptp_variations_edit_form( $group ) {
    global $ptp_importer;

    $update_submit = __( 'Update', 'ptp' );
    $update_cancel = __( 'Cancel', 'ptp' );
    $update_action = 'ptp_variations_group_update';
    $count = 0;

    ob_start(); ?>

    <tr>
        <td colspan="4" class="quick-edit-wrap">
            <div class="quick-edit-con form-wrap" style="display:none;">
                <form class="variations-form-update" >
                    <?php wp_nonce_field( 'ptp_variations_group_update', 'ptp_nonce' ); ?>
                                
                    <input type="hidden" name="term_id" value="<?php echo $group->term_id; ?>" />

                    <div class="form-field">
                        <label for="group-name"><?php _e( 'Group Name', 'ptp' ) ?></label>
                        <input type="text" name="group_name" id="group-name" placeholder="Group Name" value="<?php echo $group->name; ?>" />
                    </div>

                    <div class="form-field">
                        <label for="variation-group-parent-quick-edit"><?php _e( 'Parent Group', 'ptp' ); ?></label>
                        <?php wp_dropdown_categories( array (
                            'show_option_all'  => 'None',
                            'show_option_none' => '',
                            'orderby'          => 'name',
                            'name'             => 'parent-group',
                            'id'               => 'variation-group-parent-quick-edit',
                            'hierarchical'     => true,
                            'exclude'          => $group->term_id,
                            'selected'         => get_term_parent( $group->term_id ),
                            'taxonomy'         => $ptp_importer->taxonomy
                        ) ); ?>
                    </div>

                    <div class="form-field">
                        <label for="variations" ><?php _e( 'Variations', 'ptp' ) ?></label> 
                        
                        <div class="variations">
                        <?php foreach( $group->variations as $item ) : ?>
                            <table class="row">
                                <tr>
                                    <td>
                                        <input class="variation" type="hidden" name="variations[<?php echo $count; ?>][id]" value="<?php echo $item['id']; ?>" />
                                        <input class="variation" type="text" name="variations[<?php echo $count; ?>][name]" value="<?php echo $item['name']; ?>" placeholder="Variation Name" />
                                    </td>
                                    <td>
                                        <input class="price" type="text" name="variations[<?php echo $count; ?>][price]" value="<?php echo $item['price']; ?>" placeholder="Price" />
                                    </td>
                                </tr>
                            </table> 

                            <?php $count++; ?>   
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="group-description"><?php _e( 'Description', 'ptp' ) ?></label>
                        <textarea name="description" id="group-description" rows="5" cols="40" placeholder="Describe this Event Type here."><?php echo $group->description; ?></textarea>
                    </div>

                    <div class="submit">
                        <input type="hidden" name="action" value="<?php echo $update_action; ?>" />
                        <input type="hidden" name="variation_count" class="variation-count" value="<?php echo $count; ?>" />

                        <input type="submit" class="ptp-button-primary" id="update-variation-group" value="<?php echo esc_attr( $update_submit ); ?>">
                        <input type="button" class="cancel-quick-edit-variations-group ptp-button-secondary" value="<?php echo esc_attr( $update_cancel ); ?>">
                    </div>

                </form>
            </div>
        </td>
    </tr>
    
    <?php
    return ob_get_clean();
}

/**
 * Returns woocommerce categories dropdown.
 *
 * @param array $args
 * @return string
 */
function ptp_dropdown_categories( $args = '' ) {
    global $ptp_importer;

    $defaults = array(
        'name' => 'term_id',
        'show_option_none' => 'Select Category',
        'class' => 'term-id',
        'hide_empty'    => 0, 
        'echo' => 0,
        'hierarchical' => 1, 
        'depth' => 999,
        'taxonomy' => $ptp_importer->woocommerce_cat_tax,
        'exlude' => '',
        'walker' => '',
    );

    $args = wp_parse_args( $args, $defaults );    

    return wp_dropdown_categories( $args );
}

/**
 * Markup for Quick Add Category form
 *
 * @return string
 */
function ptp_quick_add_category_form() {
    global $ptp_importer;
    ob_start() ?>
    
    <input type="hidden" name="quick_add_category_action" value="ptp_category_quick_add" />
    <input type="text" name="category_name" value="" placeholder="Category Name" />
    <?php echo ptp_dropdown_categories( array( 'name' => 'parent_term_id', 'class' => 'parent-term-id', 'show_option_none' => 'Select Parent Category' ) ); ?>
    
    <div class="submit">
        <input type="button" id="quick-add-category" class="ptp-button-secondary" value="Add Category" />
    </div>

    <?php
    return ob_get_clean();
}

/**
 * Markup for bulk add to cart dialog
 *
 * @param array 
 * @return string
 */
function ptp_quick_order_dialog() {  
    global $ptp_importer;

    // Donnot load if there are no BPTPI products
    if ( !$ptp_importer->number_of_products )
        return;

    $templates = ptp_templates(); 

    if ( locate_template( $templates['quick-order-dialog'], false ) ) {
        locate_template( $templates['quick-order-dialog'], true );
    } else {
        include_once $ptp_importer->plugin_path . '/front-end/templates/' . $templates['quick-order-dialog'];
    }
}

/**
 * Markup delete dialog
 *
 * @param string $title
 * @param string $body
 * @return string
 */
function ptp_delete_dialog( $title, $body ) {  
    ob_start(); ?>

    <div id="ptp-dialog-confirm" title="<?php echo $title; ?>">
      <p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span><?php echo $body ?></p>
    </div> <?php

    return ob_get_clean();
}

/**
 * Add checkbox for each product
 *
 * @return string
 */
function ptp_add_checkbox() { 
    global $post, $ptp_importer;

    // Donnot add checkbox if not our product
    if ( !ptp_our_product( $post->ID ) || is_single() )
        return;
    $variation_group_id = get_post_meta( $post->ID, '_ptp_variation_group_id', true );
    if( is_array( $variation_group_id ) ) {
        $variation_group_id = $variation_group_id[ 0 ];
    }
    
    $group  = ptp_get_product_variations( );
    $groups = array( );
    foreach( $group as $g ) {
        $groups[] = array(
            'name' => $g[ 'name' ],
            'id'   => $g[ 'id' ]
        );
    }
    ?>
    <input type="checkbox" class="select-product" data-id="<?php echo $post->ID; ?>" data-groupid="<?php echo $variation_group_id; ?>" data-title="<?php the_title(); ?>" data-variations='<?php echo json_encode( $groups ); ?>' />
    <?php
}

/**
 * Add quick order trigger
 *
 * @return string
 */
function ptp_quick_order_trigger() {
    global $post, $ptp_importer;

    // Donnot load if there are no BPTPI products
    if ( !$ptp_importer->number_of_products )
        return;

    ?>
    <input type="button" class="quick-order-launch" value="<?php echo apply_filters( 'ptp_quick_order_text', 'Quick Order' ); ?>" />
    <?php
}