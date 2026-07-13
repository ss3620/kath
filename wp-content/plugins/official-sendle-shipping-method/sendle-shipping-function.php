<?php
/**
 * NOTE: Keep your ABSPATH guard in the main plugin file as well.
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

function ossm_validate_input_text( $input_text ) {
  $input_text = sanitize_text_field( $input_text );
  $input_text = esc_html( $input_text );
  $input_text = esc_js( $input_text );
  return $input_text;
}

function ossm_getAssignRole() {
  $c_id        = get_current_user_id();
  $assign_role = '';
  $user        = new WP_User( $c_id );

  if ( isset( $user->roles[0] ) ) {
    $u_role = $user->roles[0];
    if ( 'administrator' === $u_role ) {
      $assign_role = $u_role;
    } else {
      $sendle_setting         = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings' ) );
      $role_manager_settings  = isset( $sendle_setting['role_manager'] ) ? $sendle_setting['role_manager'] : '';
      if ( $u_role === $role_manager_settings ) {
        $assign_role = $u_role;
      }
    }
  }
  return $assign_role;
}

function ossm_getAssignPermission() {
  $c_id              = get_current_user_id();
  $assign_permission  = '0';
  $user              = new WP_User( $c_id );

  if ( isset( $user->roles[0] ) ) {
    $u_role = $user->roles[0];
    if ( 'administrator' === $u_role ) {
      $assign_permission = '1';
    } else {
      $sendle_setting        = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings' ) );
      $role_manager_settings = isset( $sendle_setting['role_manager'] ) ? $sendle_setting['role_manager'] : '';
      if ( $u_role === $role_manager_settings ) {
        $assign_permission = '1';
      }
    }
  }
  return $assign_permission;
}

function ossm_getWeightStrForQuote( $weight, $pickupCountry ) {
  $wpWeightUnit     = get_option( 'woocommerce_weight_unit' );
  $sendleWeightUnit = 'kg';
  if ( 'AU' === $pickupCountry ) { $sendleWeightUnit = 'kg'; }
  if ( 'US' === $pickupCountry ) { $sendleWeightUnit = 'lb'; }
  if ( 'CA' === $pickupCountry ) { $sendleWeightUnit = 'kg'; }

  return 'weight_value=' . $weight . '&weight_units=' . $sendleWeightUnit . '&';
}

function ossm_getWeightStrForOrder( $weight, $pickupCountry, $receiver_country, $satchel_booking_click = 'normal' ) {
  $wpWeightUnit     = get_option( 'woocommerce_weight_unit' );
  $sendleWeightUnit = 'kg';
  if ( 'AU' === $pickupCountry ) { $sendleWeightUnit = 'kg'; }
  if ( 'US' === $pickupCountry ) { $sendleWeightUnit = 'lb'; }
  if ( 'CA' === $pickupCountry ) { $sendleWeightUnit = 'kg'; }

  $sendle_setting = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings' ) );

  // ------   Satchel calculation start --------------------------------------------
  $wpWeightUnit = get_option( 'woocommerce_weight_unit' );

  if ( 'AU' === $pickupCountry && 'AU' === $receiver_country ) {

    $satchel_threshold_weight = isset( $sendle_setting['satchel_threshold_weight'] ) ? (float) $sendle_setting['satchel_threshold_weight'] : 0;
    $satchel_threshold_qty    = isset( $sendle_setting['satchel_threshold_qty'] ) ? (float) $sendle_setting['satchel_threshold_qty'] : 0;

    if ( isset( $sendle_setting['satchel_booking'], $sendle_setting['satchel_mode'] ) && 'yes' === $sendle_setting['satchel_booking'] ) {
      if ( 'both' === $sendle_setting['satchel_mode'] || 'booking' === $sendle_setting['satchel_mode'] ) {

        if ( 'satchel' === $satchel_booking_click ) {
          $weight           = '500';
          $sendleWeightUnit = 'g';
        } else {

          if ( 'kg' === $wpWeightUnit ) {
            $cartTotalweight = (float) $weight * 1000; // NOTE: variable used in your original logic
          }

          if ( isset( $cartTotalweight ) && $cartTotalweight <= $satchel_threshold_weight && $satchel_threshold_weight > 0 ) {
            $weight           = '500';
            $sendleWeightUnit = 'g';
            ossm_logActions( ' satchel_threshold_weight_orderbooking  :: yes   ' );
          }
          if ( isset( $cartTotalQuatity ) && $cartTotalQuatity <= $satchel_threshold_qty && $satchel_threshold_qty > 0 ) {
            $weight           = '500';
            $sendleWeightUnit = 'g';
            ossm_logActions( ' satchel_threshold_qty_orderbooking  :: yes  ' );
          }
        }
      }
    }
  }
  // ------   Satchel calculation end --------------------------------------------

  return '"weight": {"value": "' . $weight . '", "units": "' . $sendleWeightUnit . '"},';
}

function ossm_getWeight( $weight, $pickupCountry ) {
  $wpWeightUnit     = get_option( 'woocommerce_weight_unit' );
  $sendleWeightUnit = 'kg';
  if ( 'AU' === $pickupCountry ) { $sendleWeightUnit = 'kg'; }
  if ( 'US' === $pickupCountry ) { $sendleWeightUnit = 'lbs'; }
  if ( 'CA' === $pickupCountry ) { $sendleWeightUnit = 'kg'; }

  if ( $weight > 0 ) {
    $finalWeight = wc_get_weight( $weight, $sendleWeightUnit, $wpWeightUnit );
  } else {
    $finalWeight = 0;
  }
  return round( $finalWeight, 2 );
}

function ossm_getVolumeStrForQuote( $volumn, $pickupCountry ) {
  $sendle_setting = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings' ) );
  if ( ! isset( $sendle_setting['volume_param'] ) || trim( $sendle_setting['volume_param'] ) !== 'yes' ) {
    return '';
  }

  $sendleDimensionUnit = 'm3';
  if ( 'AU' === $pickupCountry ) { $sendleDimensionUnit = 'm3'; }
  if ( 'US' === $pickupCountry ) { $sendleDimensionUnit = 'in3'; }
  if ( 'CA' === $pickupCountry ) { $sendleDimensionUnit = 'm3'; }

  return 'volume_value=' . $volumn . '&volume_units=' . $sendleDimensionUnit . '&';
}

function ossm_getVolumeStrForOrder( $volumn, $weight, $pickupCountry, $receiver_country, $satchel_booking_click = 'normal' ) {
  $sendle_setting = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings' ) );
  if ( ! isset( $sendle_setting['volume_param'] ) || trim( $sendle_setting['volume_param'] ) !== 'yes' ) {
    return '';
  }

  $sendleDimensionUnit = 'm';
  if ( 'AU' === $pickupCountry ) { $sendleDimensionUnit = 'm3'; }
  if ( 'US' === $pickupCountry ) { $sendleDimensionUnit = 'in3'; }
  if ( 'CA' === $pickupCountry ) { $sendleDimensionUnit = 'm3'; }

  if ( $volumn > 0 ) {

    // ------   Satchel calculation start --------------------------------------------
    $wpWeightUnit = get_option( 'woocommerce_weight_unit' );

    if ( 'AU' === $pickupCountry && 'AU' === $receiver_country ) {

      $satchel_threshold_weight = isset( $sendle_setting['satchel_threshold_weight'] ) ? (float) $sendle_setting['satchel_threshold_weight'] : 0;
      $satchel_threshold_qty    = isset( $sendle_setting['satchel_threshold_qty'] ) ? (float) $sendle_setting['satchel_threshold_qty'] : 0;

      if ( isset( $sendle_setting['satchel_booking'], $sendle_setting['satchel_mode'] ) && 'yes' === $sendle_setting['satchel_booking'] ) {
        if ( 'both' === $sendle_setting['satchel_mode'] || 'booking' === $sendle_setting['satchel_mode'] ) {

          if ( 'satchel' === $satchel_booking_click ) {
            return '';
          }

          if ( 'kg' === $wpWeightUnit ) {
            $cartTotalweight = (float) $weight * 1000; // NOTE: variable used in your original logic
          }

          if ( isset( $cartTotalweight ) && $cartTotalweight <= $satchel_threshold_weight && $satchel_threshold_weight > 0 ) {
            ossm_logActions( ' satchel_threshold_volumn_orderbooking  :: yes   ' );
            return '';
          }
          if ( isset( $cartTotalQuatity ) && $cartTotalQuatity <= $satchel_threshold_qty && $satchel_threshold_qty > 0 ) {
            ossm_logActions( ' satchel_threshold_volumn_qty_orderbooking  :: yes  ' );
            return '';
          }
        }
      }
    }
    // ------   Satchel calculation end --------------------------------------------

    return '"volume": {"value": "' . $volumn . '", "units": "' . $sendleDimensionUnit . '"},';
  }

  return '';
}

function ossm_getDimension( $dimension, $pickupCountry ) {
  $wpDimensionUnit     = get_option( 'woocommerce_dimension_unit' );
  $sendleDimensionUnit = 'm';
  if ( 'AU' === $pickupCountry ) { $sendleDimensionUnit = 'm'; }
  if ( 'US' === $pickupCountry ) { $sendleDimensionUnit = 'in'; }
  if ( 'CA' === $pickupCountry ) { $sendleDimensionUnit = 'm'; }

  if ( $dimension > 0 ) {
    $finalDimension = wc_get_dimension( $dimension, $sendleDimensionUnit, $wpDimensionUnit );
  } else {
    $finalDimension = 0;
  }

  return $finalDimension;
}

function ossm_maxWeightLimit( $pickupCountry, $deliveryCountry ) {
  $maxWeight = SENDLE_JOOVII_AU_MAX_DOMESTIC_WEIGHT;
  if ( 'AU' === $pickupCountry ) {
    $maxWeight = SENDLE_JOOVII_AU_MAX_DOMESTIC_WEIGHT;
    if ( 'AU' !== $deliveryCountry ) {
      $maxWeight = SENDLE_JOOVII_AU_MAX_INTERNATION_WEIGHT;
    }
  }
  if ( 'US' === $pickupCountry ) { $maxWeight = SENDLE_JOOVII_US_MAX_DOMESTIC_WEIGHT; }
  if ( 'CA' === $pickupCountry ) { $maxWeight = SENDLE_JOOVII_CA_MAX_DOMESTIC_WEIGHT; }
  return $maxWeight;
}

function ossm_maxVolumeLimit( $pickupCountry, $deliveryCountry ) {
  $maxVolume = SENDLE_JOOVII_AU_MAX_DOMESTIC_VOLUMN;
  if ( 'AU' === $pickupCountry ) {
    $maxVolume = SENDLE_JOOVII_AU_MAX_DOMESTIC_VOLUMN;
    if ( 'AU' !== $deliveryCountry ) {
      $maxVolume = SENDLE_JOOVII_AU_MAX_INTERNATION_VOLUMN;
    }
  }
  if ( 'US' === $pickupCountry ) { $maxVolume = SENDLE_JOOVII_US_MAX_DOMESTIC_VOLUMN; }
  if ( 'CA' === $pickupCountry ) { $maxVolume = SENDLE_JOOVII_CS_MAX_DOMESTIC_VOLUMN; }
  return $maxVolume;
}

function ossm_weightDistributionArray( $packageArr, $weight, $volume, $maxWeight, $maxVolume ) {

  arsort( $packageArr, 1 );
  $packageDivisionArr = array();

  if ( count( $packageArr ) > 1 ) {

    $weightP            = 0;
    $volumeP            = 0;
    $volumeDistribution = 0;

    foreach ( $packageArr as $kp => $vp ) {
      $weightCurrent = $vp['w'];
      $volumeCurrent = $vp['v'];
      $weightLast    = $weightP;
      $volumeLast    = $volumeP;
      $weightP       = $weightLast + $weightCurrent;
      $volumeP       = $volumeLast + $volumeCurrent;

      if ( $weightP > $maxWeight ) {

        if ( $volumeP > $maxVolume ) { $volumeDistribution = 1; }
        $packageDivisionArr[] = array( 'w' => $weightLast, 'v' => $volumeLast );
        $weightP = $weightCurrent;
        $volumeP = $volumeCurrent;
      }
    }

    if ( $volumeP > $maxVolume ) { $volumeDistribution = 1; }
    $packageDivisionArr[] = array( 'w' => $weightP, 'v' => $volumeP );

    if ( 1 === $volumeDistribution ) {
      unset( $packageDivisionArr );
      reset( $packageArr );
      $weightP = 0;
      $volumeP = 0;

      foreach ( $packageArr as $kp => $vp ) {
        $weightCurrent = $vp['w'];
        $volumeCurrent = $vp['v'];
        $weightLast    = $weightP;
        $volumeLast    = $volumeP;
        $weightP       = $weightLast + $weightCurrent;
        $volumeP       = $volumeLast + $volumeCurrent;

        if ( $volumeP > $maxVolume ) {
          $packageDivisionArr[] = array( 'w' => $weightLast, 'v' => $volumeLast );
          $weightP = $weightCurrent;
          $volumeP = $volumeCurrent;
        }
      }
      $packageDivisionArr[] = array( 'w' => $weightP, 'v' => $volumeP );
    }

  } else {
    $packageDivisionArr[] = array( 'w' => $weight, 'v' => $volume );
  }

  return $packageDivisionArr;
}

function ossm_checkPackage( $package, $sendle_setting, $weight, $volume, $showWpNotice = 'yes' ) {

  $pickupCountry   = isset( $sendle_setting['pickup_country'] ) ? trim( $sendle_setting['pickup_country'] ) : '';
  $deliveryCountry = isset( $package['destination']['country'] ) ? trim( $package['destination']['country'] ) : '';

  if ( 'US' === $pickupCountry ) {
    if ( isset( $sendle_setting['volume_param'] ) && 'yes' === trim( $sendle_setting['volume_param'] ) ) {
      if ( $volume > SENDLE_JOOVII_US_MAX_DOMESTIC_VOLUMN ) {
        if ( 'yes' === $showWpNotice ) {
          wc_add_notice(
                sprintf(
                  /* translators: %s is the package volume in cubic inches */
                  esc_html__( 'Too large to be delivered. Your package volume: %s in³', 'official-sendle-shipping-method' ),
                  esc_html( (string) $volume )
                )
              );

          return false;
        }
      }
    }
  }

  if ( 'CA' === $pickupCountry ) {
    if ( isset( $sendle_setting['volume_param'] ) && 'yes' === trim( $sendle_setting['volume_param'] ) ) {
      if ( $volume > SENDLE_JOOVII_CS_MAX_DOMESTIC_VOLUMN ) {
        if ( 'yes' === $showWpNotice ) {
          wc_add_notice(
            sprintf(
              /* translators: %s is the package volume in cubic inches */
              esc_html__( 'Too large to be delivered. Your package volume: %s in³', 'official-sendle-shipping-method' ),
              esc_html( (string) $volume )
            )
          );

          return false;
        }
      }
    }
  }

  if ( 'AU' === $pickupCountry ) {
    if ( isset( $sendle_setting['volume_param'] ) && 'yes' === trim( $sendle_setting['volume_param'] ) ) {
      if ( 'AU' === $deliveryCountry ) {
        if ( $volume > SENDLE_JOOVII_AU_MAX_DOMESTIC_VOLUMN ) {
          if ( 'yes' === $showWpNotice ) {
            wc_add_notice(
              sprintf(
                /* translators: %s is the package volume in cubic meters */
                esc_html__( 'Too large to be delivered. Your package volume: %s m³', 'official-sendle-shipping-method' ),
                number_format_i18n( (float) $volume )
              )
            );
            return false;
          }
        }
      } else {
        if ( $volume > SENDLE_JOOVII_AU_MAX_INTERNATION_VOLUMN ) {
          if ( 'yes' === $showWpNotice ) {
            wc_add_notice(
              sprintf(
                /* translators: %s is the package volume in cubic meters */
                esc_html__( 'Too large to be delivered. Your package volume: %s m³', 'official-sendle-shipping-method' ),
                number_format_i18n( (float) $volume )
              )
            );
            return false;
          }
        }
      }
    }
  }

  return true;
}

