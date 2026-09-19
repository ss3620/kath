<?php
/**
 * This template displays gift products table pagination
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/pagination.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<nav class = "pagination pagination-centered woocommerce-pagination">
	<ul>
		<li><span class="fgf_pagination fgf_first_pagination" data-page="1"><<</span></li>
		<li><span class="fgf_pagination fgf_prev_pagination" data-page="1"><</span></li>
		<?php
		for ( $start = 1 ; $start <= $page_count ; $start++ ) :
			$page_no = fgf_get_pagination_number( $start , $page_count , $current_page ) ;
			if ( $page_no ) :
				?>
				<li><span class="<?php echo esc_attr( implode( ' ' , fgf_get_pagination_classes( $start , $current_page ) ) ) ; ?>" data-page="<?php echo esc_attr( $page_no ) ; ?>"><?php echo esc_html( $page_no ) ; ?></span></li>
				<?php
			endif ;
		endfor ;
		?>
		<li><span class="fgf_pagination fgf_next_pagination" data-page="2">></span></li>
		<li><span class="fgf_pagination fgf_last_pagination" data-page="<?php echo esc_attr( $page_count ) ; ?>">>></span></li>
	</ul>
</nav>
<?php
