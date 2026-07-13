<?php //phpcs:ignore
/**
 * Plugin Deactivation Handler.
 *
 * @package wtrs\Deactive
 */

namespace WTRS\Includes\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation feedback and reporting.
 */
class Deactive {

	/**
	 * Plugin slug
	 *
	 * @var string
	 */
	private $plugin_slug = 'wow-table-rate-shipping';

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $pagenow;

		if ( 'plugins.php' === $pagenow ) {
			add_action( 'admin_footer', array( $this, 'get_source_data_callback' ) );
		}
		add_action( 'wp_ajax_wtrs_deactive_plugin', array( $this, 'send_plugin_data' ) );
	}

	/**
	 * Send plugin deactivation data to remote server.
	 *
	 * @return void
	 */
	public function send_plugin_data() {
		DurbinClient::send( DurbinClient::DEACTIVATE_ACTION );
		wp_send_json_success();
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
				'text'  => __( 'The plugin isn’t working properly.', 'wow-table-rate-shipping' ),
			),
			array(
				'id'    => 'limited-features',
				'input' => false,
				'text'  => __( 'Limited features on the free version.', 'wow-table-rate-shipping' ),
			),
			array(
				'id'          => 'better-plugin',
				'input'       => true,
				'text'        => __( 'I found a better plugin.', 'wow-table-rate-shipping' ),
				'placeholder' => __( 'Please share which plugin.', 'wow-table-rate-shipping' ),
			),
			array(
				'id'    => 'temporary-deactivation',
				'input' => false,
				'text'  => __( "It's a temporary deactivation.", 'wow-table-rate-shipping' ),
			),
			array(
				'id'          => 'other',
				'input'       => true,
				'text'        => __( 'Other.', 'wow-table-rate-shipping' ),
				'placeholder' => __( 'Please share the reason.', 'wow-table-rate-shipping' ),
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
		<div class="wtrs-modal" id="wtrs-deactive-modal">
			<div class="wtrs-modal-wrap">
			
				<div class="wtrs-modal-header">
					<h2><?php esc_html_e( 'Quick Feedback', 'wow-table-rate-shipping' ); ?></h2>
					<button class="wtrs-modal-cancel"><span class="dashicons dashicons-no-alt"></span></button>
				</div>

				<div class="wtrs-modal-body">
					<h3><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating WowShipping:', 'wow-table-rate-shipping' ); ?></h3>
					<ul class="wtrs-modal-input">
						<?php foreach ( $this->get_deactive_settings() as $key => $setting ) { ?>
							<li>
								<label>
									<input type="radio" <?php echo 0 == $key ? 'checked="checked"' : ''; ?> id="<?php echo esc_attr( $setting['id'] ); ?>" name="<?php echo esc_attr( $this->plugin_slug ); ?>" value="<?php echo esc_attr( $setting['text'] ); ?>">
									<div class="wtrs-reason-text"><?php echo esc_html( $setting['text'] ); ?></div>
									<?php if ( isset( $setting['input'] ) && $setting['input'] ) { ?>
										<textarea placeholder="<?php echo esc_attr( $setting['placeholder'] ); ?>" class="wtrs-reason-input <?php echo $key == 0 ? 'wtrs-active' : ''; ?> <?php echo esc_html( $setting['id'] ); ?>"></textarea>
									<?php } ?>
								</label>
							</li>
						<?php } ?>
					</ul>
				</div>

				<div class="wtrs-modal-footer">
					<a class="wtrs-modal-submit wtrs-btn wtrs-btn-primary" href="#"><?php esc_html_e( 'Submit & Deactivate', 'wow-table-rate-shipping' ); ?><span class="dashicons dashicons-update rotate"></span></a>
					<a class="wtrs-modal-deactive" href="#"><?php esc_html_e( 'Skip & Deactivate', 'wow-table-rate-shipping' ); ?></a>
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
			.wtrs-modal {
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
			.wtrs-modal * {
				box-sizing: border-box;
			}
			.wtrs-modal.modal-active {
				display: block;
			}
			.wtrs-modal-wrap {
				max-width: 870px;
				width: 100%;
				position: relative;
				margin: 10% auto;
				background: #fff;
			}
			.wtrs-reason-input{
				display: none;
			}
			.wtrs-reason-input.wtrs-active{
				display: block;
			}
			.rotate{
				animation: rotate 1.5s linear infinite; 
			}
			@keyframes rotate{
				to{ transform: rotate(360deg); }
			}
			.wtrs-popup-rotate{
				animation: popupRotate 1s linear infinite; 
			}
			@keyframes popupRotate{
				to{ transform: rotate(360deg); }
			}
			#wtrs-deactive-modal {
				background: rgb(0 0 0 / 85%);
				overflow: hidden;
			}
			#wtrs-deactive-modal .wtrs-modal-wrap {
				max-width: 570px;
				border-radius: 5px;
				margin: 5% auto;
				overflow: hidden
			}
			#wtrs-deactive-modal .wtrs-modal-header {
				padding: 17px 30px;
				border-bottom: 1px solid #ececec;
				display: flex;
				align-items: center;
				background: #f5f5f5;
			}
			#wtrs-deactive-modal .wtrs-modal-header .wtrs-modal-cancel {
				padding: 0;
				border-radius: 100px;
				border: 1px solid #b9b9b9;
				background: none;
				color: #b9b9b9;
				cursor: pointer;
				transition: 400ms;
			}
			#wtrs-deactive-modal .wtrs-modal-header .wtrs-modal-cancel:focus {
				color: red;
				border: 1px solid red;
				outline: 0;
			}
			#wtrs-deactive-modal .wtrs-modal-header .wtrs-modal-cancel:hover {
				color: red;
				border: 1px solid red;
			}
			#wtrs-deactive-modal .wtrs-modal-header h2 {
				margin: 0;
				padding: 0;
				flex: 1;
				line-height: 1;
				font-size: 20px;
				text-transform: uppercase;
				color: #8e8d8d;
			}
			#wtrs-deactive-modal .wtrs-modal-body {
				padding: 25px 30px;
			}
			#wtrs-deactive-modal .wtrs-modal-body h3{
				padding: 0;
				margin: 0;
				line-height: 1.4;
				font-size: 15px;
			}
			#wtrs-deactive-modal .wtrs-modal-body ul {
				margin: 25px 0 10px;
			}
			#wtrs-deactive-modal .wtrs-modal-body ul li {
				display: flex;
				margin-bottom: 10px;
				color: #807d7d;
			}
			#wtrs-deactive-modal .wtrs-modal-body ul li:last-child {
				margin-bottom: 0;
			}
			#wtrs-deactive-modal .wtrs-modal-body ul li label {
				align-items: center;
				width: 100%;
			}
			#wtrs-deactive-modal .wtrs-modal-body ul li label input {
				padding: 0 !important;
				margin: 0;
				display: inline-block;
			}
			#wtrs-deactive-modal .wtrs-modal-body ul li label textarea {
				margin-top: 8px;
				width: 100% !important;
			}
			#wtrs-deactive-modal .wtrs-modal-body ul li label .wtrs-reason-text {
				margin-left: 8px;
				display: inline-block;
			}
			#wtrs-deactive-modal .wtrs-modal-footer {
				padding: 0 30px 30px 30px;
				display: flex;
				align-items: center;
			}
			#wtrs-deactive-modal .wtrs-modal-footer .wtrs-modal-submit {
				display: flex;
				align-items: center;
				padding: 12px 22px;
				border-radius: 3px;
				background: #0062ff;
				color: #fff;
				font-size: 16px;
				font-weight: 600;
				text-decoration: none;
			}
			#wtrs-deactive-modal .wtrs-modal-footer .wtrs-modal-submit span {
				margin-left: 4px;
				display: none;
			}
			#wtrs-deactive-modal .wtrs-modal-footer .wtrs-modal-submit.loading span {
				display: block;
			}
			#wtrs-deactive-modal .wtrs-modal-footer .wtrs-modal-deactive {
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
		<script id="wtrs-deactive-js" type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				'use strict';

				// Modal Radio Input Click Action
				$('.wtrs-modal-input input[type=radio]').on( 'change', function(e) {
					$('.wtrs-reason-input').removeClass('wtrs-active');
					$('.wtrs-modal-input').find( '.'+$(this).attr('id') ).addClass('wtrs-active');
				});

				// Modal Cancel Click Action
				$( document ).on( 'click', '.wtrs-modal-cancel', function(e) {
					$( '#wtrs-deactive-modal' ).removeClass( 'modal-active' );
				});
				
				$(document).on('click', function(event) {
					const $popup = $('#wtrs-deactive-modal');
					const $modalWrap = $popup.find('.wtrs-modal-wrap');

					if ( !$modalWrap.is(event.target) && $modalWrap.has(event.target).length === 0 && $popup.hasClass('modal-active')) {
						$popup.removeClass('modal-active');
					}
				});

				// Deactivate Button Click Action
				$( document ).on( 'click', '#deactivate-wow-table-rate-shipping', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$( '#wtrs-deactive-modal' ).addClass( 'modal-active' );
					$( '.wtrs-modal-deactive' ).attr( 'href', $(this).attr('href') );
					$( '.wtrs-modal-submit' ).attr( 'href', $(this).attr('href') );
				});

				// Submit to Remote Server
				$( document ).on( 'click', '.wtrs-modal-submit', function(e) {
					e.preventDefault();
					
					$(this).addClass('loading');
					const url = $(this).attr('href')

					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: { 
							action: 'wtrs_deactive_plugin',
							cause_id: $('#wtrs-deactive-modal input[type=radio]:checked').attr('id'),
							cause_title: $('#wtrs-deactive-modal .wtrs-modal-input input[type=radio]:checked').val(),
							cause_details: $('#wtrs-deactive-modal .wtrs-reason-input.wtrs-active').val()
						},
						success: function (data) {
							$( '#wtrs-deactive-modal' ).removeClass( 'modal-active' );
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
