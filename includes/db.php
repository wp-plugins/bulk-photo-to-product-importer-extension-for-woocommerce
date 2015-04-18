<?php
global $wpdb;

$charset_collate = '';	
if ( ! empty($wpdb->charset) )
	$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
if ( ! empty($wpdb->collate) )
	$charset_collate .= " COLLATE $wpdb->collate";
	
$tables = $wpdb->get_results("show tables like '{$wpdb->prefix}taxonomymeta'");
if (!count($tables)) {
	$wpdb->query("CREATE TABLE {$wpdb->prefix}taxonomymeta (
		meta_id bigint(20) unsigned NOT NULL auto_increment,
		taxonomy_id bigint(20) unsigned NOT NULL default '0',
		meta_key varchar(255) default NULL,
		meta_value longtext,
		PRIMARY KEY	(meta_id),
		KEY taxonomy_id (taxonomy_id),
		KEY meta_key (meta_key)
		) $charset_collate;");
}