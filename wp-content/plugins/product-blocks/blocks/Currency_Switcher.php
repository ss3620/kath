<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Currency_Switcher {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'currencySymbolPosition' => 'leftDollar',
			'countryNameShow'        => true,
			'showFlag'               => true,
			'prefixText'             => 'Currency Switcher',
			'currentPostId'          => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/currency-switcher',
			array(
				'editor_script'   => 'wopb-blocks-editor-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	/**
	 * This
	 *
	 * @return terminal
	 */
	public function content( $attr, $noAjax = false ) {
		$wraper_before = '';
		$block_name    = 'currency-switcher';
		$attr          = wp_parse_args( $attr, $this->get_attributes() );

		$allowed_html_tags  = wopb_function()->allowed_html_tags();
		$attr['className']  = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['prefixText'] = ! empty( $attr['prefixText'] ) ? wp_kses( $attr['prefixText'], $allowed_html_tags ) : '';

		$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . ( isset( $attr['className'] ) ? $attr['className'] : '' ) . '">';
			$wraper_before .= '<div class="wopb-block-wrapper">';

		if ( wopb_function()->get_setting( 'wopb_currency_switcher' ) === 'true' && wopb_function()->get_setting( 'is_lc_active' ) ) {
			$currency_code_options = get_woocommerce_currencies();
			foreach ( wopb_function()->get_setting( 'wopb_currencies' ) as $key => $currency ) {
				if ( $attr['currencySymbolPosition'] == 'leftDollar' ) {
					$added_currency[ $currency['wopb_currency'] ] = '( ' . get_woocommerce_currency_symbol( $currency['wopb_currency'] ) . ' ) ' . $currency_code_options[ $currency['wopb_currency'] ] . ' ' . $currency['wopb_currency'];
				} elseif ( $attr['currencySymbolPosition'] == 'rightDollar' ) {
					$added_currency[ $currency['wopb_currency'] ] = $currency_code_options[ $currency['wopb_currency'] ] . '( ' . get_woocommerce_currency_symbol( $currency['wopb_currency'] ) . ' ) ' . ' ' . $currency['wopb_currency'];
				}
			}
			$wopb_current_currency = array_key_exists( wopb_function()->get_setting( 'wopb_current_currency' ), $added_currency ) ? wopb_function()->get_setting( 'wopb_current_currency' ) : wopb_function()->get_setting( 'wopb_default_currency' );
			$wraper_before        .= '<div class="wopb-currency-switcher-prefixText">' . $attr['prefixText'] . '</div>';

			$wraper_before     .= '<div class="wopb-currency-switcher-container">';
				$wraper_before .= '<div class="wopb-selected-currency-container">';
			if ( $attr['countryNameShow'] ) {
				$wraper_before .= '<div class="wopb-selected-currency" value="">' . ( $attr['showFlag'] ? '<img src="' . esc_url( 'https://raw.githubusercontent.com/wpxpo/wpxpo_profile/main/country_flags/' . strtolower( $wopb_current_currency ) . '.png' ) . '" alt="flag"> ' : '' ) . $added_currency[ $wopb_current_currency ] . '</div>';
			} else {
				$Code = '/\b[A-Z]{3}\b/';
				if ( preg_match( $Code, $added_currency[ $wopb_current_currency ], $currencyMatches ) ) {
					$wraper_before .= '<div class="wopb-selected-currency" value="">' . ( $attr['showFlag'] ? '<img src="' . esc_url( 'https://raw.githubusercontent.com/wpxpo/wpxpo_profile/main/country_flags/' . strtolower( $wopb_current_currency ) . '.png' ) . '" alt="flag"> ' : '' ) . $currencyMatches[0] . '</div>';
				}
			}
					$wraper_before .= isset( $added_currency ) && count( $added_currency ) > 1 ? '<div class="wopb-currency-arrow"></div>' : '';

				$wraper_before .= '</div>';

			if ( isset( $added_currency ) && count( $added_currency ) > 1 ) {
						$wraper_before     .= '<div name="wopb_current_currency" class="wopb-set-default-currency" style="display:none">';
							$wraper_before .= '<ul class="wopb-select-container" >';
				foreach ( $added_currency as $key => $label ) {
					if ( $attr['countryNameShow'] ) {
						$wraper_before .= '<li class="' . ( $wopb_current_currency == $key ? 'hide-currency' : '' ) . '" value="' . esc_attr( $key ) . '">' . ( $attr['showFlag'] ? '<img src="' . esc_url( 'https://raw.githubusercontent.com/wpxpo/wpxpo_profile/main/country_flags/' . strtolower( $key ) . '.png' ) . '" alt="flag"> ' : '' ) . wp_strip_all_tags( esc_html( $label ) ) . '</li>';
					} else {
						$currency = '/\( ([^)]+) \)/';
						if ( preg_match( $currency, $label, $currencyMatches ) ) {
							$wraper_before .= '<li class="' . ( $wopb_current_currency == $key ? 'hide-currency' : '' ) . '" value="' . esc_attr( $key ) . '">' . ( $attr['showFlag'] ? '<img  src="' . esc_url( 'https://raw.githubusercontent.com/wpxpo/wpxpo_profile/main/country_flags/' . strtolower( $key ) . '.png' ) . '" alt="flag"> ' : '' ) . wp_strip_all_tags( $currencyMatches[0] . ' ' . $key ) . '</li>';
						}
					}
				}
							$wraper_before .= '</ul>';
						$wraper_before     .= '</div>';
			}
							$wraper_before .= '</div>';
		} else {
			$wraper_before .= '<div class="wopb-currency-switcher-container-pro-message"><a href="https://www.wpxpo.com/product/wowstore/features/woocommerce-currency-switcher/?utm_source=db-wstore-editor&utm_medium=currency_switcher&utm_campaign=wstore-dashboard">Enable Currency Switcher Addon ( Pro ) to use this block.</a><span >This is <a href="https://www.wpxpo.com/product/wowstore/#pricing">Pro</a> feature, if you want to use it, please upgrade to the pro version and also need to enable Currency Switcher Addon from WowStore Dashbord Addon pages </span></div>';
		}

			$wraper_before .= '</div>';
		$wraper_before     .= '</div>';
		return $wraper_before;
	}
}
