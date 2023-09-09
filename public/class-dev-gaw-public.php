<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/kunal1400
 * @since      1.0.0
 *
 * @package    Dev_Gaw
 * @subpackage Dev_Gaw/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Dev_Gaw
 * @subpackage Dev_Gaw/public
 * @author     Kunal Malviya <lucky.kunalmalviya@gmail.com>
 */
class Dev_Gaw_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		global $wpdb;		
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->userDesignTable = $wpdb->prefix.'user_design';
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Dev_Gaw_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Dev_Gaw_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dev-gaw-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dev-gaw-public.js', array( 'jquery' ), $this->version, false );
	}

	public function register_rest_routes() {
		// remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		// add_filter( 'rest_pre_serve_request', array($this, 'initCors'));
		register_rest_route('gaw/v1', '/add-to-cart', array(
			'methods' => 'POST',
			'callback' => array($this, 'gaw_add_to_cart_callback'),
			'permission_callback' => '__return_true'
		));
		register_rest_route('gaw/v1', '/products-with-variations', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_products_with_variations'),
		));
	}

	function initCors( $value ) {
		$origin = get_http_origin();
		$allowed_origins = [ 'site1.example.com', 'site2.example.com', 'localhost:3000' ];
	  
		if ( $origin && in_array( $origin, $allowed_origins ) ) {
		  header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
		  header( 'Access-Control-Allow-Methods: *' );
		  header( 'Access-Control-Allow-Credentials: true' );
		}
	  
		return $value;
	}

	public function gaw_add_to_cart_callback($request) {
		global $wpdb;
		$product_id = $request->get_param('product_id');
		$variation_id = $request->get_param('variation_id');
		$designName = $request->get_param('design_name');
		$meta_data_array = $request->get_param('meta_data');

		// Check if the product ID is provided
		if (empty($product_id)) {
			return new WP_Error('missing_product_id', __('Product ID is missing.', 'my-rest-api'), array('status' => 400));
		}

		if (empty($designName)) {
			return new WP_Error('missing_design_name', __('Design Name is missing.', 'my-rest-api'), array('status' => 400));
		}

		// Check if the product ID is provided
		if (!$variation_id) {
			$variation_id = 0;
		}

		// Get the product object
		$product = wc_get_product($product_id);

		// Check if the product exists and is purchasable
		if (!$product || !$product->is_purchasable()) {
			// Product not found or not purchasable
			return new WP_Error('invalid_product', __('Invalid product ID or product is not purchasable.', 'my-rest-api'), array('status' => 400));
		}

		$insertId = $wpdb->insert($this->userDesignTable, array(
			'productId' => $product_id,
			'variantId' => $variation_id,
			'designName' => $designName,
			'designedData' => json_encode($meta_data_array, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
			'slug' => $this->generateUUID(),
			'userIp' => $_SERVER['REMOTE_ADDR']
		));

		if(!$insertId) {
			return new WP_Error('db_error', __('Something went wrong.', 'my-rest-api'), array('status' => 400));
		} else {
			$lastInsertedId = $wpdb->insert_id;
			$wpdb->update(
				$this->userDesignTable, 
				array( 'slug' => $lastInsertedId.$this->generateUUID() ), 
				array('id' => $lastInsertedId)
			);
			return $wpdb->get_row("SELECT * FROM $this->userDesignTable WHERE id = $lastInsertedId", ARRAY_A);
		}
	}

	public function get_products_with_variations($request) {
		$slug = $request['slug'];

		$products_with_variations = array();

		// Fetch products by slug from WooCommerce (modify as needed)
		$product = wc_get_product(get_page_by_path($slug, OBJECT, 'product'));

		if ($product) {
			$product_with_variations = array(
				'id' => $product->get_id(),
				'name' => $product->get_name(),
				'slug' => $product->get_slug(),
				'permalink' => $product->get_permalink(),
				'type' => $product->get_type(),
				'images' => $product->get_gallery_image_ids(),
				'categories' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names')),
				'currency' => get_woocommerce_currency(),
				'currency_symbol' => get_woocommerce_currency_symbol(),
				'description' => $product->get_description(),
				'short_description' => $product->get_short_description(),
				'variations' => array(),
			);

			if ($product->get_type() === 'variable') {
				$variations = $product->get_available_variations();

				foreach ($variations as $variation) {
					$variation_attributes = $variation['attributes'];
					$variationAttributes = array();
					foreach ($variation_attributes as $attribute_name => $attribute_value) {
						$attribute_name = wc_attribute_label(str_replace('attribute_', '', $attribute_name));
	
						$variationAttributes[] = array('name'=>$attribute_name, 'option' => $attribute_value);
					}
					$variation['attributes'] = $variationAttributes;
					$product_with_variations['variations'][] = $variation;
				}
			}
	
			$products_with_variations[] = $product_with_variations;
		}

		return $products_with_variations;
	}

	public function generateUUID() {
		$uuid36 = wp_generate_uuid4();
		$uuid32 = str_replace( '-', '', $uuid36 );
		return $uuid32;
	}

	public function init_actions() {
		if(!empty($_GET['cart_key'])) {
			if ( ! WC()->session->has_session() ) {
                WC()->session->set_customer_session_cookie( true );
            }
			
			$cart_data = $this->getCartDataByKey($_GET['cart_key']);

			$variantId = $cart_data['variantId'];
			$product_id = $cart_data['productId'];
			$quantity = 1;
			$designData = json_decode($cart_data['designedData'], true);

			$metaDataForCart = array();
			if(count($designData) > 0) {
				foreach($designData as $key => $val) {
					$metaDataForCart[$val['key']] = json_encode($val['value'], JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);					
				}
			}			
			
			$res = WC()->cart->add_to_cart( $product_id, $quantity, $variantId, $metaDataForCart );
			if($res) {
				wp_redirect( wc_get_cart_url() );
				exit;
			} else {
				echo 'Something went wrong';
			}
		}
	}

	function getCartDataByKey($cart_key) {
		global $wpdb;
		return $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}user_design WHERE slug = '$cart_key'", ARRAY_A);
	}

}
