<?php wpae_check_license_status(); ?>
<br/>
<table class="wp-list-table widefat">
    <thead>
    <tr>
        <th colspan="2"><strong>Tick (✔) to Activate / Cross (✖) to Deactivate </strong></th>
    </tr>
    </thead>
    <tbody>
    <form method="post" action="">

    <tr>
         <td>
            <?php 
            $wpae_blocked_integrations              = wpae_get_blocked_integrations();
            ksort($GLOBALS['wpae_integrations']);
            foreach ($GLOBALS['wpae_integrations'] as $key => $integration_info){ 
            ?>

            <div class="integrations_list <?php echo (in_array($key, $wpae_blocked_integrations))?'red':'green'; ?>" >
                <input type="checkbox" class="checkinvert" name="wpae_integrations[]" <?php echo (in_array($key, $wpae_blocked_integrations))?'checked="checked"':''; ?> value="<?php echo $key; ?>"><?php echo $integration_info['name']; ?>
            </div>
            <?php } ?>
         </td>      

    </tr>
    <?php if (current_user_can('manage_options')) { ?>  
    <tr>        
        <td>
            <?php wp_nonce_field( 'wpae_save_integrations', 'wpae_nonce' ); ?>
            <input type="submit" name="submit-wpae-integrations" class="button-primary" value="Save Changes" /></td>
    </tr>
    <?php } else { ?>
        <tr>
            <td>
                <p style="color: red;">Only Administrators can make changes to these settings.</p>
            </td>
        </tr>
    <?php } ?>
    </form>
    
    </tbody>
</table>