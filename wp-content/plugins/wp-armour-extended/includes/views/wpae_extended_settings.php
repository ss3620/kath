<?php wpae_check_license_status(); ?>
<br/>
<table class="wp-list-table widefat">
    <thead>
    <tr>
        <th colspan="2"><strong>WP Armour Extended Settings</strong></th>
    </tr>
    </thead>
    <tbody>
    <form method="post" action="">
    <tr>
        <td>Log Spammer IP</td>
        <td>
             <?php $wpae_log_spammer_ip = get_option('wpae_log_spammer_ip'); ?>
             <select name="wpae_extended_settings[wpae_log_spammer_ip]">
                <option value="no" <?php echo $wpae_log_spammer_ip == 'no'?'selected="selected"':''; ?>>No</option>
                <option value="yes" <?php echo $wpae_log_spammer_ip == 'yes'?'selected="selected"':''; ?>>Yes</option>
            </select>
        </td>

    </tr>  

    <tr>
        <td>Log Spam Submission</td>
        <td>
             <?php $wpae_log_spam_data = get_option('wpae_log_spam_data'); ?>
             <select name="wpae_extended_settings[wpae_log_spam_data]">
                <option value="no" <?php echo $wpae_log_spam_data == 'no'?'selected="selected"':''; ?>>No</option>
                <option value="yes" <?php echo $wpae_log_spam_data == 'yes'?'selected="selected"':''; ?>>Yes</option>
            </select>
            <br/><em><strong>Want to know what Spammer tried to post ? Enable this.</strong></em>
        </td>
    </tr>  

    <tr>
        <td>Auto Delete Submission After</td>
        <td>
             <?php $wpae_auto_delete_submission = get_option('wpae_auto_delete_submission'); ?>
             <select name="wpae_extended_settings[wpae_auto_delete_submission]">                
                <option value="7" <?php echo $wpae_auto_delete_submission == '7'?'selected="selected"':''; ?>>7 Days</option>
                <option value="15" <?php echo $wpae_auto_delete_submission == '15'?'selected="selected"':''; ?>>15 Days</option>
                <option value="30" <?php echo $wpae_auto_delete_submission == '30'?'selected="selected"':''; ?>>30 Days</option>
                <option value="60" <?php echo $wpae_auto_delete_submission == '60'?'selected="selected"':''; ?>>60 Days</option>
            </select>
        </td>
    </tr> 

    <tr>
        <td>Auto Block IP after </td>
        <td>
             <?php $wpae_auto_block_ip = get_option('wpae_auto_block_ip'); ?>
             <select name="wpae_extended_settings[wpae_auto_block_ip]">
                <option value="no" <?php echo $wpae_auto_block_ip == 'no'?'selected="selected"':''; ?>>Don't Block</option>
                <option value="2" <?php echo $wpae_auto_block_ip == '2'?'selected="selected"':''; ?>>After 2 Spam Submissions</option>
                <option value="5" <?php echo $wpae_auto_block_ip == '5'?'selected="selected"':''; ?>>After 5 Spam Submissions</option>
                <option value="10" <?php echo $wpae_auto_block_ip == '10'?'selected="selected"':''; ?>>After 10 Spam Submissions</option>
            </select><br/>
            <em><strong>Blocking IP will make site inaccessible for that user. Must enable Log Spammer IP to work.</strong></em>
        </td>
    </tr>  
    <?php /* DEPRECATED LEVEL 2 
    <tr>
        <td>Enable 2 Level Spam Check</td>
        <td>
             <?php $wpae_enable_2level_check = get_option('wpae_enable_2level_check'); ?>
             <select name="wpae_extended_settings[wpae_enable_2level_check]">
                <option value="no" <?php echo $wpae_enable_2level_check == 'no'?'selected="selected"':''; ?>>No</option>
                <option value="yes" <?php echo $wpae_enable_2level_check == 'yes'?'selected="selected"':''; ?>>Yes</option>
                
            </select><br/>
            <em><strong>Enable this if you want to add 2 level spam check. <br/><span style="color:#900;">IMPORTANT!!! Please make sure you clear all the cache of your cache plugin and <br/> test the form after logging out from Wordpress Dashboard.</span></strong></em>
        </td>
    </tr>  
    */ ?>
    
    <?php if (current_user_can('manage_options')) { ?>     
        <tr>        
            <td colspan="2">
                <?php wp_nonce_field( 'wpae_save_settings', 'wpae_nonce' ); ?>
                <input type="submit" name="submit-wpae-general-settings" class="button-primary" value="Save Extended Settings" /></td>
        </tr>
    <?php } else { ?>
        <tr>
            <td colspan="2">
                <p style="color: red;">Only Administrators can make changes to these settings.</p>
            </td>
        </tr>
    <?php } ?>
    </form>
    
    </tbody>
</table>