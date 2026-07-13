<?php
/**
 * @author      Wployalty (Ilaiyaraja)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */
namespace Wlac\App\Compatibility;

use Wlac\App\Helpers\Woocommerce;

defined("ABSPATH") or die();

class Villa implements Currency
{
    /**
     * Instance key for object.
     *
     * @var null
     */
    public static $instance = null;

    /**
     * Get Instance Object.
     *
     * @param array $config
     * @return self|null
     */
    public static function getInstance($config = array())
    {
        return (!self::$instance) ? new self($config) : self::$instance;
    }

    /**
     * Get default product price.
     *
     * @param $product_price
     * @param $product
     * @param $item
     * @param $is_redeem
     * @param $order_currency
     * @return mixed
     */
    function getDefaultProductPrice($product_price, $product, $item, $is_redeem, $order_currency)
    {
        return $product_price;
    }

    /**
     * Get product price.
     *
     * @param $product_price
     * @param $item
     * @param $is_redeem
     * @param $order_currency
     * @return float
     */
    function getProductPrice($product_price, $item, $is_redeem, $order_currency)
    {
        if (empty($order_currency)) {
            $order_currency = $this->getCurrentCurrencyCode($order_currency);
        }
        $default_currency = $this->getDefaultCurrency();
        if ($order_currency == $default_currency) {
            return $product_price;
        }
        if (!empty($order_currency)) {
            return $this->convertToDefaultCurrency($product_price, $order_currency);
        }
        return $product_price;
    }

    /**
     * Get current currency code.
     *
     * @param string $code Currency code.
     * @return bool|mixed|string
     */
    function getCurrentCurrencyCode($code = '')
    {
        $setting = self::getCurrencySettingObject();
        if ($setting === null) {
            return $code;
        }
        return $setting->get_current_currency();
    }

    /**
     * Get default currency code.
     *
     * @param string $code Currency code.
     * @return mixed|string
     */
    function getDefaultCurrency($code = '')
    {
        $setting = self::getCurrencySettingObject();
        if ($setting === null) {
            return $code;
        }
        return $setting->get_default_currency();
    }

    /**
     * Convert amount into default currency.
     *
     * @param int|float $amount Currency amount.
     * @param string $current_currency_code Currency code.
     * @return float
     */
    function convertToDefaultCurrency($amount, $current_currency_code)
    {
        $default_currency = $this->getDefaultCurrency();
        if (!empty($default_currency) && $default_currency == $current_currency_code) {
            return $amount;
        }
        $setting = self::getCurrencySettingObject();
        if ($setting === null) {
            return $amount;
        }
	    $setting = self::getCurrencySettingObject();
	    $selected_currencies = $setting->get_list_currencies();
	    $rate = isset( $selected_currencies[ $current_currency_code ] ) && !empty($selected_currencies[ $current_currency_code ]['rate'])  ? $selected_currencies[ $current_currency_code ]['rate']:1;
	    return $rate > 0 ? (float)$amount/$rate: (float)wmc_revert_price($amount, $current_currency_code);
    }

    /**
     * Convert to current currency amount.
     *
     * @param int|float $original_amount Original amount.
     * @param string $default_currency Currency value.
     * @return float
     */
    function convertToCurrentCurrency($original_amount, $default_currency)
    {
        $setting = self::getCurrencySettingObject();
        if ($setting === null) {
            return $original_amount;
        }
        $current_currency_code = $this->getCurrentCurrencyCode();
        return (float)wmc_get_price($original_amount, $current_currency_code);
    }

    /**
     * Convert order total.
     *
     * @param $total
     * @param $order
     * @return float|int
     */
    function convertOrderTotal($total, $order)
    {
        $woocommerce_helper = Woocommerce::getInstance();
        $order = $woocommerce_helper->getOrder($order);
        $order_currency = $woocommerce_helper->isMethodExists($order, 'get_currency') ? $order->get_currency() : '';
        if (!empty($order_currency)) {
            return $this->convertToDefaultCurrency($total, $order_currency);
        }
        return $total;
    }

