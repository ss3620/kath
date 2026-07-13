/**
 * @author      WPLoyalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://www.wployalty.net/
 * */

if (typeof (wlac_jquery) == 'undefined') {
    wlac_jquery = jQuery.noConflict();
}

wlac = window.wlac || {};
(function (wlac) {
    wlac.saveSettings = function () {
        let data = wlac_jquery('#wlac-settings #wlac-settings_form').serializeArray();
        wlac_jquery('#wlac-settings #wlac-setting-submit-button').attr('disabled', true);
        wlac_jquery('#wlac-settings .wlac-error').remove();
        wlac_jquery("#wlac-settings #wlac-setting-submit-button span").html(wlac_localize_data.saving_button_label);
        wlac_jquery("#wlac-settings .wlac-button-block .spinner").addClass("is-active");
        wlac_jquery.ajax({
            data: data,
            type: 'post',
            url: wlac_localize_data.ajax_url,
            error: function (request, error) {
            },
            success: function (json) {
                alertify.set('notifier', 'position', 'top-right');
                wlac_jquery('#wlac-settings #wlac-setting-submit-button').attr('disabled', false);
                wlac_jquery("#wlac-settings #wlac-setting-submit-button span").html(wlac_localize_data.saved_button_label);
                wlac_jquery("#wlac-settings .wlac-button-block .spinner").removeClass("is-active");
                if (json.error) {
                    if (json.message) {
                        alertify.error(json.message);
                    }

                    if (json.field_error) {
                        wlac_jquery.each(json.field_error, function (index, value) {
                            //alertify.error(value);
                            wlac_jquery(`#wlac-settings #wlac-settings_form .wlac_${index}_value_block`).after('<span class="wlac-error" style="color: red;">' + value + '</span>');
                        });
                    }
                } else {
                    alertify.success(json.message);
                    setTimeout(function () {
                        wlac_jquery("#wlac-settings .wlac-button-block .spinner").removeClass("is-active");
                        location.reload();
                    }, 800);
                }
                if (json.redirect) {
                    window.location.href = json.redirect;
                }
            }
        });
    };
})(wlac);