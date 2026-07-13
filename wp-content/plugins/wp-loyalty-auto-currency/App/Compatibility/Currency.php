<?php

namespace Wlac\App\Compatibility;
interface Currency
{
    function getDefaultProductPrice($product_price, $product, $item, $is_redeem, $order_currency);

    function getProductPrice($product_price, $item, $is_redeem, $order_currency);

    function convertToDefaultCurrency($amount, $current_currency_code);

    function getCurrentCurrencyCode($code = '');

    function convertOrderTotal($total, $order);

    function getCartSubtotal($sub_total, $cart_data);

    function getOrderSubtotal($sub_total, $order_data);

    function getDefaultCurrency($code = '');

    function getPriceFormat($amount, $code);
}