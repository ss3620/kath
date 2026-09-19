<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.wployalty.net
 * */
defined( 'ABSPATH' ) or die;
if ( isset( $available_products ) && ! empty( $available_products ) ): ?>
    <div class="wlr-select-free-variant-product-toggle"><?php esc_html_e( 'Change Variant', 'wp-loyalty-rules' ) ?></div>
    <div class="wlr-select-variant-product">
		<?php
		foreach ( $available_products as $wlrf_available_product ) { //parent_id
			if ( isset( $customer_chose_variant ) && $wlrf_available_product != $customer_chose_variant ) {
				$wlrf_product_variation = new WC_Product_Variation( $wlrf_available_product );
				// get variation featured image
				$wlrf_variation_image = $wlrf_product_variation->get_image( array( 50, 50 ) );
				?>
                <div class="wlr_free_product_variants">
                    <span class="wlr_change_product" data-pid="<?php echo esc_attr( $wlrf_available_product ); ?>"
                          data-rule_id="<?php echo isset( $loyalty_user_reward_id ) && $loyalty_user_reward_id ? esc_attr( $loyalty_user_reward_id ) : 0; ?>"
                          data-parent_id="<?php echo isset( $parent_product_id ) && ! empty( $parent_product_id ) ? esc_attr( $parent_product_id ) : 0; ?>">
                        <span class="wlr_variation_image"><?php echo wp_kses_post( $wlrf_variation_image ); ?></span>
                        <span class="wlr-product-name"><?php echo wp_kses_post( get_the_title( $wlrf_available_product ) ); ?></span>
                    </span>
                </div>
				<?php
			}
		}
		?>
    </div>
<?php endif; ?>

