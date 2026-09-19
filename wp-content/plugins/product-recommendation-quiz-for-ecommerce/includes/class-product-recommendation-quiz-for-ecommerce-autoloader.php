<?php
/**
 * Self-contained class autoloader for the plugin.
 *
 * Maps each of the plugin's classes to its source file and loads it on first
 * use. The shipped artifact does NOT depend on Composer's vendor/ autoloader.
 *
 * Every `require_once` uses a fixed, literal path rooted at the plugin
 * directory — never a variable — so there is no dynamic file-inclusion surface
 * (the WooCommerce Marketplace / wp.org security audits flag variable include
 * arguments). Classes still load lazily: only the one being resolved is loaded.
 *
 * @link       https://revenuehunt.com/
 * @since      2.3.9
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/includes
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Static, literal-path autoloader for this plugin's classes.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/includes
 */
class Product_Recommendation_Quiz_For_Ecommerce_Autoloader {

	/**
	 * Register the autoloader with the SPL stack.
	 *
	 * @since 2.3.9
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Load one of the plugin's classes from its fixed source path.
	 *
	 * Only classes belonging to this plugin are handled; everything else is
	 * left to other registered autoloaders. Each branch requires a literal
	 * path (no variable include argument).
	 *
	 * @since 2.3.9
	 * @param string $class_name The fully-qualified class name being loaded.
	 * @return void
	 */
	public static function autoload( $class_name ) {
		switch ( $class_name ) {
			case 'Product_Recommendation_Quiz_For_Ecommerce':
				require_once plugin_dir_path( __DIR__ ) . 'includes/class-product-recommendation-quiz-for-ecommerce.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_I18n':
				require_once plugin_dir_path( __DIR__ ) . 'includes/class-product-recommendation-quiz-for-ecommerce-i18n.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Activator':
				require_once plugin_dir_path( __DIR__ ) . 'includes/class-product-recommendation-quiz-for-ecommerce-activator.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Deactivator':
				require_once plugin_dir_path( __DIR__ ) . 'includes/class-product-recommendation-quiz-for-ecommerce-deactivator.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Admin_Menu':
				require_once plugin_dir_path( __DIR__ ) . 'admin/class-product-recommendation-quiz-for-ecommerce-admin-menu.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Admin_Page':
				require_once plugin_dir_path( __DIR__ ) . 'admin/class-product-recommendation-quiz-for-ecommerce-admin-page.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Admin_Embeds_Page':
				require_once plugin_dir_path( __DIR__ ) . 'admin/class-product-recommendation-quiz-for-ecommerce-admin-embeds-page.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Embeds_Settings':
				require_once plugin_dir_path( __DIR__ ) . 'includes/class-product-recommendation-quiz-for-ecommerce-embeds-settings.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Admin_Diagnostics':
				require_once plugin_dir_path( __DIR__ ) . 'admin/class-product-recommendation-quiz-for-ecommerce-admin-diagnostics.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Site_Health':
				require_once plugin_dir_path( __DIR__ ) . 'admin/class-product-recommendation-quiz-for-ecommerce-site-health.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Admin_Oauth_Url_Builder':
				require_once plugin_dir_path( __DIR__ ) . 'admin/class-product-recommendation-quiz-for-ecommerce-admin-oauth-url-builder.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Admin_Operator_Capture':
				require_once plugin_dir_path( __DIR__ ) . 'admin/class-product-recommendation-quiz-for-ecommerce-admin-operator-capture.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Delivery':
				require_once plugin_dir_path( __DIR__ ) . 'front/interface-product-recommendation-quiz-for-ecommerce-delivery.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Front_Embed_Script':
				require_once plugin_dir_path( __DIR__ ) . 'front/class-product-recommendation-quiz-for-ecommerce-front-embed-script.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Embed_Markers':
				require_once plugin_dir_path( __DIR__ ) . 'front/class-product-recommendation-quiz-for-ecommerce-embed-markers.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Front_Global_Embed':
				require_once plugin_dir_path( __DIR__ ) . 'front/class-product-recommendation-quiz-for-ecommerce-front-global-embed.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Delivery_Resolver':
				require_once plugin_dir_path( __DIR__ ) . 'front/class-product-recommendation-quiz-for-ecommerce-delivery-resolver.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Front_Shortcode':
				require_once plugin_dir_path( __DIR__ ) . 'front/class-product-recommendation-quiz-for-ecommerce-front-shortcode.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Front_Block':
				require_once plugin_dir_path( __DIR__ ) . 'front/class-product-recommendation-quiz-for-ecommerce-front-block.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Front_Embed_Blocks':
				require_once plugin_dir_path( __DIR__ ) . 'front/class-product-recommendation-quiz-for-ecommerce-front-embed-blocks.php';
				break;
			case 'Product_Recommendation_Quiz_For_Ecommerce_Rest_Set_Token_Controller':
				require_once plugin_dir_path( __DIR__ ) . 'rest/class-product-recommendation-quiz-for-ecommerce-rest-set-token-controller.php';
				break;
		}
	}
}
