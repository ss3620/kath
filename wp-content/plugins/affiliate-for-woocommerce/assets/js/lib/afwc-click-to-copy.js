/* phpcs:ignoreFile */
(function() {
	"use strict";

	const {_x} = wp.i18n;

	const AFWClickToCopy = {
		selector: '.afwc-click-to-copy',
		init() {
			const elements = document?.querySelectorAll?.(this.selector) || null;
			!!elements && elements.forEach(element => element.addEventListener('click', this.copy.bind(this)));

			// For now only selected elements will have the 'Copied' text for 1 sec after copying
			// But in future, it should work for all `.afwc-click-to-copy` without adding any extra code.
			var addCopiedTextInElements = document.querySelectorAll(
				'.single-product-affiliate-link.afwc-click-to-copy, ' +
				'table.afwc_coupons td .afwc-click-to-copy, ' +
				'#afwc_copy_referral_link_button.afwc-click-to-copy'
			);
			for(var i = 0;i < addCopiedTextInElements.length;i++) {
				addCopiedTextInElements[i].addEventListener('copied', this.afterCopy);
			}
		},
		async copy(e = null) {
			e.preventDefault();
			const target = e?.target || null;
			const text = target?.getAttribute?.('data-ctp') || '';
			if(!text) {
				return;
			}
			const element = document?.createElement?.('input') || null;
			if(!element) {
				return;
			}
			document?.body?.appendChild?.(element);
			element.value = text;
			element.select?.();
			try {
				if(navigator?.clipboard) {
					await this.copyToClipboard(element.value);
					target.dispatchEvent(new Event('copied'));
				}
				element.remove?.();
			} catch(err) {
				console.error('Failed to copy: ', err);
			}
		},
		copyToClipboard(text = '') {
			return text ? navigator.clipboard.writeText(text)
				.then(() => Promise.resolve())
				.catch(err => Promise.reject(err)) : Promise.reject();
		},
		afterCopy(e) {
			var el = e.target;
			if(el.classList.contains('disabled')) {
				return;
			}

			var originalText = el.textContent;

			el.textContent = _x('Copied', 'Success message after copying', 'affiliate-for-woocommerce');
			el.classList.add('disabled');

			setTimeout(function() {
				el.textContent = originalText;
				el.classList.remove('disabled');
			}, 1500);
		}
	};
	// Initialize the functionality.
	AFWClickToCopy.init();
})();
