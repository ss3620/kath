<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Checkout_Login {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'blockId'               => '',
			'toggleColor'           => '#2c2c2c',
			'linkColor'             => '#007cba',
			'toggleHoverColor'      => '#2c2c2c',
			'toggleBg'              => '#f4f4f4',
			'toggleTypo'            => (object) array(
				'openTypography' => 1,
				'size'           => (object) array(
					'lg'   => '14',
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => '',
					'unit' => 'px',
				),
				'decoration'     => 'none',
				'transform'      => '',
			),
			'toggleBorder'          => (object) array(
				'openBorder' => 1,
				'width'      => (object) array(
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				),
				'color'      => '#d8d8d8',
				'type'       => 'solid',
			),
			'toggleRadius'          => (object) array(
				'lg' => (object) array(
					'top'    => 4,
					'right'  => 4,
					'bottom' => 4,
					'left'   => 4,
					'unit'   => 'px',
				),
			),
			'togglePadding'         => (object) array(
				'lg' => (object) array(
					'top'    => 10,
					'right'  => 10,
					'bottom' => 10,
					'left'   => 10,
					'unit'   => 'px',
				),
			),
			'toggleSpace'           => '10',
			'labelColor'            => '#343434',
			'requiredIconColor'     => '#f41e1e',
			'labelTypo'             => (object) array(
				'openTypography' => 1,
				'size'           => (object) array(
					'lg'   => '14',
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => '',
					'unit' => 'px',
				),
				'decoration'     => 'none',
				'transform'      => '',
			),
			'labelMargin'           => '5',
			'inputHeight'           => '',
			'inputColor'            => '#000',
			'placeholderColor'      => '#898989',
			'inputBgColor'          => '#ffffff',
			'inputTypo'             => (object) array(
				'openTypography' => 1,
				'size'           => (object) array(
					'lg'   => '14',
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => '',
					'unit' => 'px',
				),
				'decoration'     => 'none',
				'transform'      => '',
			),
			'inputBorder'           => (object) array(
				'openBorder' => 1,
				'width'      => (object) array(
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				),
				'color'      => '#e0e0e0',
				'type'       => 'solid',
			),
			'inputRadius'           => (object) array(
				'lg' => (object) array(
					'top'    => 4,
					'right'  => 4,
					'bottom' => 4,
					'left'   => 4,
					'unit'   => 'px',
				),
			),
			'inputMargin'           => (object) array(
				'lg' => (object) array(
					'top'    => 0,
					'right'  => 0,
					'bottom' => 10,
					'left'   => 0,
					'unit'   => 'px',
				),
			),
			'inputFocusColor'       => '#000',
			'placeholderFocusColor' => '#000',
			'btnTypo'               => (object) array(
				'openTypography' => 1,
				'size'           => (object) array(
					'lg'   => '16',
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => '',
					'unit' => 'px',
				),
				'decoration'     => 'none',
				'transform'      => '',
			),
			'btnColor'              => '#fff',
			'btnBg'                 => '#007cba',
			'btnBorder'             => (object) array(
				'openBorder' => 1,
				'width'      => (object) array(
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				),
				'color'      => '#000',
				'type'       => 'solid',
			),
			'btnRadius'             => (object) array(
				'lg' => (object) array(
					'top'    => 0,
					'right'  => 0,
					'bottom' => 0,
					'left'   => 0,
					'unit'   => 'px',
				),
			),
			'btnPadding'            => (object) array(
				'lg' => (object) array(
					'top'    => 6,
					'right'  => 24,
					'bottom' => 6,
					'left'   => 24,
					'unit'   => 'px',
				),
			),
			'descpColor'            => '#343434',
			'descpTypo'             => (object) array(
				'openTypography' => 1,
				'size'           => (object) array(
					'lg'   => '14',
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => '',
					'unit' => 'px',
				),
				'decoration'     => 'none',
				'transform'      => '',
			),
			'descpMargin'           => '5',
			'checkboxColor'         => '#343434',
			'checkboxTypo'          => (object) array(
				'openTypography' => 1,
				'size'           => (object) array(
					'lg'   => '14',
					'unit' => 'px',
				),
				'height'         => (object) array(
					'lg'   => '',
					'unit' => 'px',
				),
				'decoration'     => 'none',
				'transform'      => '',
			),
			'checkboxMargin'        => '10',
			'containerBg'           => '#ffffff',
			'containerBorder'       => (object) array(
				'openBorder' => 1,
				'width'      => (object) array(
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				),
				'color'      => '#d8d8d8',
				'type'       => 'solid',
			),
			'containerRadius'       => (object) array(
				'lg' => (object) array(
					'top'    => 4,
					'right'  => 4,
					'bottom' => 4,
					'left'   => 4,
					'unit'   => 'px',
				),
			),
			'containerPadding'      => (object) array(
				'lg' => (object) array(
					'top'    => 15,
					'right'  => 15,
					'bottom' => 15,
					'left'   => 15,
					'unit'   => 'px',
				),
			),
			'advanceId'             => '',
			'advanceZindex'         => '',
			'wrapMargin'            => (object) array(
				'lg' => (object) array(
					'top'    => '',
					'bottom' => '',
					'unit'   => 'px',
				),
			),
			'wrapOuterPadding'      => (object) array(
				'lg' => (object) array(
					'top'    => '',
					'bottom' => '',
					'left'   => '',
					'right'  => '',
					'unit'   => 'px',
				),
			),
			'wrapBg'                => (object) array(
				'openColor' => 0,
				'type'      => 'color',
				'color'     => '#f5f5f5',
			),
			'wrapBorder'            => (object) array(
				'openBorder' => 0,
				'width'      => (object) array(
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				),
				'color'      => '#009fd4',
				'type'       => 'solid',
			),
			'wrapShadow'            => (object) array(
				'openShadow' => 0,
				'width'      => (object) array(
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				),
				'color'      => '#009fd4',
			),
			'wrapRadius'            => (object) array(
				'lg'   => '',
				'unit' => 'px',
			),
			'wrapHoverBackground'   => (object) array(
				'openColor' => 0,
				'type'      => 'color',
				'color'     => '#FF176B',
			),
			'wrapHoverBorder'       => (object) array(
				'openBorder' => 0,
				'width'      => (object) array(
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				),
				'color'      => '#009fd4',
				'type'       => 'solid',
			),
			'wrapHoverRadius'       => (object) array(
				'lg'   => '',
				'unit' => 'px',
			),
			'wrapHoverShadow'       => (object) array(
				'openShadow' => 0,
				'width'      => (object) array(
					'top'    => 1,
					'right'  => 1,
					'bottom' => 1,
					'left'   => 1,
				),
				'color'      => '#009fd4',
			),
			'wrapInnerPadding'      => (object) array( 'lg' => (object) array( 'unit' => 'px' ) ),
			'hideExtraLarge'        => false,
			'hideDesktop'           => false,
			'hideTablet'            => false,
			'hideMobile'            => false,
			'advanceCss'            => '',
			'currentPostId'         => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/checkout-login',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax = false ) {
		if ( is_checkout() ) {
			$block_name    = 'checkout-login';
			$wraper_before = $wraper_after = $content = '';
			$attr          = wp_parse_args( $attr, $this->get_attributes() );

			if ( function_exists( 'WC' ) ) {
				$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

				if ( ! is_admin() ) {
					if ( isset( WC()->customer ) ) {
						$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . $block_name . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
							$wraper_before .= '<div class="wopb-product-wrapper">';

							ob_start();
							require_once WOPB_PATH . 'blocks/woo/checkout/login/Template.php';
							$content .= ob_get_clean();

							$wraper_after .= '</div> ';
						$wraper_after     .= '</div> ';
					}
				}
			}

			return $wraper_before . $content . $wraper_after;
		}
	}
}
