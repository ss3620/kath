<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.wployalty.net
 * */

defined('ABSPATH') or die;
$enable_conversion_in_page = (empty($options) || !isset($options['enable_conversion_in_page'])) || ($options['enable_conversion_in_page'] == 'yes');
?>
<div id="wlac-main">
    <div class="wlac-main-header">
        <h1><?php echo WLAC_PLUGIN_NAME; ?> </h1>
        <div><b><?php echo "v" . WLAC_PLUGIN_VERSION; ?></b></div>
    </div>
    <div class="wlac-tabs">
        <a class="nav-tab-active"
           href="<?php echo esc_url(admin_url('admin.php?' . http_build_query(array('page' => WLAC_PLUGIN_SLUG)))) ?>"
        ><i class="wlr wlrf-settings"></i><?php esc_html_e('Settings', 'wp-loyalty-auto-currency') ?></a>
    </div>
    <div>
        <div id="wlac-settings">
            <div class="wlac-setting-page-holder">
                <div class="wlac-spinner">
                    <span class="spinner"></span>
                </div>
                <form id="wlac-settings_form" method="post">
                    <div class="wlac-settings-header">
                        <div class="wlac-setting-heading">
                            <p><?php esc_html_e('SETTINGS', 'wp-loyalty-auto-currency') ?></p>
                        </div>
                        <div class="wlac-button-block">
                            <div class="wlac-back-to-apps wlac-button">
                                <a class="button back-to-apps" target="_self"
                                   href="<?php echo isset($app_url) ? esc_url($app_url) : '#'; ?>">
                                    <?php esc_html_e('Back to WPLoyalty', 'wp-loyalty-auto-currency'); ?></a>
                            </div>
                            <div class="wlac-save-changes wlac-button">
                                <a class="button" id="wlac-setting-submit-button"
                                   href="javascript:void(0);" onclick="wlac.saveSettings();">
                                    <?php esc_html_e('Save Changes', 'wp-loyalty-auto-currency'); ?></a>
                            </div>
                            <span class='spinner'></span>
                        </div>
                    </div>
                    <div class="wlac-setting-body">
                        <div class="wlac-settings-body-content">
                            <div class="wlac-field-block">
                                <div>
                                    <label
                                            class="wlac-settings-enable-conversion-label"><?php esc_html_e('Enable Currency conversion in WPLoyalty pages', 'wp-loyalty-auto-currency'); ?></label>
                                </div>
                                <div class="wlac-input-field">
                                    <select name="enable_conversion_in_page">
                                        <option
                                                value="no" <?php echo !$enable_conversion_in_page ? 'selected="selected"' : ''; ?> ><?php esc_html_e('No', 'wp-loyalty-auto-currency'); ?></option>
                                        <option
                                                value="yes" <?php echo $enable_conversion_in_page ? 'selected="selected"' : ''; ?>><?php esc_html_e('Yes', 'wp-loyalty-auto-currency'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="action" value="wlac_save_settings">
                            <input type="hidden" name="wlac_nonce"
                                   value="<?php echo isset($wlac_setting_nonce) && !empty($wlac_setting_nonce) ? esc_attr($wlac_setting_nonce) : ''; ?>">
                            <input type="hidden" name="option_key"
                                   value="wlac_settings">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>