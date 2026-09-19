<?php
/**
 * Main class for Affiliate conversion received Email
 *
 * @package     affiliate-for-woocommerce/includes/emails/
 * @since       2.4.2
 * @version     1.5.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Email_Commission_Paid' ) ) {

	/**
	 * The Affiliate Payout Sent Email class
	 *
	 * @extends \WC_Email
	 */
	class AFWC_Email_Commission_Paid extends WC_Email {

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
			$this->id = 'afwc_commission_paid';

			// This is the title in WooCommerce Email settings.
			$this->title = _x( 'Affiliate - Commission Paid', 'title for Affiliate - Commission Paid email', 'affiliate-for-woocommerce' );

			// This is the description in WooCommerce email settings.
			$this->description = _x( 'This email will be sent to an affiliate when their commission/payout is processed from the affiliate dashboard.', 'Description for Affiliate - Commission Paid email', 'affiliate-for-woocommerce' );

			// These are the default heading and subject lines that can be overridden using the settings.
			$this->subject = _x( 'Your affiliate commission from {site_title} is here!', 'subject for Affiliate - Commission Paid email', 'affiliate-for-woocommerce' );
			$this->heading = _x( 'Your commission is on your way!', 'heading for Affiliate - Commission Paid email', 'affiliate-for-woocommerce' );

			// Email template location.
			$this->template_html  = 'emails/afwc-commission-paid.php';
			$this->template_plain = 'emails/plain/afwc-commission-paid.php';

			// Use our plugin templates directory as the template base.
			$this->template_base = AFWC_PLUGIN_DIRPATH . '/templates/';

			$this->placeholders = array();

			// Trigger this email when commission is paid to an affiliate.
			add_action( 'afwc_email_commission_paid', array( $this, 'trigger' ), 10, 1 );

			// Filter to modify the email object before rendering the preview (WC 9.6.0 introduced email preview).
			add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'prepare_email_for_preview' ) );

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

			// Whom to send email.
			$affiliate_id = ! empty( $this->email_args['affiliate_id'] ) ? intval( $this->email_args['affiliate_id'] ) : 0;
			$user         = ! empty( $affiliate_id ) ? get_user_by( 'id', $affiliate_id ) : null;
			if ( $user instanceof WP_User && ! empty( $user->user_email ) ) {
				$this->recipient = $user->user_email;
			}

			$user_info = get_userdata( $affiliate_id );
			// TODO-MS: write a fallback logic to use nicename if first name not found.
			$this->email_args['affiliate_name']    = isset( $user_info->first_name ) ? $user_info->first_name : _x( 'there', 'Greeting for affiliate', 'affiliate-for-woocommerce' );
			$this->email_args['commission_amount'] = isset( $this->email_args['amount'] ) ? afwc_format_number( $this->email_args['amount'] ) : 0.00;
			$this->email_args['currency_symbol']   = isset( $this->email_args['currency_id'] ) ? get_woocommerce_currency_symbol( $this->email_args['currency_id'] ) : '';

			$this->email_args['start_date']            = isset( $this->email_args['from_date'] ) ? $this->email_args['from_date'] : '';
			$this->email_args['end_date']              = isset( $this->email_args['to_date'] ) ? $this->email_args['to_date'] : '';
			$this->email_args['total_referrals']       = isset( $this->email_args['total_referrals'] ) ? $this->email_args['total_referrals'] : '';
			$this->email_args['payout_notes']          = isset( $this->email_args['payout_notes'] ) ? $this->email_args['payout_notes'] : '';
			$this->email_args['payout_method']         = ! empty( $this->email_args['payout_method'] ) ? $this->email_args['payout_method'] : '';
			$this->email_args['paypal_receiver_email'] = ! empty( $this->email_args['paypal_receiver_email'] ) ? $this->email_args['paypal_receiver_email'] : '';
			$this->email_args['transaction_id']        = ( ! empty( $this->email_args['transaction_id'] ) ? $this->email_args['transaction_id'] : '' );

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
			return array(
				'email'                 => $this,
				'email_heading'         => is_callable( array( $this, 'get_heading' ) ) ? $this->get_heading() : '',
				'additional_content'    => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '',
				'my_account_afwc_url'   => afwc_myaccount_dashboard_url(),
				'show_invoice'          => is_callable( array( 'AFWC_Payout_Invoice', 'is_enabled_for_affiliate' ) ) && AFWC_Payout_Invoice::is_enabled_for_affiliate(),
				'affiliate_name'        => $this->email_args['affiliate_name'],
				'commission_amount'     => $this->email_args['commission_amount'],
				'currency_symbol'       => $this->email_args['currency_symbol'],
				'start_date'            => $this->email_args['start_date'],
				'end_date'              => $this->email_args['end_date'],
				'total_referrals'       => $this->email_args['total_referrals'],
				'payout_notes'          => $this->email_args['payout_notes'],
				'payout_method'         => $this->email_args['payout_method'],
				'paypal_receiver_email' => $this->email_args['paypal_receiver_email'],
				'transaction_id'        => $this->email_args['transaction_id'],
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
					'description' => sprintf( _x( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'Description for the email subject for affiliate commission paid email', 'affiliate-for-woocommerce' ), $this->subject ),
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

		/**
		 * Prepare email dummy data for preview.
		 *
		 * @param WC_Email $email The email object.
		 *
		 * @return WC_Email
		 */
		public function prepare_email_for_preview( $email = null ) {
			if ( empty( $email ) || ! $email instanceof WC_Email ) {
				return $email;
			}
			if ( ! empty( $email->id ) && $email->id !== $this->id ) {
				return $email;
			}
			if ( empty( $email->email_args ) ) {
				$email->email_args = array();
			}

			$date_format                                = get_option( 'date_format', 'Y-m-d' );
			$email->email_args['start_date']            = gmdate( $date_format, strtotime( 'first day of previous month' ) );
			$email->email_args['end_date']              = gmdate( $date_format, strtotime( 'last day of previous month' ) );
			$email->email_args['total_referrals']       = 9;
			$email->email_args['commission_amount']     = 572;
			$email->email_args['currency_symbol']       = AFWC_CURRENCY;
			$email->email_args['payout_method']         = esc_html_x( 'Other', 'Other payout method title', 'affiliate-for-woocommerce' );
			$email->email_args['payout_notes']          = '';
			$email->email_args['paypal_receiver_email'] = '';
			$email->email_args['transaction_id']        = '';

			return $email;
		}
	}

}
