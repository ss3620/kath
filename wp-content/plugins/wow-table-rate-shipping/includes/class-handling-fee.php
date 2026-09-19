<?php // phpcs:ignore

namespace WTRS\Includes;

use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Handling fee class.
 */
class HandlingFee {
	/**
	 * Add the custom handling fee to the WooCommerce cart.
	 *
	 * @param array    $hf_config Handling fee config.
	 * @param \WC_Cart $cart The WooCommerce cart object.
	 */
	public static function add_handling_fee_to_cart( $hf_config, $cart ) {

		if ( Flags::HANDLING_FEE_PRO && ! Flags::is_pro_or_can_preview() ) {
			return;
		}

		if ( true !== ( $hf_config['isEnabled'] ?? false ) || empty( $hf_config['amount'] ) ) {
			return;
		}

		$calculated_fee = 0;
		$apply_to       = $hf_config['applyTo'] ?? 'entire_order';
		$type           = $hf_config['type'] ?? 'fixed';
		$amount         = floatval( $hf_config['amount'] );
		$label          = $hf_config['label'] ?? '';
		$min_amount     = self::get_optional_amount( $hf_config['minAmount'] ?? '' );
		$max_amount     = self::get_optional_amount( $hf_config['maxAmount'] ?? '' );
		$is_taxable     = $hf_config['isTaxable'] ?? false;

		switch ( $apply_to ) {
			case 'entire_order':
				$cart_total = $cart->get_subtotal();
				if ( 'fixed' === $type ) {
					$calculated_fee = $amount;
				} elseif ( 'percentage' === $type ) {
					$calculated_fee = ( $cart_total * $amount ) / 100;
				}
				break;

			case 'per_item':
				foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
					$item_price    = $cart_item['data']->get_price();
					$item_quantity = $cart_item['quantity'];

					if ( 'fixed' === $type ) {
						$calculated_fee += $amount * $item_quantity;
					} elseif ( 'percentage' === $type ) {
						$calculated_fee += ( ( $item_price * $item_quantity ) * $amount ) / 100;
					}
				}
				break;

			// TODO @samin: Needs integration with carriers to do this.
			case 'per_package':
				break;
		}

		$calculated_fee = max( 0, (float) $calculated_fee );

		if ( null !== $min_amount ) {
			$calculated_fee = max( $calculated_fee, $min_amount );
		}

		if ( null !== $max_amount ) {
			$calculated_fee = min( $calculated_fee, max( $max_amount, 0 ) );
		}

		if ( null !== $min_amount && null !== $max_amount && $max_amount < $min_amount ) {
			$calculated_fee = $min_amount;
		}

		if ( $calculated_fee > 0 ) {
			$cart->add_fee(
				self::get_fee_name( $label ),
				$calculated_fee,
				$is_taxable,
			);
		}
	}

	/**
	 * Get fee name.
	 *
	 * @param string $label Custom fee label.
	 * @return string
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	private static function get_fee_name( $label = '' ) {
		if ( '' === $label ) {
			$label = __( 'Handling Fee', 'wow-table-rate-shipping' );
		}

		if ( Flags::HANDLING_FEE_PRO && Flags::show_pro_preview() ) {
			$label .= ' (PRO)';
		}

		return $label;
	}

	/**
	 * Parse an optional handling fee amount.
	 *
	 * @param mixed $amount Raw amount.
	 * @return float|null
	 */
	private static function get_optional_amount( $amount ) {
		if ( '' === $amount || null === $amount ) {
			return null;
		}

		if ( ! is_numeric( $amount ) ) {
			return null;
		}

		return max( 0, (float) $amount );
	}
}
