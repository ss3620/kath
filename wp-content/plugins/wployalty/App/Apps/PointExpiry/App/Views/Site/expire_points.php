<?php
/**
 * @author      Wployalty (Ilaiyaraja)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.wployalty.net
 * */
defined( "ABSPATH" ) or die();
$wlrf_earn_campaign_helper = \Wlr\App\Helpers\EarnCampaign::getInstance();
$wlrf_woocommerce_helper   = new \Wlr\App\Helpers\Woocommerce();
if ( isset( $expire_details ) && is_array( $expire_details ) && isset( $expire_details['expire_points'] ) && ! empty( $expire_details['expire_points'] ) ): ?>
    <div class="wlr-expire-point-blog" id="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-expire-point-details-table' ); ?>"
         style="width: 100%;">
        <div class="wlr-heading-container">
            <h3 class="wlr-heading"><?php /* translators: %s point label */
				echo esc_html( sprintf( __( 'Upcoming %s expiration', 'wp-loyalty-rules' ), $wlrf_earn_campaign_helper->getPointLabel( 3 ) ) ); ?></h3>
        </div>
        <div id="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-expire-point-table' ) ?>">
            <table class="wlr-table" style="width: 100%;">
                <thead id="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-expire-point-table-header' ) ?>"
                       class="wlr-table-header">
                <tr>
                    <th class=" wlr-text-color"
                        style="width: 50%;"><?php /* translators: 1: point label 2: point label */
						echo esc_html( sprintf( __( '%1$s available / %2$s earned', 'wp-loyalty-rules' ), ucfirst( $wlrf_earn_campaign_helper->getPointLabel( 3 ) ), ucfirst( $wlrf_earn_campaign_helper->getPointLabel( 3 ) ) ) ); ?></th>
                    <th class="set-center wlr-text-color"
                        style="width: 50%;"><?php echo esc_html__( 'Expires on', 'wp-loyalty-rules' ) ?></th>
                </tr>
                </thead>
				<?php foreach ( $expire_details['expire_points'] as $wlrf_expire_point ): ?>
                    <tr>
                        <td style="width: 50%;"
                            class="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-transaction-table-body' ) ?> wlr-text-color wlr-border-color">
							<?php $wlrf_available_point = isset( $wlrf_expire_point->available_points ) && ! empty( $wlrf_expire_point->available_points ) ? $wlrf_expire_point->available_points : '0';
							$wlrf_point                 = isset( $wlrf_expire_point->points ) && ! empty( $wlrf_expire_point->points ) ? $wlrf_expire_point->points : '0';
							echo esc_html( sprintf( '%s / %s', $wlrf_available_point, $wlrf_point ) ); ?>
                        </td>
                        <td style="width: 50%;"
                            class="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-transaction-table-body' ) ?> set-center wlr-text-color wlr-border-color">
							<?php echo isset( $wlrf_expire_point->expire_date ) && ! empty( $wlrf_expire_point->expire_date ) ? esc_html( $wlrf_woocommerce_helper->beforeDisplayDate( $wlrf_expire_point->expire_date ) ) : '-'; ?>
                        </td>
                    </tr>
				<?php endforeach; ?>
            </table>
			<?php if ( isset( $expire_details['expire_points_total'] ) && $expire_details['expire_points_total'] > 0 ):
				$wlrf_endpoint_url = wc_get_endpoint_url( 'loyalty_reward' );
				?>
                <div style="text-align: right">
					<?php if ( isset( $expire_details['offset'] ) && 1 !== (int) $expire_details['offset'] ) :
						$wlrf_endpoint_url_with_params = add_query_arg( array( 'expire_point_page' => $expire_details['offset'] - 1 ), $wlrf_endpoint_url ); ?>
                        <a class="woocommerce-button woocommerce-button--previous woocommerce-Button wlr-cursor wlr-text-color"
                           id="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-prev-button' ) ?>"
                           onclick="wlr_jquery( 'body' ).trigger( 'wlr_redirect_url', [ '<?php echo esc_url( $wlrf_endpoint_url_with_params . '#wlr-expire-point-details-table' ) ?>'] )">
							<?php esc_html_e( 'Prev', 'wp-loyalty-rules' ); ?>
                        </a>
					<?php endif; ?>
					<?php if ( isset( $expire_details['current_point_expire_count'] ) && intval( $expire_details['current_point_expire_count'] ) < $expire_details['expire_points_total'] ) :
						$wlrf_endpoint_url_with_params = add_query_arg( array( 'expire_point_page' => $expire_details['offset'] + 1 ), $wlrf_endpoint_url );
						?>
                        <a class="woocommerce-button woocommerce-button--next woocommerce-Button wlr-cursor wlr-text-color"
                           id="<?php echo esc_attr( WLR_PLUGIN_PREFIX . '-next-button' ) ?>"
                           onclick="wlr_jquery( 'body' ).trigger( 'wlr_redirect_url', [ '<?php echo esc_url( $wlrf_endpoint_url_with_params . '#wlr-expire-point-details-table' ) ?>'] )">
							<?php esc_html_e( 'Next', 'wp-loyalty-rules' ); ?>
                        </a>
					<?php endif; ?>
                </div>
			<?php endif; ?>
        </div>
    </div>
<?php endif; ?>
