<?php
class WC_Zip_Controller_Abstract_Controller {

	protected $WC_Zipmoney_Payment_Gateway;
	protected $WC_Zipmoney_Payment_Gateway_Config;

	public function __construct( WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway ) {
		$this->WC_Zipmoney_Payment_Gateway        = $WC_Zipmoney_Payment_Gateway;
		$this->WC_Zipmoney_Payment_Gateway_Config = new WC_Zipmoney_Payment_Gateway_Config( $this->WC_Zipmoney_Payment_Gateway );
	}
}
