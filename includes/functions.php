<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Forms the path to watermarked image
 *
 * @param string $file_loc
 * @param string $file_name
 * @param string $file_type
 * @return string
 */
function ptp_watermarked_image_path( $file_loc, $file_name, $file_type ) {
    global $ptp_importer;

    switch ( $file_type ) {
        case 'image/jpeg':
        case 'jpg':
            $path = dirname( $file_loc ) .'/'. basename( $file_name, '.jpg' ) . $ptp_importer->watermarked_suffix . '.jpg';
            break;
        case 'image/png':
        case 'png':
            $path = dirname( $file_loc ) .'/'. basename( $file_name, '.png' ) . $ptp_importer->watermarked_suffix  . '.png';
            break;
        default:
            return false;
    }

    return $path;
}

/**
* Add watermark
*
* @param string $file_loc
* @param string $output_file_path
* @param string $watermark_file_path
* @param string $opacity
* @param string $quality
* @return array
*/
function ptp_add_watermark( $source_file_path, $output_file_path, $watermark_file_path, $quality = 80 ) {
    
	$settings_obj = PTPImporter_Settings::getInstance();
    $settings = $settings_obj->get();
	
	list( $source_width, $source_height, $source_type ) = getimagesize( $source_file_path );

    if ( $source_type === NULL ) {
        return false;
    }

    switch ( $source_type ) {
        case IMAGETYPE_GIF:
            $source_gd_image = imagecreatefromgif( $source_file_path );
            break;
        case IMAGETYPE_JPEG:
            $source_gd_image = imagecreatefromjpeg( $source_file_path );
            break;
        case IMAGETYPE_PNG:
            $source_gd_image = imagecreatefrompng( $source_file_path );
            break;
        default:
            return false;
    }
	//Edited: 6/13/2014 Adjusted overlay image to stretch regardless of the watermark size
	//Edit Start
	
	$overlay_gd_image = imagecreatefrompng( $watermark_file_path );	
	$width = imagesx( $overlay_gd_image );
	$height  = imagesy( $overlay_gd_image );
	$overlay_width = imagesx( $overlay_gd_image );
    $overlay_height = imagesy( $overlay_gd_image );
	
	// Gaps in between watermark tiles pixels unit
	$distance = 50;
	
	if($settings['tiled_watermark'] == 1)
	{
		$h_count = $source_width / ($overlay_width + $distance);
		$v_count = $source_height / ($overlay_height + $distance);
		
		for($i = 0;$i <= $v_count;$i++)
		{
			for($j = 0;$j <= $h_count;$j++)
			{
				imagecopy (
					$source_gd_image,
					$overlay_gd_image,
					$j * ($overlay_width + $distance),
					$i * ($overlay_height + $distance),
					0,
					0,
					$overlay_width , 
					$overlay_height
				);
			}
		
		}
	}
	else
	{
        /*
		// Check if portrait or landscape
		if ($source_width > $source_height)
		{
			$percentage_aspect = ($source_height-$overlay_height)/$source_height*100; 
			$overlay_height = $source_height;
			$overlay_width = $source_height * ($percentage_aspect / 100) + $overlay_width;
		}
		else
		{
			$percentage_aspect = ($source_width-$overlay_width)/$source_width*100; 
			$overlay_width = $source_width;
			$overlay_height = $source_width * ($percentage_aspect / 100) + $overlay_height;
		}
		
		// Resize
		imagecopyresized (
			$source_gd_image,
			$overlay_gd_image,
			($source_width/2) - ($overlay_width/2),
			($source_height/2) - ($overlay_height/2),
			0,
			0,
			$overlay_width,
			$overlay_height,
			$width, 
			$height
		);
        */
       imagecopyresized (
            $source_gd_image,
            $overlay_gd_image,
            0,
            0,
            0,
            0,
            $source_width,
            $source_height,
            $width, 
            $height
        );
	}
	//Edit End
	
	
    imagejpeg( $source_gd_image, $output_file_path, $quality );
    imagedestroy( $source_gd_image );
    imagedestroy( $overlay_gd_image );
}

