<?php

/**
 * Description of ajax
 */
class PTPImporter_Ajax {

    public function __construct() {

        add_action( 'wp_ajax_ptp_product_upload', array($this, 'product_upload') );
		add_action( 'wp_ajax_ptp_product_select', array($this, 'product_select') );
        add_action( 'wp_ajax_ptp_product_import', array($this, 'product_import') );
        add_action( 'wp_ajax_ptp_product_delete', array($this, 'product_delete') );

        add_action( 'wp_ajax_ptp_products_add_to_cart', array($this, 'products_add_to_cart') );
        add_action( 'wp_ajax_nopriv_ptp_products_add_to_cart', array($this, 'products_add_to_cart') );

        add_action( 'wp_ajax_ptp_dropdown_bulk_import_categories', array($this, 'dropdown_bulk_import_categories') );
        add_action( 'wp_ajax_ptp_dropdown_variation_groups', array($this, 'dropdown_variation_groups') );

        add_action( 'wp_ajax_ptp_variations_group_edit_form', array($this, 'variations_group_edit_form') );
        add_action( 'wp_ajax_ptp_variations_group_add', array($this, 'variations_group_add') );
        add_action( 'wp_ajax_ptp_variations_group_update', array($this, 'variations_group_update') );
        add_action( 'wp_ajax_ptp_variations_group_delete', array($this, 'variations_group_delete') );
        add_action( 'wp_ajax_ptp_variations_group_delete_dialog', array($this, 'variations_group_delete_dialog') );
        add_action( 'wp_ajax_ptp_variations_group_migrate', array($this, 'variations_group_migrate') );

        add_action( 'wp_ajax_ptp_settings_save', array($this, 'settings_save') );

        add_action( 'wp_ajax_ptp_category_quick_add', array($this, 'category_quick_add') );
        add_action( 'wp_ajax_ptp_category_quick_add_form', array($this, 'category_quick_add_form') );
		add_action( 'wp_ajax_ptp_category_list', array($this, 'category_list') );

        // Mini cart
        add_action( 'wp_ajax_nopriv_ptp_get_refreshed_fragments', array( $this, 'get_refreshed_fragments' ) );
        add_action( 'wp_ajax_ptp_get_refreshed_fragments', array( $this, 'get_refreshed_fragments' ) );

        add_action( 'wp_ajax_ptp_activate', array( $this, 'activate' ) );
        add_action( 'wp_ajax_ptp_deactivate', array( $this, 'deactivate' ) );
    }

    public function dropdown_bulk_import_categories() {
        check_ajax_referer( 'ptp_nonce' );

        echo json_encode( array(
            'success' => true,
            'html' => ptp_dropdown_categories( array( 'name' => $_POST['name'], 'show_option_none' => 'Select Event', 'walker' => new Walker_Without_Children() ) )
        ) );

        exit;
    }

    public function dropdown_variation_groups() {
        check_ajax_referer( 'ptp_nonce' );

        global $wpdb, $ptp_importer;
        // $variation_group_id = ptp_get_term_meta( $_POST['term_id'], $ptp_importer->term_variation_group_meta_key, true );
        $variation_group_id = 0;

        // Nothing to exclude & pre-select
        if ( !$variation_group_id || $variation_group_id == '-1' ) {
            echo json_encode( array(
                'success' => true,
                'html' => ptp_dropdown_categories(
                    array( 
                        'name' => 'variation_group', 
                        'class' => 'variation-group', 
                        'taxonomy' => $ptp_importer->taxonomy, 
                        'show_option_none' => 
                        'Select Variation Group',
                        'walker' => new Walker_With_Variations() 
                    ) 
                )
            ) );

            exit;
        }

        $sql = "SELECT `term_id`";
        $sql .= " FROM {$wpdb->term_taxonomy}";
        $sql .= " WHERE {$wpdb->term_taxonomy}.term_id != '%s'"; 
        $sql .= " AND {$wpdb->term_taxonomy}.taxonomy = '%s'";
        $sql .= " GROUP BY {$wpdb->term_taxonomy}.term_id";

        $groups = $wpdb->get_results( $wpdb->prepare( $sql, $variation_group_id, $ptp_importer->taxonomy ) );
        $exclude = array();

        foreach ( $groups as $group ) {
            $exclude[] = (int)$group->term_id;
        }

        echo json_encode( array(
            'success' => true,
            'html' => ptp_dropdown_categories(
                array( 
                    'name' => 'variation_group', 
                    'class' => 'variation-group', 
                    'taxonomy' => $ptp_importer->taxonomy, 
                    'show_option_none' => 
                    'Select Variation Group',
                    'exclude' => $exclude,
                    'selected' =>  $variation_group_id,
                    'walker' => new Walker_With_Variations() 
                ) 
            )
        ) );

        exit;
    }