function ossm_createRequestStr( $package, $cartTotalQuatity, $cartTotalweight, $sendle_setting, $weight, $volume, $satchelbooking = 'no' ) {

  $weightUnit = 'kg';
  $volumeUnit = 'm3';
  $cost       = 0;

  $pickupCountry  = isset( $sendle_setting['pickup_country'] ) ? trim( $sendle_setting['pickup_country'] ) : '';
  $pickupSuburb   = isset( $sendle_setting['pickup_suburb'] ) ? trim( $sendle_setting['pickup_suburb'] ) : '';
  $pickupPostcode = isset( $sendle_setting['pickup_postcode'] ) ? trim( $sendle_setting['pickup_postcode'] ) : '';

  $deliveryCountry  = isset( $package['destination']['country'] ) ? trim( $package['destination']['country'] ) : '';
  $deliverySuburb   = isset( $package['destination']['city'] ) ? trim( $package['destination']['city'] ) : '';
  $deliveryPostcode = isset( $package['destination']['postcode'] ) ? trim( $package['destination']['postcode'] ) : '';
  $deliveryState    = isset( $package['destination']['state'] ) ? trim( $package['destination']['state'] ) : '';

  $deliveryAddress = '';
  if ( isset( $package['destination']['address'] ) && trim( $package['destination']['address'] ) !== '' ) {
    $deliveryAddress = 'delivery_address_line1=' . rawurlencode( trim( $package['destination']['address'] ) ) . '&';
  }

  // FOR NEW API
  $deliveryAddressForNewAPI = 'receiver_address_line1=' . rawurlencode( trim( isset( $package['destination']['address'] ) ? $package['destination']['address'] : '' ) ) . '&';

  $volumnStr = '';

  if ( isset( $sendle_setting['mode'] ) && 'live' === $sendle_setting['mode'] ) {
    $sendle_apiurl = 'https://api.sendle.com';
  } else {
    $sendle_apiurl = SENDLE_JOOVII_API_SANDBOX_URL;
  }

  $pickupLocationStr = 'pickup_suburb=' . rawurlencode( $pickupSuburb ) . '&pickup_postcode=' . $pickupPostcode . '&pickup_country=' . $pickupCountry . '&';

  // FOR NEW API
  $pickupLocationStrForNewAPI = 'sender_suburb=' . rawurlencode( $pickupSuburb ) . '&sender_postcode=' . $pickupPostcode . '&sender_country=' . $pickupCountry . '&';

  $deliveryLocationStr = $deliveryAddress . 'delivery_suburb=' . rawurlencode( $deliverySuburb ) . '&delivery_postcode=' . $deliveryPostcode . '&delivery_country=' . $deliveryCountry . '&';

  // FOR NEW API
  $deliveryLocationStrForNewAPI = $deliveryAddressForNewAPI . 'receiver_suburb=' . rawurlencode( $deliverySuburb ) . '&receiver_postcode=' . $deliveryPostcode . '&receiver_country=' . $deliveryCountry . '&';

  $weightStr = 'weight_value=' . $weight . '&weight_units=' . $weightUnit . '&';
  $weightStr = ossm_getWeightStrForQuote( $weight, $pickupCountry );

  if ( $volume > 0 ) { $volumnStr = ossm_getVolumeStrForQuote( $volume, $pickupCountry ); }

  $extraStr = 'first_mile_option=' . $sendle_setting['pickupoption'];

  // ------   Satchel calculation start --------------------------------------------
  ossm_logActions(
    ' satchel_quotation  :: ' . $sendle_setting['pickup_country'] . ' - ' . $package['destination']['country'] .
    ' - (' . $satchelbooking . ' / ' . $sendle_setting['satchel_booking'] . ') - ' . $sendle_setting['satchel_mode'] .
    ' - (' . $sendle_setting['satchel_threshold_weight'] . ' / ' . $cartTotalweight . ') - (' . $sendle_setting['satchel_threshold_qty'] . ' / ' . $cartTotalQuatity . ') '
  );

  $wpWeightUnit = get_option( 'woocommerce_weight_unit' );

  if ( 'AU' === $sendle_setting['pickup_country'] && 'AU' === $package['destination']['country'] ) {

    $satchel_threshold_weight = (float) $sendle_setting['satchel_threshold_weight'];
    $satchel_threshold_qty    = (float) $sendle_setting['satchel_threshold_qty'];

    if ( 'yes' === $satchelbooking ) {
      if ( 'yes' === $sendle_setting['satchel_booking'] ) {
        if ( 'both' === $sendle_setting['satchel_mode'] || 'quotation' === $sendle_setting['satchel_mode'] ) {

          if ( 'kg' === $wpWeightUnit ) { $cartTotalweight = (float) $cartTotalweight * 1000; }

          if ( $cartTotalweight <= $satchel_threshold_weight && $satchel_threshold_weight > 0 ) {
            $weightStr = 'weight_value=500&weight_units=g&';
            $volumnStr = '';
            ossm_logActions( ' satchel_threshold_weight_quotation  :: yes   ' );
          }
          if ( $cartTotalQuatity <= $satchel_threshold_qty && $satchel_threshold_qty > 0 ) {
            $weightStr = 'weight_value=500&weight_units=g&';
            $volumnStr = '';
            ossm_logActions( ' satchel_threshold_qty_quotation  :: yes  ' );
          }
        }
      }
    }
  }
  // ------   Satchel calculation end --------------------------------------------

  // NEW URL for NEW API
  $urlParam = $sendle_apiurl . '/api/products?' . $pickupLocationStrForNewAPI . $deliveryLocationStrForNewAPI . $weightStr . $volumnStr . $extraStr;

  return $urlParam;
}

