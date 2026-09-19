<?php
/**
 * Front-end delivery: the embed.js storefront script.
 *
 * The V1 implementation of the plugin's front-end delivery seam
 * (Product_Recommendation_Quiz_For_Ecommerce_Delivery) — it loads embed.js from
 * admin.revenuehunt.com and returns the inline hydration container embed.js
 * targets. Isolating it here keeps "how the quiz reaches the storefront"
 * swappable without touching the rest of the plugin.
 *
 * @link       https://revenuehunt.com/
 * @since      2.3.9
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Enqueues the embed.js delivery script on the storefront.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/front
 */
class Product_Recommendation_Quiz_For_Ecommerce_Front_Embed_Script implements Product_Recommendation_Quiz_For_Ecommerce_Delivery {

	/**
	 * The ID of this plugin.
	 *
	 * @since    2.3.9
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    2.3.9
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Whether the full-width breakout CSS has already been emitted this request.
	 *
	 * The resolver builds one delivery instance per request shared by every
	 * placement (shortcode + block), so this instance flag prints the small
	 * scoped <style> at most once even with several full-width quizzes on a page.
	 *
	 * @since 2.4.0
	 * @var bool
	 */
	private $full_width_css_printed = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.3.9
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * Skips loading on WooCommerce checkout and cart pages where the quiz
	 * is never rendered, so a connection timeout to our servers can never
	 * block those critical pages.
	 *
	 * The script is loaded with the 'async' strategy so that even on pages
	 * where it IS enqueued, a slow or failed connection to admin.revenuehunt.com
	 * will not block page rendering or execution.
	 *
	 * @since    2.3.9
	 */
	public function enqueue_scripts() {
		// Don't load the embed script on WooCommerce checkout or cart pages.
		// The quiz is never rendered there, and a connection timeout would
		// degrade the shopping experience for no benefit.
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return;
		}