    public function product_upload() {
        check_ajax_referer( 'ptp_product_upload', 'ptp_nonce' );

        $object_id = isset( $_REQUEST['object_id'] ) ? intval( $_REQUEST['object_id'] ) : 0;
        $photos_obj = PTPImporter_Product::getInstance();
        $response = $photos_obj->upload_file( $object_id );

        if ( !$response['success'] ) {
            echo json_encode( array(
                'success' => false,
                'error' => $response['error']
            ) );

            exit;
        }

        $file = $photos_obj->get_file( $response['file_id'] );
        $html = ptp_uploaded_item_html( $file );

        echo json_encode( array(
            'success' => true,
            'content' => $html
        ) );

        exit;
    }
	public function product_select() {
        check_ajax_referer( 'ptp_product_select', 'ptp_nonce' );
		global $ptp_importer;
		$watermarker = get_post_meta($_POST['id'],$ptp_importer->attachment_meta_key);
		if(empty($watermarker))
		{
			$file_loc = get_attached_file($_POST['id']);
			update_post_meta( $_POST['id'], $ptp_importer->attachment_meta_key, 'yes' );
		}
		$photos_obj = PTPImporter_Product::getInstance();
        $file = $photos_obj->get_file( $_POST['id'] );
        $html = ptp_uploaded_item_html( $file );

        echo json_encode( array(
            'success' => true,
            'content' => $html
        ) );

        exit;
    }

    public function product_delete() {
        check_ajax_referer( 'ptp_nonce' );

        $file_id = (isset( $_POST['file_id'] )) ? intval( $_POST['file_id'] ) : 0;

        $photos_obj = PTPImporter_Product::getInstance();
        /*
		$res = $photos_obj->delete_file( $file_id, true );
		
        if ( !$res ) {
            echo json_encode( array(
                'success' => false,
                'error' => $res
            ) );

            exit;
        }
		*/
        echo json_encode( array(
            'success' => true
        ) );

        exit;
    }

    public function product_import() {
        check_ajax_referer( 'ptp_product_import', 'ptp_nonce' );

        $photos_obj = PTPImporter_Product::getInstance();

        $post_ids = $photos_obj->create( $_POST );

        if ( !$post_ids ) {
            echo json_encode( array(
                'success' => false,
                'error' => $post_ids
            ) );

            exit;
        }

        global $ptp_importer;
        $groups = (array) ptp_get_term_meta( $_POST['term_id'], $ptp_importer->term_variation_groups_meta_key, true );
        if( !in_array( $_POST['variation_group'], $groups ) ) {
            $groups[] = $_POST['variation_group'];
        }
        ptp_update_term_meta( $_POST['term_id'], $ptp_importer->term_variation_groups_meta_key, $groups );

        // Associate variation group with term
        ptp_update_term_meta( $_POST['term_id'], $ptp_importer->term_variation_group_meta_key, $_POST['variation_group'] );

        do_action( 'ptp_product_import_complete', $_POST );

        echo json_encode( array(
            'success' => true
        ) );

        exit;
    }

    public function products_add_to_cart() {
        check_ajax_referer( 'ptp_products_add_to_cart', 'ptp_nonce' );
        
 $product_id = array();

        global $woocommerce;
        $found = false;
        $posted = $_POST;

//print_r($posted); die("die here");
        if ( !isset( $posted['ptp_grouped_products'] ) ) {

            echo json_encode( array(
                'success' => false,
                'error' => 'Queue is empty.'
            ) );

            exit;
        }
//print_r($posted['ptp_grouped_products']); 
$variation_name = $posted['variation'];
        foreach ( $posted['ptp_grouped_products'] as $grouped_product_id ) {

     $version_value = get_option('_transient_product-transient-version');

   $variations = get_option( '_transient_wc_product_children_ids_' . $grouped_product_id.$version_value  ); 
            if ( sizeof( $variations ) == 0 ) { continue; }

foreach($variations as $variationval) {
$varvalue = get_post_meta($variationval, '_downloadable', true);

if($varvalue == 'yes' && ($variation_name == 'Downloadable' OR $variation_name == 'downloadable') ){
$product_id = $variationval;

} else if($varvalue == 'no' && ($variation_name != 'Downloadable') ) {
$product_id = $variationval;
}

}

       //     foreach ( $variations as $variation ) {
          //       $product_id[] = $variation;
                 //   break;
//}


            // If no items in cart, add product directly
            if ( sizeof( $woocommerce->cart->get_cart() ) == 0 ) { 
	foreach($product_id as $product_id) {
             $woocommerce->cart->add_to_cart( $product_id ); 
}           
            //  continue;
            }

            // If there are items in cart, check if the product is added already
            foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
         
                if ( $_product->id == $product_id ) {

                    $found = true;
                }
            }

                
            // if product not found, add it
            if ( !$found ) {
                $woocommerce->cart->add_to_cart( $product_id );
            }
        }

