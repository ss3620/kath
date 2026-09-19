<?php

use zipMoney\Model\CurrencyUtil;

class WC_Zipmoney_Payment_Gateway_Config {

	const PLATFORM = 'Woocommerce';
	const CLIENT   = 'WooCommerce Zip Payment API';

	const POST_TYPE_ORDER = 'shop_order';

	const ZIP_ORDER_STATUS_AUTHORIZED_KEY         = 'wc-zip-authorised';    // The key to write in the DB
	const ZIP_ORDER_STATUS_AUTHORIZED_KEY_COMPARE = 'zip-authorised';   // If we call $order->get_status() it will cut the 'wc-'. So we need this value for status comparison
	const ZIP_ORDER_STATUS_AUTHORIZED_NAME        = 'Authorised';  // The label

	const USER_META_ADMIN_NOTICE = 'zip-admin-notice';

	const LOGO_ZIP = 'https://static.zip.co/logo/25px/zip.png';

	const META_CHECKOUT_ID = '_zipmoney_checkout_id';
	const META_CHARGE_ID   = '_zipmoney_charge_id';
	const META_POST_ID     = '_post_id';
	const META_USER_ID     = 'user_id';

	// Admin setting key
	const CONFIG_ENABLED                      = 'enabled';
	const CONFIG_TITLE                        = 'title';
	const CONFIG_SANDBOX                      = 'sandbox';
	const CONFIG_SANDBOX_MERCHANT_PUBLIC_KEY  = 'sandbox_merchant_public_key';
	const CONFIG_SANDBOX_MERCHANT_PRIVATE_KEY = 'sandbox_merchant_private_key';
	const CONFIG_MERCHANT_PUBLIC_KEY          = 'merchant_public_key';
	const CONFIG_MERCHANT_PRIVATE_KEY         = 'merchant_private_key';
	const CONFIG_PRODUCT                      = 'product';
	const CONFIG_CHARGE_CAPTURE               = 'charge_capture';
	const CONFIG_LOGGING_LEVEL                = 'log_level';
	const CONFIG_DISPLAY_WIDGET               = 'display_widget';
	const CONFIG_DISPLAY_WIDGET_MODE          = 'display_widget_mode';
	const CONFIG_DISPLAY_WIDGET_PRODUCT_PAGE  = 'display_widget_product_page';
	const CONFIG_DISPLAY_WIDGET_CART          = 'display_widget_cart';
	const CONFIG_DISPLAY_BANNERS              = 'display_banners';
	const CONFIG_DISPLAY_BANNER_SHOP          = 'display_banner_shop';
	const CONFIG_DISPLAY_BANNER_PRODUCT_PAGE  = 'display_banner_product_page';
	const CONFIG_DISPLAY_BANNER_CATEGORY      = 'display_banner_category';
	const CONFIG_DISPLAY_BANNER_CART          = 'display_banner_cart';
	const CONFIG_ORDER_THRESHOLD_MIN_TOTAL    = 'order_threshold_min_total';
	const CONFIG_ORDER_THRESHOLD_MAX_TOTAL    = 'order_threshold_max_total';
	const CONFIG_SANDBOX_CREDENTIAL_BTN       = 'sandbox_btn';
	const CONFIG_PROD_CREDENTIAL_BTN          = 'prod_btn';
	const CONFIG_SELECT_REGION                = 'select_region';
	const CONFIG_CHECK_CREDENTIALS_BTN        = 'check_credentials';
	const CONFIG_ENABLE_TOKENISATION          = 'enable_tokenisation';
	/*
	const PRODUCT_ZIP_MONEY = 'zipMoney';
	const PRODUCT_ZIP_PAY = 'zipPay';*/

	// capture charge options
	const CAPTURE_CHARGE_IMMEDIATELY = 'immediately';
	const CAPTURE_CHARGE_AUTHORIZED  = 'authorized';

	const SINGLE_CONFIG_API_KEY      = '_api_hash';
	const SINGLE_CONFIG_API_SETTINGS = '_api_settings';

