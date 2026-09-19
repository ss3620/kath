<?php wpae_check_license_status(); ?>
<?php
    global $wpdb;

    $wpae_nonce = '&_wpnonce='.wp_create_nonce( 'wpae_action_nonce' );

    // Get filter values
    $selected_source = isset($_GET['source_filter']) ? sanitize_text_field($_GET['source_filter']) : '';
    $searched_ip = isset($_GET['search_ip']) ? sanitize_text_field($_GET['search_ip']) : '';

    if ( isset( $_GET['action'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpae_action_nonce' ) ) {
        switch ($_GET['action']) {
            case 'bulkdelete':
                    wpae_bulk_truncate('wpae_spam_data');
                    $actionReturn = array('status' => 'ok','body'=>'All Records Deleted' );
                break;
            case 'delete':
                    wpae_delete('wpae_spam_data','id = "'.$_GET['id'].'"');
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

    $prefixed_wpae_spam_data = $wpdb->prefix . 'wpae_spam_data';
    $prefixed_wpae_blocked_ip = $wpdb->prefix . 'wpae_blocked_ip';
    $currentPage = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $limit = $GLOBALS['wpae_data_per_page'];
    $offset = ($currentPage - 1) * $limit;

    // Building the WHERE clause
    $where_conditions = "1=1"; // Default condition

    if (!empty($selected_source)) {
        $where_conditions .= $wpdb->prepare(" AND spammer.wp_system = %s", $selected_source);
    }

    if (!empty($searched_ip)) {
        $where_conditions .= $wpdb->prepare(" AND spammer.spammer_ip LIKE %s", '%' . $wpdb->esc_like($searched_ip) . '%');
    }

    // Get total records count
    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $prefixed_wpae_spam_data as spammer WHERE $where_conditions");
    $num_pages = ceil($total_records / $limit);

    // Fetch filtered results
    $submission_details = $wpdb->get_results(
        "SELECT spammer.*, blocked.id as blocked_id 
        FROM $prefixed_wpae_spam_data as spammer 
        LEFT JOIN $prefixed_wpae_blocked_ip as blocked 
        ON spammer.spammer_ip = blocked.spammer_ip 
        WHERE $where_conditions 
        ORDER BY spammer.id DESC 
        LIMIT $limit OFFSET $offset"
    );

    // Get all unique sources for the filter dropdown
    $sources = $wpdb->get_col("SELECT DISTINCT wp_system FROM $prefixed_wpae_spam_data WHERE wp_system IS NOT NULL AND wp_system != ''");
?>

<!-- Filter Form -->
<form method="GET" action="" class="wpa_filter_form">
    <input type="hidden" name="page" value="wp-armour">
    <input type="hidden" name="tab" value="spam_data">
    <label for="source_filter">Filter by Source:</label>
    <select name="source_filter" id="source_filter">
        <option value="">All Sources</option>
        <?php foreach ($sources as $source): ?>
            <option value="<?php echo esc_attr($source); ?>" <?php selected($selected_source, $source); ?>><?php echo esc_html($source); ?></option>
        <?php endforeach; ?>
    </select>

    <label for="search_ip">Search by IP:</label>
    <input type="text" name="search_ip" id="search_ip" value="<?php echo esc_attr($searched_ip); ?>" placeholder="Enter IP">

    <button type="submit">Apply</button>
</form>

<?php if (isset($actionReturn)): ?>
    <div class="updated <?php echo $actionReturn['status']; ?>" id="message"><p><?php echo $actionReturn['body']; ?></p></div>
<?php endif; ?>

<!-- Pagination -->
<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=spam_data', $num_pages, $currentPage); ?>
</div>

<!-- Bulk Actions -->
<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=spam_data&paged=<?php echo $currentPage; ?>&action=bulkdelete<?php echo $wpae_nonce ?>" onclick="return confirm('Are you sure?')">Remove All Spam Submissions</a>
</div>

<table class="wp-list-table widefat">
    <thead>
        <tr>
            <th><strong>Sn</strong></th>
            <th><strong>Timestamp</strong></th>
            <th><strong>IP</strong></th>
            <th><strong>Source</strong></th>
            <th><strong>Actions</strong></th>
            <th><strong>Data tried to Submit</strong></th>
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
                    <td><?php echo esc_html($submission_detail->wp_system); ?></td>
                    <td>
                        <?php if($submission_detail->blocked_id == null): ?>
                            <a href="admin.php?page=wp-armour&tab=spam_data&paged=<?php echo $currentPage; ?>&action=blockip&ip=<?php echo $submission_detail->spammer_ip.$wpae_nonce; ?>">Block IP</a> 
                        <?php else: ?>
                            <a href="admin.php?page=wp-armour&tab=spam_data&paged=<?php echo $currentPage; ?>&action=unblockip&ip=<?php echo $submission_detail->spammer_ip.$wpae_nonce; ?>">Unblock IP</a> 
                        <?php endif; ?> |
                        <a href="admin.php?page=wp-armour&tab=spam_data&paged=<?php echo $currentPage; ?>&action=delete&id=<?php echo $submission_detail->id.$wpae_nonce; ?>">Delete</a>
                    </td>
                    <td>
                        <div class="submitted_spam_data">
                            <?php 
                            $spamDatas = json_decode($submission_detail->spam_data, true);
                            if (!empty($spamDatas)) {
                                wpae_print_array($spamDatas);
                            } else {
                                echo 'No Data Available';
                            }
                            ?>
                        </div>    
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
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=spam_data', $num_pages, $currentPage); ?>
</div>


<?php /* wpae_check_license_status(); ?>
<?php
    global $wpdb;

    $wpae_nonce = '&_wpnonce='.wp_create_nonce( 'wpae_action_nonce' );

    if ( isset( $_GET['action'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpae_action_nonce' ) ) {
        switch ($_GET['action']) {
            case 'bulkdelete':
                    wpae_bulk_truncate('wpae_spam_data');
                    $actionReturn = array('status' => 'ok','body'=>'All Records Deleted' );
                break;
            case 'delete':
                    wpae_delete('wpae_spam_data','id = "'.$_GET['id'].'"');
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

    $prefixed_wpae_spam_data       =   $wpdb->prefix . 'wpae_spam_data';
    $prefixed_wpae_blocked_ip      =   $wpdb->prefix . 'wpae_blocked_ip';
    $currentPage            = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
    $limit                  =   $GLOBALS['wpae_data_per_page'];
    
    $total_records          =   $wpdb->get_var( "SELECT COUNT(*) FROM $prefixed_wpae_spam_data");

    $num_pages              =   ceil($total_records / $limit) ;
    $offset                 =   ($currentPage - 1) * $limit;
    
    ;   
    $submission_details     =   $wpdb->get_results("SELECT spammer.*, blocked.id as blocked_id FROM $prefixed_wpae_spam_data as spammer left join $prefixed_wpae_blocked_ip as blocked ON spammer.spammer_ip = blocked.spammer_ip ORDER BY spammer.id DESC LIMIT $limit OFFSET $offset");    
    
?>

<?php if (isset($actionReturn)):?>
    <div class="updated <?php echo $actionReturn['status']; ?>" id="message"><p><?php echo $actionReturn['body']; ?></p></div>
<?php endif; ?>

<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=spam_data',$num_pages,$currentPage); ?>
</div>

<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=spam_data&paged=<?php echo $currentPage; ?>&action=bulkdelete<?php echo $wpae_nonce ?>" onclick="return confirm('Are you sure?')">Remove All Spam Submissions</a>
</div>
<div style="clear: both;"></div>

<table class="wp-list-table widefat">
    <thead>
    <tr>
        <th><strong>Sn</strong></th>
        <th><strong>Timestamp</strong></th>
        <th><strong>IP</strong></th>
        <th><strong>Source</strong></th>
        <th><strong>Actions</strong></th>
        <th><strong>Data tried to Submit</strong></th>
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
                <td><?php echo $submission_detail->wp_system; ?></td>
                <td>
                    <?php
                    if($submission_detail->blocked_id == null){ ?>
                    <a href="admin.php?page=wp-armour&tab=spam_data&paged=<?php echo $currentPage; ?>&action=blockip&ip=<?php echo $submission_detail->spammer_ip.$wpae_nonce; ?>">Block IP</a> 
                    <?php } else { ?>
                        <a href="admin.php?page=wp-armour&tab=spam_data&paged=<?php echo $currentPage; ?>&action=unblockip&ip=<?php echo $submission_detail->spammer_ip.$wpae_nonce; ?>">Unblock IP</a> 
                    <?php } ?> |
                    <a href="admin.php?page=wp-armour&tab=spam_data&paged=<?php echo $currentPage; ?>&action=delete&id=<?php echo $submission_detail->id.$wpae_nonce; ?>">Delete</a>
                </td>
                <td>
                    <div class="submitted_spam_data">
                            <?php 
                            $spamDatas = json_decode($submission_detail->spam_data, true);
                            if (!empty($spamDatas)){
                                wpae_print_array($spamDatas);
                            } else {
                                echo 'No Data Available';
                            }
                            ?>
                        
                    </div>    
                </td>
            </tr>            
        <?php endforeach;
        } else { ?>
            <tr>
                <td colspan="6">No Records Found. May be you haven't enabled Spammer Submission Log ?</td>
            </tr>
        <?php } ?>
    </tbody>


</table>
<div class="wpae_pagination">
    <?php wpae_get_pagination_navigation('admin.php?page=wp-armour&tab=spam_data',$num_pages,$currentPage); ?>
</div>
<div class="wpae_bulk_actions">
    <a href="admin.php?page=wp-armour&tab=spam_data&action=bulkdelete<?php echo $wpae_nonce ?>" onclick="return confirm('Are you sure?')">Remove All Spam Submissions</a>
</div>
<div style="clear: both;"></div><?php */ ?>