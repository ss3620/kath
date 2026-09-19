<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ossm_sendle_shipping_zone_method() {
    if ( ! class_exists( 'ossm_sendle_shipping_zone_method' ) ) {
        class ossm_sendle_shipping_zone_method extends WC_Shipping_Method {
            public $api_id;
            public $api_key;
            public $pickup_suburb;
            public $pickup_postcode;
            public $plan_name;
            public $mode;
            public $apiurl;

            public function __construct( $instance_id = 0 ) {
                $this->id                 = 'ossmsendle-zone';
                $this->instance_id        = absint( $instance_id );
                $this->method_title       = __( 'Sendle', 'official-sendle-shipping-method' );
                $this->method_description = __( 'Tested and approved by Sendle, this plugin provides the ultimate connectivity between WooCommerce and Sendle.', 'official-sendle-shipping-method' );
                $this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );
                $this->init();
                $title       = $this->get_option( 'title' );
                $this->title = ! empty( $title ) ? $title : __( 'Sendle Shipping', 'official-sendle-shipping-method' );
            }

            public function init() {
                $this->init_settings();
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            public function calculate_shipping( $package = array() ) {
                global $woocommerce;
                $sendle_setting = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings' ) );
                
                if ( ! isset( $sendle_setting['enabled'] ) || 'yes' !== $sendle_setting['enabled'] ) {
                    return;
                }
                if ( ! isset( $sendle_setting['showrates'] ) || 'yes' !== $sendle_setting['showrates'] ) {
                    return;
                }

                $pickupCountry  = isset( $sendle_setting['pickup_country'] ) ? trim( $sendle_setting['pickup_country'] ) : '';
                $pickupSuburb   = isset( $sendle_setting['pickup_suburb'] ) ? trim( $sendle_setting['pickup_suburb'] ) : '';
                $pickupPostcode = isset( $sendle_setting['pickup_postcode'] ) ? trim( $sendle_setting['pickup_postcode'] ) : '';

                if ( empty( $pickupSuburb ) || empty( $pickupPostcode ) ) {
                    return;
                }

                $deliveryCountry = isset( $package['destination']['country'] ) ? trim( $package['destination']['country'] ) : '';
                if ( 'US' === $pickupCountry ) {
                    if ( 'US' !== $deliveryCountry ) {
                        wc_add_notice( __( 'Sendle does not support International Orders sent from the United States yet.', 'official-sendle-shipping-method' ) );
                        return;
                    }
                }

                $maxWeight = ossm_maxWeightLimit( $pickupCountry, $deliveryCountry );
                $maxVolume = ossm_maxVolumeLimit( $pickupCountry, $deliveryCountry );

                $items            = $woocommerce->cart->get_cart();
                $packageArr       = array();
                $cartTotalQuatity = 0;
                $cartTotalweight  = 0;
                $weight           = 0;
                $volume           = 0;

                foreach ( $package['contents'] as $item_id => $values ) {
                    $_product = $values['data'];
                    $weightP  = ossm_getWeight( $_product->get_weight(), $pickupCountry );
                    $weight   = $weight + $weightP * $values['quantity'];
                    $cartTotalQuatity = $cartTotalQuatity + $values['quantity'];
                    if ( $_product->get_weight() > 0 ) {
                        $cartTotalweight = $cartTotalweight + ( $_product->get_weight() * $values['quantity'] );
                    }

                    $volumeP = 0;

                    $dimension_log = 'get_weight/weight [1] : ' . $_product->get_weight() . '--' . $weightP . '<br/>' .
                                     'quantity/weight [2] : ' . $values['quantity'] . '<br/>' .
                                     'weight/weight [3] : ' . $weight . '<br/>' .
                                     'Dimension of Products :: [L]x[W]x[H] = [' . $_product->get_length() . '] x [' . $_product->get_width() .
                                     '] x [' . $_product->get_height() . '] <br/>';

                    // HANDLE product LENGTH
                    $product_length = $_product->get_length();
                    // Take from Settings
                    if ( empty( $product_length ) || 0 === $product_length ) {
                        if ( isset( $sendle_setting['product_default_length'] ) &&
                             ! empty( $sendle_setting['product_default_length'] ) &&
                             0 !== $sendle_setting['product_default_length']
                        ) {
                            $product_length = $sendle_setting['product_default_length'];
                        }
                    }
                    $product_length = ( empty( $product_length ) || '0' === $product_length ) ? 5 : $product_length;

                    // HANDLE product WIDTH
                    $product_width = $_product->get_width();
                    // Take from Settings
                    if ( empty( $product_width ) || 0 === $product_width ) {
                        if ( isset( $sendle_setting['product_default_width'] ) &&
                             ! empty( $sendle_setting['product_default_width'] ) &&
                             0 !== $sendle_setting['product_default_width']
                        ) {
                            $product_width = $sendle_setting['product_default_width'];
                        }
                    }
                    $product_width = ( empty( $product_width ) || '0' === $product_width ) ? 5 : $product_width;

                    // HANDLE product HEIGHT
                    $product_height = $_product->get_height();
                    // Take from Settings
                    if ( empty( $product_height ) || 0 === $product_height ) {
                        if ( isset( $sendle_setting['product_default_height'] ) &&
                             ! empty( $sendle_setting['product_default_height'] ) &&
                             0 !== $sendle_setting['product_default_height']
                        ) {
                            $product_height = $sendle_setting['product_default_height'];
                        }
                    }
                    $product_height = ( empty( $product_height ) || '0' === $product_height ) ? 5 : $product_height;

                    $dimension_log .= 'Converted Dimensions :: ' . $product_length . ' X ' . $product_width . ' X ' . $product_height . '<br/>';

                    if ( isset( $sendle_setting['volume_param'] ) && 'yes' === trim( $sendle_setting['volume_param'] ) ) {

                        if ( $_product->get_length() > 0 ) {
                            $volumnP_l = ossm_getDimension( $_product->get_length(), $pickupCountry );
                        } else {
                            $volumnP_l = 0;
                        }

                        if ( $_product->get_width() > 0 ) {
                            $volumnP_w = ossm_getDimension( $_product->get_width(), $pickupCountry );
                        } else {
                            $volumnP_w = 0;
                        }

                        if ( $_product->get_height() > 0 ) {
                            $volumnP_h = ossm_getDimension( $_product->get_height(), $pickupCountry );
                        } else {
                            $volumnP_h = 0;
                        }

                        $volumeP = ( ( $volumnP_l * $volumnP_w * $volumnP_h ) );
                        $volume += ( $volumeP * $values['quantity'] );

                        $dimension_log .= 'Volume-> :: ' . $volume . ' = ' . $volumnP_l . '--' . $volumnP_w . '--' . $volumnP_h . '<br/>';
                    }

                    $dimension_log .= 'weightP/maxWeight [3-1] : ' . $weightP . ' = ' . $maxWeight . ' - ' . $weight . '<br/>';

                    ossm_logActions( $dimension_log );

                    if ( $weightP > 0 ) {
                        if ( $weightP > $maxWeight ) {
                            if ( isset( $sendle_setting['warningtext_enable'] ) && 'yes' === $sendle_setting['warningtext_enable'] ) {
                                wc_add_notice( esc_html( $sendle_setting['warningtext'] ) );
                            }
                            return;
                        }
                        if ( $volumeP > 0 ) {
                            if ( $volumeP > $maxVolume ) {
                                if ( isset( $sendle_setting['warningtext_enable'] ) && 'yes' === $sendle_setting['warningtext_enable'] ) {
                                    wc_add_notice( esc_html( $sendle_setting['warningtext'] ) );
                                }
                                return;
                            }
                        }
                        for ( $i = 1; $i <= $values['quantity']; $i++ ) {
                            $packageArr[] = array(
                                'w' => ossm_getWeight( $_product->get_weight(), $pickupCountry ),
                                'v' => $volumeP,
                            );
                        }
                    } else {
                        if ( isset( $sendle_setting['warningtext_enable'] ) && 'yes' === $sendle_setting['warningtext_enable'] ) {
                            wc_add_notice( esc_html( $sendle_setting['warningtext'] ) );
                        }
                        return;
                    }
                }

                ossm_logActions( ' volume/weight [4] : ' . $volume . '--' . $weight );
                ossm_logActions( ' Initial cart item Arr : ' . print_r( $packageArr, true ) );
                ossm_logActions( ' maxWeight : ' . $maxWeight );
                ossm_logActions( ' maxVolume : ' . $maxVolume );
                $packageDivisionArr = ossm_weightDistributionArray( $packageArr, $weight, $volume, $maxWeight, $maxVolume );
                ossm_logActions( ' Final package Division Arr : ' . print_r( $packageDivisionArr, true ) );

                if ( $weight > $maxWeight || $volume > $maxVolume ) {

                    if ( $weight > $maxWeight ) {
                        ossm_logActions( ' cart weight > sendle max weight : ' . $weight . '>' . $maxWeight );
                    }
                    if ( $volume > $maxVolume ) {
                        ossm_logActions( ' cart volume > sendle max volume : ' . $volume . '>' . $maxVolume );
                    }
                    $grossAmount = 0;
                    $netAmount   = 0;
                    $taxAmount   = 0;
                    $volume      = 0;
                    foreach ( $packageDivisionArr as $krw => $vrw ) {

                        $weight = $vrw['w'];
                        $volume = $vrw['v'];

                        if ( $volume > $maxVolume ) {
                            $volume = 0;
                        }
                        $urlParam = ossm_createRequestStr( $package, $cartTotalQuatity, $cartTotalweight, $sendle_setting, $weight, $volume, 'no' );
                        ossm_logActions( ' url [' . ( $krw + 1 ) . '] :: ' . $urlParam . '  ' );

                        $resultBuffer = ossm_calculateSendleRate( $package, $sendle_setting, $urlParam );
                        ossm_logActions( ' rate-result [' . ( $krw + 1 ) . '] :: ' . print_r( $resultBuffer, true ) . '  ' );
                        if ( isset( $resultBuffer[0]['quote']['gross']['amount'] ) && $resultBuffer[0]['quote']['gross']['amount'] > 0 ) {

                            $grossAmount = $grossAmount + $resultBuffer[0]['quote']['gross']['amount'];
                            $netAmount   = $netAmount + $resultBuffer[0]['quote']['net']['amount'];
                            $taxAmount   = $taxAmount + $resultBuffer[0]['quote']['tax']['amount'];

                        } else {

                            if ( isset( $sendle_setting['warningtext_enable'] ) && 'yes' === $sendle_setting['warningtext_enable'] ) {
                                wc_add_notice( esc_html( $sendle_setting['warningtext'] ) );
                            }
                            return;
                        }
                    }
                    $result = array(
                        array(
                            'quote'     => array(
                                'gross' => array( 'amount' => $grossAmount ),
                                'net'   => array( 'amount' => $netAmount ),
                                'tax'   => array( 'amount' => $taxAmount ),
                            ),
                            'plan_name' => 'Easy',
                        ),
                    );

                } else {

                    $urlParam = ossm_createRequestStr( $package, $cartTotalQuatity, $cartTotalweight, $sendle_setting, $weight, $volume, 'yes' );
                    ossm_logActions( ' url  :: ' . $urlParam . '  ' );
                    $result = ossm_calculateSendleRate( $package, $sendle_setting, $urlParam );
                }

                ossm_logActions( ' resultArray ---> : ' . print_r( $result, true ) );

                $rate = ossm_createRateArray( $package, $sendle_setting, $result );

                ossm_logActions( 'RATE is ' . print_r( $rate, true ) );

                if ( ! is_array( $result ) ) {
                    ossm_logActions( 'Failed to return any RATE :: Result is not array :: ' . $result );
                    return;
                } else {
                    if ( isset( $result[0]['quote']['gross']['amount'] ) && $result[0]['quote']['gross']['amount'] > 0 ) {
                        ossm_logActions( 'We are adding RATE successfully' );
                        $this->add_rate( $rate );
                    } else {
                        ossm_logActions( 'Failed to return any RATE ' . wp_json_encode( $result ) );
                        return;
                    }
                }
            }
        }
    }
}

