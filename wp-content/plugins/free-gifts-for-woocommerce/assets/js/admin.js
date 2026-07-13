/* global fgf_admin_params, ajaxurl, wp */

jQuery(function ($) {
	'use strict';

	var FGF_Admin = {
		init: function ( ) {
			this.trigger_on_page_load();
			// Manual gift tab
			$(document).on('click', '#fgf_manual_gift_manual_gift_btn', this.manual_gift_btn);
			// Settings Tab
			$(document).on('change', '#fgf_settings_restrict_gift_product_display', this.toggle_restrict_gift_product_display);
			$(document).on('change', '#fgf_settings_enable_manual_gift_email', this.toggle_manual_gift_email);
			$(document).on('change', '#fgf_settings_gift_display_type', this.toggle_gift_display_type);
			$(document).on('change', '#fgf_settings_bogo_eligible_notice_on_single_product', this.toggle_bogo_eligible_notice_on_single_product);
			$(document).on('change', '#fgf_settings_bogo_eligible_notice_on_loop_page', this.toggle_bogo_eligible_notice_on_loop_page);
			$(document).on('change', '#fgf_settings_gift_checkout_page_display', this.toggle_checkout_gift_display);
			$(document).on('change', '#fgf_settings_checkout_gift_products_display_type', this.toggle_checkout_gift_display_type);
			$(document).on('change', '#fgf_settings_checkout_gift_products_hook_name', this.toggle_checkout_gift_display_hook);
			$(document).on('change', '#fgf_settings_gift_cart_page_display', this.toggle_gift_display_mode);
			$(document).on('change', '#fgf_settings_gift_display_table_pagination', this.toggle_table_pagination);
			$(document).on('change', '#fgf_settings_carousel_navigation', this.toggle_carousel_navigation);
			$(document).on('change', '#fgf_settings_carousel_auto_play', this.toggle_carousel_auto_play);
			$(document).on('change', '#fgf_settings_master_log_deletion', this.toggle_master_log_deletion);

			// Rules
			// Preview the rules details.
			$(document).on('click', '.fgf-preview-rules-details', this.preview_rule_details);
			
			// Master log tab
			$(document).on('click', '.fgf_master_log_info', this.master_log_info);
			$(document).on('click', '.fgf_popup_close', this.toggle_master_log_popup_close);

			// Coupon.
			$(document).on('change', '#discount_type', this.toggle_coupon_discount_type);

			// Order.
			$(document).on('click', '.fgf-order-item-gift-items-button', this.render_order_item_gift_popup);

			// Validate the action.
			$(document).on('click', '.fgf-action', this.action_confirmation);
			$(document).on('click', '.fgf-settings-wrapper #doaction', this.bulk_action_confirmation);

			$(document.body)
					.on('wc_backbone_modal_loaded', this.backbone.init)
					.on('wc_backbone_modal_response', this.backbone.response);

			// Prevent settings save in functionality.
			$('form.fgf-settings-form').on('submit', this.prevent_settings_save);

			$(document).on('fgf-init-tabs', this.tabbed_tabs).trigger('fgf-init-tabs');

		}, trigger_on_page_load: function ( ) {
			//Settings Tab
			this.restrict_gift_product_display('#fgf_settings_restrict_gift_product_display');
			this.manual_gift_email('#fgf_settings_enable_manual_gift_email');
			this.bogo_eligible_notice_on_single_product('#fgf_settings_bogo_eligible_notice_on_single_product');
			this.bogo_eligible_notice_on_loop_page('#fgf_settings_bogo_eligible_notice_on_loop_page');
			this.checkout_gift_display('#fgf_settings_gift_checkout_page_display');
			this.gift_display_type('#fgf_settings_gift_display_type');
			this.gift_display_mode('#fgf_settings_gift_cart_page_display');
			this.master_log_deletion('#fgf_settings_master_log_deletion');

			//Coupon.
			this.coupon_discount_type('#discount_type');
		}, tabbed_tabs: function ( ) {
			// trigger the clicked link.
			$('.fgf-shortcode-tab').on('click', function (event) {
				event.preventDefault();
				var $this = $(event.currentTarget),
						wrapper = $($this).closest('.fgf-shortcode-wrapper'),
						tab_wrapper = wrapper.find('.fgf-shortcode-tabs-wrapper');

				$('.fgf-shortcode-tab', tab_wrapper).removeClass('fgf-active');
				$($this).addClass('fgf-active');

				$('div.fgf-shortcode-tab-content', wrapper).hide();
				$($($this).attr('href')).show();
			});

			// Trigger the first link.
			$('div.fgf-shortcode-tabs-wrapper').each(function ( ) {
				$(this).find('.fgf-shortcode-tab').eq(0).click( );
			});
		}, toggle_restrict_gift_product_display: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.restrict_gift_product_display($this);
		}, toggle_gift_display_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.gift_display_type($this);
		}, toggle_bogo_eligible_notice_on_single_product: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.bogo_eligible_notice_on_single_product($this);
		}, toggle_bogo_eligible_notice_on_loop_page: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.bogo_eligible_notice_on_loop_page($this);
		}, toggle_checkout_gift_display: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.checkout_gift_display($this);
		}, toggle_checkout_gift_display_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.checkout_gift_display_type($this);
		}, toggle_checkout_gift_display_hook: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.checkout_gift_display_hook($this);
		}, toggle_gift_display_mode: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.gift_display_mode($this);
		}, toggle_table_pagination: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.table_pagination($this);
		}, toggle_carousel_navigation: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.carousel_navigation($this);
		}, toggle_carousel_auto_play: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.carousel_auto_play($this);
		}, toggle_manual_gift_email: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.manual_gift_email($this);
		}, toggle_master_log_deletion: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.master_log_deletion($this);
		}, toggle_master_log_popup_close: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.master_log_popup_close($this);
		}, toggle_coupon_discount_type: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			FGF_Admin.coupon_discount_type($this);
		}, prevent_settings_save: function (event) {
			if ('2' == $('#fgf_settings_gift_display_type').val() && $('#fgf_settings_carousel_gift_per_page').val() > 3) {
				if (!confirm($('#fgf_settings_carousel_gift_per_page').data('error'))) {
					event.preventDefault( );
					return false;
				}
			}

			if ('' === $('#fgf_settings_gifts_count_per_order').val()) {
				event.preventDefault( );
				alert($('#fgf_settings_gifts_count_per_order').data('error'));
				return false;

			}

		}, restrict_gift_product_display: function ($this) {
			if ($($this).is(':checked')) {
				$('#fgf_settings_gift_products_valid_rule_statuses').closest('tr').show();
			} else {
				$('#fgf_settings_gift_products_valid_rule_statuses').closest('tr').hide();
			}
		}, gift_display_type: function ($this) {
			if ($($this).val() === '1') {
				$('.fgf_gift_dropdown_display_type').closest('tr').hide();
				$('.fgf_gift_carousel_display_type').closest('tr').hide();
				$('.fgf_gift_table_display_type').closest('tr').show();
				FGF_Admin.table_pagination('#fgf_settings_gift_display_table_pagination');
			} else if ($($this).val() === '3') {
				$('.fgf_gift_table_display_type').closest('tr').hide();
				$('.fgf_gift_carousel_display_type').closest('tr').hide();
				$('.fgf_gift_dropdown_display_type').closest('tr').show();
			} else {
				$('.fgf_gift_dropdown_display_type').closest('tr').hide();
				$('.fgf_gift_table_display_type').closest('tr').hide();
				$('.fgf_gift_carousel_display_type').closest('tr').show();
				FGF_Admin.carousel_auto_play('#fgf_settings_carousel_auto_play');
				FGF_Admin.carousel_navigation('#fgf_settings_carousel_navigation');
			}
		}, checkout_gift_display: function ($this) {
			$('.fgf-gift-products-checkout-field').closest('tr').hide();

			if ('2' === $($this).val()) {
				$('#fgf_settings_checkout_gift_products_display_type').closest('tr').show();
				FGF_Admin.checkout_gift_display_type($('#fgf_settings_checkout_gift_products_display_type'));
			}
		}, checkout_gift_display_type: function ($this) {
			if ('1' === $($this).val()) {
				$('.fgf-gift-products-checkout-display-type-field').closest('tr').show();
				FGF_Admin.checkout_gift_display_hook($('#fgf_settings_checkout_gift_products_hook_name'));
			} else {
				$('.fgf-gift-products-checkout-display-type-field').closest('tr').hide();
			}
		}, checkout_gift_display_hook: function ($this) {

			if ('3' === $($this).val()) {
				$('.fgf-gift-products-checkout-display-hook-field').closest('tr').show();
			} else {
				$('.fgf-gift-products-checkout-display-hook-field').closest('tr').hide();
			}
		}, bogo_eligible_notice_on_single_product: function ($this) {
			if ('2' === $($this).val()) {
				$('#fgf_settings_bogo_eligible_notice_on_single_product_position').closest('tr').hide();
			} else {
				$('#fgf_settings_bogo_eligible_notice_on_single_product_position').closest('tr').show();
			}
		}, bogo_eligible_notice_on_loop_page: function ($this) {
			if ('2' === $($this).val()) {
				$('#fgf_settings_bogo_eligible_notice_on_loop_page_position').closest('tr').hide();
			} else {
				$('#fgf_settings_bogo_eligible_notice_on_loop_page_position').closest('tr').show();
			}
		}, gift_display_mode: function ($this) {
			if ('1' == $($this).val()) {
				$('#fgf_settings_gift_cart_page_display_position').closest('tr').show();
			} else {
				$('#fgf_settings_gift_cart_page_display_position').closest('tr').hide();
			}
		}, table_pagination: function ($this) {
			if ('1' == $($this).val()) {
				$('#fgf_settings_free_gift_per_page_column_count').closest('tr').show();
			} else {
				$('#fgf_settings_free_gift_per_page_column_count').closest('tr').hide();
			}
		}, carousel_navigation: function ($this) {
			if ($($this).is(":checked")) {
				$('.fgf_carousel_navigation_type').closest('tr').show();
			} else {
				$('.fgf_carousel_navigation_type').closest('tr').hide();
			}
		}, carousel_auto_play: function ($this) {
			if ($($this).is(":checked")) {
				$('.fgf_carousel_auto_play').closest('tr').show();
			} else {
				$('.fgf_carousel_auto_play').closest('tr').hide();
			}
		}, manual_gift_email: function ($this) {
			$('.fgf_manual_gift_email').closest('tr').hide();
			if ($($this).is(":checked")) {
				$('.fgf_manual_gift_email').closest('tr').show();
			}
		}, master_log_deletion: function ($this) {
			if ('2' === $($this).val()) {
				$('#fgf_settings_master_log_deletion_duration').closest('tr').hide();
			} else {
				$('#fgf_settings_master_log_deletion_duration').closest('tr').show();
			}
		}, master_log_popup_close: function ($this) {
			$($this).closest('div.fgf_popup_wrapper').remove();
		}, master_log_popup_outside_click: function ($this) {
			if ($($this.target).attr('class') == "fgf_popup_wrapper") {
				$('.fgf_popup_content').parent().remove();
				$('.fgf_master_log_info_popup_content').parent().remove();
			}
		}, coupon_discount_type: function ($this) {
			if ('fgf_free_gift' == $($this).val()) {
				$('#coupon_amount').closest('p').hide();
			} else {
				$('#coupon_amount').closest('p').show();
			}
		}, manual_gift_btn: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
					table = $($this).closest('table');

			FGF_Admin.block(table);

			var data = ({
				action: 'fgf_create_gift_order',
				user: $('#fgf_manual_gift_selected_user').val( ),
				products: $('#fgf_manual_gift_selected_products').val( ),
				status: $('#fgf_manual_gift_order_status').val( ),
				fgf_security: fgf_admin_params.manual_gift_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					alert(res.data.msg);
					location.reload(true);
				} else {
					alert(res.data.error);
				}
				FGF_Admin.unblock(table);
			});
		}, master_log_info: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);

			FGF_Admin.block($this);
			var data = {
				action: 'fgf_master_log_info_popup',
				master_log_id: $($this).data('fgf_master_log_id'),
				fgf_security: fgf_admin_params.fgf_master_log_info_nonce,
			};

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					$(res.data.popup).appendTo('body');
					$(document).on('click', 'body', FGF_Admin.master_log_popup_outside_click);
				} else {
					alert(res.data.error);
				}

				FGF_Admin.unblock($this);
			});
		}, preview_rule_details: function (event) {
			event.preventDefault( );
			var $this = $(event.currentTarget);

			if ($this.data('preview-data')) {
				$(this).WCBackboneModal({
					template: 'fgf-modal-preview-rules-details',
					variable: $this.data('preview-data')
				});
			} else {
				FGF_Admin.block(this);

				var data = ({
					action: 'fgf_preview_rules_details',
					rule_id: $this.data('rule_id'),
					fgf_security: fgf_admin_params.preview_nonce,
				});

				$.post(ajaxurl, data, function (res) {

					if (true === res.success) {
						$this.data('preview-data', res.data.html);

						$(this).WCBackboneModal({
							template: 'fgf-modal-preview-rules-details',
							variable: res.data.html
						});
					} else {
						alert(res.data.error);
					}
				}
				);

				FGF_Admin.unblock(this);
			}
		}, render_order_item_gift_popup: function (event) {
			event.preventDefault( );

			$(this).WCBackboneModal({
				template: 'fgf-modal-add-order-item-gift'
			});

			return false;
		}, action_confirmation: function (event) {
			var $this = $(event.currentTarget),
					$message = '';

			switch ($($this).data('action')) {
				case 'duplicate':
					$message = fgf_admin_params.duplicate_confirm_msg;
					break;
				case 'delete':
					$message = fgf_admin_params.delete_confirm_msg;
					break;
			}

			if ($message && !confirm($message)) {
				event.preventDefault( );
			} else {
				FGF_Admin.block($this.closest('table'));
			}
		}, bulk_action_confirmation: function (event) {
			var $this = $(event.currentTarget),
					bulk_actions = $($this).closest('.bulkactions'),
					message = '';

			switch ($(bulk_actions).find('select').val()) {
				case 'delete':
					message = fgf_admin_params.delete_confirm_msg;
					break;
			}

			if (message && !confirm(message)) {
				event.preventDefault( );
			} else {
				FGF_Admin.block($this.closest('.fgf_table_wrap').find('table'));
			}
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
		}, backbone: {
			init: function (e, target) {
				switch (target) {
					case 'fgf-modal-add-order-item-gift':
						$(document.body).trigger('fgf-enhanced-init');

						$(this).on('change', '.fgf-order-item-gift-products', function () {
							if (!$(this).closest('tr').is(':last-child')) {
								return;
							}

							$(this).closest('table').find('tbody').append(wp.template('fgf-modal-order-item-gift-row'));

							$(document.body).trigger('fgf-enhanced-init');
						});
						break;
				}
			},
		response: function (e, target) {
			switch (target) {
				case 'fgf-modal-add-order-item-gift':
					FGF_Admin.backbone.add_order_item_gifts(e);
					break;
			}
		},
		add_order_item_gifts: function (event) {
			event.preventDefault();
			var product_details = [],
					order_items = $('#woocommerce-order-items'),
					table = $('.fgf-modal-add-order-item-gift-table');

			table.find('tr').each(function () {
				var product_id = $(this).find('.fgf-order-item-gift-products').val(),
						product_qty = $(this).find('.fgf-order-item-gifts-quantity').val();

				if (product_id && 0 !== product_id.length) {
					product_details.push({
						'id': product_id,
						'qty': product_qty ? product_qty : 1
						});
				}
			});

			if (0 === product_details.length) {
				alert(fgf_admin_params.products_empty_msg);
				return false;
			}

			FGF_Admin.block(order_items);

			var data = {
				action: 'fgf_add_order_item_gifts',
				data: product_details,
				order_id: table.find('tbody').data('order-id'),
				fgf_security: fgf_admin_params.manual_gift_nonce
			};

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					alert(res.data.msg);
					order_items.trigger('wc_order_items_reload');
				} else {
					alert(res.data.error);
				}

				FGF_Admin.unblock(order_items);
			});

		}
		}
	};
	FGF_Admin.init( );
});
