<?php
defined( 'ABSPATH' ) || exit;

class ProductXBeaverTemplate extends FLBuilderModule {

	public function __construct() {
		parent::__construct(
			array(
				'name'            => __( 'WowStore Template', 'product-blocks' ),
				'description'     => __( 'An basic example module using jQuery TwentyTwenty.', 'product-blocks' ),
				'category'        => __( 'Basic', 'product-blocks' ),
				'dir'             => __DIR__,
				'partial_refresh' => true,
				'url'             => plugins_url( '', __FILE__ ),
			)
		);
	}
}


FLBuilder::register_module(
	'ProductXBeaverTemplate',
	array(
		'general' => array(
			'title'    => __( 'General', 'product-blocks' ),
			'sections' => array(
				'general' => array(
					'title'  => __( 'Template Settings', 'product-blocks' ),
					'fields' => array(
						'template'         => array(
							'type'         => 'select',
							'label'        => __( 'Select Your Template', 'product-blocks' ),
							'default'      => '',
							'options'      => wopb_function()->get_all_lists( 'wopb_templates' ),
							'multi-select' => false,
						),
						'edit_template'    => array(
							'type'    => 'raw',
							'label'   => __( 'Edit Template', 'product-blocks' ),
							'content' => '<a href="' . admin_url( 'edit.php?post_type=wopb_templates' ) . '" style="color:#fff; background-color:#0c0d0e; padding:10px 20px; border-radius:4px; display:inline-block;" target="_blank"><span style="color:#fff; font-size:12px; width:12px; height:12px;" class="dashicons dashicons-edit"></span> ' . esc_html__( 'Edit This Template', 'product-blocks' ) . '</a>',
						),
						'add_new_template' => array(
							'type'    => 'raw',
							'label'   => 'Add New Template',
							'content' => '<a href="' . admin_url( 'post-new.php?post_type=wopb_templates' ) . '" style="color:#fff; background-color:#0c0d0e; padding:10px 20px; border-radius:4px; display:inline-block;" target="_blank"><span style="color:#fff; font-size:12px; width:12px; height:12px;" class="dashicons dashicons-plus-alt2"></span> ' . esc_html__( 'Add New Template', 'product-blocks' ) . '</a>',
						),
					),
				),
			),
		),
	)
);
