<?php

/**
 * This is a view file.
 *
 * It's included from class-wc-zipmoney-payment-gateway-widget.php->render_express_payment_button();
 */
$WC_Zipmoney_Payment_Gateway_Config = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config;

$checkout_url = $WC_Zipmoney_Payment_Gateway_Config->get_checkout_redirect_url();

// Is is iframe flow
$is_iframe_flow = $WC_Zipmoney_Payment_Gateway_Config->is_it_iframe_flow();
if ( $is_iframe_flow ) {
	?>
	<script type="text/javascript">
		;
		(function($) {
			$(document).ready(function() {
				$("#checkout_express_cart").click(function() {
					var vUrl = "<?php echo esc_url( $checkout_url ); ?>";
					var iframe = new iframeCheckout(vUrl, '');
					iframe.redirectToCheckout();
				});
			});
		})(window.jQuery || window.$);
	</script>
<?php } ?>
<div class="zip-express-payment-button">
	<a id="checkout_express_cart" class="zipmoney-strip-banner" zm-widget="inline" zm-asset="productbutton" <?php echo ! $is_iframe_flow ? "href='" . esc_url( $checkout_url ) . "'" : null; ?> style="cursor: pointer"></a>
	<a zm-widget="popup" zm-asset="
	<?php
	if ( is_product() ) {
		echo 'productbuttonlink';
	} elseif ( is_cart() ) {
		echo 'cartbuttonlink';
	}
	?>
									" class="zip-productbuttonlink" zm-popup-asset="termsdialog"></a>
	<span class="please-wait zm-loader" id="redirecting-to-zipmoney" style="display: none ;"><span class="text">Redirecting to zipMoney ...</span></span>
</div>

<script>
	;
	(function($) {
		if (window.$zmJs !== undefined) {
			//select all the elements inside div.zip-express-payment-button to recreate the widget only.
			//otherwise, it will generate multiple banner ads for each ajax request.
			$('div.wc-proceed-to-checkout [zm-widget],[data-zm-widget]').each(function(index, widgetEl) {
				window.$zmJs._createWidget(widgetEl, $zmJs);
			});
		}
	})(window.jQuery || window.$);
</script>
