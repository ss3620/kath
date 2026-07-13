<style type="text/css">
    .wpa_stat_table{width: 100%; border-collapse: collapse;}
    .wpa_stat_table td, .wpa_stat_table th{text-align: center; padding:7px 3px; }
    .wpa_stat_table .first_td{ text-align:left;}
    .wpa_stat_table tr {border-bottom: 2px dashed #fff; }
    .wpa_stat_table tr:nth-child(odd){background: #cdd9ef;}
    .wpa_stat_table tr:nth-child(even){background: #e7ecf7;}
    .wpa_stat_table tr:nth-last-child{border-bottom: none !important; }
    .wpa_stat_table_holder{position: relative;}
    .wpa_stat_overlay{position: absolute; z-index: 10; width: 80%; height: 80%;top: 10%; left: 10%;box-shadow: 0 0 25px 10px rgba(0,0,0,0.08); background: #fff; border-radius: 5px; text-align:center;}

    .wpa_stat_overlay .wpa_stat_headline{font-size: 20px; margin-top: 20px; padding: 5px;}
    .wpa_stat_overlay .wpa_stat_content{padding: 5px;}
    .wpa_stat_button a{ padding: 5px 30px !important; }
</style>
<?php 
$currentStats = json_decode(get_option('wpa_stats'), true);
?>

<div class="wpa_stat_table_holder">
    <table class="wpa_stat_table">
        <tbody>
            <tr>
                <th class="first_td"><strong>Source</strong></th>
                <th><strong>Today</strong></th>
                <th><strong>This Week</strong></th>
                <th><strong>This Month</strong></th>
                <!-- <td><strong>All Time</strong></td>-->
            </tr>
            <?php         
            if (!empty($currentStats)){
                foreach ($currentStats as $source=>$statData): ?>
                    <tr>
                        <td class="first_td"><?php echo ucfirst($source); ?></td>
                        <td><?php echo @wpa_check_date($statData['today']['date'],'today')?$statData['today']['count']:'0'; ?></td>
                        <td><?php echo @wpa_check_date($statData['week']['date'],'week')?$statData['week']['count']:'0'; ?></td>
                        <td><?php echo @wpa_check_date($statData['month']['date'],'month')?$statData['month']['count']:'0'; ?></td>
                        <!-- <td><?php //echo $statData['all_time']; ?></td> -->
                    </tr>
                <?php endforeach;
            } else { ?>
                <tr><td colspan="5">No Record Found</td></tr>
            <?php } ?>

        </tbody>
    </table>
</div>