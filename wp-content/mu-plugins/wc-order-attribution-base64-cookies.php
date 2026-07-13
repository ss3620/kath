<?php
/**
 * Plugin Name: WooCommerce Order Attribution – Base64 Cookies
 * Description: Encodes Order Attribution (sbjs) cookies in Base64 so ModSecurity/COMODO WAF rule 218500 does not false-positive them as SQLi.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'wc_order_attribution_use_base64_cookies', '__return_true' );
