<?php
defined( "ABSPATH" ) or die();
$wlrf_earn_campaign_helper               = \Wlr\App\Helpers\EarnCampaign::getInstance();
$wlrf_woocommerce_helper                 = \Wlr\App\Helpers\Woocommerce::getInstance();
$wlrf_theme_color                        = isset( $branding ) && is_array( $branding ) && isset( $branding["theme_color"] ) && ! empty( $branding["theme_color"] ) ? $branding["theme_color"] : "#4F47EB";
$wlrf_button_text_color                  = isset( $branding ) && is_array( $branding ) && isset( $branding["button_text_color"] ) && ! empty( $branding["button_text_color"] ) ? $branding["button_text_color"] : "#ffffff";
$wlrf_endpoint_url                       = isset( $endpoint_url ) && ! empty( $endpoint_url ) ? $endpoint_url : '';
if ( isset( $user_rewards ) && ! empty( $user_rewards ) ):
	$wlrf_button_text = isset( $branding ) && is_array( $branding ) && isset( $branding["apply_coupon_button_text"] ) && ! empty( $branding["apply_coupon_button_text"] ) ? $branding["apply_coupon_button_text"] : "";
	$wlrf_apply_coupon_button_color      = isset( $branding ) && is_array( $branding ) && isset( $branding["apply_coupon_button_color"] ) && ! empty( $branding["apply_coupon_button_color"] ) ? $branding["apply_coupon_button_color"] : "";
	$wlrf_button_color                   = $wlrf_apply_coupon_button_color ? "background:" . $wlrf_apply_coupon_button_color . ";" : "background:" . $wlrf_theme_color . ";";
	$wlrf_apply_coupon_button_text_color = isset( $branding ) && is_array( $branding ) && isset( $branding["apply_coupon_button_text_color"] ) && ! empty( $branding["apply_coupon_button_text_color"] ) ? $branding["apply_coupon_button_text_color"] : "";
	$wlrf_button_text_color              = $wlrf_apply_coupon_button_text_color ? "color:" . $wlrf_apply_coupon_button_text_color . ";" : "";
	$wlrf_apply_coupon_border_color      = isset( $branding ) && is_array( $branding ) && isset( $branding["apply_coupon_border_color"] ) && ! empty( $branding["apply_coupon_border_color"] ) ? $branding["apply_coupon_border_color"] : "";
	$wlrf_coupon_border                  = $wlrf_apply_coupon_border_color ? "border:1px dashed " . $wlrf_apply_coupon_border_color . ";" : "";
	$wlrf_coupon_copy_icon_color         = $wlrf_apply_coupon_border_color ? "background:" . $wlrf_apply_coupon_border_color . ";" : "";
	$wlrf_apply_coupon_background        = isset( $branding ) && is_array( $branding ) && isset( $branding["apply_coupon_background"] ) && ! empty( $branding["apply_coupon_background"] ) ? $branding["apply_coupon_background"] : "";
	$wlrf_coupon_background              = $wlrf_apply_coupon_background ? "background:" . $wlrf_apply_coupon_background . ";" : "";
	$wlrf_css_class_name                 = 'wlr-button-reward-apply wlr-button wlr-button-action';
	$wlrf_page_type_status               = ! ( ( isset( $page_type ) && $page_type == 'cart' ) );
	?>
    <div class="wlr-coupons-list">
        <input type="hidden" id="wlr-endpoint-url" data-url="<?php echo esc_url( $wlrf_endpoint_url ); ?>">
		<?php
		$wlrf_card_key      = 1;
		$wlrf_coupons_count = 0;
		if ( isset( $user_rewards ) && ! empty( $user_rewards ) ):
			foreach ( $user_rewards as $wlrf_u_reward ):
				/* translators: %s: point label */
				$wlrf_revert_button = sprintf( __( 'Revert to %s', 'wp-loyalty-rules' ), $wlrf_earn_campaign_helper->getPointLabel( 3 ) );
				if ( isset( $wlrf_u_reward->discount_code ) && ! empty( $wlrf_u_reward->discount_code ) && isset( $wlrf_u_reward->status ) && ! empty( $wlrf_u_reward->status ) && ! in_array( $wlrf_u_reward->status, array(
						'used',
						'expired'
					) ) ):
					$wlrf_coupons_count ++;
					$wlrf_is_out_of_stock = ( $wlrf_u_reward->discount_type == 'free_product' && isset( $wlrf_u_reward->is_out_of_stock ) && ( $wlrf_u_reward->is_out_of_stock ) );
					?>
                    <div class="wlr-coupons-content <?php echo ( isset( $wlrf_u_reward->discount_code ) && ! empty( $wlrf_u_reward->discount_code ) ) ? 'wlr-new-coupon-card' : ''; ?> wlr-border-color
 <?php if ( $wlrf_is_out_of_stock ) : ?> wlr-out-of-stock <?php endif; ?>" <?php if ( $wlrf_is_out_of_stock ) : ?>   title="<?php echo esc_html( $wlrf_u_reward->out_of_stock_message ); ?>" <?php endif; ?>>
						<?php if ( $wlrf_is_out_of_stock ) : ?>
                            <div class="wlr wlrf-lock wlr-lock-card"></div>
						<?php endif; ?>
                        <div class="wlr-card-container"
                             style="<?php echo ( $wlrf_is_out_of_stock ) ? "opacity:0.6" : ""; ?>">
                            <div class="wlr-coupon-card-header">
                                <div class="wlr-title-icon">
                                    <div class="wlr-card-icon-container">
                                        <div class="wlr-card-icon">
											<?php $wlrf_discount_type = isset( $wlrf_u_reward->discount_type ) && ! empty( $wlrf_u_reward->discount_type ) ? $wlrf_u_reward->discount_type : "" ?>
											<?php $wlrf_img_icon = isset( $wlrf_u_reward->icon ) && ! empty( $wlrf_u_reward->icon ) ? $wlrf_u_reward->icon : "" ?>
											<?php echo wp_kses_post( \Wlr\App\Helpers\Base::setImageIcon( $wlrf_img_icon, $wlrf_discount_type, array( "alt" => $wlrf_u_reward->name ) ) ); ?>
                                        </div>
                                    </div>
                                    <div class="wlr-name-container">
                                        <h4 class="wlr-name wlr-text-color">
											<?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											echo \Wlr\App\Helpers\Base::readMoreLessContent( $wlrf_u_reward->name, $wlrf_card_key, 60, esc_html__( "Show more", "wp-loyalty-rules" ), esc_html__( "Show less", "wp-loyalty-rules" ), 'card-my-reward-name', 'wlr-name wlr-pre-text wlr-text-color' ); ?>
                                        </h4>
                                        <p class="wlr-theme-color-apply">
											<?php echo wp_kses_post( $wlrf_u_reward->reward_type_name ); ?>
											<?php $wlrf_discount_value = isset( $wlrf_u_reward->discount_value ) && ! empty( $wlrf_u_reward->discount_value ) && ( $wlrf_u_reward->discount_value != 0 ) ? ( $wlrf_u_reward->discount_value ) : ''; ?>
											<?php if ( $wlrf_discount_value > 0 && isset( $wlrf_u_reward->discount_type ) && in_array( $wlrf_u_reward->discount_type, array(
													'percent',
													'fixed_cart',
													'points_conversion'
												) ) ): ?>
												<?php if ( ( $wlrf_u_reward->discount_type == 'points_conversion' ) && ! empty( $wlrf_u_reward->discount_code ) ) : ?>
													<?php echo $wlrf_u_reward->coupon_type != 'percent' ? wp_kses_post( " - " . $wlrf_woocommerce_helper->convertPrice( $wlrf_discount_value, true, $wlrf_u_reward->reward_currency ) ) : esc_html( " - " . number_format( $wlrf_discount_value, 2 ) . '%' ); ?>
												<?php elseif ( $wlrf_u_reward->discount_type != 'points_conversion' ): ?>
													<?php echo ( $wlrf_u_reward->discount_type == 'percent' ) ? esc_html( " - " . round( $wlrf_discount_value ) . "%" ) : wp_kses_post( " - " . $wlrf_woocommerce_helper->convertPrice( $wlrf_discount_value, true, $wlrf_u_reward->reward_currency ) ); ?>
												<?php endif; ?>
											<?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="wlr-code-button">
                                    <div class="wlr-code" style="<?php echo esc_attr( $wlrf_coupon_border );
									echo ( $wlrf_is_out_of_stock ) ? "cursor:not-allowed;" : ""; ?>">
                                        <div class="wlr-coupon-code"
                                             style="<?php echo esc_attr( $wlrf_coupon_background ); ?>">
                                            <p title="<?php esc_html_e( 'Coupon Code', 'wp-loyalty-rules' ); ?>"
												<?php if ( ! $wlrf_is_out_of_stock ): ?>
                                                    onclick="wlr_jquery( 'body' ).trigger( 'wlr_copy_coupon',[ '<?php echo esc_js( '#wlr-' . $wlrf_u_reward->discount_code ) ?>','<?php echo esc_js( '#wlr-icon-' . $wlrf_u_reward->discount_code ) ?>'])"
												<?php endif; ?>
                                            >
                                                <span
                                                        style="<?php echo ! empty( $wlrf_apply_coupon_border_color ) ? esc_attr( "color:" . $wlrf_apply_coupon_border_color . ";" ) : ""; ?>"
                                                        id="<?php echo esc_attr( 'wlr-' . $wlrf_u_reward->discount_code ) ?>"><?php echo esc_html( $wlrf_u_reward->discount_code ); ?></span>
                                            </p>
                                        </div>
                                        <div class="wlr-coupon-copy-icon"
                                             style="<?php echo esc_attr( "color:" . $wlrf_apply_coupon_border_color . ";" . $wlrf_coupon_background ); ?>">
                                            <i id="<?php echo esc_attr( 'wlr-icon-' . $wlrf_u_reward->discount_code ) ?>"
                                               class="wlr wlrf-copy wlr-icon"
                                               title="<?php esc_html_e( 'copy to clipboard', 'wp-loyalty-rules' ); ?>"
												<?php if ( ! $wlrf_is_out_of_stock ): ?>
                                                    onclick="wlr_jquery( 'body' ).trigger( 'wlr_copy_coupon',[ '<?php echo esc_js( '#wlr-' . $wlrf_u_reward->discount_code ) ?>','<?php echo esc_js( '#wlr-icon-' . $wlrf_u_reward->discount_code ) ?>'])"
												<?php endif; ?>
                                               style="font-size:20px;"></i>
                                        </div>
                                    </div>

                                    <div class="<?php echo esc_attr( $wlrf_css_class_name ); ?> "
                                         id="<?php echo esc_attr( 'wlr-button-action-' . $wlrf_card_key ); ?>"
                                         style="<?php echo esc_attr( $wlrf_button_color );
									     echo ( $wlrf_is_out_of_stock ) ? "cursor:not-allowed;" : ""; ?>"
										<?php if ( ! $wlrf_is_out_of_stock ): ?>
                                         onclick="wlr_jquery( 'body' ).trigger( 'wlr_apply_reward_action', [ '<?php echo esc_js( $wlrf_u_reward->id ); ?>', '<?php echo esc_js( $wlrf_u_reward->reward_table ); ?>', '<?php echo esc_js( '#wlr-button-action-' . $wlrf_card_key ); ?>',<?php echo esc_js( $wlrf_page_type_status ); ?>,'<?php
										 $wlrf_endpoint_url_with_params = add_query_arg( array( 'active_reward_page' => 'coupons' ), $wlrf_endpoint_url );
										 echo esc_url( $wlrf_endpoint_url_with_params . '#wlr-my-rewards-sections' ) ?>'] )">
										<?php endif;
										?>
                                        <span class="wlr-action-text"
                                              style="<?php echo ( $wlrf_is_out_of_stock ) ? "cursor:not-allowed;" : "";
										      echo esc_attr( $wlrf_button_text_color ); ?>"><?php echo esc_html( $wlrf_button_text ); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="wlr-coupon-card-footer">
                                <div class="wlr-coupon-date-section">
									<?php if ( isset( $wlrf_u_reward->expiry_date ) && ! empty( $wlrf_u_reward->expiry_date ) && ! empty( $wlrf_u_reward->discount_code ) ): ?>
                                        <div class="wlr-flex"><i class="wlrf-clock wlr-text-color"></i>
                                            <p class="wlr-expire-date wlr-text-color">
												<?php /* translators: %s: expired date */
												echo esc_html( sprintf( __( "Expires on %s", "wp-loyalty-rules" ), $wlrf_u_reward->expiry_date ) ); ?></p>
                                        </div>
									<?php endif; ?>
                                </div>
                                <div>
									<?php if ( ! empty( $wlrf_revert_button ) && ( $wlrf_u_reward->reward_type == 'redeem_point' ) && ! empty( $wlrf_u_reward->discount_code ) && isset( $is_revert_enabled ) && $is_revert_enabled ): ?>
                                        <div class="wlr-revert wlr-revert-active wlr-flex"
                                             id="<?php echo esc_attr( 'wlr-' . $wlrf_u_reward->id . '-' . $wlrf_u_reward->discount_code ); ?>"
                                             onclick="wlr_jquery( 'body' ).trigger('wlr_revoke_coupon',['<?php echo esc_js( $wlrf_u_reward->id ); ?>','<?php echo esc_js( $wlrf_u_reward->discount_code ); ?>']);">
                                            <i class="wlrf-refresh_2 wlr-theme-color-apply"></i>
                                            <span
                                                    class="wlr-revert-reward wlr-theme-color-apply"><?php echo esc_html( $wlrf_revert_button ); ?></span>
                                        </div>
									<?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
				<?php
				endif;
				$wlrf_card_key ++;
			endforeach;
			if ( isset( $user_coupon_rewards['coupons_total'] ) && $user_coupon_rewards['coupons_total'] > 0 ):
				$wlrf_endpoint_url = wc_get_endpoint_url( 'loyalty_reward' ); ?>
                <div class="wlr-coupon-pagination">
                    <div>
                        <div style="text-align: right">
							<?php if ( isset( $user_coupon_rewards['offset'] ) && 1 !== (int) $user_coupon_rewards['offset'] ) : ?>
                                <a class="woocommerce-button woocommerce-button--previous woocommerce-Button wlr-cursor wlr-text-color"
                                   onclick="wlr_jquery( 'body' ).trigger( 'wlr_my_rewards_pagination',['coupons','<?php echo esc_js( $user_coupon_rewards['offset'] - 1 ); ?>','<?php echo esc_js( $page_type ); ?>'])"
                                   id="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-prev-button' ) ?>">
									<?php esc_html_e( 'Prev', 'wp-loyalty-rules' ); ?>
                                </a>
							<?php endif; ?>
							<?php if ( isset( $user_coupon_rewards['current_coupon_count'] ) && intval( $user_coupon_rewards['current_coupon_count'] ) < $user_coupon_rewards['coupons_total'] ) : ?>
                                <a class="woocommerce-button woocommerce-button--next woocommerce-Button  wlr-cursor wlr-text-color"
                                   id="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-next-button' ) ?>"
                                   onclick="wlr_jquery( 'body' ).trigger( 'wlr_my_rewards_pagination', ['coupons','<?php echo esc_js( $user_coupon_rewards['offset'] + 1 ); ?>','<?php echo esc_js( $page_type ); ?>'])">
									<?php esc_html_e( 'Next', 'wp-loyalty-rules' ); ?>
                                </a>
							<?php endif; ?>
                        </div>
                    </div>
                </div>
			<?php endif;
		endif;
		if ( empty( $wlrf_coupons_count ) ):?>
            <div class="wlr-norecords-container">
                <div><i class="wlrf-coupon-empty wlr-text-color"></i></div>
                <div>
                    <h4 class="wlr-text-color"><?php esc_html_e( 'Transform your points into savings! Convert to coupons now.', 'wp-loyalty-rules' ); ?></h4>
                </div>
                <div>
                    <p class="wlr-text-color"><?php esc_html_e( "Maximize the value of your earned points by converting them into discount coupons. Make your shopping experience even more rewarding!", "wp-loyalty-rules" ); ?></p>
                </div>
            </div>
		<?php endif; ?>
    </div>
<?php endif; ?>
