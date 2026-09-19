<?php wpae_check_license_status(); ?>
<br/>
<table class="wp-list-table widefat">
    <thead>
    <tr>
        <th colspan="2"><strong>WhiteList Ips</strong></th>
    </tr>
    </thead>
    <tbody>
    <form method="post" action="">

    <tr>
        <td> Your Current IP</td>
        <td><strong><?php echo $_SERVER['REMOTE_ADDR'] ?></strong></td>
    </tr>

    <tr>
        <td width="200">WhiteList IPs</td>
        <td>
             <?php 
                $wpae_whitelist_ips              = get_option('wpae_whitelist_ips');
                $wpae_whitelist_ips_array        = json_decode($wpae_whitelist_ips, true); 
                $ips_as_string                   = !empty($wpae_whitelist_ips_array) ? join('&#013;',$wpae_whitelist_ips_array) : '';
             ?>
             <textarea name="wpae_whitelist_ips" rows="10" cols="20"><?php echo $ips_as_string; ?></textarea>
        </td>

    </tr>  
    <tr>        
        <td colspan="2">
            <?php wp_nonce_field( 'wpae_save_whitelist_ips', 'wpae_nonce' ); ?>
            <input type="submit" name="submit-wpae-whitelist_ips" class="button-primary" value="Save WhiteList IPs" /></td>
    </tr>
    </form>
    
    </tbody>
</table>