/* global fgf_rule_params, ajaxurl, wp */

jQuery(function ($) {
	'use strict';

	var FGF_Admin = {
		init: function ( ) {
			this.trigger_on_page_load();
			// rules tab
			$(document).on('change', '.fgf_rule_types', this.toggle_rule_type);
			$(document).on('change', '.fgf_gift_type', this.toggle_gift_type);
			$(document).on('change', '.fgf_bogo_gift_type', this.toggle_bogo_gift_type);
			$(document).on('change', '.fgf_buy_product_type', this.toggle_buy_product_type);
			$(document).on('change', '.fgf_get_product_type', this.toggle_get_product_type);
			$(document).on('change', '.fgf_bogo_gift_repeat', this.toggle_bogo_gift_repeat);
			$(document).on('change', '.fgf_bogo_gift_repeat_mode', this.toggle_bogo_gift_repeat_mode);
			$(document).on('change', '.fgf-subtotal-gift-type', this.toggle_subtotal_gift_type);
			$(document).on('change', '.fgf-subtotal-repeat-gift', this.toggle_subtotal_repeat_gift);
			$(document).on('change', '.fgf-subtotal-repeat-gift-mode', this.toggle_subtotal_repeat_gift_mode);
			$(document).on('change', '.fgf-rule-bulk-gift-selection-type-field', this.toggle_bulk_gift_type);

			// Bulk Rule
			$(document).on('click', '.fgf-add-bulk-rule', this.add_bulk_rule);
			$(document).on('click', '.fgf-remove-bulk-rule', this.remove_bulk_rule);

			$(document).on('change', '.fgf_rule_show_notice', this.toggle_notice);
        	$(document).on('change', '.fgf-rule-show-bogo-eligible-notice-field', this.toggle_bogo_eligible_notice);
			$(document).on('change', '.fgf-rule-show-bulk-eligible-notice-field', this.toggle_bulk_eligible_notice);
			$(document).on('change', '.fgf_user_filter_type', this.toggle_user_filter_type);
			$(document).on('change', '.fgf_product_filter_type', this.toggle_product_filter_type);
			$(document).on('change', '.fgf_applicable_products_type', this.toggle_applicable_products_type);
			$(document).on('change', '.fgf_applicable_categories_type', this.toggle_applicable_categories_type);
			$(document).on('click', '.fgf_reset_rule_usage_count', this.reset_rule_usage_count);
			$(document).on('change', '.fgf-rule-total-type', this.toggle_rule_total_type);
			$(document).on('change', '.fgf-rule-subtotal-type', this.toggle_rule_subtotal_type);
			$(document).on('change', '.fgf-bulk-pricing-total-type', this.toggle_bulk_pricing_total_type);
			$(document).on('change', '.fgf_rule_allowed_user_type', this.toggle_rule_allowed_user_type);
			$(document).on('change', '.fgf_rule_user_purchased_order_count_type', this.toggle_rule_user_purchased_order_count_type);
			$(document).on('change', '.fgf-rule-restrict-by-wocommerce-coupon-type', this.toggle_rule_restrict_by_wocommerce_coupon_type);
			$(document).on('click', '.fgf-delete-uploaded-img', this.delete_uploaded_image);
			$(document).on('click', '.fgf-upload-img', this.open_wp_frame);

			//Tabbed rule panel.
			$(document).on('fgf-init-tabbed-panels', this.tabbed_rule_panels).trigger('fgf-init-tabbed-panels');

		}, trigger_on_page_load: function ( ) {
			// rules tab
			this.rule_type('.fgf_rule_types');
			this.notice('.fgf_rule_show_notice');
			this.bogo_eligible_notice('.fgf-rule-show-bogo-eligible-notice-field');
			this.bulk_eligible_notice('.fgf-rule-show-bulk-eligible-notice-field');
			this.user_filter_type('.fgf_user_filter_type');
			this.product_filter_type('.fgf_product_filter_type');
			this.rule_total_type('.fgf-rule-total-type');
			this.rule_subtotal_type('.fgf-rule-subtotal-type');
			this.rule_allowed_user_type('.fgf_rule_allowed_user_type');
			this.rule_restrict_by_wocommerce_coupon_type('.fgf-rule-restrict-by-wocommerce-coupon-type');
			this.sortable_default_fields();

		}, toggle_rule_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.rule_type($this);
		}, toggle_gift_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.gift_type($this);
		}, toggle_bogo_gift_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.bogo_gift_type($this);
		}, toggle_buy_product_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.buy_product_type($this);
		}, toggle_get_product_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.get_product_type($this);
		}, toggle_bogo_gift_repeat: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.bogo_gift_repeat($this);
		}, toggle_bogo_gift_repeat_mode: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.bogo_gift_repeat_mode($this);
		}, toggle_subtotal_gift_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.subtotal_gift_type($this);
		}, toggle_subtotal_repeat_gift: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.subtotal_repeat_gift($this);
		}, toggle_subtotal_repeat_gift_mode: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.subtotal_repeat_gift_mode($this);
		}, toggle_bulk_gift_type: function (event) {
			event.preventDefault( );
			FGF_Admin.bulk_gift_type($(event.currentTarget));
		}, toggle_notice: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.notice($this);
		}, toggle_bogo_eligible_notice: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.bogo_eligible_notice($this);
		}, toggle_bulk_eligible_notice: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.bulk_eligible_notice($this);
		},toggle_user_filter_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.user_filter_type($this);
		}, toggle_product_filter_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.product_filter_type($this);
		}, toggle_applicable_products_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.applicable_products_type($this);
		}, toggle_applicable_categories_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);
			FGF_Admin.applicable_categories_type($this);
		}, toggle_rule_total_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.rule_total_type($this);
		}, toggle_rule_subtotal_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.rule_subtotal_type($this);
		}, toggle_bulk_pricing_total_type: function (event) {
			event.preventDefault( );

			   FGF_Admin.bulk_pricing_total_type($(event.currentTarget));
		}, toggle_rule_allowed_user_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.rule_allowed_user_type($this);
		}, toggle_rule_user_purchased_order_count_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.rule_user_purchased_order_count_type($this);
		}, toggle_rule_restrict_by_wocommerce_coupon_type: function (event) {
			event.preventDefault( );

			FGF_Admin.rule_restrict_by_wocommerce_coupon_type($(event.currentTarget));
		}, rule_type: function ($this) {
			$('.fgf-rule-data-tabs').find('.notices_tab').show();
			$('.fgf_rule_type').closest('div').hide();
			$('.fgf-rule-general-fields-wrapper').hide();
			$('.fgf-rule-notice-fields-wrapper').hide();
			$('.fgf-rule-cart-quantity-wrapper').show();

			switch ($($this).val()) {
				case '1':
					$('.fgf_gift_products').data('exclude-global-variable', 'no');
					$('.fgf_manual_rule_type').closest('div').show();
					$('.fgf-rule-manual-gifts-fields-wrapper').show();
					$('.fgf-rule-manual-gifts-quantity-fields-wrapper').hide();
					FGF_Admin.gift_type('.fgf_gift_type');
					break;

				case '2':
					$('.fgf_gift_products').data('exclude-global-variable', 'yes');
					$('.fgf_gift_products').closest('div').show();
					$('.fgf_automatic_rule_type').closest('div').show();
					$('.fgf-rule-manual-gifts-quantity-fields-wrapper').show();
					$('.fgf-rule-manual-gifts-fields-wrapper').show();
					break;

				case '3':
					$('.fgf_get_products').data('exclude-global-variable', 'yes');
					$('.fgf_bogo_rule_type').closest('div').show();
					$('.fgf_automatic_bogo_rule_type').closest('div').show();
					FGF_Admin.buy_product_type('.fgf_buy_product_type');
					FGF_Admin.bogo_gift_type('.fgf_bogo_gift_type');
					FGF_Admin.bogo_gift_repeat('.fgf_bogo_gift_repeat');
					$('.fgf-rule-bogo-gifts-fields-wrapper').show();
					$('.fgf-bogo-rule-notice-fields-wrapper').show();
					break;

				case '4':
					$('.fgf_coupon_gift_products').data('exclude-global-variable', 'yes');
					$('.fgf-rule-data-tabs').find('.notices_tab').hide();
					$('.fgf_coupon_rule_type').closest('div').show();
					$('.fgf-rule-coupon-gifts-quantity-fields-wrapper').show();
					$('.fgf-rule-coupon-gifts-fields-wrapper').show();
					break;

				case '5':
					$('.fgf_get_products').data('exclude-global-variable', 'no');
					$('.fgf_bogo_rule_type').closest('div').show();
					$('.fgf_manual_bogo_rule_type').closest('div').show();
					$('.fgf-rule-bogo-gifts-fields-wrapper').show();
					$('.fgf-bogo-rule-notice-fields-wrapper').show();
					FGF_Admin.buy_product_type('.fgf_buy_product_type');
					FGF_Admin.get_product_type('.fgf_get_product_type');
					FGF_Admin.bogo_gift_repeat('.fgf_bogo_gift_repeat');
					break;

				case '6':
					$('.fgf_coupon_gift_products').data('exclude-global-variable', 'no');
					$('.fgf-rule-data-tabs').find('.notices_tab').hide();
					$('.fgf_coupon_rule_type').closest('div').show();
					$('.fgf_manual_coupon_rule_type').closest('div').show();
					$('.fgf-rule-coupon-gifts-fields-wrapper').show();
					$('.fgf-rule-coupon-gifts-quantity-fields-wrapper').hide();
					break;

				case '7':
					$('.fgf-subtotal-gift-products').data('exclude-global-variable', 'no');
					$('.fgf-subtotal-rule-type').closest('div').show();
					$('.fgf-subtotal-automatic-rule-type').closest('div').hide();
					$('.fgf-rule-subtotal-gifts-fields-wrapper').show();
					FGF_Admin.subtotal_gift_type('.fgf-subtotal-gift-type');
					FGF_Admin.subtotal_repeat_gift('.fgf-subtotal-repeat-gift');
					break;

				case '8':
					$('.fgf-subtotal-gift-products').data('exclude-global-variable', 'no');
					$('.fgf-subtotal-rule-type').closest('div').show();
					$('.fgf-subtotal-manual-rule-type').closest('div').hide();
					$('.fgf-rule-subtotal-gifts-fields-wrapper').show();
					FGF_Admin.subtotal_repeat_gift('.fgf-subtotal-repeat-gift');
					break;

				case '9':
				case '10':
					$('.fgf-rule-bulk-pricing-gifts-fields-wrapper').show();
					FGF_Admin.bulk_pricing_total_type('.fgf-bulk-pricing-total-type');
					FGF_Admin.initialize_bulk_gift_type();
					$('.fgf-bulk-rule-notice-fields-wrapper').show();
					break;

				case '11':
				case '12':
					$('.fgf-rule-bulk-quantity-gifts-fields-wrapper').show();
					FGF_Admin.initialize_bulk_gift_type();
					$('.fgf-rule-cart-quantity-wrapper').hide();
					$('.fgf-bulk-rule-notice-fields-wrapper').show();
					break;

				case '13':
				case '14':
					$('.fgf-rule-cheapest-gifts-fields-wrapper').show();
					break;
			}

		}, gift_type: function ($this) {
			$('.fgf-gift-selection-type-field').closest('div').hide();
			$('.fgf-gift-selection-type-' + $($this).val()).closest('div').show();
		}, buy_product_type: function ($this) {
			$('.fgf-buy-product-selection-type-field').closest('div').hide();
			$('.fgf-buy-product-selection-type-field-' + $($this).val()).closest('div').show();
			FGF_Admin.buy_category_type();
		}, get_product_type: function ($this) {
			$('.fgf-get-product-selection-type-field').closest('div').hide();
			$('.fgf-get-product-selection-type-field-' + $($this).val()).closest('div').show();
		}, bogo_gift_repeat: function ($this) {
			if ($($this).is(":checked")) {
				$('.fgf_bogo_gift_repeat_field').closest('div').show();
				FGF_Admin.bogo_gift_repeat_mode('.fgf_bogo_gift_repeat_mode');
			} else {
				$('.fgf_bogo_gift_repeat_field').closest('div').hide();
			}
		}, bogo_gift_repeat_mode: function ($this) {
			if ($($this).val() === '1') {
				$('.fgf_bogo_gift_repeat_limit').closest('div').hide();
			} else {
				$('.fgf_bogo_gift_repeat_limit').closest('div').show();
			}
		}, bogo_gift_type: function ($this) {
			if ($($this).val() === '1') {
				$('.fgf-rule-bogo-get-product-fields-wrapper').hide();
			} else {
				$('.fgf_get_products').closest('div').show();
				$('.fgf-rule-bogo-get-product-fields-wrapper').show();
			}

			FGF_Admin.buy_category_type();
		}, buy_category_type: function (  ) {
			if (('5' === $('.fgf_rule_types').val() || '2' === $('.fgf_bogo_gift_type').val()) && '2' === $('.fgf_buy_product_type').val()) {
				$('.fgf_buy_category_type').closest('div').show();
			} else {
				$('.fgf_buy_category_type').closest('div').hide();
			}
		}, subtotal_gift_type: function ($this) {
			$('.fgf-subtotal-gift-selection-type-field').closest('div').hide();
			$('.fgf-subtotal-gift-selection-type-' + $($this).val()).closest('div').show();
		}, subtotal_repeat_gift: function ($this) {

			if ($($this).is(":checked")) {
				$('.fgf-subtotal-repeat-gift-field').closest('div').show();
				FGF_Admin.subtotal_repeat_gift_mode('.fgf-subtotal-repeat-gift-mode');
			} else {
				$('.fgf-subtotal-repeat-gift-field').closest('div').hide();
			}
		}, subtotal_repeat_gift_mode: function ($this) {
			if ('1' === $($this).val()) {
				$('.fgf-subtotal-gift-repeat-limit').closest('div').hide();
			} else {
				$('.fgf-subtotal-gift-repeat-limit').closest('div').show();
			}
		}, initialize_bulk_gift_type: function () {
			$('.fgf-rule-bulk-gift-selection-type-field').each(function (eve) {
				FGF_Admin.bulk_gift_type(this);
			});
		}, bulk_gift_type: function ($this) {
			var row = $($this).closest('.fgf-bulk-rule-wrapper');

			row.find('.fgf-bulk-gift-type-field').closest('div').hide();

			if (-1 !== $.inArray($('.fgf_rule_types').val(), ['10', '12'])) {
				row.find('.fgf-rule-bulk-gift-products-field').closest('div').show();
				row.find('.fgf-rule-bulk-gift-selection-type-field').closest('div').hide();
			} else {
				row.find('.fgf-rule-bulk-gift-selection-type-field').closest('div').show();
				row.find('.fgf-bulk-gift-type-' + $($this).val()).closest('div').show();
			}

		}, notice: function ($this) {
			if ($($this).val() === '2') {
				$('.fgf_rule_notice').closest('div').show();
			} else {
				$('.fgf_rule_notice').closest('div').hide();
			}
		},bogo_eligible_notice: function ($this) {
			if ($($this).val() === '2') {
				$('.fgf-rule-bogo-eligible-notice-field').closest('div').show();
			} else {
				$('.fgf-rule-bogo-eligible-notice-field').closest('div').hide();
			}
		},bulk_eligible_notice: function ($this) {
			if ($($this).val() === '2') {
				$('.fgf-rule-bulk-eligible-notice-field').closest('div').show();
			} else {
				$('.fgf-rule-bulk-eligible-notice-field').closest('div').hide();
			}
		}, user_filter_type: function ($this) {
			$('.fgf_user_filter').closest('div').hide();
			$('.fgf_user_filter-' + $($this).val()).closest('div').show();
		}, product_filter_type: function ($this) {
			$('.fgf_product_filter').closest('div').hide();
			switch ($($this).val()) {
				case '2':
					$('.fgf_include_products').closest('div').show();
					$('.fgf_applicable_products_type').closest('div').show();
					FGF_Admin.applicable_products_type('.fgf_applicable_products_type');
					break;

				case '3':
					$('.fgf_exclude_products').closest('div').show();
					break;

				case '5':
					$('.fgf_include_categories').closest('div').show();
					$('.fgf_applicable_categories_type').closest('div').show();
					FGF_Admin.applicable_categories_type('.fgf_applicable_categories_type');
					break;

				case '6':
					$('.fgf_exclude_categories').closest('div').show();
					break;
			}

			$(document).trigger('fgf_product_filter_type_options', [$($this).val()]);

		}, applicable_products_type: function ($this) {
			$('.fgf_include_product_count').closest('div').hide();
			if ($($this).val() === '4') {
				$('.fgf_include_product_count').closest('div').show();
			}
		}, applicable_categories_type: function ($this) {
			$('.fgf_include_category_product_count').closest('div').hide();
			if ($($this).val() === '4') {
				$('.fgf_include_category_product_count').closest('div').show();
			}
		}, rule_total_type: function ($this) {
			var val = $($this).val();

			$('.fgf-rule-cart-total-type-fields').closest('div').hide();
			$('.fgf-rule-cart-total-type-' + val).closest('div').show();

		}, rule_subtotal_type: function ($this) {
			var val = $($this).val();

			$('.fgf-rule-total-type-fields').closest('div').hide();
			$('.fgf-rule-total-type-' + val).closest('div').show();

		},bulk_pricing_total_type: function ($this) {
			var val = $($this).val();

			$('.fgf-bulk-pricing-total-type-field').closest('div').hide();
			$('.fgf-bulk-pricing-total-type-' + val).closest('div').show();

		}, rule_allowed_user_type: function ($this) {

			if ($($this).val() === '2') {
				$('.fgf-rule-user-count-field').closest('div').show();
				FGF_Admin.rule_user_purchased_order_count_type('.fgf_rule_user_purchased_order_count_type');
			} else {
				$('.fgf-rule-user-count-field').closest('div').hide();
			}
		}, rule_user_purchased_order_count_type: function ($this) {
			switch ($($this).val()) {
				case '3':
					$('.fgf_rule_user_purchased_order_min_count').closest('div').show();
					break;
				default:
					$('.fgf_rule_user_purchased_order_min_count').closest('div').hide();
					break;
			}
		}, rule_restrict_by_wocommerce_coupon_type: function ($this) {
			switch ($($this).val()) {
				case '2':
					$('.fgf-rule-restrict-by-wocommerce-coupon').closest('div').show();
					break;
				default:
					$('.fgf-rule-restrict-by-wocommerce-coupon').closest('div').hide();
					break;
			}
		}, sortable_default_fields: function () {
			var listtable = $('table.fgf_rules #the-list').closest('table');

			listtable.sortable({
				items: 'tr',
				handle: '.fgf_post_sort_handle',
				axis: 'y',
				containment: listtable,
				update: function (event, ui) {
					var sort_order = [];

					listtable.find('.fgf_rules_sortable').each(function (e) {
						sort_order.push($(this).val( ));
					});

					$.post(ajaxurl, {
						action: 'fgf_drag_rules_list',
						sort_order: sort_order,
						fgf_security: fgf_rule_params.fgf_rules_drag_nonce
					});
				}
			});
		}, reset_rule_usage_count: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);

			FGF_Admin.block($this);

			var data = {
				action: 'fgf_reset_rule_usage_count',
				rule_id: $($this).data('rule-id'),
				fgf_security: fgf_rule_params.fgf_rules_nonce,
			};

			$.post(ajaxurl, data, function (res) {

				if (true === res.success) {
					alert(res.data.msg);
					location.reload(true);
				} else {
					alert(res.data.error);
				}

				FGF_Admin.unblock($this);
			}
			);
		}, tabbed_rule_panels: function ( ) {

			// trigger the clicked link.
			$('.fgf-rule-data-tab-link').on('click', function (event) {
				event.preventDefault();
				var $this = $(event.currentTarget),
						panel_content = $($this).closest('.fgf-rule-data-panel-content');

				$('.fgf-rule-data-tab', panel_content).removeClass('active');
				$($this).parent().addClass('active');

				$('div.fgf-rule-options-wrapper', panel_content).hide();
				$($($this).attr('href')).show();
			});

			// Trigger the first link.
			$('div.fgf-rule-data-panel-content').each(function () {
				$(this).find('.fgf-rule-data-tab').eq(0).find('a').click();
			});
		}, open_wp_frame: function (event) {
			event.preventDefault( );
			var img_container = $(this).closest('.fgf-upload-img-container'),
					wp_frame;

			// If the media frame already exists, reopen it.
			if (wp_frame) {
				wp_frame.open( );
				return;
			}

			// Create a new media frame.
			wp_frame = wp.media({
				frame: 'select',
				title: $(this).data('button-title') ? $(this).data('button-title') : fgf_admin_params.media_title,
				multiple: false,
				library: {
					type: 'image'
				},
				button: {
					text: $(this).data('button-text') ? $(this).data('button-text') : fgf_admin_params.media_button_text
				}
			});

			// When an image is selected, run a callback.
			wp_frame.on('select', function ( ) {
				// Get the attachement details from the media state.
				var attachment = wp_frame.state( ).get('selection').first( ).toJSON( );

				// Send the attachment url to input hidden field.
				img_container.find('.fgf-upload-img-url').val(attachment.id);

				// Send the attachement URL to custom preview.
				var img = $('<img />');
				img.attr('src', attachment.url);
				img_container.find('.fgf-uploaded-img-wrapper').empty( ).append(img);
				img_container.find('.fgf-delete-uploaded-img').show();
			});

			// Finally, open the modal.
			wp_frame.open( );
		},
		delete_uploaded_image: function (event) {
			event.preventDefault( );
			var img_container = $(this).closest('.fgf-upload-img-container');

			// Remove the attachment url.
			img_container.find('.fgf-upload-img-url').val('');
			img_container.find('.fgf-uploaded-img-wrapper').empty( );

			$(this).hide();
		}, add_bulk_rule: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
					rule_template = wp.template($($this).data('template_name')),
					wrapper = $($this).closest('.fgf-bulk-rules-wrapper'),
					last_rule_wrapper = wrapper.find('.fgf-bulk-rule-wrapper:last'),
					rule_id = last_rule_wrapper.data('rule_id') + 1 || 0;

			wrapper.append(rule_template({rule_id: rule_id}));

			$(document.body).trigger('fgf-enhanced-init');
			FGF_Admin.initialize_bulk_gift_type();
		}, remove_bulk_rule: function (event) {
			event.preventDefault();

			$($(event.currentTarget)).closest('.fgf-bulk-rule-wrapper').remove();
		}, block: function (id) {
			$(id).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.7
				}
			});
		}, unblock: function (id) {
			$(id).unblock();
		}
	};
	FGF_Admin.init( );
});
