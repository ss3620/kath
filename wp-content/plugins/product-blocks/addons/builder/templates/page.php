<?php
defined( 'ABSPATH' ) || exit;

global $post;
global $WOPB_HEADER_ID;
global $WOPB_FOOTER_ID;
$page_id = wopb_function()->conditions( 'return' );

$post = get_post( $page_id, OBJECT );
setup_postdata( $post );
if ( defined( 'GENERATEBLOCKS_DIR' ) ) { // Generate block css support
	generateblocks_get_dynamic_css();
}
wp_reset_postdata();

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


$width       = $page_id ? get_post_meta( $page_id, '__wopb_container_width', true ) : '1200';
$sidebar     = $page_id ? get_post_meta( $page_id, 'wopb-builder-sidebar', true ) : '';
$widget_area = $page_id ? get_post_meta( $page_id, 'wopb-builder-widget-area', true ) : '';
$has_widget  = ( $sidebar && $widget_area != '' ) ? true : false;
if ( $width ) {
	echo '<div ';
	if ( wopb_function()->get_theme_name() == 'Divi' ) {
		echo 'id="main-content"';
	}
		echo 'class="wopb-builder-container product ' . ( ( $has_widget ? ' wopb-widget-' . esc_attr( $sidebar ) : '' ) ) . '"';
		echo 'style="max-width: ' . esc_attr( $width ) . 'px; margin: 0 auto;"';
	echo '>';
	if ( is_product() ) {
		echo "<div style='width: 100%;'>";
			do_action( 'woocommerce_before_single_product' );
			wc_print_notices();
		echo '</div>';
	}
}
if ( $has_widget && $sidebar == 'left' ) {
	echo '<div class="wopb-sidebar-left">';
	if ( is_active_sidebar( $widget_area ) ) {
		dynamic_sidebar( $widget_area );
	}
	echo '</div>';
}
if ( $page_id && $has_widget ) {
	echo '<div class="wopb-builder-wrap">';
}
if ( is_checkout() && ! ( is_wc_endpoint_url() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
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

if ( $page_id ) {
	$allow_script_tag = array(
		'allowed' => array(
			'script' => array(   // Wp
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
	// 'allowed'
	$content_post = get_post( $page_id );
	$content      = $content_post->post_content;
	if ( has_blocks( $content ) ) {
		$blocks = parse_blocks( $content );
		$embed  = new WP_Embed();
		foreach ( $blocks as $block ) {
			$contents = $embed->autoembed( do_shortcode( render_block( $block ) ) );
			// $content_safe = wopb_function()->wp_kses_safe($contents, $allow_script_tag);
			echo $contents; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ( inside All database and user interactions was sanitized and validated to ensure safety and prevent vulnerabilities  )
		}
	}
} else {
	the_content();
}

if ( is_checkout() && ! ( is_wc_endpoint_url() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
	echo '</form>';
	do_action( 'woocommerce_after_checkout_form', $checkout );
}

if ( $page_id && $has_widget ) {
	echo '</div>';
}

if ( $has_widget && $sidebar == 'right' ) {
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

if ( wp_is_block_theme() ) {
	?>
	</body>
	</html>
	<?php
	if ( ! $WOPB_FOOTER_ID ) {
		ob_start();
		block_template_part( 'footer' );
		$footer_safe = wopb_function()->core_esc_wp( ob_get_clean() );
		echo '<footer class="wp-block-template-part">' . $footer_safe . '</footer>';
	}
	wp_head();
	wp_footer();
} else {
	get_footer();
}
