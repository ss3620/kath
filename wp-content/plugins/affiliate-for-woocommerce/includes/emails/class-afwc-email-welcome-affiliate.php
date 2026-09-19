<?php
/**
 * Main class for Welcome affiliate email
 *
 * @package     affiliate-for-woocommerce/includes/emails/
 * @since       2.4.0
 * @version     1.6.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Email_Welcome_Affiliate' ) ) {

	/**
	 * Welcome email for affiliate
	 *
	 * @extends \WC_Email
	 */
	class AFWC_Email_Welcome_Affiliate extends WC_Email {

		/**
		 * Email parameters
		 *
		 * @var array $email_args
		 */
		public $email_args = array();

		/**
		 * Set email defaults
		 */
		public function __construct() {
			// Set ID, this simply needs to be a unique name.
			$this->id = 'afwc_welcome';

			// This is the title in WooCommerce email settings.
			$this->title = _x( 'Affiliate - Welcome Email', 'title for Affiliate - Welcome Email email', 'affiliate-for-woocommerce' );

			// This is the description in WooCommerce email settings.
			$this->description = _x( 'This email will be sent to an affiliate after their request to join is approved OR if they automatically become an affiliate.', 'Description for Affiliate - Welcome Email email', 'affiliate-for-woocommerce' );

			// These are the default heading and subject lines that can be overridden using the settings.
			$this->subject = _x( 'Welcome to {site_title}', 'subject for Affiliate - Welcome Email email', 'affiliate-for-woocommerce' );
			$this->heading = _x( 'Welcome to our affiliate program!', 'heading for Affiliate - Welcome Email email', 'affiliate-for-woocommerce' );

			// Email template location.
			$this->template_html  = 'emails/afwc-welcome-affiliate-email.php';
			$this->template_plain = 'emails/plain/afwc-welcome-affiliate-email.php';

			// Use our plugin templates directory as the template base.
			$this->template_base = AFWC_PLUGIN_DIRPATH . '/templates/';

			$this->placeholders = array();

			// Trigger this email as a welcome email to an affiliate.
			add_action( 'afwc_email_welcome_affiliate', array( $this, 'trigger' ), 10, 1 );

			// Call parent constructor to load any other defaults not explicity defined here.
			parent::__construct();

			// When sending email to customer in this case it is affiliate.
			$this->customer_email = true;
		}

		/**
		 * Determine if the email should actually be sent and setup email merge variables
		 *
		 * @param array $args Email arguments.
		 */
		public function trigger( $args = array() ) {
			if ( empty( $args ) ) {
				return;
			}

			$this->email_args = array();
			$this->email_args = wp_parse_args( $args, $this->email_args );

			// Set the locale to the store locale for customer emails to make sure emails are in the store language.
			$this->setup_locale();

			$affiliate_id = ! empty( $this->email_args['affiliate_id'] ) ? intval( $this->email_args['affiliate_id'] ) : 0;
			$user         = ! empty( $affiliate_id ) ? get_user_by( 'id', $affiliate_id ) : null;
			if ( $user instanceof WP_User && ! empty( $user->user_email ) ) {
				$this->recipient = $user->user_email;
			}

			$user_info                     = get_userdata( $affiliate_id );
			$this->email_args['user_name'] = ! empty( $user_info->first_name ) ? $user_info->first_name : _x( 'there', 'Greeting for affiliate', 'affiliate-for-woocommerce' );

			$affiliate = new AFWC_Affiliate( $user );

			$afwc_ref_url_id                      = get_user_meta( $affiliate_id, 'afwc_ref_url_id', true );
			$affiliate_id                         = afwc_get_affiliate_id_based_on_user_id( $affiliate_id );
			$affiliate_identifier                 = ( ! empty( $afwc_ref_url_id ) ) ? $afwc_ref_url_id : $affiliate_id;
			$this->email_args['affiliate_link']   = is_callable( array( $affiliate, 'get_affiliate_link' ) ) ? $affiliate->get_affiliate_link() : '';
			$this->email_args['affiliate_id']     = $affiliate_identifier;
			$this->email_args['is_auto_approved'] = ! empty( $this->email_args['is_auto_approved'] ) ? $this->email_args['is_auto_approved'] : get_option( 'afwc_auto_add_affiliate', 'no' );
			$this->email_args['approval_action']  = ! empty( $this->email_args['approval_action'] ) ? $this->email_args['approval_action'] : 'user_registration';

			// For any email placeholders.
			$this->set_placeholders();

			$email_content = $this->get_content();
			// Replace placeholders with values in the email content.
			$email_content = ( is_callable( array( $this, 'format_string' ) ) ) ? $this->format_string( $email_content ) : $email_content;

			// Send email.
			if ( ! empty( $email_content ) && $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $email_content, $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Function to set placeholder variables used in email.
		 */
		public function set_placeholders() {
			// For any email placeholders.
			$this->placeholders = array(
				'{site_title}' => $this->get_blogname(),
			);
		}

		/**
		 * Function to load email html content
		 *
		 * @return string Email content html
		 */
		public function get_content_html() {
			global $affiliate_for_woocommerce;

			$email_arguments = $this->get_template_args();

			if ( ! empty( $email_arguments ) ) {
				ob_start();

				is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template' ) ) ? $affiliate_for_woocommerce->afwc_get_template(
					$this->template_html,
					$email_arguments
				) : '';

				return ob_get_clean();
			}

			return '';
		}

		/**
		 * Function to load email plain content
		 *
		 * @return string Email plain content
		 */
		public function get_content_plain() {
			global $affiliate_for_woocommerce;

			$email_arguments = $this->get_template_args();

			if ( ! empty( $email_arguments ) ) {
				ob_start();

				is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template' ) ) ? $affiliate_for_woocommerce->afwc_get_template(
					$this->template_plain,
					$email_arguments
				) : '';

				return ob_get_clean();
			}

			return '';
		}

		/**
		 * Function to return the required email arguments for this email template.
		 *
		 * @return array Email arguments.
		 */
		public function get_template_args() {
			/**
			 * Filter to modify the shop page URL.
			 *
			 * @param string Shop page permalink.
			 * @param array Additional information
			 *
			 * @since 2.4.0
			 */
			$shop_url = apply_filters( 'afwc_shop_url', ( ( wc_get_page_id( 'shop' ) > 0 ) ? wc_get_page_permalink( 'shop' ) : '' ), array( 'source' => $this ) );

			return array(
				'email'                  => $this,
				'email_heading'          => is_callable( array( $this, 'get_heading' ) ) ? $this->get_heading() : '',
				'additional_content'     => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '',
				'shop_page'              => $shop_url,
				'my_account_afwc_url'    => afwc_myaccount_dashboard_url(),
				'contact_email'          => get_option( 'afwc_contact_admin_email_address', '' ),
				'use_referral_coupons'   => get_option( 'afwc_use_referral_coupons', 'yes' ),
				'user_name'              => $this->email_args['user_name'],
				'affiliate_id'           => $this->email_args['affiliate_id'],
				'is_auto_approved'       => $this->email_args['is_auto_approved'],
				'approval_action'        => $this->email_args['approval_action'],
				'affiliate_link'         => esc_url( $this->email_args['affiliate_link'] ),
				'affiliate_made_coupons' => get_option( 'afwc_enable_affiliate_made_coupons', 'no' ),
			);
		}

		/**
		 * Initialize Settings Form Fields
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'affiliate-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'affiliate-for-woocommerce' ),
					'default' => 'yes',
				),
				'subject'            => array(
					'title'       => __( 'Subject', 'affiliate-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s Email subject. */
					'description' => sprintf( _x( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'Description for the email subject for the affiliate welcome email', 'affiliate-for-woocommerce' ), $this->subject ),
					'placeholder' => $this->subject,
					'default'     => '',
				),
				'heading'            => array(
					'title'       => __( 'Email Heading', 'affiliate-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s Email heading. */
					'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'affiliate-for-woocommerce' ), $this->heading ),
					'placeholder' => $this->heading,
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => __( 'Additional content', 'affiliate-for-woocommerce' ),
					'description' => __( 'Text to appear below the main email content.', 'affiliate-for-woocommerce' ),
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'affiliate-for-woocommerce' ),
					'type'        => 'textarea',
					'default'     => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '', // WC 3.7 introduced an additional content field for all emails.
				),
				'email_type'         => array(
					'title'       => __( 'Email type', 'affiliate-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'affiliate-for-woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
				),
			);
		}
	}

}