	// region for zip widget
	const REGION_AU = 'au';
	const REGION_NZ = 'nz';
//	const REGION_GB = 'gb';
//	const REGION_ZA = 'za';
//	const REGION_US = 'us';
//	const REGION_MX = 'mx';
//	const REGION_SG = 'sg';
//	const REGION_CA = 'ca';
//	const REGION_AE = 'ae';

	// Log levels
	const LOG_LEVEL_ALL   = 1;
	const LOG_LEVEL_DEBUG = 2;
	const LOG_LEVEL_INFO  = 3;
	const LOG_LEVEL_WARN  = 4;
	const LOG_LEVEL_ERROR = 5;
	const LOG_LEVEL_FATAL = 6;
	const LOG_LEVEL_OFF   = 7;

	// widget mode
	const DISPLAY_INLINE = 'inline';
	const DISPLAY_IFRAME = 'iframe';

	public static $zip_order_status = array(
		'wc-zip-authorised'   => 'Authorised',
		'wc-zip-under-review' => 'Under Review',
	);

	public $WC_Zipmoney_Payment_Gateway;

	/**
	 * We need to load the gateway class to use it's build-in functions
	 *
	 * WC_Zipmoney_Payment_Gateway_Config constructor.
	 *
	 * @param WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway
	 */
	public function __construct( WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway ) {
		$this->WC_Zipmoney_Payment_Gateway = $WC_Zipmoney_Payment_Gateway;

		add_action( 'admin_enqueue_scripts', function() {
			$js = "
            jQuery( function( $ ) {

                if($('.woocommerce_sandbox_enable_option').prop('checked') == true){
                    $( '.woocommerce_toggle_prod_field' ).closest( 'tr' ).hide();
                    $( '.woocommerce_toggle_sandbox_field' ).closest( 'tr' ).show();
                    $( '.woocommerce_sandbox_zip_credetail_btn').closest('tr').show();
                    $( '.woocommerce_prod_zip_credetail_btn').closest('tr').hide();
                }else{
                    $( '.woocommerce_toggle_prod_field' ).closest( 'tr' ).show();
                    $( '.woocommerce_toggle_sandbox_field' ).closest( 'tr' ).hide();
                    $( '.woocommerce_sandbox_zip_credetail_btn').closest('tr').hide();
                    $( '.woocommerce_prod_zip_credetail_btn').closest('tr').show();
                }
                
                if($('.woocommerce_banner_enable').prop('checked') == true){
                    $( '.woocommerce_banner_option' ).closest( 'tr' ).show();
                }else{
                    $( '.woocommerce_banner_option' ).closest( 'tr' ).hide();
                }
                if($('#woocommerce_zipmoney_select_region :selected').val() === 'au'){
                    $( '.woocommerce_banner_enable' ).closest( 'tr' ).show();
                    $('.woocommerce_tokenisation_enable_option').closest( 'tr' ).show();
                    $('.woocommerce_iframe_enable_option').closest( 'tr' ).show();
                    if($('.woocommerce_banner_enable').prop('checked') == true){
                        $( '.woocommerce_banner_option' ).closest( 'tr' ).show();
                    }else{
                        $( '.woocommerce_banner_option' ).closest( 'tr' ).hide();
                    }
                }else{
                    $( '.woocommerce_banner_enable' ).closest( 'tr' ).hide();
                    $('.woocommerce_tokenisation_enable_option').closest( 'tr' ).hide();
                    $('.woocommerce_iframe_enable_option').closest( 'tr' ).hide();
                    $( '.woocommerce_banner_option' ).closest( 'tr' ).hide();
                }
                
    
                $( '.woocommerce_sandbox_enable_option' ).change( function( event ) {
                    var checked = $( event.target ).is( ':checked' );
                        if(checked){
                            $( '.woocommerce_toggle_sandbox_field' ).closest( 'tr' ).show();
                            $( '.woocommerce_toggle_prod_field' ).closest( 'tr' ).hide();
                            $( '.woocommerce_sandbox_zip_credetail_btn').closest('tr').show();
                            $( '.woocommerce_prod_zip_credetail_btn').closest('tr').hide();
                            $('.woocommerce_sandbox_zip_credetail_btn').attr('value', 'Find your sandbox keys');
                        }else{
                            $( '.woocommerce_toggle_sandbox_field' ).closest( 'tr' ).hide();
                            $( '.woocommerce_toggle_prod_field' ).closest( 'tr' ).show();
                            $( '.woocommerce_sandbox_zip_credetail_btn').closest('tr').hide();
                            $( '.woocommerce_prod_zip_credetail_btn').closest('tr').show();
                            $('.woocommerce_sandbox_zip_credetail_btn').attr('value', 'Find your production keys');
                        }
                });
    
                $('#woocommerce_zipmoney_select_region').on('change',function(){
                    //Getting Value
                    var selValue = $('#woocommerce_zipmoney_select_region :selected').val();
                    if(selValue === 'au'){
                        $( '.woocommerce_banner_enable' ).closest( 'tr' ).show();
                        $('.woocommerce_tokenisation_enable_option').closest( 'tr' ).show();
                        $('.woocommerce_iframe_enable_option').closest( 'tr' ).show();
                        if($('.woocommerce_banner_enable').prop('checked') == true){
                            $( '.woocommerce_banner_option' ).closest( 'tr' ).show();
                        }else{
                            $( '.woocommerce_banner_option' ).closest( 'tr' ).hide();
                        }
                    }else{
                        $( '.woocommerce_banner_enable' ).closest( 'tr' ).hide();
                        $('.woocommerce_tokenisation_enable_option').closest( 'tr' ).hide();
                        $('.woocommerce_iframe_enable_option').closest( 'tr' ).hide();
                        $( '.woocommerce_banner_option' ).closest( 'tr' ).hide();
                    }
                });
    
                $( '.woocommerce_banner_enable' ).change( function( event ) {
                    var checked = $( event.target ).is( ':checked' );
                        if(checked){
                            $( '.woocommerce_banner_option' ).closest( 'tr' ).show();
                        }else{
                            $( '.woocommerce_banner_option' ).closest( 'tr' ).hide();
                        }
                });
    
                $('#woocommerce_zipmoney_check_credentials').click(function () {
                    const elements = document.getElementsByClassName('zip-notice');
                    const zipspinner = document.getElementsByClassName('zip-spinner');
                    while (elements.length > 0) elements[0].remove();
                    var checkValidateBtn = document.getElementById('woocommerce_zipmoney_check_credentials');
                    checkValidateBtn.insertAdjacentHTML('afterend', '<div class=\"zip-spinner\"></div>');
                    $('.zip-spinner').addClass('is-active');
                    var environment = 'production';
                    var privatekey = '';
                    if ($('#woocommerce_zipmoney_sandbox').is(':checked'))
                    {
                        environment = 'sandbox';
                        privatekey = $('#woocommerce_zipmoney_sandbox_merchant_private_key').val();
                    }
                    else {
                        privatekey = $('#woocommerce_zipmoney_merchant_private_key').val();
                    }
                    var data = {
                        private_key: privatekey,
                        environment: environment
                    };
                    var url = ZipApiKeyCheckUrl;
                    $.post(url, data, function(response) {
                        checkValidateBtn.insertAdjacentHTML('afterend', response['message']); //  the html has been sanitised in the api endpoint side 
                    }).always(function(){
                        while (zipspinner.length > 0) zipspinner[0].remove();
                    });
                });
    
                $('.woocommerce_sandbox_zip_credetail_btn').attr('value', 'Find your sandbox keys');
                $('.woocommerce_prod_zip_credetail_btn').attr('value', 'Find your production keys');
                $('.check_private_key').attr('value', 'Check Private Key validation');
            });
            ";
			wp_add_inline_script( 'jquery', $js );
		});
	}

