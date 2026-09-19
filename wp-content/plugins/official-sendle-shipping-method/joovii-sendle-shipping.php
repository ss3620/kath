<?php
/**
 * Plugin Name: Sendle Shipping Plugin
 * Plugin URI: http://joovii.com/shipping-method/sendle-wp.html
 * Description: Tested and approved by Sendle, this plugin provides the basic connectivity between WooCommerce and Sendle. For the ultimate connectivity and features, please install the Premium Plugin.
 * Version: 6.03
 * Author: Joovii
 * Author URI: http://joovii.com/installation-instruction/wp
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /lang
 * Text Domain: official-sendle-shipping-method
 * WC requires at least: 3.0
 * WC tested up to: 7.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { return false; }
//error_reporting(E_ALL);
define( 'SENDLE_JOOVII_API_SANDBOX_URL', 'https://sendle-sandbox.herokuapp.com' );
define( 'SENDLE_JOOVII_AU_MAX_DOMESTIC_WEIGHT', '25' );
define( 'SENDLE_JOOVII_AU_MAX_INTERNATION_WEIGHT', '20' );
define( 'SENDLE_JOOVII_US_MAX_DOMESTIC_WEIGHT', '70' );
define( 'SENDLE_JOOVII_CA_MAX_DOMESTIC_WEIGHT', '25' );

define( 'SENDLE_JOOVII_AU_MAX_DOMESTIC_VOLUMN', '0.100001' );
define( 'SENDLE_JOOVII_AU_MAX_INTERNATION_VOLUMN', '0.125' );
define( 'SENDLE_JOOVII_US_MAX_DOMESTIC_VOLUMN', '864' );
define( 'SENDLE_JOOVII_CS_MAX_DOMESTIC_VOLUMN', '0.125' );
define( 'SENDLE_JOOVII_WP_SENDLE_PLUGIN_VERSION', '6.03' );

if ( ! defined( 'OSSM_SENDLE_CAPABILITY' ) ) {
    define( 'OSSM_SENDLE_CAPABILITY', 'manage_woocommerce' );
}

add_filter( 'woocommerce_custom_orders_table_enabled', '__return_true' );
add_filter( 'woocommerce_custom_orders_table_sync_enabled', '__return_true' );


// MAKE this PLUGIN COMPATIBLE
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',   // HPOS feature flag
            __FILE__,                // path to your main plugin file
            true                     // true = compatible
        );
    }
});


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'joovii_action_links' );
function joovii_action_links( $actions ) {
	$actions[] = '<a style="color:green;font-weight:bold;" target="_blank" href="https://joovii.com/woocommerce-plugin/wordpress-sendle-premium-plugin">Get Sendle Pro</a>';
	return $actions;
}

function ossm_sendle_create_logs_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . "sendlelogs";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `eventname` varchar(50) CHARACTER SET utf8 NOT NULL,
            `orderid` int(11) NOT NULL,
            `logs` text CHARACTER SET utf8 NOT NULL,
            `timestamp` varchar(50) CHARACTER SET utf8 NOT NULL,
            PRIMARY KEY (`id`)
          )".$charset_collate."; ";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'ossm_sendle_create_logs_table');

add_action('admin_menu', 'ossm_sendle_settings');
function ossm_sendle_settings(){
	add_submenu_page('woocommerce','Sendle Settings', 'Sendle Settings',  ossm_getAssignRole(),'admin.php?page=wc-settings&tab=shipping&section=ossmsendle',null,2);
}
add_action('admin_menu', 'ossm_validate_sendle_menu');
function ossm_validate_sendle_menu(){
	add_submenu_page('woocommerce', 'Validate Sendle', 'Validate Sendle',  ossm_getAssignRole(), 'ossm_validate_sendle','ossm_validate_sendle',3);
}
add_action('admin_menu', 'ossm_sendle_trackingemailtemplate_menu');
function ossm_sendle_trackingemailtemplate_menu(){
	add_submenu_page('woocommerce', 'Sendle Email Template', 'Sendle Email Template',  ossm_getAssignRole(), 'ossm_sendle_trackingemailtemplate','ossm_sendle_trackingemailtemplate',3);
}

add_action('admin_menu','ossm_sendle_dashboard');
function ossm_sendle_dashboard() {

	 add_submenu_page('','Track Shipment','Track Shipment', ossm_getAssignRole(),'track-shipment','ossm_track_shipment',1);
	 add_submenu_page('','Download Shipping Label','Download Shipping Label', ossm_getAssignRole(),'download-shipping-label','ossm_download_shipping_label',1);
	 add_submenu_page('','Cancel Sendle Order','Cancel Sendle Order', ossm_getAssignRole(),'cancel-sendle','ossm_cancel_sendle',1);
	 add_submenu_page('','View Sendle Order Details','View Sendle Order Details', ossm_getAssignRole(),'viewdetails-sendle','ossm_create_shipment',1);
	 add_submenu_page('','Create Sendle Shipment','Create Sendle Shipment', ossm_getAssignRole(),'create-shipment','ossm_create_shipment',1 );
}

require_once("sendle-shipping-function.php");
require_once("sendle-shipping-zone.php");
require_once("sendle-shipping-global.php");
require_once("sendle-shipment-booking.php");
require_once("sendle-admin-feature.php");
require_once("track-shipment.php");
require_once("download-label.php");
require_once("frontend-tracking.php");
require_once("cancel-shipment.php");
require_once('sendle-logs.php');
require_once('validate-sendle.php');
require_once('sendle-tracking-email.php');
require_once("sendle-widget.php");
require_once("cityziplookup.php");

update_option('woocommerce_enable_compatibility_mode', 'yes');

function ossm_sendle_shipping_method() {
  $assign_permission=ossm_getAssignPermission();
	if ( ! class_exists( 'ossm_sendle_shipping_method' )  && $assign_permission ) {
        class ossm_sendle_shipping_method extends WC_Shipping_Method {
            var $api_id,$api_key,$pickup_suburb,$pickup_postcode,$mode,$apiurl;
            public function __construct() {

              $this->id                  = 'ossmsendle';
              $this->method_title        = __( 'Sendle', 'official-sendle-shipping-method' );
              $this->method_description  = __( 'Tested and approved by Sendle, this plugin provides the <b><u>basic</u></b> connectivity between WooCommerce and Sendle.<br>For the <b><u>ultimate</u></b> connectivity and features, please install the  <a style="color:green;font-weight:bold;" target="_blank" href="https://joovii.com/woocommerce-plugin/wordpress-sendle-premium-plugin">Premium Plugin</a>.', 'official-sendle-shipping-method' );
              $this->availability 		   = 'sendle_wp';
              $this->init();
              $this->enabled             = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'no';
              $this->title               = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Sendle Shipping', 'official-sendle-shipping-method' );

            }

            function init() {
                $this->init_form_fields();
                $this->init_settings();
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }
            function generate_settings_html( $form_fields = array(), $echo = true ) {
               		if ( empty( $form_fields ) ) {
               			$form_fields = $this->get_form_fields();
               		}

               		$html = '';
               		foreach ( $form_fields as $k => $v ) {
               			$type = $this->get_field_type( $v );

                     if($type == 'ossm_customplaceholder') {
                       $html .= $this->ossm_generate_text_html_custom( $k, $v );
                     }else{
                       if ( method_exists( $this, 'generate_' . $type . '_html' ) ) {
                 				$html .= $this->{'generate_' . $type . '_html'}( $k, $v );
                 			} else {
                 				$html .= $this->generate_text_html( $k, $v );
                 			}
                     }
               		}

               		if ( $echo ) {
               			echo esc_html($html) ; // WPCS: XSS ok.
               		} else {
               			return $html ;
               		}
           	}

             function ossm_generate_text_html_custom ( $key, $data ) {
               $defaults = array();
               $data = wp_parse_args( $data, $defaults );
               ob_start();
               ?>
                 <tr valign="top">
             			<td class="forminp" colspan="2">
             				--------------------------- <?php echo wp_kses_post( $data['title'] ); ?> <?php //echo wp_kses_post( $data['description'] ); ?> ----------------------------------------------
             			</td>
             		</tr>
             	<?php
               return ob_get_clean();
             }

             function init_form_fields() {

                       $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
                       $optionArray = array(
                           'enabled' => array(
                               'title'       => 	__( 'Enable', 'official-sendle-shipping-method' ),
                               'type'        => 	'checkbox',
                               'description' => 	__( 'Enable this shipping method.', 'official-sendle-shipping-method' ),
                               'default'     => 	'yes'
                           ),

                           'title' => array(
                               'title'       => 	__( 'Title', 'official-sendle-shipping-method' ),
                               'type'        => 	'text',
                               'description' => 	__( 'Title to be display on site', 'official-sendle-shipping-method' ),
                               'default'     => 	__( 'Sendle', 'official-sendle-shipping-method' )
                           ),
                           'showrates' => array(
                               'title'       => 	__( 'Show the rates in frontend', 'official-sendle-shipping-method' ),
                               'type'        => 	'checkbox',
                               'description' => 	__( 'If not then the rates will not show in frontend but backend process will work for other shipping method', 'official-sendle-shipping-method' ),
                               'default' 	  => 	'yes'
                           ),

                           'api_id' => array(
                               'title'       => 	__( 'Sendle ID', 'official-sendle-shipping-method' ),
                               'type'        => 	'text',
                               'description' => 	__( '<a href="admin.php?page=ossm_validate_sendle">Validate Your Sendle ID</a>', 'official-sendle-shipping-method' ),
                               'default'     => 	 ''
                           ),
                           'api_key' => array(
                               'title'       => 	__( 'API Key', 'official-sendle-shipping-method' ),
                               'type'        => 	'password',
                               'description' => 	__( 'Do not know what is your API key ? <a target="_blank" href="https://support.sendle.com/hc/en-us/articles/210798518-Sendle-API">Click Here</a>', 'official-sendle-shipping-method' ),
                               'default'     => 	''
                           ),
                           'mode' => array(
                               'title'       => 	__( 'Mode', 'official-sendle-shipping-method' ),
                               'type'        => 	'select',
                               'description' => 	'',
                               'default'     => 	'live',
                               'options'     => 	array("sandbox"=>"Sandbox","live"=>"Live"),
                           ),
                           'pickup_suburb' => array(
                               'title'       => 	__( 'Pickup Suburb', 'official-sendle-shipping-method' ),
                               'type'        => 	'text',
                               'description' => 	__( 'Suburb must be real and match pickup postcode.', 'official-sendle-shipping-method' ),
                               'default'     => 	''
                           ),
                           'pickup_postcode'  => array(
                               'title'       => 	__( 'Pickup Postcode', 'official-sendle-shipping-method' ),
                               'type'        => 	'text',
                               'description' => 	__( 'Four-digit post code for the pickup address.', 'official-sendle-shipping-method' ),
                               'default'     => 	''
                           ),
                           'pickup_country' => array(
                               'title'       => 	__( 'Pickup Country', 'official-sendle-shipping-method' ),
                               'type'        => 	'select',
                               'description' => 	__( 'Pickup Country', 'official-sendle-shipping-method' ),
                               'default'     => 	'AU',
                               'options'     => 	array("AU"=>"Australia","US"=>"United States","CA"=>"Canada",""=>"Please Select"),
                           ),
                           'quote_markup' => array(
                               'title'       => 	__( 'Shipping quote markup %', 'official-sendle-shipping-method' ),
                               'type'        => 	'text',
                               'description' => 	__( "Shipping quote markup %", 'official-sendle-shipping-method' ),
                               'default'     => 	''
                           ),
                           'shipping_handling_fee' => array(
                               'title'       => 	__( 'Additional Handling Fee Applied', 'official-sendle-shipping-method' ),
                               'type'        => 	'text',
                               'description' => 	__( "Additional Handling Fee Applied", 'official-sendle-shipping-method' ),
                               'default'     => 	''
                           ),
                           'enable_addressmatch' => array(
                               'title'       => 	__( 'Enable Address Match', 'official-sendle-shipping-method' ),
                               'type'        => 	'checkbox',
                               'description' => 	__( 'Enable Address Match', 'official-sendle-shipping-method' ),
                               'default'     => 	__( 'yes', 'official-sendle-shipping-method' )
                           ),
                           
						   'volume_param' => array(
                           	'title'         =>	__('Enable Volume Calcultion', 'official-sendle-shipping-method'),
                           	'type'          =>	'checkbox',
                           	'description'   =>	__('Enable Volume Calcultion for quote or book shipment. Please set proper volume unit in woocommerce for your country first. ', 'official-sendle-shipping-method'),
                           	'default'       =>	__('no', 'official-sendle-shipping-method')
                           ),

						'product_default_height' => array(
							'title'         =>	__('Enter Default Product Height', 'official-sendle-shipping-method'),
							'type'          =>	'text',
							'description'   =>	__('This is required to fetch quote when your products do not have Height Defined', 'official-sendle-shipping-method'),
							'default'       =>	__('5', 'official-sendle-shipping-method')
						),

						'product_default_length' => array(
							'title'         =>	__('Enter Default Product Length', 'official-sendle-shipping-method'),
							'type'          =>	'text',
							'description'   =>	__('This is required to fetch quote when your products do not have Length Defined', 'official-sendle-shipping-method'),
							'default'       =>	__('5', 'official-sendle-shipping-method')
						),

						'product_default_width' => array(
							'title'         =>	__('Enter Default Product Width', 'official-sendle-shipping-method'),
							'type'          =>	'text',
							'description'   =>	__('This is required to fetch quote when your products do not have Width Defined', 'official-sendle-shipping-method'),
							'default'       =>	__('5', 'official-sendle-shipping-method')
						),

                           'warningtext_enable' => array(
                             'title'         =>	__('Enable warning text', 'official-sendle-shipping-method'),
                             'type'          =>	'checkbox',
                             'description'   =>	__('Enable warning text if product  weight/volume >  sendle max weight/volume', 'official-sendle-shipping-method'),
                             'default'       =>	__('no', 'official-sendle-shipping-method')
                           ),
                           'warningtext' => array(
                               'title'       => 	__( 'Warning Text for  sendle max weight/volume', 'official-sendle-shipping-method' ),
                               'type'        => 	'text',
                               'description' => 	__( "Warning Text if product weight/volume is greater than  sendle max weight/volume", 'official-sendle-shipping-method' ),
                               'default'     => 	__( 'One of the product of the cart weight/volume is greater than the sendle max weight/volume.', 'official-sendle-shipping-method' )
                           ),

                       );

                        // ----------------Satchel config_line---------------------------------------------------
                       $satchel_manager_seperator_array =   array(
                         'satchelconfig_line' => array(
                             'title'       => 	__( 'Satchel Configuration [For Australian Domestic parcels only]', 'official-sendle-shipping-method' ),
                             'type'        => 	'ossm_customplaceholder',
                             'description' => 	__( '[Do it here] ', 'official-sendle-shipping-method' )
                         ),
                         'satchel_booking' => array(
                            'title'         =>	__('Enable Satchel', 'official-sendle-shipping-method'),
                            'type'          =>	'checkbox',
                            'description'   =>	__('Enable Satchel for Booking or Quotation [For Australian Domestic parcels only]', 'official-sendle-shipping-method'),
                            'default'       =>	__('no', 'official-sendle-shipping-method')
                         ),
                       ) ;
                       $optionArray = array_merge($optionArray, $satchel_manager_seperator_array);
                       $satchel_manager_array =   array(
                         'satchel_mode' => array(
                             'title'       => 	__( 'Satchel Mode', 'official-sendle-shipping-method' ),
                             'type'        => 	'select',
                             'description' => 	__( 'Enable Satchel for Booking/Quotation/Both ', 'official-sendle-shipping-method' ),
                             'default'     => 	'none',
                             'options'     => 	array("none"=>"Please Select","both"=>"Enable Satchel for Both Booking and Quotation","booking"=>"Enable Satchel for Booking only", "quotation"=>"Enable Satchel for Quotation only"),
                         ),
                         'satchel_threshold_weight' => array(
                             'title'       => 	__( 'Satchel Threshold Weight', 'official-sendle-shipping-method' ),
                             'type'        => 	'text',
                             'description' => 	__( "Satchel Threshold Weight(In Grams)", 'official-sendle-shipping-method' ),
                             'default'     => 	__( '500', 'official-sendle-shipping-method' )
                         ),
                         'satchel_threshold_qty' => array(
                             'title'       => 	__( 'Satchel Threshold Quantity', 'official-sendle-shipping-method' ),
                             'type'        => 	'text',
                             'description' => 	__( "Satchel Threshold Quantity", 'official-sendle-shipping-method' ),
                             'default'     => 	__( '0', 'official-sendle-shipping-method' )
                         ),
                         'satchel_booking_adminlink' => array(
                            'title'         =>	__('Enable Satchel  Booking Link in Admin ', 'official-sendle-shipping-method'),
                            'type'          =>	'checkbox',
                            'description'   =>	__('Enable a Satchel Booking link for every order in admin', 'official-sendle-shipping-method'),
                            'default'       =>	__('no', 'official-sendle-shipping-method')
                         ),
                     ) ;

                     //if($sendle_setting['satchel_booking'] == 'yes'){
                       $optionArray = array_merge($optionArray, $satchel_manager_array);
                     //}

                     // ----------------orderconfig_line---------------------------------------------------
                     $order_manager_seperator_array =   array(
                       'orderconfig_line' => array(
                           'title'       => 	__( 'Order Synchronization Configuration', 'official-sendle-shipping-method' ),
                           'type'        => 	'ossm_customplaceholder',
                           'description' => 	__( '[Do it here] ', 'official-sendle-shipping-method' )
                       ),
                       /*'orderconfig_lineEnable' => array(
                          'title'         =>	__('Enable Order Synchronization', 'official-sendle-shipping-method'),
                          'type'          =>	'checkbox',
                          'description'   =>	__('Enable Order Synchronization', 'official-sendle-shipping-method'),
                          'default'       =>	__('no')
                       ),*/
                     ) ;
                     $optionArray = array_merge($optionArray, $order_manager_seperator_array);
                     $order_manager_array =   array(
                       'pickupoption' => array(
                        'title'         =>	__('Pickup Option', 'official-sendle-shipping-method'),
                        'type'          =>	'select',
                        'description'   =>	__('Use pickup to get your parcel picked up or drop off to drop it off at the nearest drop off location.', 'official-sendle-shipping-method'),
                        'default'       =>	__('pickup', 'official-sendle-shipping-method'),
                        'options'       =>	array("pickup"=>"Pick up from merchant address (below)","drop off"=>"Drop it off at the nearest drop off location. ")
                       ),

                       'process_as_sendle_order' => array(
                           'title'       => 	__( 'Post to Sendle API for the selected shipping method', 'official-sendle-shipping-method' ),
                           'type'        => 	'multiselect',
                           'description' => 	__( 'Process As Sendle Order if any of the shipping method are selected.', 'official-sendle-shipping-method' ),
                           'default'     => 	'None',
                           'options'     => 	array("None"=>"Sendle","flat_rate"=>"Flat Rate","free_rate"=>"Free Shipping","any_method"=>"Any method selected by the customer"),
                       ),
                       'book_shipment_on' => array(
                         'title'         =>	__('Book Shipment on', 'official-sendle-shipping-method'),
                         'type'          =>	'select',
                         'description'   =>	__('Select when the shipment will be created.', 'official-sendle-shipping-method'),
                         'default'       =>	__('shipment_submit', 'official-sendle-shipping-method'),
                         'options'       =>	array("order_submit"=>"Order Submit","shipment_submit"=>"Shipment Submit from admin")
                       ),
                       'sender_name' => array(
                           'title'       => 	__( 'Sender Name', 'official-sendle-shipping-method' ),
                           'type'        => 	'text',
                           'description' => 	__( "Sender Name", 'official-sendle-shipping-method' ),
                           'default'     => 	''
                       ),
                       'sender_contact_number' => array(
                           'title'       => 	__( 'Sender contact number', 'official-sendle-shipping-method' ),
                           'type'        => 	'text',
                           'description' => 	__( "Used to coordinate pickup if the courier is outside attempting delivery.Must be a valid phone number. 13, 1300, and 1800 numbers are not allowed.", 'official-sendle-shipping-method' ),
                           'default'     => 	''
                       ),
                       'sender_address' => array(
                           'title'       => 	__( 'Sender address', 'official-sendle-shipping-method' ),
                           'type'        => 	'text',
                           'description' => 	__( "The street address where the parcel will be picked up.", 'official-sendle-shipping-method' ),
                           'default'     => 	''
                       ),
                       'sender_state' => array(
                           'title'       => 	__( 'Sender State', 'official-sendle-shipping-method' ),
                           'type'        => 	'text',
                           'description' => 	__( "Must be the pickup location’s state or territory.", 'official-sendle-shipping-method' ),
                           'default'     => 	''
                       ),
                       'sender_instruction' => array(
                           'title'       => 	__( 'Sender Pickup instructions', 'official-sendle-shipping-method' ),
                           'type'        => 	'text',
                           'description' => 	__( "Short message used as pickup instructions for courier. It must be under 255 chars, but is recommended to be under 40 chars due to label-size limitations.", 'official-sendle-shipping-method' ),
                           'default'     => 	''
                       ),
                       'receiver_instruction' => array(
                           'title'       => 	__( 'Receiver instructions', 'official-sendle-shipping-method' ),
                           'type'        => 	'text',
                           'description' => 	__( "Short message used as delivery instructions for courier. It must be under 255 chars, but is recommended to be under 40 chars due to label-size limitations.", 'official-sendle-shipping-method' ),
                           'default'     => 	''
                       ),

                       'pickup_delay' => array(
                           'title'       => 	__( 'Select pickup days delay', 'official-sendle-shipping-method' ),
                           'type'        => 	'text',
                           'description' => 	__( "Normal pickup date is the next business day. Should be a number greater than 0.", 'official-sendle-shipping-method' ),
                           'default'     => 	__( '1', 'official-sendle-shipping-method' )
                       ),
                       /*'label_size' => array(
                           'title'       => 	__( 'Download Label Size', 'official-sendle-shipping-method' ),
                           'type'        => 	'select',
                           'description' => 	__( "Download Label Size", 'official-sendle-shipping-method' ),
                           'default'     => 	'',
                           'options'     => 	array("a4"=>"Default","a4"=>"A4","cropped"=>"Cropped"),
                       ),*/

                       'change_order_status' => array(
                           'title'       => 	__( 'Change Order Status', 'official-sendle-shipping-method' ),
                           'type'        => 	'select',
                           'description' => 	__( 'Change Order Status to Processing/Completed after shipment booking', 'official-sendle-shipping-method' ),
                           'default'     => 	'no',
                           'options'     => 	array("processing"=>"Processing","completed"=>"Completed","no"=>"No"),
                       ),

                       'tracking_email' => array(
                        'title'         =>	__('Send Tracking Email', 'official-sendle-shipping-method'),
                        'type'          =>	'checkbox',
                        'description'   =>	__('Send Tracking Email. <a href="admin.php?page=ossm_sendle_trackingemailtemplate">Edit Tracking Email Template.</a> ', 'official-sendle-shipping-method'),
                        'default'       =>	__('no', 'official-sendle-shipping-method')
                       ),

                       'hs_code' => array(
                        'title'         =>	__('HS Code', 'official-sendle-shipping-method'),
                        'type'          =>	'text',
                        'description'   =>	__('A Harmonized System code for this item, appropriate for the destination country for international shipping. Including a HS code speeds up customs processing. Single HS tarrif code only. Must contain 6–10 digits with separating dots.', 'official-sendle-shipping-method'),
                        'default'     => 	''
                       ),
                       'hs_code_field_name' => array(
                         'title'         =>	__('HS Code Field Name', 'official-sendle-shipping-method'),
                         'type'          =>	'text',
                         'description'   =>	__('Enter Harmonized System Code Field Name.', 'official-sendle-shipping-method'),
                         'default'     => 	''
                        ),
                   ) ;
                   //if($sendle_setting['orderconfig_lineEnable'] == 'yes'){
                     $optionArray = array_merge($optionArray, $order_manager_array);
                   //}

                   // ----------------devconfig_line---------------------------------------------------
                   $devconfig_manager_array =   array(
                     'devconfig_line' => array(
                         'title'       => 	__( 'Developer Configuration', 'official-sendle-shipping-method' ),
                         'type'        => 	'ossm_customplaceholder',
                         'description' => 	__( '[Do it here] ', 'official-sendle-shipping-method' )
                     ),
                     'optintojoovii' => array(
                      'title'         =>	__('Allow access to Sendle and Joovii API\'s.', 'official-sendle-shipping-method'),
                      'type'          =>	'checkbox',
                      'description'   =>	__('This is required to allow live shipping quoting and booking and for support from Joovii.   The plugin will be disabled without this access approved. ', 'official-sendle-shipping-method'),
                      'default'       =>	__('yes', 'official-sendle-shipping-method')
                     ),
                     'enable_log' => array(
                         'title'       => 	__( 'Enable Log', 'official-sendle-shipping-method' ),
                         'type'        => 	'checkbox',
                         'description' => 	__( '<a href="admin.php?page=sendle_logs">Go to Sendle Log to view logs</a>', 'official-sendle-shipping-method' ),
                         'default'     => 	__( 'no', 'official-sendle-shipping-method' )
                     ),
                     'enable_customer_reference' => array(
                         'title'       => 	__( 'Enable Customer Reference', 'official-sendle-shipping-method' ),
                         'type'        => 	'checkbox',
                         'description' => 	__( 'This will apply a filter ossm_filter_customer_reference to add customer_reference to the sendle order. By default orderId will be added to the customer_reference.', 'official-sendle-shipping-method' ),
                         'default'     => 	__( 'no', 'official-sendle-shipping-method' )
                     )
                 ) ;
                 $optionArray = array_merge($optionArray, $devconfig_manager_array);

                 $role_manager_array =   array('role_manager' => array(
                     'title'       => __( 'User Role Manager', 'official-sendle-shipping-method' ),
                     'type'        => 'select',
                     'description' => __( 'Which user role can access this plugin', 'official-sendle-shipping-method' ),
                     'default'     => 'None',
                     'options'     => array('None'=>'None',
                                            "shop_manager"=>"Shop manager",
                                            //"author"=>"Author",
                                            //"editor"=>"Editor"
                                          ),
                 )) ;
				 
                 if(ossm_getAssignRole() == 'administrator'){
                   $optionArray = array_merge($optionArray, $role_manager_array);
                 }

                 $this->form_fields = $optionArray;
            }

            public function calculate_shipping( $package = array() ) {

                //$result = ossm_calculateSendleRate($package );
                //if(!is_array($result)){ reurn; }else{ $this->add_rate( $result ); }
                $sendle_setting = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
                //ossm_checkSendleZone($sendle_setting);


          }
        }
    }
}

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'ossm_add_plugin_page_settings_link');
function ossm_add_plugin_page_settings_link( $links ) {
	$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=ossmsendle' ) . '">' . __('Settings', 'official-sendle-shipping-method') . '</a>';
	return $links;
}
add_action( 'woocommerce_shipping_init', 'ossm_sendle_shipping_method' );

