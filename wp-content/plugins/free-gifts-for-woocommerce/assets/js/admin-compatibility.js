
jQuery(function ($) {
	'use strict';

	var FGF_Admin_Compatibility = {
		init: function ( ) {
			this.trigger_on_page_load();

			//Brands.
			$(document).on('change', '.fgf_applicable_brands_type', this.toggle_applicable_type_fields);
			$(document).on('fgf_product_filter_type_options', this.toggle_product_type_fields);

		}, trigger_on_page_load: function ( ) {
			// Brands.
			this.handles_applicable_type_fields('.fgf_applicable_brands_type');

		}, toggle_applicable_type_fields: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin_Compatibility.handles_applicable_type_fields($this);
		}, toggle_product_type_fields: function (event, val) {
			switch (val) {
				case 'include_brands':
					$('.fgf_include_brands').closest('div').show();
					$('.fgf_applicable_brands_type').closest('div').show();
					FGF_Admin_Compatibility.handles_applicable_type_fields('.fgf_applicable_brands_type');
					break;

				case 'exclude_brands':
					$('.fgf_exclude_brands').closest('div').show();
					break;
			}
		}, handles_applicable_type_fields: function ($this) {
			switch ($($this).val()) {
				case '4':
					$('.fgf-brand-product-count').closest('div').show();
					break;
				default:
					$('.fgf-brand-product-count').closest('div').hide();
					break;
			}
		}
	};

	FGF_Admin_Compatibility.init( );
});