/**
 *  Creates watermarked version for each size.
 * 
 * @param array $metadata.
 */
function ptp_generate_watermaked_images( $metadata = array(), $attachment_id ) {
    global $ptp_importer;
	
    // Donnot embed watermark if not our image
    if ( !get_post_meta( $attachment_id, $ptp_importer->attachment_meta_key, true ))
        return $metadata;

    // Get uploads dir
    $uploads_dir = wp_upload_dir();
    $uploads_path = $uploads_dir['path'];

    // Create wartermarked version for the original image
    $file_name = basename( $metadata['file'] );
    $file_type = wp_check_filetype( $file_name );
    $source_file_path = "{$uploads_path}/" . $file_name;
    $woocommerce_uploads_path = $uploads_dir['basedir'] . '/woocommerce_uploads'  . $uploads_dir['subdir'];
    $outpout_file_path = ptp_watermarked_image_path( $source_file_path, $file_name, $file_type['ext'] );
	ptp_add_watermark( $source_file_path, $outpout_file_path, $ptp_importer->watermark_path, 100 );

    // Make sure the file is accessible
    chmod( $outpout_file_path , 0775 );

    // Replace path with the watermarked one
    $metadata['file'] = basename( $outpout_file_path );

    if ( !file_exists( $woocommerce_uploads_path ) ) {
        @mkdir( $woocommerce_uploads_path, 0755, true );
    }

    // Rename original file by prepending downloadable_
    rename( $source_file_path, "{$woocommerce_uploads_path}/downloadable_{$file_name}" );

    // Rename output file with original file name
    rename( $outpout_file_path, $source_file_path );

    // Create watermarked version for intermediate sizes
    foreach ( $metadata['sizes'] as $size => $atts ) {
        $file_name = basename( $atts['file'] );
        $source_file_path = "{$uploads_path}/" . $atts['file'];
        $outpout_file_path = ptp_watermarked_image_path( $source_file_path, $file_name, $atts['mime-type'] );
        ptp_add_watermark( $source_file_path, $outpout_file_path, $ptp_importer->watermark_path, 100 );
		unlink($source_file_path);
        // Make sure the file is accessible
        chmod( $outpout_file_path , 0775 );

        // Replace path with the watermarked one
        $metadata['sizes'][$size]['file'] = basename( $outpout_file_path );
    }

    return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'ptp_generate_watermaked_images', 10, 2 );

/**
 *  Return Wooocomerce product metadata defaults
 *  @TODO: Find out if Woocommerce has a helper function for setting product metata. 
 *  If there's one, use that as the primary helper and use this as a fallback.
 * 
 * @return array $metadata.
 */
function ptp_product_metadata_defaults() {
    $metadata = array(
                    '_virtual' => 'no',
                    '_sale_price_dates_from' => '',
                    '_sale_price_dates_to' => '',
                    '_price' => '',
                    '_stock' => '',
                    '_stock_status' => 'instock',
                    '_backorders' => 'no',
                    '_manage_stock' => 'no',
                    '_downloadable_files' => '',
                    '_download_limit' => '',
                    '_download_expiry' => '',
                    '_product_attributes' => array(),
                    '_downloadable' => 'no',
                    '_sku' => '',
                    '_height' => '',
                    '_width' => '',
                    '_length' => '',
                    '_weight' => '',
                    '_featured' => 'no',
                    '_visibility' => 'visible',
                    '_tax_class' => '',
                    '_tax_status' => '',
                    '_sale_price' => '',
                    '_regular_price' => '',
                    'total_sales' => 0,
                    '_product_image_gallery' => '',
                    '_purchase_note' => '',
                    '_sold_individually' => ''
                );

    return $metadata;
}

/**
 *  Returns variations of the current term
 *
 * @return array $variations.
 */
