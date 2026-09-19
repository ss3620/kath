<?php
defined( "ABSPATH" ) or die();
$wlrf_earn_campaign_helper = \Wlr\App\Helpers\EarnCampaign::getInstance();
$wlrf_woocommerce_helper   = \Wlr\App\Helpers\Woocommerce::getInstance();
$wlrf_theme_color          = isset( $branding ) && is_array( $branding ) && isset( $branding["theme_color"] ) && ! empty( $branding["theme_color"] ) ? $branding["theme_color"] : "#4F47EB";
$wlrf_button_text_color    = isset( $branding ) && is_array( $branding ) && isset( $branding["button_text_color"] ) && ! empty( $branding["button_text_color"] ) ? $branding["button_text_color"] : "#ffffff";
$wlrf_is_right_to_left     = is_rtl();
if ( isset( $user_rewards ) && ! empty( $user_rewards ) ):
	$wlrf_button_text = isset( $branding ) && is_array( $branding ) && isset( $branding["redeem_button_text"] ) && ! empty( $branding["redeem_button_text"] ) ? $branding["redeem_button_text"] : "";
	/* translators: %s: point label */
	$wlrf_revert_button            = sprintf( __( 'Revert to %s', 'wp-loyalty-rules' ), $wlrf_earn_campaign_helper->getPointLabel( 3 ) );
	$wlrf_redeem_button_color      = isset( $branding ) && is_array( $branding ) && isset( $branding["redeem_button_color"] ) && ! empty( $branding["redeem_button_color"] ) ? $branding["redeem_button_color"] : "";
	$wlrf_button_color             = $wlrf_redeem_button_color ? "background:" . $wlrf_redeem_button_color . ";" : "background:" . $wlrf_theme_color . ";";
	$wlrf_redeem_button_text_color = isset( $branding ) && is_array( $branding ) && isset( $branding["redeem_button_text_color"] ) && ! empty( $branding["redeem_button_text_color"] ) ? $branding["redeem_button_text_color"] : "";
	$wlrf_button_text_color        = $wlrf_redeem_button_text_color ? "color:" . $wlrf_redeem_button_text_color . ";" : "";
	$wlrf_css_class_name           = 'wlr-button-reward wlr-button wlr-button-action';
	$wlrf_page_type                = ! ( ( isset( $page_type ) && $page_type == 'cart' ) );
	?>
    <div class="wlr-customer-reward">
		<?php
		$wlrf_card_key     = 1;
		$wlrf_reward_count = 0;
		foreach ( $user_rewards as $wlrf_u_reward ):
			if ( ! isset( $wlrf_u_reward->discount_code ) || empty( $wlrf_u_reward->discount_code ) ):
				$wlrf_reward_count ++;
				$wlrf_is_out_of_stock = ( $wlrf_u_reward->discount_type == 'free_product' && isset( $wlrf_u_reward->is_out_of_stock ) && ( $wlrf_u_reward->is_out_of_stock ) );
				?>
                <div
                        class="wlr-rewards-content wlr-reward-card wlr-border-color <?php if ( $wlrf_is_out_of_stock ): ?> wlr-out-of-stock <?php endif; ?>"
					<?php if ( $wlrf_is_out_of_stock ) : ?>   title="<?php echo esc_html( $wlrf_u_reward->out_of_stock_message ); ?>" <?php endif; ?> >

                    <div
                            style="<?php echo $wlrf_is_right_to_left ? "margin-left: -12px;" : "margin-right: -12px;"; ?>">
                        <p class="wlr-reward-type-name wlr-text-color wlr-border-color">
							<?php echo wp_kses_post( $wlrf_u_reward->reward_type_name );
							?>
							<?php $wlrf_discount_value = isset( $wlrf_u_reward->discount_value ) && ! empty( $wlrf_u_reward->discount_value ) && ( $wlrf_u_reward->discount_value != 0 ) ? ( $wlrf_u_reward->discount_value ) : ''; ?>
							<?php if ( $wlrf_discount_value > 0 && isset( $wlrf_u_reward->discount_type ) && in_array( $wlrf_u_reward->discount_type, array(
									'percent',
									'fixed_cart',
									'points_conversion'
								) ) ): ?>
								<?php if ( ( $wlrf_u_reward->discount_type == 'points_conversion' ) && ! empty( $wlrf_u_reward->discount_code ) ) : ?>
									<?php echo wp_kses_post( " - " . $wlrf_woocommerce_helper->getCustomPrice( $wlrf_discount_value ) ); ?>
								<?php elseif ( $wlrf_u_reward->discount_type != 'points_conversion' ): ?>
									<?php echo ( $wlrf_u_reward->discount_type == 'percent' ) ? esc_html( " - " . round( $wlrf_discount_value ) . "%" ) : wp_kses_post( " - " . $wlrf_woocommerce_helper->getCustomPrice( $wlrf_discount_value ) ); ?>
								<?php endif; ?>
							<?php endif; ?>
                        </p>
                    </div>
                    <div class="wlr-card-container">
                        <div class="wlr-card-icon-container" <?php if ( $wlrf_is_out_of_stock ) : ?>
                            style="display: flex;align-items: center;justify-content: space-between;"<?php endif; ?>>
                            <div class="wlr-card-icon">
								<?php $wlrf_discount_type = isset( $wlrf_u_reward->discount_type ) && ! empty( $wlrf_u_reward->discount_type ) ? $wlrf_u_reward->discount_type : "" ?>
								<?php $wlrf_img_icon = isset( $wlrf_u_reward->icon ) && ! empty( $wlrf_u_reward->icon ) ? $wlrf_u_reward->icon : "" ?>
								<?php echo wp_kses_post( \Wlr\App\Helpers\Base::setImageIcon( $wlrf_img_icon, $wlrf_discount_type, array( "alt" => $wlrf_u_reward->name ) ) );
								?>
                            </div>
							<?php if ( $wlrf_is_out_of_stock ) : ?>
                                <div class="wlr wlrf-lock wlr-lock-card" style="position: relative"></div>
							<?php endif; ?>
                        </div>

                        <div class="wlr-card-inner-container">
                            <h4 class="wlr-name wlr-text-color">
								<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo \Wlr\App\Helpers\Base::readMoreLessContent( $wlrf_u_reward->name, $wlrf_card_key, 60, esc_html__( "Show more", "wp-loyalty-rules" ), esc_html__( "Show less", "wp-loyalty-rules" ), 'card-my-reward-name', 'wlr-name wlr-pre-text wlr-text-color' );
								?>
                            </h4>
							<?php
							// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Existing public WPLoyalty hook retained because customers may use it.
							$wlrf_description = apply_filters( 'wlr_my_account_reward_desc', $wlrf_u_reward->description, $wlrf_u_reward ); ?>
							<?php if ( ! empty( $wlrf_description ) && ! ( isset( $wlrf_u_reward->discount_code ) && ! empty( $wlrf_u_reward->discount_code ) ) ): ?>
								<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo \Wlr\App\Helpers\Base::readMoreLessContent( $wlrf_description, $wlrf_card_key, 90, esc_html__( "Show more", "wp-loyalty-rules" ), esc_html__( "Show less", "wp-loyalty-rules" ), 'card-my-reward-description', 'wlr-description wlr-pre-text wlr-text-color' ); ?>
							<?php endif; ?>
							<?php if ( isset( $wlrf_u_reward->discount_type ) && $wlrf_u_reward->discount_type == 'free_product' ):
								if ( isset( $wlrf_u_reward->is_stock_empty_products ) && ! empty( $wlrf_u_reward->is_stock_empty_products ) && is_array( $wlrf_u_reward->is_stock_empty_products ) ):
									?>
                                    <div style="display: flex;flex-direction: column;gap: 3px;">
                                        <b><?php esc_html_e( 'Out of Stock:', 'wp-loyalty-rules' ); ?></b>
										<?php foreach ( $wlrf_u_reward->is_stock_empty_products as $wlrf_s_product ): ?>
                                            <small><?php echo wp_kses_post( $wlrf_s_product['product_name'] ); ?></small>
										<?php endforeach; ?>
                                    </div>
								<?php endif;endif; ?>

                        </div>


						<?php if ( isset( $wlrf_u_reward->discount_type ) && $wlrf_u_reward->discount_type == 'points_conversion' && $wlrf_u_reward->reward_table != 'user_reward' ): ?>
                            <div style="display: none;"
                                 class="wlr-point-conversion-section wlr-border-color"
                                 id="<?php echo esc_attr( 'wlr_point_conversion_div_' . $wlrf_u_reward->id ); ?>">
                                <div><i class="wlrf-close wlr-cursor wlr-text-color"
                                        title="<?php esc_html_e( 'Close', 'wp-loyalty-rules' ); ?>"
                                        onclick="wlr_jquery('<?php echo esc_js( '#wlr_point_conversion_div_' . $wlrf_u_reward->id ); ?>').hide();wlr_jquery('<?php echo esc_js( '#wlr-button-action-' . $wlrf_card_key ) ?>').show();">
                                    </i></div>
                                <div style="display: flex;gap: 15%"
                                     id="<?php echo esc_attr( 'wlr-point-conversion-section-' . $wlrf_u_reward->id ) ?>">
                                    <div class="wlr-input-point-section">
                                        <div
                                                class="wlr-input-point-conversion wlr-border-color">
                                            <input type="text" min="1" pattern="/^[0-9]+$/"
                                                   class="wlr-point-conversion-box wlr-text-color"
                                                   onkeypress="return wlr_jquery('body').trigger('wlr_validate_number');"
                                                   onchange="wlr_jquery('body').trigger('wlr_calculate_point_conversion',
                                                           ['<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_u_reward->id ); ?>','<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_u_reward->id . '_value' ); ?>']);"
                                                   onkeyup="wlr_jquery('body').trigger('wlr_calculate_point_conversion',
                                                           ['<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_u_reward->id ); ?>','<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_u_reward->id . '_value' ); ?>']);"
                                                   id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_u_reward->id ); ?>"
                                                   value="<?php echo esc_attr( $wlrf_u_reward->input_point ); ?>"
                                                   oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*)\./g, '$1');"
                                                   data-require-point="<?php echo esc_attr( $wlrf_u_reward->require_point ); ?>"
                                                   data-discount-value="<?php echo ( $wlrf_u_reward->coupon_type == 'percent' ) ? esc_attr( $wlrf_u_reward->discount_value ) : esc_attr( $wlrf_woocommerce_helper->getCustomPrice( $wlrf_u_reward->discount_value, false ) ); ?>"
                                                   data-available-point="<?php echo esc_attr( $wlrf_u_reward->available_point ); ?>"
                                                   data-cart-amount="<?php echo esc_attr( $wlrf_u_reward->cart_amount ); ?>"
                                                   data-max-allowed-point="<?php echo esc_attr( $wlrf_u_reward->max_allowed_point ); ?>"
                                                   data-min-allowed-point="<?php echo esc_attr( $wlrf_u_reward->min_allowed_point ); ?>"
                                                   data-max-message="<?php echo esc_attr( $wlrf_u_reward->max_message ); ?>"
                                                   data-min-message="<?php echo esc_attr( $wlrf_u_reward->min_message ); ?>"
                                                   data-button-id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_u_reward->id . '_button' ); ?>"
                                                   data-section-id="<?php echo esc_attr( 'wlr-point-conversion-section-' . $wlrf_u_reward->id ); ?>"
                                                   data-is-max-changed="<?php echo esc_attr( $wlrf_u_reward->is_max_changed ); ?>"
                                            ></div>
                                        <div class="wlr-point-label-content wlr-border-color">
                                            <p class="wlr-input-point-title wlr-text-color">
												<?php
												if ( $wlrf_u_reward->coupon_type == 'percent' ) :
													echo "=";
													?>
                                                    <span
                                                            id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_u_reward->id . '_value' ); ?>"
                                                            class="wlr-point-conversion-discount-label">
                                                    <?php echo esc_html( $wlrf_u_reward->input_value ); ?>
                                                </span>%
												<?php
												else:
													$wlrf_woocommerce_currency = $wlrf_woocommerce_helper->getDisplayCurrency();
													echo wp_kses_post( sprintf( '=(%s)%s', $wlrf_woocommerce_currency, $wlrf_woocommerce_helper->getCurrencySymbols( $wlrf_woocommerce_currency ) ) ); ?>
                                                    &nbsp;<span
                                                        id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_u_reward->id . '_value' ); ?>"
                                                        class="wlr-point-conversion-discount-label">
                                                    <?php echo esc_html( $wlrf_u_reward->input_value ); ?>
                                                </span>
												<?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                        id="<?php echo esc_attr( 'wlr_point_conversion_' . $wlrf_u_reward->id . '_button' ); ?>"
                                        class="wlr-button wlr-button-action"
                                        style="<?php echo esc_attr( $wlrf_button_color ); ?>"
                                        onclick="wlr_jquery( 'body' ).trigger( 'wlr_apply_point_conversion_reward',['<?php echo esc_js( $wlrf_u_reward->id ); ?>','<?php echo esc_js( $wlrf_u_reward->reward_table ); ?>','<?php echo esc_js( $wlrf_u_reward->available_point ); ?>','<?php echo esc_js( '#wlr_point_conversion_' . $wlrf_u_reward->id ); ?>' ,'<?php echo esc_js( '#wlr_point_conversion_' . $wlrf_u_reward->id . '_button' ); ?>','<?php echo esc_js( $wlrf_page_type ); ?>','<?php
										$wlrf_endpoint_url             = wc_get_endpoint_url( 'loyalty_reward' );
										$wlrf_endpoint_url_with_params = add_query_arg( array( 'active_reward_page' => 'coupons' ), $wlrf_endpoint_url );
										echo esc_url( $wlrf_endpoint_url_with_params . '#wlr-my-rewards-sections' ) ?>'] );">
                                            <span class="wlr-action-text"
                                                  style="<?php echo esc_attr( $wlrf_button_text_color ); ?>"><?php echo esc_html__( 'Redeem', 'wp-loyalty-rules' ); ?></span>
                                </div>
                            </div>
                            <div class="<?php echo esc_attr( $wlrf_css_class_name ); ?>"
                                 style="<?php echo esc_attr( $wlrf_button_color ); ?>"
                                 id="<?php echo esc_attr( 'wlr-button-action-' . $wlrf_card_key ) ?>"
                                 onclick="wlr_jquery('<?php echo esc_js( '#wlr_point_conversion_div_' . $wlrf_u_reward->id ); ?>').show();wlr_jquery('<?php echo esc_js( '#wlr-button-action-' . $wlrf_card_key ) ?>').hide();wlr_jquery('body').trigger('wlr_calculate_point_conversion',
                                         ['<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_u_reward->id ); ?>','<?php echo esc_js( 'wlr_point_conversion_' . $wlrf_u_reward->id . '_value' ); ?>']);">
                                        <span class="wlr-action-text"
                                              style="<?php echo esc_attr( $wlrf_button_text_color ); ?>"><?php echo esc_html( $wlrf_button_text ); ?></span>
                            </div>
						<?php else: ?>

                            <div class="<?php echo esc_attr( $wlrf_css_class_name ); ?> "
                                 id="<?php echo esc_attr( 'wlr-button-action-' . $wlrf_card_key ); ?>"
                                 style="<?php echo esc_attr( $wlrf_button_color ); ?><?php if ( $wlrf_u_reward->discount_type == 'free_product' && isset( $wlrf_u_reward->is_out_of_stock ) && ( $wlrf_u_reward->is_out_of_stock ) ): ?>
                                         cursor: not-allowed;opacity: 0.6;<?php endif; ?>"
								<?php if ( $wlrf_u_reward->discount_type == 'free_product' && isset( $wlrf_u_reward->is_out_of_stock ) && ! ( $wlrf_u_reward->is_out_of_stock ) || $wlrf_u_reward->discount_type != 'free_product' ): ?>
                                    onclick="wlr_jquery( 'body' ).trigger( 'wlr_apply_reward_action', [ '<?php echo esc_js( $wlrf_u_reward->id ); ?>', '<?php echo esc_js( $wlrf_u_reward->reward_table ); ?>', '<?php echo esc_js( '#wlr-button-action-' . $wlrf_card_key ); ?>','<?php echo esc_js( $wlrf_page_type ); ?>','<?php
									$wlrf_endpoint_url             = wc_get_endpoint_url( 'loyalty_reward' );
									$wlrf_endpoint_url_with_params = add_query_arg( array( 'active_reward_page' => 'coupons' ), $wlrf_endpoint_url );
									echo esc_url( $wlrf_endpoint_url_with_params . '#wlr-my-rewards-sections' ) ?>'] )"
								<?php endif; ?>
                            >
                                        <span class="wlr-action-text"
                                              style="<?php echo esc_attr( $wlrf_button_text_color ); ?>"><?php echo esc_html( $wlrf_button_text ); ?></span>
                            </div>

						<?php endif; ?>


                    </div>
                </div>
			<?php
			endif;
			$wlrf_card_key ++;
		endforeach; ?>
    </div>
	<?php if ( empty( $wlrf_reward_count ) ): ?>
    <div class="wlr-customer-reward" style="display: flex;align-items: center;justify-content: center;">
        <div class="wlr-rewards-content wlr-norecords-container active">
            <div><i class="wlrf-reward-empty-hand wlr-text-color"></i></div>
            <div>
                <h4 class="wlr-text-color"><?php
					/* translators: %s: reward label */
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
<?php endif; ?>