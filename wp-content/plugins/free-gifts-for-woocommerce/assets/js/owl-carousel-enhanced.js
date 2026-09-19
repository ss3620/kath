/* global fgf_owl_carousel_params */

jQuery(function ($) {
	'use strict';
	try {

		$(document.body).on('fgf-enhanced-carousel', function ( ) {

			var owl_carousels = $('.fgf-owl-carousel-items');
			if (!owl_carousels.length) {
				return;
			}

			var item_count = $('.fgf-owl-carousel-item').length,
					desktop_item_count = (item_count < fgf_owl_carousel_params.desktop_count) ? item_count : fgf_owl_carousel_params.desktop_count,
					tablet_item_count = (item_count < fgf_owl_carousel_params.tablet_count) ? item_count : fgf_owl_carousel_params.tablet_count,
					mobile_item_count = (item_count < fgf_owl_carousel_params.mobile_count) ? item_count : fgf_owl_carousel_params.mobile_count;

			owl_carousels.each(function (e) {
				$(this).owlCarousel({
					loop: true,
					margin: parseInt(fgf_owl_carousel_params.item_margin),
					responsiveClass: true,
					nav: ('true' === fgf_owl_carousel_params.nav),
					navText: [fgf_owl_carousel_params.nav_prev_text, fgf_owl_carousel_params.nav_next_text],
					autoplay: ('true' === fgf_owl_carousel_params.auto_play),
					dots: ('true' === fgf_owl_carousel_params.pagination),
					slideBy: fgf_owl_carousel_params.item_per_slide,
					autoplayTimeout: fgf_owl_carousel_params.slide_speed,
					autoplayHoverPause: true,
					responsive: {
						0: {
							items: mobile_item_count,
						},
						600: {
							items: tablet_item_count,
						},
						1000: {
							items: desktop_item_count,
						}
					}
				});
			});
		});

		// Initialize carousel when cart updated.
		$(document.body).on('updated_wc_div', function ( ) {
			$(document.body).trigger('fgf-enhanced-carousel');
		});

		$(document.body).trigger('fgf-enhanced-carousel');

	} catch (err) {
		window.console.log(err);
	}

});
