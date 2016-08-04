<?php
/**
 * Plugin Name: WooCommerce Sample
 * Plugin URI: http://www.isikom.net/
 * Description: Include Get Sample Button in products of your online store.
 * Author: Michele Menciassi
 * Author URI: https://plus.google.com/+MicheleMenciassi
 * Version: 0.8.0
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
			//add_action('woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
			//do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
			// Prevent add to cart
			add_filter('woocommerce_add_to_cart_validation', array( $this, 'add_to_cart_validation' ), 40, 4 );
			add_filter('woocommerce_add_cart_item_data', array( $this, 'add_sample_to_cart_item_data' ), 10, 3 );
			add_filter('woocommerce_add_cart_item', array( $this, 'add_sample_to_cart_item' ), 10, 2 );
			add_filter('woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
			add_filter('woocommerce_get_cart_item_from_session', array( $this, 'filter_session'), 10, 3);
			add_filter('woocommerce_cart_item_name', array( $this, 'cart_title'), 10, 3);
			add_filter('woocommerce_cart_widget_product_title', array( $this, 'cart_widget_product_title'), 10, 2);
			add_filter('woocommerce_cart_item_quantity', array( $this, 'cart_item_quantity'), 10, 2);
	
			add_filter('woocommerce_shipping_free_shipping_is_available', array( $this, 'enable_free_shipping'), 40, 1);
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>' ) ){
				add_filter('woocommerce_package_rates', array( $this, 'free_shipping_filter'), 10, 1);
			}else{
				add_filter('woocommerce_available_shipping_methods', array( $this, 'free_shipping_filter'), 10, 1);
			}
			add_action('woocommerce_add_order_item_meta', array($this, 'add_order_item_meta'), 10, 2);
			
			// filter for Minimum/Maximum plugin override overriding
			if (in_array('woocommerce-min-max-quantities/min-max-quantities.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				add_filter('wc_min_max_quantity_minimum_allowed_quantity', array($this, 'minimum_quantity'), 10, 4 );
				add_filter('wc_min_max_quantity_maximum_allowed_quantity', array($this, 'maximum_quantity'), 10, 4 );
				add_filter('wc_min_max_quantity_group_of_quantity', array($this, 'group_of_quantity'), 10, 4 );			
			}

			// filter for Measurement Price Calculator plugin override overriding
			if (in_array('woocommerce-measurement-price-calculator/woocommerce-measurement-price-calculator.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				add_filter('wc_measurement_price_calculator_add_to_cart_validation', array($this, 'measurement_price_calculator_add_to_cart_validation'), 10, 4 );
			}

			// filter for WooCommerce Chained Products plugin override overriding
			if (in_array('woocommerce-chained-products/woocommerce-chained-products.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				add_action( 'wc_after_chained_add_to_cart', array( $this, 'remove_chained_products' ), 20, 6 ); 
			}
			
		}
		
		function remove_chained_products ($chained_parent_id, $quantity, $chained_variation_id, $chained_variation_data, $chained_cart_item_data, $cart_item_key){
			global $woocommerce;
			$cart = $woocommerce->cart->get_cart();
			$main_is_sample = $cart[$cart_item_key]['sample'];
			if ($main_is_sample) {
				$main_product_id = $cart[$cart_item_key]['product_id'];
				if ( !get_post_meta($main_product_id, 'sample_chained_enambled', true) ) {
					foreach ($cart as $cart_key => $cart_item) {
						if ($cart_item['product_id'] == $chained_parent_id) {
							$woocommerce->cart->remove_cart_item($cart_key);
							break;
						}
					}
				}
			}
		}

		function measurement_price_calculator_add_to_cart_validation ($valid, $product_id, $quantity, $measurements){
			global $woocommerce;
			$validation = $valid;
			if (get_post_meta($product_id, 'sample_enamble') && $_REQUEST['sample']){
				$woocommerce->session->set( 'wc_notices', null );
				$validation = true;
			}
			return $validation;
		}

		function add_order_item_meta ($item_id, $values){
			if ($values['sample']){
				woocommerce_add_order_item_meta( $item_id, 'product type', 'sample');
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
      		global $woocommerce;
			if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {
				$check = true;

				foreach ($woocommerce->cart->get_cart() as $cart_item_key => $cart_item){
					if ($cart_item['sample']){
						$sample_shipping_mode = get_post_meta($cart_item['product_id'], 'sample_shipping_mode', true);
						if ($sample_shipping_mode !== 'free'){
							$check = false;
							break;							
						}else{
							// sample is setted - we go on to check all other items in cart
						}
						
					}else{
						$check = false;
						break;
					}
				}

				if ($check === true){
					return true;
				}else{
					return $is_available;
				}
			}else{
				return $is_available;
			}
      }

      function free_shipping_filter( $available_methods )
      {
      	  	foreach ($available_methods as $key => $method) {
      	  		if ($method->method_id == 'free_shipping'){
					$available_methods = array();
					$available_methods['free_shipping:1'] = $method;
					break;      	  			
      	  		}
      	  	}
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
      	      	      $title .= ' [' . __('Sample','woosample') . '] ';
      	      }
      	      return $title;
      }
	  
      function cart_widget_product_title($title, $cart_item){
			if (is_array($cart_item) && $cart_item['sample']){
				$title .= ' [' . __('Sample','woosample') . '] ';
			}
			return $title;
	  }
      
      function filter_session($cart_content, $value, $key){
      	      if ($value['sample']){
      	      	      $cart_content['sample'] = true;
      	      	      $cart_content['unique_key'] = $value['unique_key'];
      	      	      //$cart_content['data']->price = 0;
					  $product_id = $cart_content['product_id'];
					  $sample_price_mode = get_post_meta($product_id, 'sample_price_mode', true) ? get_post_meta($product_id, 'sample_price_mode', true) : 'default';
					  $sample_price = get_post_meta($product_id, 'sample_price', true) ? get_post_meta($product_id, 'sample_price', true) : 0;
					  if ($sample_price_mode === 'custom'){
					  	$cart_content['data']->price = $sample_price;
					  }else if ($sample_price_mode === 'free'){
					  	$cart_content['data']->price = 0;
					  }else{
					  	//default
					  }
      	      }
      	      return $cart_content;
      }
      
      function get_item_data($item_data, $cart_item){
      	      global $cart_item_key;
      	      return $item_data;
      }
      
      function add_sample_to_cart_item_data ($cart_item_data, $product_id, $variation_id){
      	      if (get_post_meta($product_id, 'sample_enamble') && $_REQUEST['sample']){
					$cart_item_data['sample'] = true;
					$cart_item_data['unique_key'] = md5($product_id . 'sample');
      	      }
      	      return $cart_item_data;
      }

	function add_sample_to_cart_item ($cart_item, $cart_item_key){
		if ($cart_item['sample'] === true){
			$cart_item['data']->price = 0;
		}
		return $cart_item;
	}
	  
      /**
       * add_to_cart_validation function.
       *
       * @access public
       * @param mixed $pass
       * @param mixed $product_id
       * @param mixed $quantity
       * @return void
       */
      function add_to_cart_validation( $pass, $product_id, $quantity, $variation_id = 0 ) {
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
					wc_add_notice( __( 'A sample of the same product is already present into your cart', 'woosample' ), 'error' );
					return false;
				}
				if ($cart_item['product_id'] == $product_id){
					wc_add_notice( __( 'You have already added this product on your cart, you can\'t add a sample of the same item', 'woosample' ), 'error' );
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
        echo "<li><a class='added_sample' href=\"#sample_tab\">" . __('Sample','woosample') . "</a></li>";
      }

		/**
		 * build the panel for the administrator.
		 */
		public function product_write_panel() {
        	global $post;
			$sample_enable = get_post_meta($post->ID, 'sample_enamble', true) ? get_post_meta($post->ID, 'sample_enamble', true) : false;
			if (in_array('woocommerce-chained-products/woocommerce-chained-products.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				$has_chained_products = get_post_meta($post->ID, '_chained_product_detail', true );
			} else {
				$has_chained_products = false;
			}
			$sample_chained_enambled = get_post_meta($post->ID, 'sample_chained_enambled', true) ? get_post_meta($post->ID, 'sample_chained_enambled', true) : false;
			$sample_shipping_mode = get_post_meta($post->ID, 'sample_shipping_mode', true) ? get_post_meta($post->ID, 'sample_shipping_mode', true) : 'default';
			$sample_shipping = get_post_meta($post->ID, 'sample_shiping', true) ? get_post_meta($post->ID, 'sample_shipping', true) : 0;
			$sample_price_mode = get_post_meta($post->ID, 'sample_price_mode', true) ? get_post_meta($post->ID, 'sample_price_mode', true) : 'default';
			$sample_price = get_post_meta($post->ID, 'sample_price', true) ? get_post_meta($post->ID, 'sample_price', true) : 0;
			?>
			<div id="sample_tab" class="panel woocommerce_options_panel">
				<p class="form-field sample_enamble_field ">
					<label for="sample_enamble"><?php _e('Enable sample', 'woosample');?></label>
					<input type="checkbox" class="checkbox" name="sample_enamble" id="sample_enamble" value="yes" <?php echo $sample_enable ? 'checked="checked"' : ''; ?>> <span class="description"><?php _e('Enable or disable sample option for this item.', 'woosample'); ?></span>
				</p>
			<?php if ($has_chained_products) { ?>
				<p class="form-field sample_chained_enambled_field ">
					<label for="sample_chained_enambled"><?php _e('Add chained products', 'woosample');?></label>
					<input type="checkbox" class="checkbox" name="sample_chained_enambled" id="sample_chained_enambled" value="yes" <?php echo $sample_chained_enambled ? 'checked="checked"' : ''; ?>> <span class="description"><?php _e('Add or not chained products as sample.', 'woosample'); ?></span>
				</p>
			<?php } ?>
				<legend><?php _e('Sample Shipping', 'woosample'); ?></legend>
				<div class="options_group">
					<input class="radio" id="sample_shipping_default" type="radio" value="default" name="sample_shipping_mode" <?php echo $sample_shipping_mode == 'default' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_shipping_default"><?php _e('use default product shipping methods', 'woosample'); ?></label>
				</div>
				<div class="options_group">
					<input class="radio" id="sample_shipping_free" type="radio" value="free" name="sample_shipping_mode" <?php echo $sample_shipping_mode == 'free' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_shipping_free"><?php _e('free shipping for sample', 'woosample'); ?></label>
				</div>
				<!--
				<div class="options_group">
					<input class="radio" id="sample_shipping_custom" type="radio" value="custom" name="sample_shipping_mode" <?php echo $sample_shipping_mode == 'custom' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_shipping_custom"><?php _e('custom fee shipping', 'woosample'); ?></label>
					<p class="form-field sample_shipping_field clear">
						<label for="sample_shipping"><?php _e('set shipping fee', 'woosample'); ?></label>
						<input type="number" class="wc_input_price short" name="sample_shipping" id="sample_shipping" value="<?php echo $sample_shipping; ?>" step="any" min="0">
					</p>
				</div>
				-->
				<legend><?php _e('Sample price', 'woosample'); ?></legend>
				<div class="options_group">
					<input class="radio" id="sample_price_default" type="radio" value="default" name="sample_price_mode" <?php echo $sample_price_mode == 'default' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_price_default"><?php _e('product default price', 'woosample'); ?></label>
				</div>
				<div class="options_group">
					<input class="radio" id="sample_price_free" type="radio" value="free" name="sample_price_mode" <?php echo $sample_price_mode == 'free' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_price_free"><?php _e('free', 'woosample'); ?></label>
				</div>
				<div class="options_group">
					<input class="radio" id="sample_price_custom" type="radio" value="custom" name="sample_price_mode" <?php echo $sample_price_mode == 'custom' ? 'checked="checked"' : ''; ?>>
					<label class="radio" for="sample_price_custom"><?php _e('custom price', 'woosample'); ?></label>
					<p class="form-field sample_price_field clear">
						<label for="sample_price"><?php _e('set sample price', 'woosample'); ?></label>
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
        $sample_chained_enambled = $_POST['sample_chained_enambled'];
        if (empty($sample_chained_enambled)) {
          delete_post_meta($post_id, 'sample_chained_enambled');
        }else{
          update_post_meta($post_id, 'sample_chained_enambled', true);
        }		
		$sample_price_mode = $_POST['sample_price_mode'];
        update_post_meta($post_id, 'sample_price_mode', $sample_price_mode);
		$sample_price = $_POST['sample_price'];
        update_post_meta($post_id, 'sample_price', $sample_price);
		$sample_shipping_mode = $_POST['sample_shipping_mode'];
        update_post_meta($post_id, 'sample_shipping_mode', $sample_shipping_mode);
		$sample_shipping = $_POST['sample_shipping'];
        update_post_meta($post_id, 'sample_shipping', $sample_shipping);
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
					<?php $btnclass = apply_filters('sample_button_class', "single_add_to_cart_button button alt single_add_sample_to_cart_button btn btn-default"); ?>
	      	      	<button type="submit" class="<?php echo $btnclass; ?>"><?php echo  __( 'Add Sample to cart', 'woosample' ); ?></button>
	      	        <input type="hidden" name="sample" id="sample" value="true"/>
	      	        <input type="hidden" name="add-to-cart" id="sample_add_to_cart" value="<?php echo $product->id; ?>">
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

  $plugin = plugin_basename( __FILE__ );

} else {//end if,if installed woocommerce
  add_action('admin_notices', 'woosample_tab_error_notice');

  function woosample_tab_error_notice() {
    global $current_screen;
    if ($current_screen->parent_base == 'plugins') {
      echo '<div class="error"><p>' . sprintf(__('WooCommerce Sample requires <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="%1$s" target="_blank">WooCommerce</a> first.','woosample'), admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce') ) . '</p></div>';
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
    load_plugin_textdomain( 'woosample', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
  }
  add_action('plugins_loaded', 'woosample_textdomain');

?>
