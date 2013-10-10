<?php
/*
Plugin Name: Bulk Photo to Product Importer Extension for WooCommerce (Free)
Plugin URI: http://www.theportlandcompany.com/shop/custom-web-applications/bulk-photo-to-product-importer-extension-for-woocommerce/
Description: This Plugin is an extension to WooCommerce and enables users to bulk import photos, which are automatically converted into Products.
Author: The Portland Company, Designed by Spencer Hill, Coded by Redeye Adaya
Author URI: http://www.theportlandcompany.com
Version: 2.1.9
Copyright: 2013 The Portland Company 
License: GPL v3
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PTP_Importer' ) ) {

class PTP_Importer {

    /**
     * @var string
     */
    public $version = '2.1.9';

    /**
     * @var string
     */
    public $plugin_path;

    /**
     * @var string
     */
    public $plugin_uri;

    /**
     * @var string
     */
    public $plugin_name;

    /**
     * @var string
     */
    public $watermark_path;

    /**
     * @var string
     */
    public $watermarked_suffix;

    /**
     * @var string
     */
    public $term_variation_group_meta_key;

     /**
     * @var string
     */
    public $variation_price_meta_key;

    /**
     * @var string
     */
    public $event_date_meta_key;

     /**
     * @var string
     */
    public $our_product_meta_key;

     /**
     * @var string
     */
    public $attachment_meta_key;

     /**
     * @var string
     */
    public $settings_meta_key;

     /**
     * @var string
     */
    public $woocommerce_cat_tax;

     /**
     * @var string
     */
    public $woocommerce_post_type;

     /**
     * @var int
     */
    public $number_of_products;

    /**
     * @var string
     */
    public $taxonomy;

    /**
     * @var string
     */
    public $post_type;

    /**
     * @var obj
     */
    public $sm_share_buttons;


    function __construct() {
        // Auto-load classes on demand
        spl_autoload_register( array( $this, 'autoload' ) );

        // Define version constant
        define( 'PTP_IMPORTER', $this->version );

        // Admin notices
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );

        // Include required files
        add_action( 'plugins_loaded', array( $this, 'includes' ));

        add_action( 'init', array( $this, 'init' ), 200 );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'widgets_init', array( $this, 'register_widgets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
        add_action( 'admin_footer', array( $this, 'menu_logo' ) );

        register_activation_hook( __FILE__, array( $this, 'install') );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_cron') );

        add_action( 'wp', array( $this, 'activate_cron' ));
        add_action( 'ptp_cron', array( $this, 'verify' ));
    }


    /**
     * Auto-load PTP_Importer classes on demand to reduce memory consumption.
     *
     * @access public
     * @param mixed $class
     * @return void
     */
    public function autoload( $class ) {

        $name = explode( '_', $class );

        if ( isset( $name[1] ) || isset( $name[2] ) ) {
            if ( sizeof( $name ) > 2 ) {
                $class_name = strtolower( $name[1] ) . '-' . strtolower( $name[2] );
                $filename = dirname( __FILE__ ) . '/classes/' . $class_name . '.php';
            } else {
                $class_name = strtolower( $name[1] );
                $filename = dirname( __FILE__ ) . '/classes/' . $class_name . '.php';
            }

            if ( file_exists( $filename ) ) {
                require_once $filename;
            }
        }
    }
    
    /**
     * Init PTPImporter when WordPress Initialises.
     *
     * @access public
     * @return void
     */
    public function init() {
        $this->plugin_path = dirname( __FILE__ );
        $this->plugin_uri = plugins_url( '', __FILE__ );
        $this->watermark_path = plugins_url( 'assets/images/watermark.png', __FILE__ );
        $this->plugin_name = 'Bulk Photo to Product Importer Extension for WooCommerce';

        $this->watermarked_suffix = '_watermarked';
        $this->event_date_meta_key = '_ptp_event_date';
        $this->settings_meta_key = '_ptp_settings';
        $this->our_product_meta_key = '_ptp_product';
        $this->variation_price_meta_key = '_ptp_variation_price';
        $this->term_variation_group_meta_key = '_ptp_term_variation_group';
        $this->attachment_meta_key = '_ptp_attachment';

        $this->number_of_products = 0;
        $this->woocommerce_cat_tax = 'product_cat';
        $this->woocommerce_post_type = 'product';
        $this->taxonomy = 'ptp_variation_group';
        $this->post_type = 'ptp_variation';

        add_image_size( 'ptp-uploaded-item', 178, 178, true );

        global $wpdb;
        $wpdb->taxonomymeta = "{$wpdb->prefix}taxonomymeta";

        $this->register_post_type();
        $this->register_taxonomy();
        $this->extensions();

        new PTPImporter_Product();
        new PTPImporter_Variation_Group();
        new PTPImporter_Variation_Migrate();
        new PTPImporter_Ajax();
        new PTPImporter_Settings();
    }

    /**
     * Runs the setup when the plugin is installed
     */
    public function install() {
        update_option( 'ptp_importer_version', $this->version );

        // Create custom table
        require_once dirname( __FILE__ ) . '/includes/db.php';
    }

    /**
     * Dynamic styling for menu
     */
    public function menu_logo() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            // Logo
            if ( $('#toplevel_page_ptp_bulk_import').hasClass('wp-has-current-submenu') )
                $('#toplevel_page_ptp_bulk_import').prop('id', 'ptp-toplevel-menu-active');
            else
                $('#toplevel_page_ptp_bulk_import').prop('id', 'toplevel_page_ptp_bulk_import');
        });
        </script>

        <style type="text/css">
            #toplevel_page_ptp_bulk_import .wp-menu-image {
                background: url(<?php echo $this->plugin_uri ?>/assets/images/logo.png) no-repeat;
                background-position: 5px 5px !important;
            }
            #toplevel_page_ptp_bulk_import:hover .wp-menu-image,
            #ptp-toplevel-menu-active .wp-menu-image {
                background: url(<?php echo $this->plugin_uri ?>/assets/images/logo_hover.png) no-repeat;
                background-position: 5px 5px !important;
            }
        </style>
        <?php
    }

    /**
     * Load all the plugin scripts and styles only for importer area
     */
    public function admin_scripts() {
        wp_enqueue_script( 'media-upload' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'plupload-handlers' );
        wp_enqueue_script( 'ptp_chosen', plugins_url( 'assets/js/chosen.jquery.min.js', __FILE__ ) );
        wp_enqueue_script( 'ptp_validate', plugins_url( 'assets/js/jquery.validate.min.js', __FILE__ ) );
        wp_enqueue_script( 'ptp_admin', plugins_url( 'assets/js/admin.js', __FILE__ ) );
        wp_enqueue_script( 'ptp_misc', plugins_url( 'assets/js/misc.js', __FILE__ ) );
        wp_enqueue_script( 'ptp_uploader', plugins_url( 'assets/js/image.upload.js', __FILE__ ), array('jquery', 'plupload-handlers') );

        wp_localize_script( 'ptp_admin', 'PTPImporter_Vars', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ptp_nonce' ),
            'is_active' => ptp_is_active() ? 'yes' : 'no',
            'plupload' => array(
                'browse_button' => 'upload-pickfiles',
                'container' => 'upload-container',
                'max_file_size' => wp_max_upload_size() . 'b',
                'url' => admin_url( 'admin-ajax.php' ) . '?action=ptp_product_upload&ptp_nonce=' . wp_create_nonce( 'ptp_product_upload' ),
                'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
                'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
                'filters' => array(array('title' => __( 'Image Files' ), 'extensions' => 'jpg,png')),
            )
        ) );

        wp_enqueue_style('thickbox');
        wp_enqueue_style( 'jquery-ui', plugins_url( 'assets/css/jquery-ui-1.9.1.custom.css', __FILE__ ) );
        wp_enqueue_style( 'ptp_chosen', plugins_url( 'assets/css/chosen.min.css', __FILE__ ) );
        wp_enqueue_style( 'ptp_admin', plugins_url( 'assets/css/admin.css', __FILE__ ) );

        do_action( 'ptp_admin_enqueue_scripts' );
    }

    /**
     * Load all the plugin scripts and styles only for the front-end
     */
    public function frontend_scripts() {
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'ptp_chosen', plugins_url( 'assets/js/chosen.jquery.min.js', __FILE__ ) );
        wp_enqueue_script( 'ptp_frontend', plugins_url( 'front-end/assets/js/frontend.js', __FILE__ ) );
        wp_enqueue_script( 'ptp_misc', plugins_url( 'front-end/assets/js/misc.js', __FILE__ ) );

        wp_localize_script( 'ptp_frontend', 'PTPImporter_Vars_Frontend', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ptp_importer_frontend_nonce' ),
        ) );

        wp_enqueue_style( 'jquery-ui', plugins_url( 'assets/css/jquery-ui-1.9.1.custom.css', __FILE__ ) );
        wp_enqueue_style( 'ptp_chosen', plugins_url( 'assets/css/chosen.min.css', __FILE__ ) );
        wp_enqueue_style( 'ptp_frontend', plugins_url( 'front-end/assets/css/frontend.css', __FILE__ ) );

        do_action( 'ptp_frontend_enqueue_scripts' );
    }

    /**
     * Settings page
     */
    public function includes() {
        require_once dirname( __FILE__ ) . '/includes/functions.php';
        require_once dirname( __FILE__ ) . '/includes/html.php';
        require_once dirname( __FILE__ ) . '/classes/walkers/without-children.php';
        require_once dirname( __FILE__ ) . '/classes/walkers/with-variations.php';
    }

    /**
     * Register the plugin menu
     */
    public function admin_menu() {
        if ( !class_exists('WooCommerce') )
            $capability = 'manage_options'; //minimum level: manage_options
        else
            $capability = 'manage_woocommerce'; //minimum level: manage_woocommerce

        $index_hook = add_menu_page( __( 'BPTPI', 'ptp' ), __( 'BPTPI', 'ptp' ), $capability, 'ptp_bulk_import', array($this, 'admin_page_handler'), '', 58 );
        $index_hook = add_submenu_page( 'ptp_bulk_import', __( 'Bulk Import', 'ptp' ), __( 'Bulk Import', 'ptp' ), $capability, 'ptp_bulk_import', array($this, 'admin_page_handler') );
        $variations_hook = add_submenu_page( 'ptp_bulk_import', __( 'Variation Groups', 'ptp' ), __( 'Variation Groups', 'ptp' ), $capability, 'ptp_variations', array($this, 'admin_page_handler') );

        do_action( 'ptp_before_settings_menu', $capability, array($this, 'admin_page_handler'), array($this, 'admin_scripts') );

        $settings_hook = add_submenu_page( 'ptp_bulk_import', __( 'Settings', 'ptp' ), __( 'Settings', 'ptp' ), $capability, 'ptp_settings', array($this, 'admin_page_handler') );

        add_action( $index_hook, array($this, 'admin_scripts') );
        add_action( $variations_hook, array($this, 'admin_scripts') );
        add_action( $settings_hook, array($this, 'admin_scripts') );
    }

    /**
     * Main function that renders the admin area for all the project
     * related markup.
     */
    public function admin_page_handler() {
        echo '<div class="wrap ptp">';

        $pages = apply_filters('ptp_admin_pages', array(
            array('page'=> 'ptp_bulk_import', 'html' => dirname( __FILE__ ) . '/index.php'),
            array('page'=> 'ptp_variations', 'html' => dirname( __FILE__ ) . '/variations.php'),
            array('page'=> 'ptp_settings', 'html' => dirname( __FILE__ ) . '/settings.php'),
        ));

        foreach ( $pages as $page ) {
            if ( $_GET['page'] == $page['page'] )
                include_once $page['html'];
        }

        echo '</div>';
    }

    /**
     * Register custom widgets
     *
     * @return void
     */
    public function register_widgets() {
        require_once dirname( __FILE__ ) . '/front-end/widgets/cart.php';
        register_widget( 'PTP_Widget_Cart' );
    }

        /**
     * Register custom post type
     * @return void
     */
    public function register_post_type() {
        $labels = array(
            'name' => 'Variations',
            'singular_name' => 'Variation',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Variation',
            'edit_item' => 'Edit Variation',
            'new_item' => 'New Variation',
            'all_items' => 'All Variations',
            'view_item' => 'View Variation',
            'search_items' => 'Search Variations',
            'not_found' =>  'No variations found',
            'not_found_in_trash' => 'No variations found in Trash', 
            'parent_item_colon' => '',
            'menu_name' => 'Variations',
        );

          $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => false, 
            'show_in_menu' => false, 
            'query_var' => true,
            'rewrite' => array( 'slug' => 'ptp_variation' ),
            'capability_type' => 'post',
            'has_archive' => true, 
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array(),
        ); 

        register_post_type( $this->post_type, $args );
    }

    /**
     * Register custom taxonomy
     * @return void
     */
    public function register_taxonomy() {
        $labels = array(
            'name'                => _x( 'Variation Groups', 'wpcp' ),
            'singular_name'       => _x( 'Variation Group', 'wpcp' ),
            'search_items'        => __( 'Search Variation Groups' ),
            'all_items'           => __( 'All Variation Groups' ),
            'parent_item'         => __( 'Parent Variation Group' ),
            'parent_item_colon'   => __( 'Parent Variation Group:' ),
            'edit_item'           => __( 'Edit Variation Group' ), 
            'update_item'         => __( 'Update Variation Group' ),
            'add_new_item'        => __( 'Add New Variation Group' ),
            'new_item_name'       => __( 'New Variation Group Name' ),
            'menu_name'           => __( 'Variation Groups' )
        );    

        $args = array(
            'hierarchical'        => true,
            'labels'              => $labels,
            'show_ui'             => false,
            'show_admin_column'   => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'ptp_variation_group' )
        );

        register_taxonomy( $this->taxonomy, array( $this->post_type ), $args );
    }

    /**
     * Admin notices
     *
     * @return void
     */
    public function admin_notices() { 
        ?>
        <?php if ( !class_exists('WooCommerce') && get_current_screen()->parent_base == 'ptp_bulk_import' ) : ?>
            <div class="error">
                <?php 
                printf( 
                    '<p> %1$s <a href="%2$s" target="_blank">%3$s</a></p>', 
                    __( 'Bulk Photo to Product Importer Extension for WooCommerce requires WooCommerce to be installed and activated.', 'ptp' ),
                    get_bloginfo( 'home' ) . '/wp-admin/plugin-install.php?tab=search&s=Woocommerce&plugin-search-input=Search+Plugins',
                    __( 'Install Woocommerce &nbsp;&raquo', 'ptp' )
                ); 
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ( !get_user_meta( get_current_user_id(), 'ptp_first_install', true ) && get_current_screen()->parent_base == 'ptp_bulk_import' ) : 
            if ( $_GET['ptp_nag_hide'] == 1 ) {
                update_user_meta( get_current_user_id(), 'ptp_first_install', 1 );
                return;
            } ?>
            
            <div class="updated">
                <?php 
                printf( 
                    '<p class="clear"> %1$s <a href="%2$s" target="_blank"> %3$s </a> %4$s <a class="ptp-nag-close" href="%5$s"> %6$s </a> </p>', 
                    __( 'First time? Having trouble? Review the', 'ptp' ), 
                    __( 'http://www.theportlandcompany.com/2013/08/photo-to-product-importer-extension-for-woocommerce-documentation', 'ptp'),
                    __( 'Documentation', 'ptp' ), 
                    __( '&#187;', 'ptp' ), 
                    esc_url( add_query_arg( 'ptp_nag_hide', 1 ) ), 
                    __( 'Dismiss', 'ptp' ) 
                ); 
                ?>
            </div>
        <?php endif; ?>

        <?php
    }

    /**
     * Extensions
     */
    public function extensions() {
        require_once dirname( __FILE__ ) . '/extensions/sm-share-buttons/sm-share-buttons.php';

        $args = array(
            'product_name' => 'Bulk Photo to Product Importer Extension for WooCommerce',
            'product_uri' => 'http://goo.gl/sCkeS',
            'product_description' => 'Are you a photographer who wants to sell your products on your WordPress website but you find the native WooCommerce interface to limit your ability to sell your product? Well we created this WordPress Plugin for users whose business model is centric to selling photography in an eCommerce environment...',
            'product_image' => $this->plugin_uri . '/screenshot-1.png',
            'plugin_uri' => $this->plugin_uri,
        );

        // Init SM Share Buttons
        $this->sm_share_buttons = new PTP_SM_Share( $args );
    }

    /**
     * Activate Cron
     *
     * @return void
     */
    public function activate_cron() {
        if ( !wp_next_scheduled( 'ptp_cron' ) ) {
            wp_schedule_event( time(), 'twicedaily', 'ptp_cron' );
        }
    }

    /**
     * Deactivate Cron
     *
     * @return void
     */
    public function deactivate_cron() {
        if( false !== ( $time = wp_next_scheduled( 'ptp_cron' ) ) ) {
            wp_unschedule_event( $time, 'ptp_cron' );
        }
    }

    /**
     * Verify
     *
     * @return void
     */
    public function verify() {
        $res = ptp_verify();

        if ( !$res ) {
            delete_option( '_ptp_status' );
            ptp_deactivate();
        }
    }

}

$GLOBALS['ptp_importer'] = new PTP_Importer();

} // class_exists check