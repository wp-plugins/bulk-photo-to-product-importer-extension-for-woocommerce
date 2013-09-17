<?php

/**
 * Description of photo
 */
class PTPImporter_Product {

    private static $_instance;

    public function __construct() {
        global $wpdb;

        $this->_db = $wpdb;
    }

    public static function getInstance() {
        if ( !self::$_instance ) {
            self::$_instance = new PTPImporter_Product();
        }

        return self::$_instance;
    }

    /**
     * Insert a new post
     *
     * @param array $posted
     * @return array $post_ids
     */
   public function create( $posted ) {
        global $ptp_importer;

        $post_ids = array();

        foreach ( $posted['attachments'] as $file_id ) {
            $file_data = $this->get_file( $file_id );

            $post = array(
              'post_title' => $posted['titles'][$file_id] ? $posted['titles'][$file_id] : $file_data['name'],
              'post_content' => '',
              'post_type' => 'product',
              'post_status' => 'publish',
              'post_author' => get_current_user_id(),
              'tax_input' => array( 
                                    'product_type' => array( ptp_product_type_term_id() ), 
                                    'product_cat' => array( $posted['term_id'] )
                                  )
            );

            // Create product
            $post_id = wp_insert_post( $post );
            $post_ids[] = $post_id;

            // Form product metadata
            $metadata = ptp_product_metadata_defaults();

            // Attach event date to this product
            $metadata[ $ptp_importer->event_date_meta_key ] = date( 'Y-m-d H:i:s', strtotime( $posted['date'] ) );

            // Add meta that determines if this product is imported by this plugin
            $metadata['_ptp_product'] = 'yes';
            // Record attachment id for later use
            $metadata['_ptp_attchement_id'] = $file_id;

            // Update metadata
            foreach ( $metadata as $key => $value ) {
              update_post_meta( $post_id, $key, $value );
            }

            // Set file as the post thumbnail for the product
            set_post_thumbnail( $post_id, $file_id );

            // Create variations(child products) for this grouped product
            $this->create_variations( $posted['variation_group'], $post_id, $file_data );

            do_action( 'ptp_create_products_complete', $post_id, $posted['term_id'], $posted['users'] );
        }

        return $post_ids;
    }

    /**
     * Create variations
     *
     * @param int $term_id
     * @param int $parent_id
     * @param array $file_data
     * @return array $post_ids
     */
    public function create_variations( $term_id, $parent_id, $file_data ) {
        $post_ids = array();

        $variation_obj = PTPImporter_Variation_Group::getInstance();
        $group = $variation_obj->group( $term_id );

        foreach ( $group->variations as $variation ) {
            $post = array(
              'post_title' => $variation['name'],
              'post_content' => '',
              'post_type' => 'product',
              'post_status' => 'publish',
              'post_author' => get_current_user_id(),
              'post_parent' => $parent_id,
            );

            // Create product
            $post_id = wp_insert_post( $post );
            $post_ids[] = $post_id;

            // Form product metadata
            $metadata = ptp_product_metadata_defaults();
            // Set price
            $metadata['_price'] = $variation['price'];
            // Set regular price
            $metadata['_regular_price'] = $variation['price'];
            // Set visibility to blank so it won't be displayed
            $metadata['_visibility'] = '';
            // Add meta that determines if this product is used as variation for a grouped data
            $metadata['_ptp_as_variation'] = 'yes';
            // Add SKU
            $metadata['_sku'] = uniqid();

            if ( $variation['name'] == 'Downloadable' || $variation['name'] == 'downloadable' ) {
                $uploads_dir = wp_upload_dir();
                $file_path = $uploads_dir['baseurl'] . '/woocommerce_uploads'  . $uploads_dir['subdir'] . '/downloadable_' . basename( $file_data['url'] );

                // Set as downloadable
                $metadata['_downloadable'] = 'yes';
                // Set download path
                $metadata['_file_paths'] = array( $file_path );
                // Set download limit
                $metadata['_download_limit'] = '';
                // Set download expiry
                $metadata['_download_expiry'] = '';
            }

            // Update metadata
            foreach ( $metadata as $key => $value ) {
              update_post_meta( $post_id, $key, $value );
            }
        }

        return $post_ids;
    }

