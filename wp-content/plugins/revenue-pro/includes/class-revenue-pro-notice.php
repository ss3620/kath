<?php
namespace RevenuePro;

defined( 'ABSPATH' ) || exit;

/**
 * RevenueX Notice
 *
 */
class Revenue_Pro_Notice {

	public static $is_css_added = false;


	public static function get_installation_notice_css() {
		if ( self::$is_css_added ) {
			return;
		}
		self::$is_css_added = true;
		?>
		<style>
			.button.activated-message:before, .button.activating-message:before, .button.installed:before, .button.installing:before, .button.updated-message:before, .button.updating-message:before {
				margin: 12px 5px 0 -2px;
			}
			.button.activating-message:before, .button.installing:before, .button.updating-message:before, .import-php .updating-message:before, .update-message p:before, .updating-message p:before {
				color: #ffffff;
				content: "\f463";
			}
		.revx-wc-install {
				display: flex;
				align-items: center;
				background: #fff;
				margin-top: 40px;
				width: calc(100% - 30px);
				border: 1px solid #ccd0d4;
				border-left: 3px solid #46b450;
				padding: 4px;
				border-radius: 4px;
				gap: 10px;
			}

			.revx-wc-install__img {
				margin-right: 10px;
				max-width: 120px;
				margin-left: 10px;
			}

			.revx-wc-install__body {
				flex: 1;
				padding-top: 10px;
			}

			.revx-wc-install__body > div {
				max-width: 450px;
				margin-bottom: 20px;
			}

			.revx-wc-install__heading {
				margin-top: 5px;
				font-size: 24px;
				margin-bottom: 5px;
			}

			.revx-wc-install__btn {
				margin-top: 15px;
				display: inline-block;
			}

			.revx-wc-install__spinner {
				display: none;
				animation: dashicons-spin 1s infinite linear;
			}

			.revx-wc-install--loading .revx-wc-install__spinner {
				display: inline-block;
				margin-top: 12px;
				margin-right: 5px;
			}

			@keyframes dashicons-spin {
				0% {
					transform: rotate(0deg);
				}
				100% {
					transform: rotate(360deg);
				}
			}

			.revx-wc-install__notice {
				color: white;
				background-color: #6C6CFF;
				position: relative;
				font-size: 16px;
				padding-left: 10px;
				line-height: 23px;
			}

			.revx-wc-install__notice-wrapper {
				margin-bottom: 0 !important;
				padding: 10px 5px;
			}

			.revx-wc-install__btn--pro {
				margin-left: 5px;
				background-color: #3c3cb7 !important;
				border-radius: 4px;
				max-height: 30px !important;
				padding: 8px 12px !important;
				font-size: 14px;
				position: relative;
				top: -4px;
			}

			.revx-wc-install__btn--pro:hover {
				background-color: #29298c !important;
			}

			.revx-wc-install__dismiss {
				position: absolute;
				top: 0;
				right: 0;
				color: white;
				background-color: #3f3fa6;
				padding: 4px 5px 5px;
				font-size: 12px;
				line-height: 1;
				border-bottom-left-radius: 3px;
				text-decoration: none;
			}

			.revx-wc-install__dismiss:hover {
				color: red;
			}

			.revx-wc-install__dismiss .dashicons {
				display: inline-block;
				font-size: 16px;
			}

			.revx-wc-install--pro .revx-wc-install__heading {
				font-size: 20px;
				margin-bottom: 5px;
			}

			.revx-wc-install--pro .revx-wc-install__body > div {
				max-width: 100%;
				margin-bottom: 10px;
			}

			.revx-wc-install--pro .revx-wc-install__btn--pro {
				background: #2271b1;
				color: #fff;
			}

			.revx-wc-install--pro .revx-wc-install__btn--pro:hover,
			.revx-wc-install--pro .revx-wc-install__btn--pro:focus {
				background: #185a8f;
			}

			.revx-wc-install--pro .revx-wc-install__btn--hero {
				padding: 8px 14px !important;
				min-height: inherit !important;
				line-height: 1 !important;
				box-shadow: none;
				border: none;
				transition: 400ms;
			}

			.revx-wc-install--pro .revx-wc-install__btn--hero:hover,
			.revx-wc-install--pro .revx-wc-install__btn--hero:focus {
				border: none;
				box-shadow: none;
			}

		</style>

		<?php
	}
	public static function get_wowrevenue_not_active_notice() {
		self::get_installation_notice_css();
		?>
			<div class="revx-wc-install">
				<img
					loading="lazy"
					width="200"
					src="<?php echo esc_url( REVENUE_PRO_URL . 'assets/images/revenue_logo.png' ); ?>"
					alt="Revenue logo"
					class="revx-wc-install__img"
				/>
				<div class="revx-wc-install__body">
					<h3 class="revx-wc-install__heading">
						<?php esc_html_e( 'You installed WowRevenue Pro', 'revenue-pro' ); ?>
					</h3>
					<p>
						<?php esc_html_e( 'To use all the pro features, you need to activate the free version of WowRevenue', 'revenue-pro' ); ?>
					</p>
					<p>
						<button
							id="revx-activate-wowrevenue"
							class="revx-wc-install__btn button button-primary button-hero"
						>
							<?php esc_html_e( 'Activate WowRevenue', 'revenue-pro' ); ?>
							<span class="revx-wc-install__spinner spinner" style="display:none;"></span>
						</button>
					</p>
					<div id="installation-msg" class="revx-wc-install__msg"></div>
				</div>
			</div>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('#revx-activate-wowrevenue').on('click', function(e) {
						e.preventDefault();
						let $button = $(this);
						$button.removeClass('installing').addClass('activating');
						$button.text('<?php echo esc_js( __( 'Activating...', 'revenue-pro' ) ); ?>').append('<span class="spinner"></span>');
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'revenue_activate_wowrevenue',
								_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'activate_wowrevenue' ) ); ?>'
							},
							success: function(response) {
								$button.removeClass('activating');
								$button.find('.spinner').hide();
								if (response.success) {
									window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=' . apply_filters( 'revenue_menu_slug', 'revenue' ) . '#/' ) ); ?>';
								} else {
									console.log( response.data );
								}
							},
							error: function() {
								$button.removeClass('activating');
								$button.find('.spinner').hide();
								console.log('There was an error activating WowRevenue.');
							}
						});
					});
				});
			</script>
		<?php
	}

	public static function get_wowrevenue_not_installed_notice() {
		self::get_installation_notice_css();
		?>
		<div class="revx-wc-install">
			<img
				loading="lazy"
				width="200"
				src="<?php echo esc_url( REVENUE_PRO_URL . 'assets/images/revenue_logo.png' ); ?>"
				alt="Revenue logo"
				class="revx-wc-install__img"
			/>
			<div class="revx-wc-install__body">
				<h3 class="revx-wc-install__heading">
					<?php esc_html_e( 'You installed WowRevenue Pro', 'revenue-pro' ); ?>
				</h3>
				<p>
					<?php esc_html_e( 'To use all the pro features, you need to activate the free version of WowRevenue', 'revenue-pro' ); ?>
				</p>
				<p>
					<button
						id="revx-install-wowrevenue"
						class="revx-wc-install__btn button button-primary button-hero"
					>
						<?php esc_html_e( 'Install WowRevenue', 'revenue-pro' ); ?>
						<span class="revx-wc-install__spinner spinner" style="display:none;"></span>
					</button>
				</p>
				<div id="installation-msg" class="revx-wc-install__msg"></div>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#revx-install-wowrevenue').on('click', function(e) {
					e.preventDefault();
					var $button = $(this);
					$button.addClass('installing');
					$button.find('.spinner').show();
					$button.text('<?php echo esc_js( __( 'Installing...', 'revenue-pro' ) ); ?>').append('<span class="spinner"></span>');
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'revenue_install_wowrevenue',
							_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'install_wowrevenue' ) ); ?>'// phpcs:ignore
						},
						success: function(response) {
							if (response.success) {
								$button.removeClass('installing').addClass('activating');
								$button.text('<?php echo esc_js( __( 'Activating...', 'revenue-pro' ) ); ?>').append('<span class="spinner"></span>');
								$.ajax({
									url: ajaxurl,
									type: 'POST',
									data: {
										action: 'revenue_activate_wowrevenue',
										_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'activate_wowrevenue' ) ); ?>'// phpcs:ignore
									},
									success: function(response) {
										$button.removeClass('activating');
										$button.find('.spinner').hide();
										if (response.success) {
											//location.reload();
											window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=' . apply_filters( 'revenue_menu_slug', 'revenue' ) . '#/' ) ); ?>';
										} else {
											console.log(response.data);
										}
									},
									error: function() {
										$button.removeClass('activating');
										$button.find('.spinner').hide();
										console.log('There was an error activating WowRevenue.');
									}
								});
							} else {
								$button.removeClass('installing');
								$button.find('.spinner').hide();
								console.log( response.data );
							}
						},
						error: function() {
							$button.removeClass('installing');
							$button.find('.spinner').hide();
							console.log('There was an error installing WowRevenue.');
						}
					});
				});
			});
			</script>

		<?php
	}

	/**
	 * Shown when the active free plugin is older than this Pro version requires
	 * and it could not be auto-updated to a compatible version.
	 *
	 * @return void
	 * @since  2.2.0
	 */
	public static function get_wowrevenue_incompatible_version_notice() {
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'WowRevenue version mismatch', 'revenue-pro' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'WowRevenue Pro requires a newer version of WowRevenue (free), and the automatic update did not complete. Pro campaign types stay paused until WowRevenue is updated; your campaigns and settings are untouched.', 'revenue-pro' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( 'https://wordpress.org/plugins/revenue/' ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Download the latest WowRevenue', 'revenue-pro' ); ?>
				</a>
				&nbsp;|&nbsp;
				<a href="<?php echo esc_url( 'https://www.wowrevenue.com/' ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Contact support', 'revenue-pro' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
