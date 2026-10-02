/**
 * Two small jobs, both about a widget PHP has already rendered.
 *
 * 1. Move it in front of a merchant-configured CSS selector, when one is set.
 * 2. Update its price when the shopper picks a product variation.
 *
 * What this file deliberately does not do: build the widget, read a price out of the
 * page, watch the DOM, or retry. The markup and the price come from the server, and the
 * one number that can change on the client — the variation price — is handed to us by
 * WooCommerce in the found_variation payload as display_price, which the server computed
 * with wc_get_price_to_display(). Discount, tax and the chosen variation are already
 * applied there, so there is nothing to parse and nothing to guess.
 */
( function ( window, document ) {
	'use strict';

	var config = window.zipWidgetPlacement;

	if ( ! config || ( 'product' !== config.type && 'cart' !== config.type ) ) {
		return;
	}

	var widgetSelector = '.widget-' + config.type;

	// Set on the node once we have relocated it, so a later run can tell our copy from one
	// the server has just printed.
	var placedAttribute = 'data-zip-placed';

	// Printed by the server on every widget: "auto" for the one the position hook rendered,
	// "manual" for one the merchant placed with the [zip_widget] shortcode. The browser
	// cannot tell them apart on its own, and guessing is what let this script relocate —
	// and then delete — a widget it never owned.
	var originAttribute = 'data-zip-origin';

	/**
	 * The server-rendered widgets on the page, in document order. A node inside the cart
	 * block belongs to React, which would re-create it the moment we moved it, so it is
	 * never a candidate. The server already declines to enqueue this file on a block cart;
	 * this is the second line.
	 */
	function widgets() {
		var nodes = document.querySelectorAll( widgetSelector );
		var found = [];
		var index;

		for ( index = 0; index < nodes.length; index += 1 ) {
			if ( ! nodes[ index ].closest( '.wp-block-woocommerce-cart, .wp-block-woocommerce-checkout' ) ) {
				found.push( nodes[ index ] );
			}
		}

		return found;
	}

	/**
	 * The widgets the render hook printed — the only ones this script may move or remove.
	 *
	 * A node without the attribute counts as automatic: markup cached by a page cache from
	 * before this version has no origin, and treating it as the merchant's would silently
	 * stop honouring the selector.
	 */
	function autoWidgets() {
		return widgets().filter( function ( node ) {
			return 'manual' !== node.getAttribute( originAttribute );
		} );
	}

	/**
	 * Remove the copies we relocated once the server has printed a fresh one.
	 *
	 * The classic cart replaces .woocommerce-cart-form and .cart_totals over AJAX, and the
	 * fragment carries a freshly rendered widget with the new total. Ours has by then been
	 * moved in front of the merchant's element — outside the replaced fragment — so it
	 * survives, and the shopper is left with two quotes, the relocated one showing the
	 * price the page loaded with.
	 *
	 * @param {Array} nodes Automatic widgets.
	 * @param {Node}  keep  The one to keep.
	 */
	function dropRelocatedDuplicates( nodes, keep ) {
		nodes.forEach( function ( node ) {
			if ( node !== keep && node.hasAttribute( placedAttribute ) && node.parentNode ) {
				node.parentNode.removeChild( node );
			}
		} );
	}

	/**
	 * The widget this script is responsible for placing, or null when there is none.
	 */
	function placeableWidget() {
		var nodes = autoWidgets();
		var chosen = null;
		var index;

		for ( index = 0; index < nodes.length; index += 1 ) {
			if ( ! nodes[ index ].hasAttribute( placedAttribute ) ) {
				chosen = nodes[ index ];
			}
		}

		if ( null === chosen ) {
			return nodes.length ? nodes[ nodes.length - 1 ] : null;
		}

		dropRelocatedDuplicates( nodes, chosen );

		return chosen;
	}

	/**
	 * Ask the Zip bundle to redraw. No retry on purpose: if the bundle has not loaded
	 * yet it will read the current attribute when it does, so there is nothing to wait
	 * for.
	 */
	function rerender() {
		var zip = window.Zip;

		if ( zip && zip.Widget && 'function' === typeof zip.Widget.render ) {
			try {
				zip.Widget.render();
			} catch ( error ) {
				// The bundle is third-party. A failed redraw leaves the widget as it is,
				// which is the correct fallback.
			}
		}
	}

	/**
	 * Move the widget in front of the configured element.
	 *
	 * A selector that matches nothing is not an error: the widget simply stays where the
	 * render hook put it. That is why the hook is never suppressed.
	 */
	function move() {
		if ( ! config.selector ) {
			return;
		}

		var node = placeableWidget();

		if ( ! node ) {
			return;
		}

		var target;

		try {
			target = document.querySelector( config.selector );
		} catch ( error ) {
			// Invalid CSS. Leave the widget in its configured position.
			return;
		}

		// node.contains( target ) is not paranoia: the Zip bundle fills the widget with its
		// own markup after load, and a broad merchant selector can match something inside
		// it. insertBefore() would then be asked to put the widget inside itself, which
		// throws and takes the rest of the handler with it.
		if ( ! target || ! target.parentNode || target === node || node.contains( target ) ) {
			return;
		}

		if ( node.nextElementSibling !== target ) {
			target.parentNode.insertBefore( node, target );
		}

		// Marked even when it was already in place. The mark says "this is the copy we are
		// responsible for", not "we moved it": a configured position can happen to land
		// right in front of the selector, and an unmarked copy would then outlive the
		// fragment that replaces it and leave the page with two.
		node.setAttribute( placedAttribute, '' );
	}

	/**
	 * Quote the new price on every widget the server printed, not just the placed one.
	 *
	 * A page can legitimately carry two — the one the hook rendered plus a [zip_widget]
	 * shortcode — and updating one of them leaves the shopper looking at two different
	 * instalment figures for the same variation.
	 *
	 * @param {number|string} price display_price from the variation payload.
	 */
	function setPrice( price ) {
		var value = Number( price );

		if ( ! isFinite( value ) || value < 0 ) {
			return;
		}

		var changed = false;

		widgets().forEach( function ( node ) {
			if ( node.getAttribute( 'data-zm-price' ) !== String( value ) ) {
				node.setAttribute( 'data-zm-price', String( value ) );
				changed = true;
			}
		} );

		if ( changed ) {
			rerender();
		}
	}

	function start() {
		move();

		if ( 'product' !== config.type || ! window.jQuery ) {
			return;
		}

		// found_variation carries the variation object WooCommerce built server-side.
		// reset_data fires when the shopper clears the selection and the page goes back
		// to the range, which is when the server-rendered starting price applies again.
		window
			.jQuery( document.body )
			.on( 'found_variation', function ( event, variation ) {
				if ( variation && undefined !== variation.display_price ) {
					setPrice( variation.display_price );
				}
			} )
			.on( 'reset_data', function () {
				if ( undefined !== config.price && null !== config.price ) {
					setPrice( config.price );
				}
			} );
	}

	// The classic cart replaces .woocommerce-cart-form and .cart_totals over AJAX and the
	// server re-renders the widget inside that fragment, so the move has to run again.
	// The price needs no attention here: it came back from the server with the fragment.
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'updated_wc_div wc_fragments_refreshed', move );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )( window, document );
