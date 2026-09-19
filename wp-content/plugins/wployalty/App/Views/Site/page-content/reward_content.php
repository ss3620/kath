<?php

use Wlr\App\Helpers\EarnCampaign;
use Wlr\App\Helpers\Settings;

defined( "ABSPATH" ) or die();
$wlrf_earn_campaign_helper = EarnCampaign::getInstance();
$wlrf_woocommerce_helper   = \Wlr\App\Helpers\Woocommerce::getInstance();
$wlrf_is_right_to_left     = is_rtl();
?>
<?php if ( ! empty( $items ) ): ?>
	<?php $wlrf_theme_color        = Settings::get( 'theme_color', '#4F47EB' );
	//phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
	$wlrf_button_text              = __( Settings::get( 'redeem_button_text', 'Redeem Now' ), 'wp-loyalty-rules' );
	$wlrf_redeem_button_color      = Settings::get( 'redeem_button_color', '#4F47EB' );
	$wlrf_button_color             = $wlrf_redeem_button_color ? "background:" . $wlrf_redeem_button_color . ";" : "background:" . $wlrf_theme_color . ";";
	$wlrf_redeem_button_text_color = Settings::get( 'redeem_button_text_color', '#ffffff' );
	$wlrf_button_text_color        = $wlrf_redeem_button_text_color ? "color:" . $wlrf_redeem_button_text_color . ";" : "";
	$wlrf_css_class_name           = 'wlr-button-reward wlr-button wlr-button-action';
	?>
    <div class="wlr-customer-reward">
		<?php $wlrf_card_key = 1; ?>
		<?php foreach ( $items as $wlrf_item ): ?>
			<?php
			$wlrf_is_out_of_stock = ( $wlrf_item->discount_type == 'free_product' && isset( $wlrf_item->is_out_of_stock ) && ( $wlrf_item->is_out_of_stock ) ); ?>
            <div class="wlr-rewards-content wlr-reward-card wlr-border-color <?php echo esc_attr( ! empty( $wlrf_item->discount_type ) ? $wlrf_item->discount_type : '' ); ?> <?php if ( $wlrf_is_out_of_stock ): ?> wlr-out-of-stock <?php endif; ?>"
				<?php if ( $wlrf_is_out_of_stock ) : ?>   title="<?php echo esc_html( $wlrf_item->out_of_stock_message ); ?>" <?php endif; ?> >

                <div style="<?php echo $wlrf_is_right_to_left ? "margin-left: -12px;" : "margin-right: -12px;"; ?>">
                    <p class="wlr-reward-type-name wlr-text-color wlr-border-color">
						<?php echo wp_kses_post( $wlrf_item->reward_type_name ); ?>
						<?php $wlrf_discount_value = ! empty( $wlrf_item->discount_value ) && ( $wlrf_item->discount_value != 0 ) ? ( $wlrf_item->discount_value ) : ''; ?>
						<?php if ( $wlrf_discount_value > 0 && isset( $wlrf_item->discount_type ) && in_array( $wlrf_item->discount_type, [
								'percent',
								'fixed_cart',
								'points_conversion'
							] ) ): ?>
							<?php if ( ( $wlrf_item->discount_type == 'points_conversion' ) && ! empty( $wlrf_item->discount_code ) ) : ?>
								<?php echo wp_kses_post( " - " . $wlrf_woocommerce_helper->getCustomPrice( $wlrf_discount_value ) ); ?>
							<?php elseif ( $wlrf_item->discount_type != 'points_conversion' ): ?>
								<?php echo ( $wlrf_item->discount_type == 'percent' ) ? esc_html( " - " . round( $wlrf_discount_value ) . "%" ) : wp_kses_post( " - " . $wlrf_woocommerce_helper->getCustomPrice( $wlrf_discount_value ) ); ?>
							<?php endif; ?>
						<?php endif; ?>
                    </p>
                </div>
                <div class="wlr-card-container">
                    <div class="wlr-card-icon-container" <?php if ( $wlrf_is_out_of_stock ) : ?>
                        style="display: flex;align-items: center;justify-content: space-between;"<?php endif; ?>>
                        <div class="wlr-card-icon">
							<?php $wlrf_discount_type = ! empty( $wlrf_item->discount_type ) ? $wlrf_item->discount_type : "" ?>
							<?php $wlrf_img_icon = ! empty( $wlrf_item->icon ) ? $wlrf_item->icon : "" ?>
							<?php echo wp_kses_post( \Wlr\App\Helpers\Base::setImageIcon( $wlrf_img_icon, $wlrf_discount_type, array( "alt" => $wlrf_item->name ) ) ); ?>
                        </div>
						<?php if ( $wlrf_is_out_of_stock ) : ?>
                            <div class="wlr wlrf-lock wlr-lock-card" style="position: relative"></div>
						<?php endif; ?>
                    </div>

                    <div class="wlr-card-inner-container">
                        <h4 class="wlr-name wlr-text-color">
							<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo \Wlr\App\Helpers\Base::readMoreLessContent( $wlrf_item->name, $wlrf_card_key, 60, esc_html__( "Show more", "wp-loyalty-rules" ), esc_html__( "Show less", "wp-loyalty-rules" ), 'card-my-reward-name', 'wlr-name wlr-pre-text wlr-text-color' ); ?>
                        </h4>
						<?php
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Existing public WPLoyalty hook retained because customers may use it.
						$wlrf_description = apply_filters( 'wlr_my_account_reward_desc', $wlrf_item->description, $wlrf_item ); ?>
						<?php if ( ! empty( $wlrf_description ) && empty( $wlrf_item->discount_code ) ): ?>
							<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo \Wlr\App\Helpers\Base::readMoreLessContent( $wlrf_description, $wlrf_card_key, 90, esc_html__( "Show more", "wp-loyalty-rules" ), esc_html__( "Show less", "wp-loyalty-rules" ), 'card-my-reward-description', 'wlr-description wlr-pre-text wlr-text-color' ); ?>
						<?php endif; ?>
						<?php if ( isset( $wlrf_item->discount_type ) && $wlrf_item->discount_type == 'free_product' ):
							if ( ! empty( $wlrf_item->is_stock_empty_products ) && is_array( $wlrf_item->is_stock_empty_products ) ):
								?>
                                <div style="display: flex;flex-direction: column;gap: 3px;">
                                    <b><?php esc_html_e( 'Out of Stock:', 'wp-loyalty-rules' ); ?></b>
									<?php foreach ( $wlrf_item->is_stock_empty_products as $wlrf_s_product ): ?>
                                        <small><?php echo wp_kses_post( $wlrf_s_product['product_name'] ); ?></small>
									<?php endforeach; ?>
                                </div>
							<?php endif;endif; ?>
                    </div>


					<?php if ( isset( $wlrf_item->discount_type ) && $wlrf_item->discount_type == 'points_conversion' && $wlrf_item->reward_table != 'user_reward' ): ?>
                        <div style="display: none;"
                             class="wlr-point-conversion-section wlr-border-color"
                             id="<?php echo esc_attr( 'wlr_point_conversion_div_' . $wlrf_item->id ); ?>">
                            <div><i class="wlrf-close wlr-cursor wlr-text-color"
                                    title="<?php esc_html_e( 'Close', 'wp-loyalty-rules' ); ?>"
                                    onclick="wlr_jquery('<?php echo esc_js( '#wlr_point_conversion_div_' . $wlrf_item->id ); ?>').hide();wlr_jquery('<?php echo esc_js( '#wlr-button-action-' . $wlrf_card_key ) ?>').show();">
                                </i></div>
                            <div style="display: flex;gap: 15%"
                                 id="<?php echo esc_attr( 'wlr-point-conversion-section-' . $wlrf_item->id ); ?>">
                                <div class="wlr-input-point-section">
                                    <div
                                            class="wlr-input-point-conversion wlr-border-color">
                                        <input type="text" min="1" pattern="/^[0-9]+$/"
                                               class="wlr-point-conversion-box wlr-text-color"
                                               onkeypress="return wlr_jquery('body').trigger('wlr_validate_number');"
                                               onchange="wlr_jquery('body').trigger('wlr_calculate_point_conversion',
                                                       ['<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_item->id ); ?>','<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_item->id . '_value' ); ?>']);"
                                               onkeyup="wlr_jquery('body').trigger('wlr_calculate_point_conversion',
                                                       ['<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_item->id ); ?>','<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_item->id . '_value' ); ?>']);"
                                               id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_item->id ); ?>"
                                               value="<?php echo esc_attr( $wlrf_item->input_point ); ?>"
                                               oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*)\./g, '$1');"
                                               data-require-point="<?php echo esc_attr( $wlrf_item->require_point ); ?>"
                                               data-discount-value="<?php echo ( $wlrf_item->coupon_type == 'percent' ) ? esc_attr( $wlrf_item->discount_value ) : esc_attr( $wlrf_woocommerce_helper->getCustomPrice( $wlrf_item->discount_value, false ) ); ?>"
                                               data-available-point="<?php echo esc_attr( $wlrf_item->available_point ); ?>"
                                               data-cart-amount="<?php echo esc_attr( $wlrf_item->cart_amount ); ?>"
                                               data-max-allowed-point="<?php echo esc_attr( $wlrf_item->max_allowed_point ); ?>"
                                               data-min-allowed-point="<?php echo esc_attr( $wlrf_item->min_allowed_point ); ?>"
                                               data-max-message="<?php echo esc_attr( $wlrf_item->max_message ); ?>"
                                               data-min-message="<?php echo esc_attr( $wlrf_item->min_message ); ?>"
                                               data-button-id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_item->id . '_button' ); ?>"
                                               data-section-id="<?php echo esc_attr( 'wlr-point-conversion-section-' . $wlrf_item->id ); ?>"
                                               data-is-max-changed="<?php echo esc_attr( $wlrf_item->is_max_changed ); ?>"
                                        ></div>
                                    <div class="wlr-point-label-content wlr-border-color">
                                        <p class="wlr-input-point-title wlr-text-color">
											<?php
											if ( $wlrf_item->coupon_type == 'percent' ) :
												echo "=";
												?>
                                                <span
                                                        id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_item->id . '_value' ); ?>"
                                                        class="wlr-point-conversion-discount-label">
                                                    <?php echo esc_html( $wlrf_item->input_value ); ?>
                                                </span>%
											<?php
											else:
												$wlrf_woocommerce_currency = $wlrf_woocommerce_helper->getDisplayCurrency();
												echo wp_kses_post( sprintf( '=(%s)%s', $wlrf_woocommerce_currency, $wlrf_woocommerce_helper->getCurrencySymbols( $wlrf_woocommerce_currency ) ) ); ?>
                                                &nbsp;<span
                                                    id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_item->id . '_value' ); ?>"
                                                    class="wlr-point-conversion-discount-label">
                                                    <?php echo esc_html( $wlrf_item->input_value ); ?>
                                                </span>
											<?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                    id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_item->id . '_button' ); ?>"
                                    class="wlr-button wlr-button-action"
                                    style="<?php echo esc_attr( $wlrf_button_color ); ?>"
                                    onclick="wlr_jquery( 'body' ).trigger( 'wlr_apply_point_conversion_reward',['<?php echo esc_js( $wlrf_item->id ); ?>','<?php echo esc_js( $wlrf_item->reward_table ); ?>','<?php echo esc_js( $wlrf_item->available_point ); ?>','<?php echo esc_js( '#wlr_point_conversion_' . $wlrf_item->id ); ?>' ,'<?php echo esc_js( '#wlr_point_conversion_' . $wlrf_item->id . '_button' ); ?>','<?php echo esc_js( $page_type ); ?>','<?php
									$wlrf_endpoint_url_with_params = add_query_arg( array( 'active_reward_page' => 'coupons' ), $endpoint_url );
									echo esc_url( $wlrf_endpoint_url_with_params . '#wlr-my-rewards-sections' ) ?>'] );">
                                            <span class="wlr-action-text"
                                                  style="<?php echo esc_attr( $wlrf_button_text_color ); ?>"><?php echo esc_html__( 'Redeem', 'wp-loyalty-rules' ); ?></span>
                            </div>
                        </div>
                        <div class="<?php echo esc_attr( $wlrf_css_class_name ); ?>"
                             style="<?php echo esc_attr( $wlrf_button_color ); ?>"
                             id="<?php echo esc_attr( 'wlr-button-action-' . $wlrf_card_key ) ?>"
                             onclick="wlr_jquery('<?php echo esc_js( '#wlr_point_conversion_div_' . $wlrf_item->id ); ?>').show();wlr_jquery('<?php echo esc_js( '#wlr-button-action-' . $wlrf_card_key ) ?>').hide();wlr_jquery('body').trigger('wlr_calculate_point_conversion',
                                     ['<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_item->id ); ?>','<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_item->id . '_value' ); ?>']);">
                                        <span class="wlr-action-text"
                                              style="<?php echo esc_attr( $wlrf_button_text_color ); ?>"><?php echo esc_html( $wlrf_button_text ); ?></span>
                        </div>
					<?php else: ?>

                        <div class="<?php echo esc_attr( $wlrf_css_class_name ); ?> "
                             id="<?php echo esc_attr( 'wlr-button-action-' . $wlrf_card_key ); ?>"
                             style="<?php echo esc_attr( $wlrf_button_color ); ?><?php if ( $wlrf_item->discount_type == 'free_product' && isset( $wlrf_item->is_out_of_stock ) && ( $wlrf_item->is_out_of_stock ) ): ?>
                                     cursor: not-allowed;opacity: 0.6;<?php endif; ?>"
							<?php if ( $wlrf_item->discount_type == 'free_product' && isset( $wlrf_item->is_out_of_stock ) && ! ( $wlrf_item->is_out_of_stock ) || $wlrf_item->discount_type != 'free_product' ): ?>
                                onclick="wlr_jquery( 'body' ).trigger( 'wlr_apply_reward_action', [ '<?php echo esc_js( $wlrf_item->id ); ?>', '<?php echo esc_js( $wlrf_item->reward_table ); ?>', '<?php echo esc_js( '#wlr-button-action-' . $wlrf_card_key ); ?>','<?php echo esc_js( $page_type ); ?>','<?php
								$wlrf_endpoint_url_with_params = add_query_arg( array( 'active_reward_page' => 'coupons' ), $endpoint_url );
								echo esc_url( $wlrf_endpoint_url_with_params . '#wlr-my-rewards-sections' ) ?>'] )"
							<?php endif; ?>
                        >
                                        <span class="wlr-action-text"
                                              style="<?php echo esc_attr( $wlrf_button_text_color ); ?>"><?php echo esc_html( $wlrf_button_text ); ?></span>
                        </div>

					<?php endif; ?>


                </div>
            </div>
			<?php $wlrf_card_key ++; ?>
		<?php endforeach; ?>
    </div>
    <div>
		<?php if ( isset( $total ) && $total > 0 ): ?>
            <div class="wlr-reward-pagination">
                <div>
                    <div style="text-align: right">
						<?php if ( isset( $offset ) && 1 !== (int) $offset ) : ?>
                            <a class="woocommerce-button woocommerce-button--previous woocommerce-Button wlr-cursor wlr-text-color"
                               onclick="wlr_jquery( 'body' ).trigger( 'wlr_my_reward_section_pagination',['rewards','<?php echo esc_js( $offset - 1 ); ?>','<?php echo esc_js( $page_type ); ?>'])"
                               id="<?php echo esc_js( WLR_PLUGIN_PREFIX . '-prev-button' ); ?>">
								<?php esc_html_e( 'Prev', 'wp-loyalty-rules' ); ?>
                            </a>
						<?php endif; ?>
						<?php if ( isset( $current_count ) && intval( $current_count ) < $total ) : ?>
                            <a class="woocommerce-button woocommerce-button--next woocommerce-Button  wlr-cursor wlr-text-color"
                               id="<?php echo esc_js( WLR_PLUGIN_PREFIX . '-next-button' ); ?>"
                               onclick="wlr_jquery( 'body' ).trigger( 'wlr_my_reward_section_pagination', ['rewards','<?php echo esc_js( $offset + 1 ); ?>','<?php echo esc_js( $page_type ); ?>'])">
								<?php esc_html_e( 'Next', 'wp-loyalty-rules' ); ?>
                            </a>
						<?php endif; ?>
                    </div>
                </div>
            </div>
		<?php endif; ?>
    </div>
<?php else: ?>
    <div class="wlr-customer-reward" style="display: flex;align-items: center;justify-content: center;">
        <div class="wlr-rewards-content wlr-norecords-container active">
            <div><i class="wlrf-reward-empty-hand wlr-text-color"></i></div>
            <div>
                <h4 class="wlr-text-color"><?php /* translators: %s: reward label */
					echo esc_html( sprintf( __( 'Begin Your %s Journey!', 'wp-loyalty-rules' ), ucfirst( $wlrf_earn_campaign_helper->getRewardLabel( 3 ) ) ) ); ?></h4>
            </div>
            <div>
                <p class="wlr-text-color"><?php /* translators: %s: reward label */
					echo esc_html( sprintf( __( "Shop more and unlock amazing %s! Discover all the opportunities below!", "wp-loyalty-rules" ),
						$wlrf_earn_campaign_helper->getRewardLabel( 3 ) ) ); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

