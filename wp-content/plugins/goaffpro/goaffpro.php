<?php
/**
 * @package GoAffPro
 * @version 2.7.10
 * @copyright Goaffpro
 * @licence GPL-2.0
 */
/*
Plugin Name: Goaffpro Affiliate Marketing
Plugin URI: https://goaffpro.com/#merchants
Description: This plugin connects your goaffpro account to your store. Log in to your <a target="_blank" href="https://goaffpro.com">goaffpro account</a> to add this site to your profile
Author: Goaffpro
Version: 2.7.11
Author URI: https://goaffpro.com/
*/

$goaffpro_plugin_version = "2.7.10";
$goaffpro_plugin_version_code = 34;

$goaffpro_token_key = 'goaffpro_public_token';
if(is_multisite()){
	$goaffpro_token_key .= "_".get_current_blog_id();
}


/**
 * Register activation hook and save a 12 character public token in database
 * */
register_activation_hook( __FILE__, 'goaffpro_on_activated' );
function goaffpro_on_activated(){
	global $goaffpro_token_key;
	$token = wp_generate_password(12, false);
	add_option($goaffpro_token_key, $token);
	return $token;
}

/**
 * Helper function to return the generated public token
 */
function get_goaffpro_public_token(){
	global $goaffpro_token_key;
	$token = get_option($goaffpro_token_key);
	/**
	 *  on multi-sites, plugin activation hook is not fired on individual sites
	 *  so create the option at load time
	 */
	if($token) {
		return $token;
	}else{
		return goaffpro_on_activated();
	}
}



/**
 * Add goaffpro client sdk to the website's footer
 */
add_action('wp_footer', 'goaffpro_client_footer');

function goaffpro_client_footer(){
	$javascript_url = 'https://api.goaffpro.com/loader.js?shop='.get_goaffpro_public_token();
	wp_enqueue_script("goaffpro_ref_tracking", $javascript_url);
}

/**
 * Add goaffpro client sdk `thank you` script in woocommerce thank you page
 */
add_action('woocommerce_thankyou', 'goaffpro_checkout_footer', 10, 1);

function goaffpro_checkout_footer($order_id){
	wp_enqueue_script( 'goaffpro_order_callback' );
	try{
    	// retrieve the order representation for order_id
	    $order = goaffpro_get_transformed_order($order_id);
	}catch(Exception $e){
        $order = array('id'=>$order_id);
    }
	// The script must be added to the footer to ensure that the order data is present before the call is made
	$javascript_url = 'https://api.goaffpro.com/loader.js?shop='.get_goaffpro_public_token();
	wp_enqueue_script("goaffpro_ref_tracking", $javascript_url);
    wp_add_inline_script("goaffpro_ref_tracking", "window.goaffpro_order = ".json_encode($order),'before');
	$checkout_widget_url = 'https://api.goaffpro.com/checkout_widget.js?shop='.get_goaffpro_public_token().'&woo_order_id='.$order_id;
	wp_enqueue_script("goaffpro_postcheckout_popup", $checkout_widget_url, array(), false, true);
    try{
        $attr = array(
          'publicToken' => get_goaffpro_public_token(),
          'order_id'=>$order_id
        );
        wp_remote_post("https://api.goaffpro.com/woocommerce/internal_hook", $attr);
    }catch(Exception $e){

    }
}


/**
 * Add goaffpro referral cookie in woocommerce order metadata if referral cookie is available.
 * This helps in tagging the orders received with the unique ID of the affiliate who sent the customer
 */
add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted ) {
    if(!isset($_COOKIE['ref'])) {
		return;
	}
	$referral_code = sanitize_text_field($_COOKIE['ref']);
	if($referral_code){
		update_post_meta($order_id, 'ref', $referral_code);
	}
	$visit_id = sanitize_text_field($_COOKIE['gfp_v_id']);
	if($visit_id){
	    update_post_meta($order_id,'gfp_v_id', $visit_id);
	}
} , 10, 2);

