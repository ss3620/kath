/**
 * Editor Block
 * 
 * @since 11.0.0
 */

(() => {
	'use strict';

	var reactElement = window.wp.element,
			blocks = window.wp.blocks,
			blockEditor = window.wp.blockEditor,
			wp_components = window.wp.components,
			wc_plugin_data = window.wc.wcSettings;

	const{
		free_gifts_preview_html,
		progress_bar_preview_html
	} = wc_plugin_data.getSetting('fgf-wc-blocks_data');

	/**
	 * Free Gifts block class.
	 * 
	 * @since 10.9.0
	 * @return {JSX.Element} A Wrapper used to display the free gifts in the cart/checkout block.
	 */
	const FreeGiftsBlock = {
		cartSchema: JSON.parse("{\"name\":\"woocommerce/fgf-wc-cart-free-gifts-block\",\"icon\":\"cart\",\"keywords\":[\"free\",\"gifts\"],\"version\":\"1.0.0\",\"title\":\"Free Gifts\",\"description\":\"Shows the free gifts layout in the cart block.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-items-block\"],\"textdomain\":\"free-gifts-for-woocommerce\",\"apiVersion\":2}"),
		checkoutSchema: JSON.parse("{\"name\":\"woocommerce/fgf-wc-checkout-free-gifts-block\",\"icon\":\"cart\",\"keywords\":[\"free\",\"gifts\"],\"version\":\"1.0.0\",\"title\":\"Free Gifts\",\"description\":\"Shows the free gifts layout in the checkout block.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-fields-block\"],\"textdomain\":\"free-gifts-for-woocommerce\",\"apiVersion\":2}"),
		getElement: function (e) {
			return   reactElement.createElement(wp_components.Disabled, {}, reactElement.createElement(reactElement.Fragment, {}, FreeGiftsBlock.getFormField()));
		},
		getFormField: function () {
			return reactElement.createElement(reactElement.RawHTML, {className: 'fgf-free-gifts-block'}, free_gifts_preview_html);
		},
		edit: function (attributes) {
			return reactElement.createElement('div', blockEditor.useBlockProps(), FreeGiftsBlock.getElement());
		},
		save: function (e) {
			return reactElement.createElement('div', blockEditor.useBlockProps.save());
		}
	};

	/**
	 * Progress bar block class.
	 * 
	 * @since 10.9.0
	 * @return {JSX.Element} A Wrapper used to display the progress bar in the cart/checkout block.
	 */
	const ProgressBarBlock = {
		cartSchema: JSON.parse("{\"name\":\"woocommerce/fgf-wc-cart-progress-bar-block\",\"icon\":\"cart\",\"keywords\":[\"progress\",\"bar\",\"gifts\"],\"version\":\"1.0.0\",\"title\":\"Gifts Progress Bar\",\"description\":\"Shows the gifts progress bar layout in the cart block.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-items-block\"],\"textdomain\":\"free-gifts-for-woocommerce\",\"apiVersion\":2}"),
		checkoutSchema: JSON.parse("{\"name\":\"woocommerce/fgf-wc-checkout-progress-bar-block\",\"icon\":\"cart\",\"keywords\":[\"progress\",\"bar\",\"gifts\"],\"version\":\"1.0.0\",\"title\":\"Gifts Progress Bar\",\"description\":\"Shows the gifts progress bar layout in the checkout block.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-fields-block\"],\"textdomain\":\"free-gifts-for-woocommerce\",\"apiVersion\":2}"),
		getElement: function (e) {
			return   reactElement.createElement(wp_components.Disabled, {}, reactElement.createElement(reactElement.Fragment, {}, ProgressBarBlock.getFormField()));
		},
		getFormField: function () {
			return reactElement.createElement(reactElement.RawHTML, {className: 'fgf-progress-bar-block'}, progress_bar_preview_html);
		},
		edit: function (attributes) {
			return reactElement.createElement('div', blockEditor.useBlockProps(), ProgressBarBlock.getElement());
		},
		save: function (e) {
			return reactElement.createElement('div', blockEditor.useBlockProps.save());
		}
	};

	// Register inner block of progress bar in the cart block.  
	blocks.registerBlockType(ProgressBarBlock.cartSchema.name, {
		...ProgressBarBlock.cartSchema,
		edit: ProgressBarBlock.edit,
		save: ProgressBarBlock.save
	});
	// Register inner block of progress bar in the checkout block.  
	blocks.registerBlockType(ProgressBarBlock.checkoutSchema.name, {
		...ProgressBarBlock.checkoutSchema,
		edit: ProgressBarBlock.edit,
		save: ProgressBarBlock.save
	});

	// Register inner block of free gifts in the cart block.  
	blocks.registerBlockType(FreeGiftsBlock.cartSchema.name, {
		...FreeGiftsBlock.cartSchema,
		edit: FreeGiftsBlock.edit,
		save: FreeGiftsBlock.save
	});
	// Register inner block of free gifts in the checkout block.  
	blocks.registerBlockType(FreeGiftsBlock.checkoutSchema.name, {
		...FreeGiftsBlock.checkoutSchema,
		edit: FreeGiftsBlock.edit,
		save: FreeGiftsBlock.save
	});
})();