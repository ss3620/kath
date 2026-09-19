<?php
/**
 * Builder page template.
 *
 * Renders the page/product/archive assigned to the current request through
 * the block builder, falling back to normal post content when no builder
 * page applies.
 *
 * @package WOPB\Builder
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial included directly into a method scope; these are local render-time variables, not plugin globals.

global $post;
global $WOPB_HEADER_ID;
global $WOPB_FOOTER_ID;

$page_id         = wopb_function()->conditions( 'return' );
$is_shop_archive = is_shop() || is_product_taxonomy() || is_product_tag();

$post = get_post( $page_id, OBJECT );
setup_postdata( $post );
if ( defined( 'GENERATEBLOCKS_DIR' ) ) { // Generate block css support
	generateblocks_get_dynamic_css();
}
wp_reset_postdata();

/**
 * -----------------------------------------------------------------------
 * Header
 * -----------------------------------------------------------------------
 */
if ( wp_is_block_theme() ) {
	?><!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php
	wp_body_open();
	if ( ! $WOPB_HEADER_ID ) {
		ob_start();
		block_template_part( 'header' );
		$header_safe = wopb_function()->core_esc_wp( ob_get_clean() );
		echo '<header class="wp-block-template-part">' . $header_safe . '</header>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
} else {
	get_header();
}

do_action( 'wopb_before_content' );

/**
 * -----------------------------------------------------------------------
 * Layout settings (container width / sidebar)
 * -----------------------------------------------------------------------
 */
$width       = $page_id ? get_post_meta( $page_id, '__wopb_container_width', true ) : '1200';
$sidebar     = $page_id ? get_post_meta( $page_id, 'wopb-builder-sidebar', true ) : '';
$widget_area = $page_id ? get_post_meta( $page_id, 'wopb-builder-widget-area', true ) : '';
$has_widget  = $sidebar && '' !== $widget_area;

/**
 * -----------------------------------------------------------------------
 * Open container / left sidebar
 * -----------------------------------------------------------------------
 */
if ( $width ) {
	$container_class = 'wopb-builder-container product' . ( $has_widget ? ' wopb-widget-' . $sidebar : '' );
	$divi_id_attr    = ( 'Divi' === wopb_function()->get_theme_name() ) ? ' id="main-content"' : '';

	printf(
		'<div%1$s class="%2$s" style="max-width: %3$spx; margin: 0 auto;">',
		$divi_id_attr, //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static string, not user input.
		esc_attr( $container_class ),
		esc_attr( $width )
	);

	if ( is_product() ) {
		echo "<div style='width: 100%;'>";
		do_action( 'woocommerce_before_single_product' );
		wc_print_notices();
		echo '</div>';
	}
}

if ( $has_widget && 'left' === $sidebar ) {
	echo '<div class="wopb-sidebar-left">';
	if ( is_active_sidebar( $widget_area ) ) {
		dynamic_sidebar( $widget_area );
	}
	echo '</div>';
}

if ( $page_id && $has_widget ) {
	echo '<div class="wopb-builder-wrap">';
}

/**
 * -----------------------------------------------------------------------
 * Checkout form open
 * -----------------------------------------------------------------------
 */
$is_checkout_form = is_checkout() && ! ( is_wc_endpoint_url() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) );

if ( $is_checkout_form ) {
	$checkout = WC()->checkout();

	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
	do_action( 'woocommerce_before_checkout_form', $checkout );

	// If checkout registration is disabled and not logged in, the user cannot checkout.
	if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
		echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'product-blocks' ) ) );
		return;
	}

	echo '<form name="checkout" method="post" class="checkout woocommerce-checkout wopb-checkout-form" action="' . esc_url( wc_get_checkout_url() ) . '" enctype="multipart/form-data" style="display:block">';
}

/**
 * -----------------------------------------------------------------------
 * Shop / archive hooks
 * -----------------------------------------------------------------------
 */
if ( $is_shop_archive ) {
	// The grid blocks below run their own WP_Query and render their own sorting/pagination UI,
	// so the default result-count/ordering/pagination callbacks would be inaccurate or duplicated.
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
	do_action( 'woocommerce_before_shop_loop' );
}

/**
 * -----------------------------------------------------------------------
 * Content
 * -----------------------------------------------------------------------
 */
if ( $page_id ) {
	// Reserved for future use: allow-list to permit <script> tags through wp_kses_safe()
	// below, once block output is run through it instead of being echoed raw.
	$allow_script_tag = array(
		'allowed' => array(
			'script' => array(   // Wp.
				'type'           => true,
				'src'            => true,
				'id'             => true,
				'defer'          => true,
				'integrity'      => true,
				'crossorigin'    => true,
				'referrerpolicy' => true,
				'async'          => true,
				'nomodule'       => true,
				'charset'        => true,
				'nonce'          => true,
				'data-*'         => true,
			),
		),
	);

	$content_post = get_post( $page_id );
	$content      = $content_post->post_content;

	if ( has_blocks( $content ) ) {
		$blocks = parse_blocks( $content );
		$embed  = new WP_Embed();

		foreach ( $blocks as $block ) {
			$contents = $embed->autoembed( do_shortcode( render_block( $block ) ) );
			// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- reserved for future use.
			// $content_safe = wopb_function()->wp_kses_safe( $contents, $allow_script_tag );
			echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block/shortcode output; each block's own render callback is responsible for escaping its content.
		}
	}
} else {
	the_content();
}

if ( $is_shop_archive ) {
	do_action( 'woocommerce_after_shop_loop' );
}

/**
 * -----------------------------------------------------------------------
 * Checkout form close
 * -----------------------------------------------------------------------
 */
if ( $is_checkout_form ) {
	echo '</form>';
	do_action( 'woocommerce_after_checkout_form', $checkout );
}

/**
 * -----------------------------------------------------------------------
 * Close wrap / right sidebar / container
 * -----------------------------------------------------------------------
 */
if ( $page_id && $has_widget ) {
	echo '</div>';
}

if ( $has_widget && 'right' === $sidebar ) {
	echo '<div class="wopb-sidebar-right">';
	if ( is_active_sidebar( $widget_area ) ) {
		dynamic_sidebar( $widget_area );
	}
	echo '</div>';
}

if ( $width ) {
	echo '</div>';
}

if ( is_product() ) {
	do_action( 'woocommerce_after_single_product' );
}

do_action( 'wopb_after_content' );

/**
 * -----------------------------------------------------------------------
 * Footer
 * -----------------------------------------------------------------------
 */
if ( wp_is_block_theme() ) {
	?>
	</body>
	</html>
	<?php
	if ( ! $WOPB_FOOTER_ID ) {
		ob_start();
		block_template_part( 'footer' );
		$footer_safe = wopb_function()->core_esc_wp( ob_get_clean() );
		echo '<footer class="wp-block-template-part">' . $footer_safe . '</footer>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	wp_head();
	wp_footer();
} else {
	get_footer();
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
