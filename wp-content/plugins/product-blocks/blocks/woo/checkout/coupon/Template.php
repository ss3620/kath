<?php
defined( 'ABSPATH' ) || exit;

if ( ! wc_coupons_enabled() ) { // @codingStandardsIgnoreLine.
	return;
}
?>

<div class="wopb-coupon-section">
	<div class="woocommerce-notices-wrapper"></div>
	<?php if ( $attr['showToggle'] ) { ?>
		<div class="wopb-toggle-header">
			<span class="wopb-toggle-text"><?php echo esc_html( $attr['toggleText'] ); ?></span>
			<button type="button" class="wopb-toggle-btn"></button>
		</div>
	<?php } ?>
	<div class="wopb-coupon-form checkout_coupon woocommerce-form-coupon" >
		<div class="wopb-coupon-body">
			<div class="wopb-coupon-title"><?php printf( esc_html( $attr['couponTitleText'] ) ); ?></div>
			<?php echo ( $attr['titlePosition'] == 'aboveCoupon' ? '<br>' : '' ); ?>
			<input type="text" name="coupon_code" id="coupon_code" class="input-text wopb-coupon-code" value="" placeholder="<?php echo esc_html( $attr['couponInputPlaceholder'] ); ?>"/>
			<?php echo ( $attr['applyBtnPosition'] == 'belowCoupon' ? '<br>' : '' ); ?>
			<button class="button wopb-checkout-coupon-submit-btn " wopbPageType="<?php echo is_cart() ? 'cart' : 'checkout'; ?>" name="apply_coupon" value="<?php echo esc_attr( $attr['couponBtnText'] ); ?>"><?php echo esc_html( $attr['couponBtnText'] ); ?></button>
		</div>
	</div>
</div>