function ptp_variations() {
    global $ptp_importer;

    $variation_group_id = get_post_meta( get_the_ID( ), '_ptp_variation_group_id', true );

    // Backward compatibility
    if( !$variation_group_id ) {
        $term_id = $_GET['term_id'] ? $_GET['term_id'] : get_queried_object_id();
        $variation_group_id = ptp_get_term_meta( $term_id, $ptp_importer->term_variation_group_meta_key, true );
    }

    if( is_array( $variation_group_id ) ) {
        $variation_group_id = $variation_group_id[ 0 ];
    }

    $variation_obj = PTPImporter_Variation_Group::getInstance();
    $group = $variation_obj->group( $variation_group_id );

    return $group->variations;
}

/**
 *  Returns variations of the product
 *
 * @return array $variations.
 */
function ptp_get_product_variations( $post_id = null ) {
    global $ptp_importer;

    if( !$post_id ) {
        $post_id = get_the_ID();
    }

    $variation_group_id = get_post_meta( $post_id, '_ptp_variation_group_id', true );
    if( is_array( $variation_group_id ) ) {
        $variation_group_id = $variation_group_id[ 0 ];
    }

    $variation_obj = PTPImporter_Variation_Group::getInstance();
    $group = $variation_obj->group( $variation_group_id );

    return $group->variations;
}

/**
 *  Returns term id of product_type
 *
 * @return array $term_id
 */
function ptp_product_type_term_id() {
    global $wpdb;

    $sql = "SELECT `term_id`";
    $sql .= " FROM $wpdb->term_taxonomy"; 
    $sql .= " WHERE `taxonomy` = '%s'";

    $types = $wpdb->get_results( $wpdb->prepare( $sql, 'product_type' ) );

    return intval( $types[1]->term_id );
}

/**
 * Add meta data field to a term.
 *
 * @param int $term_id Post ID.
 * @param string $key Metadata name.
 * @param mixed $value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function ptp_add_term_meta($term_id, $meta_key, $meta_value, $unique = false) {
    return add_metadata('taxonomy', $term_id, $meta_key, $meta_value, $unique);
}

/**
 * Remove metadata matching criteria from a term.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int $term_id term ID
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function ptp_delete_term_meta($term_id, $meta_key, $meta_value = '') {
    return delete_metadata('taxonomy', $term_id, $meta_key, $meta_value);
}

/**
 * Retrieve term meta field for a term.
 *
 * @param int $term_id Term ID.
 * @param string $key The meta key to retrieve.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function ptp_get_term_meta($term_id, $key, $single = false) {
    return get_metadata('taxonomy', $term_id, $key, $single);
}

/**
 * Update term meta field based on term ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @param int $term_id Term ID.
 * @param string $key Metadata key.
 * @param mixed $value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function ptp_update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '') {
    return update_metadata('taxonomy', $term_id, $meta_key, $meta_value, $prev_value);
}

/**
 * Store custom templates
 *
 * @return array $templates
 */
function ptp_templates() {
    $templates = apply_filters( 'ptp_templates', array( 
            'quick-order-dialog' => 'ptp-quick-order.php',
    ) );

    return $templates;
}

/**
 * Check if term has posts
 *
 * @param $term_id
 * @return bolean true|false
 */
function ptp_term_has_posts( $term_id ) {
    global $wpdb;

    $sql = "SELECT `object_id`";
    $sql .= " FROM $wpdb->term_relationships";
    $sql .= " JOIN $wpdb->posts ON $wpdb->term_relationships.object_id = $wpdb->posts.ID"; 
    $sql .= " WHERE $wpdb->term_relationships.term_taxonomy_id = '%s'";
    $sql .= " AND $wpdb->posts.post_status = 'publish'";

    $posts = $wpdb->get_results( $wpdb->prepare( $sql, $term_id ) );

    if ( !$posts )
        return false;

    return true;
}

/**
 * Add quick order dialog, quick order anchor, and checkboxes
 * in individual products(including search) view only
 */
