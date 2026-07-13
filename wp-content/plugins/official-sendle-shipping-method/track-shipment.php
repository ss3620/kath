<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ossm_track_shipment(){

    // Authorization
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'You are not allowed to perform this action.', 'official-sendle-shipping-method' ), 403 );
    }

    // CSRF protection
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field( wp_unslash($_REQUEST['_wpnonce']) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'ossm_track_shipment' ) ) {
        wp_die( esc_html__( 'Security check failed.', 'official-sendle-shipping-method' ), 403 );
    }
?>
    <div class="wrap">
    <h2>Tracking Information</h2>
    <?php
		//VALIDATION check
        $sendle_reference = isset($_GET['sendle_reference']) ? sanitize_text_field( wp_unslash($_GET['sendle_reference']) ) : '';
        $order_id         = isset($_GET['oid']) ? absint( wp_unslash($_GET['oid']) ) : 0;

        if ( empty($sendle_reference) || empty($order_id) ) {
            wp_die( esc_html__( 'Missing required parameters.', 'official-sendle-shipping-method' ), 400 );
        }

        $tracking_url = get_post_meta( $order_id, 'sendle_tracking_url', true );

        if ( $tracking_url === '' ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $tracking_url = (string) $order->get_meta( 'sendle_tracking_url', true );
            }
        }


        $sendle_reference= sanitize_text_field($_GET['sendle_reference']);
        $api_id = get_option('sendle_shipping_api_id');
        $api_key = get_option('sendle_shipping_api_key');
        $api_mode = get_option('sendle_shipping_api_mode');
        if($api_mode == "live"){ $apiurl = "https://api.sendle.com"; }
        else{ $apiurl = SENDLE_JOOVII_API_SANDBOX_URL; }
        $urlParam = $apiurl."/api/tracking/".$sendle_reference;


		$args = array(
						'method'			=> 'GET',
						'timeout'     => 30,
						'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
						'headers' 		=> array( 'Content-Type'=> 'application/json',
																		'Accept' =>'application/json')
						);

		$content = wp_remote_get( $urlParam, $args );
		$return = wp_remote_retrieve_body( $content );
		$response = json_decode($return, true);

		?>
		<table class="widefat" width="100%" border="0" cellspacing="5" cellpadding="5">
        <thead>
          <tr>
            <th><strong>Tracking Number</strong></th>
            <th><strong>Info</strong></th>
          </tr>
      </thead>
  	  <tbody>
      		<tr>
            	<td><?php echo esc_html( $sendle_reference ); ?></td>

              <td>
            	<?php
            	if(isset($response['error']))
                {
             		if($response['error']!="")
                    { 
                        echo esc_html( $response['error'] ) . '<br />' . esc_html( $response['error_description'] ) . '<br />';
                    }
            	}
            	
                if ( ! empty( $response['state'] ) ) 
                {
                    echo esc_html( $response['state'] ) . '<br />' . esc_html( $response['status']['description'] ) . '<br />';
                }

            	if(!empty($response['tracking_events']))
                {		
                ?>
                <table class="widefat striped">
              	<thead>
                  <tr>
                      <th><strong>Event</strong></th>
                      <th><strong>Time</strong></th>
                      <th><strong>Description</strong></th>
                  </tr>
                  </thead>
                  <tbody>
		              <?php
                    foreach(array_reverse($response['tracking_events']) as $events){
                        echo "<tr>";
                        echo "<td>". esc_html($events['event_type'])."</td>";
    										$scan_time = explode("T",$events['scan_time']);
    										$event_date = $scan_time[0];
    										$event_time = substr($scan_time[1], 0, -1);
    										echo "<td>". esc_html($event_date) . " @ " . esc_html($event_time) . "</td>";
    										echo "<td>". esc_html($events['description'])."</td>";
                        echo "</tr>";
                    }
                    ?>
          	</tbody>
          	</table>
          <?php
					}else{
							//echo "No info available";
					}
					echo '<br/><a href="' . esc_url( $tracking_url ) . '" target="_blank">' .
                   esc_html__( 'Goto Sendle for more info', 'official-sendle-shipping-method' ) .
                   '</a>';

					?></td>
            </tr>
      </tbody>
      </table>
    </div>
<?php } ?>
