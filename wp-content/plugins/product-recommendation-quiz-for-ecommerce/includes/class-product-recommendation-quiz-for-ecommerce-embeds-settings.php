<?php
/**
 * The app-embeds settings store.
 *
 * A single, dependency-free accessor for the site-wide app-embed configuration
 * (auto-popup + chat button) so the admin settings page and the front-end
 * injector read and write the exact same option keys — they can never drift.
 *
 * The whole configuration lives in one autoloaded option (`prq_embeds`). Every
 * read flows through `get()` (which sanitizes the stored value) and every write
 * flows through `sanitize()` (the registered Settings-API callback), so the
 * shape and types are guaranteed at both ends. Link-popup needs no settings —
 * it is documentation only (`embed.js` binds any `#quiz-CODE` link already).
 *
 * @link       https://revenuehunt.com/
 * @since      2.5.0
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/includes
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Reads, writes and sanitizes the app-embeds option.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/includes
 */
class Product_Recommendation_Quiz_For_Ecommerce_Embeds_Settings {

	/**
	 * The option name the whole configuration is stored under.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const OPTION = 'prq_embeds';

	/**
	 * The Settings-API option group the setting is registered in.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const GROUP = 'prq_embeds_group';

	/**
	 * The default configuration (everything disabled, contract defaults).
	 *
	 * @since 2.5.0
	 * @return array<string, array<string, mixed>> The default settings.
	 */
	public static function defaults() {
		return array(
			'auto_popup' => array(
				'enabled'      => false,
				'quiz_id'      => '',
				'timeout'      => 5,
				'exit_intent'  => false,
				'aggressive'   => false,
				'popup_width'  => 0,
				'popup_height' => 0,
			),
			'chat'       => array(
				'enabled'      => false,
				'quiz_id'      => '',
				'color'        => '',
				'dot'          => false,
				'hide'         => false,
				'greeting'     => '',
				'popup_width'  => 0,
				'popup_height' => 0,
			),
		);
	}

	/**
	 * Read the stored configuration, sanitized.
	 *
	 * Routing the stored value back through `sanitize()` makes the result
	 * shape-complete (every key present, correctly typed) regardless of what
	 * is actually in the database — including an option that predates a key or
	 * was written before this class existed. Idempotent: `sanitize(get())`
	 * equals `get()`.
	 *
	 * @since 2.5.0
	 * @return array<string, array<string, mixed>> The current settings.
	 */
	public static function get() {
		$stored = get_option( self::OPTION, array() );
		return self::sanitize( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Sanitize a raw settings array into the canonical shape.
	 *
	 * Reads only the known keys (unknown keys are dropped) and coerces each to
	 * its type: toggles to bool via `! empty()`, quiz codes / color / greeting
	 * through `sanitize_text_field()`, and timeout / widths / heights through
	 * `absint()`. This is the registered Settings-API `sanitize_callback`, so it
	 * runs on every admin save, and `get()` runs it on every read.
	 *
	 * @since 2.5.0
	 * @param mixed $input The raw value to sanitize (expected array; anything else
	 *                     is treated as empty).
	 * @return array<string, array<string, mixed>> The sanitized settings.
	 */
	public static function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();

		$ap   = ( isset( $input['auto_popup'] ) && is_array( $input['auto_popup'] ) ) ? $input['auto_popup'] : array();
		$chat = ( isset( $input['chat'] ) && is_array( $input['chat'] ) ) ? $input['chat'] : array();

		return array(
			'auto_popup' => array(
				'enabled'      => ! empty( $ap['enabled'] ),
				'quiz_id'      => isset( $ap['quiz_id'] ) ? sanitize_text_field( $ap['quiz_id'] ) : '',
				'timeout'      => isset( $ap['timeout'] ) ? absint( $ap['timeout'] ) : $defaults['auto_popup']['timeout'],
				'exit_intent'  => ! empty( $ap['exit_intent'] ),
				'aggressive'   => ! empty( $ap['aggressive'] ),
				'popup_width'  => isset( $ap['popup_width'] ) ? absint( $ap['popup_width'] ) : 0,
				'popup_height' => isset( $ap['popup_height'] ) ? absint( $ap['popup_height'] ) : 0,
			),
			'chat'       => array(
				'enabled'      => ! empty( $chat['enabled'] ),
				'quiz_id'      => isset( $chat['quiz_id'] ) ? sanitize_text_field( $chat['quiz_id'] ) : '',
				'color'        => isset( $chat['color'] ) ? sanitize_text_field( $chat['color'] ) : '',
				'dot'          => ! empty( $chat['dot'] ),
				'hide'         => ! empty( $chat['hide'] ),
				'greeting'     => isset( $chat['greeting'] ) ? sanitize_text_field( $chat['greeting'] ) : '',
				'popup_width'  => isset( $chat['popup_width'] ) ? absint( $chat['popup_width'] ) : 0,
				'popup_height' => isset( $chat['popup_height'] ) ? absint( $chat['popup_height'] ) : 0,
			),
		);
	}
}
