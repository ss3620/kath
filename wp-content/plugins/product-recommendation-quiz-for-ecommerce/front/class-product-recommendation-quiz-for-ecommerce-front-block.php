<?php
/**
 * The Product Recommendation Quiz Gutenberg block.
 *
 * A native block wrapper around the same placement seam the shortcode uses: a
 * dynamic (server-rendered) block whose render callback delegates to the active
 * delivery. The editor script is hand-authored against the global `wp.*`
 * packages — no JavaScript build step — so the repo stays toolchain-free.
 *
 * @link       https://revenuehunt.com/
 * @since      2.4.0
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers and renders the quiz placement block.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */
class Product_Recommendation_Quiz_For_Ecommerce_Front_Block {

	/**
	 * The editor script handle.
	 *
	 * @since 2.4.0
	 * @var string
	 */
	const EDITOR_HANDLE = 'product-recommendation-quiz-for-ecommerce-block-editor';

	/**
	 * The active front-end delivery.
	 *
	 * @since 2.4.0
	 * @var Product_Recommendation_Quiz_For_Ecommerce_Delivery
	 */
	private $delivery;

	/**
	 * Initialize with the active delivery.
	 *
	 * @since 2.4.0
	 * @param Product_Recommendation_Quiz_For_Ecommerce_Delivery $delivery The active delivery.
	 */
	public function __construct( Product_Recommendation_Quiz_For_Ecommerce_Delivery $delivery ) {
		$this->delivery = $delivery;
	}

	/**
	 * Register the editor script and the dynamic block.
	 *
	 * No-ops gracefully on WordPress versions without the block API.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			self::EDITOR_HANDLE,
			plugin_dir_url( __FILE__ ) . 'blocks/quiz/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			PRQ_PLUGIN_VERSION,
			true
		);

		// The editor preview iframes the hosted quiz; give it the backend origin
		// (respects the dev/prod split the core class resolves at runtime).
		$admin_origin = defined( 'PRQ_ADMIN_URL' ) ? constant( 'PRQ_ADMIN_URL' ) : 'https://admin.revenuehunt.com';
		wp_localize_script( self::EDITOR_HANDLE, 'prqQuizBlock', array( 'adminOrigin' => $admin_origin ) );

		register_block_type(
			plugin_dir_path( __FILE__ ) . 'blocks/quiz',
			array( 'render_callback' => array( $this, 'render_block' ) )
		);
	}

	/**
	 * Render the block by delegating to the active delivery.
	 *
	 * @since 2.4.0
	 * @param array<string, mixed> $attributes Block attributes ('id', 'height',
	 *                                         'heightUnit', 'fixedHeight', 'autoscroll',
	 *                                         'fullWidth').
	 * @return string The placement HTML, or '' when no quiz id is set.
	 */
	public function render_block( $attributes ) {
		// Attributes are sanitized here and output-escaped downstream in the delivery's render() (esc_url/integer height); semgrep cannot trace across the call, so the false positive is suppressed.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar -- next line is a machine-readable semgrep directive.
		// nosemgrep: audit.php.wp.security.xss.block-attr
		return $this->delivery->render(
			array(
				'id'           => isset( $attributes['id'] ) ? sanitize_text_field( (string) $attributes['id'] ) : '',
				'height'       => isset( $attributes['height'] ) ? absint( $attributes['height'] ) : 600,
				'height_unit'  => isset( $attributes['heightUnit'] ) ? sanitize_text_field( (string) $attributes['heightUnit'] ) : 'px',
				'fixed_height' => ! empty( $attributes['fixedHeight'] ),
				'autoscroll'   => ! isset( $attributes['autoscroll'] ) || (bool) $attributes['autoscroll'],
				'full_width'   => ! empty( $attributes['fullWidth'] ),
			)
		);
	}
}
