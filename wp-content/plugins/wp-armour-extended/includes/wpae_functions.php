<?php
function wpae_admin_assets(){
    wp_register_style( 'wpae_admin_css', plugins_url('/css/wpae_admin.css', __FILE__) , false, $GLOBALS['wpae_version']);
    wp_enqueue_style( 'wpae_admin_css' );    
}

function wpae_load_scripts(){
	if( get_option('wpa_disable_jquery') === 'yes' ) {
        wp_enqueue_script( 'wpaescript', plugins_url( '/js/wpae_vanilla.js', __FILE__ ), array(), $GLOBALS['wpa_version'], true );
    } else {
        wp_enqueue_script( 'wpaescript', plugins_url( '/js/wpae.js', __FILE__ ), array('jquery'), $GLOBALS['wpa_version'], true );
    }
}

function wpae_plugin_update(){
	if ( get_option( 'wpae_db_version' ) != $GLOBALS['wpae_version'] ) { // DB and plugin version will be same.
        wpae_create_db_table();
    }
}

function wpae_create_db_table(){
    global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$charset_collate = $wpdb->get_charset_collate();

	$table_wpae_spam_data = $wpdb->prefix . 'wpae_spam_data';	
	$sql1 = "CREATE TABLE $table_wpae_spam_data (
			  id int(11) NOT NULL AUTO_INCREMENT,
			  wp_system varchar(20) NOT NULL,
			  spam_data text NOT NULL,
			  spammer_ip varchar(20) NOT NULL,
			  updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY  (id)
			) $charset_collate;";
	dbDelta( $sql1 );

	$table_wpae_spammer_ip = $wpdb->prefix . 'wpae_spammer_ip';
	$sql2 = "CREATE TABLE $table_wpae_spammer_ip (
			  id int(11) NOT NULL AUTO_INCREMENT,
			  wp_system varchar(20) NOT NULL,
			  spammer_ip varchar(20) NOT NULL,
			  updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY  (id)
			) $charset_collate;";
	dbDelta( $sql2 );

	$table_wpae_blocked_ip = $wpdb->prefix . 'wpae_blocked_ip';
	$sql3 = "CREATE TABLE $table_wpae_blocked_ip (
			  id int(11) NOT NULL AUTO_INCREMENT,
			  spammer_ip varchar(20) NOT NULL,
			  updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY  (id)
			) $charset_collate;";
	dbDelta( $sql3 );

	//WILL DEPRICATE IN NEXT RELEASE ( to avoid conflict if extended version is updated and not free version )
	$table_wpae_submission_id = $wpdb->prefix . 'wpae_submission_id';
	$sql4 = "CREATE TABLE $table_wpae_submission_id (
			  id int(11) NOT NULL AUTO_INCREMENT,
			  submission_id varchar(30) NOT NULL,			  
			  updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY  (id)
			) $charset_collate;";
	dbDelta( $sql4 );
	// EOF DEPRICATE

	update_option( 'wpae_db_version', $GLOBALS['wpae_version']);
	add_option( 'wpae_log_spammer_ip', 'no');
	add_option( 'wpae_log_spam_data', 'no');
	add_option( 'wpae_auto_block_ip', 'no');
	add_option( 'wpae_auto_delete_submission', '30');
	add_option( 'wpae_enable_2level_check', 'no');
	add_option( 'wpae_whitelist_ips', '[]');
	add_option( 'wpae_blocked_integrations', '[]');
}

function wpae_modify_tabs($wpa_tabs){	
	$wpa_tabs = array(
				'settings' => array('name'=>'General Settings','path'=>'wpa_settings.php'),
				'wpae_extended_settings' => array('name'=>'Extended Settings','path'=> plugin_dir_path( __FILE__ ).'views/wpae_extended_settings.php'),
				'stats' => array('name'=>'Statistics','path'=>'wpa_stats.php'),
				'spammer_ip' => array('name'=>'Spammer IP','path'=> plugin_dir_path( __FILE__ ).'views/wpae_spammer_ip.php'),
				'spam_data' => array('name'=>'Spam Submissions','path'=> plugin_dir_path( __FILE__ ).'views/wpae_spam_data.php'),
				'blocked_ips' => array('name'=>'Blocked IPs','path'=> plugin_dir_path( __FILE__ ).'views/wpae_blocked_ip.php'),
				'whitelist_ips' => array('name'=>'Whitelist IPs','path'=> plugin_dir_path( __FILE__ ).'views/wpae_whitelist_ips.php'),
				'integrations' => array('name'=>'Integrations','path'=> plugin_dir_path( __FILE__ ).'views/wpae_integrations.php'),
				'license' => array('name'=>"License",'path'=>plugin_dir_path( __FILE__ ).'views/wpae_license.php')
				);

	if (!current_user_can('manage_options')) { 
        unset($wpa_tabs['license']);
    }

	return $wpa_tabs;
}

