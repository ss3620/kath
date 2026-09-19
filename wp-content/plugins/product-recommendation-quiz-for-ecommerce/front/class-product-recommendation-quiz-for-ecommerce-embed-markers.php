<?php
/**
 * The embed.js marker builder + per-request singleton registry.
 *
 * The three remaining delivery modes (auto-popup, chat, link-popup) are
 * satisfied purely by emitting the small marker elements the already-loaded,
 * centrally-hosted embed.js hydrates — there is NO backend or embed.js change.
 * This class is the single place those markers are built, so the site-wide
 * footer injector and the placement blocks emit byte-identical HTML.
 *
 * It also holds the per-request singleton registry. Auto-popup and chat are
 * singletons (embed.js finds them by `id`), so at most one of each may appear
 * in the final document. `*_once()` builds-and-marks under that registry, which
 * is the single mechanism behind both dedup (a second block emits nothing) and
 * the precedence rule (an on-page block renders before wp_footer, so the footer
 * injector sees the flag and steps aside).
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
 * Builds embed.js marker HTML and tracks once-per-request singletons.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */
class Product_Recommendation_Quiz_For_Ecommerce_Embed_Markers {

	/**
	 * Registry key for the auto-popup singleton.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const MODE_AUTO_POPUP = 'auto_popup';

	/**
	 * Registry key for the chat singleton.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const MODE_CHAT = 'chat';

	/**
	 * Modes already emitted this request.
	 *
	 * Per-request static state, reset naturally each request (and explicitly via
	 * reset() in tests). Safe under full-page caches: each cached page already
	 * contains the resolved marker, so there is no cross-request leakage.
	 *
	 * @since 2.5.0
	 * @var array<string, bool>
	 */
	private static $rendered = array();

	/**
	 * Build the auto-popup marker.
	 *
	 * Emits `<div id="auto-popup" …>` with every attribute embed.js reads, in the
	 * verified order: quiz id, timeout (seconds), exit-intent and aggressive as
	 * `"true"`/`"false"`, and the optional popup width/height (omitted when 0).
	 * Returns '' when no quiz id is given (an empty marker would be inert anyway,
	 * and — crucially — must NOT suppress a valid site-wide embed: see
	 * auto_popup_once()).
	 *
	 * @since 2.5.0
	 * @param array<string, mixed> $settings Auto-popup settings (quiz_id, timeout,
	 *                                       exit_intent, aggressive, popup_width,
	 *                                       popup_height).
	 * @return string The marker HTML, or '' when no quiz id is set.
	 */
	public static function auto_popup( array $settings ) {
		$quiz_id = isset( $settings['quiz_id'] ) ? sanitize_text_field( (string) $settings['quiz_id'] ) : '';
		if ( '' === $quiz_id ) {
			return '';
		}

		$timeout     = isset( $settings['timeout'] ) ? absint( $settings['timeout'] ) : 0;
		$exit_intent = empty( $settings['exit_intent'] ) ? 'false' : 'true';
		$aggressive  = empty( $settings['aggressive'] ) ? 'false' : 'true';

		$html  = '<div id="auto-popup"';
		$html .= ' data-quiz-id="' . esc_attr( $quiz_id ) . '"';
		$html .= ' data-timeout="' . esc_attr( (string) $timeout ) . '"';
		$html .= ' data-exit-intent="' . $exit_intent . '"';
		$html .= ' data-aggressive="' . $aggressive . '"';
		$html .= self::dimension_attr( 'data-popup-width', isset( $settings['popup_width'] ) ? $settings['popup_width'] : 0 );
		$html .= self::dimension_attr( 'data-popup-height', isset( $settings['popup_height'] ) ? $settings['popup_height'] : 0 );
		$html .= '></div>';

		return $html;
	}

