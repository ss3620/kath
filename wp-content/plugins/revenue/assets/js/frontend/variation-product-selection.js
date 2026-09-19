/* global revenue_campaign Revenue jQuery */
// the below line ignores revenue_campaign not camel case warning
/* eslint-disable camelcase */
jQuery( function ( $ ) {
	if ( typeof revenue_campaign === 'undefined' ) {
		console.error( 'Revenue campaign script not loaded.' );
		return;
	}

	function setSaveCapsuleText( amount ) {
		const $amountPriceContianer = $( this ).find(
			'.woocommerce-Price-amount'
		);

		if ( $amountPriceContianer.length ) {
			$amountPriceContianer.text( Revenue.formatPrice( amount ) );
		}
	}
	function setPriceText( price ) {
		const $priceContainer = $( this ).find( '.woocommerce-Price-amount' );
		$priceContainer.text( Revenue.formatPrice( price ) );
	}
	// to handle case when there is default attributes or variation of a product
	// trigger change event on initial load.
	$( function () {
		// for all variation products
		$( '[data-variations] .revx-product-Attr-wrapper' ).trigger( 'change' );
		// for volume discount variation products
		$(
			'[data-campaign-type="volume_discount"] .revx-product-Attr-wrapper'
		).trigger( 'change' );
	} );
	// for all other campaigns other than volume discount with variation product
	$( document ).on(
		'change',
		'[data-variations] .revx-product-Attr-wrapper',
		function () {
			const $container = $( this ).closest( '[data-variations]' );
			const variationMap = JSON.parse(
				$container.attr( 'data-variations' ) || '[]'
			);

			// Collect selected attributes
			const selectedAttrs = {};
			$container.find( '.revx-product-Attr-wrapper' ).each( function () {
				selectedAttrs[ $( this ).attr( 'name' ) ] = $( this ).val();
			} );

			// Try to find a matching variation (compare with variation.attributes)
			const matchedVariation = variationMap.find( ( variation ) => {
				if ( ! variation.attributes ) return false;
				return Object.entries( selectedAttrs ).every(
					( [ key, val ] ) =>
						! val ||
						! variation.attributes[
							key.replace( /attribute_/, '' )
						] ||
						variation.attributes[
							key.replace( /attribute_/, '' )
						] === val
				);
			} );

			// Update price if we found a match
			if ( matchedVariation ) {
				const $offerItemContainer = $container;
				const $saveAmountContainer = $offerItemContainer.find(
					'[data-smart-tag="saveBadgeWrapper"]'
				);
				const $regularPriceContainer = $offerItemContainer.find(
					'.revx-product-regular-price'
				);
				const $oldPriceContainer = $offerItemContainer.find(
					'.revx-product-old-price'
				);
				const $offeredPriceContainer = $offerItemContainer.find(
					'.revx-product-offered-price'
				);

				setSaveCapsuleText.call(
					$saveAmountContainer,
					matchedVariation.saved_amount
				);
				setPriceText.call(
					$regularPriceContainer,
					matchedVariation.regular_price
				);
				setPriceText.call(
					$oldPriceContainer,
					matchedVariation.regular_price
				);
				$container.attr(
					'data-regular-price',
					matchedVariation?.regular_price
				);
				// for buy x get y = is-x-product , is-trigger = fbt, bundle
				const isRequired =
					$container.data( 'is-x-product' ) === 'yes' ||
					$container.data( 'is-trigger' ) === 'yes';
				// if the product is trigger product or x product, it will get regular price
				setPriceText.call(
					$offeredPriceContainer,
					matchedVariation?.offered_price ||
						( isRequired ? matchedVariation?.regular_price : 0 )
				);
				$container.attr(
					'data-offered-price',
					matchedVariation?.offered_price ||
						( isRequired ? matchedVariation?.regular_price : 0 )
				);
				// since both have some common classes, this can be done with same function
				// make seperate funciton if situation changes, and use else if ladder or if.
				if (
					$container.attr( 'campaign_type' ) === 'bundle_discount' ||
					$container.attr( 'campaign_type' ) === 'buy_x_get_y'
				) {
					const $bundleTopContainer =
						$container.closest( '.revx-template' );
					$bundleTopContainer.trigger( 'revx-variation-changed' );
					// updateBundleTotal( $bundleTopContainer );
				} else if (
					$container.attr( 'campaign_type' ) ===
					'frequently_bought_together'
				) {
					// update data for js usage, needed for frequently bought together
					$container.data(
						'regular-price',
						matchedVariation?.regular_price
					);
					$container.data(
						'product-offered-price',
						matchedVariation?.offered_price ||
							( isRequired ? matchedVariation?.regular_price : 0 )
					);
					// check to see if this belongs to any active checkbox
					if ( $container.find( '.revx-active' ).length ) {
						// Dispatch a custom event AFTER vairation changes to recalculate frequenty bought together price
						$container.trigger( 'revx-variation-changed' );
					}
				}
			}

			// === Filter other attributes ===
			$container.find( '.revx-product-Attr-wrapper' ).each( function () {
				const $select = $( this );
				const attrName = $select.attr( 'name' );
				const attrNameFiltered = attrName.replace( /attribute_/, '' );

				// Build list of allowed options for this attribute given others
				const allowedValues = new Set(); // use Set to avoid duplicates
				allowedValues.add( '' );
				for ( const variation of variationMap ) {
					const fits = Object.entries( selectedAttrs ).every(
						( [ key, val ] ) =>
							key === attrName ||
							! val ||
							! variation.attributes[
								key.replace( /attribute_/, '' )
							] ||
							variation.attributes[
								key.replace( /^attribute_/, '' )
							] === val
					);
					if ( fits ) {
						if ( variation.attributes[ attrNameFiltered ] ) {
							allowedValues.add(
								variation.attributes[ attrNameFiltered ]
							);
						} else {
							const options = JSON.parse(
								$( this ).attr( 'data-options' ) || '[]'
							);
							options?.forEach( ( option ) =>
								allowedValues.add( option )
							);
						}
					}
				}

				// Update select options (enable/disable)
				$select.find( 'option' ).each( function () {
					const $opt = $( this );

					// keep "choose" option
					if ( allowedValues.has( $opt.val() ) ) {
						$opt.prop( 'disabled', false ).show();
					} else {
						$opt.prop( 'disabled', true ).hide();
					}
				} );
			} );

			// Store matched variation + selected attributes
			$container.attr(
				'data-selected-value',
				JSON.stringify( selectedAttrs || {} )
			);
			$container.attr(
				'data-matched-variation',
				JSON.stringify( matchedVariation || {} )
			);
			// setting variation id for payload usage
			$container.attr(
				'data-variation-id',
				JSON.stringify( matchedVariation?.id ?? 0 )
			);
			// update image source
			if ( matchedVariation?.image_url ) {
				$container
					.find( 'img' )
					.attr( 'src', matchedVariation?.image_url );
			}
		}
	);

	// for volume discount only
	$( '[data-campaign-type="volume_discount"]' ).on(
		'change',
		'.revx-product-Attr-wrapper',
		function () {
			const $container = $( this ).closest( '[data-product-id]' );
			const variationMap = JSON.parse(
				$container.attr( 'data-variation-map' ) || '[]'
			);

			// Collect selected attributes
			const selectedAttrs = {};
			$container.find( '.revx-product-Attr-wrapper' ).each( function () {
				selectedAttrs[ $( this ).attr( 'name' ) ] = $( this ).val();
			} );

			// Try to find a matching variation (compare with variation.attributes)
			const matchedVariation = variationMap.find( ( variation ) => {
				if ( ! variation.attributes ) return false;
				return Object.entries( selectedAttrs ).every(
					( [ key, val ] ) =>
						! val ||
						! variation.attributes[ key ] ||
						variation.attributes[ key ] === val
				);
			} );

			// Store matched variation + selected attributes
			$container.attr(
				'data-selected-value',
				JSON.stringify( selectedAttrs || {} )
			);
			$container.attr(
				'data-matched-variation',
				JSON.stringify( matchedVariation || {} )
			);

			// Update price if we found a match
			if ( matchedVariation ) {
				const $offerItemContainer =
					$container.closest( '[data-offer-item]' );
				const $saveAmountContainer = $offerItemContainer.find(
					'[data-smart-tag="saveBadgeWrapper"]'
				);
				const $oldPriceContainer = $offerItemContainer.find(
					'.revx-product-old-price'
				);
				const $offeredPriceContainer = $offerItemContainer.find(
					'.revx-product-offered-price'
				);
				const $radioButton = $container
					.closest( '[data-campaign-type="volume_discount"]' )
					.find( '[data-saved-amount]' );

				// Check if multiple variation selection is enabled
				const $volumeContainer = $container.closest(
					'.revx-volume-discount-item'
				);
				const $allVariationWrappers = $volumeContainer.find(
					'.revx-volume-attributes [data-variation-map]'
				);

				if ( $allVariationWrappers.length > 1 ) {
					// Multiple variations - calculate total price
					let totalRegularPrice = 0;
					let totalOfferedPrice = 0;
					let totalSavedAmount = 0;

					$allVariationWrappers.each( function () {
						const currMatchedVariation = JSON.parse(
							$( this ).attr( 'data-matched-variation' ) ?? '{}'
						);

						if ( currMatchedVariation ) {
							totalRegularPrice += parseFloat(
								currMatchedVariation.regular_price || 0
							);
							totalOfferedPrice += parseFloat(
								currMatchedVariation.offered_price || 0
							);
							totalSavedAmount += parseFloat(
								currMatchedVariation.saved_amount || 0
							);
						}
					} );

					$radioButton.data( 'saved-amount', totalSavedAmount );
					setSaveCapsuleText.call(
						$saveAmountContainer,
						totalSavedAmount
					);
					setPriceText.call( $oldPriceContainer, totalRegularPrice );
					setPriceText.call(
						$offeredPriceContainer,
						totalOfferedPrice
					);
				} else {
					// Single variation - use matched variation price
					$radioButton.data(
						'saved-amount',
						matchedVariation.saved_amount
					);
					setSaveCapsuleText.call(
						$saveAmountContainer,
						matchedVariation.saved_amount
					);
					setPriceText.call(
						$oldPriceContainer,
						matchedVariation.regular_price
					);
					setPriceText.call(
						$offeredPriceContainer,
						matchedVariation.offered_price
					);
				}

				// trigger the `handleRadioClick` function of `add-to-cart.js` file with the correct container to update the atc button text .
				$radioButton.trigger( '.revx-volume-discount-item' );
			}

			// === Filter other attributes ===
			$container.find( '.revx-product-Attr-wrapper' ).each( function () {
				const $select = $( this );
				const attrName = $select.attr( 'name' );

				// Build list of allowed options for this attribute given others
				const allowedValues = new Set(); // use Set to avoid duplicates
				allowedValues.add( '' );
				for ( const variation of variationMap ) {
					const fits = Object.entries( selectedAttrs ).every(
						( [ key, val ] ) =>
							key === attrName ||
							! val ||
							! variation.attributes[ key ] ||
							variation.attributes[ key ] === val
					);
					if ( fits ) {
						if ( variation.attributes[ attrName ] ) {
							allowedValues.add(
								variation.attributes[ attrName ]
							);
						} else {
							const options = JSON.parse(
								$( this ).attr( 'data-options' ) || '[]'
							);
							options?.forEach( ( option ) =>
								allowedValues.add( option )
							);
						}
					}
				}

				// Update select options (enable/disable)
				$select.find( 'option' ).each( function () {
					const $opt = $( this );

					// keep "choose" option
					if ( allowedValues.has( $opt.val() ) ) {
						$opt.prop( 'disabled', false ).show();
					} else {
						$opt.prop( 'disabled', true ).hide();
					}
				} );
			} );
		}
	);

	// TODO: remove this funciton, handled in campaign-total.js now
	// can handle bundle and buy x get y
	function updateBundleTotal( $container ) {
		let totalRegular = 0;
		let totalOffer = 0;
		// data-product-id is common in buy x get y and bungle.
		$container.find( '[data-product-id]' ).each( function () {
			const qty = parseInt( $( this ).attr( 'data-product-qty' ) ) || 1; // if quantity is not set, then its atleast 1
			const regularPrice =
				parseFloat( $( this ).attr( 'data-regular-price' ) ) || 0; // should always get regular price, still a fallback is set.
			const offeredPrice = parseFloat(
				$( this ).attr( 'data-offered-price' ) ?? regularPrice // need to decide if user will get the sale price or the regular price
			);

			totalRegular += regularPrice * qty;
			totalOffer += offeredPrice * qty;
		} );
		const $totalContainer = $container
			.find( '[data-smart-tag="totalText"]' )
			.parent();
		const $oldPriceContainer = $totalContainer.find(
			'.revx-product-old-price'
		);
		const $offeredPriceContainer = $totalContainer.find(
			'.revx-product-offered-price'
		);
		setPriceText.call( $oldPriceContainer, totalRegular );
		setPriceText.call( $offeredPriceContainer, totalOffer );
	}
} );
