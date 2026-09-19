<?php
/**
 * The front-end delivery seam.
 *
 * Defines how a quiz is placed at a specific spot on a page, independent of the
 * generation that fulfils it. Phase 1 isolated "how the quiz reaches the
 * storefront" on the embed.js enqueuer; this interface formalizes that seam so
 * placement (shortcode / block) routes through ONE abstraction with
 * interchangeable implementations: V1 enqueues embed.js and returns the
 * hydration container; a future V3 returns a self-contained quiz-html artifact.
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
 * Contract for placing a quiz inline on a page.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */
interface Product_Recommendation_Quiz_For_Ecommerce_Delivery {

	/**
	 * Render the placement markup for one quiz and ensure its delivery asset
	 * is loaded exactly once on the page.
	 *
	 * @since 2.4.0
	 * @param array<string, mixed> $atts Placement attributes (e.g. 'id', 'height').
	 * @return string HTML to inject at the placement location, or '' when there
	 *                is nothing to render (e.g. no quiz id given).
	 */
	public function render( array $atts ): string;
}
