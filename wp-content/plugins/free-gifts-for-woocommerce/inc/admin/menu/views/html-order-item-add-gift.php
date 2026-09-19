<?php
/**
 * Order item Add Gift.
 * 
 * @since 10.0.0
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<button type='button' class='button fgf-order-item-gift-items-button'><?php esc_html_e('Add Gift Item(s)', 'free-gifts-for-woocommerce'); ?></button>

<script type='text/template' id='tmpl-fgf-modal-add-order-item-gift'>
	<div class='wc-backbone-modal'>
	<div class='wc-backbone-modal-content'>
	<section class='wc-backbone-modal-main' role='main'>
	<header class='wc-backbone-modal-header'>
	<h1>
	<?php esc_html_e('Add Gift Products', 'free-gifts-for-woocommerce'); ?>
	</h1>
	<button class='modal-close modal-close-link dashicons dashicons-no-alt'>
	<span class='screen-reader-text'>Close modal panel</span>
	</button>
	</header>
	<article>
	<div class='fgf-order-item-gift-container'>
	<table class='fgf-modal-add-order-item-gift-table widefat'>
	<tbody data-order-id=<?php echo esc_attr($order->get_id()); ?>>
	<tr>
	<td>
	<?php
	fgf_select2_html(array(
		'id' => 'fgf-order-item-gift-products',
		'class' => 'fgf-order-item-gift-products',
		'list_type' => 'products',
		'action' => 'fgf_json_search_products_and_variations',
		'placeholder' => __('Search a Products', 'free-gifts-for-woocommerce'),
		'exclude_global_variable' => 'yes',
		'display_stock' => 'yes',
		'multiple' => false,
	));
	?>
	</td>
	<td><input type='number' class='fgf-order-item-gifts-quantity' min='1' value='1' placeholder='1'/></td>
	</tr>
	</tbody>
	</table>
	</div>
	</article>
	<footer class='wc-backbone-modal-footer'>
	<div class='fgf-add-order-item-gifts-actions inner'>           
	<button type='button' id='btn-ok' class='button button-primary fgf-add-order-item-gifts-button'><?php esc_html_e('Add Gift', 'free-gifts-for-woocommerce'); ?></button>
	</div>  
	</footer>
	</section>
	</div>
	</div>
	<div class='wc-backbone-modal-backdrop modal-close'></div>
</script>

<script type='text/template' id='tmpl-fgf-modal-order-item-gift-row'>
	<tr>
	<td>
	<?php
	fgf_select2_html(array(
		'id' => 'fgf-order-item-gift-products',
		'class' => 'fgf-order-item-gift-products',
		'list_type' => 'products',
		'action' => 'fgf_json_search_products_and_variations',
		'placeholder' => __('Search a Products', 'free-gifts-for-woocommerce'),
		'exclude_global_variable' => 'yes',
		'display_stock' => 'yes',
		'multiple' => false,
	));
	?>
	</td>
	<td><input type='number' class='fgf-order-item-gifts-quantity' min='1' value='1' placeholder='1'/></td>
	</tr>
</script>
<?php
