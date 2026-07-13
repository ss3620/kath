/* phpcs:ignoreFile */
document.addEventListener('DOMContentLoaded', () => {
	const { _x, sprintf } = wp.i18n;

	document.getElementById('afwc_referral_coupon_form')?.addEventListener('submit', (e) => {
		e.preventDefault();

		if(!afwcCreateCouponParams?.canCreateReferralCoupons) {
			return;
		}

		const msg = document.getElementById('afwc_generate_coupon_msg');
		const createCouponBtn = document.getElementById('afwc_create_coupon_by_affiliate');
		const loader = document.getElementById('afwc_generate_coupon_loader');
		const hideSectionBtn = document.getElementById('afwc_hide_referral_coupon');

		const couponCode = document.getElementById('afwc_referral_coupon_code')?.value?.trim() || '';
		const couponAmount = Number(document.getElementById('afwc_referral_coupon_amount')?.value || 0);
		const maxAllowedDiscount = Number(afwcCreateCouponParams?.maxReferralCouponAmount || 0);

		msg && (msg.className = '', msg.textContent = '');

		let err = '';
		if(!couponCode) {
			err = _x('Enter a coupon code.', 'validation error when coupon code is empty', 'affiliate-for-woocommerce');
		} else if(couponAmount < 0 || couponAmount > 100 || !Number.isFinite(couponAmount)) {
			err = _x('Discount amount is invalid.', 'validation error when discount amount is invalid', 'affiliate-for-woocommerce');
		} else if(couponAmount > maxAllowedDiscount) {
			err = _x('Discount is above the allowed maximum.', 'validation error when discount amount exceeds allowed maximum amount', 'affiliate-for-woocommerce');
		}

		if(err) {
			msg && (msg.classList.add('afwc_error'), msg.textContent = err);
			return;
		}

		createCouponBtn && (createCouponBtn.disabled = true);
		loader && (loader.style.display = 'inline-block');
		hideSectionBtn && (hideSectionBtn.style.display = 'none');

		jQuery.ajax({
			url: afwcCreateCouponParams.generateReferralCouponEndpoint,
			method: 'POST',
			data: {
				action: 'afwc_generate_referral_coupon',
				code: couponCode,
				amount: couponAmount,
				security: afwcCreateCouponParams?.generateReferralCouponSecurity
			},
			success: (res) => {
				if(res?.success) {
					msg && (msg.classList.add('afwc_success'), msg.textContent = res.data?.message || _x('Success.', 'success message when referral coupon is created successfully', 'affiliate-for-woocommerce'));
					createCouponBtn && (createCouponBtn.style.display = 'none');

					document.querySelectorAll('.afwc-generate-coupon-title, .afwc-generate-coupon-desc, .afwc-referral-coupon-amount-row, .afwc-referral-coupon-code-row').forEach(el => el.style.display = 'none');

					setTimeout(() => location.reload(), 2000);
				} else {
					handleError(res?.data?.message);
				}
			},
			error: () => handleError(_x('Something went wrong.', 'error message when an unexpected error occurs in referral coupon creation', 'affiliate-for-woocommerce'))
		});

		function handleError(text) {
			loader && (loader.style.display = 'none');
			hideSectionBtn && (hideSectionBtn.style.display = '');
			createCouponBtn && (createCouponBtn.disabled = false);
			msg && (msg.classList.add('afwc_error'), msg.textContent = text || _x('Failed.', 'error message when referral coupon creation fails', 'affiliate-for-woocommerce'));
		}
	});

	jQuery('#afwc_show_generate_referral_coupon, #afwc_hide_referral_coupon').on('click', function(e) {
		e.preventDefault();
		jQuery('#afwc_referral_coupon_form').slideToggle();
	});
});
