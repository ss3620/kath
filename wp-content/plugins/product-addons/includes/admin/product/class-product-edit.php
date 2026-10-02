<?php //phpcs:ignore
namespace PRAD\Includes\Admin\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Notice
 */
class ProductEdit {

	/**
	 * Notice Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'prad_product_custom_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'prad_tab_data' ) );
	}

	/**
	 * WowAddons Tab in Single Product Edit Page
	 *
	 * @param array $tabs Single Product Page Tabs.
	 * @return array Updated Tabs.
	 */
	public function prad_product_custom_tab( $tabs ) {
		$tabs['prad_tab'] = array(
			'label'    => 'WowAddons',
			'priority' => 15,
			'target'   => 'prad_tab_data',
			'class'    => array( 'hide_if_grouped' ),
		);

		return $tabs;
	}

	/**
	 * WowAddons Custom Tab Data.
	 *
	 * @return void
	 */
	public function prad_tab_data() {
		global $post;
		$product_id = $post->ID;
		?>
		<div class="panel woocommerce_options_panel" id="prad_tab_data" style="padding: 20px !important;">
		<div id="prad-product-edit-wrap" data-product-id="<?php echo esc_attr( $product_id ); ?>"></div>
		</div>
		<?php
	}
}
