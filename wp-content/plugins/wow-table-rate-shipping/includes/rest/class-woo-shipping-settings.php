<?php // phpcs:ignore

namespace WTRS\Includes\Rest;

use WTRS\Includes\Utils\Flags;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Woo Shipping Settings
 */
class WooShippingSettings {

	/**
	 * Setup class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'wtrs_register_shipping_regions_route' ) );
	}

	/**
	 * Register REST API route for shipping regions.
	 */
	public function wtrs_register_shipping_regions_route() {
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-regions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_shipping_regions' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Callback function to get shipping regions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|void
	 */
	public function get_shipping_regions( $request ) {
		if ( ! class_exists( 'WC_Countries' ) ) {
			if ( function_exists( 'WC' ) ) {
				include_once WC()->plugin_path() . '/includes/class-wc-countries.php';
			} else {
				return new \WP_Error( 'woocommerce_not_loaded', 'WooCommerce is not active or loaded', array( 'status' => 500 ) );
			}
		}

		$countries_obj = new \WC_Countries();
		$countries     = $countries_obj->get_countries();
		$states        = $countries_obj->get_states();

		// Get continents.
		$continents_data = WC()->countries->get_continents();

		$response = array();

		$continent_name_map = array(
			'AF' => 'Africa',
			'AN' => 'Antarctica',
			'AS' => 'Asia',
			'EU' => 'Europe',
			'NA' => 'North America',
			'OC' => 'Oceania',
			'SA' => 'South America',
		);

		foreach ( $continents_data as $continent_code => $continent_data ) {
			$continent_name = isset( $continent_name_map[ $continent_code ] )
				? $continent_name_map[ $continent_code ]
				: $continent_code;

			$continent_countries = $continent_data['countries'] ?? array();

			$formatted_countries = array();

			foreach ( $continent_countries as $country_code ) {
				if ( isset( $countries[ $country_code ] ) ) {

					$_states = array();

					if ( isset( $states[ $country_code ] ) ) {
						foreach ( $states[ $country_code ] as $state_code => $state_name ) {
							$_states[] = array(
								'state_code' => $country_code . '#' . $state_code,
								'state_name' => $state_name,
							);
						}
					}

					$formatted_countries[] = array(
						'country_code' => $country_code,
						'country_name' => $countries[ $country_code ],
						'states'       => $_states,
					);
				}
			}

			$response[] = array(
				'continent_code' => $continent_code,
				'continent_name' => $continent_name,
				'countries'      => $formatted_countries,
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $response,
			)
		);
	}

	/**
	 * Check permission for API access.
	 *
	 * @return boolean
	 */
	public function check_permission() {
		return Flags::is_user_admin();
	}
}
