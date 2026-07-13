<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ossm_sendle_trackingemailtemplate(){

	if (!is_user_logged_in()) {
		die('You must be logged in to submit this form.');
	}
	
	if ( ! current_user_can('manage_woocommerce') && ! current_user_can('manage_options') ) {
	    wp_die( 'Unauthorized', 403 );
	}

	$validPostAction = 0;
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'saveTemplate' ) 
	{
		// Check if the nonce exists and is valid
		if( !isset($_POST['ossm_sendle_template_form_nonce']) || 
			!wp_verify_nonce($_POST['ossm_sendle_template_form_nonce'], 'ossm_sendle_my_custom_form_action')) 
		{
			die('Security check failed! Possible CSRF attack.');
		}
		
		$validPostAction = 1;
	}
	
	$defaultValue = '
	Hi {{customer_name}}
	An order you recently placed on our website has had its status changed.

	The status of order #{{order_no}} is now Shipped

	Shipment Tracking Numbers: {{tracking_number}}
	Shipment Tracking Links : {{tracking_link}}

	{{store_name}}';
	
	$postemailTemplateVal='';
	
	if($validPostAction && isset($_POST['sendle_tracking_email_template'])){
		$postemailTemplateVal = sanitize_textarea_field( wp_unslash($_POST['sendle_tracking_email_template']) );
	}
	
	$emailTemplateVal = get_option('woocommerce_ossm_sendle_tracking_email_template');

	if(trim($postemailTemplateVal) != ''){

		$emailTemplateVal = $postemailTemplateVal;
		if(trim(get_option('woocommerce_ossm_sendle_tracking_email_template')) != '') {
			update_option( 'woocommerce_ossm_sendle_tracking_email_template', $emailTemplateVal );
		}else{
			add_option( 'woocommerce_ossm_sendle_tracking_email_template', $emailTemplateVal );
		}

	}else{
		if(trim(get_option('woocommerce_ossm_sendle_tracking_email_template')) != '') {
			$emailTemplateVal = $emailTemplateVal;
		}else{
			$emailTemplateVal = $defaultValue;
		}
	}
?>
<form method="post" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" enctype="multipart/form-data">
<?php wp_nonce_field('ossm_sendle_my_custom_form_action', 'ossm_sendle_template_form_nonce'); ?>
<input type="hidden" name="rate" value="calculate">
<input type="hidden" name="action" value="saveTemplate">
<div class="wrap"><h3 style="text-decoration:underline">Sendle Tracking Email Template</h3></div>
    <table cellpadding="0" cellspacing="0" border="0"  width="100%" >
    <tr>
        <td>
			<textarea rows="15" cols="120" name="sendle_tracking_email_template"><?php echo esc_textarea($emailTemplateVal);?></textarea>
		</td>
    </tr>
    <tr>
        <td align="left">&nbsp;<br><input type='submit' name="Save" value='Save' class='button'></td>
    </tr>
		<tr>
        <td align="left">&nbsp;<br><b>Following is the default email template.</b></td>
    </tr>
		<tr>
        <td align="left">&nbsp;<br> <?php echo nl2br( esc_html($defaultValue) ); ?> </td>
    </tr>
    </table>
</form>
<?php
}
?>
