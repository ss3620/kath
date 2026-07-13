<?php
$license = get_option( 'wpae_license_key' );
$status  = get_option( 'wpae_license_status' );
?>

<?php if (isset($_GET['msg']) == "activate_license"):?>
    <div class="notice notice-info" id="message"><p>Your must activate license to access the page.</p></div>
  <?php endif; ?>

<br/>
<table class="wp-list-table widefat">
    <thead>
        <tr>
            <th><strong>Wp Armour Extended License</strong></th>
        </tr>
    </thead>
    <tbody>

    <tr>
    	<td colspan="2"><strong>Activate License Key for Automatic Updates.</strong></td>
    </tr>
    <tr>
        <td>
            
            <form method="post" action="">

			<?php settings_fields('wpae_license'); ?>

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('License Key'); ?>
						</th>
						<td>
							<?php if( $status !== false && $status == 'valid' ) { ?>
								<strong>################################</strong>
							<?php } else { ?>
								<input id="wpae_license_key" name="wpae_license_key" type="text" class="regular-text" value="" />
							<?php } ?>
							
						</td>
					</tr>
					<?php //if( false !== $license ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('License Status'); ?>
							</th>
							<td>
								<?php if( $status !== false && $status == 'valid' ) { ?>
									<span style="color:green;"><?php _e('active'); ?></span>
									<?php wp_nonce_field( 'wpae_nonce', 'wpae_nonce' ); ?>
									<input type="submit" class="button-secondary" name="wpae_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
								<?php } else {
									wp_nonce_field( 'wpae_nonce', 'wpae_nonce' ); ?>
									<input type="submit" class="button-secondary" name="wpae_license_activate" value="<?php _e('Activate License'); ?>"/>
								<?php } ?>
							</td>
						</tr>
					<?php //} ?>
				</tbody>
			</table>
			<?php //submit_button(); ?>

		</form>
            
        </td>
        
    </tr>
    </tbody>
</table>
<br/>
