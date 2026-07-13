<?php

namespace Wlac\App\Helpers;
defined( "ABSPATH" ) or die();

class Woocommerce {
    public static $instance = null;

    public static function getInstance( array $config = array() ) {
        if ( ! self::$instance ) {
            self::$instance = new self( $config );
        }

        return self::$instance;
    }

    /**
     * @return bool
     */
    public static function hasAdminPrivilege() {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return true;
        } else {
            return false;
        }
    }

    public static function verify_nonce( $nonce, $action = - 1 ) {
        if ( wp_verify_nonce( $nonce, $action ) ) {
            return true;
        } else {
            return false;
        }
    }
    public static function create_nonce( $action = - 1 ) {
        return wp_create_nonce( $action );
    }

    static function validateInputAlpha( $input ) {
        return preg_replace( '/[^A-Za-z0-9_\-]/', '', $input );
    }


    function getOrder( $order = null ) {
        if ( isset( $order ) && is_object( $order ) ) {
            return $order;
        }
        if ( isset( $order ) && is_integer( $order ) && function_exists( 'wc_get_order' ) ) {
            return wc_get_order( $order );
        }

        return null;
    }

    function isMethodExists( $object, $method_name ) {
        if ( is_object( $object ) && method_exists( $object, $method_name ) ) {
            return true;
        }

        return false;
    }
    function getCurrencySymbols( $currency = '' ) {
        if ( empty( $currency ) ) {
            return $currency;
        }
        $symbols = get_woocommerce_currency_symbols();

        return isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';
    }

}