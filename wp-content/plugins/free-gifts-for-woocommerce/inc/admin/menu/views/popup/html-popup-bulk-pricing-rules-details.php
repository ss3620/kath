<?php
/**
 * Popup - Bulk pricing rules details.
 * 
 * @since 12.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='wc-backbone-modal'>
	<div class='wc-backbone-modal-content'>
		<section class='wc-backbone-modal-main' role='main'>
			<header class='wc-backbone-modal-header'>
				<h1>
					<?php
					/* translators: %1$s - ID */
					$content = sprintf(__('View Bulk Pricing Rules - #%1$s', 'free-gifts-for-woocommerce'), $rule->get_id());
					echo wp_kses_post($content);
					?>
				</h1>
				<button class='modal-close modal-close-link dashicons dashicons-no-alt'>
					<span class='screen-reader-text'>Close modal panel</span>
				</button>
			</header>
			<article>
				<div class='fgf-bulk-pricing-rules-details-wrapper fgf-popup-content-wrapper'>
					<table class='fgf-bulk-pricing-rules-details-table fgf-table'>
						<thead>
							<tr>
								<th><?php esc_html_e('Minimum', 'free-gifts-for-woocommerce'); ?></th>
								<th><?php esc_html_e('Maximum', 'free-gifts-for-woocommerce'); ?></th>
								<th><?php esc_html_e('Quantity', 'free-gifts-for-woocommerce'); ?></th>
								<th><?php esc_html_e('Gifts', 'free-gifts-for-woocommerce'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ($rule->get_bulk_pricing_rules() as $bulk_pricing_rule) :
								if (!fgf_check_is_array($bulk_pricing_rule)) :
									continue;
								endif;
								?>
								<tr>
									<td><?php echo wp_kses_post(fgf_price($bulk_pricing_rule['minimum'], false)); ?></td>
									<td><?php echo wp_kses_post(fgf_price($bulk_pricing_rule['maximum'], false)); ?></td>
									<td><?php echo esc_html($bulk_pricing_rule['gift_quantity']); ?></td>
									<td>
										<?php if ('2' === $bulk_pricing_rule['gift_type'] && '10'!== $rule->get_rule_type()) : ?>
											<b><u><?php esc_html_e('Categories', 'free-gifts-for-woocommerce'); ?></u></b><br /> <?php echo wp_kses_post(fgf_prepare_terms_edit_link_by_ids($bulk_pricing_rule['gift_categories'])); ?>
										<?php elseif ('brand' === $bulk_pricing_rule['gift_type'] && '10'!== $rule->get_rule_type()) : ?>
											<b><u><?php esc_html_e('Brands', 'free-gifts-for-woocommerce'); ?></u></b><br /> <?php echo wp_kses_post(fgf_prepare_terms_edit_link_by_ids($bulk_pricing_rule['gift_brands'], 'product_brand')); ?>
										<?php else : ?>
											<b><u><?php esc_html_e('Gift Product(s)', 'free-gifts-for-woocommerce'); ?></u></b> <br /> <?php echo wp_kses_post(fgf_prepare_products_edit_link_by_ids($bulk_pricing_rule['gift_products'])); ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</article>
		</section>
	</div>
</div>
<div class='wc-backbone-modal-backdrop modal-close'></div>
<?php