function wpae_record_spammers($wp_system, $data){
	if ($GLOBALS['wpae_log_spammer_ip'] == 'yes'){
		wpae_save_spammer_ip($wp_system);
	}

	if ($GLOBALS['wpae_log_spam_data'] == 'yes'){
		wpae_save_spam_data($wp_system, $data);
	}

	if ($GLOBALS['wpae_auto_block_ip'] != 'no'){
		wpae_block_ip();
	}
}

function wpae_save_spammer_ip($wp_system){
	global $wpdb;
	$table_wpae_spammer_ip = $wpdb->prefix . 'wpae_spammer_ip';

	$userIP = wpae_get_user_ip();

	// Prevent saving localhost IPs and unknown IPs
	if ($userIP == 'UNKNOWN' || $userIP == '127.0.0.1' || $userIP == '::1') {
		return; // Exit early if it's a localhost or unknown IP
	}

	$wpdb->insert( 
		$table_wpae_spammer_ip, 
		array( 
			'wp_system'  => $wp_system, 
			'spammer_ip' => $userIP // Use the already fetched IP
		) 
	);
}

function wpae_save_spam_data($wp_system, $data){
	global $wpdb;
	$table_wpae_spam_data = $wpdb->prefix . 'wpae_spam_data';

	$wpdb->insert( 
		$table_wpae_spam_data,
		array( 
			'wp_system' => $wp_system, 
			'spammer_ip' => wpae_get_user_ip(),
			'spam_data'	=> json_encode($data)
		)
	);

	$delete_older_than = $GLOBALS['wpae_auto_delete_submission'];
	$wpdb->query("DELETE FROM $table_wpae_spam_data WHERE DATEDIFF(now(), $table_wpae_spam_data.updated_on) >=  $delete_older_than");
}

function wpae_block_ip(){
	global $wpdb;	
	if (!current_user_can('activate_plugins')){
		$blockAfter = $GLOBALS['wpae_auto_block_ip'];
		$currentIp 	= wpae_get_user_ip();

		if ($currentIp === '127.0.0.1' || $currentIp === '::1') {
			return;
		}
		
		$spammer_table = $wpdb->prefix . 'wpae_spammer_ip';
		$blocked_table = $wpdb->prefix . 'wpae_blocked_ip';

		// Check how many times the IP appears in the spammer table
		$prepared_query = $wpdb->prepare( "SELECT COUNT(*) FROM $spammer_table WHERE spammer_ip = %s", $currentIp );
		$ip_count = $wpdb->get_var( $prepared_query );

		if ($ip_count >= $blockAfter){
			// Check if the IP is already in the blocked list
			$check_blocked_query = $wpdb->prepare( "SELECT COUNT(*) FROM $blocked_table WHERE spammer_ip = %s", $currentIp );
			$is_blocked = $wpdb->get_var( $check_blocked_query );

			if ($is_blocked == 0) { // If IP is not already blocked
				wpae_insert('wpae_blocked_ip', array('spammer_ip' => $currentIp));
			}
		}
	}
}

function wpae_get_pagination_navigation($baseUrl, $total_pages, $current_page){
	for ($i=1; $i <= $total_pages; $i++) { ?>
		<a class="<?php echo $current_page == $i?'active':'';  ?>" href="<?php echo $baseUrl.'&paged='.$i ; ?>"><?php echo $i; ?></a>
	<?php }
}

function wpae_bulk_truncate($tablename){
	global $wpdb;
    $prefixed_table_name 	 =   $wpdb->prefix . $tablename;
	$wpdb->query("TRUNCATE TABLE $prefixed_table_name");
}

function wpae_delete($tablename, $where){
	global $wpdb;
    $prefixed_table_name 	 =   $wpdb->prefix . $tablename;
	$wpdb->query("DELETE from $prefixed_table_name WHERE $where");
}

