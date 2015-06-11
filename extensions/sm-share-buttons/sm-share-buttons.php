<?php
/**
 * Description of Social Media Share Buttons
 */
class PTP_SM_Share {

	public $args;
	public $product_name;
	public $product_uri;
	public $product_description;
	public $product_image;
	public $plugin_uri;

    public function __construct( $args ) {
    	$this->args = $args;

    	$this->init();
    }

    public function init() {

    	 $defaults = array(
    		'product_name' => '',
    		'product_uri' => '',
    		'product_description' => '',
    		'product_image' => '',
    		'plugin_uri' => '',
    	);

    	$args = wp_parse_args( $this->args, $defaults );

		$this->product_name = rawurlencode( $args['product_name'] );
		$this->product_uri = rawurlencode( $args['product_uri'] );
		$this->product_description = rawurlencode( $args['product_description'] );
		$this->product_image = rawurlencode( $args['product_image'] );
		$this->plugin_uri = $args['plugin_uri'] . '/extensions/sm-share-buttons';

		if(is_admin())
			wp_enqueue_script( 'sm-scripts', $this->plugin_uri . '/js/sm.scripts.js' );
    }
	function display( $args = '' ) {

		$defaults = array(
		 	'mini' => false, 
    		'title' => 'Share this plugin',
    		'title_punctuation' => '&#58;',
    		'before_title' => '<h3 class="share-header">',
    		'after_title' => '</h3>',
    	);

    	$args = wp_parse_args( $args, $defaults );

		ob_start();
		?>
		
		<div class="share-wrapper">
				
			<?php if ( !$args['mini'] ): ?>	
			<?php echo $args['before_title'] ?> <?php echo $args['title'] ?><?php echo $args['title_punctuation'] ?> <?php echo $args['after_title'] ?>
			<?php endif; ?>
			
		</div>

		<?php
		return ob_get_clean();
	}
}