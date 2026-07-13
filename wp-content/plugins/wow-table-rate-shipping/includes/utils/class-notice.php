<?php //phpcs:ignore

namespace WTRS\Includes\Utils;

use WTRS\Includes\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Notice
 */
class Notice {


	/**
	 * Notice version
	 *
	 * @var string
	 */
	private $notice_version = 'v103';

	/**
	 * Notice JS/CSS applied
	 *
	 * @var boolean
	 */
	private $notice_js_css_applied = false;


	/**
	 * Notice Constructor
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices_callback' ) );
		add_action( 'admin_init', array( $this, 'set_dismiss_notice_callback' ) );

		// REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );

		// Woocommerce Install Action.
		add_action( 'wp_ajax_wtrs_install', array( $this, 'install_activate_plugin' ) );
	}


	/**
	 * Registers REST API endpoints.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		$routes = array(
			// Hello Bar.
			array(
				'endpoint'            => 'hello_bar',
				'methods'             => 'POST',
				'callback'            => array( $this, 'hello_bar_callback' ),
				'permission_callback' => function () {
					return Flags::is_user_admin();
				},
			),
		);

		foreach ( $routes as $route ) {
			register_rest_route(
				'wtrs/v1',
				$route['endpoint'],
				array(
					array(
						'methods'             => $route['methods'],
						'callback'            => $route['callback'],
						'permission_callback' => $route['permission_callback'],
					),
				)
			);
		}
	}

	/**
	 * Hellobar config
	 *
	 * @return array
	 */
	public static function get_hellobar_config() {
		return array(
			'wtrs_helloBar_flash_sale_2026_2'    => Xpo::get_transient_without_cache( 'wtrs_helloBar_flash_sale_2026_2' ),
			'wtrs_helloBar_surprise_sale_2026'   => Xpo::get_transient_without_cache( 'wtrs_helloBar_surprise_sale_2026' ),
			'wtrs_helloBar_massive_sale_2026'    => Xpo::get_transient_without_cache( 'wtrs_helloBar_massive_sale_2026' ),
			'wtrs_helloBar_final_hour_sale_2026' => Xpo::get_transient_without_cache( 'wtrs_helloBar_final_hour_sale_2026' ),
		);
	}

