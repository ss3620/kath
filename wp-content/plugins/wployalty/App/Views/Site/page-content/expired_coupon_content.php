<?php

use Wlr\App\Helpers\EarnCampaign;
use Wlr\App\Helpers\Settings;

defined( "ABSPATH" ) or die();
$wlrf_earn_campaign_helper = EarnCampaign::getInstance();
$wlrf_woocommerce_helper   = \Wlr\App\Helpers\Woocommerce::getInstance();
$wlrf_border_color         = Settings::get( 'border_color', '#CFCFCF' );
?>
<div class="wlr-coupons-list">
	<?php if ( ! empty( $items ) ): ?>
		<?php $wlrf_card_key = 1;
		foreach ( $items as $wlrf_item ): ?>
            <div class="wlr-coupons-expired-content <?php echo ( ! empty( $wlrf_item->discount_code ) ) ? 'wlr-new-coupon-card wlr-expired-card' : ''; ?> wlr-border-color">
                <div class="wlr-card-container">
                    <div class="wlr-coupon-card-header">
                        <div class="wlr-title-icon">
                            <div class="wlr-card-icon-container">
                                <div class="wlr-card-icon">
									<?php $wlrf_discount_type = ! empty( $wlrf_item->discount_type ) ? $wlrf_item->discount_type : "" ?>
									<?php $wlrf_img_icon = ! empty( $wlrf_item->icon ) ? $wlrf_item->icon : "" ?>
									<?php echo wp_kses_post( \Wlr\App\Helpers\Base::setImageIcon( $wlrf_img_icon, $wlrf_discount_type, [ "alt" => $wlrf_item->name ] ) ); ?>
                                </div>
                            </div>
                            <div class="wlr-name-container">
                                <h4 class="wlr-name wlr-text-color">
									<?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo \Wlr\App\Helpers\Base::readMoreLessContent( $wlrf_item->name, $wlrf_card_key, 60, esc_html__( "Show more", "wp-loyalty-rules" ), esc_html__( "Show less", "wp-loyalty-rules" ), 'card-my-reward-name', 'wlr-name wlr-pre-text wlr-text-color' ); ?>
                                </h4>
                                <p class="wlr-text-color">
									<?php echo wp_kses_post( $wlrf_item->reward_type_name ); ?>
									<?php $wlrf_discount_value = ! empty( $wlrf_item->discount_value ) && ( $wlrf_item->discount_value != 0 ) ? ( $wlrf_item->discount_value ) : ''; ?>
									<?php if ( $wlrf_discount_value > 0 && isset( $wlrf_item->discount_type ) && in_array( $wlrf_item->discount_type, [
											'percent',
											'fixed_cart',
											'points_conversion'
										] ) ): ?>
										<?php if ( ( $wlrf_item->discount_type == 'points_conversion' ) && ! empty( $wlrf_item->discount_code ) ) : ?>
											<?php echo $wlrf_item->coupon_type != 'percent' ? wp_kses_post( " - " . $wlrf_woocommerce_helper->convertPrice( $wlrf_discount_value, true, $wlrf_item->reward_currency ) ) : esc_html( " - " . number_format( $wlrf_discount_value, 2 ) . '%' ); ?>
										<?php elseif ( $wlrf_item->discount_type != 'points_conversion' ): ?>
											<?php echo ( $wlrf_item->discount_type == 'percent' ) ? esc_html( " - " . round( $wlrf_discount_value ) . "%" ) : wp_kses_post( " - " . $wlrf_woocommerce_helper->convertPrice( $wlrf_discount_value, true, $wlrf_item->reward_currency ) ); ?>
										<?php endif; ?>
									<?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="wlr-code-button">
							<?php if ( ! empty( $wlrf_item->discount_code ) ): ?>
                                <div class="wlr-code"
                                     style="<?php echo ! empty( $wlrf_border_color ) ? esc_attr( "align-items:center;justify-content:center;color:" . $wlrf_border_color . ";background:unset;border:1px dashed " . $wlrf_border_color . ";" ) : ""; ?>">
                                    <div class="wlr-coupon-code">
                                        <p title="<?php esc_html_e( 'Coupon Code', 'wp-loyalty-rules' ); ?>">
                                                <span class="wlr-border-color wlr-text-color"
                                                      id="<?php echo esc_attr( 'wlr-' . $wlrf_item->discount_code ) ?>"><?php echo esc_html( $wlrf_item->discount_code ); ?></span>
                                        </p>
                                    </div>
                                </div>
							<?php endif; ?>
                        </div>
                    </div>
                    <div class="wlr-coupon-card-footer">
                        <div class="wlr-coupon-date-section">
							<?php if ( ! empty( $wlrf_item->expiry_date ) && ! empty( $wlrf_item->discount_code ) && isset( $wlrf_item->status ) && $wlrf_item->status == 'expired' ): ?>
                                <div class="wlr-flex"><i class="wlrf-clock wlr-text-color"></i>
                                    <p class="wlr-expire-date wlr-text-color">
										<?php /* translators: %s: expired date */
										echo esc_html( sprintf( __( "Expired on %s", "wp-loyalty-rules" ), $wlrf_item->expiry_date ) ); ?></p>
                                </div>
							<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
			<?php
			$wlrf_card_key ++;
		endforeach; ?>
		<?php if ( isset( $total ) && $total > 0 ): ?>
            <div class="wlr-coupon-pagination">
                <div>
                    <div style="text-align: right">
						<?php if ( isset( $offset ) && 1 !== (int) $offset ) : ?>
                            <a class="woocommerce-button woocommerce-button--previous woocommerce-Button wlr-cursor wlr-text-color"
                               onclick="wlr_jquery( 'body' ).trigger( 'wlr_my_reward_section_pagination', ['coupons-expired','<?php echo esc_js( $offset - 1 ); ?>' ] )"
                               id="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-prev-button' ) ?>">
								<?php esc_html_e( 'Prev', 'wp-loyalty-rules' ); ?>
                            </a>
						<?php endif; ?>
						<?php if ( isset( $current_count ) && isset( $offset ) && intval( $current_count ) < $total ) : ?>
                            <a class="woocommerce-button woocommerce-button--next woocommerce-Button  wlr-cursor wlr-text-color"
                               id="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-next-button' ) ?>"
                               onclick="wlr_jquery( 'body' ).trigger( 'wlr_my_reward_section_pagination', [ 'coupons-expired','<?php echo esc_js( $offset + 1 ); ?>'] )">
								<?php esc_html_e( 'Next', 'wp-loyalty-rules' ); ?>
                            </a>
						<?php endif; ?>
                    </div>
                </div>
            </div>
		<?php endif; ?>
	<?php else: ?>
        <div class="wlr-norecords-container">
            <div><i class="wlrf-used-expired-coupons wlr-text-color"></i></div>
            <div>
                <h4 class="wlr-text-color"><?php esc_html_e( 'Used/Expired Coupons', 'wp-loyalty-rules' ); ?></h4>
            </div>
            <div>
                <p class="wlr-text-color"><?php esc_html_e( "The following are a list of coupons that you've used or got expired.", "wp-loyalty-rules" ); ?></p>
            </div>
        </div>
	<?php endif; ?>
</div>