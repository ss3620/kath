<?php
/*
 * GDPR Compliance
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

if ( ! class_exists( 'FGF_Privacy' ) ) :

	/**
	 * Class
	 */
	class FGF_Privacy {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->init_hooks() ;
		}

		/**
		 * Register plugin
		 */
		public function init_hooks() {
			// This hook registers plugin privacy content
			add_action( 'admin_init', array( __CLASS__, 'register_privacy_content' ), 20 ) ;
		}

		/**
		 * Register Privacy Content
		 */
		public static function register_privacy_content() {
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
				return ;
			}

			$content = self::get_privacy_message() ;
			if ( $content ) {
				wp_add_privacy_policy_content( __( 'Free Gifts for WooCommerce', 'free-gifts-for-woocommerce' ), $content ) ;
			}
		}

		/**
		 * Prepare Privacy Content
		 */
		public static function get_privacy_message() {

			return self::get_privacy_message_html() ;
		}

		/**
		 * Get Privacy Content
		 */
		public static function get_privacy_message_html() {
			ob_start() ;
			?>
			<p><?php esc_html_e( 'This includes the basics of what personal data your store may collect, store & share. Depending on what settings are enabled furthermore which additional plugins used, the specific information shared by your store will vary.', 'free-gifts-for-woocommerce' ) ; ?></p>
			<h2><?php esc_html_e( 'What the Plugin Does?', 'free-gifts-for-woocommerce' ) ; ?></h2>
			<p><?php esc_html_e( 'This plugin allows users to get Free Gifts for their purchase in the site based on the configuration(s).', 'free-gifts-for-woocommerce' ) ; ?> </p>
			<h2><?php esc_html_e( 'What We Collect and Store?', 'free-gifts-for-woocommerce' ) ; ?></h2>
			<p><?php esc_html_e( ' This Plugin stores user name, user email Information from the user.', 'free-gifts-for-woocommerce' ) ; ?></p>
			<?php
			$contents = ob_get_contents() ;
			ob_end_clean() ;

			return $contents ;
		}
	}

	new FGF_Privacy() ;

endif;
