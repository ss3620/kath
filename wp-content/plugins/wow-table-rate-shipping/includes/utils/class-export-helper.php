<?php // phpcs:ignore

namespace WTRS\Includes\Utils;

use WTRS\Includes\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Export Helper Class
 */
class ExportHelper {

	/**
	 * Get the export rules data.
	 *
	 * @param string $status Publish mode status.
	 * @param string $search Search string.
	 * @return array
	 */
	public static function export_rules( $status = 'all', $search = '' ) {
		$rules = DB::get_instance()->get_shipping_rules();

		$data = array();

		foreach ( $rules as $rule ) {
			if ( 'all' !== $status && $rule['publishMode'] !== $status ) {
				continue;
			}

			if ( ! empty( $search ) && ! str_contains( strtolower( $rule['generalSettings']['scenarioName'] ), strtolower( $search ) ) ) {
				continue;
			}

			$woo_zone_payload = '';

			$zone_ref = $rule['generalSettings']['shippingZone'] ?? null;
			$zone_id  = null;

			if ( is_array( $zone_ref ) ) {
				if ( isset( $zone_ref['id'] ) ) {
					$zone_id = (int) $zone_ref['id'];
				} elseif ( isset( $zone_ref['zone_id'] ) ) {
					$zone_id = (int) $zone_ref['zone_id'];
				} elseif ( isset( $zone_ref['value'] ) ) {
					$zone_id = (int) $zone_ref['value'];
				}
			} elseif ( is_scalar( $zone_ref ) && '' !== $zone_ref && null !== $zone_ref ) {
				$zone_id = (int) $zone_ref;
			}

			if ( -1 === $zone_id ) {
				$woo_zone_payload = str_replace(
					',',
					'&&&',
					wp_json_encode(
						array(
							'id'        => -1,
							'name'      => 'Global',
							'order'     => 0,
							'locations' => array(),
						)
					)
				);
			} elseif ( $zone_id > 0 ) {
				$zone      = new \WC_Shipping_Zone( $zone_id );
				$locations = array();
				foreach ( (array) $zone->get_zone_locations() as $loc ) {
					$locations[] = array(
						'type' => is_object( $loc ) ? ( $loc->type ?? '' ) : ( is_array( $loc ) ? ( $loc['type'] ?? '' ) : '' ),
						'code' => is_object( $loc ) ? ( $loc->code ?? '' ) : ( is_array( $loc ) ? ( $loc['code'] ?? '' ) : '' ),
					);
				}

				$woo_zone_data = array(
					'id'        => (int) $zone->get_id(),
					'name'      => $zone->get_zone_name(),
					'order'     => (int) $zone->get_zone_order(),
					'locations' => $locations,
				);

				$woo_zone_payload = str_replace( ',', '&&&', wp_json_encode( $woo_zone_data ) );
			}

			$data[] = array(
				'id'              => $rule['id'],
				'generalSettings' => str_replace( ',', '&&&', wp_json_encode( $rule['generalSettings'] ) ),
				'shippingMethods' => str_replace( ',', '&&&', wp_json_encode( $rule['shippingMethods'] ) ),
				'tax'             => str_replace( ',', '&&&', wp_json_encode( $rule['tax'] ) ),
				'visibleToUser'   => $rule['visibleToUser'],
				'handlingFee'     => str_replace( ',', '&&&', wp_json_encode( $rule['handlingFee'] ) ),
				'publishMode'     => $rule['publishMode'],
				'version'         => $rule['version'] ?? '2',
				'wooZone'         => $woo_zone_payload,
			);
		}

		return array(
			'filename' => 'wowshipping_methods_' . current_time( 'mysql' ) . '.csv',
			'data'     => $data,
		);
	}
}
