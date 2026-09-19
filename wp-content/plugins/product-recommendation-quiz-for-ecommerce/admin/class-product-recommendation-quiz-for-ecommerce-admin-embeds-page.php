<?php
/**
 * The "Display" settings subpage for the site-wide app embeds.
 *
 * The WordPress equivalent of Shopify's app-embed toggles: a settings subpage
 * under the plugin menu where the merchant enables and configures the global
 * auto-popup and chat button. Standard WP Settings API — `register_setting`
 * with the shared sanitize callback, an `options.php` form, `manage_options`
 * gate. The main plugin screen stays the embedded app iframe; this is a
 * subpage, consistent with the "no unsolicited admin UI on the main screen"
 * rule.
 *
 * @link       https://revenuehunt.com/
 * @since      2.5.0
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers and renders the app-embeds ("Display") settings subpage.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */
class Product_Recommendation_Quiz_For_Ecommerce_Admin_Embeds_Page {

	/**
	 * The parent menu slug this subpage is attached to (the main plugin menu).
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const PARENT_SLUG = 'prqfw';

	/**
	 * This subpage's own menu slug.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const MENU_SLUG = 'prqfw-display';

	/**
	 * Register the "Display" submenu under the plugin menu.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Display', 'product-recommendation-quiz-for-ecommerce' ),
			__( 'Display', 'product-recommendation-quiz-for-ecommerce' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Register the embeds option with the Settings API.
	 *
	 * The sanitize callback is the shared store's `sanitize()`, so the admin
	 * save path and the front-end read path coerce identically.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			Product_Recommendation_Quiz_For_Ecommerce_Embeds_Settings::GROUP,
			Product_Recommendation_Quiz_For_Ecommerce_Embeds_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Product_Recommendation_Quiz_For_Ecommerce_Embeds_Settings', 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Render the settings form.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'product-recommendation-quiz-for-ecommerce' ),
				esc_html__( 'Permission Denied', 'product-recommendation-quiz-for-ecommerce' ),
				array( 'response' => 403 )
			);
		}

		$settings   = Product_Recommendation_Quiz_For_Ecommerce_Embeds_Settings::get();
		$auto_popup = $settings['auto_popup'];
		$chat       = $settings['chat'];
		$option     = Product_Recommendation_Quiz_For_Ecommerce_Embeds_Settings::OPTION;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Display', 'product-recommendation-quiz-for-ecommerce' ); ?></h1>
			<p><?php esc_html_e( 'Enable the site-wide quiz embeds. These appear on every page of your store (except cart and checkout). To place a quiz at a specific spot instead, use the quiz blocks in the editor.', 'product-recommendation-quiz-for-ecommerce' ); ?></p>

			<form action="options.php" method="post">
				<?php settings_fields( Product_Recommendation_Quiz_For_Ecommerce_Embeds_Settings::GROUP ); ?>

				<h2><?php esc_html_e( 'Auto popup', 'product-recommendation-quiz-for-ecommerce' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Opens the quiz in a popup automatically after a delay or on exit intent.', 'product-recommendation-quiz-for-ecommerce' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable auto popup', 'product-recommendation-quiz-for-ecommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[auto_popup][enabled]" value="1" <?php checked( $auto_popup['enabled'] ); ?> />
								<?php esc_html_e( 'Show the auto popup site-wide', 'product-recommendation-quiz-for-ecommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="prq-auto-popup-quiz-id"><?php esc_html_e( 'Quiz ID', 'product-recommendation-quiz-for-ecommerce' ); ?></label></th>
						<td>
							<input type="text" id="prq-auto-popup-quiz-id" class="regular-text" name="<?php echo esc_attr( $option ); ?>[auto_popup][quiz_id]" value="<?php echo esc_attr( $auto_popup['quiz_id'] ); ?>" />
							<p class="description"><?php esc_html_e( 'The code from your quiz Share link, e.g. the CODE in /public/quiz/CODE.', 'product-recommendation-quiz-for-ecommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="prq-auto-popup-timeout"><?php esc_html_e( 'Timeout (seconds)', 'product-recommendation-quiz-for-ecommerce' ); ?></label></th>
						<td><input type="number" min="0" id="prq-auto-popup-timeout" name="<?php echo esc_attr( $option ); ?>[auto_popup][timeout]" value="<?php echo esc_attr( $auto_popup['timeout'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Exit intent', 'product-recommendation-quiz-for-ecommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[auto_popup][exit_intent]" value="1" <?php checked( $auto_popup['exit_intent'] ); ?> />
								<?php esc_html_e( 'Also open when the shopper moves to leave the page', 'product-recommendation-quiz-for-ecommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Aggressive', 'product-recommendation-quiz-for-ecommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[auto_popup][aggressive]" value="1" <?php checked( $auto_popup['aggressive'] ); ?> />
								<?php esc_html_e( 'Reopen more persistently', 'product-recommendation-quiz-for-ecommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="prq-auto-popup-width"><?php esc_html_e( 'Popup width (px)', 'product-recommendation-quiz-for-ecommerce' ); ?></label></th>
						<td><input type="number" min="0" id="prq-auto-popup-width" name="<?php echo esc_attr( $option ); ?>[auto_popup][popup_width]" value="<?php echo esc_attr( $auto_popup['popup_width'] ); ?>" /> <span class="description"><?php esc_html_e( 'Leave 0 for the default.', 'product-recommendation-quiz-for-ecommerce' ); ?></span></td>
					</tr>
					<tr>
						<th scope="row"><label for="prq-auto-popup-height"><?php esc_html_e( 'Popup height (px)', 'product-recommendation-quiz-for-ecommerce' ); ?></label></th>
						<td><input type="number" min="0" id="prq-auto-popup-height" name="<?php echo esc_attr( $option ); ?>[auto_popup][popup_height]" value="<?php echo esc_attr( $auto_popup['popup_height'] ); ?>" /> <span class="description"><?php esc_html_e( 'Leave 0 for the default.', 'product-recommendation-quiz-for-ecommerce' ); ?></span></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Chat button', 'product-recommendation-quiz-for-ecommerce' ); ?></h2>
				<p class="description"><?php esc_html_e( 'A floating button in the bottom-right corner that opens the quiz when clicked.', 'product-recommendation-quiz-for-ecommerce' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable chat button', 'product-recommendation-quiz-for-ecommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[chat][enabled]" value="1" <?php checked( $chat['enabled'] ); ?> />
								<?php esc_html_e( 'Show the chat button site-wide', 'product-recommendation-quiz-for-ecommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="prq-chat-quiz-id"><?php esc_html_e( 'Quiz ID', 'product-recommendation-quiz-for-ecommerce' ); ?></label></th>
						<td>
							<input type="text" id="prq-chat-quiz-id" class="regular-text" name="<?php echo esc_attr( $option ); ?>[chat][quiz_id]" value="<?php echo esc_attr( $chat['quiz_id'] ); ?>" />
							<p class="description"><?php esc_html_e( 'The code from your quiz Share link, e.g. the CODE in /public/quiz/CODE.', 'product-recommendation-quiz-for-ecommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="prq-chat-color"><?php esc_html_e( 'Button color', 'product-recommendation-quiz-for-ecommerce' ); ?></label></th>
						<td><input type="text" id="prq-chat-color" class="regular-text" name="<?php echo esc_attr( $option ); ?>[chat][color]" value="<?php echo esc_attr( $chat['color'] ); ?>" placeholder="#000000" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="prq-chat-greeting"><?php esc_html_e( 'Greeting text', 'product-recommendation-quiz-for-ecommerce' ); ?></label></th>
						<td><input type="text" id="prq-chat-greeting" class="regular-text" name="<?php echo esc_attr( $option ); ?>[chat][greeting]" value="<?php echo esc_attr( $chat['greeting'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Notification dot', 'product-recommendation-quiz-for-ecommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[chat][dot]" value="1" <?php checked( $chat['dot'] ); ?> />
								<?php esc_html_e( 'Show a notification dot on the button', 'product-recommendation-quiz-for-ecommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Hidden', 'product-recommendation-quiz-for-ecommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[chat][hide]" value="1" <?php checked( $chat['hide'] ); ?> />
								<?php esc_html_e( 'Keep the button hidden initially', 'product-recommendation-quiz-for-ecommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="prq-chat-width"><?php esc_html_e( 'Popup width (px)', 'product-recommendation-quiz-for-ecommerce' ); ?></label></th>
						<td><input type="number" min="0" id="prq-chat-width" name="<?php echo esc_attr( $option ); ?>[chat][popup_width]" value="<?php echo esc_attr( $chat['popup_width'] ); ?>" /> <span class="description"><?php esc_html_e( 'Leave 0 for the default.', 'product-recommendation-quiz-for-ecommerce' ); ?></span></td>
					</tr>
					<tr>
						<th scope="row"><label for="prq-chat-height"><?php esc_html_e( 'Popup height (px)', 'product-recommendation-quiz-for-ecommerce' ); ?></label></th>
						<td><input type="number" min="0" id="prq-chat-height" name="<?php echo esc_attr( $option ); ?>[chat][popup_height]" value="<?php echo esc_attr( $chat['popup_height'] ); ?>" /> <span class="description"><?php esc_html_e( 'Leave 0 for the default.', 'product-recommendation-quiz-for-ecommerce' ); ?></span></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Link popup', 'product-recommendation-quiz-for-ecommerce' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Any link on your store whose URL is "#quiz-CODE" (where CODE is your quiz ID) opens that quiz in a popup when clicked — no setting required. You can also add a Link Popup block from the editor.', 'product-recommendation-quiz-for-ecommerce' ); ?>
				</p>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
