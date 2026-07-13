<?php //phpcs:ignore
/**
 * Plugin Deactivation Handler.
 */

namespace WOPB\Includes\Durbin;

use WOPB\Includes\Durbin\DurbinClient;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation feedback and reporting.
 */
class Deactive {

	private $plugin_slug = 'product-blocks';

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $pagenow;

		if ( 'plugins.php' === $pagenow ) {
			add_action( 'admin_footer', array( $this, 'get_source_data_callback' ) );
		}
		add_action( 'wp_ajax_wopb_deactive_plugin', array( $this, 'send_plugin_data' ) );
	}

	/**
	 * Send plugin deactivation data to remote server.
	 *
	 * @param string|null $type Optional. Unused for now.
	 * @return void
	 */
	public function send_plugin_data() {
		if (
			! isset( $_POST['wopb_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wopb_nonce'] ) ), 'wopb-nonce' )
		) {
			return;
		}
		DurbinClient::send( DurbinClient::DEACTIVATE_ACTION );
	}

	/**
	 * Output deactivation modal markup, CSS, and JS.
	 *
	 * @return void
	 */
	public function get_source_data_callback() {
		$this->deactive_container_css();
		$this->deactive_container_js();
		$this->deactive_html_container();
	}

	/**
	 * Get deactivation reasons and field settings.
	 *
	 * @return array[] List of deactivation options.
	 */
	public function get_deactive_settings() {
		return array(
			array(
				'id'    => 'not-working',
				'input' => false,
				'text'  => __( 'The plugin isn’t working properly.', 'product-blocks' ),
			),
			array(
				'id'    => 'limited-features',
				'input' => false,
				'text'  => __( 'Limited features on the free version.', 'product-blocks' ),
			),
			array(
				'id'          => 'better-plugin',
				'input'       => true,
				'text'        => __( 'I found a better plugin.', 'product-blocks' ),
				'placeholder' => __( 'Please share which plugin.', 'product-blocks' ),
			),
			array(
				'id'    => 'temporary-deactivation',
				'input' => false,
				'text'  => __( "It's a temporary deactivation.", 'product-blocks' ),
			),
			array(
				'id'          => 'other',
				'input'       => true,
				'text'        => __( 'Other.', 'product-blocks' ),
				'placeholder' => __( 'Please share the reason.', 'product-blocks' ),
			),
		);
	}

	/**
	 * Output HTML for the deactivation modal.
	 *
	 * @return void
	 */
	public function deactive_html_container() {
		?>
		<div class="wopb-modal" id="wopb-deactive-modal">
			<div class="wopb-modal-wrap">
			
				<div class="wopb-modal-header">
					<h2><?php esc_html_e( 'Quick Feedback', 'product-blocks' ); ?></h2>
					<button class="wopb-modal-cancel"><span class="dashicons dashicons-no-alt"></span></button>
				</div>

				<div class="wopb-modal-body">
					<h3><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating WowStore:', 'product-blocks' ); ?></h3>
					<ul class="wopb-modal-input">
						<?php foreach ( $this->get_deactive_settings() as $key => $setting ) { ?>
							<li>
								<label>
									<input type="radio" <?php echo 0 == $key ? 'checked="checked"' : ''; ?> id="<?php echo esc_attr( $setting['id'] ); ?>" name="<?php echo esc_attr( $this->plugin_slug ); ?>" value="<?php echo esc_attr( $setting['text'] ); ?>">
									<div class="wopb-reason-text"><?php echo esc_html( $setting['text'] ); ?></div>
									<?php if ( isset( $setting['input'] ) && $setting['input'] ) { ?>
										<textarea placeholder="<?php echo esc_attr( $setting['placeholder'] ); ?>" class="wopb-reason-input <?php echo esc_attr( $key == 0 ? 'wopb-active' : '' ); ?> <?php echo esc_html( $setting['id'] ); ?>"></textarea>
									<?php } ?>
								</label>
							</li>
						<?php } ?>
					</ul>
				</div>

				<div class="wopb-modal-footer">
					<a class="wopb-modal-submit wopb-btn wopb-btn-primary" href="#"><?php esc_html_e( 'Submit & Deactivate', 'product-blocks' ); ?><span class="dashicons dashicons-update rotate"></span></a>
					<a class="wopb-modal-deactive" href="#"><?php esc_html_e( 'Skip & Deactivate', 'product-blocks' ); ?></a>
				</div>
				
			</div>
		</div>
		<?php
	}

	/**
	 * Output inline CSS for the modal.
	 *
	 * @return void
	 */
	public function deactive_container_css() {
		?>
		<style type="text/css">
			.wopb-modal {
				position: fixed;
				z-index: 99999;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				background: rgba(0,0,0,0.5);
				display: none;
				box-sizing: border-box;
				overflow: scroll;
			}
			.wopb-modal * {
				box-sizing: border-box;
			}
			.wopb-modal.modal-active {
				display: block;
			}
			.wopb-modal-wrap {
				max-width: 870px;
				width: 100%;
				position: relative;
				margin: 10% auto;
				background: #fff;
			}
			.wopb-reason-input{
				display: none;
			}
			.wopb-reason-input.wopb-active{
				display: block;
			}
			.rotate{
				animation: rotate 1.5s linear infinite; 
			}
			@keyframes rotate{
				to{ transform: rotate(360deg); }
			}
			.wopb-popup-rotate{
				animation: popupRotate 1s linear infinite; 
			}
			@keyframes popupRotate{
				to{ transform: rotate(360deg); }
			}
			#wopb-deactive-modal {
				background: rgb(0 0 0 / 85%);
				overflow: hidden;
			}
			#wopb-deactive-modal .wopb-modal-wrap {
				max-width: 570px;
				border-radius: 5px;
				margin: 5% auto;
				overflow: hidden
			}
			#wopb-deactive-modal .wopb-modal-header {
				padding: 17px 30px;
				border-bottom: 1px solid #ececec;
				display: flex;
				align-items: center;
				background: #f5f5f5;
			}
			#wopb-deactive-modal .wopb-modal-header .wopb-modal-cancel {
				padding: 0;
				border-radius: 100px;
				border: 1px solid #b9b9b9;
				background: none;
				color: #b9b9b9;
				cursor: pointer;
				transition: 400ms;
			}
			#wopb-deactive-modal .wopb-modal-header .wopb-modal-cancel:focus {
				color: red;
				border: 1px solid red;
				outline: 0;
			}
			#wopb-deactive-modal .wopb-modal-header .wopb-modal-cancel:hover {
				color: red;
				border: 1px solid red;
			}
			#wopb-deactive-modal .wopb-modal-header h2 {
				margin: 0;
				padding: 0;
				flex: 1;
				line-height: 1;
				font-size: 20px;
				text-transform: uppercase;
				color: #8e8d8d;
			}
			#wopb-deactive-modal .wopb-modal-body {
				padding: 25px 30px;
			}
			#wopb-deactive-modal .wopb-modal-body h3{
				padding: 0;
				margin: 0;
				line-height: 1.4;
				font-size: 15px;
			}
			#wopb-deactive-modal .wopb-modal-body ul {
				margin: 25px 0 10px;
			}
			#wopb-deactive-modal .wopb-modal-body ul li {
				display: flex;
				margin-bottom: 10px;
				color: #807d7d;
			}
			#wopb-deactive-modal .wopb-modal-body ul li:last-child {
				margin-bottom: 0;
			}
			#wopb-deactive-modal .wopb-modal-body ul li label {
				align-items: center;
				width: 100%;
			}
			#wopb-deactive-modal .wopb-modal-body ul li label input {
				padding: 0 !important;
				margin: 0;
				display: inline-block;
			}
			#wopb-deactive-modal .wopb-modal-body ul li label textarea {
				margin-top: 8px;
				width: 100% !important;
			}
			#wopb-deactive-modal .wopb-modal-body ul li label .wopb-reason-text {
				margin-left: 8px;
				display: inline-block;
			}
			#wopb-deactive-modal .wopb-modal-footer {
				padding: 0 30px 30px 30px;
				display: flex;
				align-items: center;
			}
			#wopb-deactive-modal .wopb-modal-footer .wopb-modal-submit {
				display: flex;
				align-items: center;
				padding: 12px 22px;
				border-radius: 3px;
				background: #FF176B;
				color: #fff;
				font-size: 16px;
				font-weight: 600;
				text-decoration: none;
			}
			#wopb-deactive-modal .wopb-modal-footer .wopb-modal-submit span {
				margin-left: 4px;
				display: none;
			}
			#wopb-deactive-modal .wopb-modal-footer .wopb-modal-submit.loading span {
				display: block;
			}
			#wopb-deactive-modal .wopb-modal-footer .wopb-modal-deactive {
				margin-left: auto;
				color: #c5c5c5;
				text-decoration: none;
			}
			.wpxpo-btn-tracking-notice {
				display: flex;
				align-items: center;
				flex-wrap: wrap;
				padding: 5px 0;
			}
			.wpxpo-btn-tracking-notice .wpxpo-btn-tracking {
				margin: 0 5px;
				text-decoration: none;
			}
		</style>
		<?php
	}

	/**
	 * Output inline JavaScript for the modal logic.
	 *
	 * @return void
	 */
	public function deactive_container_js() {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				'use strict';

				// Modal Radio Input Click Action
				$('.wopb-modal-input input[type=radio]').on( 'change', function(e) {
					$('.wopb-reason-input').removeClass('wopb-active');
					$('.wopb-modal-input').find( '.'+$(this).attr('id') ).addClass('wopb-active');
				});

				// Modal Cancel Click Action
				$( document ).on( 'click', '.wopb-modal-cancel', function(e) {
					$( '#wopb-deactive-modal' ).removeClass( 'modal-active' );
				});
				
				$(document).on('click', function(event) {
					const $popup = $('#wopb-deactive-modal');
					const $modalWrap = $popup.find('.wopb-modal-wrap');

					if ( !$modalWrap.is(event.target) && $modalWrap.has(event.target).length === 0 && $popup.hasClass('modal-active')) {
						$popup.removeClass('modal-active');
					}
				});

				// Deactivate Button Click Action
				$( document ).on( 'click', '#deactivate-product-blocks', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$( '#wopb-deactive-modal' ).addClass( 'modal-active' );
					$( '.wopb-modal-deactive' ).attr( 'href', $(this).attr('href') );
					$( '.wopb-modal-submit' ).attr( 'href', $(this).attr('href') );
				});

				$( document ).on( 'click', '#deactivate-product-blocks, a[href*="product-blocks.php"], a[href*="plugins.php?action=deactivate"]', function(e) {
					var href = $(this).attr('href') || '';
					if (
						href.includes('product-blocks.php') &&
						href.includes('plugins.php?action=deactivate') &&
						! $(this).hasClass('wopb-modal-deactive') &&
						! $(this).hasClass('wopb-modal-submit')
					) {
						e.preventDefault();
						e.stopPropagation();
						$( '#wopb-deactive-modal' ).addClass( 'modal-active' );
						$( '.wopb-modal-deactive' ).attr( 'href', $(this).attr('href') );
						$( '.wopb-modal-submit' ).attr( 'href', $(this).attr('href') );
					}
				});


				// Submit to Remote Server
				$( document ).on( 'click', '.wopb-modal-submit', function(e) {
					e.preventDefault();
					
					$(this).addClass('loading');
					const url = $(this).attr('href')

					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: { 
							action: 'wopb_deactive_plugin',
							wopb_nonce: wopb_option.security,
							cause_id: $('#wopb-deactive-modal input[type=radio]:checked').attr('id'),
							cause_title: $('#wopb-deactive-modal .wopb-modal-input input[type=radio]:checked').val(),
							cause_details: $('#wopb-deactive-modal .wopb-reason-input.wopb-active').val()
						},
						success: function (data) {
							$( '#wopb-deactive-modal' ).removeClass( 'modal-active' );
							window.location.href = url;
						},
						error: function(xhr) {
							console.log( 'Error occured. Please try again' + xhr.statusText + xhr.responseText );
						},
					});

				});

			});
		</script>
		<?php
	}
}