function ossm_add_sendle_shipping_method( $methods ) {
  $methods[] = 'ossm_sendle_shipping_method';
  return $methods;
}



add_filter( 'woocommerce_shipping_methods', 'ossm_add_sendle_shipping_method' );
add_filter( 'woocommerce_shipping_calculator_enable_city','__return_true'  );

/* function ossm_other_shipping_method($rates, $package){
    $shipping_type = 'flat_';
    foreach( $rates AS $id => $data )  {
      // if the rate id starts with "flat_", remove it
      if ( 0 === stripos( $id, $shipping_type ) ) {
        unset( $rates[ $id ] );
      }
    }
    return $rates;
}
add_filter( 'woocommerce_package_rates', 'ossm_other_shipping_method', 10, 2);*/

function ossm_my_sendle_pickup_country( $hook ) {

    $section = isset($_GET['section'])
        ? sanitize_text_field( wp_unslash($_GET['section']) )
        : '';

    if ( $section !== 'ossmsendle' ) {
        return;
    }

    $script = "
        if ( jQuery('#woocommerce_ossmsendle_api_id').val() !== '' ) {
            if ( jQuery('#woocommerce_ossmsendle_pickup_country').val() === '' ) {
                alert('Please Select Pickup Country.');
                jQuery('#woocommerce_ossmsendle_pickup_country')
                    .css('border-color', 'red')
                    .css('border-width', 'thick');
            }
        }
    ";

    // Ensure jQuery is loaded
    wp_enqueue_script( 'jquery' );

    // Attach inline JS safely
    wp_add_inline_script( 'jquery', $script );
}


add_action( 'in_admin_footer', 'ossm_my_sendle_pickup_country' );

?>
