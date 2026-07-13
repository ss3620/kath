<?php
/**
 * Free gifts block preview template
 * 
 * @since 11.0.0
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-free-gifts-block-preview_wrapper'>
	<h3><?php echo esc_html(get_option('fgf_settings_free_gift_heading_label')); ?></h3>
	<div class='fgf-free-gifts-block-content_wrapper'>
		<table class='fgf-free-gifts-block_table fgf-frontend-table'>
			<thead>
				<tr>
					<?php foreach (fgf_get_free_gifts_table_columns() as $column_name) : ?>
						<th><?php echo esc_html($column_name); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<tr class='fgf-free-gifts-block_item'>
					<td data-title="<?php esc_attr_e('Product Name', 'free-gifts-for-woocommerce'); ?>">Gift Product 1</td>
					<td data-title="<?php esc_attr_e('Product Image', 'free-gifts-for-woocommerce'); ?>">
						<img class='fgf-free-gifts-block-item_image' width='90' height='90' src='<?php echo esc_url(wc_placeholder_img_src()); ?>'>
					</td>
					<td data-title="<?php esc_attr_e('Add to cart', 'free-gifts-for-woocommerce'); ?>">
						<a class='fgf-free-gifts-block-item_link button' href='javascript:void(0)'> <?php echo esc_html(get_option('fgf_settings_free_gift_add_to_cart_button_label')); ?></a>
					</td>
				</tr>
				<tr class='fgf-free-gifts-block_item'>
					<td data-title="<?php esc_attr_e('Product Name', 'free-gifts-for-woocommerce'); ?>">Gift Product 2</td>
					<td data-title="<?php esc_attr_e('Product Image', 'free-gifts-for-woocommerce'); ?>">
						<img class='fgf-free-gifts-block-item_image' width='90' height='90' src='<?php echo esc_url(wc_placeholder_img_src()); ?>'>
					</td>
					<td data-title="<?php esc_attr_e('Add to cart', 'free-gifts-for-woocommerce'); ?>">
						<a class='fgf-free-gifts-block-item_link button' href='javascript:void(0)'> <?php echo esc_html(get_option('fgf_settings_free_gift_add_to_cart_button_label')); ?></a>
					</td>
				</tr>
			</tbody>
	</div>
</div>
<?php
