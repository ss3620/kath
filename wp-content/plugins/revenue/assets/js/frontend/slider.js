/* global revenue_campaign jQuery */
/* eslint-disable camelcase */
( function ( $ ) {
	'use strict';

	// $( '.revx-slider-wrapper' ).each( function () {
	$( document ).on( 'click', '.revx-slider-controller', function () {
		const $button = $( this );
		const isNext = $button.hasClass( 'next' );

		const $container = $button.closest( '.revx-slider-container' );
		const $scrollWrapper = $container.find( '.revx-slider-content' );

		function getBreakpoint() {
			const windowWidth = $( window ).width();
			if ( windowWidth > 992 ) {
				return 'lg';
			}
			// if ( windowWidth > 768 ) {
			// 	return 'md';
			// }
			// if ( windowWidth > 480 ) {
			// 	return 'sm';
			// }
			const containerWidth = $container.width();
			if ( containerWidth <= 440 ) {
				return 'sm';
			}
			if ( containerWidth <= 740 ) {
				return 'md';
			}
			return 'lg';
		}

		function getSliderGap() {
			const breakpoint = getBreakpoint();
			const $scrollContainer = $container.closest(
				'.revx-campaign-container'
			);
			const campaignId = $scrollContainer.data( 'campaign-id' );
			const campaignType =
				revenue_campaign?.data?.[ campaignId ]?.revenue_campaign_type;
			const campaignView =
				revenue_campaign?.data?.[ campaignId ]?.revenue_campaign_view;
			const isDivider =
				revenue_campaign?.data?.[ campaignId ]?.is_campaign_divider;
			const gap =
				revenue_campaign?.data?.[ campaignId ]?.revenue_slider_gap?.[
					breakpoint
				]?.grid?.value || 0;
			const dividerSize =
				revenue_campaign?.data?.[ campaignId ]?.revenue_divider_size?.[
					breakpoint
				]?.grid?.value || 0;

			const isBuyXGetY = campaignType === 'buy_x_get_y';

			let separatorSize = 0;
			if ( isBuyXGetY ) {
				separatorSize =
					campaignView === 'grid'
						? revenue_campaign?.data?.[ campaignId ]
								?.revenue_separator_width?.[ breakpoint ]?.grid
								?.value || 0
						: revenue_campaign?.data?.[ campaignId ]
								?.revenue_separator_height?.[ breakpoint ]
								?.value || 0;
			}

			if ( isDivider ) {
				return Number( dividerSize ) + Number( gap ) + Number( gap );
			}
			if ( isBuyXGetY ) {
				return Number( separatorSize ) + Number( gap ) + Number( gap );
			}
			return Number( gap );
		}

		const sliderGap = parseFloat( getSliderGap() ) || 0;
		const itemWidth =
			$scrollWrapper
				.find( '.revx-slider-product' )
				.first()
				.outerWidth( true ) + sliderGap;
		const scrollAmount = isNext ? itemWidth : -itemWidth;

		$scrollWrapper
			.stop( true, true )
			.animate(
				{ scrollLeft: $scrollWrapper.scrollLeft() + scrollAmount },
				300
			);
	} );
	// } );
} )( jQuery );