function ossm_calculateSendleRate( $package, $sendle_setting, $urlParam ) {

  if ( ! is_callable( 'curl_init' ) ) { return; }

  $sendle_api_id  = $sendle_setting['api_id'];
  $sendle_api_key = $sendle_setting['api_key'];

  $user_agent = '';
  if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
    $user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
  }

  $args = array(
    'method'     => 'GET',
    'timeout'    => 30,
    'user-agent' => $user_agent,
    'headers'    => array(
      'Authorization'       => 'Basic ' . base64_encode( $sendle_api_id . ':' . $sendle_api_key ),
      'Content-Type'        => 'application/json',
      'Accept'              => 'application/json',
      'Reseller-Identifier' => 'JooviiWoocommerce',
    ),
  );

  $content = wp_remote_get( $urlParam, $args );
  $return  = wp_remote_retrieve_body( $content );
  $result  = json_decode( $return, true );

  if ( isset( $result[0] ) ) {
    if ( isset( $result[0]['quote']['gross']['amount'] ) && $result[0]['quote']['gross']['amount'] > 0 ) {
      ossm_updateSendleInstallation( $sendle_setting );
    }
  }

  $optintojoovii = isset( $sendle_setting['optintojoovii'] ) ? $sendle_setting['optintojoovii'] : 'yes';

  if ( 'no' === $optintojoovii ) {
    return false;
  }
  return $result;
}

