<?php
/**
 * This template displays gift products layout in cart page
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/gift-products-layout.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class="fgf_gift_products_wrapper" id='fgf_gift_products_wrapper'>
	<?php
	/**
	 * This hook is used to display the extra content before gift products content.
	 * 
	 * @since 1.0.0
	 */
	do_action('fgf_before_gift_products_content');
	?>
	<h3><?php echo esc_html(get_option('fgf_settings_free_gift_heading_label')); ?></h3>
	<div class="fgf-gift-products-content">
		<table class="shop_table shop_table_responsive fgf_gift_products_table fgf-frontend-table">

			<thead>
				<tr>
					<?php foreach (fgf_get_free_gifts_table_columns() as $column_name) : ?>
						<th><?php echo esc_html($column_name); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>

			<tbody>
				<?php
				fgf_get_template('gift-products.php', array( 'gift_products' => $gift_products, 'permalink'=>false ));
				?>
			</tbody>

			<?php if ($pagination['page_count'] > 1) : ?>
				<tfoot>
					<tr>
						<td colspan="<?php echo esc_attr(count(fgf_get_free_gifts_table_columns())); ?>" class="footable-visible actions">
							<?php fgf_get_template('pagination.php', $pagination); ?>
						</td>
					</tr>
				</tfoot>
			<?php endif; ?>

		</table>
	</div>
	<?php
	/**
	 * This hook is used to display the extra content after gift products content.
	 * 
	 * @since 1.0.0
	 */
	do_action('fgf_after_gift_products_content');
	?>
	<input type="hidden" id="fgf_gift_products_type" value='<?php echo esc_attr($mode); ?>'>
</div>
<?php
