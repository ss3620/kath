<?php wpae_check_license_status(); ?>
<?php
    global $wpdb;

    $wpae_nonce = '&_wpnonce='.wp_create_nonce( 'wpae_action_nonce' );

    if ( isset( $_GET['action'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpae_action_nonce' ) ) {
        switch ($_GET['action']) {
            case 'bulkdelete':
                wpae_bulk_truncate('wpae_blocked_ip');
                $actionReturn = array('status' => 'ok','body'=>'All Records Deleted' );
                break;
            case 'delete':
                wpae_delete('wpae_blocked_ip','spammer_ip = "'.esc_sql($_GET['ip']).'"');
                $actionReturn = array('status' => 'ok','body'=>$_GET['ip'].' Unblocked' );
                break;
        }
    }

    $prefixed_wpae_blocked_ip = $wpdb->prefix . 'wpae_blocked_ip';
    $currentPage = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $limit = $GLOBALS['wpae_data_per_page'];
    $offset = ($currentPage - 1) * $limit;

    // Search filter by IP
    $search_ip = isset($_GET['search_ip']) ? trim($_GET['search_ip']) : '';

    $where_clause = '';
    if (!empty($search_ip)) {
        $where_clause = "WHERE spammer_ip LIKE '%" . esc_sql($search_ip) . "%'";
    }

    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $prefixed_wpae_blocked_ip $where_clause");

    $num_pages = ceil($total_records / $limit);

    $submission_details = $wpdb->get_results("
        SELECT * FROM $prefixed_wpae_blocked_ip 
        $where_clause 
        ORDER BY id DESC 
        LIMIT $limit OFFSET $offset
    ");
?>

<?php if (isset($actionReturn)): ?>
    <div class="updated <?php echo $actionReturn['status']; ?>" id="message"><p><?php echo $actionReturn['body']; ?></p></div>
<?php endif; ?>

<!-- Search Filter -->
<form method="GET" action="" class="wpa_filter_form">
    <input type="hidden" name="page" value="wp-armour">
    <input type="hidden" name="tab" value="blocked_ips">
    <input type="text" name="search_ip" placeholder="Search by IP" value="<?php echo esc_attr($search_ip); ?>">
    <button type="submit">Search</button>
</form>

<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=blocked_ips', $num_pages, $currentPage); ?>
</div>

<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=blocked_ips&paged=<?php echo $currentPage; ?>&action=bulkdelete<?php echo $wpae_nonce; ?>" onclick="return confirm('Are you sure?')">Delete All Blocked IPs</a>
</div>
<div style="clear: both;"></div>

<table class="wp-list-table widefat">
    <thead>
        <tr>
            <th><strong>Sn</strong></th>
            <th><strong>Timestamp</strong></th>
            <th><strong>IP</strong></th>
            <th><strong>Actions</strong></th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $cnt = $offset;
        if (!empty($submission_details)) {
            foreach ($submission_details as $submission_detail): 
                $cnt++;
        ?>
            <tr>
                <td><?php echo $cnt; ?></td>
                <td><?php echo esc_html($submission_detail->updated_on); ?></td>
                <td><?php echo esc_html($submission_detail->spammer_ip); ?></td>
                <td>
                    <a href="admin.php?page=wp-armour&tab=blocked_ips&paged=<?php echo $currentPage; ?>&action=delete&ip=<?php echo esc_attr($submission_detail->spammer_ip . $wpae_nonce); ?>" onclick="return confirm('Are you sure? Deleting the IP will unblock it.')">Delete</a>
                </td>
            </tr>            
        <?php endforeach;
        } else { ?>
            <tr>
                <td colspan="4">No Records Found.</td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=blocked_ips', $num_pages, $currentPage); ?>
</div>

<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=blocked_ips&action=bulkdelete<?php echo $wpae_nonce; ?>" onclick="return confirm('Are you sure?')">Delete All Blocked IPs</a>
</div>
<div style="clear: both;"></div>


<?php /*wpae_check_license_status(); ?>
<?php
    global $wpdb;

    $wpae_nonce = '&_wpnonce='.wp_create_nonce( 'wpae_action_nonce' );

    if ( isset( $_GET['action'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpae_action_nonce' ) ) {
        switch ($_GET['action']) {
            case 'bulkdelete':
                    wpae_bulk_truncate('wpae_blocked_ip');
                    $actionReturn = array('status' => 'ok','body'=>'All Records Deleted' );
                break;
            case 'delete':
                    wpae_delete('wpae_blocked_ip','spammer_ip = "'.$_GET['ip'].'"');
                    $actionReturn = array('status' => 'ok','body'=>$_GET['ip'].' Unblocked' );
                break;
        }
    }

    $prefixed_wpae_blocked_ip      =   $wpdb->prefix . 'wpae_blocked_ip';
    $currentPage            = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $limit                  =   $GLOBALS['wpae_data_per_page'];
    
    $total_records          =   $wpdb->get_var( "SELECT COUNT(*) FROM $prefixed_wpae_blocked_ip");

    $num_pages              =   ceil($total_records / $limit) ;
    $offset                 =   ($currentPage - 1) * $limit;
    
    ;   
    $submission_details     =   $wpdb->get_results("SELECT * FROM $prefixed_wpae_blocked_ip ORDER BY id DESC LIMIT $limit OFFSET $offset");    
    
?>

<?php if (isset($actionReturn)):?>
    <div class="updated <?php echo $actionReturn['status']; ?>" id="message"><p><?php echo $actionReturn['body']; ?></p></div>
<?php endif; ?>

<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=blocked_ips',$num_pages,$currentPage); ?>
</div>

<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=blocked_ips&paged=<?php echo $currentPage; ?>&action=bulkdelete<?php echo $wpae_nonce ?>" onclick="return confirm('Are you sure?')">Delete All Blocked Ips</a>
</div>
<div style="clear: both;"></div>

<table class="wp-list-table widefat">
    <thead>
    <tr>
        <th><strong>Sn</strong></th>
        <th><strong>Timestamp</strong></th>
        <th><strong>IP</strong></th>
        <th><strong>Actions</strong></th>
    </tr>
    </thead>
    <tbody>
        <?php 
        $cnt = $offset;
        if (!empty($submission_details)){
        foreach ($submission_details as $submission_detail): 
            $cnt++;
        ?>
            <tr>
                <td><?php echo $cnt; ?></td>
                <td><?php echo $submission_detail->updated_on; ?></td>
                <td><?php echo $submission_detail->spammer_ip; ?></td>
                <td>
                    <a href="admin.php?page=wp-armour&tab=blocked_ips&paged=<?php echo $currentPage; ?>&action=delete&ip=<?php echo $submission_detail->spammer_ip.$wpae_nonce; ?>" onclick="return confirm('Are you sure? Deleting the IP will unlblock as well.')">Delete</a>
                </td>
                
            </tr>            
        <?php endforeach;
        } else { ?>
            <tr>
                <td colspan="4">No Records Found.</td>
            </tr>
        <?php } ?>
    </tbody>


</table>
<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=blocked_ips',$num_pages,$currentPage); ?>
</div>
<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=blocked_ips&action=bulkdelete<?php echo $wpae_nonce ?>" onclick="return confirm('Are you sure?')">Delete All Blocked Ips</a>
</div>
<div style="clear: both;"></div> <?php */ ?>