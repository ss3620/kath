<?php
/**
 * The plugin's admin settings page.
 *
 * Renders the connected (iframe) and first-visit (authorize) views and
 * orchestrates the prerequisite checks before either is shown. Collaborates
 * with the diagnostics unit (prerequisite checks / error screens) and the
 * connection-layer URL builder (OAuth handshake URLs).
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
 * Renders the admin settings page and orchestrates its prerequisite checks.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */
class Product_Recommendation_Quiz_For_Ecommerce_Admin_Page {

	/**
	 * Tags allowed when echoing a translated sentence that embeds a link.
	 *
	 * The sentence stays one msgid; the anchor is injected via a sprintf()
	 * placeholder and sanitized for output with wp_kses().
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
	 * Prerequisite checks and diagnostic error screens.
	 *
	 * @since    2.3.9
	 * @var      Product_Recommendation_Quiz_For_Ecommerce_Admin_Diagnostics    $diagnostics    Diagnostics helper.
	 */
	private $diagnostics;

	/**
	 * Connection-layer URL builder (WooCommerce OAuth handshake).
	 *
	 * @since    2.3.9
	 * @var      Product_Recommendation_Quiz_For_Ecommerce_Admin_Oauth_Url_Builder    $oauth_url_builder    URL builder.
	 */
	private $oauth_url_builder;

	/**
	 * Initialize the page with its collaborators.
	 *
	 * @since 2.3.9
	 * @param Product_Recommendation_Quiz_For_Ecommerce_Admin_Diagnostics|null       $diagnostics       Diagnostics helper.
	 * @param Product_Recommendation_Quiz_For_Ecommerce_Admin_Oauth_Url_Builder|null $oauth_url_builder URL builder.
	 */
	public function __construct( $diagnostics = null, $oauth_url_builder = null ) {
		$this->diagnostics       = $diagnostics ? $diagnostics : new Product_Recommendation_Quiz_For_Ecommerce_Admin_Diagnostics();
		$this->oauth_url_builder = $oauth_url_builder ? $oauth_url_builder : new Product_Recommendation_Quiz_For_Ecommerce_Admin_Oauth_Url_Builder();
	}