/**
 * ------------------------------------------------------------------
 * Create Rate Array
 * ------------------------------------------------------------------
 */
function ossm_createRateArray( $package, $sendle_setting, $result ) {

  $pickup_country         = sanitize_text_field( $sendle_setting['pickup_country'] ?? '' );
  $shipping_quote_markup  = floatval( $sendle_setting['quote_markup'] ?? 0 );
  $shipping_handling_fee  = floatval( $sendle_setting['shipping_handling_fee'] ?? 0 );
  $sendleTitle            = sanitize_text_field( $sendle_setting['title'] ?? 'Sendle Shipping' );

  $sendlecost = 0;
  if ( isset( $result[0]['quote']['gross']['amount'] ) ) {
    $sendlecost = floatval( $result[0]['quote']['gross']['amount'] );
  }

  $taxesArray                = false;
  $shipping_handling_fee_tax = 0;
  $shipping_handling_fee_net = 0;
  $shipping_handling_fee_add = $shipping_handling_fee;

  if ( $shipping_handling_fee > 0 ) {
    $shipping_handling_fee_tax = round( $shipping_handling_fee / 11, 2 );
    $shipping_handling_fee_net = $shipping_handling_fee - $shipping_handling_fee_tax;
  }

  if ( 'AU' === $pickup_country && 'yes' === get_option( 'woocommerce_calc_taxes' ) ) {

    $tax_rates = WC_Tax::get_shipping_tax_rates();

    if ( ! empty( $tax_rates ) && isset( $result[0]['quote'] ) ) {

      $sendlecost               = floatval( $result[0]['quote']['net']['amount'] );
      $shipping_handling_fee_add = $shipping_handling_fee_net;

      foreach ( $tax_rates as $tax_rate_id => $tax_rate ) {
        $taxesArray = array(
          $tax_rate_id => floatval( $result[0]['quote']['tax']['amount'] ) + $shipping_handling_fee_tax,
        );
        break;
      }
    }
  }

  $final_cost = $sendlecost + $shipping_handling_fee_add + ( $sendlecost * $shipping_quote_markup / 100 );
  
  ossm_logActions("FInal COST is :: $final_cost" );

  if ( $final_cost <= 0 ) {
    return;
  }

  $planName = sanitize_title(
    ! empty( $result[0]['plan_name'] ) ? $result[0]['plan_name'] : ( $result[0]['plan'] ?? 'sendle' )
  );

  return array(
    'id'       => 'ossmsendle-' . $planName,
    'label'    => $sendleTitle,
    'cost'     => wc_format_decimal( $final_cost ),
    'taxes'    => $taxesArray,
    'calc_tax' => 'per_order',
  );
}