    /**
     * Add variations. This is used when there are new variations added in a Variation Group.
     *
     * @param int $variation_group_id
     * @param array $variations
     * @return void
     */
    public function add_variations( $variation_group_id, $variations ) {
        global $ptp_importer;

        $sql = "SELECT {$this->_db->posts}.ID";
        $sql .= " FROM {$this->_db->posts}";
        $sql .= " JOIN {$this->_db->term_relationships} ON {$this->_db->posts}.ID = {$this->_db->term_relationships}.object_id"; 
        $sql .= " JOIN {$this->_db->taxonomymeta} ON {$this->_db->term_relationships}.term_taxonomy_id = {$this->_db->taxonomymeta}.taxonomy_id"; 
        $sql .= " JOIN {$this->_db->postmeta} ON {$this->_db->posts}.ID = {$this->_db->postmeta}.post_id"; 
        $sql .= " WHERE {$this->_db->postmeta}.meta_key = '%s'"; 
        $sql .= " AND {$this->_db->taxonomymeta}.meta_key = '%s'"; 
        $sql .= " AND {$this->_db->taxonomymeta}.meta_value = '%s'"; 
        $sql .= " AND {$this->_db->posts}.post_status = '%s'"; 
        $sql .= " GROUP BY {$this->_db->posts}.ID";

        $parents = $this->_db->get_results( $this->_db->prepare( $sql, $ptp_importer->our_product_meta_key, $ptp_importer->term_variation_group_meta_key, $variation_group_id, 'publish' ) );

        for ( $i = 0; $i < count($parents); $i++ ) {
            foreach ( $variations as $variation ) {
                $post = array(
                  'post_title' => $variation['name'],
                  'post_content' => '',
                  'post_type' => 'product',
                  'post_status' => 'publish',
                  'post_author' => get_current_user_id(),
                  'post_parent' => $parents[$i]->ID,
                );

                // Create product
                $post_id = wp_insert_post( $post );

                // Form product metadata
                $metadata = ptp_product_metadata_defaults();

                // Set price
                $metadata['_price'] = $variation['price'];

                // Set regular price
                $metadata['_regular_price'] = $variation['price'];

                // Set visibility to blank so it won't be displayed
                $metadata['_visibility'] = '';

                // Add meta that determines if this product is used as variation for a grouped data
                $metadata['_ptp_as_variation'] = 'yes';

                // Add SKU
                $metadata['_sku'] = uniqid();

                if ( $variation['name'] == 'Downloadable' || $variation['name'] == 'downloadable' ) {
                    $uploads_dir = wp_upload_dir();
                    $file_path = $uploads_dir['baseurl'] . '/woocommerce_uploads'  . $uploads_dir['subdir'] . '/downloadable_' . basename( wp_get_attachment_url( get_post_meta( $parents[$i]->ID, '_ptp_attchement_id', true ) ) );

                    // Set as downloadable
                    $metadata['_downloadable'] = 'yes';
                    // Set download path
                    $metadata['_file_paths'] = array( $file_path );
                    // Set download limit
                    $metadata['_download_limit'] = '';
                    // Set download expiry
                    $metadata['_download_expiry'] = '';

                    // Add this to cache. This fixes the issue of this downloadable product not being included in the front-end
                    $transient_name = 'wc_product_children_ids_' . $parents[$i]->ID;
                    $children = (array)get_transient( $transient_name );
                    $children[] = $post_id;
                    set_transient( $transient_name, $children );
                }

                // Update metadata
                foreach ( $metadata as $key => $value ) {
                  update_post_meta( $post_id, $key, $value );
                }
            }
        }
    }

    /**
     * Update variations. This is used when there are updated variations in a Variation Group.
     *
     * @param int $variation_group_id
     * @param array $replaced_variations
     * @param array $updated_variations
     * @return void
     */
    public function update_variations( $variation_group_id, $replaced_variations, $updated_variations ) {
        global $ptp_importer;

        $sql = "SELECT {$this->_db->posts}.ID";
        $sql .= " FROM {$this->_db->posts}";
        $sql .= " JOIN {$this->_db->term_relationships} ON {$this->_db->posts}.ID = {$this->_db->term_relationships}.object_id"; 
        $sql .= " JOIN {$this->_db->taxonomymeta} ON {$this->_db->term_relationships}.term_taxonomy_id = {$this->_db->taxonomymeta}.taxonomy_id"; 
        $sql .= " JOIN {$this->_db->postmeta} ON {$this->_db->posts}.ID = {$this->_db->postmeta}.post_id"; 
        $sql .= " WHERE {$this->_db->postmeta}.meta_key = '%s'"; 
        $sql .= " AND {$this->_db->taxonomymeta}.meta_key = '%s'"; 
        $sql .= " AND {$this->_db->taxonomymeta}.meta_value = '%s'"; 
        $sql .= " AND {$this->_db->posts}.post_status = '%s'"; 
        $sql .= " GROUP BY {$this->_db->posts}.ID";

        $parents = $this->_db->get_results( $this->_db->prepare( $sql, $ptp_importer->our_product_meta_key, $ptp_importer->term_variation_group_meta_key, $variation_group_id, 'publish' ) );

        for ( $i = 0; $i < count($parents); $i++ ) {
            for ( $v = 0; $v < count($replaced_variations); $v++ ) {

                $sql = "SELECT `ID`";
                $sql .= " FROM {$this->_db->posts}";
                $sql .= " WHERE {$this->_db->posts}.post_title = '%s'"; 
                $sql .= " AND {$this->_db->posts}.post_parent = '%s'"; 
                $sql .= " AND {$this->_db->posts}.post_status = '%s'";  

                $post = $this->_db->get_row( $this->_db->prepare( $sql, trim( $replaced_variations[$v]['name'] ), (int)$parents[$i]->ID, 'publish' ) );

                $args = array(
                    'ID'            => $post->ID,
                    'post_title'    => $updated_variations[$v]['name'],
                );

                wp_update_post( $args );

                // Form product metadata
                $metadata = ptp_product_metadata_defaults();

                // Set price
                $metadata['_price'] = $updated_variations[$v]['price'];

                // Set regular price
                $metadata['_regular_price'] = $updated_variations[$v]['price'];

                // Update metadata
                foreach ( $metadata as $key => $value ) {
                  update_post_meta( $post->ID, $key, $value );
                }
            }
        }
    }

