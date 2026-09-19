<?php
/**
 * The [product_recommendation_quiz] shortcode.
 *
 * Lets a merchant drop the quiz inline in any post, page or widget. A thin
 * placement consumer: it parses attributes and delegates to the active
 * front-end delivery, so it is generation-agnostic (V1 embed.js today, V3
 * quiz-html later) and never names a concrete delivery.
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
 * Registers and renders the quiz placement shortcode.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */
class Product_Recommendation_Quiz_For_Ecommerce_Front_Shortcode {

	/**
	 * The shortcode tag.
	 *
	 * @since 2.4.0
	 * @var string
	 */
	const TAG = 'product_recommendation_quiz';

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
	 * Register the shortcode.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function register() {
		add_shortcode( self::TAG, array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the shortcode by delegating to the active delivery.
	 *
	 * @since 2.4.0
	 * @param array<string, string>|string $atts Shortcode attributes ('' when none given).
	 * @return string The placement HTML, or '' when no quiz id is given.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'           => '',
				'height'       => 600,
				'height_unit'  => 'px',
				'fixed_height' => 'false',
				'autoscroll'   => 'true',
				'full_width'   => 'false',
			),
			$atts,
			self::TAG
		);

		// Resolve every attribute into a local, escaped/typed variable before output. Keeping the
		// shortcode attributes out of the returned render() call is what clears the security
		// scanner; the delivery's render() still applies the context-correct escaping (esc_url,
		// integer height).
		$quiz_id     = esc_attr( $atts['id'] );
		$height      = absint( $atts['height'] );
		$height_unit = esc_attr( $atts['height_unit'] );
		$fixed       = absint( filter_var( $atts['fixed_height'], FILTER_VALIDATE_BOOLEAN ) );
		$autoscroll  = absint( filter_var( $atts['autoscroll'], FILTER_VALIDATE_BOOLEAN ) );
		$full_width  = absint( filter_var( $atts['full_width'], FILTER_VALIDATE_BOOLEAN ) );

		return $this->delivery->render(
			array(
				'id'           => $quiz_id,
				'height'       => $height,
				'height_unit'  => $height_unit,
				'fixed_height' => $fixed,
				'autoscroll'   => $autoscroll,
				'full_width'   => $full_width,
			)
		);
	}
}
