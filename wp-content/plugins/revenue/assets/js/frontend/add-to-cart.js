/* eslint-disable @wordpress/no-unused-vars-before-return */
/* global revenue_campaign Revenue jQuery */
// the below line ignores revenue_campaign not camel case warning
/* eslint-disable camelcase */
jQuery( function ( $ ) {
	if ( typeof revenue_campaign === 'undefined' ) {
		console.error( 'Revenue campaign script not loaded.' );
		return;
	}

	let prevButtonText = '';
	const prepareData = ( $button, index ) => {
		const campaignId = $button.data( 'campaignId' );
		const productId = $button.data( 'productId' );
		const campaignType = $button.data( 'campaign-type' );
		const qty =
			$button.data( 'quantity' ) ||
			$(
				`input[name="revx-quantity-${ campaignId }-${ productId }-${ index }"]`
			).val() ||
			1;

		const data = {
			action: 'revenue_add_to_cart',
			productId,
			campaignId,
			_wpnonce: revenue_campaign.nonce,
			quantity: qty,
			campaignSourcePage: $button.data( 'campaign_source_page' ),
			campaignType,
			index,
		};

		const typeHandlers = {
			buy_x_get_y: () => {
				data.productId = $( '.single_add_to_cart_button' ).val();
				data.bxgy_data = getBxgyData( campaignId );
				data.bxgy_trigger_data = getBxgyTriggerData( campaignId );
				data.bxgy_offer_data = getBxgyOfferData( campaignId );
			},
			volume_discount: () => {
				data.quantity = $button
					.closest( '.revx-volume-discount' )
					.find( '.revx-campaign-item[data-revx-selected=true]' )
					.data( 'quantity' );
			},
			frequently_bought_together: () => {
				data.requiredProduct = productId;
				data.fbt_data = getFbtData( campaignId );
			},
			mix_match: () => {
				data.mix_match_data = getMixMatchData( campaignId );
			},
		};

		if ( typeHandlers[ campaignType ] ) {
			typeHandlers[ campaignType ]();
		}

		if ( 'mix_match' === campaignType ) {
			const requiredProducts = JSON.parse(
				$( `input[name=revx-required-products-${ campaignId }` ).val()
			);

			// Check if each required product exists in mixMatchData
			const missingProducts = requiredProducts.filter(
				( pid ) => ! data.mix_match_data.hasOwnProperty( pid )
			);

			if ( missingProducts.length > 0 ) {
				const message =
					missingProducts.length === 1
						? revenue_campaign?.required_product_missing
						: revenue_campaign?.required_products_missing;
				const defaultMessage =
					missingProducts.length === 1
						? 'Error adding to cart, A required product is missing!'
						: 'Error adding to cart, Some required products are missing!';
				showToast( message || defaultMessage, 'error' );
				return;
			}
		} else if ( 'frequently_bought_together' === campaignType ) {
			if ( Object.keys( data.fbt_data ).length === 0 ) {
				showToast(
					revenue_campaign?.select_items_first ||
						'Please select the item(s) first',
					'error'
				);
				return;
			}
		}
		if ( ! validateData( data ) ) {
			return null;
		}

		return data;
	};

	const validateData = ( data ) => {
		return data?.productId && data?.campaignId && data?._wpnonce;
	};

	const getBxgyData = ( campaignId ) => {
		let offerData = $( `input[name=revx-offer-data-${ campaignId }]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		const bxgyData = {};

		Object.keys( jsonData ).forEach( ( pid ) => {
			const qty = $(
				`input[name=revx-quantity-${ campaignId }-${ pid }]`
			).val();
			bxgyData[ pid ] = qty;
		} );

		return bxgyData;
	};
	const getBxgyTriggerData = ( campaignId ) => {
		let offerData = $( `input[name=revx-trigger-data-${ campaignId }]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		const bxgyData = {};

		Object.keys( jsonData ).forEach( ( pid ) => {
			const qty = jsonData[ pid ].offer;
			bxgyData[ pid ] = qty[ 0 ].qty;
		} );
		return bxgyData;
	};
	const getBxgyOfferData = ( campaignId ) => {
		let offerData = $( `input[name=revx-offer-data-${ campaignId }]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		const bxgyData = {};

		Object.keys( jsonData ).forEach( ( pid ) => {
			const qty = jsonData[ pid ].offer;
			bxgyData[ pid ] = qty[ 0 ].qty;
		} );

		return bxgyData;
	};

	// Helper to parse integers safely
	function toIntOr( value, fallback ) {
		const n = parseInt( value, 10 );
		return Number.isFinite( n ) ? n : fallback;
	}
	// Initialize event handlers

	const requestsQueue = [];

	const addRequest = ( request ) => {
		requestsQueue.push( request );
		if ( requestsQueue.length === 1 ) {
			processRequests();
		}
	};

	const processRequests = () => {
		const request = requestsQueue[ 0 ];
		const originalComplete = request.complete;

		request.complete = () => {
			if ( typeof originalComplete === 'function' ) {
				originalComplete();
			}
			requestsQueue.shift();
			if ( requestsQueue.length > 0 ) {
				processRequests();
			}
		};

		$.ajax( request );
	};

	function handleAddToCart( e ) {
		e.preventDefault();

		const $button = $( this );
		prevButtonText = $button.text();
		const campaignType =
			$button.attr( 'campaign_type' ) ??
			$button.data( 'campaignType' ) ??
			'';

		// Build dynamic class for this campaign
		const dynamicClass = `.revx-${ campaignType }-add-to-cart`;

		// Get the closest container for this button only
		let $container = $button.closest( dynamicClass );

		if ( ! $container.length ) {
			$container = $button.siblings(
				`.revx-${ campaignType }-add-to-cart`
			);
		}
		// Extract basic product info
		const productId =
			$container.data( 'productId' ) ??
			$container.attr( 'data-product-id' ) ??
			'';

		// Handle variations only for the clicked product
		let variationId = null;

		const isVariable =
			$container.attr( 'product_type' ) === 'variable' ||
			$container.data( 'product-type' ) === 'variable';

		let selectedAttr = {};
		let hasMissingAttributes = false;

		if ( isVariable ) {
			selectedAttr = getSelectedAttributes( $container );
			// Sanity check: if any selected attribute value is empty, show warning and return null for this product
			const hasEmptyAttr = Object.values( selectedAttr ).some(
				( val ) =>
					val === '' || val === null || typeof val === 'undefined'
			);
			if ( hasEmptyAttr ) {
				hasMissingAttributes = true;
			}

			const matchedVariation = getMatchedVariationData( $container );

			if ( matchedVariation ) {
				variationId = matchedVariation.id || 0;
			}
		}

		if ( hasMissingAttributes ) {
			showToast(
				revenue_campaign?.select_all_attributes ||
					'Please select all required attributes',
				'error'
			);
			return;
		}

		// Get additional data
		const productIndex =
			$container.data( 'productIndex' ) ??
			$container.attr( 'data-product-index' ) ??
			'';
		const campaignId =
			$container.attr( 'campaign_id' ) ??
			$container.data( 'campaignId' ) ??
			$container.data( 'campaign-id' ) ??
			$button.data( 'campaign-id' ) ??
			'';

		const qtyRaw =
			$container.find( '.revx-product-input' ).val() ??
			$container.data( 'productQty' ) ??
			$container.data( 'quantity' ) ??
			$container.attr( 'data-product-qty' ) ??
			1;

		const quantity = toIntOr( qtyRaw, 1 );

		toggleLoading( $button, true );

		const data = {
			action: 'revenue_add_to_cart',
			productId,
			variationId,
			selectedAttr,
			campaignId,
			_wpnonce: revenue_campaign.nonce || '',
			quantity,
			campaignType,
			index: productIndex,
		};

		// Send AJAX request
		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			dataType: 'json',
			success: ( response ) =>
				handleAddToCartSuccess( response, $button, data ),
			error: () => handleError( $container ),
		} );
	}

	// button text is different for the volume discount campaign
	function setAtcButtonText() {
		const $button = $( this );
		const $container = $button.parent().siblings( '[data-product-id]' );

		const activeRadio = $container.find( '.revx-active' );

		const quantity = activeRadio.data( 'quantity' );
		const savedAmount = activeRadio.data( 'saved-amount' );
		const $buttonParent = $( this ).parent();
		const atcButtonText = $buttonParent.data( 'atc-button-text' );
		if ( atcButtonText && typeof atcButtonText === 'string' ) {
			$button.text(
				atcButtonText
					.replace( '{qty}', quantity )
					.replace(
						'{save_amount}',
						Revenue.formatPrice( savedAmount )
					)
			);
		}
	}

	function handleRadioClick() {
		const $button = $( this )
			.parent()
			.parent()
			.find( '.revx-volume_discount-btn' );

		if ( $button.length ) {
			setAtcButtonText.call( $button[ 0 ] ); // call with button as `this`
		}
	}

	function handleVolumeDiscount( e ) {
		e.preventDefault();

		const $button = $( this );
		prevButtonText = $button.text();
		const campaignType =
			$button.attr( 'campaign_type' ) ??
			$button.data( 'campaignType' ) ??
			'';

		const $container = $button.parent().siblings( '[data-product-id]' );

		const activeItem = $container.find(
			'.revx-volume-discount-item .revx-active'
		);

		const productId =
			$container.data( 'product-id' ) ??
			$container.attr( 'data-product-id' ) ??
			'';

		// Handle variations only for the clicked product
		let variationId = null;

		const isVariable =
			$container.attr( 'product_type' ) === 'variable' ||
			$container.data( 'product-type' ) === 'variable';

		let selectedAttr = {};
		let hasMissingAttributes = false;

		if ( isVariable ) {
			selectedAttr = getSelectedAttributes( activeItem );
			// Sanity check: if any selected attribute value is empty, show warning and return null for this product
			const hasEmptyAttr = Object.values( selectedAttr ).some(
				( val ) =>
					val === '' || val === null || typeof val === 'undefined'
			);
			if ( hasEmptyAttr ) {
				hasMissingAttributes = true;
			}

			const variationContainer = $container.find(
				'[data-matched-variation]'
			);

			const matchedVariation =
				getMatchedVariationData( variationContainer );

			if ( matchedVariation ) {
				variationId = matchedVariation.variation_id || 0;
			}
		}

		if ( hasMissingAttributes ) {
			showToast(
				revenue_campaign?.select_all_attributes ||
					'Please select all required attributes',
				'error'
			);
			return;
		}

		// Build products array for multiple variation support
		const products = [];
		if ( isVariable ) {
			// Find all variation attribute wrappers
			const $variationContainers = $container.find(
				'.revx-volume-attributes.revx-active'
			);

			if ( $variationContainers.length > 0 ) {
				// Find each attribute wrapper div that contains the variation selects
				$variationContainers
					.find( '[data-variation-map]' )
					.each( function () {
						const $attrWrapper = $( this );
						const variationMap = JSON.parse(
							$attrWrapper.attr( 'data-variation-map' ) || '[]'
						);

						// Get selected attributes from THIS specific wrapper
						const attrs = getSelectedAttributes( $attrWrapper );

						// Check if all attributes are selected
						const hasEmptyAttr = Object.values( attrs ).some(
							( val ) =>
								val === '' ||
								val === null ||
								typeof val === 'undefined'
						);

						if (
							! hasEmptyAttr &&
							Object.keys( attrs ).length > 0
						) {
							// Match selected attributes with variation map to get variation_id
							let matchedVariationId = 0;
							for ( let i = 0; i < variationMap.length; i++ ) {
								const variation = variationMap[ i ];
								let isMatch = true;

								// Check if all selected attributes match this variation
								for ( const attrKey in attrs ) {
									const selectedValue =
										attrs[ attrKey ].toLowerCase();
									const variationValue =
										variation.attributes &&
										variation.attributes[ attrKey ]
											? variation.attributes[
													attrKey
											  ].toLowerCase()
											: '';

									if (
										selectedValue !== variationValue &&
										variationValue !== ''
									) {
										isMatch = false;
										break;
									}
								}

								if ( isMatch ) {
									matchedVariationId =
										variation.variation_id || 0;
									break;
								}
							}

							if ( matchedVariationId > 0 ) {
								products.push( {
									product_id: productId,
									variation_id: matchedVariationId,
									selected_attributes: attrs,
									quantity: 0, // Will be set below
								} );
							}
						}
					} );
			} else if ( variationId ) {
				// Single variation selection
				products.push( {
					product_id: productId,
					variation_id: variationId,
					selected_attributes: selectedAttr,
					quantity: 0, // Will be set below
				} );
			}
		}

		// Get additional data
		const productIndex =
			$container.data( 'productIndex' ) ??
			$container.attr( 'data-product-index' ) ??
			'';
		const campaignId =
			$container.attr( 'campaign_id' ) ??
			$container.data( 'campaignId' ) ??
			$container.data( 'campaign-id' ) ??
			$button.data( 'campaign-id' ) ??
			'';

		const activeRadio = $container.find(
			'.revx-radio-wrapper.revx-active'
		);

		let qtyRaw;
		let offerIndex = 0; // default to first offer
		if ( activeRadio.length ) {
			qtyRaw = activeRadio.data( 'quantity' );
			offerIndex = activeRadio.data( 'offer-index' ) ?? 0;
		} else {
			// fallback to existing logic
			qtyRaw =
				$container.find( '.revx-product-input' ).val() ??
				$container.data( 'productQty' ) ??
				$container.data( 'quantity' ) ??
				$container.attr( 'data-product-qty' ) ??
				1;
		}
		const quantity = toIntOr( qtyRaw, 1 );

		// Update quantity in products array
		if ( products.length > 0 ) {
			const qtyPerVariation = products.length > 1 ? 1 : quantity;
			products.forEach( ( product ) => {
				product.quantity = qtyPerVariation;
			} );
		}

		toggleLoading( $button, true );

		const data = {
			action: 'revenue_add_to_cart',
			productId,
			variationId,
			selectedAttr,
			campaignId,
			_wpnonce: revenue_campaign.nonce || '',
			quantity,
			campaignType,
			index: productIndex,
			offerIndex,
		};

		// Add products array if we have variable products
		if ( products.length > 0 ) {
			data.products = products;
		}

		// Send AJAX request
		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			dataType: 'json',
			success: ( response ) =>
				handleAddToCartSuccess( response, $button, data ),
			error: () => handleError( $container ),
		} );
	}

	function handleBuyXGetY( e ) {
		e.preventDefault();

		const $button = $( this );
		prevButtonText = $button.text();
		const campaignType =
			$button.attr( 'campaign_type' ) ??
			$button.data( 'campaignType' ) ??
			'';

		// Find the main container for this campaign (up to .revx-buy-x-get-y-container)
		let $container = $button.closest( '.revx-items-wrapper' );
		if ( ! $container.length ) {
			// fallback: find parent with multiple .revx-campaign-product-card
			$container = $button
				.parents()
				.filter( function () {
					return (
						$( this ).find( '.revx-campaign-product-card' ).length >
						0
					);
				} )
				.first();
		}

		// Try to get campaignId from container, or walk up DOM if not found
		let campaignId =
			$container.data( 'campaign-id' ) ||
			$container.attr( 'data-campaign-id' ) ||
			$container.data( 'campaign_id' ) ||
			$container.attr( 'data-campaign_id' );
		if ( ! campaignId ) {
			// Try to find any parent with campaign id
			$container.parents().each( function () {
				const cid =
					$( this ).data( 'campaign-id' ) ||
					$( this ).attr( 'data-campaign-id' ) ||
					$( this ).data( 'campaign_id' ) ||
					$( this ).attr( 'data-campaign_id' );
				if ( cid ) {
					campaignId = cid;
					return false; // break
				}
			} );
		}
		campaignId = campaignId || '';
		let qtyRaw = $container.find( '.revx-product-input' ).first().val();
		if ( ! qtyRaw ) {
			qtyRaw =
				$container.data( 'productQty' ) ??
				$container.attr( 'data-product-qty' ) ??
				1;
		}

		const quantity = toIntOr( qtyRaw, 1 );
		const triggerProductId = getTriggerProductId( campaignId );

		// Build products array
		const products = [];
		let hasMissingAttributes = false;
		$container.find( '.revx-campaign-product-card' ).each( function () {
			const $prod = $( this );
			const product_id =
				$prod.data( 'product-id' ) ?? $prod.attr( 'data-product-id' );
			const isXproduct = $prod.data( 'is-x-product' ) ?? false;
			let variation_id =
				$prod.data( 'variation-id' ) ??
				$prod.attr( 'data-variation-id' ) ??
				null;
			let prodQty =
				$prod.data( 'product-qty' ) ??
				$prod.attr( 'data-product-qty' ) ??
				1;
			// Try to get input value if present
			const $input = $prod.find( '.revx-product-input' );
			if ( $input.length ) {
				const inputVal = $input.val();
				if ( inputVal ) {
					prodQty = inputVal;
				}
			}
			prodQty = toIntOr( prodQty, 1 );

			const isVariable =
				$prod.attr( 'product_type' ) === 'variable' ||
				$prod.data( 'product-type' ) === 'variable';

			let selected_attributes = {};
			if ( isVariable ) {
				selected_attributes = getSelectedAttributes( $prod );
				// Sanity check: if any selected attribute value is empty, show warning and return null for this product
				const hasEmptyAttr = Object.values( selected_attributes ).some(
					( val ) =>
						val === '' || val === null || typeof val === 'undefined'
				);
				if ( hasEmptyAttr ) {
					hasMissingAttributes = true;
					return null;
				}

				const matchedVariation = getMatchedVariationData( $prod );

				if ( matchedVariation ) {
					variation_id = matchedVariation.id || 0;
				}
			}
			// Build product object based on type
			const productObj = {
				product_id,
				quantity: prodQty,
				is_x_product: isXproduct,
			};
			if ( isVariable ) {
				productObj.variation_id = variation_id || 0;
				if ( Object.keys( selected_attributes ).length ) {
					productObj.selected_attributes = selected_attributes;
				}
			}
			products.push( productObj );
		} );

		if ( hasMissingAttributes ) {
			showToast(
				revenue_campaign?.select_all_attributes ||
					'Please select all required attributes',
				'error'
			);
			return;
		}

		const bxgy = {
			bxgy_data: getBxgyData( campaignId ),
			bxgy_trigger_data: getBxgyTriggerData( campaignId ),
			bxgy_offer_data: getBxgyOfferData( campaignId ),
		};

		toggleLoading( $button, true );

		const data = {
			action: 'revenue_add_to_cart',
			productId: triggerProductId,
			...bxgy,
			campaignId,
			_wpnonce: revenue_campaign.nonce || '',
			quantity,
			campaignType,
			products,
		};

		// Send AJAX request
		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			dataType: 'json',
			success: ( response ) =>
				handleAddToCartSuccess( response, $button, data ),
			error: () => handleError( $container ),
		} );
	}

	const getCookieData = ( cookieName ) => {
		try {
			return JSON.parse( Revenue.getCookie( cookieName ) || '{}' );
		} catch ( e ) {
			console.error(
				`Failed to parse cookie data for ${ cookieName }:`,
				e
			);
			return {};
		}
	};

	const getMixMatchData = ( campaignId ) => {
		const cookieName = `revx_mix_match_${ campaignId }`;
		let prevData = getCookieData( cookieName );

		let prevSelectedItems = $(
			`input[name=revx-selected-items-${ campaignId }]`
		).val();

		prevSelectedItems = prevSelectedItems
			? JSON.parse( prevSelectedItems )
			: {};

		if ( Object.keys( prevData ).length === 0 ) {
			prevData = prevSelectedItems;
		}

		const mixMatchData = prevData;
		const mixMatchProducts = {};
		Object.values( mixMatchData ).forEach( ( item ) => {
			mixMatchProducts[ item.id ] = item.quantity;
		} );

		return mixMatchProducts;
	};

	// Mix and match add to cart handler
	function handleMixAndMatch( e ) {
		e.preventDefault();

		const $button = $( this );
		prevButtonText = $button.text();
		const campaignId = $button.data( 'campaignId' ) || '';
		const campaignType = $button.data( 'campaignType' ) || '';
		// const $container = $button.closest( '.revx-campaign-product-card' );

		let $container = $button.closest( '.revx-items-wrapper' );
		if ( ! $container.length ) {
			// fallback: find parent with multiple .revx-campaign-product-card
			$container = $button
				.parents()
				.filter( function () {
					return (
						$( this ).find( '.revx-campaign-product-card' ).length >
						0
					);
				} )
				.first();
		}
		const qtyRaw =
			$container.find( '.revx-product-input' ).val() ??
			$container.data( 'productQty' ) ??
			$container.data( 'quantity' ) ??
			$container.attr( 'data-product-qty' ) ??
			1;

		const quantity = toIntOr( qtyRaw, 1 );

		// find all selected items in this campaign.
		const selectedItems = document.querySelectorAll(
			'.revx-selected-item:not(.revx-d-none)'
		);

		const products = Array.from( selectedItems ).map( ( item ) => {
			const productType = item.getAttribute( 'data-product-type' );
			const data = {
				quantity,
			};

			if ( productType === 'variable' ) {
				data.product_id = item.getAttribute( 'data-parent-id' );
				data.variation_id = item.getAttribute( 'data-product-id' );
				data.selected_attributes = JSON.parse(
					item.getAttribute( 'data-selected-attribute' ) || '{}'
				);
			} else {
				data.product_id = item.getAttribute( 'data-product-id' );
			}

			return data;
		} );

		toggleLoading( $button, true );

		const prevData = getMixMatchData( campaignId );

		const modifiedProducts = products.map( ( p ) => {
			const newProduct = { ...p };
			if ( p.variation_id && prevData[ p.variation_id ] ) {
				newProduct.quantity = parseInt( prevData[ p.variation_id ] );
			} else if ( prevData[ p.product_id ] ) {
				newProduct.quantity = parseInt( prevData[ p.product_id ] );
			}
			return newProduct;
		} );

		const data = {
			action: 'revenue_add_to_cart',
			_wpnonce: revenue_campaign.nonce || '',
			campaignType,
			quantity,
			campaignId,
			products: modifiedProducts,
		};
		data.mix_match_data = getMixMatchData( campaignId );

		// Send AJAX request
		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			dataType: 'json',
			success: ( response ) =>
				handleAddToCartSuccess( response, $button, data ),
			error: () => handleError( $container ),
		} );
	}

	const handleAddBundleToCart = ( e ) => {
		e.preventDefault();

		const $button = $( e.currentTarget );
		prevButtonText = $button.text();

		// Find the bundle container that wraps all product cards
		// Try .revx-items-wrapper first, fallback to closest with multiple .revx-campaign-product-card
		let $container = $button.closest( '.revx-items-wrapper' );
		if ( ! $container.length ) {
			// fallback: find parent with multiple .revx-campaign-product-card
			$container = $button
				.parents()
				.filter( function () {
					return (
						$( this ).find( '.revx-campaign-product-card' ).length >
						0
					);
				} )
				.first();
		}

		// Try to get campaignId from container, or walk up DOM if not found
		let campaignId =
			$container.data( 'campaign-id' ) ||
			$container.attr( 'data-campaign-id' ) ||
			$container.data( 'campaign_id' ) ||
			$container.attr( 'data-campaign_id' );
		if ( ! campaignId ) {
			// Try to find any parent with campaign id
			$container.parents().each( function () {
				const cid =
					$( this ).data( 'campaign-id' ) ||
					$( this ).attr( 'data-campaign-id' ) ||
					$( this ).data( 'campaign_id' ) ||
					$( this ).attr( 'data-campaign_id' );
				if ( cid ) {
					campaignId = cid;
					return false; // break
				}
			} );
		}
		campaignId = campaignId || '';
		let qtyRaw = $container.find( '.revx-product-input' ).first().val();
		if ( ! qtyRaw ) {
			qtyRaw =
				$container.data( 'productQty' ) ??
				$container.attr( 'data-product-qty' ) ??
				1;
		}

		const quantity = toIntOr( qtyRaw, 1 );
		let triggerProductId = getTriggerProductId( campaignId );

		// Build products array
		const products = [];
		let hasMissingAttributes = false;
		$container.find( '.revx-campaign-product-card' ).each( function () {
			const $prod = $( this );
			const product_id =
				$prod.data( 'product-id' ) ?? $prod.attr( 'data-product-id' );
			const is_trigger =
				$prod.data( 'is-trigger' ) ??
				$prod.attr( 'data-is-trigger' ) ??
				false;
			triggerProductId = is_trigger ? product_id : triggerProductId;
			let variation_id =
				$prod.data( 'variation-id' ) ??
				$prod.attr( 'data-variation-id' ) ??
				null;
			let prodQty =
				$prod.data( 'product-qty' ) ??
				$prod.attr( 'data-product-qty' ) ??
				1;
			// Try to get input value if present
			const $input = $prod.find( '.revx-product-input' );
			if ( $input.length ) {
				const inputVal = $input.val();
				if ( inputVal ) {
					prodQty = inputVal;
				}
			}
			prodQty = toIntOr( prodQty, 1 );

			const isVariable =
				$prod.attr( 'product_type' ) === 'variable' ||
				$prod.data( 'product-type' ) === 'variable';
			let selected_attributes = {};

			if ( isVariable ) {
				selected_attributes = getSelectedAttributes( $prod );
				// Sanity check: if any selected attribute value is empty, show warning and return null for this product
				const hasEmptyAttr = Object.values( selected_attributes ).some(
					( val ) =>
						val === '' || val === null || typeof val === 'undefined'
				);
				if ( hasEmptyAttr ) {
					hasMissingAttributes = true;
					return null;
				}

				const matchedVariation = getMatchedVariationData( $prod );

				if ( matchedVariation ) {
					variation_id = matchedVariation.id || 0;
				}
			}
			// Build product object based on type
			const productObj = {
				product_id,
				quantity: prodQty,
				is_trigger,
			};
			if ( isVariable ) {
				productObj.variation_id = variation_id || 0;
				if ( Object.keys( selected_attributes ).length ) {
					productObj.selected_attributes = selected_attributes;
				}
			}
			products.push( productObj );
		} );

		if ( hasMissingAttributes ) {
			showToast(
				revenue_campaign?.select_all_attributes ||
					'Please select all required attributes',
				'error'
			);
			return;
		}

		const data = {
			action: 'revenue_add_bundle_to_cart',
			campaignId,
			_wpnonce: revenue_campaign.nonce,
			trigger_product_id: triggerProductId,
			quantity,
			campaignType: 'bundle_discount',
			products,
		};

		toggleLoading( $button, true );
		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			success: ( response ) =>
				handleBundleSuccess( response, $button, data ),
			error: () => handleError( $button ),
			dataType: 'json',
		} );
	};

	const handleBundleSuccess = ( response, $button, data ) => {
		const campaignId = $button.data( 'campaignId' );
		$( `.revx-campaign-${ campaignId }` ).trigger(
			'revx_added_to_cart',
			data
		);

		$( document ).trigger( 'revenue:add_bundle_to_cart', {
			campaignId,
			response,
		} );

		$( document.body ).trigger( 'added_to_cart', [
			response?.data?.fragments,
			response?.data?.cart_hash,
			$button,
		] );

		toggleLoading(
			$button,
			false,
			revenue_campaign?.added_to_cart || 'Added to Cart'
		);
		showToast( revenue_campaign?.added_to_cart || 'Added to cart' );

		// true if any of the class exist.
		if ( $button.is( '.revx-builder-atc-skip, .revx-skip-add-to-cart' ) ) {
			// Remove revx-loading class and update button text to 'Added to Cart'
			window.location.assign( revenue_campaign.checkout_page_url );
		}

		if ( response?.data?.is_reload ) {
			location.reload();
		}
	};

	const getTriggerProductId = ( campaignId ) => {
		// find trigger product id from our container or get the one from the site add to cart button
		return (
			$(
				`input[name="revx-trigger-product-id-${ campaignId }"]`
			).val() || $( '.single_add_to_cart_button' ).val()
		);
	};

	const handleError = ( $button ) => {
		toggleLoading( $button, false );
		console.error( 'Error adding to cart' );
		showToast(
			revenue_campaign?.error_adding_to_cart || 'Error adding to cart',
			'error'
		);
	};

	const handleAddToCartSuccess = ( response, $button, data ) => {
		const campaignId = $button.data( 'campaign_id' );
		$( `.revx-campaign-${ campaignId }` ).trigger(
			'revx-add-to-cart-btn',
			data
		);

		$( document ).trigger( 'revenue:add_to_cart', {
			campaignId,
			response,
		} );

		if ( response?.data?.on_cart_action === 'hide_products' ) {
			hideProduct( $button.attr( 'data-product-id' ), campaignId );
		}

		toggleLoading(
			$button,
			false,
			revenue_campaign?.added_to_cart || 'Added to Cart'
		);
		showToast( revenue_campaign?.added_to_cart || 'Added to cart' );

		$( document.body ).trigger( 'added_to_cart', [
			response?.data?.fragments,
			response?.data?.cart_hash,
			$button,
		] );

		if ( $button.hasClass( 'revx-skip-add-to-cart' ) ) {
			// Remove revx-loading class and update button text to 'Added to Cart'
			window.location.assign( revenue_campaign.checkout_page_url );
		}
		if ( response?.data?.is_reload ) {
			location.reload();
		}
	};

	const hideProduct = ( productId, campaignId ) => {
		const target = $(
			`#revenue-campaign-item-${ productId }-${ campaignId }`
		);
		target.hide( 'slow', () => {
			target.remove();
		} );
	};

	const toggleLoading = (
		$button,
		isLoading,
		text = revenue_campaign?.adding || 'Adding...'
	) => {
		$button
			.toggleClass( 'revx-loading', isLoading )
			.text( isLoading ? text : prevButtonText || 'Add to Cart' );
	};

	function showToast( message, type = 'success', duration = 3000 ) {
		// Check if toast container exists, otherwise create it
		let $toastContainer = $( '.revx-toaster-container' );
		if ( $toastContainer.length === 0 ) {
			$toastContainer = $( '<div class="revx-toaster-container"></div>' );
			$( 'body' ).append( $toastContainer );
		}

		// Determine toast class and icon based on type
		const toastClasses = {
			success: 'revx-toaster__success',
			error: 'revx-toaster__error',
		};

		const icons = {
			success: `
				<svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" fill="none" viewBox="0 0 16 16" class="revx-toaster__close-icon revx-toaster__icon">
					<path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2" d="m12 4-8 8M4 4l8 8"></path>
				</svg>
			`,
			error: `
				<svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" fill="none" viewBox="0 0 16 16" class="revx-toaster__close-icon revx-toaster__icon">
					<path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2" d="m12 4-8 8M4 4l8 8"></path>
				</svg>
			`,
		};

		// Create a new toast element as a jQuery object
		const $toast = $( `
			<div class="revx-toaster revx-justify-space revx-toaster-lg ${ toastClasses[ type ] }" style="display: flex;">
				<div class="revx-paragraph--xs revx-align-center-xs">
					${ message }
				</div>
				<div class="revx-paragraph--xs revx-align-center">
					${ icons[ type ] }
				</div>
			</div>
		` );

		// Add close button functionality
		$toast.find( '.revx-toaster__close-icon' ).on( 'click', function () {
			$toast.fadeOut( 400, function () {
				$( this ).remove(); // Remove the toast from DOM
			} );
		} );

		// Append the toast to the toast container
		$toastContainer.append( $toast );

		// Show the toast
		$toast.fadeIn( 400 );

		// Set timeout to hide the toast after the specified duration
		setTimeout( function () {
			if ( $toast.is( ':visible' ) ) {
				// Only remove if still visible
				$toast.fadeOut( 400, function () {
					$( this ).remove(); // Remove the toast from DOM
				} );
			}
		}, duration );
	}

	function clearData( e, data ) {
		const campaignId = data.campaignId;
		const campaignType = data.campaignType;

		switch ( campaignType ) {
			case 'mix_match':
				Revenue.setCookie( `mix_match_${ campaignId }`, '', -1 );
				$( `input[name=revx-selected-items-${ campaignId }]` ).val(
					''
				);
				Revenue.updateMixMatchHeaderAndPrices( campaignId, '' );
				$( `.revx-campaign-${ campaignId }` )
					.find( '.revx-selected-item' )
					.each( function () {
						if ( ! $( this ).hasClass( 'revx-d-none' ) ) {
							$( this ).remove();
						}
					} );

				$(
					`.revx-campaign-${ campaignId } .revx-empty-selected-products`
				).removeClass( 'revx-d-none' );
				$(
					`.revx-campaign-${ campaignId } .revx-selected-product-container`
				).addClass( 'revx-empty-selected-items' );
				$(
					`.revx-campaign-${ campaignId } .revx-empty-mix-match`
				).removeClass( 'revx-d-none' );
				break;
			// case 'frequently_bought_together': {
			// 	let hasRequired = false;

			// 	const parent = $( this ).find( '.revx-campaign-container' );

			// 	const productId = $(
			// 		`button.revx-campaign-add-to-cart-btn[data-campaign-id="${ campaignId }"]`
			// 	).data( 'product-id' );

			// 	$( `.revx-campaign-${ campaignId }` )
			// 		.find( '.revx-builder-checkbox' )
			// 		.each( function () {
			// 			if (
			// 				! $( this )
			// 					.parent()
			// 					.hasClass( 'revx-item-required' )
			// 			) {
			// 				Revenue.updateStyles( $( this ), false );
			// 			} else {
			// 				hasRequired = true;
			// 			}
			// 		} );

			// 	if ( hasRequired ) {
			// 		$(
			// 			`input[name=revx-fbt-selected-items-${ campaignId }]`
			// 		).val( JSON.stringify( { [ productId ]: 1 } ) );
			// 		Revenue.setCookie(
			// 			`campaign_${ campaignId }`,
			// 			JSON.stringify( { [ productId ]: 1 } ),
			// 			1
			// 		);
			// 	} else {
			// 		$(
			// 			`input[name=revx-fbt-selected-items-${ campaignId }]`
			// 		).val( JSON.stringify( {} ) );
			// 		Revenue.setCookie(
			// 			`campaign_${ campaignId }`,
			// 			JSON.stringify( {} ),
			// 			1
			// 		);
			// 	}

			// 	Revenue.fbtCalculation( parent, campaignId );

			// 	break;
			// }
			default:
				break;
		}

		$( `.revx-campaign-view-${ campaignId }.revx-floating-main` ).hide();
		$( `.revx-campaign-view-${ campaignId }.revx-popup` ).hide();
		$( `.revx-campaign-${ campaignId }.revx-volume-discount` ).hide();
		$( `.revx-campaign-${ campaignId }.revx-bundle-discount` ).hide();
		$( `.revx-campaign-${ campaignId }.revx-mix-match` ).hide();
		$(
			`.revx-campaign-${ campaignId }.revx-frequently-bought-together`
		).hide();
		$( `.revx-campaign-${ campaignId }.revx-buyx-gety` ).hide();
	}

	function handleFrequentlyBoughtTogetherAddToCart( e ) {
		e.preventDefault();
		const $button = $( this );
		const campaignType =
			$button.attr( 'campaign_type' ) ??
			$button.data( 'campaignType' ) ??
			'';

		const campaignId = $button.data( 'campaign-id' ) ?? '';
		const dynamicClass = `revx-${ campaignType }-add-to-cart`;

		const $selectedProducts = $button
			.closest( '.revx-campaign-wrapper' )
			.find( `.${ dynamicClass }` )
			.filter( function () {
				return $( this )
					.find( '.revx-checkbox-container' )
					.hasClass( 'revx-active' );
			} );
		let hasEmptyAttributes = false;
		// create array of the required products to pass to the server.
		const requiredProducts = [];

		const productsData = $selectedProducts
			.map( function () {
				const $product = $( this );
				const productType = $product.attr( 'product_type' );
				const isChecked = $product
					.find( '.revx-checkbox-container' )
					.hasClass( 'revx-active' );
				const isRequired = $product
					.find( '.revx-checkbox-wrapper' )
					.hasClass( 'revx-required-product' );

				if ( isRequired ) {
					requiredProducts.push( $product.data( 'product-id' ) );
				}

				let variation_id = $product.data( 'variation-id' ) || 0;

				// Always get live selected attributes from selects
				let selectedAttr = {};
				if ( isChecked && productType === 'variable' ) {
					selectedAttr = getSelectedAttributes( $product );
					// Sanity check: if any selected attribute value is empty, show warning and return null for this product
					const hasEmptyAttr = Object.values( selectedAttr ).some(
						( val ) =>
							val === '' ||
							val === null ||
							typeof val === 'undefined'
					);
					if ( hasEmptyAttr ) {
						hasEmptyAttributes = true;
						return null;
					}

					const matchedVariation =
						getMatchedVariationData( $product );

					if ( matchedVariation ) {
						variation_id = matchedVariation.id || 0;
					}
				}

				return {
					productId: $product.data( 'product-id' ),
					productIndex: $product.data( 'product-index' ),
					selectedAttr,
					campaignId,
					campaignType,
					productType,
					quantity: parseInt( $product.data( 'product-qty' ) ) || 1,
					isRequired,
					isChecked,
					variation_id: productType === 'variable' ? variation_id : 0,
				};
			} )
			.get()
			.filter( Boolean );

		if ( hasEmptyAttributes ) {
			showToast(
				revenue_campaign?.select_all_attributes ||
					'Please select all required attributes',
				'error'
			);
			return;
		}

		if ( productsData.length === 0 ) {
			showToast(
				revenue_campaign?.select_at_least_one_product ||
					'Please select at least one product to add',
				'error'
			);
			return;
		}

		const products = productsData?.map( ( item ) => {
			if ( item.productType === 'variable' ) {
				return {
					product_id: item.productId,
					variation_id: item.variation_id,
					selected_attributes: item.selectedAttr,
					quantity: item.quantity,
				};
			}
			return {
				product_id: item.productId,
				quantity: item.quantity,
			};
		} );

		const fbtData = productsData.reduce( ( acc, product ) => {
			acc[ product.productId ] = product.quantity;
			return acc;
		}, {} );

		const data = {
			action: 'revenue_add_to_cart',
			productId: productsData[ 0 ]?.productId || 0,
			campaignId,
			campaignType,
			quantity: 1,
			requiredProducts,
			_wpnonce: revenue_campaign.nonce,
			fbt_data: fbtData,
			products,
		};

		toggleLoading( $button, true );

		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			dataType: 'json',
			success: ( response ) =>
				handleAddToCartSuccess( response, $button, data ),
			error: () => handleError( $button ),
		} );
	}

	function handleFreeShippingBarUpsellAddToCart( e ) {
		e.preventDefault();
		const $button = $( this );
		prevButtonText = $button.text();
		const campaignId = $button.data( 'campaign-id' ) ?? '';

		// Find the closest parent with data-product-id (the top-level container)
		const $container = $button.closest( '.revx-campaign-product-card' );
		const productId = $container.data( 'product-id' ) ?? '';
		const qtyRaw =
			$container.find( '.revx-product-input' ).val() ??
			$container.data( 'productQty' ) ??
			$container.data( 'quantity' ) ??
			$container.attr( 'data-product-qty' ) ??
			1;

		const quantity = toIntOr( qtyRaw, 1 );
		const campaignType =
			$container.data( 'campaign-type' ) ?? 'free_shipping_bar';

		const productType = $container.attr( 'product_type' );

		// Placeholder for variationId. may be use in the future.
		let variationId = $container.data( 'variation-id' ) || 0;

		let selectedAttr = {};
		if ( productType === 'variable' ) {
			selectedAttr = getSelectedAttributes( $container );
			// Sanity check: if any selected attribute value is empty, show warning and return null for this product
			const hasEmptyAttr = Object.values( selectedAttr ).some(
				( val ) =>
					val === '' || val === null || typeof val === 'undefined'
			);
			if ( hasEmptyAttr ) {
				showToast(
					revenue_campaign?.select_all_attributes ||
						'Please select all required attributes',
					'error'
				);
				return;
			}

			const matchedVariation = getMatchedVariationData( $container );

			if ( matchedVariation ) {
				variationId = matchedVariation.id || 0;
			}
		}

		const data = {
			action: 'revenue_add_to_cart',
			productId,
			variationId,
			selectedAttr,
			campaignId,
			_wpnonce: revenue_campaign.nonce || '',
			quantity,
			campaignType,
		};

		toggleLoading( $button, true );

		// Send AJAX request
		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			dataType: 'json',
			success: ( response ) =>
				handleAddToCartSuccess( response, $button, data ),
			error: () => {
				toggleLoading( $button, false ); // Ensure loading state is reset on error
				handleError( $container );
			},
			complete: () => toggleLoading( $button, false ), // Reset loading state after completion
		} );
	}

	function handleSpendingGoalUpsellAddToCart( e ) {
		e.preventDefault();
		const $button = $( this );
		prevButtonText = $button.text();
		const campaignId = $button.data( 'campaign-id' ) ?? '';

		// Find the closest parent with data-product-id (the top-level container)
		const $container = $button.closest( '.revx-campaign-product-card' );
		const productId = $container.data( 'product-id' ) ?? '';
		const productType = $container.attr( 'product_type' );

		const qtyRaw =
			$container.find( '.revx-product-input' ).val() ??
			$container.data( 'productQty' ) ??
			$container.data( 'quantity' ) ??
			$container.attr( 'data-product-qty' ) ??
			1;

		const quantity = toIntOr( qtyRaw, 1 );

		const campaignType =
			$container.data( 'campaign-type' ) ?? 'spending_goal';

		let variationId = $container.data( 'variation-id' ) || 0;

		let selectedAttr = {};
		if ( productType === 'variable' ) {
			selectedAttr = getSelectedAttributes( $container );
			// Sanity check: if any selected attribute value is empty, show warning and return null for this product
			const hasEmptyAttr = Object.values( selectedAttr ).some(
				( val ) =>
					val === '' || val === null || typeof val === 'undefined'
			);
			if ( hasEmptyAttr ) {
				showToast(
					revenue_campaign?.select_all_attributes ||
						'Please select all required attributes',
					'error'
				);
				return;
			}

			const matchedVariation = getMatchedVariationData( $container );

			if ( matchedVariation ) {
				variationId = matchedVariation.id || 0;
			}
		}

		const data = {
			action: 'revenue_add_to_cart',
			productId,
			variationId,
			selectedAttr,
			campaignId,
			_wpnonce: revenue_campaign.nonce || '',
			quantity,
			campaignType,
		};
		toggleLoading( $button, true );

		// Send AJAX request
		addRequest( {
			type: 'POST',
			url: revenue_campaign.ajax,
			data,
			dataType: 'json',
			success: ( response ) =>
				handleAddToCartSuccess( response, $button, data ),
			error: () => {
				toggleLoading( $button, false ); // Ensure loading state is reset on error
				handleError( $container );
			},
			complete: () => toggleLoading( $button, false ), // Reset loading state after completion
		} );
	}

	// Bind click handler
	const campaigns = [ 'normal_discount' ];
	campaigns.forEach( ( campaignType ) => {
		const selector = `.revx-${ campaignType }-btn`;
		$( document ).on( 'click', selector, handleAddToCart );
	} );

	// volume discount handler
	$( document ).on(
		'click',
		'.revx-volume_discount-btn',
		handleVolumeDiscount
	);
	$( '.revx-volume_discount-btn' ).each( setAtcButtonText );
	$( document ).on( 'click', '.revx-volume-discount-item', handleRadioClick );

	// buy x get y handler
	$( document ).on( 'click', '.revx-buy_x_get_y-btn', handleBuyXGetY );

	// Mix and Match handler
	$( document ).on( 'click', '.revx-mix_match-btn', handleMixAndMatch );

	// frequently bought together add to cart button handler
	$( document ).on(
		'click',
		'.revx-frequently_bought_together-btn',
		handleFrequentlyBoughtTogetherAddToCart
	);

	// Free Shipping Bar handler
	$( document ).on(
		'click',
		'.revx-free_shipping_bar-btn',
		handleFreeShippingBarUpsellAddToCart
	);

	// Free Shipping Bar handler
	$( document ).on(
		'click',
		'.revx-spending_goal-btn',
		handleSpendingGoalUpsellAddToCart
	);

	const initEventHandlers = () => {
		$( document.body )
			.find( '.revx-campaign-add-to-cart-btn:not(.revx-prevent-event)' )
			.end()
			.on(
				'click',
				'.revx-bundle_discount-btn:not(.revx-prevent-event)',
				handleAddBundleToCart
			)
			.on( 'revx_added_to_cart', clearData );
	};

	initEventHandlers();

	// Added Dynamic way to display updated quantity number
	// Handle Plus button
	$( document ).on( 'click', '.revx-quantity-plus', function () {
		const $input = $( this ).siblings( '.revx-product-input' );
		const qty = parseInt( $input.val(), 10 ) || 0;
		$input.val( qty + 1 ).trigger( 'input' ); // trigger input event to update multiplier
	} );

	// Handle minus button
	$( document ).on( 'click', '.revx-quantity-minus', function () {
		const $input = $( this ).siblings( '.revx-product-input' );
		const qty = parseInt( $input.val(), 10 ) || 0;
		if ( qty >= parseInt( $input.attr( 'min' ), 10 ) ) {
			$input.val( qty - 1 ).trigger( 'input' ); // trigger input event to update multiplier
		}
	} );

	// Update multiplier on manual input
	$( document ).on( 'input', '.revx-product-input', function () {
		const $input = $( this );
		const qty = parseInt( $input.val(), 10 ) || 0;
		const productId = $input.data( 'product-id' );
		const $multiplier = $( '.revx-quantity-multiplier-' + productId );
		if ( $multiplier.length ) {
			$multiplier.text( '(x' + qty + ')' );
		}
	} );

	// Handle quantity change for all campaigns
	function handleQuantityChange() {
		const $input = $( this );
		// find the wrapper of the product/offer
		const $parentContainer = $input.closest(
			'[data-product-id][campaign_type]'
		);
		const newQuantity = parseInt( $input.val() ) || 1; // Fallback to 1 if invalid

		// Update the parent container's data-product-qty attribute
		$parentContainer.data( 'product-qty', newQuantity );
		$parentContainer.attr( 'data-product-qty', newQuantity );
	}

	$( document ).on(
		'change input',
		'.revx-product-input',
		handleQuantityChange
	);

	// Get selected attributes for a product card (works for campaign product cards)
	function getSelectedAttributes( $container ) {
		const selectedData = {};
		$container.find( 'select[name^="attribute_"]' ).each( function () {
			const key = $( this ).attr( 'name' );
			selectedData[ key ] = $( this ).val() || '';
		} );
		return selectedData;
	}

	function getMatchedVariationData( $product ) {
		const matchedVariation = JSON.parse(
			$product.attr( 'data-matched-variation' ) || '{}'
		);

		return matchedVariation || {};
	}

	// Update data-selected-value when any attribute dropdown changes
	$( '.product' ).on( 'change', 'select[name^="attribute_"]', function () {
		const $form = $( this ).closest( '.product' );
		const selectedData = getSelectedAttributes( $form );
		const matchedVariation = getMatchedVariationData( $form );
		// Update the data-selected-value attribute
		$form.attr( 'data-selected-value', JSON.stringify( selectedData ) );
		$form.attr(
			'data-matched-variation',
			JSON.stringify( matchedVariation )
		);
	} );
} );
