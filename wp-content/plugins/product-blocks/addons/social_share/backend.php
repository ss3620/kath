<?php
defined( 'ABSPATH' ) || exit;

/**
 * Social Share Addons Initial Configuration
 *
 * @since v.3.2.0
 */
add_filter( 'wopb_addons_config', 'wopb_social_share_init' );
function wopb_social_share_init( $config ) {
	$configuration               = array(
		'name'     => __( 'Quick Social Share', 'product-blocks' ),
		'desc'     => __( 'Display social share icons and let your shoppers share products with their social profiles instantly.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://wpxpo.com/docs/wowstore/add-ons/quick-social-share/', // live link missing
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/quick-social-share/',
		'type'     => 'exclusive',
		'priority' => 20,
	);
	$config['wopb_social_share'] = $configuration;
	return $config;
}

/**
 * Social Share Addons Default Settings
 *
 * @since v.3.2.0
 * @param ARRAY | Default Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_social_share_opt', 10, 1 );
function get_social_share_opt( $config ) {
	$is_active = wopb_function()->get_setting( 'is_lc_active' );
	$arr       = array(
		'wopb_social_share' => array(
			'label' => __( 'Social Share Settings', 'product-blocks' ),
			'attr'  => array(
				'tab' => array(
					'type'    => 'tab',
					'options' => array(
						'settings' => array(
							'label' => __( 'Settings', 'product-blocks' ),
							'attr'  => array(
								'wopb_social_share' => array(
									'type'  => 'toggle',
									'value' => 'false',
									'label' => __( 'Enable Social Share', 'product-blocks' ),
									'desc'  => __( 'Enable social share on your website', 'product-blocks' ),
								),
								'container_1'       => array(
									'type' => 'container',
									'attr' => array(
										'social_share_media' => array(
											'type'    => 'select_item',
											'label'   => __( 'Select Share Item', 'product-blocks' ),
											'desc'    => __( 'Select the item you want to display. To rearrange these fields, you can also drag and drop. You can also customize the label name according to your preferences. Mail, WhatsApp, and Messenger are Pro features. You need to upgrade to Pro.', 'product-blocks' ),
											'options' => array(
												array(
													'key' => '',
													'label' => __( 'Select Item', 'product-blocks' ),
												),
												array(
													'key' => 'facebook',
													'label' => __( 'Facebook', 'product-blocks' ),
												),
												array(
													'key' => 'twitter',
													'label' => __( 'Twitter', 'product-blocks' ),
												),
												array(
													'key' => 'linkedin',
													'label' => __( 'LinkedIn', 'product-blocks' ),
												),
												array(
													'key' => 'skype',
													'label' => __( 'Skype', 'product-blocks' ),
												),
												array(
													'key' => 'pinterest',
													'label' => __( 'Pinterest', 'product-blocks' ),
												),
												array(
													'key' => 'reddit',
													'label' => __( 'Reddit', 'product-blocks' ),
												),
												array(
													'key' => 'mail',
													'pro' => true,
													'label' => __( 'Mail', 'product-blocks' ),
												),
												array(
													'key' => 'whatsapp',
													'pro' => true,
													'label' => __( 'WhatsApp', 'product-blocks' ),
												),
												array(
													'key' => 'messenger',
													'pro' => true,
													'label' => __( 'Messenger', 'product-blocks' ),
												),
											),
											'default' => array(
												array(
													'key' => 'facebook',
													'label' => 'Facebook',
												),
												array(
													'key' => 'twitter',
													'label' => 'Twitter',
												),
												array(
													'key' => 'skype',
													'label' => 'Skype',
												),
												array(
													'key' => 'linkedin',
													'label' => 'LinkedIn',
												),
												array(
													'key' => 'pinterest',
													'label' => 'Pinterest',
												),
											),
										),
										'social_share_icon_show' => array(
											'type'    => 'toggle',
											'label'   => __( 'Show Icon', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Show Default Icon with the Button.', 'product-blocks' ),
										),
										'social_share_label_show' => array(
											'type'    => 'toggle',
											'label'   => __( 'Show Label', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Show Default Label with the Button.', 'product-blocks' ),
										),
										'social_share_count_show' => array(
											'type'    => 'toggle',
											'label'   => __( 'Show Share Count', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Show Share Count in the Social Share.', 'product-blocks' ),
										),
										'social_share_count_lvl' => array(
											'type'    => 'text',
											'label'   => __( 'Share Count Label', 'product-blocks' ),
											'default' => 'Share',
											'depends' => array(
												array(
													'key' => 'social_share_label_show',
													'condition' => '==',
													'value' => 'yes',
												),
												array(
													'key' => 'social_share_count_show',
													'condition' => '==',
													'value' => 'yes',
												),
											),
										),
										'social_share_position_type' => array(
											'type'    => 'radio',
											'label'   => __( 'Position Type', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'inside' => __( 'Inside Element', 'product-blocks' ),
												'sticky' => __( 'Sticky', 'product-blocks' ),
											),
											'default' => 'inside',
										),
										'social_share_inside_position' => array(
											'type'    => 'radio',
											'label'   => __( 'Inside Position', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'before_meta' => __( 'Before Meta', 'product-blocks' ),
												'after_meta' => __( 'After Meta', 'product-blocks' ),
											),
											'default' => 'after_meta',
											'depends' => array(
												'key'   => 'social_share_position_type',
												'condition' => '==',
												'value' => 'inside',
											),
										),
										'social_share_sticky' => array(
											'type'    => 'radio',
											'label'   => __( 'Sticky Position', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'left'  => __( 'Left', 'product-blocks' ),
												'right' => __( 'Right', 'product-blocks' ),
											),
											'default' => 'left',
											'depends' => array(
												'key'   => 'social_share_position_type',
												'condition' => '==',
												'value' => 'sticky',
											),
										),
									),
								),
							),
						),
						'design'   => array(
							'label' => __( 'Design', 'product-blocks' ),
							'attr'  => array(
								'container_2' => array(
									'type' => 'container',
									'attr' => array(
										'social_share_brand_show' => array(
											'type'    => 'toggle',
											'label'   => __( 'Show Brand Color', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Show Brand Color in the Button.', 'product-blocks' ),
										),
										'social_share_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#ffffff',
												'hover_color' => '',
											),
										),
										'social_share_icon_size' => array(
											'type'    => 'number',
											'label'   => __( 'Icon Size', 'product-blocks' ),
											'default' => 20,
										),
										'social_share_bg'  => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Background Color', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '#000000',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
											'depends' => array(
												'key'   => 'social_share_brand_show',
												'condition' => '==',
												'value' => '',
											),
										),
										'social_share_padding' => array(
											'type'    => 'dimension',
											'label'   => __( 'Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 5,
												'bottom' => 5,
												'left'   => 10,
												'right'  => 10,
											),
										),
										'social_share_border' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'social_share_radius' => array(
											'type'       => 'number',
											'plus_minus' => true,
											'label'      => __( 'Border Radius', 'product-blocks' ),
											'default'    => 3,
										),
										'social_share_gap' => array(
											'type'       => 'number',
											'plus_minus' => true,
											'label'      => __( 'Button Gap Between', 'product-blocks' ),
											'default'    => 10,
										),
									),
								),
							),
						),
					),
				),
			),
		),
	);
	return array_merge( $config, $arr );
}
