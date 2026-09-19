<?php

/**
 * Compatibility - WooCommerce Points and Rewards.
 * 
 * @since 9.7.0
 * @link https://woocommerce.com/products/woocommerce-points-and-rewards/
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WC_Points_Rewards_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 9.7.0
	 */
	class FGF_WC_Points_Rewards_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 * 
		 * @since 9.7.0
		 */
		public function __construct() {
			$this->id = 'wc_points_rewards';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 * @since 9.7.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('WC_Points_Rewards');
		}

		/**
		 * Admin Action.
		 * 
		 * @since 9.7.0
		 */
		public function admin_action() {
			// Add the points and rewards option in the rule criteria total type options.
			add_filter('fgf_rule_criteria_total_type_options', array( $this, 'add_custom_criteria_total_type_option' ), 20, 1);
		}

		/**
		 * Frontend Action.
		 * 
		 * @since 9.7.0
		 */
		public function frontend_action() {
			// May be alter the cart criteria total based on selection.
			add_action('fgf_rule_cart_criteria_total', array( $this, 'maybe_alter_cart_criteria_total' ), 100, 2);
			// May be alter the total price.
			add_action('fgf_rule_total_price', array( $this, 'maybe_alter_subtotal_rule_total' ), 100, 2);
			// May be alter the cart criteria total based on selection.
			add_action('fgf_rule_bulk_pricing_cart_total', array( $this, 'maybe_alter_cart_total' ), 100, 2);
		}

		/**
		 * Add the points and rewards option in the rule criteria total type options.
		 * 
		 * @since 9.7.0
		 * @param array $options
		 * @return array
		 */
		public function add_custom_criteria_total_type_option( $options ) {
			$options['wc_points'] = __('Points Earned', 'free-gifts-for-woocommerce');

			return $options;
		}

		/**
		 * May be alter the cart criteria total based on selection.
		 * 
		 * @since 9.7.0
		 * @param float $total
		 * @param object $rule
		 * @return float
		 */
		public function maybe_alter_cart_criteria_total( $total, $rule ) {
			// Return if the total type is not a points.
			if ('wc_points' !== $rule->get_total_type()) {
				return $total;
			}

			return WC_Points_Rewards_Manager::get_users_points(get_current_user_id());
		}

		/**
		 * May be alter the subtotal rule total based on selection.
		 * 
		 * @since 12.1.0
		 * @param float $total
		 * @param object $rule
		 * @return float
		 */
		public function maybe_alter_subtotal_rule_total( $total, $rule ) {
			// Return if the total type is not a points.
			if ('wc_points' !== $rule->get_subtotal_price_type()) {
				return $total;
			}

			return WC_Points_Rewards_Manager::get_users_points(get_current_user_id());
		}

		/**
		 * May be alter the cart total based on selection.
		 * 
		 * @since 9.7.0
		 * @param float $total
		 * @param object $rule
		 * @return float
		 */
		public function maybe_alter_cart_total( $total, $rule ) {
			// Return if the total type is not a points.
			if (( in_array($rule->get_total_type(), array( '9', '10' )) && 'wc_points' === $rule->get_bulk_pricing_total_type() ) || 'wc_points' !== $rule->get_total_type()) {
				return WC_Points_Rewards_Manager::get_users_points(get_current_user_id());
			}

			return $total;
		}
	}

}
