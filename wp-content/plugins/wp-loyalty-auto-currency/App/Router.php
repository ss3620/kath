<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wlac\App;

use Wlac\App\Controllers\Site\Main;

defined('ABSPATH') or die;

class Router
{
    private static $site, $admin;

    function init()
    {
        self::$site = empty(self::$site) ? new Main() : self::$site;
        //self::$admin = empty(self::$admin) ? new Main() : self::$admin;
        add_filter('wlr_core_multicurrency_allowed', function ($status) {
            return false;
        });
        if (is_admin()) {
            add_action('admin_menu', 'Wlac\App\Controllers\Site\Main::addMenu');
            add_action('admin_footer', 'Wlac\App\Controllers\Site\Main::menuHide');
            add_action('admin_enqueue_scripts', 'Wlac\App\Controllers\Site\Main::adminScripts', 100);
            add_action('wp_ajax_wlac_save_settings', 'Wlac\App\Controllers\Site\Main::saveSettings');
        }
        add_filter('wlr_default_product_price', 'Wlac\App\Controllers\Site\Main::getDefaultProductPrice', 10, 5);
        add_filter('wlr_product_price', 'Wlac\App\Controllers\Site\Main::getProductPrice', 10, 4);
        /*add_filter('wlr_convert_to_default_currency', array(self::$site, 'convertToDefaultCurrency'), 10, 2);*/
        /*Woocommerce Helper Functions*/
        add_filter('wlr_current_currency', 'Wlac\App\Controllers\Site\Main::getCurrentCurrencyCode');
        //Purchase Last order amount
        add_filter('wlr_get_order_total', 'Wlac\App\Controllers\Site\Main::handleWoocommerceHelperOrderTotal', 10, 2);
        /* Conditions*/
        /*Subtotal Conditions*/
        add_filter('wlr_get_cart_subtotal', 'Wlac\App\Controllers\Site\Main::getCartSubtotal', 10, 2);
        add_filter('wlr_get_order_subtotal',  'Wlac\App\Controllers\Site\Main::getOrderSubtotal', 10, 2);
        /*Life time sale value*/
        //add_filter( 'wlr_life_time_sale_value_order_total', array( self::$site, 'handleConditionOrderTotal' ), 10, 2 );
        //add_filter( 'wlr_purchase_spent_order_total', array( self::$site, 'handleConditionOrderTotal' ), 10, 2 );
        /*Custom price change*/
        add_filter('wlr_custom_default_currency', 'Wlac\App\Controllers\Site\Main::getDefaultCurrency', 10);
        add_filter('wlr_custom_price_convert', 'Wlac\App\Controllers\Site\Main::convertDefaultToCurrentAmount', 10, 4);
        add_filter('wlr_custom_display_currency',  'Wlac\App\Controllers\Site\Main::getDisplayCurrency', 10);
        add_filter('wlr_page_user_reward_list',  'Wlac\App\Controllers\Site\Main::handleActionShortCodes', 10);
        add_filter('wlr_page_reward_list', 'Wlac\App\Controllers\Site\Main::handleRewardShortCodes', 10);
    }
}