function wpae_insert($tablename, $data){
	global $wpdb;
    $prefixed_table_name 	 =   $wpdb->prefix . $tablename;
	$wpdb->insert($prefixed_table_name,$data);
}

function wpae_get($tablename, $where){
	global $wpdb;
    $prefixed_table_name 	 =   $wpdb->prefix . $tablename;
	$results = $wpdb->get_results("SELECT * FROM  $prefixed_table_name WHERE $where");
	return $results;
}

function wpae_is_ip_blocked(){
	global $wpdb;
	if (!is_admin() && ($GLOBALS['pagenow'] != 'wp-login.php')){
		$currentIp = wpae_get_user_ip();
		if ($currentIp  != 'UNKNOWN'){
			$wpae_whitelist_ips              = json_decode(get_option('wpae_whitelist_ips'), true);
			if (!in_array($currentIp, $wpae_whitelist_ips)){
				$prefixed_table_name = $wpdb->prefix . 'wpae_blocked_ip';
				$prepared_query = $wpdb->prepare( "SELECT COUNT(*) FROM $prefixed_table_name WHERE spammer_ip = %s", $currentIp );
				$ip_count = $wpdb->get_var( $prepared_query );
				if ($ip_count > 0){
					header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1.
					header("Pragma: no-cache"); // HTTP 1.0.
					header("Expires: Sat, 26 Jul 2022 05:00:00 GMT"); // Date in the past.
					wp_die('Your IP is Blocked. If you think this is not right, please contact site administrator.');
				}
			}		
		}
	}
}

function wpae_get_user_ip(){
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    } else {
        return 'UNKNOWN';
    }

    $ipv4pattern = '/\b(?:\d{1,3}\.){3}\d{1,3}\b/';
	preg_match($ipv4pattern, $ip_address, $ips);
	
	if (!empty($ips)){
		$ipv4Address = $ips[0];	
	} else {
		$ipv4Address = $ip_address;
	}	
	
	return $ipv4Address;
}

function wpae_admin_notice(){
    if( !is_plugin_active( 'honeypot/wp-armour.php' ) ) {
    	$plugin_install_url = admin_url('plugin-install.php?s=WP+Armour&tab=search');
    	echo '<div class="notice notice-error is-dismissible">
          <p>For WP Armour Extended to work, you must activate <a href="'.$plugin_install_url.'" >Wp Armour</a> . Please activate it.</p>
         </div>';        
	} else {
		if (@$GLOBALS['wpa_version'] < 1.9 ){
			echo '<div class="notice notice-error is-dismissible">
		  		<p><strong style="color:#900;">IMPORTANT : </strong> Your are using older version of WP Armour Lite (Free Version). Please update to 1.9 or above for it to work properly.</p>
		 	</div>';	
		}
	}
	
}

function wpae_widget_content(){
	ob_start();
	include('views/wpae_stats_widget.php');
	$widget_content = ob_get_contents();
	ob_end_clean ();
	return $widget_content;
}


function wpae_save_whitelist_ips(){	
	if ( isset($_POST['wpae_nonce']) && wp_verify_nonce($_POST['wpae_nonce'], 'wpae_save_whitelist_ips')) {		
		$wpae_whitelist_ips					= 	$_POST['wpae_whitelist_ips'];
		$wpae_whitelist_ips_array 			=   preg_split("/\r\n|\n|\r/", $wpae_whitelist_ips);

		$wpae_whitelist_ips_array_sanitize	=  array_filter(filter_var_array($wpae_whitelist_ips_array, FILTER_VALIDATE_IP));
		
		update_option('wpae_whitelist_ips',json_encode($wpae_whitelist_ips_array_sanitize));

		$return['status']   = 'ok';
		$return['body'] 	= 'Whitelist IPs Saved';
	} else {
		$return['status']   = 'error';
		$return['body'] 	= 'Sorry, your nonce did not verify. Please try again.';
	}
	return $return;
}

function wpae_save_integrations(){	
	if ( isset($_POST['wpae_nonce']) && wp_verify_nonce($_POST['wpae_nonce'], 'wpae_save_integrations')) {		
		update_option('wpae_blocked_integrations',json_encode($_POST['wpae_integrations']));
		$return['status']   = 'ok';
		$return['body'] 	= 'Integrations Changes Saved.';
	} else {
		$return['status']   = 'error';
		$return['body'] 	= 'Sorry, your nonce did not verify. Please try again.';
	}
	return $return;
}

