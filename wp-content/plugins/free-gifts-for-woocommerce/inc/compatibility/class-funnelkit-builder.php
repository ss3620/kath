<?php

/**
 * Compatibility - FunnelKit Builder for WooCommerce
 * 
 * @since 11.7.0
 * @tested up to 3.6.2
 * @pluginauthor Funnel Kit
 * @authorurl https://funnelkit.com
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Funnelkit_Builder_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 11.7.0
	 */
	class FGF_Funnelkit_Builder_Compatibility extends FGF_Compatibility {

		/**
		 * Template file call by notice template.
		 * 
		 * @since 11.7.0
		 */
		public static $call_by_notice_template=false;

		/**
		 * Class Constructor.
		 * 
		 * @since 11.7.0
		 */
		public function __construct() {
			$this->id = 'funnelkit_builder';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 * @since 11.7.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('WFFN_Core');
		}

		/**
		 * Frontend Action.
		 * 
		 * @since 11.7.0
		 */
		public function frontend_action() {
			// Set call by notice template properties.
			add_filter('fgf_notice_arguments', array( $this, 'set_call_by_notice_template' ), 10, 1);
			// Stop override notice templates by Funnel Kit builder.
			add_filter('wfacp_override_notices_templates', array( $this, 'stop_override_notice_templates' ), 10, 2);
		}

		/**
		 * Set call by notice template properties.
		 * 
		 * @since 11.7.0
		 */
		public function set_call_by_notice_template( $notices ) {
			self::$call_by_notice_template=true;

			return $notices;
		}

		/**
		 * Stop override notice templates by Funnel Kit builder.
		 * 
		 * Which will solve the notices not displayed on the checkout page.
		 * 
		 * @since 11.7.0
		 */
		public function stop_override_notice_templates( $template_names, $template_name ) {
			self::$call_by_notice_template=false;
			
			return array();
		}
	}

}