/**
 * Adds a goaffpro endpoint to the REST API to verify the plugin integration with the goaffpro service
 */
add_action( 'rest_api_init', 'add_goaffpro_api_route');

function add_goaffpro_api_route(){
    register_rest_route( 'goaffpro', '/config', array(
        'methods' => 'GET',
		'callback' => 'get_goaffpro_config',
        'permission_callback' => '__return_true'
	));
    register_rest_route('goaffpro','/public_token', array(
        'methods' => 'GET',
        'callback' => 'get_goaffpro_public_token',
        'permission_callback'=>'__return_true'
    ));
    /*
	register_rest_route('goaffpro','/test', array(
	    'methods' => 'GET',
	    'callback' => 'get_wc_order',
	    'permission_callback'=>'__return_true'
	));
	*/
}

/**
 * Returns a goaffpro_order representation of the woocommerce_order object
*/
function goaffpro_get_transformed_order($order_id){
    $order = wc_get_order($order_id);
    $line_items = array();
    foreach ($order->get_items() as $item_id => $item ) {
        $price = $order->get_item_subtotal($item);
        $quantity = $item->get_quantity();
        $total = $order->get_line_total($item);
        $tax = $order->get_line_tax($item);
        $subtotal = $order->get_line_subtotal($item);
        $discount = $subtotal - $total + $tax;
        $data = $item->get_data();
       array_push($line_items, array(
        'id'=>$item_id,
        'name'=>$item->get_name(),
        'quantity'=>$quantity,
        'discount'=>$discount,
        'total'=>$total,
        'tax'=>$tax,
        'price'=>$price,
        'product_id'=>$data['product_id'],
        'variation_id'=>$data['variation_id'],
        // 'data'=>$data,
       ));
    }
    return array(
              'id'=>$order->get_id(),
              'number'=>$order->get_order_number(),
             // 'data'=>$order->get_data(),
              'total'=>$order->get_total(),
              'subtotal'=>$order->get_subtotal(),
              'discount'=> $order->get_discount_total(),
              'tax'=>$order->get_total_tax(),
              'shipping'=> $order->get_shipping_total(),
              'currency'=> $order->get_currency(),
              'customer'=>$order->get_address(),
              'coupons'=>$order->get_coupon_codes(),
              'order_status_url'=>$order->get_checkout_order_received_url(),
              'status'=> $order->get_status(),
              'line_items'=>$line_items,
        );
}
/*
function get_wc_order(){
    return goaffpro_get_transformed_order('66');
}
*/

function get_goaffpro_config(){
    global $goaffpro_plugin_version;
    global $goaffpro_plugin_version_code;
	$data = array(
		'goaffpro_public_token'=> get_goaffpro_public_token(),
		'store_name'=> get_option('blogname'),
		'plugin_version' => $goaffpro_plugin_version,
		'plugin_version_code' => $goaffpro_plugin_version_code,
	);
	return $data;
}



function goaffpro_apply_discount_to_cart() {
try{
    if(isset($_COOKIE['discount_code'])){
       	$coupon_code = sanitize_text_field($_COOKIE['discount_code']);
     }
      if(empty($coupon_code)){
         $coupon_code = WC()->session->get( 'coupon_code');
      }
      if ( ! empty( $coupon_code ) && ! WC()->cart->has_discount( $coupon_code ) ){
          WC()->cart->add_discount( $coupon_code ); // apply the coupon discount
          WC()->session->__unset( 'coupon_code' ); // remove coupon code from session
      }
    }catch(Exception $e){

    }
}
add_action( 'woocommerce_before_cart_table', 'goaffpro_apply_discount_to_cart');