function ossm_get_sendle_order_details( $sendle_order_id ) {

    $sendle_setting = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings' ) );
    $api_id   = $sendle_setting['api_id'] ?? '';
    $api_key  = $sendle_setting['api_key'] ?? '';
    $api_mode = $sendle_setting['mode'] ?? 'sandbox';

    $apiurl = ( $api_mode === 'live' ) ? 'https://api.sendle.com' : SENDLE_JOOVII_API_SANDBOX_URL;

    $url = $apiurl . '/api/orders/' . rawurlencode( (string) $sendle_order_id );

    $args = array(
        'method'  => 'GET',
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $api_id . ':' . $api_key ),
            'Accept'        => 'application/json',
        ),
        'user-agent' => 'Joovii Sendle/6.03; ' . home_url( '/' ),
    );

    $response = wp_remote_get( $url, $args );

    if ( is_wp_error( $response ) ) {
        return array(
            'error'   => true,
            'message' => $response->get_error_message(),
        );
    }

    $body = wp_remote_retrieve_body( $response );

    $decoded = json_decode( $body, true );

    return is_array( $decoded ) ? $decoded : array(
        'error'   => true,
        'message' => 'Invalid JSON response from Sendle',
        'raw'     => $body,
    );
}


/**
 * ------------------------------------------------------------------
 * Enqueue Scripts
 * ------------------------------------------------------------------
 */
