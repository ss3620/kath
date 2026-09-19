jQuery("#woocommerce_zipmoney_sandbox").change(function () {
    var sandbox = jQuery("#woocommerce_zipmoney_sandbox_merchant_public_key, #woocommerce_zipmoney_sandbox_merchant_private_key").closest('tr');
    var production = jQuery("#woocommerce_zipmoney_merchant_public_key, #woocommerce_zipmoney_merchant_private_key").closest('tr');

    if (jQuery(this).is(':checked')) {
        sandbox.show();
        production.hide();
    } else {
        sandbox.hide();
        production.show();
    }

}).change();

jQuery("#woocommerce_zipmoney_display_banners").change(function () {

    var banner_settings = jQuery("#woocommerce_zipmoney_display_banner_shop, #woocommerce_zipmoney_display_banner_product_page, #woocommerce_zipmoney_display_banner_category, #woocommerce_zipmoney_display_banner_cart");
    var bannerSettingsTr = banner_settings.closest("tr");

    if (jQuery(this).is(':checked')) {
        bannerSettingsTr.show();
    } else {
        bannerSettingsTr.hide();
    }

}).change();

jQuery('.check_private_key').click(function () {
    const elements = document.getElementsByClassName("zip-notice");
    const zipspinner = document.getElementsByClassName("zip-spinner");
    while (elements.length > 0) elements[0].remove();
    var checkValidateBtn = document.getElementById('woocommerce_zipmoney_check_credentials');
    checkValidateBtn.insertAdjacentHTML('afterend', '<div class="zip-spinner"></div>');
    jQuery(".zip-spinner").addClass("is-active");
    var environment = 'production';
    var privatekey = '';
    if (jQuery('#woocommerce_zipmoney_sandbox').is(":checked"))
    {
        environment = 'sandbox';
        privatekey = jQuery("#woocommerce_zipmoney_sandbox_merchant_private_key").val();
    }
    else {
        privatekey = jQuery("#woocommerce_zipmoney_merchant_private_key").val();
    }
    var data = {
        private_key: privatekey,
        environment: environment
    };
    var url = ZipApiKeyCheckUrl;
    // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
    jQuery.post(url, data, function(response) {
        checkValidateBtn.insertAdjacentHTML('afterend', response['message']);
        // alert(response['message']);
    }).always(function(){
        while (zipspinner.length > 0) zipspinner[0].remove();
    });
});