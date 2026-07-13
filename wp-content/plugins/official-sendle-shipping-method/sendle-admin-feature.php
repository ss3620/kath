<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'load-post.php', 'ossm_sendle_meta_boxes_setup' );
add_action( 'load-post-new.php', 'ossm_sendle_meta_boxes_setup' );

function ossm_sendle_meta_boxes_setup(){
	$sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
	//if($sendle_setting['orderconfig_lineEnable'] == 'yes'){
		add_action( 'add_meta_boxes', 'ossm_sendle_meta_boxes' );
	//}
}

add_action( 'add_meta_boxes', 'ossm_sendle_meta_boxes' );

function ossm_sendle_meta_boxes(){
		if (ossm_getAssignPermission()){
			add_meta_box(
				'woocommerce-track-shipment',
				__( 'Sendle Shipment Options', 'official-sendle-shipping-method' ),
				'ossm_sendle_shipment_options',
				'shop_order',
				'side',
				'default'
			);
			
			// For new WOoCommerce
			add_meta_box(
				'woocommerce-track-shipment',
				__( 'Sendle Shipment Options', 'official-sendle-shipping-method' ),
				'ossm_sendle_shipment_options',
				'woocommerce_page_wc-orders',
				'side',
				'high'
			);
		}
}


function ossm_sendle_shipment_options()
{

  if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
      return;
  }

  global $woocommerce, $post;
    
	$order_id = "";
	
	if( $post )
	{
		// LOAD ORder
		$order = new WC_Order($post->ID);
		//$order_number = trim(str_replace('#', '', $order->get_order_number()));
		$order_id = trim($post->ID);
	}
	
	if ( empty( $order_id ) && isset( $_GET['id'] ) ) {
	    $order_id = absint( wp_unslash( $_GET['id'] ) );

	    // LOAD Order
  		$order    = new WC_Order( $order_id );
	}

	$sendle_reference = get_post_meta($order_id,'sendle_reference',true);
	$sendle_order_id = get_post_meta($order_id,'sendle_order_id',true);

	// Fallback to WooCommerce order meta (HPOS-safe)
	if ( $sendle_reference === '' || $sendle_order_id === '' ) {

	    if ( $order ) {

	        if ( $sendle_reference === '' ) {
	            $sendle_reference = $order->get_meta( 'sendle_reference', true );
	        }

	        if ( $sendle_order_id === '' ) {
	            $sendle_order_id = $order->get_meta( 'sendle_order_id', true );
	        }
	    }
	}

	$sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
	$book_shipment_on = $sendle_setting['book_shipment_on'];
	
	$shipping_method = @array_shift($order->get_shipping_methods());
	$shipping_method_name = explode("-",$shipping_method['method_id']);
	$shipping_method_name2 = explode(":",$shipping_method['method_id']);

	$pickup_country 		 = $sendle_setting['pickup_country'];
	$receiver_country 	 = get_post_meta($order_id,"_shipping_country",true);
  
  if ( $receiver_country === '' ) {
	    if ( $order ) {
	        $receiver_country = $order->get_shipping_country();
	    }
	} 
	
	$satchel_booking = $sendle_setting['satchel_booking'];
	//print_r($shipping_method);
	//print_r($shipping_method_name);
	//print_r($shipping_method_name2);
	$weight = 0;
	$maxWeight = 0;
	$volume = 0;
	$maxVolume = 0;
	if($sendle_order_id == ''){
			$sendlePost = 0;
			$items = $order->get_items();
			$weight = 0;
			$pv = 0;
			foreach ( $items as $item ) {
				if ( $item['product_id'] > 0 ) {
					$product_id = $item['variation_id'];
					if(trim($product_id) == '' || trim($product_id) == '0'){
						// for variation item
						$product_id = $item['product_id'];
						$_product = wc_get_product($product_id);
						if ( ! $_product->is_virtual() ) {
							if($_product->get_weight() > 0){
								$weight += (float)$_product->get_weight() * $item['qty'];
							}
						}
						if(trim($sendle_setting['volume_param']) == 'yes'){
							if($_product->get_length() > 0 && $_product->get_width() >0 && $_product->get_height()> 0){
								$pv = (ossm_getDimension($_product->get_length(),$pickup_country) * ossm_getDimension($_product->get_width(),$pickup_country) * ossm_getDimension($_product->get_height(),$pickup_country));
								if($pv > 0){
									$volume += (float)$pv * $item['qty'];
								}
							}
						}
					}else{
						// for root item [which has no variation products]
						$variation_id = $item['variation_id'];
						$_product = wc_get_product($product_id);
						$_product_variation = wc_get_product($variation_id);
						if ( ! $_product->is_virtual() ) {
							if($_product->get_weight() > 0){
								$weight += (float)$_product->get_weight() * $item['qty'];
						  }else{
								$weight += (float)$_product_variation->get_weight() * $item['qty'];
							}

							if(trim($sendle_setting['volume_param']) == 'yes'){
								if($_product->get_length() > 0){
									$pv = (ossm_getDimension($_product->get_length(),$pickup_country) * ossm_getDimension($_product->get_width(),$pickup_country) * ossm_getDimension($_product->get_height(),$pickup_country));
									$volume += (float)$pv * $item['qty'];
								}else{
									// if varition item has no volume then get the volume of root item
									$pv = (ossm_getDimension($_product_variation->get_length(),$pickup_country) * ossm_getDimension($_product_variation->get_width(),$pickup_country) * ossm_getDimension($_product_variation->get_height(),$pickup_country));
									$volume += (float)$pv * $item['qty'];
								}
							}
						}
					}
				}
		  }

			$weight = ossm_getWeight ($weight, $pickup_country );
			$maxWeight = ossm_maxWeightLimit ($pickup_country, trim($receiver_country) );
			$maxVolume = ossm_maxVolumeLimit ($pickup_country, trim($receiver_country) );
			//echo $pickup_country."-".$receiver_country."-".$weight."-".$maxWeight."-".$volume."-".$maxVolume;

	}else{

		$api_id = $sendle_setting['api_id'];
		$api_key = $sendle_setting['api_key'];
		$api_mode = $sendle_setting['mode'];
		if($api_mode == "live"){ $apiurl = "https://api.sendle.com";}
		else{ $apiurl = SENDLE_JOOVII_API_SANDBOX_URL; }
		$url = $apiurl."/api/orders/".$sendle_order_id;

		$args = array(
						'method'			=> 'GET',
						'timeout'     => 30,
						'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
						'headers' 		=> array( 'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_key ),
																		'Content-Type'=> 'application/json',
																		'Accept' =>'application/json')
						);

		$content = wp_remote_get( $url, $args );
		$return = wp_remote_retrieve_body( $content );
		$ostatus = json_decode($return, true);

	}
    
	if(!isset($ostatus) ||  $sendle_order_id == ''){ $ostatus['state'] = "yet to post"; }
	?>
    <ul>
		<?php
		$psoArr = $sendle_setting['process_as_sendle_order'];
		$sendlePost = 0;
		//if($sendle_setting['showrates'] == 'yes'){ $sendlePost = 0; }else{ }
		if (is_array($psoArr) && is_array($shipping_method_name2)){
			if(in_array("flat_rate", $psoArr) && in_array("flat_rate", $shipping_method_name2)){ $sendlePost = 1;	 }
			if(in_array("free_rate", $psoArr) && in_array("free_shipping", $shipping_method_name2)){ $sendlePost = 1; }
		}
		if (is_array($psoArr)) {
			if(in_array("any_method", $psoArr) ){ $sendlePost = 1; }
		}
		if(isset($ostatus['state'])){
			echo '<li>' . esc_html__( 'Sendle Order Status:', 'official-sendle-shipping-method' ) . ' ' . esc_html( $ostatus['state'] ) . '</li>';
		}
		
		//echo "Shipping Method" ;	print_r($shipping_method_name);
		
		if(in_array("ossmsendle", $shipping_method_name) || $sendlePost == 1 ){

			if($weight > $maxWeight || $volume > $maxVolume){

					$sendleWeightUnit = 'kg';
			    if($pickup_country == 'AU') { $sendleWeightUnit = 'kg';}
			    if($pickup_country == 'US') { $sendleWeightUnit = 'lbs';}
					if($pickup_country == 'CA') { $sendleWeightUnit = 'kg';}
					if($weight > $maxWeight){
						
						echo '<li><strong>' . esc_html__( 'Your order weight is greater than the Sendle max weight. Please book separate shipments.', 'official-sendle-shipping-method' ) . '</strong></li>';

						echo '<li>' . sprintf(
						    /* translators: 1: order weight value, 2: weight unit */
						    esc_html__( 'Order weight: %1$s %2$s', 'official-sendle-shipping-method' ),
						    esc_html( round( $weight, 2 ) ),
						    esc_html( $sendleWeightUnit )
						) . '</li>';


						echo '<li>' . sprintf(
						    /* translators: 1: maximum allowed weight, 2: weight unit */
						    esc_html__( 'Sendle max weight: %1$s %2$s', 'official-sendle-shipping-method' ),
						    esc_html( $maxWeight ),
						    esc_html( $sendleWeightUnit )
						) . '</li>';


						echo '<li>&nbsp;</li>';

					}

					$sendleDimensionUnit = 'm3';
					if($pickup_country == 'AU') { $sendleDimensionUnit = 'm3';}
					if($pickup_country == 'US') { $sendleDimensionUnit = 'in3';}
					if($pickup_country == 'CA') { $sendleDimensionUnit = 'm3';}
					
					if($volume > $maxVolume){

							echo '<li><strong>' . esc_html__(
							    'Your order volume is greater than the Sendle max volume. Please book separate shipments.',
							    'official-sendle-shipping-method'
							) . '</strong></li>';

							echo '<li>' . sprintf(
							    /* translators: 1: order volume value, 2: volume unit */
							    esc_html__( 'Order volume: %1$s %2$s', 'official-sendle-shipping-method' ),
							    esc_html( round( $volume, 2 ) ),
							    esc_html( $sendleDimensionUnit )
							) . '</li>';

							if ( $pickup_country === 'AU' ) {
							    echo '<li>' . esc_html__(
							        'Sendle max volume: 0.1 m3',
							        'official-sendle-shipping-method'
							    ) . '</li>';
							} else {
							    echo '<li>' . sprintf(
							        /* translators: 1: maximum allowed volume, 2: volume unit */
							        esc_html__( 'Sendle max volume: %1$s %2$s', 'official-sendle-shipping-method' ),
							        esc_html( $maxVolume ),
							        esc_html( $sendleDimensionUnit )
							    ) . '</li>';
							}


					}

			}else{
				if(isset($ostatus['state'])){
					if($ostatus['state'] != 'Cancelled' ) {

						if($weight > 0){

						if($book_shipment_on=="shipment_submit" && $sendle_reference==""){?>
							<li>

								<a href="<?php
												  echo esc_url(
												    wp_nonce_url(
												      admin_url( 'admin.php?page=create-shipment&method=normal&oid=' . absint($order_id) ),
												      'ossm_create_shipment'
												    )
												  );
												?>" target="_blank">

								<br>
								<?php if($satchel_booking == 'yes' && $pickup_country == 'AU' && $receiver_country == 'AU'  && $sendle_setting['satchel_booking_adminlink'] == 'yes' &&  ($sendle_setting['satchel_mode'] == 'both' || $sendle_setting['satchel_mode'] == 'booking')){ ?>
								<br>

								<a href="<?php
												  echo esc_url(
												    wp_nonce_url(
												      admin_url( 'admin.php?page=create-shipment&method=satchel&oid=' . absint($order_id) ),
												      'ossm_create_shipment'
												    )
												  );
												?>" target="_blank">
							  <?php } ?>
							</li>
						<?php }
						if($book_shipment_on=="order_submit" && $sendle_reference==""){?>
							<li>
						    <h2 style="color:red">
						        <?php echo esc_html__( 'There seems to be an error while submitting. Please create shipment once more.', 'official-sendle-shipping-method' ); ?>
						    </h2>

						    <?php
								$base_url = admin_url('admin.php?page=create-shipment&method=normal&oid=' . absint($order_id));
								$url      = wp_nonce_url($base_url, 'ossm_create_shipment'); // adds _wpnonce=
								?>

								<a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
								  <?php esc_html_e('Create Shipment', 'official-sendle-shipping-method'); ?>
								</a>

						    <br>

						    <?php if ( $satchel_booking === 'yes' && $pickup_country === 'AU' && $receiver_country === 'AU' && $sendle_setting['satchel_booking_adminlink'] === 'yes'
						        && ( $sendle_setting['satchel_mode'] === 'both' || $sendle_setting['satchel_mode'] === 'booking' ) ) : ?>

						        <br>
						        <?php
										$base_url = admin_url('admin.php?page=create-shipment&method=satchel&oid=' . absint($order_id));
										$url      = wp_nonce_url($base_url, 'ossm_create_shipment');
										?>

										<a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
										  <?php esc_html_e('Create Shipment [Satchel Booking]', 'official-sendle-shipping-method'); ?>
										</a>

						    <?php endif; ?>
						</li>

						<?php }
					}else{
						
						if($sendle_reference==""){
							echo '<li><strong>' . esc_html__(
							    'Your order weight is 0. Please fix the product weight before booking a shipment.',
							    'official-sendle-shipping-method'
							) . '</strong></li>';

							echo '<li>' . sprintf(
							    /* translators: 1: order weight value, 2: weight unit */
							    esc_html__( 'Order weight: %1$s %2$s', 'official-sendle-shipping-method' ),
							    esc_html( round( $weight, 2 ) ),
							    esc_html( $sendleWeightUnit )
							) . '</li>';

							echo '<li>&nbsp;</li>';

						}
					}
						if($sendle_reference!=""){
								$labelsArray = ossm_getDownloadLabelLink($sendle_order_id);
						?>
							<li>
						    <a href="<?php
						        echo esc_url(
						            wp_nonce_url(
						                admin_url(
						                    'admin.php?page=track-shipment&sendle_reference=' . rawurlencode( $sendle_reference ) . '&oid=' . absint( $order_id )
						                ),
						                'ossm_track_shipment'
						            )
						        );
						    ?>" target="_blank">
						        <?php esc_html_e( 'Track', 'official-sendle-shipping-method' ); ?>
						    </a>
						</li>
							<?php
							if(isset($labelsArray)) { 
								foreach ( $labelsArray as $kl => $vl ) {
								    $download_url = wp_nonce_url(
								        admin_url(
								            'admin.php?page=download-shipping-label&oid=' . absint( $order_id ) . '&pdfdlink=' . rawurlencode( $vl['size'] )
								        ),
								        'ossm_download_label'
								    );
								    ?>
								    <li>
								        <a href="<?php echo esc_url( $download_url ); ?>" target="_blank">
								            <?php
								            echo esc_html(
															    sprintf(
															        /* translators: %s: shipping label size (e.g. A4, cropped) */
															        __( 'Download Shipping Label [size=%s]', 'official-sendle-shipping-method' ),
															        $vl['size']
															    )
															);
								            ?>
								        </a>
								    </li>
								    <?php
								}
							}
							?>
								<li>
								  <a href="<?php
								    echo esc_url(
								      wp_nonce_url(
								        admin_url(
								          'admin.php?page=cancel-sendle&sendle_order_id=' . rawurlencode( $sendle_order_id ) . '&oid=' . absint( $order_id )
								        ),
								        'ossm_cancel_sendle'
								      )
								    );
								  ?>" target="_blank">
								    <?php esc_html_e( 'Cancel Sendle Order', 'official-sendle-shipping-method' ); ?>
								  </a>
								</li>
							  <li>
								  <a href="<?php
								    echo esc_url(
								      wp_nonce_url(
								        admin_url(
								          'admin.php?page=viewdetails-sendle&sendle_order_id=' . rawurlencode( $sendle_order_id ) . '&oid=' . absint( $order_id )
								        ),
								        'ossm_viewdetails_sendle'
								      )
								    );
								  ?>" target="_blank">
								    <?php esc_html_e( 'View Sendle Order Details', 'official-sendle-shipping-method' ); ?>
								  </a>
								</li>
						<?php
						}
					}
				}else{
					if($book_shipment_on=="order_submit" && $sendle_reference==""){?>
						<li>
					    <h2 style="color:red">
					        <?php echo esc_html__(
					            'There seems to be an error while submitting. Please create the shipment once more.',
					            'official-sendle-shipping-method'
					        ); ?>
					    </h2>

					    <a href="<?php echo esc_url( admin_url(
					        'admin.php?page=create-shipment&method=normal&oid=' . absint( $order_id )
					    ) ); ?>" target="_blank" rel="noopener noreferrer">
					        <?php esc_html_e( 'Create Shipment', 'official-sendle-shipping-method' ); ?>
					    </a>
					    <br>

					    <?php if (
					        $satchel_booking === 'yes'
					        && $pickup_country === 'AU'
					        && $receiver_country === 'AU'
					        && ! empty( $sendle_setting['satchel_booking_adminlink'] )
					        && $sendle_setting['satchel_booking_adminlink'] === 'yes'
					        && (
					            $sendle_setting['satchel_mode'] === 'both'
					            || $sendle_setting['satchel_mode'] === 'booking'
					        )
					    ) : ?>

					        <br>
					        <a href="<?php echo esc_url( admin_url(
					            'admin.php?page=create-shipment&method=satchel&oid=' . absint( $order_id )
					        ) ); ?>" target="_blank" rel="noopener noreferrer">
					            <?php esc_html_e(
					                'Create Shipment [Satchel Booking]',
					                'official-sendle-shipping-method'
					            ); ?>
					        </a>

					    <?php endif; ?>
					</li>

					<?php }
				}
			}

		}else{
			echo '<li>' . esc_html__( 'No option available', 'official-sendle-shipping-method' ) . '</li>';
		}
		echo '</ul>';
} ?>
