<?php
defined( 'ABSPATH' ) || exit;

$is_block_theme = wp_is_block_theme();

if ( $is_block_theme ) {
	global $WOPB_HEADER_ID;
	global $WOPB_FOOTER_ID;
	?><!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<?php
		wp_head();
		?>
	</head>
	<body <?php body_class(); ?>>
	<?php
	wp_body_open();

	if ( ! $WOPB_HEADER_ID ) {
		ob_start();
		block_template_part( 'header' );
		$header_safe = wopb_function()->core_esc_wp( ob_get_clean() );
		echo '<header class="wp-block-template-part">'.$header_safe.'</header>'; // phpcs:ignore
	}
} else {
	get_header();
}

do_action( 'wopb_before_content' );

$width = wopb_function()->get_setting( 'container_width' );
$width = $width ? $width : '1140';
?>
<div 
	class="wopb-template-container" 
	style="margin:0 auto; max-width:<?php echo esc_attr( $width ); ?>px; padding: 0 15px; width: -webkit-fill-available; width: -moz-available;"
	<?php
	if ( wopb_function()->get_theme_name() == 'Divi' ) {
		echo 'id="main-content"'; }
	?>
>
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		endwhile;
	?>
</div>
<?php

do_action( 'wopb_after_content' );

if ( $is_block_theme ) {
	if ( ! $WOPB_FOOTER_ID ) {
		ob_start();
		block_template_part( 'footer' );
		$footer_safe = wopb_function()->core_esc_wp( ob_get_clean() );
		echo '<footer class="wp-block-template-part">' . $footer_safe . '</footer>';
	}
	wp_head();
	wp_footer();
	?>
	</body>
	</html>
	<?php
} else {
	get_footer();
}