        echo json_encode( array(
            'success' => true
        ) );

        exit;
    }

    public function settings_save() {
        check_ajax_referer( 'ptp_settings_save', 'ptp_nonce' );
		
		$posted = $_POST;
        $settings_obj = PTPImporter_Settings::getInstance();
        $result = $settings_obj->update( $posted );

		if ( !$result ) {
            echo json_encode( array(
                'success' => false,
                'error' => $result
            ) );

            exit;
        }

        echo json_encode( array(
            'success' => true
        ) );

        exit;
    }

    public function variations_group_edit_form() {
        check_ajax_referer( 'ptp_nonce' );

        $posted = $_POST;

        $variations_obj = PTPImporter_Variation_Group::getInstance();
        $group = $variations_obj->group( $posted['term_id'] );

        if ( !$group ) {
            echo json_encode( array(
                'success' => false,
                'error' => 'No variations found.'
            ) );

            exit;
        }

        echo json_encode( array(
            'success' => true,
            'html' => ptp_variations_edit_form( $group )
        ) );

        exit;
    }

    public function variations_group_add() {
        check_ajax_referer( 'ptp_variations_group_add', 'ptp_nonce' );

        $posted = $_POST;

        if ( !$posted['variations'] ) {
            echo json_encode( array(
                'success' => false,
                'error' => 'You must add at least one variation.'
            ) );

            exit;
        }

        $variations_obj = PTPImporter_Variation_Group::getInstance();

        $term_id = $variations_obj->add( $posted['group_name'], $posted['description'], $posted['variations'], $posted['parent-group'] );

        if ( $term_id ) {
            echo json_encode( array(
                'success' => true,
                'parent'  => intval($posted['parent-group']),
                'html'    => ptp_variations_list_item( $variations_obj->group( $term_id ) )
            ) );
        }

        exit;
    }

    public function variations_group_update() {
        check_ajax_referer( 'ptp_variations_group_update', 'ptp_nonce' );

        $posted = $_POST;

        if ( !$posted['variations'] ) {
            echo json_encode( array(
                'success' => false,
                'error' => 'You must add at least one variation.'
            ) );

            exit;
        }

        $variations_obj = PTPImporter_Variation_Group::getInstance();
        $old_parent     = get_term_parent( $posted['term_id'] );
        
        $result         = $variations_obj->update( $posted['term_id'], $posted['group_name'], $posted['description'], $posted['variations'], $posted['parent-group'] );

        if ( !$result ) {
            echo json_encode( array(
                'success' => false,
                'term_id'  => intval($posted['term_id']),
                'parent'  => intval($posted['parent-group']),
                'error'   => $result
            ) );

            exit;
        }
        
        $parents = tpc_get_term_parents( $posted['term_id'] );
        $dashes  = '';

        for( $i = 0; $i < count( $parents ); $i ++ ) $dashes .= '&mdash;';

        echo json_encode( array(
            'success'  => true,
            'term_id'  => intval($posted['term_id']),
            'name'     => $dashes . ' ' . $posted['group_name'],
            'is_moved' => $old_parent != $posted['parent-group'],
            'parent'   => intval($posted['parent-group']),
        ) );

        exit;
    }

    public function variations_group_delete() {
        check_ajax_referer( 'ptp_nonce' ); // generic nonce

        $post = $_POST;

        $variations_obj = PTPImporter_Variation_Group::getInstance();
        $result = $variations_obj->delete( $post['term_ids'] );

        if ( !$result ) {
            echo json_encode( array(
                'success' => false,
                'error' => $result
            ) );

            exit;
        }

        echo json_encode( array(
            'success' => true
        ) );

        exit;
    }

    public function variations_group_delete_dialog() {
        check_ajax_referer( 'ptp_nonce' ); // generic nonce

        echo json_encode( array(
            'success' => true,
            'html' => ptp_delete_dialog( 'Delete Variation Group(s)?', 'You are about to delete a Variation Group(s). After doing so, all products associated with this group(s) will be deleted too.' )
        ) );

        exit;
    }

    public function variations_group_migrate() {
        check_ajax_referer( 'ptp_nonce' ); // generic nonce

        $variations_migrate_obj = PTPImporter_Variation_Migrate::getInstance();
        $result = $variations_migrate_obj->migrate();
//print_r($result); die("Die for variation group migrate");
        if ( !$result ) {
            echo json_encode( array(
                'success' => false
            ) );

            exit;
        }

        echo json_encode( array(
            'success' => true
        ) );

        exit;
    }

    public function category_quick_add_form() {
        check_ajax_referer( 'ptp_nonce' );

        echo json_encode( array(
            'success' => true,
            'html' => ptp_quick_add_category_form()
        ) );

        exit;
    }
	
	public function category_list() {
        check_ajax_referer( 'ptp_nonce' );
		
		global $ptp_importer;
		$settings_obj = PTPImporter_Settings::getInstance();
		$settings = $settings_obj->get();
		$bptpi_category_naming_scheme_value = $settings['bptpi_category_naming_scheme'];
//die("Die Here for naming scheme");
		if ( isset($bptpi_category_naming_scheme_value) ) {
			$bptpi_category_naming_scheme = $settings['bptpi_category_naming_scheme'];
		} else {
			$bptpi_category_naming_scheme = 'Category';
		}

        echo json_encode( array(
            'success' => true,
            'html' => ptp_dropdown_categories( array( 'name' => 'term_id', 'show_option_none' => 'Select a ' . $bptpi_category_naming_scheme, 'walker' => new Walker_Without_Children() ) )
        ) );

        exit;
    }

    public function category_quick_add() {
        check_ajax_referer( 'ptp_nonce' ); // generic nonce

        global $ptp_importer;
//print_r($_POST['parent']); die("Die in category");
		if(isset($_POST['parent']))	
			$termx = get_term_by( 'id', $_POST['parent'], "product_cat" );
	
		if($termx->parent != 0 && isset($_POST['parent']) && $_POST['parent'] > 0)
		{
			echo json_encode( array(
                'success' => false,
                'error' => 'Unable to create from children nodes from children category.'
            ) );
			exit;
		}
		else
		{
			$term = wp_insert_term(
				$_POST['name'], 
				$ptp_importer->woocommerce_cat_tax, 
				array(
					'parent' => (int)$_POST['parent']
				)
			);
		}

        if ( is_wp_error( $term ) ) {
            echo json_encode( array(
                'success' => false,
                'error' => 'Unable to create category.'
            ) );

            exit;
        }

        echo json_encode( array(
            'success' => true,
            'error' => $_POST
        ) );

        exit;
    }

    public function get_refreshed_fragments() {
        global $woocommerce;

        // Get mini cart
        ob_start();
      // print_r( woocommerce_mini_cart() ); die("Atleast die any where");
        $mini_cart = ob_get_clean();
       // print_r($mini_cart); die("Die before Cart");

        if ( !$mini_cart ) {
            $mini_cart = '<p class="empty-cart">You have not added any products to your cart yet.</p>';
        }

        // Fragments and mini cart are returned
      //  $data = array(
       //     'fragments' => apply_filters( 'add_to_cart_fragments', array(
       //             'div.ptp-widget-cart-content' => '<div class="ptp-widget-cart-content">' . $mini_cart . '</div>'
       //         )
      //      ),
      //      'cart_hash' => md5( json_encode( $woocommerce->cart->get_cart() ) )
      //  );
		$data = array(
            'fragments' => apply_filters( 'add_to_cart_fragments', array(
                    'div.ptp-widget-cart-content' => '<div class="ptp-widget-cart-content"></div>'
                )
            ),
            'cart_hash' => md5( json_encode( $woocommerce->cart->get_cart() ) )
        );
//print_r($data); die("Die Before Cart-2"); 
        echo json_encode( $data );

       exit;
    }

        /**
     * Activate this plugin
     *
     * @return json object
     */
    public function activate() {
        check_ajax_referer( 'ptp_nonce' );

        global $ptp_importer;

        // Check if serial key is valid
        $res = ptp_is_valid_serial_key( $_POST['serial_key'] );
        if ( !$res['success'] ) {
            echo json_encode(array(
                'success' => false,
                'message' => $res['message']
            ));

            exit;
        }

        // Check if item names match
        if ( $res['item_name'] != $ptp_importer->plugin_name ) {
            echo json_encode(array(
                'success' => false,
                'message' => 'The serial key your using is not for '.$ptp_importer->plugin_name
            ));

            exit;
        }

        // Register this IP
        $res = ptp_register_server_info( $_POST['serial_key'] );
        if ( !$res['success'] ) {
            echo json_encode(array(
                'success' => false,
                'message' => $res['message']
            ));

            exit;
        }

        update_option( '_ptp_status', 'active' );
        update_option( '_ptp_sk', $_POST['serial_key'] );

        echo json_encode(array(
            'success' => true,
        ));

        exit;
    }

    /**
     * Dectivate this plugin
     *
     * @return json object
     */
    public function deactivate() {
        check_ajax_referer( 'ptp_nonce' );

        $res = ptp_deactivate( $_POST['serial_key'] );
        if ( !$res['success'] ) {
            echo json_encode(array(
                'success' => false,
                'message' => $res['message']
            ));

            exit;
        }

        delete_option( '_ptp_status' );

        echo json_encode(array(
            'success' => true,
        ));

        exit;
    }

}