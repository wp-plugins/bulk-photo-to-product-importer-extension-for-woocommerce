<?php

/**
 * Only enable Variation Groups that have Variations. Used in Bulk Import.
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class Walker_With_Variations extends Walker_CategoryDropdown {
	/**
	 * @see Walker::$tree_type
	 * @since 2.1.0
	 * @var string
	 */
	var $tree_type = 'category';

	/**
	 * @see Walker::$db_fields
	 * @since 2.1.0
	 * @todo Decouple this
	 * @var array
	 */
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	/**
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $category Category data object.
	 * @param int $depth Depth of category. Used for padding.
	 * @param array $args Uses 'selected' and 'show_count' keys, if they exist.
	 */
	function start_el( &$output, $category, $depth, $args, $id = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		$cat_name = apply_filters('list_cats', $category->name, $category);
		$variation_group_obj = PTPImporter_Variation_Group::getInstance();

		$variations = $variation_group_obj->has_variations( $category->term_id );
		$disabled = $variations ? '' : 'disabled';

		$output .= "\t<option class=\"level-$depth\" value=\"".$category->term_id."\"" . $disabled;
		if ( $category->term_id == $args['selected'] )
			$output .= ' selected="selected"';
		$output .= '>';
		$output .= $pad.$cat_name;
		if ( $args['show_count'] )
			$output .= '&nbsp;&nbsp;('. $category->count .')';
		$output .= "</option>\n";
	}
}