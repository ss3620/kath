<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('woocommerce_cancelled_order','ossm_sendle_order_cancelled');

function ossm_sendle_order_cancelled($order_id=0) {

    ossm_logActions(" Order cancelled invoked ");
    $order = new WC_Order( $order_id );
    $sendleOrderId = get_post_meta($order_id,'sendle_order_id',true);
    if ( $sendleOrderId === '' ) {
        if ( $order ) {
            $sendleOrderId = $order->get_meta( 'sendle_order_id', true );
        }
    }
    $sendle_setting  = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
    $api_id = $sendle_setting['api_id'];
    $api_key = $sendle_setting['api_key'];
    $api_mode = $sendle_setting['mode'];
    if($api_mode == "live"){ $apiurl = "https://api.sendle.com"; }
    else{ $apiurl = SENDLE_JOOVII_API_SANDBOX_URL; }
    ossm_logActions (" Order ID : ". $order_id ." ---- Sendel OrderId : ". $sendleOrderId ." ---Url : ". $url ." ----set to CANCELLED -". $order->status." ");
    if($order->status === 'cancelled' ) {
        $args = array(
                'method'	  => 'GET',
                'timeout'     => 30,
                'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
                'headers' 	  => array('Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_key ),
                                        'Content-Type'=> 'application/json',
                                        'Accept' =>'application/json',
                                        '-X'=> 'DELETE'));
        $content = wp_remote_get( $url, $args );
        $response = wp_remote_retrieve_body( $content );
        ossm_logActions(" Order ID : ". $order_id ." ---- Sendel OrderId : ". $sendleOrderId ." ---Url :". $url ."  ---- has been CANCELLED ");
    }

    if(isset($response['messages'])){
      if(trim($response['messages']) != ''){
          ossm_logActions($response['messages']);
      }
    }
    if(isset($response['cancellation_message'])){
      if(trim($response['cancellation_message']) != ''){
          ossm_logActions('Sendle Order has been successfully Cancelled.');
          ossm_logActions($response['cancellation_message']);
      }
    }
}

function ossm_cancel_sendle(){

    // AuthZ: Only allow privileged users
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'You are not allowed to perform this action.', 'official-sendle-shipping-method' ), 403 );
    }

    // CSRF protection (nonce)
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field( wp_unslash($_REQUEST['_wpnonce']) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'ossm_cancel_sendle' ) ) {
        wp_die( esc_html__( 'Security check failed.', 'official-sendle-shipping-method' ), 403 );
    }

    echo '<div class="wrap"><h2>Cancel Sendle Order</h2></div>';

    // Safe input handling
    $sendleOrderId = isset($_GET['sendle_order_id']) ? sanitize_text_field( wp_unslash($_GET['sendle_order_id']) ) : '';
    $order_id      = isset($_GET['oid']) ? absint( wp_unslash($_GET['oid']) ) : 0;

    if ( empty($sendleOrderId) || empty($order_id) ) {
        wp_die( esc_html__( 'Missing required parameters.', 'official-sendle-shipping-method' ), 400 );
    }

    $order = new WC_Order( $order_id );
    $sendle_setting  = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
    $api_id = $sendle_setting['api_id'];
    $api_key = $sendle_setting['api_key'];
    $api_mode = $sendle_setting['mode'];
    if($api_mode == "live"){ $apiurl = "https://api.sendle.com"; }
    else{   $apiurl = SENDLE_JOOVII_API_SANDBOX_URL; }

    $url = $apiurl."/api/orders/".$sendleOrderId;
    $args = array(
            'method'            => 'DELETE',
            'timeout'     => 30,
            'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
            'headers'       => array('Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_key ),
                                    'Content-Type'=> 'application/json',
                                    'Accept' =>'application/json',
                                    '-X' => 'DELETE'));
    $content = wp_remote_get( $url, $args );
    $return = wp_remote_retrieve_body( $content );
    $response = json_decode($return, true);

    if(isset($response['messages'])){
        if(trim($response['messages']) != ''){
            ossm_logActions("Order ID : ". $order_id ." -- ".$response['messages']);
        }
    }
    if(isset($response['cancellation_message'])){
        if(trim($response['cancellation_message']) != ''){
            ossm_logActions("Order ID : ". $order_id ." -- ".$response['cancellation_message']);
        }
    }

    echo '<table class="widefat"><thead><tr><th><strong>Message</strong></th></tr></thead><tbody>';

    // ✅ Escape output (prevents XSS warnings too)
    if(isset($response['messages'])){
        if(trim($response['messages']) != ''){
            echo '<tr><td>' . esc_html( $response['messages'] ) . '</td></tr>';
        }
    }
    if(isset($response['cancellation_message'])){
        if(trim($response['cancellation_message']) != ''){
            echo '<tr><td>' . esc_html( 'Sendle Order #' . $order_id . ' has been successfully Cancelled. ' . $response['cancellation_message'] ) . '</td></tr>';
        }
    }

    echo '</tbody></table>';
}


?>
