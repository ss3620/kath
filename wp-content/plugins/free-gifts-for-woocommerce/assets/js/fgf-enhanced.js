/* global fgf_enhanced_params */

jQuery(function ($) {
	'use strict';

	try {

		function fgf_get_enhanced_select_format_string() {
			return {
				'language': {
					errorLoading: function () {
						return fgf_enhanced_params.i18n_searching;
					},
					inputTooLong: function (args) {
						var overChars = args.input.length - args.maximum;

						if (1 === overChars) {
							return fgf_enhanced_params.i18n_input_too_long_1;
						}

						return fgf_enhanced_params.i18n_input_too_long_n.replace('%qty%', overChars);
					},
					inputTooShort: function (args) {
						var remainingChars = args.minimum - args.input.length;

						if (1 === remainingChars) {
							return fgf_enhanced_params.i18n_input_too_short_1;
						}

						return fgf_enhanced_params.i18n_input_too_short_n.replace('%qty%', remainingChars);
					},
					loadingMore: function () {
						return fgf_enhanced_params.i18n_load_more;
					},
					maximumSelected: function (args) {
						if (args.maximum === 1) {
							return fgf_enhanced_params.i18n_selection_too_long_1;
						}

						return fgf_enhanced_params.i18n_selection_too_long_n.replace('%qty%', args.maximum);
					},
					noResults: function () {
						return fgf_enhanced_params.i18n_no_matches;
					},
					searching: function () {
						return fgf_enhanced_params.i18n_searching;
					}
				}
			};
		}

		$(document.body).on('fgf-enhanced-init', function () {
			if ($('select.fgf_select2').length) {
				//Select2 with customization
				$('select.fgf_select2').each(function () {
					var select2_args = {
						allowClear: $(this).data('allow_clear') ? true : false,
						placeholder: $(this).data('placeholder'),
						minimumResultsForSearch: 10
					};

					select2_args = $.extend(select2_args, fgf_get_enhanced_select_format_string());

					$(this).selectWoo(select2_args);
				});
			}
			if ($('select.fgf_select2_search').length) {
				//Multiple select with ajax search
				$('select.fgf_select2_search').each(function () {
					var select2_args = {
						allowClear: $(this).data('allow_clear') ? true : false,
						placeholder: $(this).data('placeholder'),
						minimumInputLength: $(this).data('minimum_input_length') ? $(this).data('minimum_input_length') : 3,
						escapeMarkup: function (m) {
							return m;
						},
						ajax: {
							url: fgf_enhanced_params.ajaxurl,
							dataType: 'json',
							delay: 250,
							data: function (params) {
								return {
									term: params.term,
									action: $(this).data('action') ? $(this).data('action') : 'fgf_json_search_customers',
									display_stock: $(this).data('display-stock') ? $(this).data('display-stock') : 'no',
									exclude_global_variable: $(this).data('exclude-global-variable') ? $(this).data('exclude-global-variable') : 'no',
									fgf_security: $(this).data('nonce') ? $(this).data('nonce') : fgf_enhanced_params.search_nonce,
								};
							},
							processResults: function (data) {
								var terms = [];
								if (data) {
									$.each(data, function (id, term) {
										terms.push({
											id: id,
											text: term
										});
									});
								}
								return {
									results: terms
								};
							},
							cache: true
						}
					};

					select2_args = $.extend(select2_args, fgf_get_enhanced_select_format_string());

					$(this).selectWoo(select2_args);
				});
			}

			if ($('.fgf_datepicker').length) {
				$('.fgf_datepicker').on('change', function ( ) {
					if ($(this).val() === '') {
						$(this).next().next(".fgf_alter_datepicker_value").val('');
					}
				});

				$('.fgf_datepicker').each(function ( ) {
					$(this).datepicker({
						altField: $(this).next(".fgf_alter_datepicker_value"),
						altFormat: 'yy-mm-dd',
						changeMonth: true,
						changeYear: true,
						showButtonPanel: true,
						showOn: "button",
						buttonImage: fgf_enhanced_params.calendar_image,
						buttonImageOnly: true
					});
				});
			}

			if ($('.fgf_datetimepicker').length) {
				$('.fgf_datetimepicker').on('change', function () {
					if ($(this).val() === '') {
						$(this).next().next('.fgf_alter_datepicker_value').val('');
					}
				});
				$('.fgf_datetimepicker').each(function () {
					$(this).datetimepicker({
						altField: $(this).next(".fgf_alter_datepicker_value"),
						altFormat: 'yy-mm-dd',
						altFieldTimeOnly: false,
						dateFormat: fgf_enhanced_params.date_format,
						changeMonth: true,
						changeYear: true,
						showButtonPanel: true,
						showOn: "button",
						buttonImage: fgf_enhanced_params.calendar_image,
						buttonImageOnly: true
					});
				});
			}

		});

		$(document.body).trigger('fgf-enhanced-init');
	} catch (err) {
		window.console.log(err);
	}

});
