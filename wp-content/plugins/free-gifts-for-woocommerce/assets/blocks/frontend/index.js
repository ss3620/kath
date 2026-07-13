/**
 * Cart / Checkout block.
 * 
 * @since 11.0.0
 */
(() => {
	'use strict';
	var reactElement = window.wp.element,
			wc_blocks_checkout = window.wc.blocksCheckout,
			wp_data = window.wp.data,
			wc_plugin_data = window.wc.wcSettings,
			notice_ids = [];

	const {
		createNotice, removeNotice
	} = wp_data.dispatch('core/notices');
	const{
		gift_added_message
	} = wc_plugin_data.getSetting('fgf-wc-blocks_data');

	/**
	 * Free gifts cart block class.
	 * 
	 * @since 11.0.0
	 * @return {JSX.Element} A Wrapper used to display the free gifts related data in the cart page.
	 */
	const FreeGiftsCartBlock = {
		context: 'wc/cart',
		getElement: function (e) {
			// Remove notices if already added.
			removeNotices(FreeGiftsCartBlock.context);
			if (!e.extensions['fgf-free-gifts']) {
				return '';
			}

			createNotices(e.extensions['fgf-free-gifts']['cart_notices'], FreeGiftsCartBlock.context);
			createSnackBarNotices(e.extensions['fgf-free-gifts']['snackbar_notices']);

			if (!e.extensions['fgf-free-gifts']['cart_gifts_html']) {
				return '';
			}

			// Reinitialize third party library
			setTimeout(function(){
				jQuery(document.body).trigger('fgf-enhanced-carousel');
			    jQuery(document.body).trigger('fgf-enhanced-lightcase');
			},1000);
			
			return reactElement.createElement(reactElement.RawHTML, null, e.extensions['fgf-free-gifts']['cart_gifts_html']);
		}
	};

	/**
	 * Free gifts checkout block class.
	 * 
	 * @since 11.0.0
	 * @return {JSX.Element} A Wrapper used to display the free gifts related data in the checkout page.
	 */
	const FreeGiftsCheckoutBlock = {
		context: 'wc/checkout',
		getElement: function (e) {
			// Remove notices if already added.
			removeNotices(FreeGiftsCheckoutBlock.context);
			if (!e.extensions['fgf-free-gifts']) {
				return '';
			}

			createNotices(e.extensions['fgf-free-gifts']['checkout_notices'], FreeGiftsCheckoutBlock.context);
			createSnackBarNotices(e.extensions['fgf-free-gifts']['snackbar_notices']);

			if (!e.extensions['fgf-free-gifts']['checkout_gifts_html']) {
				return '';
			}

			// Reinitialize third party library
			setTimeout(function(){
				jQuery(document.body).trigger('fgf-enhanced-carousel');
			    jQuery(document.body).trigger('fgf-enhanced-lightcase');
			},1000);
			
			return reactElement.createElement(reactElement.RawHTML, null, e.extensions['fgf-free-gifts']['checkout_gifts_html']);
		}
	};

	/**
	 * Progress bar cart block class.
	 * 
	 * @since 11.0.0
	 * @return {JSX.Element} A Wrapper used to display the progress bar related data in the cart page.
	 */
	const ProgressBarCartBlock = {
		context: 'wc/cart',
		getElement: function (e) {
			if (!e.extensions['fgf-free-gifts']) {
				return '';
			}

			if (!e.extensions['fgf-free-gifts']['cart_progress_bar_html']) {
				return '';
			}

			return reactElement.createElement(reactElement.RawHTML, null, e.extensions['fgf-free-gifts']['cart_progress_bar_html']);
		}
	};

	/**
	 * Prgress bar checkout block class.
	 * 
	 * @since 11.0.0
	 * @return {JSX.Element} A Wrapper used to display the progress bar related data in the checkout page.
	 */
	const ProgressBarCheckoutBlock = {
		context: 'wc/checkout',
		getElement: function (e) {
			if (!e.extensions['fgf-free-gifts']) {
				return '';
			}

			if (!e.extensions['fgf-free-gifts']['checkout_progress_bar_html']) {
				return '';
			}

			return reactElement.createElement(reactElement.RawHTML, null, e.extensions['fgf-free-gifts']['checkout_progress_bar_html']);
		}
	};

	/**
	 * Modify the free gifts cart item in the cart item table.
	 * 
	 * @since 11.0.0
	 */
	const CartItem = {
		/**
		 * Handles remove item link.
		 * 
		 * @since 11.0.0
		 * @param {boolean} defaultValue
		 * @param {object} extensions
		 * @param {array} args
		 * @returns {string}
		 */
		handleRemoveItemLink: function (defaultValue, extensions, args) {
			// Check current cart item is a free gift cart item.
			if (!extensions['fgf-free-gifts'] || undefined === extensions['fgf-free-gifts'].show_remove_link) {
				return defaultValue;
			}

			return extensions['fgf-free-gifts'].show_remove_link;
		},
		/**
		 * Modify the cart item price.
		 * 
		 * @since 11.0.0
		 * @param {string} defaultValue
		 * @param {object} extensions
		 * @param {object} args
		 * @param {boolean} validation
		 * @returns {string}
		 */
		handleItemPrice: function (defaultValue, extensions, args, validation) {
			// Check current cart item is a free gift cart item.
			if (!extensions['fgf-free-gifts'] || !extensions['fgf-free-gifts'].item_price) {
				return defaultValue;
			}

			// Discounted price already not having the price return it as same value.
			if (!extensions['fgf-free-gifts'].item_price.discounted_price) {
				return defaultValue;
			}

			if ('price' === extensions['fgf-free-gifts'].item_price.type) {
				var price = window.wc.priceFormat.formatPrice(extensions['fgf-free-gifts'].item_price.discounted_price);
			} else {
				var price = extensions['fgf-free-gifts'].item_price.label;
			}

			return price + defaultValue;
		}
	};
	/**
	 * Create snackbar notices to the block.
	 * 
	 * @since 11.0.0
	 * @param {array} notices
	 * @param {string} context
	 * @returns {undefined}
	 */
	function createSnackBarNotices(group_notices, context, status = 'success') {
		if (group_notices) {
			jQuery.each(group_notices, function (type, notices) {
				jQuery.each(notices, function (index, notice) {
					createNotice(type, notice, {
						id: 'fgf-gift-snackbar-notices' + index,
						context: 'wc/cart',
						type: 'snackbar'
					});
				});
			});
		}
	}

	/**
	 * Create notices to the block.
	 * 
	 * @since 11.0.0
	 * @param {array} notices
	 * @param {string} context
	 * @returns {undefined}
	 */
	function createNotices(notices, context, status = 'success') {
		// Add eligible notices.
		if (notices) {
			jQuery.each(notices, function (index, notice) {
				let id = 'fgf-gifts-notice-' + index;
				createNotice(status, '<div class="fgf-gifts-notices-wrapper">' + notice + '</div>', {
					id: id,
					context: context,
					isDismissible: true
				});
				notice_ids.push(id);
			});
		}
	}

	/**
	 * Remove previous notices from the block.
	 * 
	 * @since 11.0.0
	 * @param {string} context
	 * @returns {undefined}
	 */
	function removeNotices(context) {
		jQuery.each(notice_ids, function (index, id) {
			removeNotice(id, context);
		});
	}

	/**
	 * Update cart after gifts are added.
	 * 
	 * @since 11.0.0
	 */
	jQuery(document).on('fgf_update_cart_block', function () {
		// Refresh the cart.
		wc_blocks_checkout.extensionCartUpdate({
			namespace: 'fgf-free-gifts',
			data: {
				action: 'refresh_cart'
			}
		}).then(() => {
			// Reinitialize third party library
			jQuery(document.body).trigger('fgf-enhanced-carousel');
			jQuery(document.body).trigger('fgf-enhanced-lightcase');
		}).finally(() => {
			createNotice('success', gift_added_message, {
				id: 'fgf-gift-added',
				context: 'wc/cart',
				type: 'snackbar'
			});
		});
	});

	// Register inner block of progress bar in the cart block.
	wc_blocks_checkout.registerCheckoutBlock({
		metadata: JSON.parse("{\"name\":\"woocommerce/fgf-wc-cart-progress-bar-block\",\"icon\":\"cart\",\"keywords\":[\"progress\",\"bar\",\"gifts\"],\"version\":\"1.0.0\",\"title\":\"Gifts Progress Bar\",\"description\":\"Shows the gifts progress bar layout in the cart block.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-items-block\"],\"textdomain\":\"free-gifts-for-woocommerce\",\"apiVersion\":2}"),
		component: ProgressBarCartBlock.getElement
	});
	// Register inner block of progress bar in the checkout block.
	wc_blocks_checkout.registerCheckoutBlock({
		metadata: JSON.parse("{\"name\":\"woocommerce/fgf-wc-checkout-progress-bar-block\",\"icon\":\"cart\",\"keywords\":[\"progress\",\"bar\",\"gifts\"],\"version\":\"1.0.0\",\"title\":\"Gifts Progress Bar\",\"description\":\"Shows the gifts progress bar layout in the checkout block.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-fields-block\"],\"textdomain\":\"free-gifts-for-woocommerce\",\"apiVersion\":2}"),
		component: ProgressBarCheckoutBlock.getElement
	});

	// Register inner block of gifts in the cart block.
	wc_blocks_checkout.registerCheckoutBlock({
		metadata: JSON.parse("{\"name\":\"woocommerce/fgf-wc-cart-free-gifts-block\",\"icon\":\"cart\",\"keywords\":[\"free\",\"gifts\"],\"version\":\"1.0.0\",\"title\":\"Free Gifts\",\"description\":\"Shows the free gifts layout in the cart block.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-items-block\"],\"textdomain\":\"free-gifts-for-woocommerce\",\"apiVersion\":2}"),
		component: FreeGiftsCartBlock.getElement
	});
	// Register inner block of gifts in the checkout block.
	wc_blocks_checkout.registerCheckoutBlock({
		metadata: JSON.parse("{\"name\":\"woocommerce/fgf-wc-checkout-free-gifts-block\",\"icon\":\"cart\",\"keywords\":[\"free\",\"gifts\"],\"version\":\"1.0.0\",\"title\":\"Free Gifts\",\"description\":\"Shows the free gifts layout in the checkout block.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-fields-block\"],\"textdomain\":\"free-gifts-for-woocommerce\",\"apiVersion\":2}"),
		component: FreeGiftsCheckoutBlock.getElement
	});

	// Alter the free gifts cart item in the cart item table.
	wc_blocks_checkout.registerCheckoutFilters('fgf-free-gifts', {
		showRemoveItemLink: CartItem.handleRemoveItemLink,
		cartItemPrice: CartItem.handleItemPrice
	});
})();