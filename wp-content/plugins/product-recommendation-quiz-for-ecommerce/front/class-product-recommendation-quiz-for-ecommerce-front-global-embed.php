<?php
/**
 * The site-wide app-embed injector (the WordPress "app embeds" surface).
 *
 * Hooked to `wp_footer`, this is the WordPress equivalent of Shopify's global
 * app-embed toggles: it reads the merchant's settings and, for each enabled
 * singleton mode (auto-popup, chat), echoes the embed.js marker once. It skips
 * cart/checkout to match the existing embed.js enqueue guard (a marker without
 * the script is inert there anyway).
 *
 * Precedence is automatic: server-rendered blocks run during the_content /
 * template rendering, both of which complete BEFORE the wp_footer action. So an
 * on-page auto-popup/chat block has already marked its mode in the registry by
 * the time this runs, and `*_once()` returns '' here — the on-page block wins.
 * Link-popup needs no footer output: embed.js already binds any `#quiz-CODE`
 * link wherever it loads.
 *
 * @link       https://revenuehunt.com/
 * @since      2.5.0
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Emits the enabled site-wide embed markers in the footer.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */
class Product_Recommendation_Quiz_For_Ecommerce_Front_Global_Embed {

	/**
	 * Echo the enabled singleton markers into the footer.
	 *
	 * Each marker is built (and dedup/precedence-guarded) by Embed_Markers, which
	 * escapes every attribute internally — so the echo is safe even though PHPCS
	 * cannot statically see the escaping through the builder.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function render() {
		// Match the embed.js enqueue guard: never on cart/checkout.
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return;
		}

		$settings = Product_Recommendation_Quiz_For_Ecommerce_Embeds_Settings::get();

		if ( ! empty( $settings['auto_popup']['enabled'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Embed_Markers::auto_popup escapes every attribute (esc_attr).
			echo Product_Recommendation_Quiz_For_Ecommerce_Embed_Markers::auto_popup_once( $settings['auto_popup'] );
		}

		if ( ! empty( $settings['chat']['enabled'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Embed_Markers::chat escapes every attribute (esc_attr).
			echo Product_Recommendation_Quiz_For_Ecommerce_Embed_Markers::chat_once( $settings['chat'] );
		}
	}
}
