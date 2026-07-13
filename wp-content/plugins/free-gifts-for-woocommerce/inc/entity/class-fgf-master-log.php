<?php

/**
 * Master Log.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Master_Log')) {

	/**
	 * FGF_Master_Log Class.
	 */
	class FGF_Master_Log extends FGF_Post {

		/**
		 * Post Type.
		 * 
		 * @var string
		 */
		protected $post_type = FGF_Register_Post_Types::MASTER_LOG_POSTTYPE;

		/**
		 * Post Status.
		 * 
		 * @var string
		 */
		protected $post_status = 'fgf_manual';

		/**
		 * User ID.
		 * 
		 * @var int
		 */
		protected $user_id;

		/**
		 * Post keys.
		 * 
		 * @since 11.1.0
		 * @var array.
		 * */
		protected $post_keys = array(
			'user_id' => 'post_parent',
		);

		/**
		 * Meta data keys
		 */
		protected $meta_data_keys = array(
			'fgf_rule_ids' => '',
			'fgf_product_details' => '',
			'fgf_user_name' => '',
			'fgf_user_email' => '',
			'fgf_order_id' => '',
			'fgf_type' => '',
		);

		/**
		 * Get the formatted created datetime.
		 * 
		 * @return string
		 */
		public function get_formatted_created_date() {
			return FGF_Date_Time::get_wp_format_datetime($this->get_created_date());
		}

		/**
		 * Get the customer name.
		 * 
		 * @since 10.0.0
		 * @return string
		 */
		public function get_customer_name() {
			$customer_name = $this->get_user_name();
			if ($this->get_user_email()) {
				$customer_name .= ' (' . $this->get_user_email() . ')';
			}

			return $customer_name;
		}

		/**
		 * Setters and Getters
		 */

		/**
		 * Set user ID.
		 */
		public function set_user_id( $value ) {
			$this->set_post_prop('user_id', $value);
		}

		/**
		 * Set rule IDs.
		 */
		public function set_rule_ids( $value ) {
			$this->set_meta_prop('fgf_rule_ids', $value);
		}

		/**
		 * Set order ID.
		 */
		public function set_order_id( $value ) {
			$this->set_meta_prop('fgf_order_id', $value);
		}

		/**
		 * Set product details.
		 */
		public function set_product_details( $value ) {
			$this->set_meta_prop('fgf_product_details', $value);
		}

		/**
		 * Set user name.
		 */
		public function set_user_name( $value ) {
			$this->set_meta_prop('fgf_user_name', $value);
		}

		/**
		 * Set user email.
		 */
		public function set_user_email( $value ) {
			$this->set_meta_prop('fgf_user_email', $value);
		}

		/**
		 * Set type.
		 */
		public function set_type( $value ) {
			$this->set_meta_prop('fgf_type', $value);
		}

		/**
		 * Get user ID.
		 */
		public function get_user_id() {
			return $this->get_post_prop('user_id');
		}

		/**
		 * Get rule IDs.
		 */
		public function get_rule_ids() {
			return $this->get_meta_prop('fgf_rule_ids');
		}

		/**
		 * Get order ID.
		 */
		public function get_order_id() {
			return $this->get_meta_prop('fgf_order_id');
		}

		/**
		 * Get product details.
		 */
		public function get_product_details() {
			return $this->get_meta_prop('fgf_product_details');
		}

		/**
		 * Get user name.
		 */
		public function get_user_name() {
			return $this->get_meta_prop('fgf_user_name');
		}

		/**
		 * Get user email.
		 */
		public function get_user_email() {
			return $this->get_meta_prop('fgf_user_email');
		}

		/**
		 * Get type.
		 */
		public function get_type() {
			return $this->get_meta_prop('fgf_type');
		}
	}

}