		$this->enqueue_embed();
	}

	/**
	 * The origin embed.js and the hosted quiz are served from.
	 *
	 * Single accessor for the backend origin so both the global enqueue and the
	 * placement renderer reference it identically.
	 *
	 * @since 2.4.0
	 * @return string The admin/backend origin (no trailing slash).
	 */
	private function admin_origin() {
		return PRQ_ADMIN_URL;
	}

	/**
	 * Enqueue embed.js with its js_vars payload.
	 *
	 * Shared by the site-wide hook and by placement rendering. WordPress dedupes
	 * by script handle, so calling this more than once per request never
	 * double-loads the script.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	private function enqueue_embed() {
		$data_to_pass = array(
			'shop'           => PRQ_STORE_URL,
			'platform'       => 'woocommerce',
			'channel'        => 'wordpress',
			'plugin_version' => PRQ_PLUGIN_VERSION,
			'woo_version'    => PRQ_WOO_VERSION,
			'wp_version'     => PRQ_WP_VERSION,
		);

		wp_enqueue_script( $this->plugin_name, $this->admin_origin() . '/embed.js?shop=' . rawurlencode( PRQ_STORE_URL ), array(), PRQ_PLUGIN_VERSION, true );
		wp_localize_script( $this->plugin_name, 'js_vars', $data_to_pass );
	}

	/**
	 * Render an inline quiz placement and ensure embed.js is loaded.
	 *
	 * Returns the container embed.js hydrates in place (the documented
	 * `rh-widget rh-inline` element with the quiz's public data-url). Placement
	 * is explicit merchant intent, so — unlike the site-wide enqueue — it does
	 * not skip cart/checkout. embed.js keeps its async / graceful-degradation
	 * behavior (the script_loader_tag filter applies to this enqueue too), and
	 * the shared handle means the script is never loaded twice on a page.
	 *
	 * Maps placement attributes to the inline embed contract embed.js reads (the
	 * same one the app's Share tab emits): the height/unit in the style, plus the
	 * optional data-fixed-height and data-autoscroll attributes. Defaults match
	 * embed.js — the quiz expands to fit content and auto-scrolls — so an
	 * attribute is only emitted to opt OUT.
	 *
	 * Full-width is a plugin-side wrapper concern, NOT an embed.js attribute: when
	 * 'full_width' is set the same hydration container is wrapped in a breakout
	 * element so it spans the viewport instead of the content column. The inner
	 * `rh-widget rh-inline` element is byte-identical either way, so the embed.js
	 * contract is untouched.
	 *
	 * @since 2.4.0
	 * @param array<string, mixed> $atts Placement attributes: 'id' (quiz id, required),
	 *                                   'height' (number, default 600), 'height_unit'
	 *                                   ('px'|'vh', default 'px'), 'fixed_height'
	 *                                   (bool, default false), 'autoscroll' (bool, default true),
	 *                                   'full_width' (bool, default false).
	 * @return string The placement HTML, or '' when no quiz id is given.
	 */
	public function render( array $atts ): string {
		$quiz_id = isset( $atts['id'] ) ? sanitize_text_field( (string) $atts['id'] ) : '';
		if ( '' === $quiz_id ) {
			return '';
		}

		$height = isset( $atts['height'] ) ? absint( $atts['height'] ) : 600;
		if ( 0 === $height ) {
			$height = 600;
		}

		$unit = isset( $atts['height_unit'] ) ? (string) $atts['height_unit'] : 'px';
		if ( ! in_array( $unit, array( 'px', 'vh' ), true ) ) {
			$unit = 'px';
		}

		$fixed_height = ! empty( $atts['fixed_height'] );
		$autoscroll   = ! isset( $atts['autoscroll'] ) || (bool) $atts['autoscroll'];

		$data_attrs = '';
		if ( $fixed_height ) {
			$data_attrs .= ' data-fixed-height="true"';
		}
		if ( ! $autoscroll ) {
			$data_attrs .= ' data-autoscroll="false"';
		}

		$this->enqueue_embed();

		$quiz_url = $this->admin_origin() . '/public/quiz/' . rawurlencode( $quiz_id );

		$container = sprintf(
			'<div class="rh-widget rh-inline" data-url="%s"%s style="margin:10px auto;width:100%%;height:%d%s;display:flex;"></div>',
			esc_url( $quiz_url ),
			$data_attrs,
			$height,
			$unit
		);

		if ( ! empty( $atts['full_width'] ) ) {
			return $this->wrap_full_width( $container );
		}

		return $container;
	}

	/**
	 * Wrap an inline placement so it breaks out of the content column to span the
	 * full viewport width.
	 *
	 * The wrapper carries the breakout CSS via a scoped class; the inner
	 * container keeps its `width:100%`, which now resolves to 100% of the
	 * full-viewport-wide wrapper. The technique is the standard, theme-agnostic
	 * negative-margin breakout (`margin-left/right: calc(50% - 50vw)`, the same
	 * math WordPress's `alignfull` uses): the negative margins land the wrapper
	 * flush against both viewport edges.
	 *
	 * The declarations are `!important` because block themes cap content children
	 * with `max-width: var(--content-size)` and re-center them with
	 * `margin-inline: auto` — without `!important` that cap clamps `width:100vw`
	 * and leaves the quiz centered with gutters instead of full-bleed. The inner
	 * container's margins are reset to 0 so nothing re-centers it inside the
	 * now-full-width wrapper. The scoped <style> is printed at most once per request.
	 *
	 * @since 2.4.0
	 * @param string $container The inline hydration container to wrap.
	 * @return string The full-width wrapper markup, plus the scoped CSS on first use.
	 */
	private function wrap_full_width( string $container ): string {
		$style = '';
		if ( ! $this->full_width_css_printed ) {
			$this->full_width_css_printed = true;
			$style                        = '<style id="prq-full-width-css">.prq-quiz--full-width{width:100vw!important;max-width:100vw!important;margin-left:calc(50% - 50vw)!important;margin-right:calc(50% - 50vw)!important}.prq-quiz--full-width>.rh-inline{max-width:none!important;margin-left:0!important;margin-right:0!important}</style>';
		}

		return $style . '<div class="prq-quiz prq-quiz--full-width">' . $container . '</div>';
	}

	/**
	 * Add 'async' attribute to the embed.js script tag.
	 *
	 * This ensures that even if admin.revenuehunt.com is unreachable (e.g. due
	 * to ISP routing issues), the merchant's page continues to load and function
	 * normally. The quiz simply won't appear — graceful degradation.
	 *
	 * @since    2.3.9
	 * @param string $tag    The full script tag HTML.
	 * @param string $handle The script's registered handle.
	 * @param string $src    The script source URL.
	 * @return string Modified script tag with async attribute.
	 */
	public function add_async_to_embed_script( $tag, $handle, $src ) {
		if ( $this->plugin_name !== $handle ) {
			return $tag;
		}

		// Don't double-add if WordPress already added it (WP 6.3+ strategy support).
		if ( false !== strpos( $tag, ' async' ) ) {
			return $tag;
		}

		return str_replace( '<script ', '<script async ', $tag );
	}
}
