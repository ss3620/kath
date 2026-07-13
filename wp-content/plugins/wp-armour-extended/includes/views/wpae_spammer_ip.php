<?php wpae_check_license_status(); ?>
<?php
    global $wpdb;

    $wpae_nonce = '&_wpnonce='.wp_create_nonce( 'wpae_action_nonce' );

    if ( isset( $_GET['action'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpae_action_nonce' ) ) {
        switch ($_GET['action']) {
            case 'bulkdelete':
                wpae_bulk_truncate('wpae_spammer_ip');
                $actionReturn = array('status' => 'ok','body'=>'All Records Deleted' );
                break;
            case 'delete':
                wpae_delete('wpae_spammer_ip','spammer_ip = "'.esc_sql($_GET['ip']).'"');
                $actionReturn = array('status' => 'ok','body'=>'1 Record Deleted' );
                break;
            case 'blockip':
                wpae_insert('wpae_blocked_ip', array('spammer_ip' =>esc_sql($_GET['ip'])));
                $actionReturn = array('status' => 'ok','body'=>$_GET['ip'].' Blocked' );
                break;
            case 'unblockip':
                wpae_delete('wpae_blocked_ip','spammer_ip = "'.esc_sql($_GET['ip']).'"');
                $actionReturn = array('status' => 'ok','body'=>$_GET['ip'].' Unblocked' );
                break;
        }
    }

    $prefixed_wpae_spammer_ip = $wpdb->prefix . 'wpae_spammer_ip';
    $prefixed_wpae_blocked_ip = $wpdb->prefix . 'wpae_blocked_ip';
    $currentPage = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $limit = $GLOBALS['wpae_data_per_page'];
    $offset = ($currentPage - 1) * $limit;

    // Search filter by IP
    $search_ip = isset($_GET['search_ip']) ? trim($_GET['search_ip']) : '';

    $where_clause = '';
    if (!empty($search_ip)) {
        $where_clause = "WHERE spammer.spammer_ip LIKE '%" . esc_sql($search_ip) . "%'";
    }

    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM (SELECT COUNT(*) FROM $prefixed_wpae_spammer_ip $where_clause GROUP BY spammer_ip) AS group_count");

    $num_pages = ceil($total_records / $limit);

    $spammer_details = $wpdb->get_results("
        SELECT spammer.*, blocked.id as blocked_id, COUNT(spammer.id) as no_of_submission 
        FROM $prefixed_wpae_spammer_ip as spammer 
        LEFT JOIN $prefixed_wpae_blocked_ip as blocked ON spammer.spammer_ip = blocked.spammer_ip 
        $where_clause
        GROUP BY spammer.spammer_ip 
        ORDER BY no_of_submission DESC 
        LIMIT $limit OFFSET $offset
    ");
?>

<?php if (isset($actionReturn)): ?>
    <div class="updated <?php echo $actionReturn['status']; ?>" id="message"><p><?php echo $actionReturn['body']; ?></p></div>
<?php endif; ?>

<!-- Search Filter -->
<form method="GET" action="" class="wpa_filter_form">
    <input type="hidden" name="page" value="wp-armour">
    <input type="hidden" name="tab" value="spammer_ip">
    <input type="text" name="search_ip" placeholder="Search by IP" value="<?php echo esc_attr($search_ip); ?>">
    <button type="submit">Search</button>
</form>

<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=spammer_ip', $num_pages, $currentPage); ?>
</div>

<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=spammer_ip&paged=<?php echo $currentPage; ?>&action=bulkdelete<?php echo $wpae_nonce; ?>" onclick="return confirm('Are you sure?')">Remove All Spammer IPs</a>
</div>
<div style="clear: both;"></div>

<table class="wp-list-table widefat">
    <thead>
        <tr>
            <th><strong>Sn</strong></th>
            <th><strong>Timestamp</strong></th>
            <th><strong>IP</strong></th>
            <th><strong>Source</strong></th>
            <th><strong>No of Submissions Attempt</strong></th>
            <th><strong>Actions</strong></th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $cnt = $offset;
        if (!empty($spammer_details)) {
            foreach ($spammer_details as $spammer_detail): 
                $cnt++;
        ?>
            <tr>
                <td><?php echo $cnt; ?></td>
                <td><?php echo esc_html($spammer_detail->updated_on); ?></td>
                <td><?php echo esc_html($spammer_detail->spammer_ip); ?></td>
                <td><?php echo esc_html($spammer_detail->wp_system); ?></td>
                <td><?php echo esc_html($spammer_detail->no_of_submission); ?></td>
                <td>
                    <?php if ($spammer_detail->blocked_id == null) { ?>
                        <a href="admin.php?page=wp-armour&tab=spammer_ip&paged=<?php echo $currentPage; ?>&action=blockip&ip=<?php echo esc_attr($spammer_detail->spammer_ip . $wpae_nonce); ?>">Block IP</a> 
                    <?php } else { ?>
                        <a href="admin.php?page=wp-armour&tab=spammer_ip&paged=<?php echo $currentPage; ?>&action=unblockip&ip=<?php echo esc_attr($spammer_detail->spammer_ip . $wpae_nonce); ?>">Unblock IP</a> 
                    <?php } ?> |
                    <a href="admin.php?page=wp-armour&tab=spammer_ip&paged=<?php echo $currentPage; ?>&action=delete&ip=<?php echo esc_attr($spammer_detail->spammer_ip . $wpae_nonce); ?>">Delete</a>
                </td>
            </tr>            
        <?php endforeach;
        } else { ?>
            <tr>
                <td colspan="6">No Records Found.</td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=spammer_ip', $num_pages, $currentPage); ?>
</div>

<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=spammer_ip&action=bulkdelete<?php echo $wpae_nonce; ?>" onclick="return confirm('Are you sure?')">Remove All Spammer IPs</a>
</div>
<div style="clear: both;"></div>

<?php /* wpae_check_license_status(); ?>
<?php
    global $wpdb;

    $wpae_nonce = '&_wpnonce='.wp_create_nonce( 'wpae_action_nonce' );

    if ( isset( $_GET['action'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpae_action_nonce' ) ) {
        switch ($_GET['action']) {
            case 'bulkdelete':
                    wpae_bulk_truncate('wpae_spammer_ip');
                    $actionReturn = array('status' => 'ok','body'=>'All Records Deleted' );
                break;
            case 'delete':
                    wpae_delete('wpae_spammer_ip','spammer_ip = "'.$_GET['ip'].'"');
                    $actionReturn = array('status' => 'ok','body'=>'1 Record Deleted' );
                break;
            case 'blockip':
                    wpae_insert('wpae_blocked_ip', array('spammer_ip' =>$_GET['ip']));
                    $actionReturn = array('status' => 'ok','body'=>$_GET['ip'].' Blocked' );
                break;
            case 'unblockip':
                    wpae_delete('wpae_blocked_ip','spammer_ip = "'.$_GET['ip'].'"');
                    $actionReturn = array('status' => 'ok','body'=>$_GET['ip'].' Unblocked' );
                break;
        }
    }

    $prefixed_wpae_spammer_ip       =   $wpdb->prefix . 'wpae_spammer_ip';
    $prefixed_wpae_blocked_ip       =   $wpdb->prefix . 'wpae_blocked_ip';
    $currentPage            = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $limit                  =   $GLOBALS['wpae_data_per_page'];
    
    $total_records          =   $wpdb->get_var( "SELECT COUNT(*) FROM (SELECT COUNT(*) FROM $prefixed_wpae_spammer_ip GROUP BY spammer_ip) AS group_count" );

    $num_pages              =   ceil($total_records / $limit) ;
    $offset                 =   ($currentPage - 1) * $limit;
    
    ;   
    $spammer_details     =   $wpdb->get_results("SELECT spammer.*, blocked.id as blocked_id, COUNT(spammer.id) as no_of_submission FROM $prefixed_wpae_spammer_ip as spammer left join $prefixed_wpae_blocked_ip as blocked ON spammer.spammer_ip = blocked.spammer_ip GROUP BY spammer.spammer_ip ORDER BY no_of_submission DESC LIMIT $limit OFFSET $offset");    
    
?>

<?php if (isset($actionReturn)):?>
    <div class="updated <?php echo $actionReturn['status']; ?>" id="message"><p><?php echo $actionReturn['body']; ?></p></div>
<?php endif; ?>

<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=spammer_ip',$num_pages,$currentPage); ?>
</div>

<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=spammer_ip&paged=<?php echo $currentPage; ?>&action=bulkdelete<?php echo $wpae_nonce ?>" onclick="return confirm('Are you sure?')">Remove All Spammer IPs</a>
</div>
<div style="clear: both;"></div>

<table class="wp-list-table widefat">
    <thead>
    <tr>
        <th><strong>Sn</strong></th>
        <th><strong>Timestamp</strong></th>
        <th><strong>IP</strong></th>
        <th><strong>Source</strong></th>
        <th><strong>No of Submissions Attempt</strong></th>
        <th><strong>Actions</strong></th>
    </tr>
    </thead>
    <tbody>
        <?php 
        $cnt = $offset;
        if (!empty($spammer_details)){
        foreach ($spammer_details as $spammer_detail): 
            $cnt++;
        ?>
            <tr>
                <td><?php echo $cnt; ?></td>
                <td><?php echo $spammer_detail->updated_on; ?></td>
                <td><?php echo $spammer_detail->spammer_ip; ?></td>
                <td><?php echo $spammer_detail->wp_system; ?></td>
                <td><?php echo $spammer_detail->no_of_submission; ?></td>
                <td>
                    <!-- <a href="">View Spam Submissions</a> | -->
                    <?php
                    if($spammer_detail->blocked_id == null){ ?>
                    <a href="admin.php?page=wp-armour&tab=spammer_ip&paged=<?php echo $currentPage; ?>&action=blockip&ip=<?php echo $spammer_detail->spammer_ip.$wpae_nonce; ?>">Block IP</a> 
                    <?php } else { ?>
                        <a href="admin.php?page=wp-armour&tab=spammer_ip&paged=<?php echo $currentPage; ?>&action=unblockip&ip=<?php echo $spammer_detail->spammer_ip.$wpae_nonce; ?>">Unblock IP</a> 
                    <?php } ?> |
                    <a href="admin.php?page=wp-armour&tab=spammer_ip&paged=<?php echo $currentPage; ?>&action=delete&ip=<?php echo $spammer_detail->spammer_ip.$wpae_nonce; ?>">Delete</a>
                </td>
            </tr>            
        <?php endforeach;
        } else { ?>
            <tr>
                <td colspan="5">No Records Found. May be you haven't enabled Spammer IP Log ?</td>
            </tr>
        <?php } ?>
    </tbody>


</table>
<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=spammer_ip',$num_pages,$currentPage); ?>
</div>
<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=spammer_ip&action=bulkdelete<?php echo $wpae_nonce ?>" onclick="return confirm('Are you sure?')">Remove All Spammer IPs</a>
</div>
<div style="clear: both;"></div><?php */ ?>