	/**
	 * Build the chat-button marker.
	 *
	 * Emits `<div id="rh-chat" …>` in the verified order: quiz id, optional color,
	 * dot and hide as `"true"`/`"false"`, optional greeting, optional popup
	 * width/height. embed.js builds the fixed-position button itself, so the
	 * marker's DOM position is irrelevant. Returns '' when no quiz id is given.
	 *
	 * @since 2.5.0
	 * @param array<string, mixed> $settings Chat settings (quiz_id, color, dot,
	 *                                       hide, greeting, popup_width, popup_height).
	 * @return string The marker HTML, or '' when no quiz id is set.
	 */
	public static function chat( array $settings ) {
		$quiz_id = isset( $settings['quiz_id'] ) ? sanitize_text_field( (string) $settings['quiz_id'] ) : '';
		if ( '' === $quiz_id ) {
			return '';
		}

		$color    = isset( $settings['color'] ) ? sanitize_text_field( (string) $settings['color'] ) : '';
		$greeting = isset( $settings['greeting'] ) ? sanitize_text_field( (string) $settings['greeting'] ) : '';
		$dot      = empty( $settings['dot'] ) ? 'false' : 'true';
		$hide     = empty( $settings['hide'] ) ? 'false' : 'true';

		$html  = '<div id="rh-chat"';
		$html .= ' data-quiz-id="' . esc_attr( $quiz_id ) . '"';
		if ( '' !== $color ) {
			$html .= ' data-chat-color="' . esc_attr( $color ) . '"';
		}
		$html .= ' data-chat-dot="' . $dot . '"';
		$html .= ' data-chat-hide="' . $hide . '"';
		if ( '' !== $greeting ) {
			$html .= ' data-chat-greeting="' . esc_attr( $greeting ) . '"';
		}
		$html .= self::dimension_attr( 'data-popup-width', isset( $settings['popup_width'] ) ? $settings['popup_width'] : 0 );
		$html .= self::dimension_attr( 'data-popup-height', isset( $settings['popup_height'] ) ? $settings['popup_height'] : 0 );
		$html .= '></div>';

		return $html;
	}

	/**
	 * Build the link-popup href for a quiz code.
	 *
	 * Returns `#quiz-CODE`; embed.js binds any `<a>` whose href contains `#quiz-`
	 * and opens the code after it. There is no container and no registry
	 * interaction (link-popup is not a singleton).
	 *
	 * @since 2.5.0
	 * @param string $code The quiz code.
	 * @return string `#quiz-CODE`, or '' when no code is given.
	 */
	public static function link_href( $code ) {
		$code = sanitize_text_field( (string) $code );
		if ( '' === $code ) {
			return '';
		}
		return '#quiz-' . $code;
	}

	/**
	 * Build the auto-popup marker once per request.
	 *
	 * Guards on the registry (returns '' if already rendered → dedup + the footer
	 * stepping aside for an on-page block), builds the marker, and marks the mode
	 * rendered ONLY when the output is non-empty — so an empty/invalid block does
	 * not suppress a valid site-wide embed.
	 *
	 * @since 2.5.0
	 * @param array<string, mixed> $settings Auto-popup settings.
	 * @return string The marker HTML the first time, '' thereafter (or when empty).
	 */
	public static function auto_popup_once( array $settings ) {
		if ( self::was_rendered( self::MODE_AUTO_POPUP ) ) {
			return '';
		}
		$html = self::auto_popup( $settings );
		if ( '' === $html ) {
			return '';
		}
		self::mark_rendered( self::MODE_AUTO_POPUP );
		return $html;
	}

	/**
	 * Build the chat marker once per request. See auto_popup_once().
	 *
	 * @since 2.5.0
	 * @param array<string, mixed> $settings Chat settings.
	 * @return string The marker HTML the first time, '' thereafter (or when empty).
	 */
	public static function chat_once( array $settings ) {
		if ( self::was_rendered( self::MODE_CHAT ) ) {
			return '';
		}
		$html = self::chat( $settings );
		if ( '' === $html ) {
			return '';
		}
		self::mark_rendered( self::MODE_CHAT );
		return $html;
	}

	/**
	 * Mark a singleton mode as rendered this request.
	 *
	 * @since 2.5.0
	 * @param string $mode One of the MODE_* constants.
	 * @return void
	 */
	public static function mark_rendered( $mode ) {
		self::$rendered[ $mode ] = true;
	}

	/**
	 * Whether a singleton mode has already been rendered this request.
	 *
	 * @since 2.5.0
	 * @param string $mode One of the MODE_* constants.
	 * @return bool
	 */
	public static function was_rendered( $mode ) {
		return ! empty( self::$rendered[ $mode ] );
	}

	/**
	 * Clear the registry (test isolation; also resets naturally per request).
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public static function reset() {
		self::$rendered = array();
	}

	/**
	 * Build ` name="N"` for a numeric dimension, or '' when it is 0/empty.
	 *
	 * @since 2.5.0
	 * @param string $name  The attribute name.
	 * @param mixed  $value The raw dimension value.
	 * @return string The attribute fragment (leading space) or ''.
	 */
	private static function dimension_attr( $name, $value ) {
		$value = absint( $value );
		if ( 0 === $value ) {
			return '';
		}
		return ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
	}
}