	/**
	 * Display the authenticated admin panel with iframe.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prquiz_authenticated_visit() {
		?>
		<div class="wrap">
			<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'img/revenuehunt-logo.png' ); ?>" width="24" height="24" alt="<?php esc_attr_e( 'RevenueHunt', 'product-recommendation-quiz-for-ecommerce' ); ?>" />
			<p class="fright h-24 mtop-0 prq-author">
				<?php esc_html_e( 'Product Recommendation Quiz for eCommerce', 'product-recommendation-quiz-for-ecommerce' ); ?>
				<span class="fright">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: link to the RevenueHunt website (anchor "RevenueHunt"). */
						__( 'by %s', 'product-recommendation-quiz-for-ecommerce' ),
						'<a href="https://revenuehunt.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'RevenueHunt', 'product-recommendation-quiz-for-ecommerce' ) . '</a>'
					),
					self::ALLOWED_LINK_HTML
				);
				?>
				</span>
			</p>
			<iframe title="<?php esc_attr_e( 'Product Recommendation Quiz for eCommerce', 'product-recommendation-quiz-for-ecommerce' ); ?>" src="<?php echo esc_url( $this->oauth_url_builder->prquiz_get_oauth_url() ); ?>" name="app-iframe" context="Main" class="prq-iframe"></iframe>
		</div>
		<?php
	}

	/**
	 * Display the first visit setup page with authorization button.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prquiz_first_visit() {
		?>
		<div class="wrap">
			<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'img/revenuehunt-logo.png' ); ?>" width="24" height="24" alt="<?php esc_attr_e( 'RevenueHunt', 'product-recommendation-quiz-for-ecommerce' ); ?>" />
			<p class="fright h-24 mtop-0 prq-author">
				<?php esc_html_e( 'Product Recommendation Quiz for eCommerce', 'product-recommendation-quiz-for-ecommerce' ); ?>
				<span class="fright">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: link to the RevenueHunt website (anchor "RevenueHunt"). */
						__( 'by %s', 'product-recommendation-quiz-for-ecommerce' ),
						'<a href="https://revenuehunt.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'RevenueHunt', 'product-recommendation-quiz-for-ecommerce' ) . '</a>'
					),
					self::ALLOWED_LINK_HTML
				);
				?>
				</span>
			</p>
			<hr>
			<h1 class="mtop-60 alcenter"><?php esc_html_e( 'Congratulations!', 'product-recommendation-quiz-for-ecommerce' ); ?></h1>
			<p class="lg alcenter"><?php esc_html_e( 'You\'re one step away from getting more conversions and sales in your store.', 'product-recommendation-quiz-for-ecommerce' ); ?></p>
			<p class="lg alcenter"><?php esc_html_e( 'We just need you to grant this plugin permission to access your WooCommerce store:', 'product-recommendation-quiz-for-ecommerce' ); ?></p>
			<p class="lg alcenter mtop-30">
				<a class="btn btn-main" href="<?php echo esc_url( $this->oauth_url_builder->prquiz_get_woocommerce_auth_url() ); ?>"><?php esc_html_e( 'grant permission now', 'product-recommendation-quiz-for-ecommerce' ); ?></a>
			</p>
			<p class="alcenter mtop-30">
			<?php
			$article_link = '<a href="https://revenuehunt.com/faqs/troubleshooting-product-recommendation-quiz-app-issues-for-wordpress-woocommerce/" target="_blank" rel="noopener noreferrer">'
				. esc_html__( 'this article', 'product-recommendation-quiz-for-ecommerce' ) . '</a>';
			echo wp_kses(
				sprintf(
					/* translators: %s: link to the troubleshooting article (anchor "this article"). */
					__( 'Are you having trouble granting access? Check out %s', 'product-recommendation-quiz-for-ecommerce' ),
					$article_link
				),
				self::ALLOWED_LINK_HTML
			);
			?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the main plugin options page.
	 *
	 * Performs prerequisite checks and displays either the OAuth flow
	 * or the authenticated admin panel.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function prquiz_options() {
		// Verify user has permission to access this page.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'product-recommendation-quiz-for-ecommerce' ),
				esc_html__( 'Permission Denied', 'product-recommendation-quiz-for-ecommerce' ),
				array( 'response' => 403 )
			);
		}

		$domain = wp_parse_url( site_url(), PHP_URL_HOST );

		// Check WooCommerce is installed.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->diagnostics->woocommerce_missing();
			return;
		}

		// Check HTTPS/SSL (except for local environments).
		if ( Product_Recommendation_Quiz_For_Ecommerce::is_development_environment() ) {
			// Development environment - skip HTTPS check.
			if ( ! defined( 'PRQ_HTTPS_STORE' ) ) {
				define( 'PRQ_HTTPS_STORE', true );
			}
		} elseif ( ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) || ( ! empty( $_SERVER['SERVER_PORT'] ) && 443 === (int) $_SERVER['SERVER_PORT'] ) ) {
			// Your website does have HTTPS - OK.
			if ( ! defined( 'PRQ_HTTPS_STORE' ) ) {
				define( 'PRQ_HTTPS_STORE', true );
			}
		} else {
			// Your website doesn't have HTTPS - ERROR.
			$this->diagnostics->https_ssl_missing();
			return;
		}

		// Check not running on localhost.
		if ( 'localhost' === $domain ) {
			$this->diagnostics->is_localhost();
			return;
		}

		// Check permalinks are not set to plain.
		if ( $this->diagnostics->check_plain_permalink() ) {
			$this->diagnostics->plain_permalink_warning();
			return;
		}

		// Check REST API accessibility.
		$wp_api_check = $this->diagnostics->api_check_json( PRQ_STORE_URL );

		// Handle cURL missing error.
		if ( 0 === $wp_api_check[0] ) {
			$decoded = json_decode( $wp_api_check[1], true );
			if ( isset( $decoded['error'] ) && 'curl_missing' === $decoded['error'] ) {
				?>
				<div class="error">
					<p><strong><?php esc_html_e( 'The cURL extension is required but not installed on your server.', 'product-recommendation-quiz-for-ecommerce' ); ?></strong></p>
				</div>
				<?php
				return;
			}
		}

		/*
		 * RESPONSE CODES:
		 * 200 success, OK
		 * 400 invalid domain was passed
		 * 404 invalid JSON received
		 * 500 valid domain but no connection
		 * 429 tested more than 10 times per minute
		 */
		if ( 404 === $wp_api_check[0] ) {
			$this->diagnostics->wp_json_error();

			$wp_api_check_json = json_decode( $wp_api_check[1] );
			if ( isset( $wp_api_check_json->content_type ) ) {
				$is_html_type = strpos( $wp_api_check_json->content_type, 'text/html' ) !== false;
				$is_json_body = isset( $wp_api_check_json->body ) && $this->diagnostics->is_json( $wp_api_check_json->body );

				if ( $is_html_type && $is_json_body ) {
					$this->diagnostics->wp_json_error_html_content_type( PRQ_STORE_URL );
				}

				if ( isset( $wp_api_check_json->body ) ) {
					$this->diagnostics->wp_json_error_body( $wp_api_check_json->body );
				}
			}
			return;
		}

		// Check shop credentials.
		$shop_hashid = get_option( PRQ_OPTION_SHOP_HASHID );

		if ( $shop_hashid ) {
			// Already have permissions, go to oauth.
			$this->prquiz_authenticated_visit();
		} else {
			// Needs to receive credentials from our server.
			// Check if WPML is active, it causes authentication issues.
			// https://stackoverflow.com/questions/65776787/woocommerce-is-encoding-the-authorization-endpoint
			if ( $this->diagnostics->check_wpml() ) {
				return;
			}
			$this->prquiz_first_visit();
		}
	}
}
