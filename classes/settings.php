<?php

/**
 * Description of Settings
 */
class PTPImporter_Settings {

    private static $_instance; 
    private $ptp_importer;
    private $defaults;

    public function __construct() {
        global $ptp_importer;

        $this->ptp_importer = $ptp_importer;

        $this->init();
    }

    public static function getInstance() {
        if ( !self::$_instance ) {
            self::$_instance = new PTPImporter_Settings();
        }

        return self::$_instance;
    }

    /**
     * Set default settings
     */
    public function init() {
        $this->defaults = apply_filters( 'ptp_settings_args', array(
            'hide_variations' => 1,
        ) );

        if ( get_option( $this->ptp_importer->settings_meta_key ) )
            return;

        add_option( $this->ptp_importer->settings_meta_key, $this->defaults );
    }

    /**
     * Update/Save settings
     *
     * @param $posted
     */
    public function update( $posted = array() ) {

        $settings = get_option( $this->ptp_importer->settings_meta_key );
        $count = 0;

        foreach ( $this->defaults as $key => $value ) {
            if ( !isset($posted[$key]) )
                continue;

            if ( $settings[$key] == $posted[$key] )
                continue;

            $settings[$key] = $posted[$key];

            ++$count;
        }

        if ( $count )
            update_option( $this->ptp_importer->settings_meta_key, $settings );
        
        return true;
    }

    /**
     * Retun settings
     * 
     * @return array $settings
     */
    public function get() {
        $settings = wp_parse_args( get_option( $this->ptp_importer->settings_meta_key ), $this->defaults );

        return $settings;
    }

}