function wpae_save_settings(){	
	if ( isset($_POST['wpae_nonce']) && wp_verify_nonce($_POST['wpae_nonce'], 'wpae_save_settings')) {		
		$settingsFields = 	$_POST['wpae_extended_settings'];

		foreach ($settingsFields as $option_key => $option_value) {
			update_option($option_key,$option_value);
		}

		$return['status']   = 'ok';
		$return['body'] 	= 'Settings Saved';
	} else {
		$return['status']   = 'error';
		$return['body'] 	= 'Sorry, your nonce did not verify. Please try again.';
	}
	return $return;
}

//WILL DEPRICATE IN NEXT RELEASE ( to avoid conflict if extended version is updated and not free version )
function wpae_unqiue_field_value(){
	$randomCode = rand(1111, 999999);
	if ($GLOBALS['wpae_enable_2level_check']== 'yes'){ // ONLY RECORDS SUBMISSION ID if 2 level enabled.
		wpae_delete('wpae_submission_id',"updated_on < NOW() - INTERVAL 3 HOUR"); // DELETE OLD SUBMISSION IDs older than 3 hours.
		wpae_insert('wpae_submission_id', array('submission_id'=>$randomCode));
	}
	return $randomCode;
}

//WILL DEPRICATE IN NEXT RELEASE ( to avoid conflict if extended version is updated and not free version )
function wpae_2level_spam_check($field_value){	
	if ($GLOBALS['wpae_enable_2level_check']== 'yes'){
		$result = wpae_get('wpae_submission_id','submission_id = '.$field_value);		
		if (!empty($result)){
			wpae_delete('wpae_submission_id', 'submission_id = '.$field_value); // DELETE AFTER SUBMISSION ID IS USED;
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}

function wpae_check_license_status(){
	$status  = get_option( 'wpae_license_status' );
	if ($status != 'valid'){
		echo '<script>window.location = "'.admin_url( 'admin.php?page=wp-armour&tab=license&msg=activate_license' ).'"</script>';
	}
}

function wpae_get_blocked_integrations(){
	$wpae_blocked_integrations              = json_decode(get_option('wpae_blocked_integrations'), true);   
    if ($wpae_blocked_integrations == null){$wpae_blocked_integrations = array();}
    return $wpae_blocked_integrations;
}

function wpae_print_array($arr) {
    foreach ($arr as $key => $value) {
        echo "<span><strong>" . $key . "</strong> : ";
        if (is_array($value)) {
            wpae_print_array($value);
        } else {
            echo $value . "<br/></span>";
        }
    }
}

function wpae_reset_stats(){
	update_option('wpa_stats','{"total":{"today":{"date":"'.date('Ymd').'","count":0},"week":{"date":"'.date('Ymd').'","count":0},"month":{"date":"'.date('Ymd').'","count":0},"all_time":0}}');
}


function wpae_block_ip_action($ip){
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return; // Invalid IP, do nothing
    }

    global $wpdb;

    // Skip localhost
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return;
    }

    $blocked_table = $wpdb->prefix . 'wpae_blocked_ip';

    // Check if IP is already blocked
    $check_blocked_query = $wpdb->prepare("SELECT COUNT(*) FROM $blocked_table WHERE spammer_ip = %s", $ip);
    $is_blocked = $wpdb->get_var($check_blocked_query);

    if ($is_blocked == 0) {
        wpae_insert('wpae_blocked_ip', array('spammer_ip' => $ip));
    }
}

function wpae_unblock_ip_action($ip){
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return; // Invalid IP
    }

    global $wpdb;

    // Skip localhost
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return;
    }

    $blocked_table = $wpdb->prefix . 'wpae_blocked_ip';

    // Check if IP is blocked
    $check_blocked_query = $wpdb->prepare("SELECT COUNT(*) FROM $blocked_table WHERE spammer_ip = %s", $ip);
    $is_blocked = $wpdb->get_var($check_blocked_query);

    if ($is_blocked > 0) { // Only delete if it exists
        wpae_delete('wpae_blocked_ip', array('spammer_ip' => $ip));
    }
}

add_action('wpae_block_ip_action', 'wpae_block_ip_action');
add_action('wpae_unblock_ip_action', 'wpae_unblock_ip_action');