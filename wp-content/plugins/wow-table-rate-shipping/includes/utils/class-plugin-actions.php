<?php // phpcs:ignore

namespace WTRS\Includes\Utils;

use WTRS\Includes\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Actions
 */
class PluginActions {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . WTRS_BASE, array( $this, 'plugin_action_links_callback' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_settings_meta' ), 10, 2 );
	}

	/**
	 * Adds quick action links below the plugin name.
	 *
	 * @param array $links Default plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function plugin_action_links_callback( $links ) {

		$offer_config = array(
			array(
				'start'  => '2026-05-07 00:00 Asia/Dhaka',
				'end'    => '2026-05-21 23:59 Asia/Dhaka',
				'text'   => __(
					'Flash Sale - Up to 55% OFF',
					'wow-table-rate-shipping'
				),
				'utmKey' => 'flash',
			),
			array(
				'start'  => '2026-05-22 00:00 Asia/Dhaka',
				'end'    => '2026-06-01 23:59 Asia/Dhaka',
				'text'   => __(
					'Surprise Sale - Up to 60% OFF',
					'wow-table-rate-shipping'
				),
				'utmKey' => 'surprise',
			),
			array(
				'start'  => '2026-06-02 00:00 Asia/Dhaka',
				'end'    => '2026-06-20 23:59 Asia/Dhaka',
				'text'   => __(
					'Massive Sale - Up to 60% OFF',
					'wow-table-rate-shipping'
				),
				'utmKey' => 'massive',
			),
			array(
				'start'  => '2026-06-21 00:00 Asia/Dhaka',
				'end'    => '2026-06-30 23:59 Asia/Dhaka',
				'text'   => __(
					'Final Hour Sale - Up to 60% OFF',
					'wow-table-rate-shipping'
				),
				'utmKey' => 'final-hour',
			),
		);

		$setting_link = array(
			'wtrs_rules' => '<a href="' . esc_url( admin_url( 'admin.php?page=wtrs-dashboard#shipping-methods' ) ) . '">' . esc_html__( 'Create Shipping', 'wow-table-rate-shipping' ) . '</a>',
			// 'wtrs_rules' => '<a href="' . esc_url( admin_url( 'admin.php?page=wtrs-dashboard#shipping-methods-add' ) ) . '">' . esc_html__( 'Create Shipping', 'wow-table-rate-shipping' ) . '</a>',
			// 'wtrs_analytics' => '<a href="' . esc_url( admin_url( 'admin.php?page=wtrs-dashboard#logs-analytics' ) ) . '">' . esc_html__( 'Analytics', 'wow-table-rate-shipping' ) . '</a>',
		);

		$upgrade_link = array();

		// Free user or expired license user.
		if ( ! defined( 'WTRS_PRO_VER' ) || Xpo::is_lc_expired() ) {

			$license_key = Xpo::get_lc_key() ?? '';

			if ( Xpo::is_lc_expired() ) {
				$text = esc_html__( 'Renew License', 'wow-table-rate-shipping' );
				$url  = 'https://account.wpxpo.com/checkout/?edd_license_key=' . $license_key;
			} else {

				$text = esc_html__( 'Upgrade to Pro', 'wow-table-rate-shipping' );
				$url  = Xpo::generate_utm_link();

				foreach ( $offer_config as $offer ) {
					$current_time = gmdate( 'U' );
					$notice_start = gmdate( 'U', strtotime( $offer['start'] ) );
					$notice_end   = gmdate( 'U', strtotime( $offer['end'] ) );
					if ( $current_time >= $notice_start && $current_time <= $notice_end ) {
						$url  = Xpo::generate_utm_link(
							array(
								'utmKey' => $offer['utmKey'],
							)
						);
						$text = $offer['text'];
						break;
					}
				}
			}

			$upgrade_link['wtrs_pro'] = '<a style="color: #0062ff; font-weight: bold;" target="_blank" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
		}

		return array_merge( $setting_link, $links, $upgrade_link );
	}

	/**
	 * Adds extra links to the plugin row meta on the plugins page.
	 *
	 * @param array  $links Existing plugin meta links.
	 * @param string $file  Plugin file path.
	 * @return array Modified plugin meta links.
	 */
	public function plugin_settings_meta( $links, $file ) {
		if ( strpos( $file, 'wow-table-rate-shipping.php' ) !== false ) {
			$new_links = array(
				'wtrs_docs'    => '<a href="https://wpxpo.com/docs/wowshipping/" target="_blank">' . esc_html__( 'Docs', 'wow-table-rate-shipping' ) . '</a>',
				'wtrs_support' => '<a href="https://www.wpxpo.com/contact/" target="_blank">' . esc_html__( 'Support', 'wow-table-rate-shipping' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}
}
