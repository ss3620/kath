<?php
/**
 * This template displays contents inside shortcode layout
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/shortcode-layout.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<div class="fgf_shortcode_gift_products_wrapper">
	<?php
	/**
	 * This hook is used to display the extra content before gift products content.
	 * 
	 * @since 1.0
	 */
	do_action( 'fgf_before_shortcode_gift_products_content' ) ;

	if ( $data_args ) :

		if ( 'popup' == $mode ) :
			?>
			<p class="fgf_shortcode_popup_message"><?php echo wp_kses_post( $popup_message ) ; ?></p>
			<?php
			// Display the gift products popup layout.
			fgf_get_template( 'popup-layout.php', array( 'data_args' => $data_args ) ) ;
		else :
			// Display the gift products layout.
			fgf_get_template( $data_args[ 'template' ], $data_args ) ;
		endif ;

	else :
		echo wp_kses_post( get_option( 'fgf_settings_shortcode_free_gift_empty_message' ) ) ;
	endif ;

	/**
	 * This hook is used to display the extra content after gift products content.
	 * 
	 * @since 1.0
	 */
	do_action( 'fgf_after_shortcode_gift_products_content' ) ;
	?>
</div>
<?php

