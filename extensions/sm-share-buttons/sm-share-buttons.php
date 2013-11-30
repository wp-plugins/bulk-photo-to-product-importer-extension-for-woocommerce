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
			
			<?php if ( !class_exists( 'BPTPI_Premium' ) ) { ?>
			
				<ul class="share purchase-premium-notification clear" style="background: #a8e49e;" <?php if ( $args['mini'] ) echo 'style="display:none;"' ?>>
					
					<li><b>Downloadable Variations Introduced!</b> Simply create a BPTPI Variation named "Downloadable" and viola! Any users who purchase that Variation will be able to download the photo upon purchasing!</li>
				
					<?php if ( $args['mini'] ): ?>	
						<li class="last"><a class="ptp-nag-close" href="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;ptp_sm_hide=1"><?php _e( 'Dismiss', 'ptp' ); ?></a></li>
					<?php endif; ?>
				</ul>
			
				<ul class="share purchase-premium-notification clear" style="background: #a8e49e;" <?php if ( $args['mini'] ) echo 'style="display:none;"' ?>>
					
					<li><a href="http://www.theportlandcompany.com/shop/custom-web-applications/photo-to-product-importer-wordpress-plugin-for-woocommerce" target="_blank" style="color: #fff; font-size: 18px; padding: 3px 0 0 0; font-style: italics;"><i>1. Leave a Review. 2. Send an Email to Support 3. We'll send you a to upgrade for just $15!</i> &nbsp;&#187;</a></li>
				
					<?php if ( $args['mini'] ): ?>	
						<li class="last"><a class="ptp-nag-close" href="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;ptp_sm_hide=1"><?php _e( 'Dismiss', 'ptp' ); ?></a></li>
					<?php endif; ?>
				</ul>
			
				<ul class="share purchase-premium-notification clear" <?php if ( $args['mini'] ) echo 'style="display:none;"' ?>>
					
					<li><a href="http://www.theportlandcompany.com/shop/custom-web-applications/photo-to-product-importer-wordpress-plugin-for-woocommerce" target="_blank">Unlock new features by purchasing the Premium version &#187;</a></li>
				
					<?php if ( $args['mini'] ): ?>	
						<li class="last"><a class="ptp-nag-close" href="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;ptp_sm_hide=1"><?php _e( 'Dismiss', 'ptp' ); ?></a></li>
					<?php endif; ?>
				</ul>
				
			<?php } ?>
			
			<ul class="share clear" <?php if ( $args['mini'] ) echo 'style="display:none;"' ?>>
				
				<?php if ( $args['mini'] ): ?>	
				<li><img src="<?php echo $this->plugin_uri . '/images/share_icon.png'; ?>" alt="Share"/></li>
				<?php endif; ?>
				
				<li>Sharing this Plugin helps fund it! </li>

				<li><a href="javascript:twitterShare('<?php echo $this->product_uri; ?>', '<?php echo $this->product_description; ?>', 602, 496 )" data-lang="en"><img src="<?php echo $this->plugin_uri . '/images/twitter_icon.jpg'; ?>" alt="Share on Twitter" /></a></li>
				<li><a href="javascript:fbShare('<?php echo $this->product_uri; ?>', '<?php echo $this->product_name; ?>', '<?php echo $this->product_description; ?>', 600, 400)" target="_blank"><img src="<?php echo $this->plugin_uri . '/images/fb_icon.jpg'; ?>" alt="Share on Facebook" /></a></li>
				<li><a href="javascript:gplusShare('<?php echo $this->product_uri; ?>', 483, 540)" ><img src="<?php echo $this->plugin_uri . '/images/gplus_icon.jpg'; ?>" alt="Share on Google+"/></a></li>
				<li><a href="http://www.tumblr.com/share/link?url=<?php echo $this->product_uri ?>&amp;name=<?php echo $this->product_name ?>&amp;description=<?php echo $this->product_description ?>" title="Share on Tumblr" ><img src="<?php echo $this->plugin_uri . '/images/tumblr_icon.jpg'; ?>" alt="Share on Tumblr"/></a></li>
				<li><a href="javascript:pinterestShare('<?php echo $this->product_uri; ?>', '<?php echo $this->product_image; ?>', '<?php echo $this->product_description; ?>', 774, 452)" data-pin-do="buttonPin" ><img src="<?php echo $this->plugin_uri . '/images/pinterest_icon.jpg'; ?>" alt="Share on Pinterest"/></a></li>
				<li><a href="javascript:stumbleuponShare('<?php echo $this->product_uri; ?>', 802, 592)"><img src="<?php echo $this->plugin_uri . '/images/stumbleupon_icon.jpg'; ?>" alt="Share on Stumbleupon"/></a></li>
				<li><a href="javascript:linkedinShare('<?php echo $this->product_uri; ?>', '<?php echo $this->product_name; ?>', '<?php echo $this->product_description; ?>', 850, 450)"><img src="<?php echo $this->plugin_uri . '/images/linkedin_icon.jpg'; ?>" alt="Share on LinkdeIn"/></a></li>
				<li><a href="javascript:redditShare('<?php echo $this->product_uri; ?>', 800, 400)"><img src="<?php echo $this->plugin_uri . '/images/reddit_icon.jpg'; ?>" alt="Share on Reddit"/></a></li>
				<li><a href="mailto:?subject=<?php echo $this->product_name; ?>&amp;body=This plugin is really good. Check it out:<?php echo $this->product_uri; ?>"><img src="<?php echo $this->plugin_uri . '/images/email_icon.jpg'; ?>" alt="Email to a friend"/></a></li>
			
				<?php if ( $args['mini'] ): ?>	
				<li class="last"><a class="ptp-nag-close" href="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;ptp_sm_hide=1"><?php _e( 'Dismiss', 'ptp' ); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>

		<?php
		return ob_get_clean();
	}
}