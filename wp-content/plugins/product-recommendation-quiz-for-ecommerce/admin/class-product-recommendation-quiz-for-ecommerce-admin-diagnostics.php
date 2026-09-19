<?php
/**
 * Admin diagnostics: prerequisite checks and the merchant-facing error screens.
 *
 * Owns the connectivity / environment preconditions that must hold before the
 * OAuth flow can run, plus the error notices shown when one fails. Kept as a
 * focused, generation-agnostic unit (the V3 plugin needs the same class of
 * status/diagnostics surface).
 *
 * @link       https://revenuehunt.com/
 * @since      2.3.9
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Prerequisite checks and diagnostic error screens for the admin page.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */
class Product_Recommendation_Quiz_For_Ecommerce_Admin_Diagnostics {

	/**
	 * Tags allowed when echoing a translated sentence that embeds a link.
	 *
	 * Translatable sentences are kept whole (one msgid) and the anchor is
	 * injected via a sprintf() placeholder, then sanitized for output with
	 * wp_kses(). Only the anchor and its safe attributes are permitted.
	 *
	 * @since 2.3.10
	 * @var array<string, array<string, array<empty, empty>>>
	 */
	const ALLOWED_LINK_HTML = array(
		'a' => array(
			'href'   => array(),
			'target' => array(),
			'rel'    => array(),
		),
	);

	/**
	 * Check if permalinks are set to plain structure.
	 *
	 * @since 1.0.0
	 * @return bool True if plain permalinks, false otherwise.
	 */
	public function check_plain_permalink() {
		$permalink_structure = get_option( 'permalink_structure' );
		return empty( $permalink_structure );
	}

