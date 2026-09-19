<?php
/**
 * Main class for Visits.
 *
 * @package     affiliate-for-woocommerce/includes/reports/
 * @since       6.31.0
 * @version     2.1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Referral_Mediums\Referral_Medium_Interface;
use AFWC\Referral_Mediums\Referral_Medium_Registry;

if ( ! class_exists( 'AFWC_Visits' ) ) {

	/**
	 * Main class for Visit reports.
	 */
	class AFWC_Visits {

		/**
		 * Variable to hold affiliate ids.
		 *
		 * @var array $affiliate_ids
		 */
		public $affiliate_ids = array();

		/**
		 * Variable to hold the from date.
		 *
		 * @var string $from
		 */
		public $from = '';

		/**
		 * Variable to hold the to date.
		 *
		 * @var string $to
		 */
		public $to = '';

		/**
		 * Variable to hold batch limit per request.
		 *
		 * @var int $batch_limit
		 */
		public $batch_limit = 1;

		/**
		 * Variable to hold batch start per request.
		 *
		 * @var int $start_limit
		 */
		public $start_limit = 0;

		/**
		 * Variable to hold the DB data.
		 *
		 * @var array $data
		 */
		private $data = array();

		/**
		 * Constructor
		 *
		 * @param array $affiliate_ids Affiliates ids.
		 * @param array $args          The arguments.
		 */
		public function __construct( $affiliate_ids = array(), $args = array() ) {
			$this->affiliate_ids = ( ! is_array( $affiliate_ids ) ) ? array( $affiliate_ids ) : $affiliate_ids;
			$this->from          = ( ! empty( $args['from'] ) ) ? gmdate( 'Y-m-d H:i:s', strtotime( $args['from'] ) ) : '';
			$this->to            = ( ! empty( $args['to'] ) ) ? gmdate( 'Y-m-d H:i:s', strtotime( $args['to'] ) ) : '';
			$this->batch_limit   = ( ! empty( $args['limit'] ) ) ? ( intval( $args['limit'] ) ) : 1;
			$this->start_limit   = ( ! empty( $args['start'] ) ) ? intval( $args['start'] ) : 0;
		}

		/**
		 * Method to get the visits raw data.
		 *
		 * @return array The visits data.
		 */
		public function get_data() {
			if ( empty( $this->data ) ) {
				// Set the data if not set.
				$this->set_data_from_db();
			}

			return ! empty( $this->data ) && is_array( $this->data ) ? $this->data : array();
		}

		/**
		 * Retrieves formatted report data based on provided arguments.
		 *
		 * @param array $args Optional. Arguments to customize report output.
		 *
		 * @return array Array of structured report entries.
		 */
		public function get_reports( $args = array() ) {
			// Default arguments.
			$default_args = array(
				'is_affiliate_dashboard' => false,
				'get_user_agent_info'    => true,
			);
			$args         = wp_parse_args( $args, $default_args );

			// Get the raw data.
			$raw_data = ! empty( $this->data ) ? $this->data : $this->get_data();
			if ( empty( $raw_data ) || ! is_array( $raw_data ) ) {
				return array();
			}

			$datetime_format        = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i:s' );
			$is_affiliate_dashboard = $args['is_affiliate_dashboard'];

			$reports = array();
			foreach ( $raw_data as $row ) {
				if ( empty( $row['id'] ) || ! is_numeric( $row['id'] ) ) {
					continue;
				}

				$hit_id = intval( $row['id'] );
				$ip     = $this->get_ip( $hit_id );
				$report = array(
					'id'            => $hit_id,
					'datetime'      => $this->get_date_time( $hit_id, $datetime_format, $is_affiliate_dashboard ),
					'referring_url' => $this->get_referring_url( $hit_id, $is_affiliate_dashboard ),
					'medium'        => $this->get_medium( $hit_id ),
					'country'       => $this->get_country( $ip ),
					'is_converted'  => $this->is_converted( $hit_id ),
				);

				if ( ! $is_affiliate_dashboard ) {
					$report['ip'] = $ip;
				}

				if ( true === $args['get_user_agent_info'] ) {
					$user_agent                = $this->get_user_agent( $hit_id );
					$report['user_agent_info'] = AFWC_User_Agent_Parser::parse( $user_agent );
				}

				$reports[] = $report;
			}

			return $reports;
		}

		/**
		 * Method to get the date and time for the given hit ID.
		 *
		 * @param int    $hit_id The hit ID.
		 * @param string $datetime_format The datetime format.
		 * @param bool   $is_affiliate_dashboard Whether it is for the My account dashboard display.
		 *
		 * @return string The date and time.
		 */
		public function get_date_time( $hit_id = 0, $datetime_format = '', $is_affiliate_dashboard = false ) {
			if ( empty( $hit_id ) ) {
				return '';
			}

			$data = ! empty( $this->data ) ? $this->data : $this->get_data();
			if ( empty( $data ) || empty( $data[ $hit_id ] ) || empty( $data[ $hit_id ]['site_datetime'] ) ) {
				return '';
			}

			$datetime = $data[ $hit_id ]['site_datetime'];
			if ( $is_affiliate_dashboard && ! empty( $datetime ) ) {
				return gmdate( $datetime_format, strtotime( $datetime ) );
			}

			return $data[ $hit_id ]['site_datetime'];
		}

		/**
		 * Method to get the referring URL used to hit the page.
		 *
		 * @param int  $hit_id                 The hit ID.
		 * @param bool $is_affiliate_dashboard Whether to exclude AJAX/REST-like URLs for My account dashboard.
		 *
		 * @return string The sanitized referring URL, or empty string invalid.
		 */
		public function get_referring_url( $hit_id = 0, $is_affiliate_dashboard = false ) {
			if ( empty( $hit_id ) ) {
				return '';
			}

			$data = ! empty( $this->data ) ? $this->data : $this->get_data();
			if ( empty( $data ) || empty( $data[ $hit_id ] ) || empty( $data[ $hit_id ]['url'] ) ) {
				return '';
			}

			$url = $data[ $hit_id ]['url'];

			if ( $is_affiliate_dashboard ) {
				$unwanted_url_parts = array( 'admin-ajax.php', 'wp-json', 'wc-ajax', 'wp-content', 'favicon.ico' );
				foreach ( $unwanted_url_parts as $part ) {
					if ( strpos( $url, $part ) !== false ) {
						return '';
					}
				}
			}

			return esc_url_raw( $url );
		}

		/**
		 * Method to gets the visit medium.
		 *
		 * @param int $hit_id The hit ID.
		 *
		 * @return string The visit medium.
		 */
		public function get_medium( $hit_id = 0 ) {
			if ( empty( $hit_id ) ) {
				return '';
			}

			$data = ( ! empty( $this->data ) ? $this->data : $this->get_data() );

			$medium = ! empty( $data ) && ! empty( $data[ $hit_id ] ) && ! empty( $data[ $hit_id ]['type'] ) ? sanitize_key( $data[ $hit_id ]['type'] ) : '';
			if ( empty( $medium ) ) {
				return '';
			}

			$medium_registry = is_callable( array( Referral_Medium_Registry::class, 'get_instance' ) ) ? Referral_Medium_Registry::get_instance() : null;

			$medium_obj = is_callable( array( $medium_registry, 'get_registered' ) )
				? $medium_registry->get_registered( $medium )
				: null;

			return ! empty( $medium_obj ) && $medium_obj instanceof Referral_Medium_Interface ? $medium_obj->get_label() : $medium;
		}

		/**
		 * Method to gets the visit IP Address.
		 *
		 * @param int $hit_id The hit ID.
		 *
		 * @return string The IP address.
		 */
		public function get_ip( $hit_id = 0 ) {
			if ( empty( $hit_id ) ) {
				return '';
			}

			$data = ! empty( $this->data ) ? $this->data : $this->get_data();

			// Decode IPv4.
			$ip = ! empty( $data ) && ! empty( $data[ $hit_id ] ) && ! empty( $data[ $hit_id ]['ip'] ) ? $data[ $hit_id ]['ip'] : '';

			// Checking the numeric value for backward compatibility.
			return ( is_numeric( $ip ) ? long2ip( $ip ) : $ip );
		}

		/**
		 * Method to get the country information based on IP address.
		 *
		 * @param string $ip_address The IP address to geolocate.
		 *
		 * @return array Associative array containing:
		 *               - 'code': Country code (e.g., 'IN').
		 *               - 'name': Full country name (e.g., 'India').
		 */
		public function get_country( $ip_address ) {
			$empty_country_data = array(
				'code' => '',
				'name' => '',
			);

			if ( empty( $ip_address ) || ! is_string( $ip_address ) ) {
				return $empty_country_data;
			}

			if ( ! is_callable( array( 'WC_Geolocation', 'geolocate_ip' ) ) ) {
				return $empty_country_data;
			}

			$country_data = WC_Geolocation::geolocate_ip( $ip_address );
			if ( is_array( $country_data ) && ! empty( $country_data['country'] ) ) {
				return array(
					'code' => $country_data['country'],
					'name' => $this->get_country_fullname( $country_data['country'] ),
				);
			}

			return $empty_country_data;
		}

		/**
		 * Method to get the full country name from a country code.
		 *
		 * @see WC_Countries::get_countries()
		 *
		 * @param string $country_code The country code (e.g., 'IN').
		 *
		 * @return string Full country name or an empty string if not found.
		 */
		public function get_country_fullname( $country_code ) {
			if ( empty( $country_code ) || ! is_string( $country_code ) ) {
				return '';
			}

			if ( ! function_exists( 'WC' ) ) {
				return '';
			}

			$countries_instance = WC()->countries;
			if ( ! is_object( $countries_instance ) || ! is_callable( array( $countries_instance, 'get_countries' ) ) ) {
				return '';
			}

			$countries = $countries_instance->get_countries();
			if ( is_array( $countries ) && ! empty( $countries[ $country_code ] ) ) {
				return $countries[ $country_code ];
			}

			return '';
		}

		/**
		 * Method to gets the user agent.
		 *
		 * @param int $hit_id The hit ID.
		 *
		 * @return string The user agent string.
		 */
		public function get_user_agent( $hit_id = 0 ) {
			if ( empty( $hit_id ) ) {
				return '';
			}

			$data = ( ! empty( $this->data ) ? $this->data : $this->get_data() );

			return ( ! empty( $data ) && ! empty( $data[ $hit_id ] ) && ! empty( $data[ $hit_id ]['user_agent'] ) ? $data[ $hit_id ]['user_agent'] : '' );
		}

		/**
		 * Method to check whether the hit is converted to referral or not.
		 *
		 * @param int $hit_id The hit ID.
		 *
		 * @return bool The Return true if converted otherwise false.
		 */
		public function is_converted( $hit_id = 0 ) {
			if ( empty( $hit_id ) ) {
				return false;
			}

			$data = ( ! empty( $this->data ) ? $this->data : $this->get_data() );

			return ( ! empty( $data ) && ! empty( $data[ $hit_id ] ) && ! empty( $data[ $hit_id ]['is_converted'] ) && 'yes' === $data[ $hit_id ]['is_converted'] );
		}

		/**
		 * Method to set raw data from DB using provided filters.
		 */
		private function set_data_from_db() {
			global $wpdb;

			if ( ! empty( $this->affiliate_ids ) ) {
				if ( count( $this->affiliate_ids ) === 1 ) {
					$affiliate_id = current( $this->affiliate_ids );
					if ( ! empty( $this->from ) && ! empty( $this->to ) ) {
						$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT
									DISTINCT hit.id,
									hit.affiliate_id,
									IFNULL(hit.type, '') AS type,
									IFNULL(hit.ip, '') AS ip,
									IFNULL(hit.url, '') AS url,
									IFNULL(hit.user_agent, '') AS user_agent,
                                	DATE_FORMAT(CONVERT_TZ(hit.datetime, '+00:00', %s), %s) AS site_datetime
								FROM {$wpdb->prefix}afwc_hits AS hit
								WHERE hit.affiliate_id = %d
									AND hit.datetime BETWEEN %s AND %s
								ORDER BY hit.id DESC
								LIMIT %d, %d",
								AFWC_TIMEZONE_STR,
								'%d-%b-%Y %H:%i:%s',
								$affiliate_id,
								$this->from,
								$this->to,
								intval( $this->start_limit ),
								intval( $this->batch_limit )
							),
							'ARRAY_A'
						);
					} else {
						$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT
									DISTINCT hit.id,
									hit.affiliate_id,
									IFNULL(hit.type, '') AS type,
									IFNULL(hit.ip, '') AS ip,
									IFNULL(hit.url, '') AS url,
									IFNULL(hit.user_agent, '') AS user_agent,
									DATE_FORMAT(CONVERT_TZ(hit.datetime, '+00:00', %s), %s) AS site_datetime
								FROM {$wpdb->prefix}afwc_hits AS hit
								WHERE hit.affiliate_id = %d
								ORDER BY hit.id DESC
								LIMIT %d, %d",
								AFWC_TIMEZONE_STR,
								'%d-%b-%Y %H:%i:%s',
								$affiliate_id,
								intval( $this->start_limit ),
								intval( $this->batch_limit )
							),
							'ARRAY_A'
						);
					}
				} else {
					$option_nm = 'afwc_visits_details_affiliate_ids_' . uniqid();
					update_option( $option_nm, implode( ',', $this->affiliate_ids ), 'no' );

					if ( ! empty( $this->from ) && ! empty( $this->to ) ) {
						$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT
									DISTINCT hit.id,
									hit.affiliate_id,
									IFNULL(hit.type, '') AS type,
									IFNULL(hit.ip, '') AS ip,
									IFNULL(hit.url, '') AS url,
									IFNULL(hit.user_agent, '') AS user_agent,
									DATE_FORMAT(CONVERT_TZ(hit.datetime, '+00:00', %s), %s) AS site_datetime
								FROM {$wpdb->prefix}afwc_hits AS hit
								WHERE FIND_IN_SET ( hit.affiliate_id, ( SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s ) )
									AND hit.datetime BETWEEN %s AND %s
								ORDER BY hit.id DESC
								LIMIT %d, %d",
								AFWC_TIMEZONE_STR,
								'%d-%b-%Y %H:%i:%s',
								$option_nm,
								$this->from,
								$this->to,
								intval( $this->start_limit ),
								intval( $this->batch_limit )
							),
							'ARRAY_A'
						);
					} else {
						$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT 
									DISTINCT hit.id,
									hit.affiliate_id,
									IFNULL(hit.type, '') AS type,
									IFNULL(hit.ip, '') AS ip,
									IFNULL(hit.url, '') AS url,
									IFNULL(hit.user_agent, '') AS user_agent,
									DATE_FORMAT(CONVERT_TZ(hit.datetime, '+00:00', %s), %s) AS site_datetime
								FROM {$wpdb->prefix}afwc_hits AS hit
								WHERE FIND_IN_SET ( hit.affiliate_id, ( SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s ) )
								ORDER BY hit.id DESC
								LIMIT %d, %d",
								AFWC_TIMEZONE_STR,
								'%d-%b-%Y %H:%i:%s',
								$option_nm,
								intval( $this->start_limit ),
								intval( $this->batch_limit )
							),
							'ARRAY_A'
						);
					}

					delete_option( $option_nm );
				}
			} elseif ( ! empty( $this->from ) && ! empty( $this->to ) ) {
				$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT
							DISTINCT hit.id,
							hit.affiliate_id,
							IFNULL(hit.type, '') AS type,
							IFNULL(hit.ip, '') AS ip,
							IFNULL(hit.url, '') AS url,
							IFNULL(hit.user_agent, '') AS user_agent,
							DATE_FORMAT(CONVERT_TZ(hit.datetime, '+00:00', %s), %s) AS site_datetime
						FROM {$wpdb->prefix}afwc_hits AS hit
						WHERE hit.affiliate_id != %d
							AND hit.datetime BETWEEN %s AND %s
						ORDER BY hit.id DESC
						LIMIT %d, %d",
						AFWC_TIMEZONE_STR,
						'%d-%b-%Y %H:%i:%s',
						0,
						$this->from,
						$this->to,
						intval( $this->start_limit ),
						intval( $this->batch_limit )
					),
					'ARRAY_A'
				);
			} else {
				$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT 
							DISTINCT hit.id,
							hit.affiliate_id,
							IFNULL(hit.type, '') AS type,
							IFNULL(hit.ip, '') AS ip,
							IFNULL(hit.url, '') AS url,
							IFNULL(hit.user_agent, '') AS user_agent,
							DATE_FORMAT(CONVERT_TZ(hit.datetime, '+00:00', %s), %s) AS site_datetime
						FROM {$wpdb->prefix}afwc_hits AS hit
						WHERE hit.affiliate_id != %d
						ORDER BY hit.id DESC
						LIMIT %d, %d",
						AFWC_TIMEZONE_STR,
						'%d-%b-%Y %H:%i:%s',
						0,
						intval( $this->start_limit ),
						intval( $this->batch_limit )
					),
					'ARRAY_A'
				);
			}

			if ( empty( $results ) || ! is_array( $results ) ) {
				return;
			}

			$converted_ids = array();
			$hit_ids       = array_column( $results, 'id' );
			if ( ! empty( $hit_ids ) ) {
				$option_name = 'afwc_temp_hit_ids_' . uniqid();
				update_option( $option_name, implode( ',', $hit_ids ), 'no' );

				$ref_hit_affiliate_map = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT CONCAT(ref.hit_id, '_', ref.affiliate_id, '_', ref.type) AS map_key
						FROM {$wpdb->prefix}afwc_referrals as ref
						WHERE FIND_IN_SET( ref.hit_id, (SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s ) )",
						$option_name
					)
				);

				if ( ! empty( $ref_hit_affiliate_map ) && is_array( $ref_hit_affiliate_map ) ) {
					$ref_hit_affiliate_map = array_flip( $ref_hit_affiliate_map );
					foreach ( $results as $hit ) {
						if ( isset( $ref_hit_affiliate_map[ $hit['id'] . '_' . $hit['affiliate_id'] . '_' . $hit['type'] ] ) ) {
							$converted_ids[] = $hit['id'];
						}
					}
				}

				delete_option( $option_name );
			}

			foreach ( $results as $row ) {
				if ( empty( $row['id'] ) ) {
					continue;
				}

				$row['is_converted'] = ! empty( $converted_ids ) && is_array( $converted_ids ) && in_array( $row['id'], $converted_ids, true ) ? 'yes' : 'no';

				$this->data[ $row['id'] ] = $row;
			}
		}

		/**
		 * Returns SVG icon for the conversion type.
		 *
		 * @param string $is_converted Whether the visit is converted (yes, no).
		 * @return string SVG tag.
		 */
		public static function afwc_get_is_converted_svg( $is_converted = 'no' ) {
			$is_converted_svg = array(
				'yes' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="yes">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
					</svg>',
				'no'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="no">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
					</svg>',
			);

			return ( ! empty( $is_converted ) && ! empty( $is_converted_svg[ $is_converted ] ) ? $is_converted_svg[ $is_converted ] : '-' );
		}

		/**
		 * Returns SVG icon for the specified device type.
		 *
		 * @param string $device_type Device type (mobile, tablet, desktop).
		 * @return string SVG tag.
		 */
		public static function afwc_get_device_type_svg( $device_type = '' ) {
			$device_type_svg = array(
				'mobile'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
						<title>' . _x( 'Mobile', 'Icon title for mobile', 'affiliate-for-woocommerce' ) . '</title>
					</svg>',
				'tablet'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 002.25-2.25v-15a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25z" />
						<title>' . _x( 'Tablet', 'Icon title for tablet', 'affiliate-for-woocommerce' ) . '</title>
					</svg>',
				'desktop' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
						<title>' . _x( 'Desktop', 'Icon title for desktop', 'affiliate-for-woocommerce' ) . '</title>
					</svg>',
			);

			return ( ! empty( $device_type ) && ! empty( $device_type_svg[ $device_type ] ) ? $device_type_svg[ $device_type ] : '-' );
		}
	}
}
