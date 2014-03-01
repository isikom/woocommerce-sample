<?php
/**
 * Plugin Name: WooCommerce Sample
 * Plugin URI: http://www.isikom.net/
 * Description: Include Get Sample Button in products of your online store.
 * Author: Michele Menciassi
 * Author URI: https://plus.google.com/+MicheleMenciassi
 * Version: 0.0.2
 * License: GPLv2 or later
 */
 
// Exit if accessed directly
if (!defined('ABSPATH'))
  exit;

//Checks if the WooCommerce plugins is installed and active.
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	if (!class_exists('WooCommerce_Sample')) {
		class WooCommerce_Sample {
		/**
		 * Gets things started by adding an action to initialize this plugin once
		 * WooCommerce is known to be active and initialized
		 */
		public function __construct() {
			add_action('woocommerce_init', array(&$this, 'init'));
		}

		/**
		 * to add the necessary actions for the plugin
		 */
		public function init() {
	        // backend stuff
	        add_action('woocommerce_product_write_panel_tabs', array($this, 'product_write_panel_tab'));
	        add_action('woocommerce_product_write_panels', array($this, 'product_write_panel'));
	        add_action('woocommerce_process_product_meta', array($this, 'product_save_data'), 10, 2);
	        // frontend stuff
	        add_action('woocommerce_after_add_to_cart_form', array($this, 'product_sample_button'));      
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
			// Prevent add to cart
			add_filter('woocommerce_add_to_cart_validation', array( $this, 'add_to_cart' ), 40, 4 );
			add_filter('woocommerce_add_cart_item_data', array( $this, 'add_sample_to_cart_item_data' ), 10, 3 );
			add_filter('woocommerce_get_item_data', array( $this, 'add_item_data' ), 10, 2 );
			add_filter('woocommerce_get_cart_item_from_session', array( $this, 'filter_session'), 10, 3);
			add_filter('woocommerce_in_cart_product_title', array( $this, 'cart_title'), 10, 3);
			add_filter('woocommerce_cart_item_quantity', array( $this, 'cart_item_quantity'), 10, 2);
			add_filter('woocommerce_shipping_free_shipping_is_available', array( $this, 'enable_free_shipping'), 40, 1);
			add_filter('woocommerce_available_shipping_methods', array( $this, 'free_shipping_filter'), 10, 1);
			// filter for Minimum/Maximum plugin override overriding
			if (in_array('woocommerce-min-max-quantities/min-max-quantities.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				add_filter('wc_min_max_quantity_minimum_allowed_quantity', array($this, 'minimum_quantity'), 10, 4 );
				add_filter('wc_min_max_quantity_maximum_allowed_quantity', array($this, 'maximum_quantity'), 10, 4 );
				add_filter('wc_min_max_quantity_group_of_quantity', array($this, 'group_of_quantity'), 10, 4 );			
			}
		}

		// filter for Minimum/Maximum plugin overriding
		function minimum_quantity($minimum_quantity, $checking_id, $cart_item_key, $values){
			if ($values['sample'])
				$minimum_quantity = 1;
			return $minimum_quantity;
		}
      
		function maximum_quantity($maximum_quantity, $checking_id, $cart_item_key, $values){
			if ($values['sample'])
				$maximum_quantity = 1;
			return $maximum_quantity;
		}

		function group_of_quantity($group_of_quantity, $checking_id, $cart_item_key, $values){
			if ($values['sample'])
				$group_of_quantity = 1;
			return $group_of_quantity;
		}
		// end filter for Mimimum/Maximum plugin overriding

      function enable_free_shipping($is_available){
      	      $is_available = true;
      	      return $is_available;
      }

      function free_shipping_filter( $available_methods )
      {
      	      //var_dump( $available_methods );
      	      // remove standard shipping option
    	      // var_dump( get_option('active_plugins') );
      	      
      	      if ( isset( $available_methods['free_shipping'] ) AND isset( $available_methods['flat_rate'] ) )
      	      	      unset( $available_methods['flat_rate'] );
      	      return $available_methods;
      }

      function cart_item_quantity ($product_quantity, $cart_item_key){
      	      global $woocommerce;
      	      if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
      	      	      $cart_items = $woocommerce->cart->get_cart();
      	      	      $cart_item =$cart_items[$cart_item_key];
      	      	      if ($cart_item['sample']){
      	      	      	      $product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
      	      	      }
      	      }			
      	      return $product_quantity; 
      }
      
      function cart_title($title, $values, $cart_item_key){
      	      if ($values['sample']){
      	      	      $title .= ' [' . __('Sample','woo_sample') . '] ';
      	      }
      	      return $title;
      }
      
      function filter_session($cart_content, $value, $key){
      	      if ($value['sample']){
      	      	      $cart_content['sample'] = true;
      	      	      $cart_content['unique_key'] = $value['unique_key'];
      	      }
      	      return $cart_content;
      }
      
      function add_item_data($item_data, $cart_item){
      	      global $cart_item_key;
      	      
      	      error_log("add_item_data");
      	      error_log(serialize($cart_item_key));
      	      error_log(serialize($item_data));
      	      error_log(serialize($cart_item));
      	      if ($cart_item['sample']){
      	      	      error_log('SAMPLE TRUE');
      	      }else{
      	      	      error_log('SAMPLE FALSE');
      	      }
      	      return $item_data;
      }
      
      function add_sample_to_cart_item_data ($cart_item_data, $product_id, $variation_id){
      	      error_log("##################################################################################");
      	      error_log("########################## add_sample_to_cart_item_data ##########################");
      	      error_log(serialize($cart_item_data));
      	      error_log("##################################################################################");
      	      if (get_post_meta($product_id, 'sample_enamble') && $_REQUEST['sample']){
      	      		error_log('is a sample');
					$cart_item_data['sample'] = true;
					$cart_item_data['unique_key'] = md5($product_id . 'sample');
      	      }else{
      	      		error_log('is not a sample');
      	      }
      	      error_log("##################################################################################");
      	      error_log(serialize($cart_item_data));
      	      error_log("##################################################################################");
      	      error_log("##################################################################################");
      	      return $cart_item_data;
      }
      
      /**
       * add_to_cart function.
       *
       * @access public
       * @param mixed $pass
       * @param mixed $product_id
       * @param mixed $quantity
       * @return void
       */
      function add_to_cart( $pass, $product_id, $quantity, $variation_id = 0 ) {
	global $woocommerce;
	
	// se ci sono articoli nel carrello eseguiamo i controlli altrimenti se il carrello è vuoto aggiungiamo l'elemento senza controlli ulteriori
	if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
		$is_sample = empty($_REQUEST['sample']) ? false : true;
		// eseguiamo una validazione specifica solo se l'articolo aggiunto è un campione
		if ($is_sample){
			// l'articolo richiesto è un "campione" controlliamo che non sia già stato inserito nel carrello
			$cart_items = $woocommerce->cart->get_cart();
			$unique_key = md5($product_id . 'sample');
			
			foreach ($cart_items as $cart_id_key => $cart_item){
				if ($cart_item['unique_key'] == $unique_key){
					$woocommerce->add_error( __( 'A sample of the same product is already present into your cart', 'woo_sample' ) );
					return false;
				}
				if ($cart_item['product_id'] == $product_id){
					$woocommerce->add_error( __( 'You have already added this product on your cart, you can\'t add a sample of the same item', 'woo_sample' ) );
					return false;
				}
			}
		}
	}
	// passiamo il valore impostato di default;
	return $pass;
      }
      /**
       * creates the tab for the administrator, where administered product sample.
       */
      public function product_write_panel_tab() {
        echo "<li><a class='added_sample' href=\"#sample_tab\">" . __('Sample','woo_sample') . "</a></li>";
      }

		/**
		 * build the panel for the administrator.
		 */
		public function product_write_panel() {
        	global $post;
			$sample_enable = get_post_meta($post->ID, 'sample_enamble', true) ? get_post_meta($post->ID, 'sample_enamble', true) : false;
			$sample_shipping_mode = get_post_meta($post->ID, 'sample_shipping_mode', true) ? get_post_meta($post->ID, 'sample_shipping_mode', true) : 'default';
			$sample_shipping = get_post_meta($post->ID, 'sample_shiping', true) ? get_post_meta($post->ID, 'sample_shipping', true) : 0;
			$sample_price_mode = get_post_meta($post->ID, 'sample_price_mode', true) ? get_post_meta($post->ID, 'sample_price_mode', true) : 'default';
			$sample_price = get_post_meta($post->ID, 'sample_price', true) ? get_post_meta($post->ID, 'sample_price', true) : 0;
			?>
			<div id="sample_tab" class="panel woocommerce_options_panel">
				<p class="form-field sample_enamble_field ">
					<label for="sample_enamble"><?php _e('Enable sample', 'woo_sample');?></label>
					<input type="checkbox" class="checkbox" name="sample_enamble" id="sample_enamble" value="yes" <?php echo $sample_enable ? 'checked="checked"' : ''; ?>> <span class="description"><?php _e('Enable or disable sample option for this item.', 'woo_sample'); ?></span>
				</p>
				<legend><?php _e('Sample Shipping', 'woo_sample'); ?></legend>
				<div class="options_group">
					<input class="radio" id="sample_shipping_default" type="radio" value="default" name="sample_shipping_mode" <?php echo $sample_shipping_mode == 'default' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_shipping_default"><?php _e('use default product shipping methods', 'woo_sample'); ?></label>
				</div>
				<div class="options_group">
					<input class="radio" id="sample_shipping_free" type="radio" value="free" name="sample_shipping_mode" <?php echo $sample_shipping_mode == 'free' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_shipping_free"><?php _e('free shipping for sample', 'woo_sample'); ?></label>
				</div>
				<div class="options_group">
					<input class="radio" id="sample_shipping_custom" type="radio" value="custom" name="sample_shipping_mode" <?php echo $sample_shipping_mode == 'custom' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_shipping_custom"><?php _e('custom fee shipping', 'woo_sample'); ?></label>
					<p class="form-field sample_shipping_field clear">
						<label for="sample_shipping"><?php _e('set shipping fee', 'woo_sample'); ?></label>
						<input type="number" class="wc_input_price short" name="sample_shipping" id="sample_shipping" value="<?php echo $sample_shipping; ?>" step="any" min="0">
					</p>
				</div>
				<legend><?php _e('Sample price', 'woo_sample'); ?></legend>
				<div class="options_group">
					<input class="radio" id="sample_price_default" type="radio" value="default" name="sample_price_mode" <?php echo $sample_price_mode == 'default' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_price_default"><?php _e('product default price', 'woo_sample'); ?></label>
				</div>
				<div class="options_group">
					<input class="radio" id="sample_price_free" type="radio" value="free" name="sample_price_mode" <?php echo $sample_price_mode == 'free' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_price_free"><?php _e('free', 'woo_sample'); ?></label>
				</div>
				<div class="options_group">
					<input class="radio" id="sample_price_custom" type="radio" value="custom" name="sample_price_mode" <?php echo $sample_price_mode == 'custom' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_price_custom"><?php _e('custom price', 'woo_sample'); ?></label>
					<p class="form-field sample_price_field clear">
						<label for="sample_price"><?php _e('set sample price', 'woo_sample'); ?></label>
						<input type="number" class="wc_input_price short" name="sample_price" id="sample_price" value="<?php echo $sample_price; ?>" step="any" min="0">
					</p>
				</div>
			</div>
			<?php
		}

      /*
       * build form to the administrator.
       */


      /**
       * updating the database post.
       */
      public function product_save_data($post_id, $post) {

        $sample_enamble = $_POST['sample_enamble'];
        if (empty($sample_enamble)) {
          delete_post_meta($post_id, 'sample_enamble');
        }else{
          update_post_meta($post_id, 'sample_enamble', true);
        }
        //$videos = $_POST['_tab_sample'];
        //$length = count($videos);
        //foreach($videos as $key=>$video){
        //  if(!empty($video)) update_post_meta($post_id, 'wo_di_video_product'.$key, stripslashes($video));
        //  else delete_post_meta($post_id, 'wo_di_video_product'.$key);
        //}
        
      }

		public function product_sample_button() {
			global $post, $product;
			$is_sample = get_post_meta($post->ID, 'sample_enamble');
			if ($is_sample){
			?>
				<?php do_action('woocommerce_before_add_sample_to_cart_form'); ?>
				<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="cart sample" method="post" enctype='multipart/form-data'>
				<?php do_action('woocommerce_before_add_sample_to_cart_button'); ?>
					<div class="single_variation_wrap" style="">      	      
	      	      	<button type="submit" class="single_add_to_cart_button button alt single_add_sample_to_cart_button"><?php echo  __( 'Add Sample to cart', 'woo_sample' ); ?></button>
	      	        <input type="hidden" name="sample" id="sample" value="true"/>
	      	        </div>
				<?php do_action('woocommerce_after_add_sample_to_cart_button'); ?>
				</form>
				<?php do_action('woocommerce_after_add_sample_to_cart_form'); ?>
			<?php
			}
		}
	  
		function enqueue_scripts() {
			global $pagenow, $wp_scripts;
			$plugin_url = untrailingslashit(plugin_dir_url(__FILE__));
			if ( ! is_admin() ) {
				wp_enqueue_script('woocommerce-sample', $plugin_url . '/js/woocommerce-sample.js', array('jquery'), '1.0', true);
			}
			/*
			if (is_admin() && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' || 'edit-tags.php')) {
				// for admin enqueue
			}
			*/
		}
	  
      
    }//end of the class  
  }//end of the if, if the class exists

  /*
   * Instantiate plugin class and add it to the set of globals.
   */
  $woocommerce_sample_tab = new WooCommerce_Sample();

  //agrego el contexto a la url
  function woosample_add_my_context_to_url($url, $type) {
    if (isset($_REQUEST['context'])) {
      $url = add_query_arg('context', $_REQUEST['context'], $url);
    }
    return $url;
  }

  $plugin = plugin_basename( __FILE__ );

} else {//end if,if installed woocommerce
  add_action('admin_notices', 'woosample_tab_error_notice');

  function woosample_tab_error_notice() {
    global $current_screen;
    if ($current_screen->parent_base == 'plugins') {
      echo '<div class="error"><p>' . __('WooCommerce Sample requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="' . admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce') . '" target="_blank">WooCommerce</a> first.','woo_sample') . '</p></div>';
    }
  }
}

 /**
  * Enqueue plugin style-file
  */
  function woosample_add_scripts() {
    // Respects SSL, style-admin.css is relative to the current file
    wp_register_style( 'woosample-styles', plugins_url('css/style-admin.css', __FILE__) );
    wp_register_script( 'woosample-scripts', plugins_url('js/woocommerce-sample.js', __FILE__), array('jquery') );
    wp_enqueue_style( 'woosample-styles' );
    wp_enqueue_script( 'woosample-scripts' );
  }
  add_action( 'admin_enqueue_scripts', 'woosample_add_scripts' );

  /**
  * Set up localization
  */
  function woosample_textdomain() {
    load_plugin_textdomain( 'woosample', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
  }
  add_action('plugins_loaded', 'woosample_textdomain');

?>
