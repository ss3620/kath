<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ossm_validate_sendle() {

	if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You are not allowed to perform this action.', 'official-sendle-shipping-method' ), 403 );
	}

	$sendle_setting         = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings' ) );
	$pickup_suburb          = $sendle_setting['pickup_suburb'];
	$pickup_postcode        = $sendle_setting['pickup_postcode'];
	$shipping_quote_markup  = $sendle_setting['quote_markup'];
	$sendle_id              = $sendle_setting['api_id'];
	$sendle_key             = $sendle_setting['api_key'];
	$mode                   = $sendle_setting['mode'];
	$pickupoption           = $sendle_setting['pickupoption'];

	/**
	 * Read request values safely (unslash first, then sanitize).
	 * We keep the same variable names and the same defaulting behavior.
	 */
	$pickup_country = 'AU';
	if ( isset( $_REQUEST['pickup_country'] ) ) {
		$pickup_country = ossm_validate_input_text( sanitize_text_field( wp_unslash( $_REQUEST['pickup_country'] ) ) );
	}

	// Default test values based on plugin pickup_country setting (logic unchanged)
	if ( $sendle_setting['pickup_country'] == 'US' ) {
		$delivery_suburb   = 'Brooklyn';
		$delivery_postcode = '11203';
		$product_weight    = '10';
		$product_volume    = '70';
		$delivery_country  = 'US';
		$pickup_country    = 'US';
	} elseif ( $sendle_setting['pickup_country'] == 'CA' ) {
		$delivery_suburb   = 'Toronto';
		$delivery_postcode = 'M4Y 0A9';
		$product_weight    = '1';
		$product_volume    = '0.001';
		$delivery_country  = 'CA';
		$pickup_country    = 'CA';
	} else {
		$delivery_suburb   = 'Sydney';
		$delivery_postcode = '2000';
		$product_weight    = '1';
		$product_volume    = '0.001';
		$delivery_country  = 'AU';
		$pickup_country    = 'AU';
	}

	/**
	 * Allow overriding defaults from request (logic unchanged) – but safely.
	 * Note: We intentionally keep $_REQUEST usage (as your logic does), but
	 * we unslash + sanitize before use to satisfy Plugin Check.
	 */
	if ( isset( $_REQUEST['sendle_id'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['sendle_id'] ) );
		if ( trim( $tmp ) !== '' ) {
			$sendle_id = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['sendle_key'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['sendle_key'] ) );
		if ( trim( $tmp ) !== '' ) {
			$sendle_key = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['mode'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['mode'] ) );
		if ( trim( $tmp ) !== '' ) {
			$mode = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['pickup_suburb'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['pickup_suburb'] ) );
		if ( trim( $tmp ) !== '' ) {
			$pickup_suburb = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['pickup_postcode'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['pickup_postcode'] ) );
		if ( trim( $tmp ) !== '' ) {
			$pickup_postcode = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['delivery_suburb'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['delivery_suburb'] ) );
		if ( trim( $tmp ) !== '' ) {
			$delivery_suburb = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['delivery_postcode'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['delivery_postcode'] ) );
		if ( trim( $tmp ) !== '' ) {
			$delivery_postcode = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['delivery_country'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['delivery_country'] ) );
		if ( trim( $tmp ) !== '' ) {
			$delivery_country = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['product_weight'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['product_weight'] ) );
		if ( trim( $tmp ) !== '' ) {
			$product_weight = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['product_volume'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['product_volume'] ) );
		if ( trim( $tmp ) !== '' ) {
			$product_volume = ossm_validate_input_text( $tmp );
		}
	}

	if ( isset( $_REQUEST['pickupoption'] ) ) {
		$tmp = sanitize_text_field( wp_unslash( $_REQUEST['pickupoption'] ) );
		if ( trim( $tmp ) !== '' ) {
			$pickupoption = ossm_validate_input_text( $tmp );
		}
	}

	$countries_obj = new WC_Countries();
	$countries     = $countries_obj->__get( 'countries' );

	?>
	<!-- FORM STARTS -->
	<form method="post" action="<?php echo esc_url( remove_query_arg( array( '_wpnonce_ossm_validate_sendle' ) ) ); ?>" enctype="multipart/form-data">
		<?php wp_nonce_field( 'ossm_validate_sendle', '_wpnonce_ossm_validate_sendle' ); ?>

		<input type="hidden" name="rate" value="calculate">

		<div class="wrap"><h3 style="text-decoration:underline"><?php echo esc_html__( 'Validate Sendle Api Key', 'official-sendle-shipping-method' ); ?></h3></div>

		<table cellpadding="0" cellspacing="0" border="0" width="100%">
			<tr>
				<td width="10%" align="right"><?php echo esc_html__( 'Sendle ID', 'official-sendle-shipping-method' ); ?></td>
				<td width="1%">&nbsp;</td>
				<td><input type="text" name="sendle_id" value="<?php echo esc_attr( $sendle_id ); ?>" style="width:350px" /></td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Sendle Key', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td><input type="text" name="sendle_key" value="<?php echo esc_attr( $sendle_key ); ?>" style="width:350px" /></td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Mode', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td>
					<select class="select" name="mode" id="mode">
						<option value="sandbox" <?php selected( $mode, 'sandbox' ); ?>><?php echo esc_html__( 'Sandbox', 'official-sendle-shipping-method' ); ?></option>
						<option value="live" <?php selected( $mode, 'live' ); ?>><?php echo esc_html__( 'Live', 'official-sendle-shipping-method' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Pickup Country', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td>
					<select name="pickup_country" id="pickup_country">
						<option value="AU" <?php selected( $pickup_country, 'AU' ); ?>><?php echo esc_html__( 'Australia', 'official-sendle-shipping-method' ); ?></option>
						<option value="US" <?php selected( $pickup_country, 'US' ); ?>><?php echo esc_html__( 'United States', 'official-sendle-shipping-method' ); ?></option>
						<option value="CA" <?php selected( $pickup_country, 'CA' ); ?>><?php echo esc_html__( 'Canada', 'official-sendle-shipping-method' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Pickup Suburb', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td><input type="text" name="pickup_suburb" value="<?php echo esc_attr( $pickup_suburb ); ?>" /></td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Pickup Postcode', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td><input type="text" name="pickup_postcode" value="<?php echo esc_attr( $pickup_postcode ); ?>" /></td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Delivery Suburb', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td><input type="text" name="delivery_suburb" value="<?php echo esc_attr( $delivery_suburb ); ?>" /></td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Delivery Postcode', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td><input type="text" name="delivery_postcode" value="<?php echo esc_attr( $delivery_postcode ); ?>" /></td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Delivery Country', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td>
					<select name="delivery_country" id="delivery_country">
						<?php foreach ( $countries as $k => $v ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $delivery_country, $k ); ?>>
								<?php echo esc_html( $v ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php echo esc_html__( '[Change it for international Order, also Sendle does not support International Orders sent from the United States yet.]', 'official-sendle-shipping-method' ); ?>
				</td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Product Weight', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td>
					<input type="text" name="product_weight" value="<?php echo esc_attr( $product_weight ); ?>" />
					<?php echo esc_html__( '(In KG for AU/CA and lb for US)', 'official-sendle-shipping-method' ); ?>
				</td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Product Volume', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td>
					<input type="text" name="product_volume" value="<?php echo esc_attr( $product_volume ); ?>" />
					<?php echo esc_html__( '(In m3 for AU/CA and in3 for US)', 'official-sendle-shipping-method' ); ?>
				</td>
			</tr>
			<tr>
				<td align="right"><?php echo esc_html__( 'Pickup Option', 'official-sendle-shipping-method' ); ?></td>
				<td>&nbsp;</td>
				<td>
					<select class="select" name="pickupoption" id="pickupoption">
						<option value="pickup" <?php selected( $pickupoption, 'pickup' ); ?>><?php echo esc_html__( 'Pickup From store', 'official-sendle-shipping-method' ); ?></option>
						<option value="drop off" <?php selected( $pickupoption, 'drop off' ); ?>><?php echo esc_html__( 'Drop it off at the nearest drop off location.', 'official-sendle-shipping-method' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td align="left">&nbsp;<input type="submit" name="validate" value="<?php echo esc_attr__( 'Validate', 'official-sendle-shipping-method' ); ?>" class="button"></td>
			</tr>
		</table>
	</form>
	<?php

	/**
	 * PROCESS FORM (nonce verified BEFORE using POST values)
	 * Plugin Check expects nonce verification before processing.
	 */
	if ( isset( $_POST['rate'] ) && 'calculate' === sanitize_text_field( wp_unslash( $_POST['rate'] ) ) ) {

		$nonce = isset( $_POST['_wpnonce_ossm_validate_sendle'] )
			? sanitize_text_field( wp_unslash( $_POST['_wpnonce_ossm_validate_sendle'] ) )
			: '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ossm_validate_sendle' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'official-sendle-shipping-method' ), 403 );
		}

		// Now safely read POST values (unslash first, then sanitize)
		$delivery_country  = isset( $_POST['delivery_country'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['delivery_country'] ) ) ) : '';
		$delivery_suburb   = isset( $_POST['delivery_suburb'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['delivery_suburb'] ) ) ) : '';
		$delivery_postcode = isset( $_POST['delivery_postcode'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['delivery_postcode'] ) ) ) : '';

		$pickup_country    = isset( $_POST['pickup_country'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['pickup_country'] ) ) ) : '';
		$pickup_suburb     = isset( $_POST['pickup_suburb'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['pickup_suburb'] ) ) ) : '';
		$pickup_postcode   = isset( $_POST['pickup_postcode'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['pickup_postcode'] ) ) ) : '';
		$pickupoption      = isset( $_POST['pickupoption'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['pickupoption'] ) ) ) : '';

		$sendle_id         = isset( $_POST['sendle_id'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['sendle_id'] ) ) ) : '';
		$sendle_key        = isset( $_POST['sendle_key'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['sendle_key'] ) ) ) : '';
		$mode              = isset( $_POST['mode'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['mode'] ) ) ) : '';

		$product_weight    = isset( $_POST['product_weight'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['product_weight'] ) ) ) : '';
		$product_volume    = isset( $_POST['product_volume'] ) ? ossm_validate_input_text( sanitize_text_field( wp_unslash( $_POST['product_volume'] ) ) ) : '';

		$package                         = array();
		$package['destination']['country']  = $delivery_country;
		$package['destination']['city']     = $delivery_suburb;
		$package['destination']['postcode'] = $delivery_postcode;
		$package['destination']['state']    = '';

		$sendleSettingArr                               = array();
		$sendleSettingArr['pickup_country']             = $pickup_country;
		$sendleSettingArr['pickup_suburb']              = $pickup_suburb;
		$sendleSettingArr['pickup_postcode']            = $pickup_postcode;
		$sendleSettingArr['pickupoption']               = $pickupoption;
		$sendleSettingArr['woocommerce_ossm_sendle_updatejoovii'] = 'yes';
		$sendleSettingArr['enabled']                    = 'yes';
		$sendleSettingArr['showrates']                  = 'yes';
		$sendleSettingArr['quote_markup']               = 0;
		$sendleSettingArr['shipping_handling_fee']      = 0;
		$sendleSettingArr['api_id']                     = $sendle_id;
		$sendleSettingArr['api_key']                    = $sendle_key;
		$sendleSettingArr['mode']                       = $mode;
		$sendleSettingArr['volume_param']               = '';
		$sendleSettingArr['satchel_booking']            = '';
		$sendleSettingArr['satchel_mode']               = '';
		$sendleSettingArr['satchel_threshold_weight']   = '';
		$sendleSettingArr['satchel_threshold_qty']      = '';
		$sendleSettingArr['satchel_threshold_weight']   = '';
		$sendleSettingArr['satchel_threshold_qty']      = '';

		$urlParam = ossm_createRequestStr( $package, '99999', '99999', $sendleSettingArr, $product_weight, $product_volume, 'no' );
		$result   = ossm_calculateSendleRate( $package, $sendleSettingArr, $urlParam );
		?>
		<br /><?php echo esc_html__( 'GET URL : ', 'official-sendle-shipping-method' ); ?><?php echo esc_html( $urlParam ); ?><br /><br />
		<?php

		if ( isset( $result['error_description'] ) && '' !== trim( (string) $result['error_description'] ) ) {

			echo '<div style="' . esc_attr( 'width:50%; text-align:left; font-weight:bold; font-size:14px; background-color:#D98888;padding: 2px;' ) . '">'
				. esc_html__( 'Error :: ', 'official-sendle-shipping-method' )
				. esc_html( (string) $result['error_description'] )
				. '</div><br><br>';

			if ( isset( $result['messages'] ) ) {
				echo '<pre>' . esc_html( print_r( $result['messages'], true ) ) . '</pre>';
			}
		}

		if ( isset( $result[0]['quote']['gross']['amount'] ) ) {
			$rate = $result[0]['quote']['gross']['amount'];
			?>
			<div style="<?php echo esc_attr( 'width:50%; text-align:left; font-weight:bold; font-size:14px; background-color:#CCC; padding: 2px;' ); ?>">
				<?php
				echo esc_html__( 'Sendle Shipping Cost : ', 'official-sendle-shipping-method' );
				echo '&nbsp;' . esc_html( (string) $rate );
				?>
			</div>
			<?php
		}
	}
}
