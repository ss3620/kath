<?php //phpcs:ignore
namespace WOPB\Includes\Notice;

use WOPB\Includes\Durbin\DurbinClient;
use WOPB\Includes\Durbin\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Notice
 */
class Notice {

	/**
	 * Notice Priority
	 *
	 * @var string $plugin_notice_priority
	 */
	private $plugin_notice_priority = 5;

	/**
	 * Notice Priority
	 *
	 * @var string $plugin_notice_priority
	 */
	private $plugin_notice_priority_key = 'wow_store';

	/**
	 * Notice version
	 *
	 * @var string
	 */
	private $notice_version = 'v4133';

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
		add_action( 'wp_ajax_wopb_install', array( $this, 'install_activate_plugin' ) );
		add_filter( 'xpo_active_notice_lists', array( $this, 'handle_xpo_active_notice_lists' ), 99, 1 );
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
					return current_user_can( 'manage_options' ); // change with Flags::is_user_admin();
				},
			),
		);

		foreach ( $routes as $route ) {
			register_rest_route(
				'wopb',
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
			'wopb_helloBar_flash_sale_2026_3'    => Xpo::get_transient_without_cache( 'wopb_helloBar_flash_sale_2026_3' ),
			'wopb_helloBar_surprise_sale_2026_3' => Xpo::get_transient_without_cache( 'wopb_helloBar_surprise_sale_2026_3' ),
			'wopb_helloBar_massive_sale_2026_3'  => Xpo::get_transient_without_cache( 'wopb_helloBar_massive_sale_2026_3' ),
			'wopb_helloBar_final_hour_2026_3'    => Xpo::get_transient_without_cache( 'wopb_helloBar_final_hour_2026_3' ),
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
		$status         = 'failed';

		if ( 'hello_bar' === $type && ! empty( $id ) ) {
			$status = 'success';
			Xpo::set_transient_without_cache( $id, 'hide', 1296000 );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'status'  => $status,
				'message' => __( 'Hello Bar Action performed', 'product-blocks' ),
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

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['wopb_db_nonce'] ?? '' ) ), 'wopb-dashboard-nonce' ) ) {
			return;
		}

		$durbin_key = sanitize_text_field( wp_unslash( $_GET['wopb_durbin_key'] ?? '' ) );

		// Durbin notice dismiss.
		if ( ! empty( $durbin_key ) ) {
			Xpo::set_transient_without_cache( 'wopb_durbin_notice_' . $durbin_key, 'off' );

			if ( 'get' === sanitize_text_field( wp_unslash( $_GET['wopb_get_durbin'] ?? '' ) ) ) {
				DurbinClient::send( DurbinClient::ACTIVATE_ACTION );
			}
		}

		// Install notice dismiss.
		$install_key = sanitize_text_field( wp_unslash( $_GET['wopb_install_key'] ?? '' ) );
		if ( ! empty( $install_key ) ) {
			Xpo::set_transient_without_cache( 'wopb_install_notice_' . $install_key, 'off' );
		}

		$notice_key = sanitize_text_field( wp_unslash( $_GET['disable_wopb_notice'] ?? '' ) );
		if ( ! empty( $notice_key ) ) {
			$interval = (int) sanitize_text_field( wp_unslash( $_GET['wopb_interval'] ?? '' ) );
			$interval = ! empty( $interval ) ? $interval : 0;
			Xpo::set_transient_without_cache( 'wopb_get_pro_notice_' . $notice_key, 'off', $interval );
		}
	}

	/**
	 * Admin Notices Callback
	 *
	 * @return void
	 */
	public function admin_notices_callback() {
		$this->wopb_dashboard_notice_callback();
		$this->wopb_dashboard_durbin_notice_callback();
		$this->our_plugin_install_notice_callback(); // different from common utils.
	}

	/**
	 * Admin Dashboard Notice Callback
	 *
	 * @return void
	 */
	public function wopb_dashboard_notice_callback() {
		if ( $this->is_available_for_notice() ) {
			$this->wopb_dashboard_banner_notice();
			$this->wopb_dashboard_content_notice();
		}
	}

	/**
	 * Dashboard Banner Notice
	 * $return_bool
	 *
	 * @return bool
	 */
	public function wopb_dashboard_banner_notice( $return_bool = false ) {
		$wopb_db_nonce  = wp_create_nonce( 'wopb-dashboard-nonce' );
		$banner_notices = array(
			array(
				'key'                => 'wopb_spring_sale_2026_1',
				'start'              => '2026-04-05 00:00 Asia/Dhaka', // format YY-MM-DD always set time 00:00 and zone Asia/Dhaka.
				'end'                => '2026-04-14 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#DD106C',

				'left_image'         => WOPB_URL . '/assets/img/banners/spring_sale/left_image.png',
				'right_image'        => WOPB_URL . '/assets/img/banners/spring_sale/right_image.png',
				'bg_image'           => WOPB_URL . '/assets/img/banners/spring_sale/bg.png',
				'text'               => 'Hurry Before It Ends!',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#000',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'spring_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
			),

			// Flash sale Notice
			array(
				'key'                => 'wopb_flash_sale_2026_1',
				'start'              => '2026-05-07 00:00 Asia/Dhaka', // format YY-MM-DD always set time 00:00 and zone Asia/Dhaka.
				'end'                => '2026-05-12 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#DD106C',

				'left_image'         => WOPB_URL . '/assets/img/dashboard_banner/flash_sale/offer.png',
				'right_image'        => WOPB_URL . '/assets/img/dashboard_banner/flash_sale/btn.png',
				'bg_image'           => WOPB_URL . '/assets/img/dashboard_banner/flash_sale/bg.png',
				'text'               => 'Deal ending soon',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
			),

			// Surprise sale Notice
			array(
				'key'                => 'wopb_surprise_sale_2026_1',
				'start'              => '2026-05-22 00:00 Asia/Dhaka', // format YY-MM-DD always set time 00:00 and zone Asia/Dhaka.
				'end'                => '2026-05-25 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#DD106C',

				'left_image'         => WOPB_URL . '/assets/img/dashboard_banner/surprise_sale/offer.png',
				'right_image'        => WOPB_URL . '/assets/img/dashboard_banner/surprise_sale/btn.png',
				'bg_image'           => WOPB_URL . '/assets/img/dashboard_banner/surprise_sale/bg.png',
				'text'               => 'Limited Time Offer',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'surprise_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
			),
			// Massive sale Notice
			array(
				'key'                => 'wopb_massive_sale_2026_1',
				'start'              => '2026-06-02 00:00 Asia/Dhaka', // format YY-MM-DD always set time 00:00 and zone Asia/Dhaka.
				'end'                => '2026-06-10 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#DD106C',

				'left_image'         => WOPB_URL . '/assets/img/dashboard_banner/massive_sale/offer.png',
				'right_image'        => WOPB_URL . '/assets/img/dashboard_banner/massive_sale/btn.png',
				'bg_image'           => WOPB_URL . '/assets/img/dashboard_banner/massive_sale/bg.png',
				'text'               => 'Deal ending soon',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'massive_sale',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
			),
			// Final hours sale Notice
			array(
				'key'                => 'wopb_final_hours_sale_2026_1',
				'start'              => '2026-06-21 00:00 Asia/Dhaka', // format YY-MM-DD always set time 00:00 and zone Asia/Dhaka.
				'end'                => '2026-06-24 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#DD106C',

				'left_image'         => WOPB_URL . '/assets/img/dashboard_banner/final_hour_sale/offer.png',
				'right_image'        => WOPB_URL . '/assets/img/dashboard_banner/final_hour_sale/btn.png',
				'bg_image'           => WOPB_URL . '/assets/img/dashboard_banner/final_hour_sale/bg.png',
				'text'               => 'Hurry Before It Ends!',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final_hour',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
			),

		);

		foreach ( $banner_notices as $notice ) {
			$notice_key = isset( $notice['key'] ) ? $notice['key'] : $this->notice_version;
			if ( isset( $_GET['disable_wopb_notice'] ) && $notice_key === sanitize_key( $_GET['disable_wopb_notice'] ) ) { // phpcs:ignore
				continue;
			}

			$current_time = gmdate( 'U' );
			$notice_start = gmdate( 'U', strtotime( $notice['start'] ) );
			$notice_end   = gmdate( 'U', strtotime( $notice['end'] ) );
			if ( $current_time >= $notice_start && $current_time <= $notice_end && $notice['visibility'] ) {

				$notice_transient = Xpo::get_transient_without_cache( 'wopb_get_pro_notice_' . $notice_key );

				if ( 'off' === $notice_transient ) {
					continue;
				}

				if ( $return_bool ) { // Early return for Other plugin notice.
					return true;
				}

				if ( ! $this->notice_js_css_applied ) {
					$this->notice_js_css_applied = true;
					$this->wopb_banner_notice_js();
				}
				$query_args = array(
					'disable_wopb_notice' => $notice_key,
					'wopb_db_nonce'       => $wopb_db_nonce,
				);
				if ( isset( $notice['repeat_interval'] ) && $notice['repeat_interval'] ) {
					$query_args['wopb_interval'] = $notice['repeat_interval'];
				}
				?>
				<style type="text/css">
					.wopb-notice-wrapper.wopb-banner-notice {
						height: auto !important;
						padding: 0 !important;
						position: relative;
						box-sizing: border-box;
						background-repeat: no-repeat;
						background-size: cover;
						background-position: center;
					}
					.wopb-notice-wrapper.wopb-banner-notice .wopb-banner-link {
						width: 100%;
						text-decoration: none;
						display: block;
					}
					.wopb-notice-wrapper.wopb-banner-notice .wopb-banner-content {
						display: flex;
						justify-content: space-between;
						align-items: center;
						max-width: 700px;
						margin: 0 auto;
						padding: 10px 16px;
						gap: 16px;
					}
					.wopb-notice-wrapper.wopb-banner-notice .wopb-banner-side-image {
						display: block;
						max-width: 100%;
						height: auto;
						max-height: 32px;
					}
					.wopb-notice-wrapper.wopb-banner-notice .wopb-banner-main {
						display: flex;
						flex-direction: column;
						gap: 4px;
						align-items: center;
						justify-content: center;
						font-weight: 700;
						font-size: 18px;
						color: #DD106C;
						line-height: 1.2;
						text-align: center;
					}

					@media screen and (max-width: 1100px) {
						/* .wopb-notice-wrapper.wopb-banner-notice .wopb-banner-content {
							flex-direction: column;
						} */
						.wopb-notice-wrapper.wopb-banner-notice .wopb-banner-content .wopb-banner-main-text {
							display: none;
						}
					}

					@media screen and (max-width: 782px) {
						.wopb-notice-wrapper.wopb-banner-notice .wopb-banner-content {
							justify-content: center;
							padding: 12px 32px 12px 12px;
						}
						.wopb-notice-wrapper.wopb-banner-notice .wopb-banner-main {
							font-size: 22px;
							line-height: 28px;
						}
					}
					@media screen and (max-width: 480px) {
						.wopb-notice-wrapper.wopb-banner-notice .wopb-banner-content {
							padding: 10px 32px 10px 10px;
						}
						.wopb-notice-wrapper.wopb-banner-notice .wopb-banner-main {
							font-size: 18px;
							line-height: 24px;
						}
						.wopb-notice-wrapper.wopb-banner-notice {
							display: none;
						}
					}
				</style>
				<div 
					class="wopb-notice-wrapper wopb-banner-notice notice" 
					style="
						border-left: 3px solid <?php echo esc_attr( $notice['brand_color'] ); ?>;
						background-image: url('<?php echo esc_attr( $notice['bg_image'] ); ?>');
					"
				>
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
						aria-label="<?php esc_html_e( 'Close Banner', 'product-blocks' ); ?>"
						href="<?php echo esc_url( add_query_arg( $query_args ) ); ?>">
					</a>

					<a class="wopb-banner-link" target="_blank" href="<?php echo esc_url( $notice['url'] ); ?>">
						<div class="wopb-banner-content">
							<img class="wopb-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice['left_image'] ); ?>" />
							<div class="wopb-banner-main">
								<span style="color: #fff" class="wopb-banner-main-text">
									<?php echo esc_html( $notice['text'] ); ?>
								</span>	
								<div 
									class="wopb-notice-countdown" 
									style="color: <?php echo esc_attr( $notice['countdown_color'] ); ?>;"
									data-notice-key="<?php echo esc_attr( $notice_key . '-countdown' ); ?>" 
									data-duration="<?php echo esc_attr( $notice['countdown_duration'] ); ?>">
									00:00:00:00
								</div>
							</div>
							<img class="wopb-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice['right_image'] ); ?>" />
						</div>
					</a>
				</div>
				<?php
			}
		}
	}

	/**
	 * Banner JS
	 *
	 * @return void
	 */
	public function wopb_banner_notice_js() {
		?>
		<script type="text/javascript">
			jQuery(function($) {
				'use strict';

				const storagePrefix = 'wopb_notice_countdown_';

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

				$('.wopb-notice-countdown').each(function() {
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
	 * Dashboard Content Notice
	 *
	 * @return void
	 */
	public function wopb_dashboard_content_notice( $return_bool = false ) {

		$content_notices = array(
			array(
				'key'                => 'wopb_dashboard_content_notice_spring_sale_v1',
				'start'              => '2026-03-16 00:00 Asia/Dhaka',
				'end'                => '2026-03-25 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'content_notice',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Spring Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 60% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/logo.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wopb_dashboard_content_notice_spring_sale_v2',
				'start'              => '2026-03-26 00:00 Asia/Dhaka',
				'end'                => '2026-04-04 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'content_notice',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Spring Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 60% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/discount.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),

			// FFlash sale
			array(
				'key'                => 'wopb_dashboard_content_notice_flash_sale_v1',
				'start'              => '2026-05-13 00:00 Asia/Dhaka',
				'end'                => '2026-05-17 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash_sale_content',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Flash Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/logo.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wopb_dashboard_content_notice_flash_sale_discount_v2',
				'start'              => '2026-05-18 00:00 Asia/Dhaka',
				'end'                => '2026-05-21 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash_sale_content',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Flash Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/55_off.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),

			// Surprise sale
			array(
				'key'                => 'wopb_dashboard_content_notice_surprise_sale_v1',
				'start'              => '2026-05-26 00:00 Asia/Dhaka',
				'end'                => '2026-05-28 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'surprise_sale_content',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Surprise Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 60% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/logo.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wopb_dashboard_content_notice_surprise_sale_discount_v1',
				'start'              => '2026-05-29 00:00 Asia/Dhaka',
				'end'                => '2026-06-01 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'surprise_sale_content',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Surprise Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 60% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/discount.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),
			// Massive sale
			array(
				'key'                => 'wopb_dashboard_content_notice_massive_sale_v1',
				'start'              => '2026-06-11 00:00 Asia/Dhaka',
				'end'                => '2026-06-16 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'massive_sale_content',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Massive Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/logo.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wopb_dashboard_content_notice_massive_sale_discount_v1',
				'start'              => '2026-06-17 00:00 Asia/Dhaka',
				'end'                => '2026-06-20 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'massive_sale_content',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Massive Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 55% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/55_off.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),
			// Final hours sale
			array(
				'key'                => 'wopb_dashboard_content_notice_final_hours_sale_v1',
				'start'              => '2026-06-25 00:00 Asia/Dhaka',
				'end'                => '2026-06-27 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final_hour_content',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Final Hours Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 60% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/logo.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wopb_dashboard_content_notice_final_hours_sale_discount_v1',
				'start'              => '2026-06-28 00:00 Asia/Dhaka',
				'end'                => '2026-06-30 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final_hour_content',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Final Hours Sale:', 'product-blocks' ),
				'content_subheading' => __( 'WowStore offers are live - Enjoy %s off on WowStore.', 'product-blocks' ),
				'discount_content'   => ' up to 60% OFF',
				'border_color'       => '#DD106C',
				'icon'               => WOPB_URL . 'assets/img/dashboard_banner/discount.svg',
				'button_text'        => __( 'Upgrade Now', 'product-blocks' ),
				'is_discount_logo'   => true,
			),
		);

		$wopb_db_nonce = wp_create_nonce( 'wopb-dashboard-nonce' );

		foreach ( $content_notices as $key => $notice ) {
			$notice_key = isset( $notice['key'] ) ? $notice['key'] : $this->notice_version;
			if ( isset( $_GET['disable_wopb_notice'] ) && $notice_key === $_GET['disable_wopb_notice'] ) {
				continue;
			} else {
				$border_color = $notice['border_color'];

				$current_time = gmdate( 'U' );
				$notice_start = gmdate( 'U', strtotime( $notice['start'] ) );
				$notice_end   = gmdate( 'U', strtotime( $notice['end'] ) );
				if ( $current_time >= $notice_start && $current_time <= $notice_end && $notice['visibility'] ) {
					$notice_transient = Xpo::get_transient_without_cache( 'wopb_get_pro_notice_' . $notice_key );

					if ( 'off' !== $notice_transient ) {

						if ( $return_bool ) { // Early return for Other plugin notice.
							return true;
						}

						$query_args = array(
							'disable_wopb_notice' => $notice_key,
							'wopb_db_nonce'       => $wopb_db_nonce,
						);
						if ( isset( $notice['repeat_interval'] ) && $notice['repeat_interval'] ) {
							$query_args['wopb_interval'] = $notice['repeat_interval'];
						}

						$url = isset( $notice['url'] ) ? $notice['url'] : Xpo::generate_utm_link(
							array(
								'utmKey' => 'content_notice',
							)
						);

						?>

						<style id="wopb-notice-css" type="text/css">
							.wopb-content-notice-wrapper {
								border: 1px solid #c3c4c7;
								border-left: 3px solid #DD106C;
								margin: 15px 0 !important;
								display: flex;
								align-items: center;
								background: #f3edf1;
								width: 100%;
								padding: 10px 0;
								position: relative;
								box-sizing: border-box;
							}

							.wopb-content-notice-wrapper.notice {
								margin: 10px 0;
								width: calc(100% - 20px);
							}

							.wrap .wopb-content-notice-wrapper.notice {
								width: 100%;
							}

							.wopb-content-notice-icon {
								margin-left: 15px;
							}

							.wopb-content-notice-discout-icon {
								margin-left: 10px;
							}

							.wopb-content-notice-icon img {
								max-width: 42px;
								height: 70px;
							}

							.wopb-content-notice-discout-icon img {
								height: 70px;
								width: 70px;
							}

							.wopb-notice-content-wrapper {
								display: flex;
								flex-direction: column;
								gap: 8px;
								font-size: 14px;
								line-height: 20px;
								margin-left: 15px;
							}

							.wopb-content-notice-buttons {
								display: flex;
								align-items: center;
								gap: 15px;
							}

							.wopb-content-notice-btn {
								font-weight: 600;
								text-transform: uppercase !important;
								padding: 2px 10px !important;
								background-color: #DD106C;
								border: none !important;
							}

							.wopb-content-discount_btn {
								background-color: #ffffff;
								text-decoration: none;
								border: 1px solid #DD106C;
								padding: 5px 10px;
								border-radius: 5px;
								font-weight: 500;
								text-transform: uppercase;
								color: #DD106C !important;
							}

							.wopb-content-notice-close {
								position: absolute;
								right: 2px;
								top: 5px;
								text-decoration: none;
								color: #b6b6b6;
								font-family: dashicons;
								font-size: 16px;
								line-height: 20px;
							}

							.wopb-content-notice-close-icon {
								font-size: 14px;
							}
						</style>
					<div class="wopb-content-notice-wrapper notice data_collection_notice" 
					style="border-left: 3px solid <?php echo esc_attr( $border_color ); ?>;"
					> 
						<?php
						if ( $notice['is_discount_logo'] ) {
							?>
								<div class="wopb-content-notice-discout-icon"> <img src="<?php echo esc_url( $notice['icon'] ); ?>"/>  </div>
							<?php
						} else {
							?>
								<div class="wopb-content-notice-icon"> <img src="<?php echo esc_url( $notice['icon'] ); ?>"/>  </div>
							<?php
						}
						?>
						
						<div class="wopb-notice-content-wrapper">
							<div class="">
								<strong><?php printf( esc_html( $notice['content_heading'] ) ); ?> </strong>
						<?php
						printf(
							wp_kses_post( $notice['content_subheading'] ),
							'<strong>' . esc_html( $notice['discount_content'] ) . '</strong>'
						);
						?>
							</div>
							<div class="wopb-content-notice-buttons">
							<?php if ( isset( $notice['is_discount_logo'] ) && $notice['is_discount_logo'] ) : ?>
									<a class="wopb-content-discount_btn" href="<?php echo esc_url( $url ); ?>" target="_blank">
										<?php echo esc_html( $notice['button_text'] ); ?>
									</a>
								<?php else : ?>
									<a class="wopb-content-notice-btn button button-primary" href="<?php echo esc_url( $url ); ?>" target="_blank" style="background-color: <?php echo ! empty( $notice['background_color'] ) ? esc_attr( $notice['background_color'] ) : '#DD106C'; ?>;">
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
						class="wopb-content-notice-close"><span class="wopb-content-notice-close-icon dashicons dashicons-dismiss"> </span></a>
					</div>
								<?php
					}
				}
			}
		}
	}

	/**
	 * Dashboard Content Notice
	 *
	 * @return void
	 */
	public function our_plugin_install_notice_callback() {
		global $pagenow;
		$notice_content = array(
			array(
				'type'       => 'wow_revenue',
				'url'        => Xpo::generate_utm_link(
					array(
						'utmKey' => 'summer_db',
					)
				),
				'visibility' => ! Xpo::is_lc_active() && ( 'plugins.php' === $pagenow || 'index.php' === $pagenow ),
			),
		);

		foreach ( $notice_content as $key => $notice ) {
				// phpcs:ignore
				if ( ( isset( $_GET['wopb_install_key'] ) && sanitize_key( $_GET['wopb_install_key'] ) === $notice['type'] ) ||
					'off' === Xpo::get_transient_without_cache( 'wopb_install_notice_' . $notice['type'], ) || isset( $notice['visibility'] ) && ! ( $notice['visibility'] )
				) {
				return;
			}

				$this->install_notice_css();
				$this->install_notice_js();

			switch ( $notice['type'] ) {
				case 'wow_revenue':
					$revenue_installed = file_exists( WP_PLUGIN_DIR . '/revenue/revenue.php' );
					$campaign_url      = admin_url( 'admin.php?page=revenue#/campaigns' );
					$is_revenue_active = is_plugin_active( 'revenue/revenue.php' );
					$cmp_count         = 0;
					if ( $is_revenue_active ) {
						$cmp_count = $this->get_revenue_campaign_count(); // $cmp_count

						if ( $cmp_count && is_object( $cmp_count ) ) {
							$cmp_count = $cmp_count->total_campaigns;
						}
					}

					if ( $cmp_count ) {
						return;
					}

					ob_start();
					?>
							<div class="wopb-pro-notice wopb-wc-install wc-install wopb-wowrev-notice">
								<div class="wopb-wowrev-notice__wrapper">
									<div class="wopb-wowrev-notice__title">
										🚀 Offer Discounts, Boost Sales, and Increase Revenue
									</div>
									<div class="wopb-wowrev-notice__desc">Looking to maximize profits? WowRevenue can help send your store’s sales through the roof - It's a discount builder that ignites growth with flexible upselling, cross-selling, and downselling campaigns.</div>
									<div class="wopb-wowrev-notice__tag">
										<div>Bundle Discount</div>
										<span></span>
										<div>Quantity Discount</div>
										<span></span>
										<div>Frequently Bought Together</div>
										<span></span>
										<div>Buy X Get Y</div>
									</div>
								<?php
								if ( is_plugin_active( 'revenue/revenue.php' ) ) {
									?>
										<a  href="<?php echo esc_url( $campaign_url ); ?>" class="wopb-wowrev-notice__button">Create Discount WowRevenue<span></span></a>
										<?php
								} elseif ( $revenue_installed && ! is_plugin_active( 'revenue/revenue.php' ) ) {
									?>
										<a href="#" data-plugin-slug="<?php echo esc_attr( 'wow_revenue' ); ?>" class="wopb-wowrev-btn wopb-wowrev-notice__button wopb-revx-active wopb-revx-activate wc-install-btn wopb-install-btn" data-link="<?php echo esc_url( $campaign_url ); ?>" data-api-url="<?php echo esc_url( rest_url( '/wopb/v2/install-extra-plugin' ) ); ?>"><span class="dashicons dashicons-image-rotate"></span>Active WowRevenue <span></span></a>
										<?php
								} elseif ( ! $revenue_installed ) {
									?>
										<a href="#" data-plugin-slug="<?php echo esc_attr( 'wow_revenue' ); ?>" class="wopb-wowrev-btn wopb-wowrev-notice__button wopb-revx-install wc-install-btn wopb-install-btn" data-link="<?php echo esc_url( $campaign_url ); ?>" data-api-url="<?php echo esc_url( rest_url( '/wopb/v2/install-extra-plugin' ) ); ?>"><span class="dashicons dashicons-image-rotate"></span>Free Install WowRevenue<span></span></a>
										<?php
								}
								?>
									<div class="wopb-notice-close wopb-wowrev-notice__notice-close">
										<a href="
										<?php
										echo esc_url(
											add_query_arg(
												array(
													'wopb_install_key' => $notice['type'],
													'rv_banner_nonce'  => wp_create_nonce( 'ultp-revenue-install-nonce' ),
												)
											)
										);
										?>
													" ><span class="dashicons dashicons-no-alt"></span></a>
									</div>
								</div>
							</div>
							<?php
							echo ob_get_clean(); // phpcs:ignore
					break;
				default:
					// code...
					break;
			}
				return '';
		}
	}

	/**
	 * The Durbin Html
	 *
	 * @return STRING | HTML
	 */
	public function wopb_dashboard_durbin_notice_callback() {
		$durbin_key = 'wopb_durbin_dc1';
		// phpcs:ignore
		if (
			isset( $_GET['wopb_durbin_key'] ) || // phpcs:ignore
			'off' === Xpo::get_transient_without_cache( 'wopb_durbin_notice_' . $durbin_key ) ||
			defined( 'WOPB_PRO_VER' )
		) {
			return;
		}

		if ( ! $this->notice_js_css_applied ) {

			$this->notice_js_css_applied = true;
		}

		$wopb_db_nonce = wp_create_nonce( 'wopb-nonce' );

		?>
		<style>
				.wopb-consent-box {
					width: 656px;
					padding: 16px !important;
					border: 1px solid #070707;
					border-left-width: 4px;
					border-radius: 4px;
					background-color: #fff;
					position: relative;
					width: 100%;
					box-sizing: border-box;
				}
				.wopb-consent-content {
					display: flex;
					justify-content: flex-start;
					align-items: flex-end;
					gap: 26px;
				}
 
				.wopb-consent-text-first {
					font-size: 14px;
					font-weight: 600;
					color: #070707;
				}
				.wopb-consent-text-last {
					margin: 4px 0 0;
					font-size: 14px;
					color: #070707;
				}
 
				.wopb-consent-accept {
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
				.wopb-consent-accept:hover {
					background-color:rgb(38, 38, 38);
					color: #fff;
				}
			</style>
			<div class="wopb-consent-box wopb-notice-wrapper notice data_collection_notice">
				<div class="wopb-consent-content">
					<div class="wopb-consent-text">
						<div class="wopb-consent-text-first"><?php esc_html_e( 'Want to help make WowStore even more awesome?', 'product-blocks' ); ?></div>
						<div class="wopb-consent-text-last">
							<?php esc_html_e( 'Allow us to collect diagnostic data and usage information. see ', 'product-blocks' ); ?>
							<a href="https://www.wpxpo.com/data-collection-policy/" target="_blank" ><?php esc_html_e( 'what we collect.', 'product-blocks' ); ?></a>
						</div>
					</div>
					<a
						class="wopb-consent-accept"
						href=
						<?php
							echo esc_url(
								add_query_arg(
									array(
										'wopb_durbin_key' => $durbin_key,
										'wopb_get_durbin' => 'get',
										'wpnonce'         => $wopb_db_nonce,
									)
								)
							);
						?>
									class="wopb-notice-close"
					>
						<?php esc_html_e( 'Accept & Close', 'product-blocks' ); ?>
					</a>
				</div>
				<a 
					href=
					<?php
						echo esc_url(
							add_query_arg(
								array(
									'wopb_durbin_key' => $durbin_key,
								)
							)
						);
					?>
					class="wopb-notice-close"
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
						class="wopb-notice-close-icon dashicons dashicons-dismiss"
						style="font-size: 14px;"
					> </span>
				</a>
			</div>
		<?php
	}


	/**
	 * Plugin Install and Active Action
	 *
	 * @since v.1.6.8
	 * @return STRING | Redirect URL
	 */
	public function install_activate_plugin() {
		if ( ! isset( $_POST['install_plugin'] ) ||
			! current_user_can( 'manage_options' ) ||
			! isset( $_POST['wopb_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wopb_nonce'] ) ), 'wopb-nonce' )
		) {
			return wp_send_json_error( esc_html__( 'Invalid request.', 'product-blocks' ) );
		}
		$plugin_slug = sanitize_text_field( wp_unslash( $_POST['install_plugin'] ) ); // phpcs:ignore

		Xpo::install_and_active_plugin( $plugin_slug );

		if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) || isset( $_POST['action'] ) && 'activate-selected' == sanitize_text_field( $_POST['action'] ) ) { //phpcs:ignore
			return;
		}

		return wp_send_json_success( admin_url( 'admin.php?page=wopb-dashboard#dashboard' ) );
	}

	/**
	 * Installation Notice CSS
	 *
	 * @since v.1.0.0
	 */
	public function install_notice_css() {
		?>
		<style type="text/css">
			.wopb-wc-install {
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
			.wopb-wc-install img {
				width: 100px;
			}
			.wopb-install-body {
				-ms-flex: 1;
				flex: 1;
			}
			.wopb-install-body.wopb-image-banner {
				padding: 0px !important;
			}
			.wopb-install-body.wopb-image-banner img {
				width: 100%;
			}
			.wopb-install-body>div {
				max-width: 450px;
				margin-bottom: 20px !important;
			}
			.wopb-install-body h3 {
				margin: 0 !important;
				font-size: 20px;
				margin-bottom: 10px !important;
				line-height: 1;
			}
			.wopb-pro-notice .wc-install-btn,
			.wp-core-ui .wopb-wc-active-btn {
				display: inline-flex;
				align-items: center;
				padding: 3px 20px !important;
			}
			.wopb-pro-notice.loading .wc-install-btn {
				opacity: 0.7;
				pointer-events: none;
			}
			.wopb-wc-install.wc-install .dashicons-image-rotate.dashicons {
				display: none;
				animation: dashicons-spin 1s infinite;
				animation-timing-function: linear;
			}
			.wopb-wc-install.wc-install.loading .dashicons {
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
			.wopb-wc-install .wc-dismiss-notice {
				position: relative;
				text-decoration: none;
				float: right;
				right: 5px;
				display: flex;
				align-items: center;
			}
			.wopb-wc-install .wc-dismiss-notice .dashicons {
				display: flex;
				text-decoration: none;
				animation: none;
				align-items: center;
			}
			.wopb-pro-notice {
				position: relative;
				border-left: 3px solid #037fff;
				width: calc(100% - 20px);
				box-sizing: border-box;
			}
			.wopb-pro-notice .wopb-install-body h3 {
				font-size: 20px;
				margin-bottom: 5px !important;
			}
			.wopb-pro-notice .wopb-install-body>div {
				max-width: 800px;
				margin-bottom: 0 !important;
			}
			.wopb-pro-notice .button-hero {
				padding: 8px 14px !important;
				min-height: inherit !important;
				line-height: 1 !important;
				box-shadow: none;
				border: none;
				transition: 400ms;
				background: #46b450;
			}
			.wopb-pro-notice .button-hero:hover,
			.wp-core-ui .wopb-pro-notice .button-hero:active {
				background: #389e41;
			}
			.wopb-pro-notice .wopb-btn-notice-pro {
				background: #e5561e;
				color: #fff;
			}
			.wopb-pro-notice .wopb-btn-notice-pro:hover,
			.wopb-pro-notice .wopb-btn-notice-pro:focus {
				background: #ce4b18;
			}
			.wopb-pro-notice .button-hero:hover,
			.wopb-pro-notice .button-hero:focus {
				border: none;
				box-shadow: none;
			}
			.wopb-pro-notice .wopb-promotional-dismiss-notice {
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
			.wopb-eid-notice p {
				margin: 0 !important;
				color: #f7f7f7;
				font-size: 16px;
			}
			.wopb-eid-notice p.wopb-eid-offer {
				color: #fff;
				font-weight: 700;
				font-size: 18px;
			}
			.wopb-eid-notice p.wopb-eid-offer a {
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
			.wopb-eid-notice p.wopb-eid-offer a:hover {
				background-color: #edaa42;
			}
			.wopb-install-body .wopb-promotional-dismiss-notice {
				right: 4px;
				top: 3px;
				border-radius: unset !important;
				padding: 10px 8px 12px !important;
				text-decoration: none;
			}
			.wopb-notice {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-left-color: #037fff !important;
				border-left-width: 4px;
				border-radius: 4px 0px 0px 4px;
				box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
				padding: 0px !important;
				margin: 40px 20px 0 2px !important;
				clear: both;
			}
			.wopb-notice .wopb-notice-container {
				display: flex;
				width: 100%;
			}
			.wopb-notice .wopb-notice-container a {
				text-decoration: none;
			}
			.wopb-notice .wopb-notice-container a:visited {
				color: white;
			}
			.wopb-notice .wopb-notice-container img {
				width: 100%;
				max-width: 30px !important;
				padding: 12px !important;
			}
			.wopb-notice .wopb-notice-image {
				display: flex;
				align-items: center;
				flex-direction: column;
				justify-content: center;
				background-color: #f4f4ff;
			}
			.wopb-notice .wopb-notice-image img {
				max-width: 100%;
			}
			.wopb-notice .wopb-notice-content {
				width: 100%;
				margin: 5px !important;
				padding: 8px !important;
				display: flex;
				flex-direction: column;
				gap: 0px;
			}
			.wopb-notice .wopb-notice-wopb-button {
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
			.wopb-notice-heading {
				font-size: 18px;
				font-weight: 500;
				color: #1b2023;
			}
			.wopb-notice-content-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			.wopb-notice-close .dashicons-no-alt {
				font-size: 25px;
				height: 26px;
				width: 25px;
				cursor: pointer;
				color: #585858;
			}
			.wopb-notice-close .dashicons-no-alt:hover {
				color: red;
			}
			.wopb-notice-content-body {
				font-size: 12px;
				color: #343b40;
			}
			.wopb-bold {
				font-weight: bold;
			}
			a.wopb-pro-dismiss:focus {
				outline: none;
				box-shadow: unset;
			}
			.wopb-free-notice .loading,
			.wopb-notice .loading {
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
			a.wopb-notice-wopb-button:hover {
				color: #fff !important;
			}
			.wopb-notice .wopb-link-wrap {
				margin-top: 10px !important;
			}
			.wopb-notice .wopb-link-wrap a {
				margin-right: 4px !important;
			}
			.wopb-notice .wopb-link-wrap a:hover {
				background-color: #ce4b18;
			}
			body .wopb-notice .wopb-link-wrap>a.wopb-notice-skip {
				background: none !important;
				border: 1px solid #e5561e;
				color: #e5561e;
				padding: 6px 15px !important;
			}
			body .wopb-notice .wopb-link-wrap>a.wopb-notice-skip:hover {
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

			.wopb-install-btn-wrap {
				display: flex;
				align-items: stretch;
				gap: 10px;
			}
			.wopb-install-btn-wrap .wopb-install-cancel {
				position: static !important;
				padding: 3px 20px;
				border: 1px solid #a0a0a0;
				border-radius: 2px;
			}

			.wopb-wowrev-notice {
				margin: 20px 20px 0 2px;
				padding: 0px !important;
				border: 0px;
				display: block;
			}

			.wopb-wowrev-notice__wrapper {
				background-color: #fff;
				padding: 30px 40px;
				box-sizing: border-box;
				box-shadow: 0px 0px 16px 32px #585C5F1A;
				background-image: url("<?php echo esc_url( WOPB_URL . 'assets/img/wow_rev_activation_updated.jpg' ); ?>");
				background-position: 50% 50%;
				background-repeat: no-repeat;
				border-radius: 8px;
				position: relative;
				border: 0px;
				background-position: 100% 100%;
				background-size: cover;
			}

			.wopb-wowrev-notice__title {
				font-size: 24px;
				font-weight: 600;
				line-height: 32px;
				color: #0A0D14;
				margin-bottom: 8px;
			}

			.wopb-wowrev-notice__desc {
				color: #525866;
				max-width: 664px;
				margin-bottom: 16px;
			}

			.wopb-wowrev-notice__tag,
			.wopb-wowrev-notice__tag div {
				display: flex;
				align-items: center;
				gap: 8px;
			}

			.wopb-wowrev-notice__tag div {
				color: #6E3FF3;
				font-weight: 400;
				text-decoration: none;
			}

			.wopb-wowrev-notice__tag span {
				width: 6px;
				height: 6px;
				display: block;
				border-radius: 10px;
				background-color: #6E3FF3;
				box-sizing: border-box;
			}

			.wopb-wowrev-notice__desc,
			.wopb-wowrev-notice__button,
			.wopb-wowrev-notice__tag div {
				font-size: 14px;
				line-height: 20px;
				text-decoration: none;
			}

			.wopb-wowrev-notice .wopb-wowrev-notice__button {
				color: #fff;
				border-radius: 8px;
				padding: 10px 20px !important;
				box-sizing: border-box;
				display: block;
				width: fit-content;
				margin-top: 24px;
				background-color: #00A464;
			}
			.wopb-wowrev-notice__button:focus,
			.wopb-wowrev-notice__button:active, 
			.wopb-wowrev-notice__button:hover {
				color: #fff;
			}

			.wopb-wowrev-notice__campaign-img {
				position: absolute;
				top: 0px;
				right: 0px;
				border-top-right-radius: 8px;
				border-bottom-right-radius: 8px;
			}

			.wopb-notice-close .dashicons-no-alt {
				font-size: 25px;
				height: 26px;
				width: 25px;
				cursor: pointer;
				color: #fff;
			}

			.wopb-wowrev-notice__notice-close {
				position: absolute;
				top: 12px;
				right: 12px;
			}

			.wopb-wowrev-notice__notice-close a {
				display: block;
				width: fit-content;
				text-decoration: none;
			}
		</style>
		<?php
	}

	/**
	 * Installation Notice JS
	 *
	 * @since v.1.0.0
	 */
	public function install_notice_js() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				'use strict';
				$(document).on('click', '.wc-install-btn.wopb-install-btn', function(e) {
					e.preventDefault();
					const $that = $(this);
					console.log($that.attr('data-plugin-slug'), "data-plugin-slug");
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {
							install_plugin: $that.attr('data-plugin-slug'),
							action: 'wopb_install',
							wopb_nonce: wopb_option.security,
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

	/**
	 * Wow Revenue Campaign
	 *
	 * @return void
	 * @since 2.6.1
	 */
	public function get_revenue_campaign_count() {

		global $wpdb;
		// phpcs:ignore
		$res = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 1 AS total_campaigns FROM {$wpdb->prefix}revenue_campaigns LIMIT %d",
				1
			)
		);

		return $res;
	}

	/**
	 * Handle Plugin Notice for all plugins
	 *
	 * @param array $active_lists Lists of all active plugin notice.
	 * @return array
	 */
	public function handle_xpo_active_notice_lists( $active_lists ) {

		if ( $this->wopb_dashboard_banner_notice( true ) || $this->wopb_dashboard_content_notice( true ) ) {
			$active_lists[ $this->plugin_notice_priority_key ] = $this->plugin_notice_priority;
		}

		return $active_lists;
	}

	/**
	 * Handle Plugin Notice for all plugins
	 *
	 * @return bool
	 */
	public function is_available_for_notice() {
		$active_notices = apply_filters( 'xpo_active_notice_lists', array() );

		if ( empty( $active_notices ) ) {
			return true;
		}

		asort( $active_notices );

		return array_key_first( $active_notices ) === $this->plugin_notice_priority_key;
	}
}
