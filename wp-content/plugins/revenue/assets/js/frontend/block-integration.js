/* global revenue_campaign jQuery */
// the below line ignores revenue_campaign not camel case warning
/* eslint-disable camelcase */

( function ( $ ) {
	'use strict';

	$( function () {
		init();
	} );

	// ------------------
	// Functions
	// ------------------

	function init() {
		$( '.revenue-block-slot' ).each( function () {
			const $slot = $( this );
			const position = $slot.data( 'position' ); // e.g., revenue-before-cart

			if ( ! position ) {
				return true;
			}

			$.ajax( {
				url: revenue_campaign.ajax, // from wp_localize_script
				method: 'GET',
				data: {
					action: 'revenue_get_campaign_html',
					position,
					security: revenue_campaign.nonce,
				},
				success: ( response ) => {
					if ( response.success ) {
						$slot.html( response.data.innerHtml );
						$( document.body ).trigger( 'updated_checkout' );
					}
				},
			} );
		} );
	}

	// ------------------
	// Block Register Filters and Functions
	// ------------------

	const { registerCheckoutFilters } = window.wc.blocksCheckout;

	// Register filters for the bundle discount campaign
	// combinely handle the bundle discount on the block cart page and the checkout page
	registerCheckoutFilters( 'revenue-bundle-discount', {
		subtotalPriceFormat: ( value, extensions, args ) => {
			// Apply only to the Cart and checkout context
			// checkout = summary
			if ( ! [ 'cart', 'summary' ].includes( args?.context ) ) {
				return value;
			}
			if (
				! extensions?.revenue?.is_bundle_parent ||
				! extensions?.revenue?.line_subtotal_price
			) {
				return value;
			}
			const price = extensions?.revenue?.line_subtotal_price;
			const formatedPrice = window.Revenue.formatPrice( price );
			// Return modified format; must include <price/> tag
			return `${ formatedPrice } <price/> `;
		},
		cartItemPrice: ( value, extensions, args ) => {
			// Apply only to the Cart and checkout context
			// checkout = summary
			if ( ! [ 'cart', 'summary' ].includes( args?.context ) ) {
				return value;
			}
			if (
				! extensions?.revenue?.is_bundle_parent ||
				! extensions?.revenue?.line_total_price
			) {
				return value;
			}
			const price = extensions?.revenue?.line_total_price;

			const formatedPrice = window.Revenue.formatPrice( price );
			// Return modified format; must include <price/> tag
			return `${ formatedPrice } <price/> `;
		},
		cartItemClass: ( className, extensions, args ) => {
			// Apply only to the Cart and checkout context
			// checkout = summary
			if ( ! [ 'cart', 'summary' ].includes( args?.context ) ) {
				return className;
			}

			if ( extensions?.revenue?.is_bundle_child ) {
				return className + ' is-bundle-child';
			}
			if ( extensions?.revenue?.is_bundle_parent ) {
				return className + ' is-bundle-parent';
			}
			// Return modified format; must include <price/> tag
			return className;
		},
		showRemoveItemLink: ( showRemoveItemLink, extensions, args ) => {
			// Apply only to the Cart context
			if ( args?.context !== 'cart' ) {
				return showRemoveItemLink;
			}
			if ( extensions?.revenue?.is_bundle_child ) {
				return false;
			}

			return showRemoveItemLink;
		},
	} );
} )( jQuery );
