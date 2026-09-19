<?php //phpcs:ignore
/**
 * Plugin Deactivation Handler.
 *
 * @package PRAD\Deactive
 * @since
 */

namespace PRAD\Includes;

use PRAD\Includes\Admin\Durbin\DurbinClient;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation feedback and reporting.
 */
class Deactive {

	/**
	 * Plugin slug used for identifying the plugin.
	 *
	 * @var string
	 */
	private $plugin_slug = 'product-addons';

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $pagenow;

		if ( 'plugins.php' === $pagenow ) {
			add_action( 'admin_footer', array( $this, 'get_source_data_callback' ) );
		}
		add_action( 'wp_ajax_prad_deactive_plugin', array( $this, 'send_plugin_data' ) );
	}

	/**
	 * Send plugin deactivation data to remote server.
	 *
	 * @return void
	 */
	public function send_plugin_data() {
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
				'text'  => __( 'The plugin isn’t working properly.', 'product-addons' ),
			),
			array(
				'id'    => 'limited-features',
				'input' => false,
				'text'  => __( 'Limited features on the free version.', 'product-addons' ),
			),
			array(
				'id'          => 'better-plugin',
				'input'       => true,
				'text'        => __( 'I found a better plugin.', 'product-addons' ),
				'placeholder' => __( 'Please share which plugin.', 'product-addons' ),
			),
			array(
				'id'    => 'temporary-deactivation',
				'input' => false,
				'text'  => __( "It's a temporary deactivation.", 'product-addons' ),
			),
			array(
				'id'          => 'other',
				'input'       => true,
				'text'        => __( 'Other.', 'product-addons' ),
				'placeholder' => __( 'Please share the reason.', 'product-addons' ),
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
		<div class="prad-modal" id="prad-deactive-modal">
			<div class="prad-modal-wrap">
			
				<div class="prad-modal-header">
					<h2><?php esc_html_e( 'Quick Feedback', 'product-addons' ); ?></h2>
					<button class="prad-modal-cancel"><span class="dashicons dashicons-no-alt"></span></button>
				</div>

				<div class="prad-modal-body">
					<h3><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating WowAddons:', 'product-addons' ); ?></h3>
					<ul class="prad-modal-input">
						<?php foreach ( $this->get_deactive_settings() as $key => $setting ) { ?>
							<li>
								<label>
									<input type="radio" <?php echo 0 == $key ? 'checked="checked"' : ''; ?> id="<?php echo esc_attr( $setting['id'] ); ?>" name="<?php echo esc_attr( $this->plugin_slug ); ?>" value="<?php echo esc_attr( $setting['text'] ); ?>">
									<div class="prad-reason-text"><?php echo esc_html( $setting['text'] ); ?></div>
									<?php if ( isset( $setting['input'] ) && $setting['input'] ) { ?>
										<textarea id="prad-tarea" placeholder="<?php echo esc_attr( $setting['placeholder'] ); ?>" class="prad-reason-input <?php echo $key == 0 ? 'prad-active' : ''; ?> <?php echo esc_html( $setting['id'] ); ?>"></textarea>
									<?php } ?>
								</label>
							</li>
						<?php } ?>
					</ul>
				</div>

				<div class="prad-modal-footer">
					<a class="prad-modal-submit prad-btn prad-btn-primary" href="#"><?php esc_html_e( 'Submit & Deactivate', 'product-addons' ); ?><span class="dashicons dashicons-update rotate"></span></a>
					<a class="prad-modal-deactive" href="#"><?php esc_html_e( 'Skip & Deactivate', 'product-addons' ); ?></a>
				</div>
				
			</div>
		</div>
		<?php
	}

	/**
	 * Performs cleanup tasks when the plugin is deactivated.
	 *
	 * @return void
	 */
	public static function cleanup() {
		global $wpdb;

		// Remove scheduled cron jobs.
		wp_clear_scheduled_hook( 'prad_cleanup_upload_files' );

		// Remove all plugin temporary data.

		$prad_option_posts = get_posts(
			array(
				'post_type'      => 'prad_option',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $prad_option_posts ) ) {
			foreach ( $prad_option_posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		$options = array(
			'prad_addons_default_option_created',
			'prad_option_assign_all',
			'prad_product_image_update_data',
			'prad_global_style',
			'prad_global_style_css',
			'prad_settings',
			'prad_custom_fonts',
			'edd_prad_license_data',
			'edd_prad_license_key',
		);

		foreach ( $options as $option ) {
			delete_option( $option );
		}

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}prad_stats_graph" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}prad_stats_table" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->termmeta} WHERE meta_key = %s", 'prad_term_assigned_meta_inc' ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", 'prad_product_assigned_meta_exc' ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", 'prad_product_assigned_meta_inc' ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	}

	/**
	 * Output inline CSS for the modal.
	 *
	 * @return void
	 */
	public function deactive_container_css() {
		?>
		<style type="text/css">
			.prad-modal {
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
			.prad-modal * {
				box-sizing: border-box;
			}
			.prad-modal.modal-active {
				display: block;
			}
			.prad-modal-wrap {
				max-width: 870px;
				width: 100%;
				position: relative;
				margin: 10% auto;
				background: #fff;
			}
			.prad-reason-input{
				display: none;
			}
			.prad-reason-input.prad-active{
				display: block;
			}
			.rotate{
				animation: rotate 1.5s linear infinite; 
			}
			@keyframes rotate{
				to{ transform: rotate(360deg); }
			}
			.prad-popup-rotate{
				animation: popupRotate 1s linear infinite; 
			}
			@keyframes popupRotate{
				to{ transform: rotate(360deg); }
			}
			#prad-deactive-modal {
				background: rgb(0 0 0 / 85%);
				overflow: hidden;
			}
			#prad-deactive-modal .prad-modal-wrap {
				max-width: 570px;
				border-radius: 5px;
				margin: 5% auto;
				overflow: hidden;
			}
			#prad-deactive-modal .prad-modal-header {
				padding: 17px 30px;
				border-bottom: 1px solid #ececec;
				display: flex;
				align-items: center;
				background: #f5f5f5;
			}
			#prad-deactive-modal .prad-modal-header .prad-modal-cancel {
				padding: 0;
				border-radius: 100px;
				border: 1px solid #b9b9b9;
				background: none;
				color: #b9b9b9;
				cursor: pointer;
				transition: 400ms;
			}
			#prad-deactive-modal .prad-modal-header .prad-modal-cancel:focus {
				color: red;
				border: 1px solid red;
				outline: 0;
			}
			#prad-deactive-modal .prad-modal-header .prad-modal-cancel:hover {
				color: red;
				border: 1px solid red;
			}
			#prad-deactive-modal .prad-modal-header h2 {
				margin: 0;
				padding: 0;
				flex: 1;
				line-height: 1;
				font-size: 20px;
				text-transform: uppercase;
				color: #8e8d8d;
			}
			#prad-deactive-modal .prad-modal-body {
				padding: 25px 30px;
			}
			#prad-deactive-modal .prad-modal-body h3{
				padding: 0;
				margin: 0;
				line-height: 1.4;
				font-size: 15px;
			}
			#prad-deactive-modal .prad-modal-body ul {
				margin: 25px 0 10px;
			}
			#prad-deactive-modal .prad-modal-body ul li {
				display: flex;
				margin-bottom: 10px;
				color: #807d7d;
			}
			#prad-deactive-modal .prad-modal-body ul li:last-child {
				margin-bottom: 0;
			}
			#prad-deactive-modal .prad-modal-body ul li label {
				align-items: center;
				width: 100%;
			}
			#prad-deactive-modal .prad-modal-body ul li label input {
				padding: 0 !important;
				margin: 0;
				display: inline-block;
			}
			#prad-deactive-modal .prad-modal-body ul li label textarea {
				margin-top: 8px;
				width: 100%;
			}
			#prad-deactive-modal .prad-modal-body ul li label .prad-reason-text {
				margin-left: 8px;
				display: inline-block;
			}
			#prad-deactive-modal .prad-modal-footer {
				padding: 0 30px 30px 30px;
				display: flex;
				align-items: center;
			}
			#prad-deactive-modal .prad-modal-footer .prad-modal-submit {
				display: flex;
				align-items: center;
				padding: 12px 22px;
				border-radius: 3px;
				background: #86a62c;
				color: #fff;
				font-size: 16px;
				font-weight: 600;
				text-decoration: none;
			}
			#prad-deactive-modal .prad-modal-footer .prad-modal-submit span {
				margin-left: 4px;
				display: none;
			}
			#prad-deactive-modal .prad-modal-footer .prad-modal-submit.loading span {
				display: block;
			}
			#prad-deactive-modal .prad-modal-footer .prad-modal-deactive {
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
				$('.prad-modal-input input[type=radio]').on( 'change', function(e) {
					$('.prad-reason-input').removeClass('prad-active');
					$('.prad-modal-input').find( '.'+$(this).attr('id') ).addClass('prad-active');
				});

				// Modal Cancel Click Action
				$( document ).on( 'click', '.prad-modal-cancel', function(e) {
					$( '#prad-deactive-modal' ).removeClass( 'modal-active' );
				});
				
				$(document).on('click', function(event) {
					const $popup = $('#prad-deactive-modal');
					const $modalWrap = $popup.find('.prad-modal-wrap');

					if ( !$modalWrap.is(event.target) && $modalWrap.has(event.target).length === 0 && $popup.hasClass('modal-active')) {
						$popup.removeClass('modal-active');
					}
				});

				// Deactivate Button Click Action
				$( document ).on( 'click', '#deactivate-product-addons', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$( '#prad-deactive-modal' ).addClass( 'modal-active' );
					$( '.prad-modal-deactive' ).attr( 'href', $(this).attr('href') );
					$( '.prad-modal-submit' ).attr( 'href', $(this).attr('href') );
				});

				// Submit to Remote Server
				$( document ).on( 'click', '.prad-modal-submit', function(e) {
					e.preventDefault();
					
					$(this).addClass('loading');
					const url = $(this).attr('href')

					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: { 
							action: 'prad_deactive_plugin',
							cause_id: $('#prad-deactive-modal input[type=radio]:checked').attr('id'),
							cause_title: $('#prad-deactive-modal .prad-modal-input input[type=radio]:checked').val(),
							cause_details: $('#prad-deactive-modal .prad-reason-input.prad-active').val()
						},
						success: function (data) {
							$( '#prad-deactive-modal' ).removeClass( 'modal-active' );
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