function goaffpro_get_custom_coupon_code_to_session() {
try{
    // retrieve coupon code from URL
    if( isset( $_GET[ 'coupon_code' ] ) ) {
        // Ensure that customer session is started
        if( !WC()->session->has_session() )
            WC()->session->set_customer_session_cookie(true);
        // Check and register coupon code in a custom session variable
        $coupon_code = WC()->session->get( 'coupon_code' );
        if( empty( $coupon_code ) && isset( $_GET[ 'coupon_code' ] ) ) {
            $coupon_code = esc_attr( $_GET[ 'coupon_code' ] );
            WC()->session->set( 'coupon_code', $coupon_code ); // Set the coupon code in session
        }
    }
    }catch(Exception $e){

    }
}
add_action( 'init', 'goaffpro_get_custom_coupon_code_to_session' );

function goaffpro_get_product_variation_attributes($variation_id){
	$variation = new WC_Product_Variation($variation_id);
	$attributes = array();
	foreach ( $variation->get_variation_attributes() as $attribute_name => $attribute ) {
		$attributes[wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ), $variation )] = $attribute;
    }
	return $attributes;
}
/*
This function adds support for query parameters to build a checkout dynamically
Supported parameters
gfp_checkout = product_id.quantity.variation_id|product_id.quantity.variation_id
coupon_code = test
gfp_new_cart = 1
*/
function goaffpro_set_checkout_from_url() {
    if(!isset( $_GET['gfp_checkout'] ) ) {
        return;
    }
	$cart_type = $_GET['gfp_new_cart'];
	if(isset($cart_type)){
		WC()->cart->empty_cart();
	}
    $products = $_GET['gfp_checkout'];
	$parts = explode("|",$products);
	foreach($parts as $product){
       $x = explode(".", trim($product));
	   $product_id = trim($x[0]);
	   $quantity = isset($x[1]) ? trim($x[1]) : 1;
	   $variation_id = isset($x[2]) ? trim($x[2]) : null;
		$variation_attributes = isset($variation_id) ? goaffpro_get_product_variation_attributes($variation_id) : null;
       if(!empty($product_id)){
          WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation_attributes );
       }
	}
	$coupon = $_GET['coupon_code'];
	if(isset($coupon)){
		 WC()->cart-> apply_coupon( $coupon );
	}
}

add_action( 'template_redirect', 'goaffpro_set_checkout_from_url');


add_filter('woocommerce_rest_prepare_product_object', 'goaffpro_add_variation_data_to_rest_api', 20, 3);
add_filter('woocommerce_rest_prepare_product_variation_object', 'goaffpro_add_variation_data_to_rest_api', 20, 3);

function goaffpro_add_variation_data_to_rest_api($response, $object, $request) {
    $variations = isset($response->data['variations']) ? $response->data['variations'] : null;
    $variations_res = array();
    $variations_array = array();
    if (!empty($variations) && is_array($variations)) {
        foreach ($variations as $variation) {
            $variation_id = $variation;
            $variation = new WC_Product_Variation($variation_id);
            $variations_res['id'] = $variation_id;
            $variations_res['on_sale'] = $variation->is_on_sale();
            $variations_res['regular_price'] = (float)$variation->get_regular_price();
            $variations_res['sale_price'] = (float)$variation->get_sale_price();
            $variations_res['sku'] = $variation->get_sku();
            $variations_res['quantity'] = $variation->get_stock_quantity();
            if ($variations_res['quantity'] == null) {
                $variations_res['quantity'] = '';
            }
            $variations_res['stock'] = $variation->get_stock_quantity();

            $attributes = array();
            // variation attributes
            foreach ( $variation->get_variation_attributes() as $attribute_name => $attribute ) {
                // taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
                $attributes[] = array(
                    'name'   => wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ), $variation ),
                    'slug'   => str_replace( 'attribute_', '', wc_attribute_taxonomy_slug( $attribute_name ) ),
                    'option' => $attribute,
                );
            }

            $variations_res['attributes'] = $attributes;
            $variations_array[] = $variations_res;
        }
    }
    $response->data['product_variations'] = $variations_array;

    return $response;
}

?>
