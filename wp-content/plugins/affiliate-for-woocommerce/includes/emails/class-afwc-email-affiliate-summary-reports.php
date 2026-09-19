<?php
/**
 * Main class for Affiliate summary report email
 *
 * @package     affiliate-for-woocommerce/includes/emails/
 * @since       7.5.0
 * @version     1.2.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Email_Affiliate_Summary_Reports' ) ) {

	/**
	 * Email class for affiliate summary report
	 *
	 * @extends \WC_Email
	 */
	class AFWC_Email_Affiliate_Summary_Reports extends WC_Email {

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
			$this->id = 'afwc_summary_email_reports';

			// This is the title in WooCommerce email settings.
			$this->title = _x( 'Affiliate - Summary Email', 'title of affiliate Summary email', 'affiliate-for-woocommerce' );

			// This is the description in WooCommerce email settings.
			$this->description = _x( 'This email will be sent to each active affiliate of their monthly performance at the start of every month.', 'Description for Affiliate Summary email reports', 'affiliate-for-woocommerce' );

			// These are the default heading and subject lines that can be overridden using the settings.
			$this->subject = _x( 'Your Monthly Summary for {site_title}', 'subject for affiliate summary email', 'affiliate-for-woocommerce' );
			$this->heading = _x( 'Your affiliate monthly summary is here', 'title for affiliate summary email', 'affiliate-for-woocommerce' );

			// Email template location.
			$this->template_html  = 'emails/afwc-affiliate-summary-reports.php';
			$this->template_plain = 'emails/plain/afwc-affiliate-summary-reports.php';

			// Use our plugin templates directory as the template base.
			$this->template_base = AFWC_PLUGIN_DIRPATH . '/templates/';

			$this->placeholders = array();

			// Trigger this email.
			add_action( 'afwc_email_affiliate_summary_reports', array( $this, 'trigger' ), 10, 1 );

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

			// Set the locale to the store locale for affiliate emails to make sure emails are in the store language.
			$this->setup_locale();

			$affiliate_id = ! empty( $this->email_args['affiliate_id'] ) ? intval( $this->email_args['affiliate_id'] ) : 0;
			if ( empty( $affiliate_id ) ) {
				return;
			}

			// Set the user data.
			$user = get_userdata( $affiliate_id );
			if ( $user instanceof WP_User && ! empty( $user->user_email ) ) {
				$this->recipient                    = $user->user_email;
				$this->email_args['affiliate_name'] = ! empty( $user->first_name ) ? $user->first_name : _x( 'there', 'Greeting for the affiliate', 'affiliate-for-woocommerce' );
			}

			$reports = $this->get_reports(
				$affiliate_id,
				! empty( $this->email_args['date_range'] ) ? $this->email_args['date_range'] : ''
			);

			$this->email_args = array_merge(
				$this->email_args,
				( is_array( $reports ) && ! empty( $reports ) ) ? $reports : array()
			);

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
				'email'               => $this,
				'email_heading'       => is_callable( array( $this, 'get_heading' ) ) ? $this->get_heading() : '',
				'additional_content'  => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '',
				'site_address'        => str_replace( array( 'http://', 'https://' ), '', get_home_url() ), // Remove the http(s) protocol from the URL.
				'my_account_afwc_url' => afwc_myaccount_dashboard_url(),
				'affiliate_name'      => $this->email_args['affiliate_name'],
				'total_earning'       => $this->email_args['total_earning'],
				'total_visits'        => $this->email_args['total_visits'],
				'total_customers'     => $this->email_args['total_customers'],
				'conversion_rate'     => $this->email_args['conversion_rate'],
				'converted_urls'      => $this->email_args['converted_urls'],
			);
		}

		/**
		 * Initialize Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => _x( 'Enable/Disable', 'title for enable/disable the affiliate summary report email', 'affiliate-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => _x( 'Enable this email notification', 'label for enable/disable the affiliate summary report email', 'affiliate-for-woocommerce' ),
					'default' => 'no',
				),
				'subject'            => array(
					'title'       => _x( 'Subject', 'title for the subject of the affiliate summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s Email subject. */
					'description' => sprintf( _x( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'Description for the email subject of the affiliate summary report email', 'affiliate-for-woocommerce' ), $this->subject ),
					'placeholder' => $this->subject,
					'default'     => '',
				),
				'heading'            => array(
					'title'       => _x( 'Email Heading', 'title for the email heading of the affiliate summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s Email heading. */
					'description' => sprintf( _x( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'description for the email heading of the affiliate summary report email', 'affiliate-for-woocommerce' ), $this->heading ),
					'placeholder' => $this->heading,
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => _x( 'Additional content', 'title for the additional content of the affiliate summary report email', 'affiliate-for-woocommerce' ),
					'description' => _x( 'Text to appear below the main email content.', 'description for the additional content of the affiliate summary report email', 'affiliate-for-woocommerce' ),
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => _x( 'N/A', 'placeholder for the additional content of the affiliate summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'textarea',
					'default'     => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '', // WC 3.7 introduced an additional content field for all emails.
				),
				'email_type'         => array(
					'title'       => _x( 'Email type', 'title for the email type of the affiliate summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'select',
					'description' => _x( 'Choose which format of email to send.', 'description for the email type of the affiliate summary report email', 'affiliate-for-woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
				),
			);
		}

		/**
		 * Get the reports for this email.
		 *
		 * @param int|array $affiliate_id The affiliate ID(s) for fetching the report.
		 * @param array     $date_range The date range for fetching the affiliates.
		 *
		 * @return array Return the array of report.
		 */
		public function get_reports( $affiliate_id = 0, $date_range = array() ) {
			if ( empty( $affiliate_id ) ) {
				return array();
			}

			if ( ! class_exists( 'AFWC_Admin_Affiliates' ) ) {
				$class_path = AFWC_PLUGIN_DIRPATH . '/includes/admin/class-afwc-admin-affiliates.php';
				if ( ! file_exists( $class_path ) ) {
					return array();
				}
				include_once $class_path;
			}

			$affiliate_data = new AFWC_Admin_Affiliates(
				$affiliate_id,
				is_array( $date_range ) && ! empty( $date_range['from'] ) ? get_gmt_from_date( $date_range['from'] ) : '',
				is_array( $date_range ) && ! empty( $date_range['to'] ) ? get_gmt_from_date( $date_range['to'] ) : '',
				'',
				AFWC_CURRENCY_CODE
			);

			$aggregated = is_callable( array( $affiliate_data, 'get_commissions_customers' ) ) ? $affiliate_data->get_commissions_customers() : array();

			// Assign the required values to affiliate date for getting the proper net commission.
			$affiliate_data->paid_commissions   = floatval( ( ! empty( $aggregated['paid_commissions'] ) ) ? $aggregated['paid_commissions'] : 0 );
			$affiliate_data->unpaid_commissions = floatval( ( ! empty( $aggregated['unpaid_commissions'] ) ) ? $aggregated['unpaid_commissions'] : 0 );

			$visitor_count  = intval( is_callable( array( $affiliate_data, 'get_visitors_count' ) ) ? $affiliate_data->get_visitors_count() : 0 );
			$customer_count = intval( ( ! empty( $aggregated['customers_count'] ) ) ? $aggregated['customers_count'] : 0 );
			$conversion     = ( $visitor_count > 0 ) ? $customer_count * 100 / $visitor_count : 0;

			/**
			 * Filter to modify the limit to show the top referral URL in affiliate summary email.
			 *
			 * @param string $pattern The regex pattern.
			 *
			 * @since 7.5.0
			 */
			$top_referral_url_limit = apply_filters( 'afwc_top_referral_urls_limit_on_summary_email', 10, array( 'source' => $this ) );

			return array(
				'total_earning'   => afwc_format_price( floatval( is_callable( array( $affiliate_data, 'get_earned_commissions' ) ) ? $affiliate_data->get_earned_commissions() : 0 ) ),
				'total_visits'    => $visitor_count,
				'total_customers' => $customer_count,
				'conversion_rate' => afwc_format_number( $conversion ) . '%',
				'converted_urls'  => is_callable( array( $affiliate_data, 'get_converted_url_stats' ) )
									? $affiliate_data->get_converted_url_stats(
										array( 'limit' => $top_referral_url_limit )
									) : array(),
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

			$email->email_args['total_earning']   = afwc_format_price( 2178 );
			$email->email_args['total_visits']    = 980;
			$email->email_args['total_customers'] = 49;
			$email->email_args['conversion_rate'] = '5%';
			$email->email_args['converted_urls']  = array(
				array(
					'url'            => afwc_get_affiliate_url( home_url(), '', 43 ),
					'referral_count' => 16,
					'visitor_count'  => 145,
				),
				array(
					'url'            => afwc_get_affiliate_url( home_url() . '/product/phone/', '', 43 ),
					'referral_count' => 12,
					'visitor_count'  => 103,
				),
				array(
					'url'            => afwc_get_affiliate_url( home_url() . '/blog/offer/', '', 43 ),
					'referral_count' => 10,
					'visitor_count'  => 86,
				),
				array(
					'url'            => afwc_get_affiliate_url( home_url() . '/abcd/', '', 43 ),
					'referral_count' => 9,
					'visitor_count'  => 91,
				),
				array(
					'url'            => afwc_get_affiliate_url( home_url() . '/product/laptop/', '', 43 ),
					'referral_count' => 5,
					'visitor_count'  => 67,
				),
			);

			return $email;
		}
	}
}
