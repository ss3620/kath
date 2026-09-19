<?php
/**
 * Selects the active front-end delivery implementation.
 *
 * The single place that decides which delivery fulfils placement. Today there
 * is one implementation (V1 embed.js); when the V3 plugin lands this is where
 * the choice branches on the active connection / configuration, so placement
 * consumers (shortcode, block) never name a concrete delivery.
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
 * Resolves the front-end delivery seam to its active implementation.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */
class Product_Recommendation_Quiz_For_Ecommerce_Delivery_Resolver {

	/**
	 * Return the active delivery implementation.
	 *
	 * @since 2.4.0
	 * @param string $plugin_name The plugin/script handle delivery should use.
	 * @param string $version     The plugin version.
	 * @return Product_Recommendation_Quiz_For_Ecommerce_Delivery The active delivery.
	 */
	public static function resolve( $plugin_name, $version ): Product_Recommendation_Quiz_For_Ecommerce_Delivery {
		// V1: enqueue embed.js and return the hydration container. The future V3
		// delivery (self-contained quiz-html) is selected here when available.
		return new Product_Recommendation_Quiz_For_Ecommerce_Front_Embed_Script( $plugin_name, $version );
	}
}
