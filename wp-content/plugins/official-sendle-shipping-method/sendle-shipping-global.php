<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ossm_sendle_shipping_global_notice() {
    if(!ossm_is_sendle_widget_enable()){
	     $class = 'notice notice-error';
    }
}
add_action( 'admin_notices', 'ossm_sendle_shipping_global_notice' );

function ossm_is_sendle_widget_enable(){

  $sendle_setting  = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
  $api_id = '';
  if(isset($sendle_setting['api_id'])){
    $api_id = $sendle_setting['api_id'];
  }

  $api_key = '';
  if(isset($sendle_setting['api_key'])){
	   $api_key = $sendle_setting['api_key'];
  }

  $api_mode = '';
  if(isset($sendle_setting['mode'])){
	   $api_mode = $sendle_setting['mode'];
  }
  if(!empty($api_mode) && !empty($api_id) && !empty($api_key)){ return true;  }
  return false;

}

add_action('admin_menu', 'ossm_sendle_shipping_global_submenu');

function ossm_sendle_shipping_global_submenu() {
    add_action( 'admin_init', 'ossm_register_sendle_shipping_global_settings' );
}

function ossm_register_sendle_shipping_global_settings() {

	register_setting( 'sendle-shipping-global-group', 'sendle_shipping_api_mode' );
	register_setting( 'sendle-shipping-global-group', 'sendle_shipping_api_id' );
	register_setting( 'sendle-shipping-global-group', 'sendle_shipping_api_key' );
}

function ossm_sendle_global_setting_page() {

	$sendle_setting  = maybe_unserialize( get_option('woocommerce_ossmsendle_settings') );
	$api_id = $sendle_setting['api_id'];
	$api_key = $sendle_setting['api_key'];
	$api_mode = $sendle_setting['mode'];
?>
<div class="wrap">
<h1>Sendle Shipping Setting For Tracking Widget / ShortCode</h1>
<p>This API info will only be used for sendle tracking widget/shortcode and will not overwrite the standard API data from Woocommerce Sendle Shipping method</p>

<form method="post" action="options.php">
    <?php settings_fields( 'sendle-shipping-global-group' ); ?>
    <?php do_settings_sections( 'sendle-shipping-global-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">API Mode</th>
        <td>
        <select  name="sendle_shipping_api_mode">
        <option <?php if($api_mode == "sandbox"){ echo "selected"; } ?> value="sandbox">Sandbox</option>
        <option <?php if($api_mode == "live"){ echo "selected"; } ?> value="live">Live</option>
        </select>
        </td>
        </tr>
        <tr valign="top">
        <th scope="row">API Id</th>
        <td><input type="text" name="sendle_shipping_api_id" value="<?php echo esc_attr( $api_id ); ?>" /></td>
        </tr>
        <tr valign="top">
        <th scope="row">API Key</th>
        <td><input type="password" name="sendle_shipping_api_key" value="<?php echo esc_attr( $api_key); ?>" /></td>
        </tr>

    </table>
    <?php submit_button(); ?>
</form>
</div>
<?php } ?>