    /**
     * Remove variations. This is used when there are variations removed from a Variation Group.
     *
     * @param int $variation_group_id
     * @param array $variations
     * @return void
     */
    public function remove_variations( $variation_group_id, $variations ) {
        global $ptp_importer;

        $sql = "SELECT {$this->_db->posts}.ID";
        $sql .= " FROM {$this->_db->posts}";
        $sql .= " JOIN {$this->_db->term_relationships} ON {$this->_db->posts}.ID = {$this->_db->term_relationships}.object_id"; 
        $sql .= " JOIN {$this->_db->taxonomymeta} ON {$this->_db->term_relationships}.term_taxonomy_id = {$this->_db->taxonomymeta}.taxonomy_id"; 
        $sql .= " JOIN {$this->_db->postmeta} ON {$this->_db->posts}.ID = {$this->_db->postmeta}.post_id"; 
        $sql .= " WHERE {$this->_db->postmeta}.meta_key = '%s'"; 
        $sql .= " AND {$this->_db->taxonomymeta}.meta_key = '%s'"; 
        $sql .= " AND {$this->_db->taxonomymeta}.meta_value = '%s'"; 
        $sql .= " AND {$this->_db->posts}.post_status = '%s'"; 
        $sql .= " GROUP BY {$this->_db->posts}.ID";

        $parents = $this->_db->get_results( $this->_db->prepare( $sql, $ptp_importer->our_product_meta_key, $ptp_importer->term_variation_group_meta_key, $variation_group_id, 'publish' ) );

        for ( $i = 0; $i < count($parents); $i++ ) {
            foreach ( $variations as $variation ) {

                $sql = "SELECT `ID`";
                $sql .= " FROM {$this->_db->posts}";
                $sql .= " WHERE {$this->_db->posts}.post_title = '%s'"; 
                $sql .= " AND {$this->_db->posts}.post_parent = '%s'"; 
                $sql .= " AND {$this->_db->posts}.post_status = '%s'";  

                $post = $this->_db->get_row( $this->_db->prepare( $sql, trim( $variation['name'] ), (int)$parents[$i]->ID, 'publish' ) );

                wp_delete_post( $post->ID, true );
            }
        }
    }

    /**
     * Upload a file and insert as attachment
     *
     * @param int $post_id
     * @return int|bool
     */
    public function upload_file( $post_id = 0 ) {
        if ( $_FILES['ptp_attachment']['error'] > 0 ) {
            return false;
        }

        $upload = array(
            'name' => $_FILES['ptp_attachment']['name'],
            'type' => $_FILES['ptp_attachment']['type'],
            'tmp_name' => $_FILES['ptp_attachment']['tmp_name'],
            'error' => $_FILES['ptp_attachment']['error'],
            'size' => $_FILES['ptp_attachment']['size']
        );

        $uploaded_file = wp_handle_upload( $upload, array('test_form' => false) );

        if ( isset( $uploaded_file['file'] ) ) {
            $file_loc = $uploaded_file['file'];
            $file_name = basename( $_FILES['ptp_attachment']['name'] );
            $file_type = wp_check_filetype( $file_name );

            $attachment = array(
                'post_mime_type' => $file_type['type'],
                'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $file_name ) ),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            global $ptp_importer;

            $attach_id = wp_insert_attachment( $attachment, $file_loc );
            update_post_meta( $attach_id, $ptp_importer->attachment_meta_key, 'yes' );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $file_loc );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            return array( 'success' => true, 'variations' => $variations, 'file_id' => $attach_id );
        }

        return array( 'success' => false, 'error' => $_FILES['ptp_attachment']['name'] );
    }

    /**
     * Get an attachment file
     *
     * @param int $attachment_id
     * @return array
     */
    public function get_file( $attachment_id ) {
        $file = get_post( $attachment_id );

        if ( $file ) {
            $response = array(
                'id' => $attachment_id,
                'name' => get_the_title( $attachment_id ),
                'url' => wp_get_attachment_url( $attachment_id ),
            );

            if ( wp_attachment_is_image( $attachment_id ) ) {

                $thumb = wp_get_attachment_image_src( $attachment_id, 'ptp-uploaded-item' );
                $response['thumb'] = $thumb[0];
                $response['type'] = 'image';
            } else {
                $response['thumb'] = wp_mime_type_icon( $file->post_mime_type );
                $response['type'] = 'file';
            }

            return $response;
        }

        return false;
    }

    public function delete_file( $file_id, $force = true ) {
        $res = wp_delete_attachment( $file_id, $force );

        if ( $res === false )
          return false;

        return true;
    }

}