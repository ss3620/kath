/* phpcs:ignoreFile */
jQuery(function(){
	"use strict";

	const productAffiliateLink = {
		singleProductAffiliateLink: '',
		init() {
			this.singleProductAffiliateLink = jQuery('.single-product-affiliate-link');
			jQuery('form.variations_form').on('hide_variation', this.resetAffiliateLink.bind(this)).on('found_variation', this.refreshAffiliateLink.bind(this));
		},
		resetAffiliateLink() {
			this.singleProductAffiliateLink.attr('href', this.singleProductAffiliateLink.attr('data-product-referral-link'));
			this.singleProductAffiliateLink.show().removeClass('disabled');
		},
		refreshAffiliateLink(e, variation) {
			e.preventDefault();
			this.singleProductAffiliateLink.addClass('disabled');
			if (undefined === variation || 'object' !== typeof variation) {
				return;
			}
			if (undefined === variation.variation_id || 0 === parseInt(variation.variation_id)) {
				return;
			}
			let variationId = parseInt(variation.variation_id);
			jQuery.ajax({
				url: afwcAffiliateLinkParams.product.ajaxURL || '',
				type: 'POST',
				dataType: 'json',
				data: {
					product_id: variationId,
					security: afwcAffiliateLinkParams.product.security || '',
				},
				success: res => {
					if (res && res.success) {
						this.singleProductAffiliateLink.attr({
							'href': res.data.url || '',
							'data-ctp': res.data.url || ''
						});
						(res.data && res.data.url) ? this.singleProductAffiliateLink.show().removeClass('disabled') : this.singleProductAffiliateLink.hide();
					}
				},
				error: err => {
					console.log('Cannot get the product\'s affiliate link: ', err);
				}
			})
		}
	};

	// Initialization
	productAffiliateLink.init();
});