	// return the admin form fields
	public static function get_admin_form_fields() {
		return array(
			self::CONFIG_ENABLED                      => array(
				'title'       => __( 'Active', 'zippayment' ),
				'label'       => __( 'Enable Zip payment', 'zippayment' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),

			self::CONFIG_SANDBOX                      => array(
				'title'    => __( 'Environment', 'zippayment' ),
				'label'    => __( 'Enable sandbox mode', 'zippayment' ),
				'class'    => 'woocommerce_sandbox_enable_option',
				'type'     => 'checkbox',
				'desc_tip' => __( 'ONLY use sandbox mode in conjunction with sandbox Zip credentials.', 'zippayment' ),
				'default'  => 'no',
			),
			self::CONFIG_SANDBOX_MERCHANT_PUBLIC_KEY  => array(
				'title'       => __( 'Sandbox public key', 'zippayment' ),
				'type'        => 'text',
				'class'       => 'woocommerce_toggle_sandbox_field',
				'description' => __( 'Required for Australia only.', 'zippayment' ),
				'default'     => 'no',
			),
			self::CONFIG_SANDBOX_MERCHANT_PRIVATE_KEY => array(
				'title'   => __( 'Sandbox private key', 'zippayment' ),
				'type'    => 'text',
				'class'   => 'woocommerce_toggle_sandbox_field',
				'default' => 'no',
			),
			self::CONFIG_MERCHANT_PUBLIC_KEY          => array(
				'title'       => __( 'Public key', 'zippayment' ),
				'type'        => 'text',
				'class'       => 'woocommerce_toggle_prod_field',
				'description' => __( 'Required for Australia only.', 'zippayment' ),
				'default'     => '',
			),
			self::CONFIG_MERCHANT_PRIVATE_KEY         => array(
				'title'   => __( 'Private key', 'zippayment' ),
				'type'    => 'text',
				'class'   => 'woocommerce_toggle_prod_field',
				'default' => '',
			),

			self::CONFIG_CHECK_CREDENTIALS_BTN        => array(
				'type'     => 'button',
				'value'    => 'Check Private key is valid',
				'class'    => 'check_private_key',
				'desc_tip' => true,
			),
			self::CONFIG_SELECT_REGION                => array(
				'title'    => __( 'Widget Region', 'zippayment' ),
				'type'     => 'select',
				'desc_tip' => __( 'Select Region to show proper zip widget in product, cart and checkout page', 'zippayment' ),
				'default'  => self::REGION_AU,
				'options'  => array(
					self::REGION_AU => 'Australia',
//					self::REGION_CA => 'Canada',
//					self::REGION_MX => 'Mexico',
					self::REGION_NZ => 'New Zealand',
//					self::REGION_SG => 'Singapore',
//					self::REGION_ZA => 'South Africa',
//					self::REGION_AE => 'United Arab Emirates',
//					self::REGION_GB => 'United Kingdom',
//					self::REGION_US => 'United States',
				),
			),
			self::CONFIG_ENABLE_TOKENISATION          => array(
				'title'       => __( 'Tokenisation', 'zippayment' ),
				'label'       => __( 'Enable Tokenisation', 'zippayment' ),
				'class'       => 'woocommerce_tokenisation_enable_option',
				'type'        => 'checkbox',
				'description' => __( 'Tokenisation allows a seamless one-click checkout experience for returning Zip customers, removing the need to redirect and complete a Zip login after their first purchase. (Australia only)', 'zippayment' ),
				'default'     => 'no',
			),
			self::CONFIG_CHARGE_CAPTURE               => array(
				'title'    => __( 'Capture method', 'zippayment' ),
				'type'     => 'select',
				'desc_tip' => __( 'Set to "Immediate capture" unless directed by Zip. Immedate capture = automatically capture funds when order approved by Zip. Authorise & capture = initially authorise the funds on the customers account and manually capture at a later time.', 'zippayment' ),
				'default'  => self::CAPTURE_CHARGE_IMMEDIATELY,
				'options'  => array(
					self::CAPTURE_CHARGE_IMMEDIATELY => __( 'Immediate capture', 'zippayment' ),
					self::CAPTURE_CHARGE_AUTHORIZED  => __( 'Authorise & capture', 'zippayment' ),
				),
			),
			self::CONFIG_LOGGING_LEVEL                => array(
				'title'    => __( 'Log setting', 'zippayment' ),
				'desc_tip' => __( 'So Zip can assist with troubleshooting any potential issues, we recommend configuring Log Setting to "ALL"', 'zippayment' ),
				'type'     => 'select',
				'default'  => self::LOG_LEVEL_ALL,
				'options'  => array(
					self::LOG_LEVEL_ALL   => __( 'All messages', 'zippayment' ),
					self::LOG_LEVEL_DEBUG => __( 'Debug (and above)', 'zippayment' ),
					self::LOG_LEVEL_INFO  => __( 'Info (and above)', 'zippayment' ),
					self::LOG_LEVEL_WARN  => __( 'Warn (and above)', 'zippayment' ),
					self::LOG_LEVEL_ERROR => __( 'Error (and above)', 'zippayment' ),
					self::LOG_LEVEL_FATAL => __( 'Fatal (and above)', 'zippayment' ),
					self::LOG_LEVEL_OFF   => __( 'Off (No message will be logged)', 'zippayment' ),
				),
			),
			self::CONFIG_ORDER_THRESHOLD_MIN_TOTAL    => array(
				'title'    => __( 'Minimum order value', 'zippayment' ),
				'type'     => 'text',
				'desc_tip' => __( 'Set the minimum shopping cart value that Zip will be available for use.', 'zippayment' ),
				'default'  => 1,
			),
			self::CONFIG_ORDER_THRESHOLD_MAX_TOTAL    => array(
				'title'    => __( 'Maximum order value', 'zippayment' ),
				'type'     => 'text',
				'desc_tip' => __( 'Set the maximum shopping cart value that Zip will be available for use.', 'zippayment' ),
				'default'  => 1500,
			),
			self::CONFIG_DISPLAY_WIDGET_MODE          => array(
				'title'    => __( 'Display Widget Mode', 'zippayment' ),
				'type'     => 'select',
				'desc_tip' => __( 'Select Display widget mode for Zip widget', 'zippayment' ),
				'default'  => self::DISPLAY_INLINE,
				'options'  => array(
					self::DISPLAY_IFRAME => __( 'iframe', 'zippayment' ),
					self::DISPLAY_INLINE => __( 'inline', 'zippayment' ),
				),
			),
			self::CONFIG_DISPLAY_WIDGET_PRODUCT_PAGE  => array(
				'title'    => __( 'Marketing widgets', 'zippayment' ),
				'label'    => __( 'Display on product page', 'zippayment' ),
				'type'     => 'checkbox',
				'desc_tip' => __( 'The product widget will break down the price of the item and display a minimum weekly repayment or divide the price by 4 and show the customer an equal price breakdown', 'zippayment' ),
				'default'  => 'yes',
			),
			self::CONFIG_DISPLAY_WIDGET_CART          => array(
				'label'    => __( 'Display on cart page', 'zippayment' ),
				'type'     => 'checkbox',
				'desc_tip' => __( 'The cart widget will break down the price of the item and display a minimum weekly repayment or divide the price by 4 and show the customer an equal price breakdown', 'zippayment' ),
				'default'  => 'yes',
			),
			self::CONFIG_DISPLAY_BANNERS              => array(
				'title'    => __( 'Marketing banners', 'zippayment' ),
				'label'    => __( 'Display marketing banners', 'zippayment' ),
				'class'    => 'woocommerce_banner_enable',
				'type'     => 'checkbox',
				'desc_tip' => __( 'Enable to display the Zip strip banners on the pages outlined below.', 'zippayment' ),
				'default'  => 'no',
			),
			self::CONFIG_DISPLAY_BANNER_SHOP          => array(
				'label'   => __( 'Display on shop', 'zippayment' ),
				'class'   => 'woocommerce_banner_option',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			self::CONFIG_DISPLAY_BANNER_PRODUCT_PAGE  => array(
				'label'   => __( 'Display on product page', 'zippayment' ),
				'class'   => 'woocommerce_banner_option',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			self::CONFIG_DISPLAY_BANNER_CATEGORY      => array(
				'label'   => __( 'Display on category cage', 'zippayment' ),
				'class'   => 'woocommerce_banner_option',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			self::CONFIG_DISPLAY_BANNER_CART          => array(
				'label'   => __( 'Display on cart', 'zippayment' ),
				'class'   => 'woocommerce_banner_option',
				'type'    => 'checkbox',
				'default' => 'no',
			),
		);
	}

	public function get_checkout_redirect_url() {
		$url = get_home_url();
        $url .= '/zipmoneypayment/expresscheckout/';

		if ( is_product() ) {
			global $product;
			$checkout_url = add_query_arg(
				array(
					'product_id'      => $product->id,
					'checkout_source' => 'product_page',
				),
				$url
			);
		} elseif ( is_cart() ) {
			$checkout_url = add_query_arg(
				array(
					'checkout_source' => 'cart',
				),
				$url
			);
		} else {
			$checkout_url = add_query_arg(
				array(
					'checkout_source' => 'checkout',
				),
				$url
			);
		}

		return $checkout_url;
	}

	/**
	 * Hash the updated merchant_id and merchant_key into a md5 key.
	 * This function will be called in the config save hook.
	 */
	public function hash_api_key() {
		$merchant_public_key  = $this->get_merchant_public_key();
		$merchant_private_key = $this->get_merchant_private_key();

		// get the update key
		$update_key = $this->get_single_config_key( self::SINGLE_CONFIG_API_KEY );

		$current_api_hash = get_option( $update_key, true );

		// hash the new changes
		$new_hash = md5( serialize( array( $merchant_public_key, $merchant_private_key ) ) );

		if ( $current_api_hash !== $new_hash ) {
			// update config in single entry
			update_option( $update_key, $new_hash );
		}
	}

	/**
	 * Return the environment
	 *
	 * @return string
	 */
	public function get_environment() {
		 return $this->is_bool_config_by_key( self::CONFIG_SANDBOX ) ? 'sandbox' : 'production';
	}

	/**
	 * Get the merchant public key
	 *
	 * @return string
	 */
	public function get_merchant_public_key() {
		if ( $this->is_bool_config_by_key( self::CONFIG_SANDBOX ) ) {
			return $this->WC_Zipmoney_Payment_Gateway->get_option( self::CONFIG_SANDBOX_MERCHANT_PUBLIC_KEY );
		}

		return $this->WC_Zipmoney_Payment_Gateway->get_option( self::CONFIG_MERCHANT_PUBLIC_KEY );
	}

	/**
	 * Get the merchant private key
	 *
	 * @return string
	 */
	public function get_merchant_private_key() {
		if ( $this->is_bool_config_by_key( self::CONFIG_SANDBOX ) ) {
			return $this->WC_Zipmoney_Payment_Gateway->get_option( self::CONFIG_SANDBOX_MERCHANT_PRIVATE_KEY );
		}

		return $this->WC_Zipmoney_Payment_Gateway->get_option( self::CONFIG_MERCHANT_PRIVATE_KEY );
	}


	/**
	 * Get the single config key.
	 * It's a single entry in the config table.
	 *
	 * @param $key
	 * @return string
	 */
	public function get_single_config_key( $key ) {
		 return $this->WC_Zipmoney_Payment_Gateway->plugin_id . $this->WC_Zipmoney_Payment_Gateway->id . $key;
	}

	/**
	 * Get the config value by key.
	 * NOTE: The value must be 'yes' or 'no'
	 *
	 * @param $key
	 * @return bool
	 */
	public function is_bool_config_by_key( $key ) {
		 return $this->WC_Zipmoney_Payment_Gateway->get_option( $key ) === 'yes' ? true : false;
	}

	/**
	 * check zip checkout iframe is enabled
	 */
	public function is_it_iframe_flow() {
		$currency = get_option( 'woocommerce_currency' );
		if ( $currency != CurrencyUtil::CURRENCY_AUD ) {
			return false; // iframe checking is disable until we fix zip checkout js issue to support iframe for all browse
		}
	}
}