	/**
	 * Handles Hello Bar dismissal action via REST API .
	 *
	 * @param \WP_REST_Request $request REST request object .
	 * @return \WP_REST_Response
	 */
	public function hello_bar_callback( \WP_REST_Request $request ) {
		$request_params = $request->get_params();
		$type           = isset( $request_params['type'] ) ? $request_params['type'] : '';
		$id             = isset( $request_params['id'] ) ? $request_params['id'] : '';

		if ( 'hello_bar' === $type && ! empty( $id ) ) {
			Xpo::set_transient_without_cache( $id, 'hide', 1296000 );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Hello Bar Action performed', 'wow-table-rate-shipping' ),
			),
			200
		);
	}

	/**
	 * Set Notice Dismiss Callback
	 *
	 * @return void
	 */
	public function set_dismiss_notice_callback() {

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['wpnonce'] ?? '' ) ), 'wtrs-nonce' ) ) {
			return;
		}

		$durbin_key = sanitize_text_field( wp_unslash( $_GET['wtrs_durbin_key'] ?? '' ) );

		// Durbin notice dismiss.
		if ( ! empty( $durbin_key ) ) {
			Xpo::set_transient_without_cache( 'wtrs_durbin_notice_' . $durbin_key, 'off' );

			if ( 'get' === sanitize_text_field( wp_unslash( $_GET['wtrs_get_durbin'] ?? '' ) ) ) {
				DurbinClient::send( DurbinClient::ACTIVATE_ACTION );
			}
		}

		// Install notice dismiss.
		$install_key = sanitize_text_field( wp_unslash( $_GET['wtrs_install_key'] ?? '' ) );
		if ( ! empty( $install_key ) ) {
			Xpo::set_transient_without_cache( 'wtrs_install_notice_' . $install_key, 'off' );
		}

		$notice_key = sanitize_text_field( wp_unslash( $_GET['disable_wtrs_notice'] ?? '' ) );
		if ( ! empty( $notice_key ) ) {
			$interval = (int) sanitize_text_field( wp_unslash( $_GET['wtrs_interval'] ?? '' ) );
			if ( ! empty( $interval ) ) {
				Xpo::set_transient_without_cache( 'wtrs_get_pro_notice_' . $notice_key, 'off', $interval );
			} else {
				Xpo::set_transient_without_cache( 'wtrs_get_pro_notice_' . $notice_key, 'off' );
			}
		}
	}

	/**
	 * Admin Notices Callback
	 *
	 * @return void
	 */
	public function admin_notices_callback() {
		$this->wtrs_dashboard_notice_callback();
		$this->wtrs_dashboard_durbin_notice_callback();
		$this->wtrs_dashboard_content_notice();
	}

	/**
	 * Admin Dashboard Notice Callback
	 *
	 * @return void
	 */
	public function wtrs_dashboard_notice_callback() {
		$this->wtrs_dashboard_banner_notice();
	}

	/**
	 * Dashboard Content Notice
	 *
	 * @return void
	 */
	public function wtrs_dashboard_content_notice() {

		$content_notices = array(
			array(
				'key'                => 'wtrs_text_banner_flash_sale_2026_1',
				'start'              => '2026-05-07 00:00 Asia/Dhaka',
				'end'                => '2026-05-12 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => 'Flash Sale:',
				'content_subheading' => 'Enjoy up to %s off on WowShipping Pro.',
				'discount_content'   => '55% OFF',
				'border_color'       => '#0c54fc',
				'icon'               => WTRS_URL . 'assets/img/banners/icon.svg',
				'button_text'        => 'Claim Your Discount!',
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wtrs_text_banner_flash_sale_2026_2',
				'start'              => '2026-05-13 00:00 Asia/Dhaka',
				'end'                => '2026-05-17 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => 'Flash Sale:',
				'content_subheading' => 'Enjoy up to %s off on WowShipping Pro.',
				'discount_content'   => '55% OFF',
				'border_color'       => '#0c54fc',
				'icon'               => WTRS_URL . 'assets/img/banners/discount_55.svg',
				'button_text'        => 'Claim Your Discount!',
				'is_discount_logo'   => true,
			),

			// Surprise Sale.
			array(
				'key'                => 'wtrs_text_banner_surprise_sale_2026_1',
				'start'              => '2026-05-22 00:00 Asia/Dhaka',
				'end'                => '2026-05-25 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'surprise',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => 'Surprise Sale:',
				'content_subheading' => 'Enjoy up to %s off on WowShipping Pro.',
				'discount_content'   => '60% OFF',
				'border_color'       => '#0c54fc',
				'icon'               => WTRS_URL . 'assets/img/banners/icon.svg',
				'button_text'        => 'Claim Your Discount!',
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wtrs_text_banner_surprise_sale_2026_2',
				'start'              => '2026-05-26 00:00 Asia/Dhaka',
				'end'                => '2026-05-28 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'surprise',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => 'Surprise Sale:',
				'content_subheading' => 'Enjoy up to %s off on WowShipping Pro.',
				'discount_content'   => '60% OFF',
				'border_color'       => '#0c54fc',
				'icon'               => WTRS_URL . 'assets/img/banners/discount_60.svg',
				'button_text'        => 'Claim Your Discount!',
				'is_discount_logo'   => true,
			),

			// Massive Sale.
			array(
				'key'                => 'wtrs_text_banner_massive_sale_2026_1',
				'start'              => '2026-06-02 00:00 Asia/Dhaka',
				'end'                => '2026-06-10 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'massive',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => 'Massive Sale:',
				'content_subheading' => 'Enjoy up to %s off on WowShipping Pro.',
				'discount_content'   => '60% OFF',
				'border_color'       => '#0c54fc',
				'icon'               => WTRS_URL . 'assets/img/banners/icon.svg',
				'button_text'        => 'Claim Your Discount!',
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wtrs_text_banner_massive_sale_2026_2',
				'start'              => '2026-06-11 00:00 Asia/Dhaka',
				'end'                => '2026-06-16 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'massive',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => 'Massive Sale:',
				'content_subheading' => 'Enjoy up to %s off on WowShipping Pro.',
				'discount_content'   => '60% OFF',
				'border_color'       => '#0c54fc',
				'icon'               => WTRS_URL . 'assets/img/banners/discount_60.svg',
				'button_text'        => 'Claim Your Discount!',
				'is_discount_logo'   => true,
			),

			// Final Hour Sale.
			array(
				'key'                => 'wtrs_text_banner_final_hour_sale_2026_1',
				'start'              => '2026-06-21 00:00 Asia/Dhaka',
				'end'                => '2026-06-24 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final-hour',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => 'Final Hour Sale:',
				'content_subheading' => 'Enjoy up to %s off on WowShipping Pro.',
				'discount_content'   => '60% OFF',
				'border_color'       => '#0c54fc',
				'icon'               => WTRS_URL . 'assets/img/banners/icon.svg',
				'button_text'        => 'Claim Your Discount!',
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wtrs_text_banner_final_hour_sale_2026_2',
				'start'              => '2026-06-25 00:00 Asia/Dhaka',
				'end'                => '2026-06-27 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final-hour',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => 'Final Hour Sale:',
				'content_subheading' => 'Enjoy up to %s off on WowShipping Pro.',
				'discount_content'   => '60% OFF',
				'border_color'       => '#0c54fc',
				'icon'               => WTRS_URL . 'assets/img/banners/discount_60.svg',
				'button_text'        => 'Claim Your Discount!',
				'is_discount_logo'   => true,
			),
		);

		$wtrs_db_nonce = wp_create_nonce( 'wtrs-nonce' );
		$is_testing    = false;

		foreach ( $content_notices as $key => $notice ) {
			$notice_key = isset( $notice['key'] ) ? $notice['key'] : $this->notice_version;

			if ( ! $is_testing ) {
				if ( isset( $_GET['disable_wtrs_notice'] ) && $notice_key === $_GET['disable_wtrs_notice'] ) {
					continue;
				}

				if ( ! $notice['visibility'] ) {
					continue;
				}

				$current_time = gmdate( 'U' );
				$notice_start = gmdate( 'U', strtotime( $notice['start'] ) );
				$notice_end   = gmdate( 'U', strtotime( $notice['end'] ) );

				if ( $current_time < $notice_start || $current_time > $notice_end ) {
					continue;
				}

				$notice_transient = Xpo::get_transient_without_cache( 'wtrs_get_pro_notice_' . $notice_key );

				if ( 'off' === $notice_transient ) {
					continue;
				}
			}

			$border_color = $notice['border_color'];

			$query_args = array(
				'disable_wtrs_notice' => $notice_key,
				'wtrs_db_nonce'       => $wtrs_db_nonce,
			);
			if ( isset( $notice['repeat_interval'] ) && $notice['repeat_interval'] ) {
				$query_args['wtrs_interval'] = $notice['repeat_interval'];
			}

			$url = isset( $notice['url'] ) ? $notice['url'] : Xpo::generate_utm_link(
				array(
					'utmKey' => 'content_notice',
				)
			);

			?>

						<style id="wtrs-notice-css" type="text/css">
							.wtrs-content-notice-wrapper {
								border: 1px solid #c3c4c7;
								border-left: 3px solid #037fff;
								margin: 15px 0 !important;
								display: flex;
								align-items: center;
								background: #ffffff;
								width: 100%;
								padding: 10px 0;
								position: relative;
								box-sizing: border-box;
							}

							.wtrs-content-notice-wrapper.notice {
								margin: 10px 0;
								width: calc(100% - 20px);
							}

							.wrap .wtrs-content-notice-wrapper.notice {
								width: 100%;
							}

							.wtrs-content-notice-icon {
								margin-left: 15px;
							}

							.wtrs-content-notice-discout-icon {
								margin-left: 10px;
							}

							.wtrs-content-notice-icon img {
								max-width: 42px;
								height: 70px;
							}

							.wtrs-content-notice-discout-icon img {
								height: 70px;
								width: 70px;
							}

							.wtrs-notice-content-wrapper {
								display: flex;
								flex-direction: column;
								gap: 8px;
								font-size: 14px;
								line-height: 20px;
								margin-left: 15px;
							}

							.wtrs-content-notice-buttons {
								display: flex;
								align-items: center;
								gap: 15px;
							}

							.wtrs-content-notice-btn {
								font-weight: 600;
								text-transform: uppercase !important;
								padding: 2px 10px !important;
								background-color: #86a62c;
								border: none !important;
							}

							.wtrs-content-discount_btn {
								background-color: #ffffff;
								text-decoration: none;
								border: 1px solid #0c54fc;
								padding: 5px 10px;
								border-radius: 5px;
								font-weight: 500;
								text-transform: uppercase;
								color: #0c54fc !important;
							}

							.wtrs-content-notice-close {
								position: absolute;
								right: 2px;
								top: 5px;
								text-decoration: none;
								color: #b6b6b6;
								font-family: dashicons;
								font-size: 16px;
								line-height: 20px;
							}

							.wtrs-content-notice-close-icon {
								font-size: 14px;
							}
						</style>
					<div class="wtrs-content-notice-wrapper notice data_collection_notice" 
					style="border-left: 3px solid <?php echo esc_attr( $border_color ); ?>;"
					> 
					<?php
					if ( $notice['is_discount_logo'] ) {
						?>
								<div class="wtrs-content-notice-discout-icon"> <img src="<?php echo esc_url( $notice['icon'] ); ?>"/>  </div>
							<?php
					} else {
						?>
								<div class="wtrs-content-notice-icon"> <img src="<?php echo esc_url( $notice['icon'] ); ?>"/>  </div>
							<?php
					}
					?>
						
						<div class="wtrs-notice-content-wrapper">
							<div class="">
								<strong><?php printf( esc_html( $notice['content_heading'] ) ); ?> </strong>
						<?php
						printf(
							wp_kses_post( $notice['content_subheading'] ),
							'<strong>' . esc_html( $notice['discount_content'] ) . '</strong>'
						);
						?>
							</div>
							<div class="wtrs-content-notice-buttons">
					<?php if ( isset( $notice['is_discount_logo'] ) && $notice['is_discount_logo'] ) : ?>
									<a class="wtrs-content-discount_btn" href="<?php echo esc_url( $url ); ?>" target="_blank">
										<?php echo esc_html( $notice['button_text'] ); ?>
									</a>
								<?php else : ?>
									<a class="wtrs-content-notice-btn button button-primary" href="<?php echo esc_url( $url ); ?>" target="_blank" style="background-color: <?php echo ! empty( $notice['background_color'] ) ? esc_attr( $notice['background_color'] ) : '#86a62c'; ?>;">
									<?php echo esc_html( $notice['button_text'] ); ?>
										
									</a>
								<?php endif; ?>
							</div>
						</div>
						<a href=
						<?php
						echo esc_url(
							add_query_arg(
								$query_args
							)
						);
						?>
						class="wtrs-content-notice-close"><span class="wtrs-content-notice-close-icon dashicons dashicons-dismiss"> </span></a>
					</div>
						<?php

		}
	}

	/**
	 * Dashboard Banner Notice
	 *
	 * @return void
	 */
	public function wtrs_dashboard_banner_notice() {
		$wtrs_db_nonce  = wp_create_nonce( 'wtrs-nonce' );
		$is_testing     = false;
		$banner_notices = array(
			array(
				'key'                => 'wtrs_countdown_banner_flash_sale_2026_1',
				'start'              => '2026-05-18 00:00 Asia/Dhaka',
				'end'                => '2026-05-21 23:59 Asia/Dhaka',

				'brand_color'        => '#335cff',

				'left_image'         => WTRS_URL . '/assets/img/banners/flash-sale/left.png',
				'right_image'        => WTRS_URL . '/assets/img/banners/flash-sale/right.png',
				'bg_image'           => WTRS_URL . '/assets/img/banners/flash-sale/bg.png',
				'text'               => 'Deal ending soon',

				'countdown_duration' => 3 * DAY_IN_SECONDS, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
			),
			array(
				'key'                => 'wtrs_countdown_banner_surprise_sale_2026_1',
				'start'              => '2026-05-29 00:00 Asia/Dhaka',
				'end'                => '2026-06-01 23:59 Asia/Dhaka',

				'brand_color'        => '#335cff',

				'left_image'         => WTRS_URL . '/assets/img/banners/surprise-sale/left.png',
				'right_image'        => WTRS_URL . '/assets/img/banners/surprise-sale/right.png',
				'bg_image'           => WTRS_URL . '/assets/img/banners/surprise-sale/bg.png',
				'text'               => 'Deal ending soon',

				'countdown_duration' => 3 * DAY_IN_SECONDS, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'surprise',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
			),
			array(
				'key'                => 'wtrs_countdown_banner_massive_sale_2026_1',
				'start'              => '2026-06-17 00:00 Asia/Dhaka',
				'end'                => '2026-06-20 23:59 Asia/Dhaka',

				'brand_color'        => '#335cff',

				'left_image'         => WTRS_URL . '/assets/img/banners/massive-sale/left.png',
				'right_image'        => WTRS_URL . '/assets/img/banners/massive-sale/right.png',
				'bg_image'           => WTRS_URL . '/assets/img/banners/massive-sale/bg.png',
				'text'               => 'Deal ending soon',

				'countdown_duration' => 3 * DAY_IN_SECONDS, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'massive',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
			),
			array(
				'key'                => 'wtrs_countdown_banner_final_hour_sale_2026_1',
				'start'              => '2026-06-28 00:00 Asia/Dhaka',
				'end'                => '2026-06-30 23:59 Asia/Dhaka',

				'brand_color'        => '#335cff',

				'left_image'         => WTRS_URL . '/assets/img/banners/final-hour-sale/left.png',
				'right_image'        => WTRS_URL . '/assets/img/banners/final-hour-sale/right.png',
				'bg_image'           => WTRS_URL . '/assets/img/banners/final-hour-sale/bg.png',
				'text'               => 'Deal ending soon',

				'countdown_duration' => 3 * DAY_IN_SECONDS, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final-hour',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
			),
		);

		foreach ( $banner_notices as $notice ) {
			$notice_key = isset( $notice['key'] ) ? $notice['key'] : $this->notice_version;

			if ( ! $is_testing ) {
				if ( isset( $_GET['disable_wtrs_notice'] ) && $notice_key === sanitize_text_field( wp_unslash( $_GET['disable_wtrs_notice'] ) ) ) { // phpcs:ignore
					continue;
				}

				if ( ! $notice['visibility'] ) {
					continue;
				}

				$current_time = gmdate( 'U' );
				$notice_start = gmdate( 'U', strtotime( $notice['start'] ) );
				$notice_end   = gmdate( 'U', strtotime( $notice['end'] ) );

				if ( $current_time < $notice_start || $current_time > $notice_end ) {
					continue;
				}

				$notice_transient = Xpo::get_transient_without_cache( 'wtrs_get_pro_notice_' . $notice_key );

				if ( 'off' === $notice_transient ) {
					continue;
				}
			}

			if ( ! $this->notice_js_css_applied ) {
				$this->wtrs_banner_notice_js();
				$this->notice_js_css_applied = true;
			}
			$query_args = array(
				'disable_wtrs_notice' => $notice_key,
				'wpnonce'             => $wtrs_db_nonce,
			);
			if ( isset( $notice['repeat_interval'] ) && $notice['repeat_interval'] ) {
				$query_args['wtrs_interval'] = $notice['repeat_interval'];
			}
			?>
				<style type="text/css">
					.wtrs-notice-wrapper.wtrs-banner-notice {
						height: auto !important;
						min-height: 90px;
						padding: 0 !important;
						position: relative;
						box-sizing: border-box;
						background-repeat: no-repeat;
						background-size: cover;
						background-position: center;
					}
					.wtrs-notice-wrapper.wtrs-banner-notice .wtrs-banner-link {
						width: 100%;
						text-decoration: none;
						display: block;
					}
					.wtrs-notice-wrapper.wtrs-banner-notice .wtrs-banner-content {
						display: flex;
						justify-content: space-between;
						align-items: center;
						max-width: 1358px;
						margin: 0 auto;
						padding: 10px 16px;
						gap: 16px;
					}
					.wtrs-notice-wrapper.wtrs-banner-notice .wtrs-banner-side-image {
						display: block;
						max-width: 100%;
						height: auto;
					}
					.wtrs-notice-wrapper.wtrs-banner-notice .wtrs-banner-main {
						display: flex;
						flex-direction: column;
						gap: 4px;
						align-items: center;
						justify-content: center;
						font-weight: 700;
						font-size: 28px;
						color: #fff;
						line-height: 32px;
						text-align: center;
					}

					@media screen and (max-width: 1100px) {
						.wtrs-notice-wrapper.wtrs-banner-notice .wtrs-banner-content {
							flex-direction: column;
						}
					}

					@media screen and (max-width: 782px) {
						.wtrs-notice-wrapper.wtrs-banner-notice .wtrs-banner-content {
							justify-content: center;
							padding: 12px 32px 12px 12px;
						}
						.wtrs-notice-wrapper.wtrs-banner-notice .wtrs-banner-main {
							font-size: 22px;
							line-height: 28px;
						}
					}
					@media screen and (max-width: 480px) {
						.wtrs-notice-wrapper.wtrs-banner-notice .wtrs-banner-content {
							padding: 10px 32px 10px 10px;
						}
						.wtrs-notice-wrapper.wtrs-banner-notice .wtrs-banner-main {
							font-size: 18px;
							line-height: 24px;
						}
					}
				</style>
				<div 
					class="wtrs-notice-wrapper wtrs-banner-notice notice" 
					style="
						border-left: 3px solid <?php echo esc_attr( $notice['brand_color'] ); ?>;
						background-image: url('<?php echo esc_attr( $notice['bg_image'] ); ?>');
				">
					<a 
						class="wc-dismiss-notice dashicons dashicons-no-alt" 
						style="
							position: absolute;
							top: 1px;
							right: 1px;
							border-radius: 50%;
							background-color: black;
							color: white;
							font-size: 14px;
							display: flex;
							align-items: center;
							justify-content: center;
						"
						aria-label="<?php esc_html_e( 'Close Banner', 'wow-table-rate-shipping' ); ?>"
						href="<?php echo esc_url( add_query_arg( $query_args ) ); ?>">
					</a>

					<a class="wtrs-banner-link" target="_blank" href="<?php echo esc_url( $notice['url'] ); ?>">
						<div class="wtrs-banner-content">
							<img class="wtrs-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice['left_image'] ); ?>" />
							<div class="wtrs-banner-main">
								<span style="color:white;">
									<?php echo esc_html( $notice['text'] ); ?>
								</span>	
								<div 
									class="wtrs-notice-countdown" 
									style="
										color: <?php echo esc_attr( $notice['countdown_color'] ); ?>;
									"
									data-notice-key="<?php echo esc_attr( $notice_key . '-countdown' ); ?>" 
									data-duration="<?php echo esc_attr( $notice['countdown_duration'] ); ?>">
									00:00:00:00
								</div>
							</div>
							<img class="wtrs-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice['right_image'] ); ?>" />
						</div>
					</a>
				</div>
				<?php
		}
	}

	/**
	 * Banner JS
	 *
	 * @return void
	 */
	public function wtrs_banner_notice_js() {
		?>
		<script type="text/javascript">
			jQuery(function($) {
				'use strict';

				const storagePrefix = 'wtrs_notice_countdown_';

				const formatCountdown = function(seconds) {
					const days = Math.floor(seconds / 86400);
					const hours = Math.floor((seconds % 86400) / 3600);
					const minutes = Math.floor((seconds % 3600) / 60);
					const secs = seconds % 60;

					return String(days).padStart(2, '0') + ':' + String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
				};

				const parseDurationToSeconds = function(duration) {
					if (typeof duration === 'number' && Number.isFinite(duration) && duration > 0) {
						return Math.floor(duration);
					}

					const durationString = String(duration || '').trim();
					if (/^\d+$/.test(durationString)) {
						return parseInt(durationString, 10);
					}

					return 0;
				};

				const nowInSeconds = function() {
					return Math.floor(Date.now() / 1000);
				};

				$('.wtrs-notice-countdown').each(function() {
					const countdownElement = $(this);
					const noticeKey = String(countdownElement.data('noticeKey') || '');
					const duration = parseDurationToSeconds(countdownElement.data('duration'));

					if (!noticeKey || duration <= 0) {
						return;
					}

					const storageKey = storagePrefix + noticeKey;
					let endAt = 0;

					try {
						const storedDataRaw = window.localStorage.getItem(storageKey);
						if (storedDataRaw) {
							const storedData = JSON.parse(storedDataRaw);
							if (storedData && parseInt(storedData.duration, 10) === duration) {
								endAt = parseInt(storedData.endAt, 10) || 0;
							}
						}
					} catch (error) {
						endAt = 0;
					}

					const saveTimerState = function(nextEndAt) {
						try {
							window.localStorage.setItem(
								storageKey,
								JSON.stringify({
									endAt: nextEndAt,
									duration: duration,
								})
							);
						} catch (error) {
							// No-op.
						}
					};

					const resetTimer = function(currentTime) {
						endAt = currentTime + duration;
						saveTimerState(endAt);
					};

					const tick = function() {
						const currentTime = nowInSeconds();

						if (endAt <= currentTime) {
							resetTimer(currentTime);
						}

						const remaining = Math.max(endAt - currentTime, 0);
						countdownElement.text(formatCountdown(remaining));
					};

					if (endAt <= nowInSeconds()) {
						resetTimer(nowInSeconds());
					}

					tick();
					window.setInterval(tick, 1000);
				});
			});
		</script>
		<?php
	}


	/**
	 * The Durbin Html
	 *
	 * @return void
	 */
	public function wtrs_dashboard_durbin_notice_callback() {
		$durbin_key = 'wtrs_durbin_dc1';

		if (
			isset( $_GET['wtrs_durbin_key'] ) || // phpcs:ignore
			'off' === Xpo::get_transient_without_cache( 'wtrs_durbin_notice_' . $durbin_key )
		) {
			return;
		}

		if ( ! $this->notice_js_css_applied ) {
			$this->notice_js_css_applied = true;
		}

		$wtrs_db_nonce = wp_create_nonce( 'wtrs-nonce' );

		?>
		<style>
				.wtrs-consent-box {
					width: 656px;
					padding: 16px;
					border: 1px solid #070707;
					border-left-width: 4px;
					border-radius: 4px;
					background-color: #fff;
					position: relative;
					width: 100%;
					box-sizing: border-box;
				}
				.wtrs-consent-content {
					display: flex;
					justify-content: flex-start;
					align-items: flex-end;
					gap: 26px;
				}
 
				.wtrs-consent-text-first {
					font-size: 14px;
					font-weight: 600;
					color: #070707;
				}
				.wtrs-consent-text-last {
					margin: 4px 0 0;
					font-size: 14px;
					color: #070707;
				}
 
				.wtrs-consent-accept {
					background-color: #070707;
					color: #fff;
					border: none;
					padding: 6px 10px;
					border-radius: 4px;
					cursor: pointer;
					font-size: 12px;
					font-weight: 600;
					text-decoration: none;
				}
				.wtrs-consent-accept:hover {
					background-color:rgb(38, 38, 38);
					color: #fff;
				}
			</style>
			<div class="wtrs-consent-box wtrs-notice-wrapper notice data_collection_notice">
			<div class="wtrs-consent-content">
			<div class="wtrs-consent-text">
			<div class="wtrs-consent-text-first"><?php esc_html_e( 'Want to help make WowShipping even more awesome?', 'wow-table-rate-shipping' ); ?></div>
			<div class="wtrs-consent-text-last">
					<?php esc_html_e( 'Allow us to collect diagnostic data and usage information. see ', 'wow-table-rate-shipping' ); ?>
			<a href="https://www.wpxpo.com/data-collection-policy/" target="_blank" ><?php esc_html_e( 'what we collect.', 'wow-table-rate-shipping' ); ?></a>
			</div>
			</div>
			<a
					class="wtrs-consent-accept"
					href=
					<?php
									echo esc_url(
										add_query_arg(
											array(
												'wtrs_durbin_key' => $durbin_key,
												'wtrs_get_durbin' => 'get',
												'wpnonce' => $wtrs_db_nonce,
											)
										)
									);
					?>
									class="wtrs-notice-close"
			><?php esc_html_e( 'Accept & Close', 'wow-table-rate-shipping' ); ?></a>
			</div>
			<a href=
				<?php
							echo esc_url(
								add_query_arg(
									array(
										'wtrs_durbin_key' => $durbin_key,
										'wpnonce'         => $wtrs_db_nonce,
									)
								)
							);
				?>
				class="wtrs-notice-close"
				style="
					position: absolute;
					right: 2px;
					top: 5px;
					text-decoration: unset;
					color: #b6b6b6;
					font-family: dashicons;
					font-size: 16px;
					font-style: normal;
					font-weight: 400;
					line-height: 20px;
				"
			>
				<span 
				style="font-size: 14px;"
				class="wtrs-notice-close-icon dashicons dashicons-dismiss"> </span></a>
			</div>
		<?php
	}

	/**
	 * Plugin Install and Active Action
	 *
	 * @return void
	 */
	public function install_activate_plugin() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpnonce'] ?? '' ) ), 'wtrs-nonce' ) ) {
			wp_send_json_error( esc_html__( 'Invalid nonce.', 'wow-table-rate-shipping' ) );
		}

		if ( ! isset( $_POST['install_plugin'] ) || ! Flags::is_user_admin() ) {
			wp_send_json_error( esc_html__( 'Invalid request.', 'wow-table-rate-shipping' ) );
		}
		$plugin_slug = sanitize_text_field( wp_unslash( $_POST['install_plugin'] ) );

		Xpo::install_and_active_plugin( $plugin_slug );

		$action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) ); // phpcs:ignore

		if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) || 'activate-selected' === $action ) { //phpcs:ignore
			die();
		}

		wp_send_json_success( admin_url( 'admin.php?page=wtrs-dashboard#dashboard' ) );
	}

	/**
	 * Installation Notice CSS
	 *
	 * @return void
	 */
	public function install_notice_css() {
		?>
		<style type="text/css">
			.wtrs-wc-install {
				display: flex;
				align-items: center;
				background: #fff;
				margin-top: 30px !important;
				/*width: calc(100% - 65px);*/
				border: 1px solid #ccd0d4;
				padding: 4px !important;
				border-radius: 4px;
				border-left: 3px solid #46b450;
				line-height: 0;
				gap: 15px;
				padding: 15px 10px !important;
			}
			.wtrs-wc-install img {
				width: 100px;
			}
			.wtrs-install-body {
				-ms-flex: 1;
				flex: 1;
			}
			.wtrs-install-body.wtrs-image-banner {
				padding: 0px !important;
			}
			.wtrs-install-body.wtrs-image-banner img {
				width: 100%;
			}
			.wtrs-install-body>div {
				max-width: 450px;
				margin-bottom: 20px !important;
			}
			.wtrs-install-body h3 {
				margin: 0 !important;
				font-size: 20px;
				margin-bottom: 10px !important;
				line-height: 1;
			}
			.wtrs-pro-notice .wc-install-btn,
			.wp-core-ui .wtrs-wc-active-btn {
				display: inline-flex;
				align-items: center;
				padding: 3px 20px !important;
			}
			.wtrs-pro-notice.loading .wc-install-btn {
				opacity: 0.7;
				pointer-events: none;
			}
			.wtrs-wc-install.wc-install .dashicons {
				display: none;
				animation: dashicons-spin 1s infinite;
				animation-timing-function: linear;
			}
			.wtrs-wc-install.wc-install.loading .dashicons {
				display: inline-block;
				margin-right: 5px !important;
			}
			@keyframes dashicons-spin {
				0% {
					transform: rotate(0deg);
				}
				100% {
					transform: rotate(360deg);
				}
			}
			.wtrs-wc-install .wc-dismiss-notice {
				position: relative;
				text-decoration: none;
				float: right;
				right: 5px;
				display: flex;
				align-items: center;
			}
			.wtrs-wc-install .wc-dismiss-notice .dashicons {
				display: flex;
				text-decoration: none;
				animation: none;
				align-items: center;
			}
			.wtrs-pro-notice {
				position: relative;
				border-left: 3px solid #86a62c;
			}
			.wtrs-pro-notice .wtrs-install-body h3 {
				font-size: 20px;
				margin-bottom: 5px !important;
			}
			.wtrs-pro-notice .wtrs-install-body>div {
				max-width: 800px;
				margin-bottom: 0 !important;
			}
			.wtrs-pro-notice .button-hero {
				padding: 8px 14px !important;
				min-height: inherit !important;
				line-height: 1 !important;
				box-shadow: none;
				border: none;
				transition: 400ms;
				background: #46b450;
			}
			.wtrs-pro-notice .button-hero:hover,
			.wp-core-ui .wtrs-pro-notice .button-hero:active {
				background: #389e41;
			}
			.wtrs-pro-notice .wtrs-btn-notice-pro {
				background: #e5561e;
				color: #fff;
			}
			.wtrs-pro-notice .wtrs-btn-notice-pro:hover,
			.wtrs-pro-notice .wtrs-btn-notice-pro:focus {
				background: #ce4b18;
			}
			.wtrs-pro-notice .button-hero:hover,
			.wtrs-pro-notice .button-hero:focus {
				border: none;
				box-shadow: none;
			}
			.wtrs-pro-notice .wtrs-promotional-dismiss-notice {
				background-color: #000000;
				padding-top: 0px !important;
				position: absolute;
				right: 0;
				top: 0px;
				padding: 10px 10px 14px !important;
				border-radius: 0 0 0 4px;
				border: 1px solid;
				display: inline-block;
				color: #fff;
			}
			.wtrs-eid-notice p {
				margin: 0 !important;
				color: #f7f7f7;
				font-size: 16px;
			}
			.wtrs-eid-notice p.wtrs-eid-offer {
				color: #fff;
				font-weight: 700;
				font-size: 18px;
			}
			.wtrs-eid-notice p.wtrs-eid-offer a {
				background-color: #ffc160;
				padding: 8px 12px !important;
				border-radius: 4px;
				color: #000;
				font-size: 14px;
				margin-left: 3px !important;
				text-decoration: none;
				font-weight: 500;
				position: relative;
				top: -4px;
			}
			.wtrs-eid-notice p.wtrs-eid-offer a:hover {
				background-color: #edaa42;
			}
			.wtrs-install-body .wtrs-promotional-dismiss-notice {
				right: 4px;
				top: 3px;
				border-radius: unset !important;
				padding: 10px 8px 12px !important;
				text-decoration: none;
			}
			.wtrs-notice {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-left-color: #86a62c !important;
				border-left-width: 4px;
				border-radius: 4px 0px 0px 4px;
				box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
				padding: 0px !important;
				margin: 40px 20px 0 2px !important;
				clear: both;
			}
			.wtrs-notice .wtrs-notice-container {
				display: flex;
				width: 100%;
			}
			.wtrs-notice .wtrs-notice-container a {
				text-decoration: none;
			}
			.wtrs-notice .wtrs-notice-container a:visited {
				color: white;
			}
			.wtrs-notice .wtrs-notice-container img {
				width: 100%;
				max-width: 30px !important;
				padding: 12px !important;
			}
			.wtrs-notice .wtrs-notice-image {
				display: flex;
				align-items: center;
				flex-direction: column;
				justify-content: center;
				background-color: #f4f4ff;
			}
			.wtrs-notice .wtrs-notice-image img {
				max-width: 100%;
			}
			.wtrs-notice .wtrs-notice-content {
				width: 100%;
				margin: 5px !important;
				padding: 8px !important;
				display: flex;
				flex-direction: column;
				gap: 0px;
			}
			.wtrs-notice .wtrs-notice-wtrs-button {
				max-width: fit-content;
				text-decoration: none;
				padding: 7px 12px !important;
				font-size: 12px;
				color: white;
				border: none;
				border-radius: 2px;
				cursor: pointer;
				margin-top: 6px !important;
				background-color: #e5561e;
			}
			.wtrs-notice-heading {
				font-size: 18px;
				font-weight: 500;
				color: #1b2023;
			}
			.wtrs-notice-content-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			.wtrs-notice-close .dashicons-no-alt {
				font-size: 25px;
				height: 26px;
				width: 25px;
				cursor: pointer;
				color: #585858;
			}
			.wtrs-notice-close .dashicons-no-alt:hover {
				color: red;
			}
			.wtrs-notice-content-body {
				font-size: 12px;
				color: #343b40;
			}
			.wtrs-bold {
				font-weight: bold;
			}
			a.wtrs-pro-dismiss:focus {
				outline: none;
				box-shadow: unset;
			}
			.wtrs-free-notice .loading,
			.wtrs-notice .loading {
				width: 16px;
				height: 16px;
				border: 3px solid #FFF;
				border-bottom-color: transparent;
				border-radius: 50%;
				display: inline-block;
				box-sizing: border-box;
				animation: rotation 1s linear infinite;
				margin-left: 10px !important;
			}
			a.wtrs-notice-wtrs-button:hover {
				color: #fff !important;
			}
			.wtrs-notice .wtrs-link-wrap {
				margin-top: 10px !important;
			}
			.wtrs-notice .wtrs-link-wrap a {
				margin-right: 4px !important;
			}
			.wtrs-notice .wtrs-link-wrap a:hover {
				background-color: #ce4b18;
			}
			body .wtrs-notice .wtrs-link-wrap>a.wtrs-notice-skip {
				background: none !important;
				border: 1px solid #e5561e;
				color: #e5561e;
				padding: 6px 15px !important;
			}
			body .wtrs-notice .wtrs-link-wrap>a.wtrs-notice-skip:hover {
				background: #ce4b18 !important;
			}
			@keyframes rotation {
				0% {
					transform: rotate(0deg);
				}
				100% {
					transform: rotate(360deg);
				}
			}

			.wtrs-install-btn-wrap {
				display: flex;
				align-items: stretch;
				gap: 10px;
			}
			.wtrs-install-btn-wrap .wtrs-install-cancel {
				position: static !important;
				padding: 3px 20px;
				border: 1px solid #a0a0a0;
				border-radius: 2px;
			}
		</style>
		<?php
	}

	/**
	 * Installation Notice JS
	 *
	 * @return void
	 */
	public function install_notice_js() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				'use strict';
				$(document).on('click', '.wc-install-btn.wtrs-install-btn', function(e) {
					e.preventDefault();
					const $that = $(this);
					console.log($that.attr('data-plugin-slug'));
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {
							install_plugin: $that.attr('data-plugin-slug'),
							action: 'wtrs_install',
							wpnonce: '<?php echo esc_js( wp_create_nonce( 'wtrs-nonce' ) ); ?>',
						},
						beforeSend: function() {
							$that.parents('.wc-install').addClass('loading');
						},
						success: function(response) {
							window.location.reload()
						},
						complete: function() {
							// $that.parents('.wc-install').removeClass('loading');
						}
					});
				});
			});
		</script>
		<?php
	}
}
