<?php
class PTPImporter_Variation_Migrate {

    private $_db;
    private static $_instance;

    public function __construct() {
        global $wpdb;

        $this->_db = $wpdb;
    }

    public static function getInstance() {
        if ( !self::$_instance ) {
            self::$_instance = new PTPImporter_Variation_Migrate();
        }

        return self::$_instance;
    }

    /**
     * Migreate old variations
     *
     * @return null
     */
    public function migrate() {
        $groups = $this->groups();

        foreach ( $groups as $group ) {
            $variations_obj = PTPImporter_Variation_Group::getInstance();
            $variations_obj->add( $group->name, $group->description, $group->variations );
        }

        return true;
    }

    /**
     * Get all groups of variations
     *
     * @return object $variations
     */
    public function groups() {
        $sql = "SELECT Var.taxonomy_id as 'ID', `name`, Var.meta_value as 'variations', Des.meta_value as `description`, TT.term_id as `term_id`";
        $sql .= " FROM {$this->_db->taxonomymeta} Var";
        $sql .= " JOIN {$this->_db->taxonomymeta} Des ON Var.taxonomy_id = Des.taxonomy_id"; 
        $sql .= " JOIN {$this->_db->term_taxonomy} TT ON Var.taxonomy_id = TT.term_id";
        $sql .= " JOIN {$this->_db->terms} T ON Var.taxonomy_id = T.term_id"; 
        $sql .= " WHERE Var.meta_key = '%s'"; 
        $sql .= " AND Des.meta_key = '%s'";
        $sql .= " AND TT.parent = 0";
        $sql .= " GROUP BY Var.taxonomy_id";

        $groups = $this->_db->get_results( $this->_db->prepare( $sql, '_ptp_variations', '_ptp_variations_desc', 0 ) );

        if ( $groups ) {
            // unserialize
            foreach ( $groups as $group ) {
                $group->variations = unserialize( $group->variations );
            }
        }

        return $groups;
    }
}