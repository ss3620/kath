<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wlac\App\Compatibility;

use Wlac\App\Helpers\Woocommerce;

class RealMag implements Currency
{
    public static $instance = null;

    function getDefaultProductPrice($product_price, $product, $item, $is_redeem, $order_currency)
    {
        return $product_price;
    }

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

    function getCurrentCurrencyCode($code = '')
    {
        global $WOOCS;
        return isset($WOOCS->current_currency) ? $WOOCS->current_currency : $code;
    }

    function getDefaultCurrency($code = '')
    {
        global $WOOCS;
        return $WOOCS->default_currency;
    }

    function convertToDefaultCurrency($amount, $current_currency_code)
    {
        $default_currency = $this->getDefaultCurrency();
        if (!empty($default_currency) && $default_currency == $current_currency_code) {
            return $amount;
        }
        global $WOOCS;
        $currencies = $WOOCS->get_currencies();
        $rate = isset($currencies[$current_currency_code]['rate']) && !empty($currencies[$current_currency_code]['rate']) ? $currencies[$current_currency_code]['rate'] : 0;
        $decimal = isset($currencies[$current_currency_code]['decimals']) && !empty($currencies[$current_currency_code]['decimals']) ? $currencies[$current_currency_code]['decimals'] : 2;
        if ($rate > 0) {
            $amount = $WOOCS->back_convert($amount, $rate, $decimal);
        }
        return (float)$amount;
    }

    function convertOrderTotal($total, $order)
    {
        $woocommerce_helper = Woocommerce::getInstance();
        $order_data = $woocommerce_helper->isMethodExists($order, 'get_data') ? $order->get_data() : '';
        $order_currency = !empty($order_data) && is_array($order_data) && isset($order_data['currency']) && !empty($order_data['currency']) ? $order_data['currency'] : '';
        $default_currency = $this->getDefaultCurrency();
        if ($order_currency != $default_currency) {
            $total = $this->convertToDefaultCurrency($total, $order_currency);
        }
        return $total;
    }

    public static function getInstance(array $config = array())
    {
        if (!self::$instance) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    function getCartSubtotal($sub_total, $cart_data)
    {
        $default_currency = $this->getDefaultCurrency();
        $current_currency = $this->getCurrentCurrencyCode();
        if ($default_currency == $current_currency) {
            return $sub_total;
        }
        return $this->convertToDefaultCurrency($sub_total, $current_currency);
    }

    function getOrderSubtotal($sub_total, $order_data)
    {
        $woocommerce_helper = Woocommerce::getInstance();
        $order = $woocommerce_helper->getOrder($order_data);
        $order_data = $woocommerce_helper->isMethodExists($order, 'get_data') ? $order->get_data() : '';
        $order_currency = !empty($order_data) && is_array($order_data) && isset($order_data['currency']) && !empty($order_data['currency']) ? $order_data['currency'] : '';
        if (!empty($order_currency)) {
            return $this->convertToDefaultCurrency($sub_total, $order_currency);
        }
        return $sub_total;
    }

    function convertToCurrentCurrency($original_amount, $default_currency)
    {
        global $WOOCS;
        $currencies = $WOOCS->get_currencies();
        $current_currency_code = $this->getCurrentCurrencyCode();
        if (isset($currencies[$current_currency_code]) && isset($currencies[$current_currency_code]['rate'])) {
            $original_amount = floatval($original_amount) * floatval($currencies[$current_currency_code]['rate']);
        } else {
            $original_amount = $WOOCS->woocs_exchange_value($original_amount);
        }
        return $original_amount;
    }

    function getPriceFormat($amount, $code = '')
    {
        if (empty($code)) {
            return $amount;
        }
        global $WOOCS;
        $currencies = $WOOCS->get_currencies();
        $currency = is_array($currencies) && isset($currencies[$code]) && !empty($currencies[$code]) ? $currencies[$code] : array();
        if (!is_array($currency) || empty($currency)) {
            return $amount;
        }
        $currency_symbol = $this->getCurrencySymbol($currency, $code);
        $num_decimal = is_array($currency) && !empty($currency['decimals']) ? $currency['decimals'] : wc_get_price_decimals();
        $dec_sep = is_array($currency) && !empty($currency['separators']) ? $currency['separators'] : 0;
        $decimal_sep = $WOOCS->get_decimal_sep($dec_sep);
        $thousand_sep = wc_get_price_thousand_separator();
        $amount = number_format($amount, $num_decimal, $decimal_sep, $thousand_sep);
        $price_format = $this->getFormat($currency, $code);
        $formatted_price = sprintf($price_format, '<span class="woocommerce-Price-currencySymbol">' . $currency_symbol . '</span>', $amount);
        return '<span class="woocommerce-Price-amount amount"><bdi>' . $formatted_price . '</bdi></span>';
    }

    protected function getCurrencySymbol($current_currency, $code)
    {
        $woocommerce_helper = new Woocommerce();
        if (!is_array($current_currency)) {
            return $woocommerce_helper->getCurrencySymbols($code);
        }
        if (isset($current_currency['symbol']) && !empty($current_currency['symbol'])) {
            return $current_currency['symbol'];
        }
        return $woocommerce_helper->getCurrencySymbols($code);
    }

    protected function getFormat($currency, $code = '')
    {
        $format = get_woocommerce_price_format();
        if (empty($code)) {
            return $format;
        }
        if (is_array($currency) && !empty($currency['position'])) {
            switch ($currency['position']) {
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