function ptp_add_custom_elements( $q ) {
    if ( is_admin() || !$q->is_main_query() ) 
        return false;

    $term_id = isset( $_GET['term_id'] ) ? $_GET['term_id'] : get_queried_object_id();

    // If not in category view bail out. Quick order works in category view only.
    if ( !$term_id ) 
        return;

    global $ptp_importer;

    // Disable quick order if not in child-most category
    //if ( get_term_children( $term_id, $ptp_importer->woocommerce_cat_tax ) )
    //    return;

    add_action( 'woocommerce_before_shop_loop_item', 'ptp_add_checkbox' );
    add_action( 'woocommerce_after_shop_loop', 'ptp_quick_order_trigger' );
    add_action( 'wp_footer', 'ptp_quick_order_dialog' );
}
add_filter( 'pre_get_posts', 'ptp_add_custom_elements' );

/**
 * Filter out variations in products page in the admin
 *
 * @param object $q
 * @return void
 */
function ptp_filter_out_variations( $q ) {
	
	require_once(ABSPATH . 'wp-admin/includes/screen.php');
    if ( !is_admin() || get_current_screen()->id != 'edit-product' || !$q->is_main_query() ) 
        return;

    $settings_obj = PTPImporter_Settings::getInstance();
    $settings = $settings_obj->get();

    if ($settings['hide_variations'] == "0") 
        return;

    global $wpdb;

    $sql = "SELECT {$wpdb->posts}.ID";
    $sql .= " FROM {$wpdb->posts}"; 
    $sql .= " JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id"; 
    $sql .= " WHERE {$wpdb->postmeta}.meta_key = '%s'"; 
    $sql .= " AND {$wpdb->postmeta}.meta_value = '%s'"; 
    $sql .= " GROUP BY {$wpdb->posts}.ID";

    $result = $wpdb->get_results( $wpdb->prepare( $sql, '_ptp_as_variation', 'yes' ) );

    $post__not_in = array();

    foreach ( $result as $post ) {
        $post__not_in[] = (int)$post->ID;
    }

    $q->set( 'post__not_in', $post__not_in );
}
add_filter( 'pre_get_posts', 'ptp_filter_out_variations' );

/**
 * Checks if current product is our product
 *
 * @param $post_id
 */
function ptp_our_product( $post_id ) {
    global $ptp_importer;

    if ( get_post_meta( $post_id, $ptp_importer->our_product_meta_key, true ) ) {
        ptp_our_products_counter();
        return true;
    } else 
        return false;
}

/**
 * Tracks number of products in the current page that are improted by BPTPI
 *
 * @return int $count
 */
function ptp_our_products_counter() {
    global $ptp_importer;

    ++$ptp_importer->number_of_products;
}

/**
 * Check if the serial entered is valid
 * 
 * @param string $serial_key
 * @return array
 */
function ptp_is_valid_serial_key( $serial_key ) {}

/**
 * Check if this IP is registered in the API
 *
 * @return array
 */
function ptp_is_ip_registered() {}

/**
 * Register IP Address and Domain Name where this plugin is installed
 *
 * @param string $serial_key
 * @return array
 */
function ptp_register_server_info( $serial_key ) {}

/**
 * Deactivate this plugin
 *
 * @param string $serial_key
 * @return array
 */
function ptp_deactivate($serial_key) {}

/**
 * Checks if plugin is active
 *
 * @return boolean
 */
function ptp_is_active() {
    return true;
}

/**
 * Verify
 *
 * @return array
 */
function ptp_verify() {}

/**
 * Get the term parent
 * @param  integer $term_id Term ID
 * @return integer          Term ID
 */
function get_term_parent( $term_id ) {
    global $wpdb;
    return $wpdb->get_var( $wpdb->prepare( "SELECT `tx`.`parent` FROM `{$wpdb->term_taxonomy}` `tx` LEFT JOIN `{$wpdb->terms}` `t` ON `t`.`term_id` = `tx`.`term_id` WHERE `tx`.`term_id` = %d", $term_id ) );
}

/**
 * Get the term parents
 * @param  integer $term_id Term ID
 * @return array            List of term parents
 */
function tpc_get_term_parents( $term_id ) {
    $parent  = $term_id;
    $parents = array( );

    while( $parent = get_term_parent( $parent ) ) {
        $parents[] = $parent;
    }

    return $parents;
}