<?php

/**
 * Customer- Manual Gift
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'FGF_Customer_Manual_Gift_Notification' ) ) {

	/**
	 * Class FGF_Customer_Manual_Gift_Notification
	 */
	class FGF_Customer_Manual_Gift_Notification extends FGF_Notifications {

		/**
		 * Class Constructor
		 */
		public function __construct() {

			$this->id = 'customer_manual_gift' ;

			// Triggers for this email.
			add_action( sanitize_key( $this->plugin_slug . '_manual_gift_order_created' ) , array( $this, 'trigger' ) , 10 , 1 ) ;

			parent::__construct() ;
		}

		/**
		 * Get Enabled.
		 */
		public function get_enabled() {

			return get_option( 'fgf_settings_enable_manual_gift_email' , 'no' ) ;
		}

		/*
		 * Default Subject
		 */

		public function get_default_subject() {

			return '{site_name}  - Free Gift Received' ;
		}

		/*
		 * Default Message
		 */

		public function get_default_message() {

			return 'Hi {user_name},

You have received the following Product(s) as a Gift from the Site Admin.

{free_gifts}' ;
		}

		/**
		 * Get subject.
		 */
		public function get_subject() {

			return $this->format_string( get_option( 'fgf_settings_manual_gift_email_subject' , $this->get_default_subject() ) ) ;
		}

		/**
		 * Get Message.
		 */
		public function get_message() {
			$string = $this->format_string( get_option( 'fgf_settings_manual_gift_email_message' , $this->get_default_message() ) ) ;
			$string = wpautop( $string ) ;
			$string = $this->email_inline_style( $string ) ;

			return $string ;
		}

		/**
		 * Trigger the sending of this email.
		 */
		public function trigger( $master_log_id ) {
			$master_log_object = fgf_get_master_log( $master_log_id ) ;

			if ( is_object( $master_log_object ) ) {
				$this->recipient                      = $master_log_object->get_user_email() ;
				$this->placeholders[ '{order_id}' ]   = $master_log_object->get_order_id() ;
				$this->placeholders[ '{user_name}' ]  = $master_log_object->get_user_name() ;
				$this->placeholders[ '{free_gifts}' ] = self::render_gift_product_table( $master_log_object ) ;
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send_email( $this->get_recipient() , $this->get_subject() , $this->get_formatted_message() , $this->get_headers() , $this->get_attachments() ) ;
			}
		}

		/**
		 * Custom CSS
		 */
		public function custom_css() {
			return 'table.fgf_gift_products_table {
                    border-collapse: collapse;
                    border : 1px solid #CCC;
                }
                table.fgf_gift_products_table th{
                    background : #CCC;
                    padding : 9px 12px;
                }
                table.fgf_gift_products_table td{
                    border-right: 1px solid #CCC;
                    padding : 9px 12px;
                }' ;
		}

		/**
		 * Render Gift product table.
		 * 
		 * @return mixed
		 */
		public function render_gift_product_table( $master_log_object ) {
			return fgf_get_template_html( 'email/gift-product-details.php' , array( 'product_details' => $master_log_object->get_product_details() ) ) ;
		}
	}

}
