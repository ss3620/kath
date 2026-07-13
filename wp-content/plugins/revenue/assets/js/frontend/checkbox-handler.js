/* global Revenue jQuery */
/* eslint-disable camelcase */
// this file handles the main checkbox toggle logic, and also the frequently bought together campaign dynamic value update.
jQuery( function ( $ ) {
	function updatePriceAndQuantity(
		$container,
		titleTag,
		priceTag,
		quantity,
		price
	) {
		const $quantityContainer = $container.find(
			`[data-smart-tag="${ titleTag }"]`
		);
		const smartTitle = $quantityContainer.data( 'smart-tag-text' ) ?? '';
		$quantityContainer.text( smartTitle.replace( '{qty}', quantity ) );

		const $priceContainer = $container.find(
			`[data-smart-tag="${ priceTag }"]`
		);
		$priceContainer.text( Revenue.formatPrice( price ) );
	}

	function handleFbtCheckBoxClick( $container ) {
		const $parentContainer = $container.parent();
		let totalQuantity = 0;
		let totalPrice = 0;
		// rp = required product
		let rpQuantity = 0;
		let rpTotalPrice = 0;
		// loop through the active checkboxes to calculate the total price
		$parentContainer.find( '.revx-active' ).each( function () {
			const $itemContainer = $( this ).closest(
				'[data-product-offered-price]'
			);
			// check if this is one of the required product or not
			const isRequired =
				$itemContainer.find( '.revx-required-product' ).length > 0 ||
				$itemContainer.data( 'is-trigger' ) === 'yes';

			let price;
			const quantity = $itemContainer.data( 'product-qty' );
			// when the product is required there shouldn't be any discount.
			// use regular price for calculation
			if ( isRequired ) {
				price =
					$itemContainer.data( 'product-offered-price' ) ??
					$itemContainer.data( 'regular-price' );
				rpQuantity += parseInt( quantity );
				rpTotalPrice += parseFloat( price ) * parseInt( quantity );
			} else {
				// frequently bought together useage product-offered-price data attribute
				price = $itemContainer.data( 'product-offered-price' );
			}
			totalQuantity += parseInt( quantity );
			totalPrice += parseFloat( price ) * parseInt( quantity );
		} );

		const $topLevelContainer = $parentContainer.closest( '.revx-template' ); // easy to find, most likely non-replaceable
		const $totalPriceContainer = $topLevelContainer
			.find( '[data-fbt-total]' )
			.find( '[data-smart-tag="selectedTotalTitle"]' );
		$totalPriceContainer.text( Revenue.formatPrice( totalPrice ) );

		// required product contiainers price and quantity
		const $triggerContainer = $topLevelContainer.find(
			'[data-fbt-trigger-items]'
		);
		updatePriceAndQuantity(
			$triggerContainer,
			'selectedTriggerTitle',
			'selectedPrice',
			rpQuantity,
			rpTotalPrice
		);

		// Add on product contiainers price and quantity
		const $addOnContainer = $topLevelContainer.find(
			'[data-fbt-offer-items]'
		);
		updatePriceAndQuantity(
			$addOnContainer,
			'selectedOfferTitle',
			'selectedPrice',
			totalQuantity - rpQuantity,
			totalPrice - rpTotalPrice
		);
	}

	function toggleCheckbox( $checkbox ) {
		const isChecked = $checkbox.attr( 'data-is-checked' );

		if ( isChecked === 'yes' ) {
			$checkbox
				.data( 'is-checked', 'no' )
				.attr( 'data-is-checked', 'no' )
				.removeClass( 'revx-active' )
				.addClass( 'revx-inactive' );
		} else {
			$checkbox
				.data( 'is-checked', 'yes' )
				.attr( 'data-is-checked', 'yes' )
				.removeClass( 'revx-inactive' )
				.addClass( 'revx-active' );
		}

		// Dispatch a custom event AFTER toggle is done
		$checkbox.trigger( 'revx-checkbox-toggled' );
	}

	// track clicks and update checkbox. This is the main checkbox handler
	$( document ).on( 'click', '.revx-checkbox-container', function () {
		const $checkbox = $( this );

		toggleCheckbox( $checkbox );

		const $itemContainer = $checkbox.closest( '[campaign_type]' );
		const campaign_type = $itemContainer.attr( 'campaign_type' );

		if ( 'frequently_bought_together' === campaign_type ) {
			handleFbtCheckBoxClick( $itemContainer );
		}
	} );

	// track variation change from variation-product-selection.js file and update fbt price
	$( document ).on(
		'revx-variation-changed revx-quantity-changed revx-fbt-init',
		'[campaign_type="frequently_bought_together"]',
		function () {
			handleFbtCheckBoxClick( $( this ) );
		}
	);
	$( document.body ).on(
		'updated_cart_totals added_to_cart removed_from_cart',
		function () {
			$( '[campaign_type="frequently_bought_together"]' ).each(
				function () {
					// trigger the event to update the price
					$( this ).trigger( 'revx-fbt-init' );
				}
			);
		}
	);
} );
