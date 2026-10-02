<?php

namespace RevenuePro;

defined( 'ABSPATH' ) || exit;

/**
 * Registers storefront assets owned by Pro campaigns.
 */
class Revenue_Pro_Assets {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'revenue_enqueue_campaign_assets', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_builder_styles' ), 20 );
	}

	/**
	 * Load Pro campaign styles on the Free builder screen, which previews Pro campaign markup.
	 */
	public function enqueue_builder_styles() {
		if ( ! wp_style_is( 'revx-campaign', 'enqueued' ) ) {
			return;
		}

		$handles = array(
			'revenue-campaign-double_order',
			'revenue-campaign-spending_goal',
			'revenue-campaign-fbt',
			'revenue-campaign-mix_match',
		);

		foreach ( $handles as $handle ) {
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Enqueue Pro campaign assets when Free renders a campaign.
	 *
	 * @param string $campaign_type Campaign type currently being rendered.
	 */
	public function enqueue_assets( $campaign_type = '' ) {
		$assets = array(
			'double_order'               => array(
				'scripts' => array( 'revenue-double-order' ),
				'styles'  => array( 'revenue-campaign-double_order' ),
			),
			'spending_goal'              => array(
				'scripts' => array( 'revenue-spending-goal' ),
				'styles'  => array( 'revenue-campaign-spending_goal' ),
			),
			'frequently_bought_together' => array(
				'scripts' => array( 'revenue-frequently-bought-together' ),
				'styles'  => array( 'revenue-campaign-fbt' ),
			),
			'mix_match'                  => array(
				'scripts' => array( 'revenue-mix-match' ),
				'styles'  => array( 'revenue-campaign-mix_match' ),
			),
		);

		if ( ! isset( $assets[ $campaign_type ] ) ) {
			return;
		}

		foreach ( $assets[ $campaign_type ]['scripts'] as $script ) {
			wp_enqueue_script( $script );
		}

		foreach ( $assets[ $campaign_type ]['styles'] as $style ) {
			wp_enqueue_style( $style );
		}
	}

	/**
	 * Register Pro campaign scripts and styles under their existing handles.
	 */
	public function register_assets() {
		wp_register_script(
			'revenue-spending-goal',
			REVENUE_PRO_URL . 'assets/js/frontend/spending-goal.js',
			array( 'jquery', 'wp-i18n', 'revenue-campaign' ),
			REVENUE_PRO_VER,
			array( 'strategy' => 'defer' )
		);
		wp_register_script(
			'revenue-double-order',
			REVENUE_PRO_URL . 'assets/js/frontend/double_order.js',
			array( 'jquery', 'revenue-utils', 'revenue-checkbox-handler' ),
			REVENUE_PRO_VER,
			array( 'strategy' => 'defer' )
		);
		wp_register_script(
			'revenue-frequently-bought-together',
			REVENUE_PRO_URL . 'assets/js/frontend/frequently-bought-together.js',
			array(
				'jquery',
				'revenue-campaign',
				'revenue-add-to-cart',
				'revenue-variation-product-selection',
				'revenue-checkbox-handler',
				'revenue-campaign-total',
				'revenue-popup',
				'revenue-floating',
			),
			REVENUE_PRO_VER,
			array( 'strategy' => 'defer' )
		);
		wp_register_script(
			'revenue-mix-match',
			REVENUE_PRO_URL . 'assets/js/frontend/mix-match.js',
			array( 'jquery', 'revenue-campaign', 'revenue-add-to-cart', 'revenue-popup', 'revenue-floating' ),
			REVENUE_PRO_VER,
			array( 'strategy' => 'defer' )
		);

		$styles = array(
			'revenue-campaign-double_order'  => 'assets/css/frontend/campaign/double_order.css',
			'revenue-campaign-spending_goal' => 'assets/css/frontend/campaign/spending_goal.css',
			'revenue-campaign-fbt'           => 'assets/css/frontend/campaign/frequently_bought_together.css',
			'revenue-campaign-mix_match'     => 'assets/css/frontend/campaign/mix_match.css',
		);

		foreach ( $styles as $handle => $path ) {
			wp_register_style( $handle, REVENUE_PRO_URL . $path, array(), REVENUE_PRO_VER );
		}

		wp_set_script_translations( 'revenue-spending-goal', 'revenue', REVENUE_PATH . 'languages' );
	}
}
