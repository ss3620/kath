<?php
/**
 * WordPress Site Health tests for the plugin's prerequisites.
 *
 * Surfaces the existing prerequisite checks (permalinks, SSL, problematic WPML,
 * REST API reachability) in the WordPress-standard Site Health screen, so
 * merchants and support see them where they expect. Thin wrappers over the
 * diagnostics unit — no new business rules. All registered as direct tests for
 * simplicity (an async/AJAX REST probe is a later refinement).
 *
 * @link       https://revenuehunt.com/
 * @since      2.4.0
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers the plugin's Site Health tests.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */
class Product_Recommendation_Quiz_For_Ecommerce_Site_Health {

	/**
	 * Prerequisite checks.
	 *
	 * @since 2.4.0
	 * @var Product_Recommendation_Quiz_For_Ecommerce_Admin_Diagnostics
	 */
	private $diagnostics;

	/**
	 * Initialize with the diagnostics unit.
	 *
	 * @since 2.4.0
	 * @param Product_Recommendation_Quiz_For_Ecommerce_Admin_Diagnostics|null $diagnostics Diagnostics helper.
	 */
	public function __construct( $diagnostics = null ) {
		$this->diagnostics = $diagnostics ? $diagnostics : new Product_Recommendation_Quiz_For_Ecommerce_Admin_Diagnostics();
	}

	/**
	 * Register the plugin's direct Site Health tests.
	 *
	 * @since 2.4.0
	 * @param array<string, array<string, mixed>> $tests The Site Health tests array.
	 * @return array<string, array<string, mixed>> The tests array with ours added.
	 */
	public function register_tests( $tests ) {
		$tests['direct']['prq_permalinks'] = array(
			'label' => __( 'Product Recommendation Quiz: permalink structure', 'product-recommendation-quiz-for-ecommerce' ),
			'test'  => array( $this, 'test_permalinks' ),
		);
		$tests['direct']['prq_ssl']        = array(
			'label' => __( 'Product Recommendation Quiz: HTTPS', 'product-recommendation-quiz-for-ecommerce' ),
			'test'  => array( $this, 'test_ssl' ),
		);
		$tests['direct']['prq_wpml']       = array(
			'label' => __( 'Product Recommendation Quiz: WPML compatibility', 'product-recommendation-quiz-for-ecommerce' ),
			'test'  => array( $this, 'test_wpml' ),
		);
		$tests['direct']['prq_rest_api']   = array(
			'label' => __( 'Product Recommendation Quiz: REST API reachability', 'product-recommendation-quiz-for-ecommerce' ),
			'test'  => array( $this, 'test_rest_api' ),
		);

		return $tests;
	}

	/**
	 * The Site Health badge shared by every test.
	 *
	 * @since 2.4.0
	 * @return array<string, string>
	 */
	private function badge() {
		return array(
			'label' => __( 'Product Recommendation Quiz', 'product-recommendation-quiz-for-ecommerce' ),
			'color' => 'blue',
		);
	}