	/**
	 * Display WooCommerce missing error.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function woocommerce_missing() {
		$woocommerce_link = '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'WooCommerce', 'product-recommendation-quiz-for-ecommerce' ) . '</a>';
		?>
		<div class="error">
			<p><strong>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: link to the WooCommerce plugin on wordpress.org (anchor text "WooCommerce"). */
					__( 'Product Recommendation Quiz for eCommerce requires the WooCommerce plugin to be installed and active. You can download %s here. If you want this plugin developed for your eCommerce platform, please send us a message.', 'product-recommendation-quiz-for-ecommerce' ),
					$woocommerce_link
				),
				self::ALLOWED_LINK_HTML
			);
			?>
			</strong></p>
		</div>
		<?php
	}

	/**
	 * Display HTTPS/SSL missing error.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function https_ssl_missing() {
		?>
		<div class="error">
			<p><strong><?php esc_html_e( 'Product Recommendation Quiz for eCommerce requires your website to have a valid HTTPS/SSL certificate.', 'product-recommendation-quiz-for-ecommerce' ); ?></strong></p>
		</div>
		<?php
	}

	/**
	 * Display localhost error.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function is_localhost() {
		?>
		<div class="error">
			<p><strong><?php esc_html_e( 'This plugin does not work on local environments. It needs to be installed on a live website. Your website needs to be public and not hidden by a site under construction plugin because it needs connection to our server in order to work.', 'product-recommendation-quiz-for-ecommerce' ); ?></strong></p>
		</div>
		<?php
	}

	/**
	 * Display plain permalink warning.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function plain_permalink_warning() {
		?>
		<?php
		$permalinks_link = '<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">'
			. esc_html__( 'Settings > Permalinks', 'product-recommendation-quiz-for-ecommerce' ) . '</a>';
		?>
		<div class="error">
			<p><strong><?php esc_html_e( 'Your current permalink structure is set to "Plain". For this plugin to authenticate correctly, a different permalink structure (such as "Post name") is required.', 'product-recommendation-quiz-for-ecommerce' ); ?></strong></p>
			<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: link to the WordPress "Settings > Permalinks" screen. */
					__( 'Please update your permalink settings under %s to ensure seamless authentication.', 'product-recommendation-quiz-for-ecommerce' ),
					$permalinks_link
				),
				self::ALLOWED_LINK_HTML
			);
			?>
			</p>
		</div>
		<?php
	}

	/**
	 * Display WPML compatibility warning.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wpml_active() {
		?>
		<?php
		$more_info_link = '<a href="https://revenuehunt.com/faqs/woocommerce-authentication-error-404-not-found-missing-parameter-app-name/" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'here', 'product-recommendation-quiz-for-ecommerce' ) . '</a>';
		?>
		<div class="error">
			<p><strong>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: link to the troubleshooting FAQ (anchor text "here"). */
					__( 'There\'s an issue with the WPML Multilingual CMS plugin which interferes with the authentication process of other plugins. Please deactivate the WPML Multilingual CMS plugin temporarily, you can reactivate it later. More info %s.', 'product-recommendation-quiz-for-ecommerce' ),
					$more_info_link
				),
				self::ALLOWED_LINK_HTML
			);
			?>
			</strong></p>
		</div>
		<?php
	}

	/**
	 * Display WordPress REST API error.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wp_json_error() {
		?>
		<?php
		$rest_api_link  = '<a href="https://developer.wordpress.org/rest-api/" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'WordPress REST API', 'product-recommendation-quiz-for-ecommerce' ) . '</a>';
		$more_info_link = '<a href="https://revenuehunt.com/faqs/woocommerce-authentication-error-404-not-found-missing-parameter-app-name/" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'here', 'product-recommendation-quiz-for-ecommerce' ) . '</a>';
		?>
		<div class="error">
			<p><strong>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: link to the WordPress REST API docs (anchor "WordPress REST API"); 2: link to the troubleshooting FAQ (anchor "here"). */
					__( 'It seems like there\'s something interfering with your %1$s. This needs to be fixed in order to grant access to this plugin. More info %2$s. We\'re getting the following error accessing your WooCommerce API from our server:', 'product-recommendation-quiz-for-ecommerce' ),
					$rest_api_link,
					$more_info_link
				),
				self::ALLOWED_LINK_HTML
			);
			?>
			</strong></p>
		</div>
		<?php
	}

	/**
	 * Display error when REST API returns HTML content-type.
	 *
	 * @since 1.0.0
	 * @param string $domain The store domain.
	 * @return void
	 */
	public function wp_json_error_html_content_type( $domain ) {
		?>
		<div class="error">
			<p><strong>
				<?php esc_html_e( 'The following REST API endpoint is returning a valid JSON but the returned content-type is text/html instead of the expected application/json:', 'product-recommendation-quiz-for-ecommerce' ); ?>
				<a href="<?php echo esc_url( 'https://' . $domain . '/wp-json/wc/v3/' ); ?>" target="_blank" rel="noopener noreferrer">https://<?php echo esc_html( $domain ); ?>/wp-json/wc/v3/</a>
			</strong></p>
		</div>
		<?php
	}

	/**
	 * Display REST API error response body.
	 *
	 * @since 1.0.0
	 * @param string $wp_api_check_body The API response body to display.
	 * @return void
	 */
	public function wp_json_error_body( $wp_api_check_body ) {
		?>
		<div class="error">
			<p><strong><?php echo esc_html( wp_strip_all_tags( $wp_api_check_body ) ); ?></strong></p>
		</div>
		<?php
	}

	/**
	 * Display domain migration warning.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function migration_warning() {
		?>
		<?php
		$contact_link = '<a href="https://revenuehunt.com/contact/" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'contact us', 'product-recommendation-quiz-for-ecommerce' ) . '</a>';
		?>
		<div class="error">
			<p><strong>
			<?php
			printf(
				/* translators: 1: previous store domain; 2: new store domain. */
				esc_html__( 'We\'ve detected that you\'ve changed the domain name. We\'re migrating your Product Recommendation Quiz account from %1$s to %2$s', 'product-recommendation-quiz-for-ecommerce' ),
				esc_html( get_option( PRQ_OPTION_DOMAIN ) ),
				esc_html( PRQ_STORE_URL )
			);
			?>
			</strong></p>
			<p><strong>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: link to the RevenueHunt contact page (anchor "contact us"). */
					__( 'Please %s if you encounter any issues.', 'product-recommendation-quiz-for-ecommerce' ),
					$contact_link
				),
				self::ALLOWED_LINK_HTML
			);
			?>
			</strong></p>
		</div>
		<?php
	}

	/**
	 * Check if a string is valid JSON.
	 *
	 * @since 1.0.0
	 * @param string $string The string to check.
	 * @return bool True if valid JSON, false otherwise.
	 */
	public function is_json( $string ) {
		json_decode( $string );
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Check WooCommerce REST API accessibility from RevenueHunt server.
	 *
	 * Makes a request to the RevenueHunt API to verify that this store's
	 * WooCommerce REST API is accessible from the outside.
	 *
	 * @since 1.0.0
	 * @param string $domain The store domain to check.
	 * @return array Tuple [HTTP code, response body string]. HTTP code is 0 on transport error.
	 */
	public function api_check_json( $domain ) {
		$url  = 'https://api.revenuehunt.com/api/v1/woocommerce/check?domain=' . rawurlencode( $domain );
		$args = array(
			'timeout'     => 10,
			'redirection' => 5,
		);
		// Identify with a fixed plugin user-agent rather than reflecting the
		// visitor's User-Agent header to our API.
		$args['user-agent'] = 'ProductRecommendationQuiz/' . PRQ_PLUGIN_VERSION;

		// Force IPv4 for this request to mirror legacy curl behavior on hosts with broken IPv6.
		$ipv4_filter = function ( $handle ) {
			if ( defined( 'CURLOPT_IPRESOLVE' ) && defined( 'CURL_IPRESOLVE_V4' ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Setting CURLOPT_IPRESOLVE is only possible via the http_api_curl hook; WP's HTTP API exposes no option to force IPv4.
				curl_setopt( $handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
			}
			return $handle;
		};
		add_action( 'http_api_curl', $ipv4_filter );
		$response = wp_remote_post( $url, $args );
		remove_action( 'http_api_curl', $ipv4_filter );

		if ( is_wp_error( $response ) ) {
			return array( 0, '' );
		}

		return array(
			(int) wp_remote_retrieve_response_code( $response ),
			wp_remote_retrieve_body( $response ),
		);
	}

	/**
	 * Whether a problematic (pre-4.5.0) WPML version is active.
	 *
	 * WPML versions below 4.5.0 have a known issue that interferes with
	 * WooCommerce authentication. Pure predicate (no output), reused by both the
	 * OAuth-flow gate and the Site Health test.
	 *
	 * @since 2.4.0
	 * @return bool True if a problematic WPML version is active, false otherwise.
	 */
	public function is_wpml_problematic() {
		if ( ! function_exists( 'icl_object_id' ) ) {
			return false;
		}

		// WPML 4.5.0+ fixed the endpoint-encoding issue that breaks authentication.
		// See https://wpml.org/errata/endpoints-containing-slashes-are-incorrectly-encoded/.
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '4.5.0', '>=' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check for problematic WPML versions, warning the merchant if found.
	 *
	 * @since 1.0.0
	 * @return bool True if problematic WPML detected (should block), false otherwise.
	 */
	public function check_wpml() {
		if ( $this->is_wpml_problematic() ) {
			$this->wpml_active();
			return true;
		}
		return false;
	}
}
