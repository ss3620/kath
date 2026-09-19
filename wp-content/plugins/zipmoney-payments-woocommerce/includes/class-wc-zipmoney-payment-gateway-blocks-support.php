<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

/**
 * Eway payment method integration
 *
 * @since 3.2.0
 */
final class WC_Zipmoney_Payment_Gateway_Blocks_Support extends AbstractPaymentMethodType {
	/**
	 * Name of the payment method.
	 *
	 * @var string
	 */
	protected $name = 'zipmoney';

	private $WC_Zipmoney_Payment_Gateway;

	public function __construct() {
		 require_once plugin_dir_path( __FILE__ ) . '/class-wc-zipmoney-payment-gateway.php';
		$this->WC_Zipmoney_Payment_Gateway = new WC_Zipmoney_Payment_Gateway();
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_zipmoney_settings', array() );

		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			array( $this, 'process_payment_with_context' ),
			10,
			2
		);
	}

	/**
	 * Handles payment processing for the block-based checkout.
	 * Runs before Legacy.php (priority 999) so it takes over the flow.
	 *
	 * @param PaymentContext $context
	 * @param PaymentResult  $result
	 */
	public function process_payment_with_context( PaymentContext $context, PaymentResult &$result ) {
		if ( $context->payment_method !== $this->name ) {
			return;
		}

		$order_id = $context->order->get_id();

		require_once plugin_dir_path( __FILE__ ) . '/controller/class-wc-zipmoney-payment-checkout-controller.php';
		$checkout_controller = new WC_Zip_Controller_Checkout_Controller( $this->WC_Zipmoney_Payment_Gateway );

		try {
			// Pass empty array: in blocks checkout the order already holds all customer data.
			$checkout = $checkout_controller->create_checkout( array(), $order_id );
		} catch ( \zipMoney\ApiException $exception ) {
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getCode() . $exception->getMessage() );
			$result->set_status( 'failure' );
			$result->set_payment_details( array( 'errorMessage' => $exception->getMessage() ) );
			return;
		}

		if ( isset( $checkout['redirect_uri'] ) && $checkout['result'] === 'success' ) {
			$result->set_status( 'success' );
			$result->set_redirect_url( $checkout['redirect_uri'] );
			$payment_details = array( 'checkoutId' => $checkout['checkout_id'] );
			if ( isset( $checkout['token'] ) ) {
				$payment_details['token'] = $checkout['token'];
			}
			$result->set_payment_details( $payment_details );
			return;
		}

		$result->set_status( 'failure' );
		$result->set_payment_details( array( 'errorMessage' => $checkout['message'] ?? __( 'Payment error.', 'zippayment' ) ) );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();

		return $payment_gateways['zipmoney']->is_available();
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$url                = home_url() . '/?p=zipmoneypayment&route=updatesession';
		$payment_method_data = array(
			'title'        => $this->WC_Zipmoney_Payment_Gateway->title,
			'description'  => $this->WC_Zipmoney_Payment_Gateway->description,
			'supports'     => $this->get_supported_features(),
			'tokenisation' => $this->WC_Zipmoney_Payment_Gateway->showSaveAccountInCheckout(),
			'savezip'      => $this->WC_Zipmoney_Payment_Gateway->customerHasToken(),
			'sessionUrl'   => $url,
		);

		return $payment_method_data;
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$asset_path   = WOOCOMMERCE_GATEWAY_ZIPMONEY_PATH . '/build/index.asset.php';
		$version      = WOOCOMMERCE_GATEWAY_ZIPMONEY_VERSION;
		$dependencies = array();
		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = is_array( $asset ) && isset( $asset['version'] )
				? $asset['version']
				: $version;
			$dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
				? $asset['dependencies']
				: $dependencies;
		}
		wp_register_script(
			'wc-payment-method-zipmoney',
			WOOCOMMERCE_GATEWAY_ZIPMONEY_URL . '/build/index.js',
			$dependencies,
			$version,
			true
		);
		wp_set_script_translations(
			'wc-payment-method-zipmoney',
			'zippayment'
		);

		return array( 'wc-payment-method-zipmoney' );
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		return $payment_gateways['zipmoney']->supports;
	}
}
