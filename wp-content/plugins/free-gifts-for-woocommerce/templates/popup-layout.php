<?php
/**
 * This template displays the contents popup layout.
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/popup-layout.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<div class="fgf_hide">
	<div class="fgf-popup-gift-products-wrapper woocommerce" id="fgf_gift_products_modal">
		<?php
		fgf_get_template( $data_args[ 'template' ] , $data_args ) ;
		?>
	</div>
</div>
<?php

