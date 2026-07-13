<?php
/**
 * Main class for Admin summary report email
 *
 * @package     affiliate-for-woocommerce/includes/emails/
 * @since       8.25.0
 * @version     1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Email_Admin_Summary_Reports' ) ) {

	/**
	 * Email class for admin summary report
	 *
	 * @extends WC_Email
	 */
	class AFWC_Email_Admin_Summary_Reports extends WC_Email {

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
			$this->id = 'afwc_admin_summary_email_reports';

			// This is the title in WooCommerce email settings.
			$this->title = _x( 'Affiliate Manager - Summary Email', 'title of admin summary email', 'affiliate-for-woocommerce' );

			// This is the description in WooCommerce email settings.
			$this->description = _x( "This email will be sent to the store admin and affiliate manager for the affiliate program's monthly report on the first of every month at 1 p.m.", 'description for admin summary email', 'affiliate-for-woocommerce' );

			// These are the default heading and subject lines that can be overridden using the settings.
			$this->subject = _x( "Affiliate Program's Monthly Summary for {site_title}", 'subject of admin summary email', 'affiliate-for-woocommerce' );
			$this->heading = _x( 'Tracking your affiliate program: key insights from last month', 'heading of admin summary email', 'affiliate-for-woocommerce' );

			// Email template location.
			$this->template_html  = 'afwc-admin-summary-reports.php';
			$this->template_plain = 'plain/afwc-admin-summary-reports.php';

			// Use our plugin templates directory as the template base.
			$this->template_base = AFWC_PLUGIN_DIRPATH . '/templates/emails/';

			$this->placeholders = array();

			// Trigger this email.
			add_action( 'afwc_email_admin_summary_reports', array( $this, 'trigger' ) );

			// Set action scheduler time.
			add_filter( $this->id . '_as_timestamp', array( $this, 'get_as_timestamp_for_summary' ) );

			// Filter to modify the email object before rendering the preview (WC 9.6.0 introduced email preview).
			add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'prepare_email_for_preview' ) );

			// Call parent constructor to load any other defaults not explicity defined here.
			parent::__construct();

			$this->recipient = $this->get_option( 'recipient', $this->get_default_recipients() );
		}

		/**
		 * Method to get default recipients of admin summary email.
		 *
		 * @return string Comma separated emails
		 */
		public function get_default_recipients() {
			$admin_email               = get_option( 'admin_email', '' );
			$afwc_admin_contact_emails = get_option( 'afwc_contact_admin_email_address', '' );

			$recipient_emails_string = str_replace( ' ', '', "$admin_email,$afwc_admin_contact_emails" );
			$recipient_emails_list   = array_filter( array_unique( explode( ',', $recipient_emails_string ) ), 'is_email' );

			return implode( ',', $recipient_emails_list );
		}

		/**
		 * Determine if the email should actually be sent and setup email merge variables
		 */
		public function trigger() {
			if ( ! $this->is_enabled() ) {
				return;
			}

			// Set the locale to the store locale for affiliate emails to make sure emails are in the store language.
			$this->setup_locale();

			$reports = $this->get_reports();

			$this->email_args = array_merge(
				$this->email_args,
				! empty( $reports ) ? $reports : array()
			);

			// For any email placeholders.
			$this->set_placeholders();

			$email_content = $this->get_content();
			// Replace placeholders with values in the email content.
			$email_content = ( is_callable( array( $this, 'format_string' ) ) ) ? $this->format_string( $email_content ) : $email_content;

			// Send email.
			if ( ! empty( $email_content ) && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $email_content, $this->get_headers(), $this->get_attachments() );

				// To prevent any tip from being skipped, update the tip value only after the email has been successfully sent.
				if ( ! empty( $this->email_args['expert_tips'] ) ) {
					$afwc_get_admin_summary_tip = absint( get_option( 'afwc_get_admin_summary_tip', 1 ) );
					update_option( 'afwc_get_admin_summary_tip', ( $afwc_get_admin_summary_tip + 1 ), 'no' );
				}
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

				wc_get_template(
					$this->template_html,
					$email_arguments,
					is_callable( array( $affiliate_for_woocommerce, 'get_template_base_dir' ) ) ? $affiliate_for_woocommerce->get_template_base_dir( $this->template_html ) : '',
					$this->template_base
				);

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

				wc_get_template(
					$this->template_plain,
					$email_arguments,
					is_callable( array( $affiliate_for_woocommerce, 'get_template_base_dir' ) ) ? $affiliate_for_woocommerce->get_template_base_dir( $this->template_plain ) : '',
					$this->template_base
				);

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
				'email'                         => $this,
				'email_heading'                 => is_callable( array( $this, 'get_heading' ) ) ? $this->get_heading() : '',
				'additional_content'            => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '',
				'site_address'                  => str_replace( array( 'http://', 'https://' ), '', get_home_url() ), // Remove the http(s) protocol from the URL.
				'pending_payouts_dashboard_url' => is_callable( array( 'AFWC_URL_Handler', 'get_admin_dashboard_clean_url' ) )
					? AFWC_URL_Handler::get_admin_dashboard_clean_url( 'pending-payouts' )
					: add_query_arg( array( 'page' => 'affiliate-for-woocommerce#!/pending-payouts' ), admin_url( 'admin.php' ) ),
				'from_date'                     => $this->email_args['from_date'],
				'to_date'                       => $this->email_args['to_date'],
				'affiliates_revenue_amount'     => $this->email_args['affiliates_revenue_amount'],
				'site_order_total_amount'       => $this->email_args['site_order_total_amount'],
				'affiliates_order_count'        => $this->email_args['affiliates_order_count'],
				'site_order_count'              => $this->email_args['site_order_count'],
				'paid_commissions'              => $this->email_args['paid_commissions'],
				'unpaid_commissions'            => $this->email_args['unpaid_commissions'],
				'newly_joined_affiliates'       => $this->email_args['newly_joined_affiliates'],
				'pending_affiliates'            => $this->email_args['pending_affiliates'],
				'top_performing_affiliates'     => $this->email_args['top_performing_affiliates'],
				'converted_urls'                => $this->email_args['converted_urls'],
				'expert_tips'                   => $this->email_args['expert_tips'],
			);
		}

		/**
		 * Initialize Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'            => array(
					'title'       => _x( 'Enable/Disable', 'title for enable/disable the admin summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => _x( 'Enable this email notification', 'label for enable/disable checkbox of the admin summary report email', 'affiliate-for-woocommerce' ),
					'description' => '',
					'default'     => 'yes',
				),
				'recipient'          => array(
					'title'       => _x( 'Recipient(s)', 'title for recipients of the admin summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s: WP admin email and Affiliate manager email */
					'description' => sprintf( _x( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'label for description of recipient of the admin summary report email', 'affiliate-for-woocommerce' ), '<code>' . esc_attr( $this->get_default_recipients() ) . '</code>' ),
					'placeholder' => esc_attr( $this->get_default_recipients() ),
					'default'     => '',
				),
				'subject'            => array(
					'title'       => _x( 'Subject', 'title for the subject of the admin summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s Email subject. */
					'description' => sprintf( _x( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'Description for the email subject of the admin summary report email', 'affiliate-for-woocommerce' ), $this->subject ),
					'placeholder' => $this->subject,
					'default'     => '',
				),
				'heading'            => array(
					'title'       => _x( 'Email Heading', 'title for the email heading of the admin summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'text',
					/* translators: %s Email heading. */
					'description' => sprintf( _x( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'description for the email heading of the admin summary report email', 'affiliate-for-woocommerce' ), $this->heading ),
					'placeholder' => $this->heading,
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => _x( 'Additional content', 'title for the additional content of the admin summary report email', 'affiliate-for-woocommerce' ),
					'description' => _x( 'Text to appear below the main email content.', 'description for the additional content of the admin summary report email', 'affiliate-for-woocommerce' ),
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => _x( 'N/A', 'placeholder for the additional content of the admin summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'textarea',
					'default'     => is_callable( array( $this, 'get_additional_content' ) ) ? $this->get_additional_content() : '', // WC 3.7 introduced an additional content field for all emails.
				),
				'email_type'         => array(
					'title'       => _x( 'Email type', 'title for the email type of the admin summary report email', 'affiliate-for-woocommerce' ),
					'type'        => 'select',
					'description' => _x( 'Choose which format of email to send.', 'description for the email type of the admin summary report email', 'affiliate-for-woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
				),
			);
		}

		/**
		 * Get the reports for this email.
		 *
		 * @return array Return the array of different values of report.
		 */
		public function get_reports() {
			$date_range = $this->get_date_range_for_summary();

			$form_date_gmt = is_array( $date_range ) && ! empty( $date_range['from'] ) ? get_gmt_from_date( $date_range['from'] ) : '';
			$to_date_gmt   = is_array( $date_range ) && ! empty( $date_range['to'] ) ? get_gmt_from_date( $date_range['to'] ) : '';

			if ( ! class_exists( 'AFWC_Admin_Summary_Reports' ) ) {
				$class_path = AFWC_PLUGIN_DIRPATH . '/includes/admin/class-afwc-admin-summary-reports.php';
				if ( ! file_exists( $class_path ) ) {
					return array();
				}
				include_once $class_path;
			}

			$all_affiliates_summary_data = new AFWC_Admin_Summary_Reports(
				array(),
				$form_date_gmt,
				$to_date_gmt
			);

			return is_callable( array( $all_affiliates_summary_data, 'get_admin_summary_report_data' ) ) ? $all_affiliates_summary_data->get_admin_summary_report_data() : array();
		}

		/**
		 * Get the data range for summary.
		 * Currently it returns the date range of this month.
		 *
		 * @return array Return the array of date range(From date and To date)
		 */
		public function get_date_range_for_summary() {
			$offset_timestamp = is_callable( array( 'Affiliate_For_WooCommerce', 'get_offset_timestamp' ) ) ? intval( Affiliate_For_WooCommerce::get_offset_timestamp() ) : 0;
			$format           = 'd-m-Y';

			// Get the first day of the previous month.
			$first_day_previous_month = gmdate( $format, mktime( 0, 0, 0, gmdate( 'n', $offset_timestamp ) - 1, 1, gmdate( 'Y', $offset_timestamp ) ) );

			// Get the last day of the previous month.
			$last_day_previous_month = gmdate( $format, mktime( 0, 0, 0, gmdate( 'n', $offset_timestamp ), 0, gmdate( 'Y', $offset_timestamp ) ) );

			return array(
				'from' => $first_day_previous_month . ' 00:00:00', // From date: the start date of the previous month.
				'to'   => $last_day_previous_month . ' 23:59:59', // To date: the end date of the previous month.
			);
		}

		/**
		 * Converts GMT timestamp to local timezone for Action Scheduler
		 *
		 * @return int Adjusted timestamp for scheduling
		 */
		public function get_as_timestamp_for_summary() {
			$gmt_timestamp = intval( strtotime( 'first day of +1 month 13:00' ) );
			if ( empty( $gmt_timestamp ) ) {
				return 0;
			}

			$gmt_offset = is_callable( array( 'Affiliate_For_WooCommerce', 'get_gmt_offset' ) ) ? intval( Affiliate_For_WooCommerce::get_gmt_offset() ) : 0;

			// Adjust the timestamp to start the action with store timezone.
			return $gmt_timestamp - $gmt_offset;
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

			$date_format                                    = get_option( 'date_format', 'Y-m-d' );
			$email->email_args['from_date']                 = gmdate( $date_format, strtotime( 'first day of previous month' ) );
			$email->email_args['to_date']                   = gmdate( $date_format, strtotime( 'last day of previous month' ) );
			$email->email_args['affiliates_revenue_amount'] = afwc_format_price( 9374.25 );
			$email->email_args['site_order_total_amount']   = afwc_format_price( 84406.63 );
			$email->email_args['affiliates_order_count']    = 317;
			$email->email_args['site_order_count']          = 4032;
			$email->email_args['paid_commissions']          = afwc_format_price( 749 );
			$email->email_args['unpaid_commissions']        = afwc_format_price( 3589.38 );
			$email->email_args['newly_joined_affiliates']   = 46;
			$email->email_args['pending_affiliates']        = 3;
			$email->email_args['top_performing_affiliates'] = array(
				array(
					'display_name'       => 'Lori Soper',
					'order_total_amount' => 2178,
				),
				array(
					'display_name'       => 'Kate Welch',
					'order_total_amount' => 842.62,
				),
				array(
					'display_name'       => 'Anne Beckenbauer',
					'order_total_amount' => 650.41,
				),
				array(
					'display_name'       => 'Lin Ying',
					'order_total_amount' => 568.35,
				),
				array(
					'display_name'       => 'James Brown',
					'order_total_amount' => 394.73,
				),
			);
			$email->email_args['converted_urls']            = array(
				array( 'url' => afwc_get_affiliate_url( home_url(), '', 43 ) ),
				array( 'url' => afwc_get_affiliate_url( home_url() . '/product/phone/', '', 35 ) ),
				array( 'url' => afwc_get_affiliate_url( home_url() . '/blog/offer/', '', 27 ) ),
				array( 'url' => afwc_get_affiliate_url( home_url() . '/abcd/', '', 19 ) ),
				array( 'url' => afwc_get_affiliate_url( home_url() . '/product/laptop/', '', 51 ) ),
			);
			$email->email_args['expert_tips']               = array();

			return $email;
		}
	}
}