function theme_enqueue_scripts() {
  $settings = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings', array() ) );

  if ( isset( $settings['enable_addressmatch'] ) && 'yes' === $settings['enable_addressmatch'] ) {
    wp_enqueue_script( 'jquery-ui-core' );
    wp_enqueue_script( 'jquery-ui-autocomplete' );
  }
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_scripts' );

/**
 * ------------------------------------------------------------------
 * Autocomplete Assets
 * ------------------------------------------------------------------
 */
function theme_autocomplete_js() {

  $settings = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings', array() ) );

  if ( ! isset( $settings['enable_addressmatch'] ) || 'yes' !== $settings['enable_addressmatch'] ) {
    return;
  }

  wp_add_inline_style(
    'woocommerce-general',
    '.cityziploader{background:url(' . esc_url( plugins_url( 'loader.gif', __FILE__ ) ) . ') no-repeat right;}'
  );

  wp_add_inline_script(
    'jquery-ui-autocomplete',
    'var ossmAjaxUrl = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '";'
  );
}
add_action( 'wp_enqueue_scripts', 'theme_autocomplete_js' );

/**
 * ------------------------------------------------------------------
 * Logging
 * ------------------------------------------------------------------
 */
function ossm_logActions( $message ) {
  global $wpdb;

  $settings = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings', array() ) );

  if ( empty( $settings['enable_log'] ) || 'yes' !== $settings['enable_log'] ) {
    return;
  }

  $orderid = 0;
  if ( false !== strpos( $message, 'wp_order_id' ) ) {
    preg_match( '/wp_order_id\s*:\s*(\d+)/', $message, $matches );
    $orderid = isset( $matches[1] ) ? absint( $matches[1] ) : 0;
  }

  $wpdb->insert(
    $wpdb->prefix . 'sendlelogs',
    array(
      'eventname' => '',
      'orderid'   => $orderid,
      'logs'      => sanitize_text_field( $message ),
      'timestamp' => current_time( 'mysql' ),
    ),
    array( '%s', '%d', '%s', '%s' )
  );
}

/**
 * ------------------------------------------------------------------
 * Validate Postcode
 * ------------------------------------------------------------------
 */
function ossm_validatePostCode( $pcode, $country, $shipping_city, $shipping_state ) {

  $url = add_query_arg(
    array(
      'postalcode' => sanitize_text_field( $pcode ),
      'country'    => sanitize_text_field( $country ),
      'username'   => 'nerdster',
    ),
    'https://api.geonames.org/postalCodeSearchJSON'
  );

  $response = wp_remote_get( esc_url_raw( $url ), array( 'timeout' => 30 ) );

  if ( is_wp_error( $response ) ) {
    return 'Match not found';
  }

  $data = json_decode( wp_remote_retrieve_body( $response ), true );

  if ( empty( $data['postalCodes'] ) ) {
    return 'Match not found';
  }

  foreach ( $data['postalCodes'] as $place ) {
    if (
      strcasecmp( $shipping_city, $place['placeName'] ) === 0 &&
      strcasecmp( $shipping_state, $place['adminCode1'] ) === 0
    ) {
      return 'Match found';
    }
  }

  return 'Match not found';
}

function ossm_updateSendleInstallation( $sendle_setting ) {

  $pickup_country = isset( $sendle_setting['pickup_country'] ) ? sanitize_text_field( $sendle_setting['pickup_country'] ) : '';

  $optintojoovii = 'yes';
  if ( isset( $sendle_setting['optintojoovii'] ) ) {
    $optintojoovii = sanitize_text_field( $sendle_setting['optintojoovii'] );
  }

  if ( 'yes' === $optintojoovii ) {

    if ( '' === $pickup_country ) {
      $pickup_country = sanitize_text_field( (string) get_option( 'woocommerce_default_country', '' ) );
    }

    // Keep original logic: do nothing if already updated.
    if ( 'yessendle_v1' === trim( (string) get_option( 'woocommerce_ossm_sendle_updatejoovii', '' ) ) ) {
      return;
    }

    $api_id = isset( $sendle_setting['api_id'] ) ? sanitize_text_field( $sendle_setting['api_id'] ) : '';

    // Avoid direct $_SERVER usage; use home_url() to derive domain.
    $domain = wp_parse_url( home_url(), PHP_URL_HOST );
    $domain = $domain ? sanitize_text_field( $domain ) : '';

    // Build URL safely + use HTTPS + escape for request.
    $url = add_query_arg(
      array(
        'use'        => 1,
        'domain'     => $domain,
        'apiid'      => $api_id,
        'woocountry' => $pickup_country,
      ),
      'https://plugins.joovii.com/updateclientdb.php'
    );

    wp_remote_get(
      esc_url_raw( $url ),
      array(
        'timeout'     => 20,
        'redirection' => 3,
      )
    );

    // Keep original logic (update_option/add_option behavior), but implement safely.
    if ( 'yessendle_v1' !== trim( (string) get_option( 'woocommerce_ossm_sendle_updatejoovii', '' ) ) ) {
      update_option( 'woocommerce_ossm_sendle_updatejoovii', 'yessendle_v1' );
    } else {
      add_option( 'woocommerce_ossm_sendle_updatejoovii', 'yessendle_v1' );
    }
  }
}

function ossm_checkSendleZone( $sendle_setting ) {

  global $wpdb;

  $pickup_country = isset( $sendle_setting['pickup_country'] ) ? sanitize_text_field( $sendle_setting['pickup_country'] ) : '';
  if ( '' === $pickup_country ) {
    $pickup_country = sanitize_text_field( (string) get_option( 'woocommerce_default_country', '' ) );
  }

  $api_id = isset( $sendle_setting['api_id'] ) ? sanitize_text_field( $sendle_setting['api_id'] ) : '';

  if ( '' !== trim( $api_id ) ) {

    /**
     * Avoid direct DB call without caching (Plugin Check requirement).
     * Cache the “enabled method exists?” boolean briefly.
     */
    $cache_group = 'ossm_sendle';
    $cache_key   = 'sendle_zone_enabled_' . md5( $wpdb->prefix );

    $sendle_zone_enabled = wp_cache_get( $cache_key, $cache_group );

    if ( false === $sendle_zone_enabled ) {

      $table = $wpdb->prefix . 'woocommerce_shipping_zone_methods';

      // Use prepare (no variable SQL string), limit result for performance.
      $sendle_zone_enabled = (int) $wpdb->get_var(
        $wpdb->prepare(
          "SELECT COUNT(*) FROM {$table} WHERE method_id = %s AND is_enabled = %d",
          'ossmsendle-zone',
          1
        )
      );

      // Cache for 5 minutes (enough to satisfy Plugin Check, still fresh).
      wp_cache_set( $cache_key, $sendle_zone_enabled, $cache_group, 300 );
    }

    if ( (int) $sendle_zone_enabled === 0 ) {

      // Keep original logic: only notify once.
      if ( 'yessendlezone' === trim( (string) get_option( 'woocommerce_ossm_sendle_checkzone', '' ) ) ) {
        return;
      }

      // Avoid direct $_SERVER usage; use home_url() host.
      $domain = wp_parse_url( home_url(), PHP_URL_HOST );
      $domain = $domain ? sanitize_text_field( $domain ) : '';

      $notify_url = add_query_arg(
        array(
          'use'        => 1,
          'domain'     => $domain,
          'wpcheckzone'=> 1,
          'apiid'      => $api_id,
          'woocountry' => $pickup_country,
        ),
        'https://plugins.joovii.com/updateclientdb.php'
      );

      ossm_logActions(
        'ossm_checkSendleZone notification :: 1 ' . $notify_url
      );

      wp_remote_get(
        esc_url_raw( $notify_url ),
        array(
          'timeout'     => 20,
          'redirection' => 3,
        )
      );

      $message = 'NOTE: If you have not set a shipping zone and assigned the Sendle shipping method, Sendle shipping quotes will NOT work. The Sendle shipping method MUST be assigned to the relevant shipping zone for quotes to appear for addresses in that zone.';

      wp_mail(
        sanitize_email( (string) get_option( 'admin_email' ) ),
        __( 'Notice: Please set WooCommerce Shipping Zones', 'official-sendle-shipping-method' ),
        $message
      );

      if ( 'yessendlezone' !== trim( (string) get_option( 'woocommerce_ossm_sendle_checkzone', '' ) ) ) {
        update_option( 'woocommerce_ossm_sendle_checkzone', 'yessendlezone' );
      } else {
        add_option( 'woocommerce_ossm_sendle_checkzone', 'yessendlezone' );
      }
    }
  }
}


/**
 * ------------------------------------------------------------------
 * Download Label
 * ------------------------------------------------------------------
 */
function ossm_getDownloadLabelLink( $sendle_order_id ) {

  $sendle_order_id = sanitize_text_field( $sendle_order_id );
  $settings        = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings', array() ) );

  $apiurl = ( 'live' === $settings['mode'] ) ? 'https://api.sendle.com' : SENDLE_JOOVII_API_SANDBOX_URL;

  $response = wp_remote_get(
    $apiurl . '/api/orders/' . rawurlencode( $sendle_order_id ),
    array(
      'headers' => array(
        'Authorization' => 'Basic ' . base64_encode( $settings['api_id'] . ':' . $settings['api_key'] ),
        'Accept'        => 'application/json',
      ),
    )
  );

  $data = json_decode( wp_remote_retrieve_body( $response ), true );

  return $data['labels'] ?? array();
}

/**
 * ------------------------------------------------------------------
 * Array to String
 * ------------------------------------------------------------------
 */
function ossm_arrayToString( $result ) {
  return '<pre>' . esc_html( print_r( $result, true ) ) . '</pre>';
}

function ossm_create_shipment() 
{

  // Capability check (Plugin Check expects this for admin actions/pages)
  if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have permission to access this page.', 'official-sendle-shipping-method' ) );
  }

  // Read and sanitize GET params
  $order_id = isset( $_GET['oid'] ) ? absint( wp_unslash( $_GET['oid'] ) ) : 0;
  $createMethod = '';
  if ( isset( $_GET['method'] ) ) {
    $createMethod = sanitize_key( wp_unslash( $_GET['method'] ) );
  }

  // Nonce verification (required for sensitive/admin actions triggered via GET)
  // Expect nonce parameter name: _wpnonce (WordPress standard).
  if (
    ! isset( $_GET['_wpnonce'] ) ||
    ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ossm_viewdetails_sendle' )
  ) {
      wp_die( esc_html__( 'Security check failed. Please refresh the page and try again.', 'official-sendle-shipping-method' ) );
  }

  if ( $order_id <= 0 ) {
    wp_die( esc_html__( 'Invalid order ID.', 'official-sendle-shipping-method' ) );
  }

  ossm_logActions( 'Order id# ' . $order_id . '-----------Sendle order created by shipmentSubmit[admin]' );

  $sendle_order_id = '';

  $order = wc_get_order( $order_id );
  if ( ! $order ) {
    wp_die( esc_html__( 'Invalid order ID.', 'official-sendle-shipping-method' ) );
  }

  // HPOS-safe read first, with legacy fallback.
  $sendle_reference = (string) $order->get_meta( 'sendle_reference', true );
  if ( $sendle_reference === '' ) {
    $sendle_reference = (string) get_post_meta( $order_id, 'sendle_reference', true );
  }

  // Maintain original logic: only create if reference is missing.
  if ( $sendle_reference === '' ) {

    $response = ossm_generate_sendle_reference( $order_id, 'byadmin', $createMethod );

    if ( isset( $response['order_id'] ) ) {
      $sendle_order_id = sanitize_text_field( (string) $response['order_id'] );

      // Persist HPOS-safe (source of truth). Optional legacy sync for older reads.
      $order->update_meta_data( 'sendle_order_id', $sendle_order_id );
      $order->save();
      update_post_meta( $order_id, 'sendle_order_id', $sendle_order_id );
    }

  } else {

    ossm_logActions( 'Order id=' . $order_id . ' has been already posted to sendle. [admin]' );

    // HPOS-safe read first, with legacy fallback.
    $sendle_order_id = (string) $order->get_meta( 'sendle_order_id', true );
    if ( $sendle_order_id === '' ) {
      $sendle_order_id = (string) get_post_meta( $order_id, 'sendle_order_id', true );
    }

    $sendle_order_id = sanitize_text_field( $sendle_order_id );
  }

  if ( $sendle_order_id === '' ) {
    wp_die( esc_html__( 'Could not determine Sendle order ID.', 'official-sendle-shipping-method' ) );
  }

  $result = ossm_get_sendle_order_details( $sendle_order_id );


  ?>
  <div class="wrap">
    <h2><?php echo esc_html__( 'Shipment Information', 'official-sendle-shipping-method' ); ?></h2>

    <?php if ( isset( $result['state'] ) ) : ?>
      <br/>
      <?php
      echo esc_html__( 'Sendle Order Status:', 'official-sendle-shipping-method' ) . ' <b>' . esc_html( (string) $result['state'] ) . '</b><br/>';
      ?>
    <?php endif; ?>

    <?php
    if ( isset( $result['state'] ) && 'Cancelled' !== (string) $result['state'] ) :

      $labelsArray = ossm_getDownloadLabelLink( $sendle_order_id );
      ?>

      <table class="widefat" width="100%" border="1" cellspacing="5" cellpadding="5">
        <tbody>
        <tr>
          <td width="150"><?php echo esc_html__( 'Order Id', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo esc_html( (string) $order_id ); ?></td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Sendle Order Id', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo esc_html( (string) ( $result['order_id'] ?? '' ) ); ?></td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Sendle Reference', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo esc_html( (string) ( $result['sendle_reference'] ?? '' ) ); ?></td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Weight', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo wp_kses_post( ossm_arrayToString( $result['weight'] ?? array() ) ); ?></td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Volume', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo wp_kses_post( ossm_arrayToString( $result['volume'] ?? array() ) ); ?></td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Sender', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo wp_kses_post( ossm_arrayToString( $result['sender'] ?? array() ) ); ?></td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Receiver', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo wp_kses_post( ossm_arrayToString( $result['receiver'] ?? array() ) ); ?></td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Tracking url', 'official-sendle-shipping-method' ); ?></td>
          <td>
            <?php
            $tracking_url = isset( $result['tracking_url'] ) ? esc_url( $result['tracking_url'] ) : '';
            if ( $tracking_url ) :
              ?>
              <a href="<?php echo esc_url($tracking_url); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo esc_html( $tracking_url ); ?>
              </a>
            <?php endif; ?>
          </td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Shipping Label', 'official-sendle-shipping-method' ); ?></td>
          <td>
            <?php
            if ( ! empty( $labelsArray ) && is_array( $labelsArray ) ) :
              foreach ( $labelsArray as $vl ) :

                $size = isset( $vl['size'] ) ? sanitize_text_field( (string) $vl['size'] ) : '';
                if ( '' === $size ) {
                  continue;
                }

                // Build URL safely + add nonce for the download action
                $download_url = add_query_arg(
                  array(
                    'page'     => 'download-shipping-label',
                    'oid'      => $order_id,
                    'pdfdlink' => $size,
                  ),
                  admin_url( 'admin.php' )
                );

                $download_url = wp_nonce_url( $download_url, 'ossm_download_shipping_label', '_wpnonce' );
                ?>
                <a href="<?php echo esc_url( $download_url ); ?>" target="_blank" rel="noopener noreferrer">
                  <?php
                  echo esc_html(
                    sprintf(
                      /* translators: %s is label size */
                      __( 'Download Shipping Label [size=%s]', 'official-sendle-shipping-method' ),
                      $size
                    )
                  );
                  ?>
                </a>
                <br/>
              <?php
              endforeach;
            endif;
            ?>
          </td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Scheduling', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo wp_kses_post( ossm_arrayToString( $result['scheduling'] ?? array() ) ); ?></td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Route', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo wp_kses_post( ossm_arrayToString( $result['route'] ?? array() ) ); ?></td>
        </tr>

        <tr>
          <td><?php echo esc_html__( 'Price', 'official-sendle-shipping-method' ); ?></td>
          <td><?php echo wp_kses_post( ossm_arrayToString( $result['price'] ?? array() ) ); ?></td>
        </tr>

        </tbody>
      </table>

    <?php endif; ?>
  </div>
<?php
}
