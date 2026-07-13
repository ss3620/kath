<?php
/**
 * Social Share Addons Core.
 *
 * @package WOPB\SocialShare
 * @since v.3.2.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * SocialShare class.
 */
class SocialShare {

	/**
	 * Setup class.
	 *
	 * @since v.3.2.0
	 */
	public function __construct() {
		$position_type   = wopb_function()->get_setting( 'social_share_position_type' );
		$position_inside = wopb_function()->get_setting( 'social_share_inside_position' );

		if ( $position_type == 'inside' ) {
			if ( $position_inside == 'before_meta' ) {
				add_action( 'woocommerce_product_meta_start', array( $this, 'social_share_content' ) );
			} elseif ( $position_inside == 'after_meta' ) {
				add_action( 'woocommerce_product_meta_end', array( $this, 'social_share_content' ) );
			}
		} elseif ( $position_type == 'sticky' ) {
			add_action( 'wp_footer', array( $this, 'social_share_content' ) );
		}

		// CSS Generator
		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 );
	}

	/**
	 * CSS Generator
	 *
	 * @param $key
	 * @return void
	 * @since v.3.2.0
	 */
	public function generate_css( $key ) {
		if ( $key == 'wopb_social_share' && method_exists( wopb_function(), 'convert_css' ) ) {
			$settings   = wopb_function()->get_setting();
			$item_style = $settings['social_share_typo'];
			if ( $settings['social_share_brand_show'] != 'yes' ) {
				$item_style = array_merge( $settings['social_share_typo'], $settings['social_share_bg'] );
			}

			$css      = '.wopb-social-share-wrapper .wopb-share-items{gap: ' . $settings['social_share_gap'] . 'px;}';
			$css     .= ' ' . ( $settings['social_share_position_type'] == 'inside' ? '.product-template-default' : '' ) . ' .wopb-social-share-wrapper .wopb-share-item a{';
				$css .= wopb_function()->convert_css( 'general', $item_style );
				$css .= 'padding: ' . wopb_function()->convert_css( 'dimension', $settings['social_share_padding'] ) . ';';
				$css .= 'border: ' .
					( ! empty( $settings['social_share_border']['border'] )
						? $settings['social_share_border']['border']
						: 0
					) . 'px solid ' .
					( ! empty( $settings['social_share_border']['color'] )
						? $settings['social_share_border']['color']
						: ''
					) . ';';
				$css .= ! empty( $settings['social_share_radius'] ) ? 'border-radius: ' . $settings['social_share_radius'] . 'px;' : '';
			$css     .= '}';
			$css     .= ' ' . ( $settings['social_share_position_type'] == 'inside' ? '.product-template-default' : '' ) . ' .wopb-social-share-wrapper .wopb-share-item a:hover{';
				$css .= wopb_function()->convert_css( 'hover', $item_style );
			$css     .= '}';
			$css     .= '.wopb-social-share-wrapper .wopb-share-item svg{';
				$css .= 'width : ' . ( ! empty( $settings['social_share_icon_size'] ) ? $settings['social_share_icon_size'] : 20 ) . 'px;';
				$css .= 'height : ' . ( ! empty( $settings['social_share_icon_size'] ) ? $settings['social_share_icon_size'] : 20 ) . 'px;';
			$css     .= '}';

			if ( $settings['social_share_brand_show'] != 'yes' ) {
				$css     .= '.wopb-social-share-wrapper .wopb-share-item svg, .wopb-social-share-wrapper .wopb-share-item svg path{';
					$css .= isset( $settings['social_share_typo']['color'] ) ? 'fill: ' . $settings['social_share_typo']['color'] . ';' : '';
				$css     .= '}';
				$css     .= '.wopb-social-share-wrapper .wopb-share-item svg:hover, .wopb-social-share-wrapper .wopb-share-item svg:hover path{';
					$css .= isset( $settings['social_share_typo']['hover_color'] ) ? 'fill: ' . $settings['social_share_typo']['hover_color'] . ';' : '';
				$css     .= '}';
			}
			wopb_function()->update_css( $key, 'add', $css );
		}
	}

	/**
	 * Social Share Content
	 *
	 * @return NULL
	 * @since v.4.0.0
	 */
	public function social_share_content() {
		$html = '';
		if ( is_product() ) {
			global $product;
			$position   = wopb_function()->get_setting( 'social_share_position_type' );
			$label_show = wopb_function()->get_setting( 'social_share_label_show' );

			$wrapper_class = ' wopb-' . $position;
			if ( $position == 'sticky' ) {
				$wrapper_class .= ' wopb-' . wopb_function()->get_setting( 'social_share_sticky' );
			}
			$total_share = get_post_meta( $product->get_id(), 'wopb_share_count', true );
			$total_share = $total_share ? $total_share : 0;

			$html     .= '<div class="wopb-social-share-wrapper ' . esc_attr( $wrapper_class ) . '">';
				$html .= '<div class="wopb-share-items">';
			if ( wopb_function()->get_setting( 'social_share_count_show' ) == 'yes' ) {
				$html         .= '<span class="wopb-share-count-section">';
					$html     .= '<span class="wopb-share-count-inside">';
						$html .= '<span class="wopb-share-count">' . $total_share . '</span>';
				if ( $label_show == 'yes' ) {
					$html .= wopb_function()->get_setting( 'social_share_count_lvl' );
				}
					$html     .= '</span>';
						$html .= '</span>';
			}
			foreach ( wopb_function()->get_setting( 'social_share_media' ) as $share_item ) {
				$html     .= '<span class="wopb-share-item' . ( wopb_function()->get_setting( 'social_share_brand_show' ) == 'yes' ? ' wopb-' . $share_item['key'] : '' ) . '" postId="' . $product->get_id() . '" count="' . $total_share . '">';
					$html .= '<a href="javascript:" data-url=" ' . $this->share_link( $share_item['key'], get_permalink( $product->get_id() ) ) . '">';
				if ( wopb_function()->get_setting( 'social_share_icon_show' ) == 'yes' ) {
					$html .= wopb_function()->svg_icon( $share_item['key'] );
				}
				if ( $label_show == 'yes' ) {
					$html .= $share_item['label'];
				}
					$html     .= '</a>';
						$html .= '</span>';
			}
				$html .= '</div>';
			$html     .= '</div>';
		}
		echo $html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Social Share Link
	 *
	 * @param $key
	 * @param $post_link
	 * @return string
	 * @since v.4.0.0
	 */
	public function share_link( $key = 'facebook', $post_link = '' ) {
		$shareLink = array(
			'facebook'  => 'http://www.facebook.com/sharer.php?u=' . $post_link,
			'twitter'   => 'http://twitter.com/share?url=' . $post_link,
			'linkedin'  => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $post_link,
			'pinterest' => 'http://pinterest.com/pin/create/link/?url=' . $post_link,
			'whatsapp'  => 'https://wa.me/send?text=' . $post_link,
			'messenger' => 'https://www.facebook.com/dialog/send?app_id=1904103319867886&amp;link=' . $post_link . '&amp;redirect_uri=' . $post_link,
			'mail'      => 'mailto:?body=' . $post_link,
			'reddit'    => 'https://www.reddit.com/submit?url=' . $post_link,
			'skype'     => 'https://web.skype.com/share?url=' . $post_link,
		);
		return $shareLink[ $key ];
	}
}
