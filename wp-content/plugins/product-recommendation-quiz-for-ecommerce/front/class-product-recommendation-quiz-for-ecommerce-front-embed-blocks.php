<?php
/**
 * The three placement blocks: auto-popup, chat-button, link-popup.
 *
 * The WordPress "app blocks" surface — the equivalent of Shopify's section
 * picker entries for the non-inline delivery modes. Each is a dynamic
 * (server-rendered) block following the existing hand-authored block.json +
 * plain editor.js pattern (no JSX/build step); the render callbacks delegate to
 * Embed_Markers so block placements emit the exact same markers as the
 * site-wide footer.
 *
 * auto-popup and chat render through `*_once()` so they set the singleton
 * registry — which both dedups a second block on the page and makes the footer
 * injector step aside (on-page block wins). link-popup is not a singleton: it
 * simply emits an `<a href="#quiz-CODE">` that embed.js binds.
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
 * Registers and renders the auto-popup / chat-button / link-popup blocks.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */
class Product_Recommendation_Quiz_For_Ecommerce_Front_Embed_Blocks {

	/**
	 * The shared editor script handle (registers all three blocks).
	 *
	 * @since 2.5.0
	 * @var string
	 */
	const EDITOR_HANDLE = 'product-recommendation-quiz-for-ecommerce-embed-blocks-editor';

	/**
	 * Register the shared editor script and the three dynamic blocks.
	 *
	 * No-ops gracefully on WordPress versions without the block API.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			self::EDITOR_HANDLE,
			plugin_dir_url( __FILE__ ) . 'blocks/embed-blocks-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			PRQ_PLUGIN_VERSION,
			true
		);

		register_block_type(
			plugin_dir_path( __FILE__ ) . 'blocks/auto-popup',
			array( 'render_callback' => array( $this, 'render_auto_popup' ) )
		);
		register_block_type(
			plugin_dir_path( __FILE__ ) . 'blocks/chat-button',
			array( 'render_callback' => array( $this, 'render_chat_button' ) )
		);
		register_block_type(
			plugin_dir_path( __FILE__ ) . 'blocks/link-popup',
			array( 'render_callback' => array( $this, 'render_link_popup' ) )
		);
	}

	/**
	 * Render the auto-popup block: the `#auto-popup` marker, dedup-guarded.
	 *
	 * @since 2.5.0
	 * @param array<string, mixed> $attributes Block attributes (quizId, timeout,
	 *                                         exitIntent, aggressive, popupWidth,
	 *                                         popupHeight).
	 * @return string The marker HTML, or '' (no quiz id / already rendered).
	 */
	public function render_auto_popup( $attributes ) {
		// Attributes are sanitized here and output-escaped downstream in Embed_Markers (esc_attr/absint); semgrep cannot trace across the call, so the false positive is suppressed.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- next line is a machine-readable semgrep directive.
		// nosemgrep: audit.php.wp.security.xss.block-attr
		return Product_Recommendation_Quiz_For_Ecommerce_Embed_Markers::auto_popup_once(
			array(
				'quiz_id'      => isset( $attributes['quizId'] ) ? sanitize_text_field( (string) $attributes['quizId'] ) : '',
				'timeout'      => isset( $attributes['timeout'] ) ? absint( $attributes['timeout'] ) : 0,
				'exit_intent'  => ! empty( $attributes['exitIntent'] ),
				'aggressive'   => ! empty( $attributes['aggressive'] ),
				'popup_width'  => isset( $attributes['popupWidth'] ) ? absint( $attributes['popupWidth'] ) : 0,
				'popup_height' => isset( $attributes['popupHeight'] ) ? absint( $attributes['popupHeight'] ) : 0,
			)
		);
	}

	/**
	 * Render the chat-button block: the `#rh-chat` marker, dedup-guarded.
	 *
	 * @since 2.5.0
	 * @param array<string, mixed> $attributes Block attributes (quizId, color, dot,
	 *                                         hide, greeting, popupWidth, popupHeight).
	 * @return string The marker HTML, or '' (no quiz id / already rendered).
	 */
	public function render_chat_button( $attributes ) {
		// Attributes are sanitized here and output-escaped downstream in Embed_Markers (esc_attr/absint); semgrep cannot trace across the call, so the false positive is suppressed.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- next line is a machine-readable semgrep directive.
		// nosemgrep: audit.php.wp.security.xss.block-attr
		return Product_Recommendation_Quiz_For_Ecommerce_Embed_Markers::chat_once(
			array(
				'quiz_id'      => isset( $attributes['quizId'] ) ? sanitize_text_field( (string) $attributes['quizId'] ) : '',
				'color'        => isset( $attributes['color'] ) ? sanitize_text_field( (string) $attributes['color'] ) : '',
				'dot'          => ! empty( $attributes['dot'] ),
				'hide'         => ! empty( $attributes['hide'] ),
				'greeting'     => isset( $attributes['greeting'] ) ? sanitize_text_field( (string) $attributes['greeting'] ) : '',
				'popup_width'  => isset( $attributes['popupWidth'] ) ? absint( $attributes['popupWidth'] ) : 0,
				'popup_height' => isset( $attributes['popupHeight'] ) ? absint( $attributes['popupHeight'] ) : 0,
			)
		);
	}

	/**
	 * Render the link-popup block: an `<a href="#quiz-CODE">` embed.js binds.
	 *
	 * Not a singleton — no registry interaction. Returns '' when no quiz id is set.
	 *
	 * @since 2.5.0
	 * @param array<string, mixed> $attributes Block attributes (quizId, label).
	 * @return string The anchor HTML, or '' when no quiz id is set.
	 */
	public function render_link_popup( $attributes ) {
		$code = isset( $attributes['quizId'] ) ? $attributes['quizId'] : '';
		$href = Product_Recommendation_Quiz_For_Ecommerce_Embed_Markers::link_href( $code );
		if ( '' === $href ) {
			return '';
		}

		$label = ( isset( $attributes['label'] ) && '' !== trim( (string) $attributes['label'] ) )
			? $attributes['label']
			: __( 'Take the quiz', 'product-recommendation-quiz-for-ecommerce' );

		return sprintf(
			'<div class="wp-block-button prq-link-popup"><a class="wp-block-button__link" href="%s">%s</a></div>',
			esc_url( $href ),
			esc_html( $label )
		);
	}
}
