<?php
/**
 * Shopping Cart Widget
 *
 * Displays shopping cart widget
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PTP_Widget_Cart extends WP_Widget {
	function __construct() {

		$widget_ops = array('classname' => 'ptp_widget_cart', 'description' => __( "Display the user's Cart in the sidebar.") );
		parent::__construct('ptp-cart', __('BPTPI Cart'), $widget_ops);
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		global $woocommerce;

		extract( $args );

		if ( is_cart() || is_checkout() ) return;

		$title = $instance['title'];
		$hide_if_empty = empty( $instance['hide_if_empty'] ) ? 0 : 1;

		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;

		// Insert cart widget placeholder - code in woocommerce.js will update this on page load
		echo '<div class="ptp-widget-cart-wrap"><input type="button" class="toggle" value="'. apply_filters( 'ptp_widget_cart_toggle_text', 'View Cart' ) .'" /><div class="con"><div class="ptp-widget-cart-content"></div></div></div>';

		echo $after_widget;
	}


	/**
	 * update function.
	 *
	 * @see WP_Widget->update
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags( stripslashes( $new_instance['title'] ) );
		$instance['hide_if_empty'] = empty( $new_instance['hide_if_empty'] ) ? 0 : 1;
		return $instance;
	}


	/**
	 * form function.
	 *
	 * @see WP_Widget->form
	 * @access public
	 * @param array $instance
	 * @return void
	 */
	function form( $instance ) {
		$hide_if_empty = empty( $instance['hide_if_empty'] ) ? 0 : 1;
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'woocommerce' ) ?></label>
		<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
		<?php
	}

}