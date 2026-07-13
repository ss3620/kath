<?php
namespace RevenuePro;

/**
 * RevenueX Campaign
 *
 * @hooked on init
 */
class Revenue_Pro_Campaign {

	public function __construct() {

		add_filter( 'revenue_campaign_instance', array( $this, 'set_campaign_instance' ), 10, 2 );
		add_filter( 'revenue_campaign_file_path', array( $this, 'set_shortcode_pro_file_path' ), 10, 2 );
	}

	public function set_shortcode_pro_file_path( $file_path, $campaign_type ) {
		switch ( $campaign_type ) {
			case 'mix_match':
			case 'frequently_bought_together':
			case 'double_order':
			case 'spending_goal':
				$file_path = REVENUE_PRO_PATH;
				break;

			default:
				// code...
				break;
		}

		return $file_path;
	}


	public function set_campaign_instance( $class, $type ) {

		switch ( $type ) {
			case 'mix_match':
				$class = Revenue_Mix_Match::instance();
				break;
			case 'frequently_bought_together':
				$class = Revenue_Frequently_Bought_Together::instance();
				break;
			case 'double_order':
				$class = Revenue_Double_Order::instance();
				break;
			case 'spending_goal':
				$class = Revenue_Spending_Goal::instance();
				break;
		}

		return $class;
	}
}