	/**
	 * Test: the permalink structure is not "Plain".
	 *
	 * @since 2.4.0
	 * @return array<string, mixed>
	 */
	public function test_permalinks() {
		if ( $this->diagnostics->check_plain_permalink() ) {
			return array(
				'label'       => __( 'Your permalink structure is set to "Plain"', 'product-recommendation-quiz-for-ecommerce' ),
				'status'      => 'critical',
				'badge'       => $this->badge(),
				'description' => '<p>' . esc_html__( 'The Product Recommendation Quiz needs a permalink structure other than "Plain" to authenticate with your store. Change it under Settings > Permalinks.', 'product-recommendation-quiz-for-ecommerce' ) . '</p>',
				'actions'     => sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'options-permalink.php' ) ),
					esc_html__( 'Open Permalinks settings', 'product-recommendation-quiz-for-ecommerce' )
				),
				'test'        => 'prq_permalinks',
			);
		}

		return array(
			'label'       => __( 'Your permalink structure works with the quiz', 'product-recommendation-quiz-for-ecommerce' ),
			'status'      => 'good',
			'badge'       => $this->badge(),
			'description' => '<p>' . esc_html__( 'Your permalink structure is not set to "Plain", so the quiz can authenticate with your store.', 'product-recommendation-quiz-for-ecommerce' ) . '</p>',
			'test'        => 'prq_permalinks',
		);
	}

	/**
	 * Test: the site is served over HTTPS.
	 *
	 * @since 2.4.0
	 * @return array<string, mixed>
	 */
	public function test_ssl() {
		if ( is_ssl() ) {
			return array(
				'label'       => __( 'Your site is served over HTTPS', 'product-recommendation-quiz-for-ecommerce' ),
				'status'      => 'good',
				'badge'       => $this->badge(),
				'description' => '<p>' . esc_html__( 'A valid HTTPS connection is present, which the quiz requires.', 'product-recommendation-quiz-for-ecommerce' ) . '</p>',
				'test'        => 'prq_ssl',
			);
		}

		return array(
			'label'       => __( 'Your site is not served over HTTPS', 'product-recommendation-quiz-for-ecommerce' ),
			'status'      => 'critical',
			'badge'       => $this->badge(),
			'description' => '<p>' . esc_html__( 'The Product Recommendation Quiz requires a valid HTTPS/SSL certificate to work.', 'product-recommendation-quiz-for-ecommerce' ) . '</p>',
			'test'        => 'prq_ssl',
		);
	}

	/**
	 * Test: no WPML version that breaks WooCommerce authentication is active.
	 *
	 * @since 2.4.0
	 * @return array<string, mixed>
	 */
	public function test_wpml() {
		if ( $this->diagnostics->is_wpml_problematic() ) {
			return array(
				'label'       => __( 'A WPML version that breaks authentication is active', 'product-recommendation-quiz-for-ecommerce' ),
				'status'      => 'critical',
				'badge'       => $this->badge(),
				'description' => '<p>' . esc_html__( 'WPML versions older than 4.5.0 interfere with WooCommerce authentication. Update WPML to 4.5.0 or later.', 'product-recommendation-quiz-for-ecommerce' ) . '</p>',
				'test'        => 'prq_wpml',
			);
		}

		return array(
			'label'       => __( 'No conflicting WPML version detected', 'product-recommendation-quiz-for-ecommerce' ),
			'status'      => 'good',
			'badge'       => $this->badge(),
			'description' => '<p>' . esc_html__( 'No WPML version known to interfere with authentication is active.', 'product-recommendation-quiz-for-ecommerce' ) . '</p>',
			'test'        => 'prq_wpml',
		);
	}

	/**
	 * Test: this store's REST API is reachable from the RevenueHunt server.
	 *
	 * @since 2.4.0
	 * @return array<string, mixed>
	 */
	public function test_rest_api() {
		$host   = wp_parse_url( site_url(), PHP_URL_HOST );
		$domain = is_string( $host ) ? $host : '';
		$result = $this->diagnostics->api_check_json( $domain );
		$code   = isset( $result[0] ) ? (int) $result[0] : 0;

		if ( 200 === $code ) {
			return array(
				'label'       => __( 'Your store REST API is reachable', 'product-recommendation-quiz-for-ecommerce' ),
				'status'      => 'good',
				'badge'       => $this->badge(),
				'description' => '<p>' . esc_html__( 'The RevenueHunt server can reach your WooCommerce REST API.', 'product-recommendation-quiz-for-ecommerce' ) . '</p>',
				'test'        => 'prq_rest_api',
			);
		}

		return array(
			'label'       => __( 'Your store REST API could not be reached', 'product-recommendation-quiz-for-ecommerce' ),
			'status'      => 'critical',
			'badge'       => $this->badge(),
			'description' => '<p>' . esc_html__( 'The RevenueHunt server could not reach your WooCommerce REST API, or it returned an unexpected response (a security plugin, a wrong content type, or a blocked request can cause this). The quiz cannot authenticate until this is resolved.', 'product-recommendation-quiz-for-ecommerce' ) . '</p>',
			'test'        => 'prq_rest_api',
		);
	}
}