add_action( 'woocommerce_shipping_init', 'ossm_sendle_shipping_zone_method' );

function ossm_add_sendle_shipping_zone_method( $methods ) {
    $methods['ossmsendle-zone'] = 'ossm_sendle_shipping_zone_method';
    return $methods;
}

/**
 * Admin UI Script
 */
if ( ! function_exists( 'ossm_my_sendle' ) ) {
    function ossm_my_sendle() {
        wp_add_inline_script(
            'jquery',
            "jQuery('#woocommerce_ossmsendle_process_as_sendle_order').css('height','90px');"
        );
    }
}

add_filter( 'woocommerce_shipping_methods', 'ossm_add_sendle_shipping_zone_method' );
add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_true' );
add_action(
    'admin_init',
    function () {
        if ( function_exists( 'ossm_my_sendle' ) ) {
            add_action( 'in_admin_footer', 'ossm_my_sendle' );
        }
    }
);

add_action( 'wp_enqueue_scripts', 'theme_enqueue_scripts' );
add_action( 'wp_footer', 'theme_autocomplete_js' );
add_action( 'wp_ajax_nopriv_sendlejooviicityziplookup', 'ossm_getcityziplookup' );
add_action( 'wp_ajax_sendlejooviicityziplookup', 'ossm_getcityziplookup' );