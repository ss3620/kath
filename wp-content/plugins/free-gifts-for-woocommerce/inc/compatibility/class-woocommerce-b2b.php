<?php

/**
 * Compatibility - WooCommerce B2B.
 * 
 * @since 9.2
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WooCommerce_B2B_Compatibility')) {

	/**
	 * Class.
	 */
	class FGF_WooCommerce_B2B_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'woocommerce_b2b';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('WooCommerceB2B');
		}

		/**
		 * Admin Action.
		 */
		public function admin_action() {
			// Add the group filters options.
			add_filter('fgf_rule_user_filter_options', array( $this, 'add_group_filter_options' ), 10, 1);
			// Render the group filters settings.
			add_action('fgf_after_rule_user_filters_settings', array( $this, 'render_group_filter_settings' ), 10, 1);
			// Save the group filters settings.
			add_action('fgf_after_created_new_rule', array( $this, 'save_group_filter_settings' ), 10, 2);
			add_action('fgf_after_updated_rule', array( $this, 'save_group_filter_settings' ), 10, 2);
		}

		/**
		 * Front end action.
		 */
		public function frontend_action() {
			// Validate the user groups.
			add_filter('fgf_validate_rule_users', array( $this, 'validate_user_groups' ), 10, 2);
		}

		/**
		 * Add the group filter options.
		 * 
		 * @param array $options
		 * @return array
		 */
		public static function add_group_filter_options( $options ) {
			if (!fgf_check_is_array($options)) {
				return $options;
			}

			$options['b2b_inculde_groups'] = __('Include Group(s)', 'free-gifts-for-woocommerce');
			$options['b2b_exculde_groups'] = __('Exclude Group(s)', 'free-gifts-for-woocommerce');

			return $options;
		}

		/**
		 * Render the group filter settings. 
		 * 
		 * @param type $rule_data
		 */
		public static function render_group_filter_settings( $rule_data ) {
			$groups = wcb2b_get_groups()->posts;
			$rule_data['fgf_b2b_include_groups'] = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_b2b_include_groups', true)) : array();
			$rule_data['fgf_b2b_exclude_groups'] = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_b2b_exclude_groups', true)) : array();

			include_once FGF_ABSPATH . 'inc/compatibility/views/html-rule-group-filters.php' ;
		}

		/**
		 * Save the group filter settings.
		 * 
		 * @param int $rule_id
		 * @param array $rule_post_data
		 */
		public static function save_group_filter_settings( $rule_id, $rule_post_data ) {
			if (!$rule_id) {
				return;
			}

			$include_groups = isset($_REQUEST['fgf_rule']['fgf_b2b_include_groups']) ? wc_clean(wp_unslash(( $_REQUEST['fgf_rule']['fgf_b2b_include_groups'] ))) : array();
			$exclude_groups = isset($_REQUEST['fgf_rule']['fgf_b2b_exclude_groups']) ? wc_clean(wp_unslash(( $_REQUEST['fgf_rule']['fgf_b2b_exclude_groups'] ))) : array();

			update_post_meta($rule_id, 'fgf_b2b_include_groups', $include_groups);
			update_post_meta($rule_id, 'fgf_b2b_exclude_groups', $exclude_groups);
		}

		/**
		 * Validate the user groups.
		 * 
		 * @param bool $bool
		 * @param object $rule
		 * @return bool
		 */
		public static function validate_user_groups( $bool, $rule ) {
			if (!is_object($rule)) {
				return $bool;
			}

			switch ($rule->get_user_filter_type()) {
				case 'b2b_inculde_groups':
					$bool = false;
					$selected_groups = array_filter((array) get_post_meta($rule->get_id(), 'fgf_b2b_include_groups', true));
					if (is_user_logged_in() && wcb2b_has_role(get_current_user_id(), 'customer')) {
						$group_id = get_the_author_meta('wcb2b_group', get_current_user_id());
						if (in_array($group_id, $selected_groups)) {
							$bool = true;
						}
					}

					break;

				case 'b2b_exculde_groups':
					$selected_groups = array_filter((array) get_post_meta($rule->get_id(), 'fgf_b2b_exclude_groups', true));
					if (is_user_logged_in() && wcb2b_has_role(get_current_user_id(), 'customer')) {
						$group_id = get_the_author_meta('wcb2b_group', get_current_user_id());
						if (in_array($group_id, $selected_groups)) {
							$bool = false;
						}
					}
					break;
			}

			return $bool;
		}
	}

}
