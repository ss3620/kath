<?php
defined( 'ABSPATH' ) || exit;

global $product;
$post_id        = $product->get_id();
$title          = $product->get_name();
$titlelink      = get_permalink( $post_id );
$post_thumb_id  = get_post_thumbnail_id( $post_id );
$_sales         = $product ? $product->get_sale_price() : 0;
$_regular       = $product ? $product->get_regular_price() : 0;
$_discount      = ( $_sales && $_regular ) ? round( ( $_regular - $_sales ) / $_regular * 100 ) . '%' : '';
$rating_count   = $product ? $product->get_rating_count() : 0;
$rating_average = $product ? $product->get_average_rating() : 0;