    /**
     * Get cart subtotal.
     *
     * @param $sub_total
     * @param $cart_data
     * @return float|int
     */
    function getCartSubtotal($sub_total, $cart_data)
    {
        $current_currency = $this->getCurrentCurrencyCode();
        return $this->convertToDefaultCurrency($sub_total, $current_currency);
    }

    /**
     * Get order subtotal value.
     *
     * @param $sub_total
     * @param $order_data
     * @return float
     */
    function getOrderSubtotal($sub_total, $order_data)
    {
        $woocommerce_helper = Woocommerce::getInstance();
        $order = $woocommerce_helper->getOrder($order_data);
        $order_currency = $woocommerce_helper->isMethodExists($order, 'get_currency') ? $order->get_currency() : '';
        if (!empty($order_currency)) {
            return $this->convertToDefaultCurrency($sub_total, $order_currency);
        }
        return $sub_total;
    }

    /**
     * Get the currency setting object.
     *
     * @return object|null The currency setting object or null if not found.
     */
    static function getCurrencySettingObject()
    {
        if (class_exists('\WOOMULTI_CURRENCY_F_Data')) {
            return new \WOOMULTI_CURRENCY_F_Data();
        } elseif (class_exists('\WOOMULTI_CURRENCY_Data')) {
            return new \WOOMULTI_CURRENCY_Data();
        }
        return null;
    }

    /**
     * Get price format.
     *
     * @param int|float $amount Price amount.
     * @param string $code Currency code.
     * @return false|string
     */
    function getPriceFormat($amount, $code = '')
    {
        if (empty($code)) {
            return false;
        }
        $setting = self::getCurrencySettingObject();
        if ($setting === null) {
            return $amount;
        }
        $selected_currencies = $setting->get_list_currencies();
        $currency = isset($selected_currencies[$code]) && is_array($selected_currencies[$code]) ? $selected_currencies[$code] : array();
        if (empty($currency)) {
            return $amount;
        }
        $currency_symbol = $this->getCurrencySymbol($currency, $code);
        $num_decimal = is_array($currency) && !empty($currency['decimals']) ? $currency['decimals'] : wc_get_price_decimals();
        $decimal_sep = wc_get_price_decimal_separator();
        $thousand_sep = wc_get_price_thousand_separator();

        $amount = number_format($amount, $num_decimal, $decimal_sep, $thousand_sep);
        $price_format = $this->getFormat($currency, $code);
        $formatted_price = sprintf($price_format, '<span class="woocommerce-Price-currencySymbol">' . $currency_symbol . '</span>', $amount);
        return '<span class="woocommerce-Price-amount amount"><bdi>' . $formatted_price . '</bdi></span>';
    }

    /**
     * Get currency symbol.
     *
     * @param array $current_currency Currency data
     * @param string $code Currency code.
     * @return mixed
     */
    protected function getCurrencySymbol($current_currency, $code)
    {
        $woocommerce_helper = new Woocommerce();
        if (!is_array($current_currency)) {
            return $woocommerce_helper->getCurrencySymbols($code);
        }
        if (isset($current_currency['custom']) && !empty($current_currency['custom'])) {
            return $current_currency['custom'];
        }
        return $woocommerce_helper->getCurrencySymbols($code);
    }

    /**
     * Get price format for currency.
     *
     * @param array $currency Currency data.
     * @param string $code Currency code.
     * @return string
     */
    protected function getFormat($currency, $code = '')
    {
        $format = get_woocommerce_price_format();
        if (empty($code)) {
            return $format;
        }
        if (is_array($currency) && !empty($currency['pos'])) {
            switch ($currency['pos']) {
                case 'left':
                    $format = '%1$s%2$s';
                    break;
                case 'right':
                    $format = '%2$s%1$s';
                    break;
                case 'left_space':
                    $format = '%1$s&nbsp;%2$s';
                    break;
                case 'right_space':
                    $format = '%2$s&nbsp;%1$s';
                    break;
            }
        }
        return $format;
    }
}