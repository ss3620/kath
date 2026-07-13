/* eslint-disable @wordpress/no-unused-vars-before-return */
( function ( $ ) {
	( 'use strict' );

	const makeTemplatesVisible = () => {
		const templates = document.querySelectorAll( '.revx-template' );
		templates.forEach( ( template ) => {
			template.style.visibility = 'visible';
			template.style.opacity = '1';
		} );
	};

	// Run on first load
	makeTemplatesVisible();

	// add events that does partial refresh of the DOM instead of full reload, to make the templates visible again
	// Listen for cart / checkout partial refreshes
	$( document.body ).on(
		'updated_checkout updated_cart_totals woocommerce_update_order_review',
		makeTemplatesVisible
	);
	// Listen for mini-cart updates
	$( document.body ).on(
		'wc_fragments_loaded wc_fragments_refreshed',
		makeTemplatesVisible
	);

	// -----------------
	// Plugins
	$.fn.revxSlider = function () {
		return this.each( function () {
			const $container = $( this );
			const $sliderIcons = $container.find( '.revx-slider-icons' );
			const $items = $container.find( '.revx-grid-item' );
			const itemCount = $items.length;

			let currentPosition = 0;

			function getBreakpoint( containerWidth ) {
				if ( containerWidth <= 300 ) {
					return 'sm';
				}
				if ( containerWidth <= 500 ) {
					return 'md';
				}
				return 'lg';
			}

			function getVisibleItems() {
				const containerWidth = $container.width();
				const layout = $container.data( 'layout' ) || 'inpage';
				const sliderColumns = $container.data( 'slider-columns' ) || {};
				const breakpoint = getBreakpoint( containerWidth );

				if (
					sliderColumns[ layout ] &&
					sliderColumns[ layout ][ breakpoint ]
				) {
					return parseInt(
						sliderColumns[ layout ][ breakpoint ],
						10
					);
				}

				return 1;
			}

			function getMaxStartIndex() {
				return itemCount - getVisibleItems();
			}

			function updateSliderPosition() {
				$container.css(
					'--revx-slider-translate',
					`-${ currentPosition * 100 }%`
				);
			}

			function handleNext() {
				const maxStartIndex = getMaxStartIndex();
				currentPosition =
					currentPosition >= maxStartIndex ? 0 : currentPosition + 1;
				updateSliderPosition();
			}

			function handlePrev() {
				const maxStartIndex = getMaxStartIndex();
				currentPosition =
					currentPosition <= 0 ? maxStartIndex : currentPosition - 1;
				updateSliderPosition();
			}

			function updateSliderVisibility() {
				const visibleItems = getVisibleItems();
				const isSliderNeeded = itemCount > visibleItems;

				$container.toggleClass( 'has-slider', isSliderNeeded );

				return isSliderNeeded;
			}

			function initializeSlider() {
				const visibleItems = getVisibleItems();
				const maxStartIndex = itemCount - visibleItems;

				if ( currentPosition > maxStartIndex ) {
					currentPosition = Math.max( 0, maxStartIndex );
					updateSliderPosition();
				}

				updateSliderVisibility();
			}

			// Attach events
			$sliderIcons
				.find( '.next' )
				.off( 'click' )
				.on( 'click', handleNext );
			$sliderIcons
				.find( '.prev' )
				.off( 'click' )
				.on( 'click', handlePrev );

			// Resize event
			$( window ).on( 'resize', initializeSlider );

			// Resize observer for wrapper
			if ( typeof ResizeObserver !== 'undefined' ) {
				const resizeObserver = new ResizeObserver( initializeSlider );
				const wrapper = $container
					.find( '.revx-items-wrapper' )
					.get( 0 );
				if ( wrapper ) {
					resizeObserver.observe( wrapper );
				}
			}

			// Initial setup
			initializeSlider();
		} );
	};

	$.fn.revxCountdownTimer = function ( options ) {
		const settings = $.extend(
			{
				endTimeAttr: 'data-end-time',
				startTimeAttr: 'data-start-time',
				formatTimeUnit( unit ) {
					return unit < 10 ? '0' + unit : unit;
				},
				onEnd( $el ) {
					$el.html( '<span>00</span><span>:</span><span>00</span>' );
				},
				onBeforeStart( $el ) {
					$el.html( '<span>00</span><span>:</span><span>00</span>' ); // You can change to "Coming Soon..." etc
				},
			},
			options
		);

		function initTimer( $timer ) {
			if ( $timer.data( 'revxCountdownInitialized' ) ) {
				return;
			}
			$timer.data( 'revxCountdownInitialized', true );

			const endTime = parseInt( $timer.attr( settings.endTimeAttr ), 10 );
			const startTime =
				parseInt( $timer.attr( settings.startTimeAttr ), 10 ) || null;

			if ( isNaN( endTime ) ) {
				settings.onEnd( $timer );
				return;
			}

			function update() {
				const now = Date.now();

				if ( startTime && now < startTime ) {
					settings.onBeforeStart( $timer );
					return;
				}

				const diff = endTime - now;

				if ( diff <= 0 ) {
					clearInterval( interval );
					settings.onEnd( $timer );
					return;
				}

				const days = Math.floor( diff / ( 1000 * 60 * 60 * 24 ) );
				const hours = Math.floor( ( diff / ( 1000 * 60 * 60 ) ) % 24 );
				const minutes = Math.floor( ( diff / ( 1000 * 60 ) ) % 60 );
				const seconds = Math.floor( ( diff / 1000 ) % 60 );

				let html = '';

				if ( days > 0 ) {
					html +=
						'<span>' +
						settings.formatTimeUnit( days ) +
						'</span><span>:</span>';
				}
				if ( days > 0 || hours > 0 ) {
					html +=
						'<span>' +
						settings.formatTimeUnit( hours ) +
						'</span><span>:</span>';
				}
				html +=
					'<span>' +
					settings.formatTimeUnit( minutes ) +
					'</span><span>:</span>';
				html +=
					'<span>' + settings.formatTimeUnit( seconds ) + '</span>';

				$timer.html( html );
			}

			update();
			var interval = setInterval( update, 1000 );
			$timer.data( 'revxCountdownInterval', interval );
		}

		// Initialize on each element
		this.each( function () {
			const $this = $( this );
			initTimer( $this );
		} );

		return this;
	};

	function calculateCurrentPrice( offerData, productId, quantity ) {
		const product = offerData[ productId ];

		if ( ! product ) {
			throw new Error( 'Product not found' );
		}

		const regularPrice = parseFloat( product.regular_price );
		let currentPrice = regularPrice;

		if ( product.offer && Array.isArray( product.offer ) ) {
			for ( let i = 0; i < product.offer.length; i++ ) {
				const offer = product.offer[ i ];
				const minQty = parseInt( offer.qty, 10 );

				if ( offer.type == 'free' ) {
					if ( quantity >= minQty ) {
						switch ( offer.type ) {
							case 'free':
								currentPrice = 0;

								break;

							default:
								break;
						}
						// Add more offer types if needed (e.g., fixed discount)
					}
				} else if ( quantity >= minQty ) {
					switch ( offer.type ) {
						case 'percentage':
							currentPrice =
								regularPrice -
								( parseFloat( offer.value ) / 100 ) *
									regularPrice;
							break;
						case 'amount':
						case 'fixed_discount':
							currentPrice =
								regularPrice - parseFloat( offer.value );

							break;
						case 'fixed_price':
							currentPrice = parseFloat( offer.value );

							break;
						case 'no_discount':
							currentPrice = regularPrice;

							break;
						case 'free':
							currentPrice = 0;

							break;

						default:
							break;
					}
					// Add more offer types if needed (e.g., fixed discount)
				}
			}
		}

		return parseFloat( currentPrice * quantity );
	}

	const formatPrice = ( price ) => {
		const currencyFormat = revenue_campaign?.currency_format;
		const currencySymbol = revenue_campaign?.currency_format_symbol;
		const decimalSeparator = revenue_campaign?.currency_format_decimal_sep;
		const thousandSeparator =
			revenue_campaign?.currency_format_thousand_sep;
		const numDecimals = revenue_campaign?.currency_format_num_decimals;

		const fixedPrice = parseFloat( price ).toFixed( numDecimals || 2 );

		const parts = fixedPrice.split( '.' );
		let integerPart = parts[ 0 ];
		let decimalSepAndNum = '';
		if ( numDecimals > 0 ) {
			decimalSepAndNum = decimalSeparator + ( parts[ 1 ] || '00' );
		}

		integerPart = integerPart.replace(
			/\B(?=(\d{3})+(?!\d))/g,
			thousandSeparator
		);

		const formattedPrice = integerPart + decimalSepAndNum;

		return currencyFormat
			.replace( '%1$s', currencySymbol )
			.replace( '%2$s', formattedPrice );
	};

	// Volume Discount
	$( '.revx-volume-discount .revx-campaign-item' ).on(
		'click',
		function ( e ) {
			// Remove selected style from all items
			e.stopPropagation();
			const that = $( this );
			$( '.revx-volume-discount .revx-campaign-item' ).each( function () {
				const item = $( this ).find( '.revx-volume-discount__tag' );
				const defaultStyle = item.data( 'default-style' );
				$( item ).attr( 'style', defaultStyle );
				$( this ).attr( 'data-revx-selected', false );
			} );
			const clickedItem = that.find( '.revx-volume-discount__tag' );

			// Apply selected style to the clicked item
			const selectedStyle = clickedItem.data( 'selected-style' );
			clickedItem.attr( 'style', selectedStyle );
			that.attr( 'data-revx-selected', true );

			$( '.revx-ticket-type' ).trigger( 'change' );
		}
	);

	$( 'select.revx-productAttr-wrapper__field' ).on( 'change', function () {
		const attributeData =
			$( '.variations_form' ).data( 'product_variations' );

		const attributeName = $( this ).data( 'attribute_name' );

		const parentWrapper = $( this ).closest( '.revx-productAttr-wrapper' );
		const fieldsInParent = parentWrapper.find(
			'.revx-productAttr-wrapper__field'
		);

		let allFieldsHaveValue = true;
		const values = {};
		fieldsInParent.each( function () {
			if ( $( this ).val() === '' ) {
				allFieldsHaveValue = false;
				return false; // Break the loop
			}
			values[ $( this ).data( 'attribute_name' ) ] = $( this ).val();
		} );

		let selectedVariation = false;

		if ( allFieldsHaveValue ) {
			attributeData.forEach( ( element ) => {
				if (
					JSON.stringify( element.attributes ) ==
					JSON.stringify( values )
				) {
					selectedVariation = element;
				}
			} );
		}

		$( '.revx-campaign-item' ).removeAttr( 'data-product-id' );
		if ( selectedVariation ) {
			const parent = $( this ).closest( '.revx-campaign-item' );
			const campaignID = parent.data( 'campaignId' );
			const quantity = parent.data( 'quantity' );

			const offerData = JSON.parse(
				$( `input[name="revx-offer-data-${ campaignID }"]` ).val()
			);

			const variation_id = selectedVariation.variation_id;

			const offer = offerData[ variation_id ];

			let nearestOffer = offer.offer[ 0 ];
			for ( let i = 1; i < offer.offer.length; i++ ) {
				if ( offer.offer[ i ].qty <= quantity ) {
					nearestOffer = offer.offer[ i ];
				} else {
					break;
				}
			}

			let regular_price = offer.regular_price;
			let sale_price = '';

			switch ( nearestOffer.type ) {
				case 'percentage':
					sale_price =
						parseFloat( offer.regular_price * nearestOffer.qty ) *
						( 1 - nearestOffer.value / 100 );

					break;
				case 'amount':
				case 'fixed_discount':
					sale_price =
						Math.max(
							0,
							parseFloat( offer.regular_price ) -
								parseFloat( nearestOffer.value )
						) * nearestOffer.qty;

					break;
				case 'fixed_price':
					sale_price =
						parseFloat( nearestOffer.value ) * nearestOffer.qty;
					regular_price = false;

					break;
				case 'no_discount':
					sale_price =
						parseFloat( offer.regular_price ) * nearestOffer.qty;
					regular_price = false;

					break;
				case 'free':
					sale_price = 0;

					break;

				default:
					break;
			}

			parent.attr( 'data-product-id', variation_id );

			parent
				.parent()
				.parent()
				.find( '.revx-campaign-add-to-cart-btn' )
				.attr( 'data-product-id', variation_id );

			parent.find( 'input[data-name=revx_quantity]' ).trigger( 'change' );

			parent
				.find( '.revx-campaign-item__regular-price' )
				.html( formatPrice( regular_price * quantity ) );
			parent
				.find( '.revx-campaign-item__sale-price' )
				.html( formatPrice( sale_price ) );
		} else {
			const parent = $( this ).closest( '.revx-campaign-item' );
			parent.find( '.revx-campaign-item__regular-price' ).html( '' );
			parent.find( '.revx-campaign-item__sale-price' ).html( '' );
		}
	} );

	function updatePriceDisplay( parent, quantity, salePrice, regularPrice ) {
		salePrice = parseFloat( salePrice );
		regularPrice = parseFloat( regularPrice );
		const salePriceElement = $( parent ).find(
			'.revx-campaign-item__sale-price'
		);
		const regularPriceElement = $( parent ).find(
			'.revx-campaign-item__regular-price'
		);
		const savingsTag = $( parent ).find( '.revx-builder-savings-tag' );

		if ( quantity == 0 ) {
			salePriceElement.html( formatPrice( 0 ) );
			savingsTag.hide();
			return;
		}

		if ( salePrice !== regularPrice ) {
			salePriceElement.html(
				quantity > 1
					? `${ quantity } x ` + formatPrice( salePrice )
					: formatPrice( salePrice )
			);
			regularPriceElement.html(
				quantity > 1
					? `${ quantity } x ` + formatPrice( regularPrice )
					: formatPrice( regularPrice )
			);
			savingsTag.show();
		} else {
			salePriceElement.html(
				quantity > 1
					? `${ quantity } x ` + formatPrice( regularPrice )
					: formatPrice( regularPrice )
			);
			regularPriceElement.empty();
			savingsTag.hide();
		}
	}

	$(
		'.revx-volume-discount .revx-builder__quantity input[data-name=revx_quantity]'
	).on( 'change', function () {
		const parent = $( this ).closest( '.revx-campaign-item' );
		const product_id = $( this )
			.closest( '.revx-campaign-item' )
			.data( 'product-id' );

		if ( ! product_id ) {
			return;
		}

		const quantity = $( this ).val();

		parent
			.parent()
			.parent()
			.find( '.revx-campaign-add-to-cart-btn' )
			.attr( 'data-quantity', quantity );

		const campaignId = $( this ).data( 'campaign-id' ); // Get data-campaign-id attribute value

		let offerData = $( `input[name="revx-offer-data-${ campaignId }"]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		const regularPrice = (
			jsonData[ product_id ].regular_price * quantity
		).toFixed( 2 );
		const salePrice = calculateCurrentPrice(
			jsonData,
			product_id,
			quantity
		).toFixed( 2 );

		if ( salePrice != regularPrice ) {
			$( parent )
				.find( '.revx-campaign-item__sale-price' )
				.html( formatPrice( salePrice ) );
			$( parent )
				.find( '.revx-campaign-item__regular-price' )
				.html( formatPrice( regularPrice ) );
		} else {
			$( parent )
				.find( '.revx-campaign-item__regular-price' )
				.html( formatPrice( salePrice ) );
		}
	} );

	// Bundle Discount
	$(
		'.revx-bundle-discount .revx-builder__quantity input[data-name=revx_quantity]'
	).on( 'change', function () {
		const parent = $( this ).closest( '.revx-campaign-container__wrapper' );
		const bundle_products = $( this )
			.closest( '.revx-campaign-container__wrapper' )
			.data( 'bundle_products' );

		if ( bundle_products.length == 0 ) {
			return;
		}

		const quantity = $( this ).val();

		parent
			.find( '.revenue-campaign-add-bundle-to-cart' )
			.attr( 'data-quantity', quantity );

		const campaignId = $( this ).data( 'campaign-id' ); // Get data-campaign-id attribute value

		let offerData = $( `input[name="revx-offer-data-${ campaignId }"]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		let totalRegularPrice = 0;
		let totalSalePrice = 0;

		bundle_products.forEach( ( product ) => {
			const productId = product.item_id;
			// let quantity = product.quantity;

			if ( jsonData[ productId ] ) {
				totalRegularPrice += parseFloat(
					(
						jsonData[ productId ].regular_price *
						quantity *
						product.quantity
					).toFixed( 2 )
				);
				totalSalePrice += parseFloat(
					calculateCurrentPrice(
						jsonData,
						productId,
						product.quantity * quantity
					).toFixed( 2 )
				);
			}
		} );

		if ( totalRegularPrice != totalSalePrice ) {
			$( parent )
				.find(
					'.revx-total-price__offer-price .revx-campaign-item__sale-price'
				)
				.html( formatPrice( totalSalePrice ) );
			$( parent )
				.find(
					'.revx-total-price__offer-price .revx-campaign-item__regular-price'
				)
				.html( formatPrice( totalRegularPrice ) );
		} else {
			$( parent )
				.find(
					'.revx-total-price__offer-price .revx-campaign-item__regular-price'
				)
				.html( formatPrice( totalRegularPrice ) );
		}
	} );

	// Normal Discount
	$(
		'.revx-normal-discount .revx-builder__quantity input[data-name=revx_quantity]'
	).on( 'change', function () {
		const parent = $( this ).closest( '.revx-campaign-item' );
		const product_id = $( this )
			.closest( '.revx-campaign-item' )
			.data( 'product-id' );

		if ( ! product_id ) {
			return;
		}

		const quantity = $( this ).val();

		parent
			.find( '.revx-campaign-add-to-cart-btn' )
			.attr( 'data-quantity', quantity );
		const campaignId = $( this ).data( 'campaign-id' ); // Get data-campaign-id attribute value

		let offerData = $( `input[name="revx-offer-data-${ campaignId }"]` );
		offerData = offerData[ 0 ].value;
		const jsonData = JSON.parse( offerData );

		if ( ! jsonData[ product_id ] ) {
			return;
		}

		const inRP = jsonData[ product_id ].regular_price;

		const regularPrice = (
			jsonData[ product_id ].regular_price * quantity
		).toFixed( 2 );

		const salePrice = calculateCurrentPrice(
			jsonData,
			product_id,
			quantity
		).toFixed( 2 );

		const inSP = ( salePrice / quantity ).toFixed( 2 );

		updatePriceDisplay( parent, quantity, inSP, inRP );

		// if (salePrice != regularPrice) {
		//     $(parent).find('.revx-campaign-item__sale-price').html(`${qty} x `+formatPrice(inSP));
		//     $(parent).find('.revx-campaign-item__regular-price').html(`${qty} x `+formatPrice(inRP));
		// } else {
		//     $(parent).find('.revx-campaign-item__sale-price').html(`${qty} x `+formatPrice(inRP));
		// }
	} );

	// Delegate click event for the 'minus' button
	// $( document ).on( 'click', '.revx-quantity-minus', function ( e ) {
	// 	e.preventDefault();
	// 	e.stopPropagation(); // Stop event propagation to parent elements
	// 	const $input = $( this ).siblings( 'input[type="number"]' );

	// 	if ( $( this ).data( 'skip-global' ) ) {
	// 		return;
	// 	}

	// 	if ( ! $input ) {
	// 		return;
	// 	}
	// 	$input.focus(); // Focus on the input field after updating its value

	// 	const currentValue = parseInt( $input.val(), 10 );
	// 	const min = $input.attr( 'min' );

	// 	if ( min && currentValue - 1 >= min ) {
	// 		if ( ! isNaN( currentValue ) && currentValue > 0 ) {
	// 			$input.val( currentValue - 1 );
	// 		}
	// 	}
	// 	// else if ( ! isNaN( currentValue ) && currentValue - 1 > 0 ) {
	// 	// 	$input.val( currentValue - 1 );
	// 	// }

	// 	$input.trigger( 'change' );
	// } );

	// // Delegate click event for the 'plus' button
	// $( document ).on( 'click', '.revx-quantity-plus', function ( e ) {
	// 	e.preventDefault();
	// 	e.stopPropagation(); // Stop event propagation to parent elements

	// 	// Skip if it has data-skip-global attribute
	// 	if ( $( this ).data( 'skip-global' ) ) {
	// 		return;
	// 	}

	// 	const $input = $( this ).siblings( 'input[type="number"]' );
	// 	if ( ! $input.length ) {
	// 		return;
	// 	}
	// 	$input.focus(); // Focus on the input field after updating its value

	// 	const currentValue = parseInt( $input.val(), 10 );
	// 	const maxValue = parseInt( $input.attr( 'max' ), 10 ); // Get the max attribute value

	// 	if ( ! isNaN( currentValue ) ) {
	// 		// Check if current value is less than the max value
	// 		if ( ! isNaN( maxValue ) && currentValue < maxValue ) {
	// 			$input.val( currentValue + 1 );
	// 		} else if ( isNaN( maxValue ) ) {
	// 			$input.val( currentValue + 1 ); // No max set, just increment
	// 		}
	// 	} else {
	// 		$input.val( 1 ); // Set default value if current value is not a number
	// 	}

	// 	$input.trigger( 'change' );
	// } );

	// Delegate input event for the quantity input field
	$( document ).on(
		'input',
		'input[data-name=revx_quantity]',
		function ( e ) {
			const minVal = parseInt( $( this ).attr( 'min' ) ) || 0; // Default to 0 if min is not set
			const maxVal = parseInt( $( this ).attr( 'max' ) ); // Parse max value from the attribute
			const val = parseInt( $( this ).val() ); // Get the current value of the input

			// If the current value is less than min, set it to min
			if ( val < minVal ) {
				$( this ).val( minVal );
			}

			// If the current value is greater than max, set it to max
			if ( ! isNaN( maxVal ) && val > maxVal ) {
				$( this ).val( maxVal );
			}
			// Trigger change event after updating the value
			$( this ).trigger( 'change' );
		}
	);

	function getCookie( cname ) {
		const name = 'revx_' + cname + '=';
		const decodedCookie = decodeURIComponent( document.cookie );
		const ca = decodedCookie.split( ';' );
		for ( let i = 0; i < ca.length; i++ ) {
			let c = ca[ i ];
			while ( c.charAt( 0 ) == ' ' ) {
				c = c.substring( 1 );
			}
			if ( c.indexOf( name ) == 0 ) {
				return c.substring( name.length, c.length );
			}
		}
		return '';
	}

	// Function to set the cookie
	function setCookie( name, value, days ) {
		const date = new Date();
		date.setTime( date.getTime() + days * 24 * 60 * 60 * 1000 );
		const expires = 'expires=' + date.toUTCString();
		document.cookie =
			'revx_' +
			name +
			'=' +
			encodeURIComponent( value ) +
			';' +
			expires +
			';path=/';
	}

	// Mix Match campaigns single product add to cart button.
	$( '.revx-campaign-product-card .revx-mix-match-product-btn' ).on(
		'click',
		function ( e ) {
			e.preventDefault();

			const campaignId = $( this ).data( 'campaign-id' );
			const item = $( this ).closest( '.revx-mix_match-add-to-cart' );
			let productId = item.data( 'product-id' );
			const productType = item.attr( 'product_type' );

			const quantity =
				item.find( `input[data-name="revx_quantity"]` ).val() ?? 1;

			const offerData = $(
				`input[name="revx-offer-data-${ campaignId }"]`
			).val();
			const container = $( this ).closest(
				'.revx-campaign-product-card'
			);
			const jsonData = JSON.parse( offerData );

			const qtyData = $(
				`input[name="revx-qty-data-${ campaignId }"]`
			).val();
			const jsonQtyData = JSON.parse( qtyData );
			let parentId = null;
			let variationProductDetails = null;
			let variationAttributes = null;
			let selectedData = null;

			if ( productType === 'variable' ) {
				const variationMap = item
					.find( '[data-variation-map]' )
					.data( 'variation-map' );
				selectedData = getSelectedAttributes( item );
				const odata = container.attr( 'data-variations' );
				parentId = container.data( 'product-id' );
				const a = JSON.parse( odata );

				if ( ! selectedData ) {
					// throw new Error( 'Selected data is undefined or null.' );
					showToast(
						revenue_campaign?.select_all_attributes ||
							'Please select all product attributes before adding to cart.',
						'error',
						3000
					);
					return;
				}

				// Check for empty or undefined attribute values
				for ( const [ , value ] of Object.entries( selectedData ) ) {
					if (
						value === '' ||
						value === null ||
						value === undefined
					) {
						showToast(
							revenue_campaign?.select_all_attributes ||
								'Please select all required attributes',
							'error',
							3000
						);
						return;
					}
				}

				// now i need to match product id from variation map with selected data
				let matchedVariationId = null;

				const matchedVariation = variationMap.find( ( variation ) => {
					if ( ! variation.attributes ) {
						return false;
					}
					return Object.entries( selectedData ).every(
						( [ key, val ] ) =>
							! val ||
							! variation.attributes[ key ] ||
							variation.attributes[ key ] === val
					);
				} );

				if ( matchedVariation ) {
					matchedVariationId = matchedVariation.variation_id;
					productId = matchedVariationId;
				}

				variationProductDetails = a.find( ( v ) => {
					return parseInt( v.id ) === parseInt( matchedVariationId );
				} );

				variationAttributes = Object.entries( selectedData )
					.filter( ( [ key ] ) => key.startsWith( 'attribute_' ) ) // only attributes
					.map( ( [ , value ] ) => value ) // just take the value
					.join( ' - ' ); // join with dash

				jsonData[ matchedVariationId ] = {
					item_id: parseInt( matchedVariationId ),
					item_name: `${ jsonData[ parentId ]?.item_name || '' }${
						variationAttributes ? ' - ' + variationAttributes : ''
					}`,
					thumbnail: variationProductDetails.image_url || '',
					regular_price: variationProductDetails.regular_price || '',
					sale_price: variationProductDetails.sale_price || '',
					quantity: 1,
					parent_id: parentId,
				};
			}

			const data = {
				id: productId,
				productName:
					productType === 'variable'
						? `${ jsonData[ parentId ]?.item_name || '' }${
								variationAttributes
									? ' - ' + variationAttributes
									: ''
						  }`
						: jsonData[ productId ]?.item_name,
				regularPrice:
					productType === 'variable'
						? variationProductDetails.regular_price
						: jsonData[ productId ]?.regular_price,
				thumbnail:
					productType === 'variable'
						? variationProductDetails.image_url
						: jsonData[ productId ]?.thumbnail,
				quantity,
			};

			const cookieName = `mix_match_${ campaignId }`;
			let prevData = getCookie( cookieName );

			let prevSelectedItems = $(
				`input[name="revx-selected-items-${ campaignId }"]`
			).val();

			prevSelectedItems = prevSelectedItems
				? JSON.parse( prevSelectedItems )
				: {};

			prevData = prevData ? JSON.parse( prevData ) : {};

			// handles the case of initial render. when the required items are pre-selected and not in cookie,
			// merge the cookie data with pre selected data and then update accordingly.
			if ( Object.keys( prevSelectedItems ).length !== 0 ) {
				for ( const itemId in prevSelectedItems ) {
					if ( ! prevData[ itemId ] ) {
						prevData[ itemId ] = prevSelectedItems[ itemId ];
					}
				}
			}
			let clonedItem;

			if ( prevData[ productId ] ) {
				prevData[ productId ].quantity =
					parseInt( prevData[ productId ].quantity ) +
					parseInt( quantity );

				$(
					`.revx-selected-item[data-campaign-id=${ campaignId }][data-product-id=${ productId }] .revx-selected-item__product-price .woocommerce-Price-amount`
				).text( `${ formatPrice( data.regularPrice ) }` );
				$(
					`.revx-selected-item[data-campaign-id=${ campaignId }][data-product-id=${ productId }] .revx-selected-item__product-price .revx-qty`
				).text( `(x ${ prevData[ productId ].quantity })` );
			} else {
				const selectedContainer = $(
					`.revx-campaign-${ campaignId } .revx-selected-container`
				);

				selectedContainer.removeClass( 'revx-d-none' );
				selectedContainer.removeClass( 'revx-empty-selected-items' );

				$(
					`.revx-campaign-${ campaignId } .revx-empty-mix-match`
				).addClass( 'revx-d-none' );

				prevData[ productId ] = data;

				const placeholderItem = $(
					`.revx-selected-item.revx-d-none[data-campaign-id=${ campaignId }]`
				).first(); // ensure only one placeholder.
				clonedItem = placeholderItem.clone();
				clonedItem
					.find( '.revx-selected-title' )
					.html( data.productName );
				clonedItem
					.find( '.revx-campaign-item__image img' )
					.attr( 'src', data.thumbnail );
				clonedItem
					.find( '.revx-campaign-item__image img' )
					.attr( 'alt', data.productName );
				clonedItem
					.find(
						'.revx-selected-item__product-price .revx-price-placeholder'
					)
					.text( `${ formatPrice( data.regularPrice ) }` );
				clonedItem
					.find( '.revx-selected-item__product-price .revx-qty' )
					.text( `(x ${ quantity })` );
				clonedItem.removeClass( 'revx-d-none' );
				clonedItem.attr( 'data-product-id', productId );
				if ( productType === 'variable' ) {
					clonedItem.attr(
						'data-selected-attribute',
						JSON.stringify( selectedData )
					);
				}
				clonedItem.attr( 'data-parent-id', parentId );
				clonedItem.attr( 'data-product-type', productType );
				placeholderItem.before( clonedItem );
			}

			$( `input[name="revx-selected-items-${ campaignId }"]` ).val(
				JSON.stringify( prevData )
			);

			setCookie( cookieName, JSON.stringify( prevData ), 7 );

			updateMixMatchHeaderAndPrices( campaignId, prevData, jsonQtyData );

			$( this )
				.parent()
				.find( `input[data-name="revx_quantity"]` )
				.val( 1 );
			// make the reset button visible after adding any product
			$( this )
				.closest( '.revx-template' )
				.find( '[data-mix-match-reset-btn]' )
				.removeClass( 'revx-d-none' );
		}
	);

	function removeMixMatchSelectedItem() {
		const productId = $( this )
			.closest( '[data-product-id]' )
			.data( 'product-id' );
		const campaignId = $( this )
			.closest( '[data-campaign-id]' )
			.data( 'campaign-id' );
		const item = $(
			`.revx-selected-item[data-campaign-id=${ campaignId }][data-product-id="${ productId }"]`
		);

		item.remove();

		const cookieName = `mix_match_${ campaignId }`;
		let prevData = getCookie( cookieName );
		let prevSelectedItems = $(
			`input[name=revx-selected-items-${ campaignId }]`
		).val();
		prevSelectedItems = prevSelectedItems
			? JSON.parse( prevSelectedItems )
			: {};

		prevData = prevData ? JSON.parse( prevData ) : {};

		// handles the case of initial render, when the required items are pre-selected,
		// merge the cookie data with pre selected data and then update accordingly.
		if ( Object.keys( prevSelectedItems ).length !== 0 ) {
			for ( const itemId in prevSelectedItems ) {
				if ( ! prevData[ itemId ] ) {
					prevData[ itemId ] = prevSelectedItems[ itemId ];
				}
			}
		}

		delete prevData[ productId ];
		setCookie( cookieName, JSON.stringify( prevData ), 7 );
		$( `input[name=revx-selected-items-${ campaignId }]` ).val(
			JSON.stringify( prevData )
		);

		const qtyData = $( `input[name=revx-qty-data-${ campaignId }]` ).val();
		const jsonQtyData = JSON.parse( qtyData );

		if ( Object.keys( prevData ).length === 0 ) {
			$(
				`.revx-campaign-${ campaignId } .revx-empty-selected-products`
			).removeClass( 'revx-d-none' );
			$(
				`.revx-campaign-${ campaignId } .revx-selected-product-container`
			).addClass( 'revx-empty-selected-items' );
			$(
				`.revx-campaign-${ campaignId } .revx-empty-mix-match`
			).removeClass( 'revx-d-none' );

			$(
				`.revx-campaign-${ campaignId } [data-mix-match-reset-btn]`
			).addClass( 'revx-d-none' );
		}

		updateMixMatchHeaderAndPrices( campaignId, prevData, jsonQtyData );
	}

	$( '[data-container-level="mix_match_file"]' ).on(
		'click',
		'.revx-selected-remove',
		removeMixMatchSelectedItem
	);

	// refactor later to make DRY
	function resetMixMatchSelectedItem() {
		const campaignId = $( this )
			.closest( '[data-campaign-id]' )
			.data( 'campaign-id' );

		const cookieName = `mix_match_${ campaignId }`;

		let prevSelectedItems = $(
			`input[name=revx-selected-items-${ campaignId }]`
		).val();
		prevSelectedItems = prevSelectedItems
			? JSON.parse( prevSelectedItems )
			: {};

		for ( const itemId in prevSelectedItems ) {
			const $item = $( this )
				.closest( '[revx-campaign-id]' )
				.find(
					`.revx-selected-item[data-campaign-id=${ campaignId }][data-product-id="${ itemId }"]`
				);
			if ( prevSelectedItems[ itemId ].is_required === 'yes' ) {
				prevSelectedItems[ itemId ].quantity = 1;
				$item.find( '.revx-qty' ).text( '(x 1)' );
			} else {
				$item.remove();
				delete prevSelectedItems[ itemId ];
			}
		}
		setCookie( cookieName, JSON.stringify( prevSelectedItems ), 7 );
		$( `input[name=revx-selected-items-${ campaignId }]` ).val(
			JSON.stringify( prevSelectedItems )
		);

		const qtyData = $( `input[name=revx-qty-data-${ campaignId }]` ).val();
		const jsonQtyData = JSON.parse( qtyData );

		if ( Object.keys( prevSelectedItems ).length === 0 ) {
			$(
				`.revx-campaign-${ campaignId } .revx-empty-selected-products`
			).removeClass( 'revx-d-none' );
			$(
				`.revx-campaign-${ campaignId } .revx-selected-product-container`
			).addClass( 'revx-empty-selected-items' );
			$(
				`.revx-campaign-${ campaignId } .revx-empty-mix-match`
			).removeClass( 'revx-d-none' );
			$( this ).addClass( 'revx-d-none' );
		}

		updateMixMatchHeaderAndPrices(
			campaignId,
			prevSelectedItems,
			jsonQtyData
		);
	}
	$( '[data-container-level="mix_match_file"]' ).on(
		'click',
		'[data-mix-match-reset-btn]',
		resetMixMatchSelectedItem
	);

	function setTierState( $tier, tierClass, checkboxClass, isChecked ) {
		$tier.addClass( tierClass.add ).removeClass( tierClass.remove );
		const $checkboxContainer = $tier.find( '.revx-checkbox-container' );
		$checkboxContainer
			.addClass( checkboxClass.add )
			.removeClass( checkboxClass.remove )
			.attr( 'data-is-checked', isChecked ? 'yes' : 'no' )
			.css( 'display', isChecked ? '' : 'none' ); // Ensure visibility based on state
	}

	function updateMixMatchHeaderAndPrices(
		campaignId,
		prevData,
		jsonQtyData = {}
	) {
		const header = $(
			`.revx-campaign-${ campaignId } .revx-price-container`
		);
		const itemCounts = Object.keys( prevData ).length;
		let $selectedTier = null;
		$( '.revx-tier-button' ).each( function () {
			// Extract the item count from the mix-match-title text
			const titleText = $( this )
				.find( '.revx-mix-match-title' )
				.text()
				.trim();
			const itemCount = parseInt( titleText.split( ' ' )[ 0 ], 10 ); // Extract number from "X item"

			if ( itemCounts >= itemCount ) {
				$selectedTier = $( this );
			}
		} );
		const selectedClass = {
			tierClass: {
				add: 'revx-tier-selected',
				remove: 'revx-tier-regular',
			},
			checkboxClass: {
				add: 'revx-active',
				remove: 'revx-inactive revx-d-none',
			},
		};
		const unselectedClass = {
			tierClass: {
				add: 'revx-tier-regular',
				remove: 'revx-tier-selected',
			},
			checkboxClass: {
				add: 'revx-inactive revx-d-none',
				remove: 'revx-active',
			},
		};
		// Update tier button styles based on selection
		$( '.revx-tier-button' ).each( function () {
			if ( $selectedTier && $( this ).is( $selectedTier ) ) {
				setTierState(
					$( this ),
					selectedClass.tierClass,
					selectedClass.checkboxClass,
					true
				);
			} else {
				setTierState(
					$( this ),
					unselectedClass.tierClass,
					unselectedClass.checkboxClass,
					false
				);
			}
		} );
		const qtyData = $( `input[name=revx-qty-data-${ campaignId }]` ).val();
		jsonQtyData = qtyData ? JSON.parse( qtyData ) : [];

		header.toggleClass( 'revx-d-none', ! itemCounts ); // remove none if more than 0, adds none when item count is 0

		// const addToCart = $(`.revx-campaign-add-to-cart-btn[data-campaign-id=${campaign_id}]`);
		// if(item_counts==0 && !addToCart.hasClass('revx-d-none') ) {
		//     $(`.revx-campaign-add-to-cart-btn[data-campaign-id=${campaign_id}]`).addClass('revx-d-none');
		// } else if(item_counts>0) {
		//     $(`.revx-campaign-add-to-cart-btn[data-campaign-id=${campaign_id}]`).removeClass('revx-d-none');
		// }
		header.find( '.revx-selected-product-count' ).html( itemCounts );

		let totalRegularPrice = 0;
		let totalSalePrice = 0;
		let totalQuantity = 0;
		Object.values( prevData ).forEach( ( item ) => {
			totalRegularPrice +=
				parseFloat( item.regularPrice ) * parseInt( item.quantity );
			totalQuantity += parseInt( item.quantity );
		} );

		let selectedIndex = -1;
		jsonQtyData.forEach( ( item, idx ) => {
			if ( itemCounts >= item.quantity ) {
				selectedIndex = idx;

				switch ( item.type ) {
					case 'percentage':
						totalSalePrice =
							totalRegularPrice * ( 1 - item.value / 100 );
						break;
					case 'fixed_discount':
						totalSalePrice = Math.max(
							0,
							parseFloat( totalRegularPrice ) -
								parseFloat( item.value * totalQuantity )
						);

						break;
					case 'no_discount':
						totalSalePrice = totalRegularPrice;

						break;
					case 'fixed_price':
						totalSalePrice = item.value * totalQuantity;
						break;
					default:
						break;
				}
			}
		} );
		// make display none for no_discount, other cases, remove the class
		header
			.find( '.revx-product-old-price' )
			.toggleClass(
				'revx-d-none',
				jsonQtyData[ selectedIndex ]?.type === 'no_discount' ||
					totalSalePrice === 0
			);
		const that = $(
			`.revx-campaign-${ campaignId } .revx-mixmatch-quantity`
		);
		that.each( function () {
			const item = $( this ).find( '.revx-mixmatch-regular-quantity' );

			$( item )
				.find( '.revx-checkbox-container' )
				.addClass( 'revx-d-none' );
		} );

		const clickedItem = that.find( `div[data-index=${ selectedIndex }]` );
		$( clickedItem )
			.find( '.revx-checkbox-container' )
			.removeClass( 'revx-d-none' );
		if ( totalSalePrice === 0 ) {
			header
				.find( '.revx-campaign-item__sale-price' )
				.html( formatPrice( totalRegularPrice ) );
		} else {
			if (
				totalSalePrice &&
				header
					.find( '.revx-campaign-item__sale-price' )
					.hasClass( 'revx-d-none' )
			) {
				header
					.find( '.revx-campaign-item__sale-price' )
					.removeClass( 'revx-d-none' );
			}
			if (
				totalRegularPrice &&
				header
					.find( '.revx-campaign-item__regular-price' )
					.hasClass( 'revx-d-none' )
			) {
				header
					.find( '.revx-campaign-item__regular-price' )
					.removeClass( 'revx-d-none' );
			}

			header
				.find( '.revx-campaign-item__sale-price' )
				.html( formatPrice( totalSalePrice ) );
			header
				.find( '.revx-product-old-price' )
				.html( formatPrice( totalRegularPrice ) );
		}
	}

	// old fbt - available for old campaigns in v1
	// Frequently Bought Together
	// Function to update the styles based on selection
	// function updateStyles( $checkbox, selected ) {
	// 	const selectedStyles = $checkbox.data( 'selected-style' );
	// 	const defaultStyles = $checkbox.data( 'default-style' );
	// 	if ( selected ) {
	// 		$checkbox.attr( 'style', selectedStyles );
	// 	} else {
	// 		$checkbox.attr( 'style', defaultStyles );
	// 	}
	// }
	// $( '.revx-frequently-bought-together' ).on(
	// 	'click',
	// 	'.revx-item-options .revx-item-option',
	// 	function ( e ) {
	// 		e.preventDefault();

	// 		const $this = $( this );
	// 		if ( $this.hasClass( 'revx-item-required' ) ) {
	// 			return;
	// 		}
	// 		const $checkbox = $this.find( '.revx-builder-checkbox' );

	// 		const parent = $this.closest( '.revx-campaign-container__wrapper' );

	// 		const campaign_id = parent.data( 'campaign-id' );
	// 		const cookieName = `campaign_${ campaign_id }`;
	// 		let selectedProducts = getCookie( cookieName );
	// 		let prevSelectedItems = $(
	// 			`input[name=revx-fbt-selected-items-${ campaign_id }]`
	// 		).val();
	// 		prevSelectedItems = prevSelectedItems
	// 			? JSON.parse( prevSelectedItems )
	// 			: {};
	// 		selectedProducts = selectedProducts
	// 			? JSON.parse( selectedProducts )
	// 			: {};
	// 		if ( Object.keys( selectedProducts ) == 0 ) {
	// 			selectedProducts = { ...prevSelectedItems };
	// 		}

	// 		const productId = $this.data( 'product-id' );

	// 		// Toggle the selected state
	// 		if ( selectedProducts[ productId ] ) {
	// 			// selectedProducts = selectedProducts.filter(id => id !== productId);
	// 			delete selectedProducts[ productId ];
	// 			updateStyles( $checkbox, false );
	// 		} else {
	// 			const quantityInput =
	// 				$(
	// 					`input[name="revx-quantity-${ campaign_id }-${ productId }"]`
	// 				).val() ?? $this.data( 'min-quantity' );

	// 			selectedProducts[ productId ] = quantityInput;
	// 			updateStyles( $checkbox, true );
	// 		}
	// 		$( `input[name="revx-fbt-selected-items-${ campaign_id }"]` ).val(
	// 			JSON.stringify( selectedProducts )
	// 		);

	// 		// Update the cookie
	// 		setCookie( cookieName, JSON.stringify( selectedProducts ), 1 );

	// 		fbtCalculation( parent, campaign_id );
	// 	}
	// );

	// const fbtCalculation = ( parent, campaign_id ) => {
	// 	const cookieName = `campaign_${ campaign_id }`;
	// 	let selectedProducts = getCookie( cookieName );
	// 	let prevSelectedItems = $(
	// 		`input[name=revx-fbt-selected-items-${ campaign_id }]`
	// 	).val();
	// 	prevSelectedItems = prevSelectedItems
	// 		? JSON.parse( prevSelectedItems )
	// 		: {};
	// 	selectedProducts = selectedProducts
	// 		? JSON.parse( selectedProducts )
	// 		: {};
	// 	if ( Object.keys( selectedProducts ) == 0 ) {
	// 		selectedProducts = { ...prevSelectedItems };
	// 	}

	// 	const calculateSalePrice = ( data, qty = 1 ) => {
	// 		if ( ! data?.type ) {
	// 			return data.regular_price * qty;
	// 		}
	// 		let total = 0;
	// 		switch ( data.type ) {
	// 			case 'percentage':
	// 				total =
	// 					parseFloat( data.regular_price ) *
	// 					( 1 - data.value / 100 );

	// 				break;
	// 			case 'amount':
	// 			case 'fixed_discount':
	// 				total = Math.max(
	// 					0,
	// 					parseFloat( data.regular_price ) -
	// 						parseFloat( data.value )
	// 				);

	// 				break;
	// 			case 'fixed_price':
	// 				total = parseFloat( data.value );

	// 				break;
	// 			case 'no_discount':
	// 				total = parseFloat( data.regular_price );

	// 				break;
	// 			case 'free':
	// 				total = 0;

	// 				break;

	// 			default:
	// 				break;
	// 		}

	// 		return parseFloat( total ) * parseInt( qty );
	// 	};
	// 	let offerData = $(
	// 		`input[name=revx-offer-data-${ campaign_id }]`
	// 	).val();

	// 	offerData = JSON.parse( offerData );

	// 	let totalRegularPrice = 0;
	// 	let totalSalePrice = 0;

	// 	Object.keys( selectedProducts ).forEach( ( id ) => {
	// 		totalRegularPrice +=
	// 			parseFloat( offerData[ id ]?.regular_price ) *
	// 			parseInt( selectedProducts[ id ] );
	// 		totalSalePrice += parseFloat(
	// 			calculateSalePrice(
	// 				offerData[ id ],
	// 				parseInt( selectedProducts[ id ] )
	// 			)
	// 		);
	// 	} );

	// 	if ( totalRegularPrice != totalSalePrice ) {
	// 		parent
	// 			.find(
	// 				`.revx-triggerProduct .revx-campaign-item__regular-price`
	// 			)
	// 			.html( formatPrice( totalRegularPrice ) );
	// 		parent
	// 			.find( `.revx-triggerProduct .revx-campaign-item__sale-price` )
	// 			.html( formatPrice( totalSalePrice ) );
	// 	} else {
	// 		parent
	// 			.find( `.revx-triggerProduct .revx-campaign-item__sale-price` )
	// 			.html( formatPrice( totalSalePrice ) );
	// 		parent
	// 			.find(
	// 				`.revx-triggerProduct .revx-campaign-item__regular-price`
	// 			)
	// 			.html( '' );
	// 	}
	// 	parent
	// 		.find( `.revx-triggerProduct .revx-selected-product-count` )
	// 		.html( Object.keys( selectedProducts ).length );
	// };

	// $( '.revx-frequently-bought-together' ).on(
	// 	'change',
	// 	'input[data-name=revx_quantity]',
	// 	function ( e ) {
	// 		e.preventDefault();
	// 		const parent = $( this ).closest(
	// 			'.revx-campaign-container__wrapper'
	// 		);

	// 		const quantity = $( this ).val();

	// 		const campaign_id = parent.data( 'campaign-id' );

	// 		// addFbtRequiredProductsIfNotAdded(campaign_id,false);
	// 		const product_id = $( this ).data( 'product-id' );
	// 		const cookieName = `campaign_${ campaign_id }`;

	// 		let selectedProducts = getCookie( cookieName );
	// 		let prevSelectedItems = $(
	// 			`input[name=revx-fbt-selected-items-${ campaign_id }]`
	// 		).val();
	// 		prevSelectedItems = prevSelectedItems
	// 			? JSON.parse( prevSelectedItems )
	// 			: {};
	// 		selectedProducts = selectedProducts
	// 			? JSON.parse( selectedProducts )
	// 			: {};
	// 		if ( Object.keys( selectedProducts ) == 0 ) {
	// 			selectedProducts = { ...prevSelectedItems };
	// 		}

	// 		if ( selectedProducts[ product_id ] ) {
	// 			selectedProducts[ product_id ] = quantity;

	// 			setCookie( cookieName, JSON.stringify( selectedProducts ), 1 );
	// 			fbtCalculation( parent, campaign_id );
	// 		}

	// 		$( `input[name=revx-fbt-selected-items-${ campaign_id }]` ).val(
	// 			JSON.stringify( selectedProducts )
	// 		);

	// 		const calculateSalePrice = ( data, qty = 1 ) => {
	// 			if ( ! data?.type ) {
	// 				return data.regular_price * qty;
	// 			}
	// 			let total = 0;
	// 			switch ( data.type ) {
	// 				case 'percentage':
	// 					total =
	// 						parseFloat( data.regular_price ) *
	// 						( 1 - data.value / 100 );

	// 					break;
	// 				case 'amount':
	// 				case 'fixed_discount':
	// 					total = Math.max(
	// 						0,
	// 						parseFloat( data.regular_price ) -
	// 							parseFloat( data.value )
	// 					);

	// 					break;
	// 				case 'fixed_price':
	// 					total = parseFloat( data.value );

	// 					break;
	// 				case 'no_discount':
	// 					total = parseFloat( data.regular_price );

	// 					break;
	// 				case 'free':
	// 					total = 0;

	// 					break;

	// 				default:
	// 					break;
	// 			}

	// 			return parseFloat( total ) * parseInt( qty );
	// 		};
	// 		let offerData = $( `input[name=revx-offer-data-${ campaign_id }]` );

	// 		offerData = offerData[ 0 ].value;
	// 		const jsonData = JSON.parse( offerData );

	// 		const salePrice = calculateSalePrice(
	// 			jsonData[ product_id ],
	// 			quantity
	// 		).toFixed( 2 );

	// 		const inRP = jsonData[ product_id ].regular_price;

	// 		const inSP = ( salePrice / quantity ).toFixed( 2 );

	// 		const itemParent = $( this ).closest( '.revx-campaign-item' );

	// 		updatePriceDisplay( itemParent, quantity, inSP, inRP );
	// 	}
	// );

	// Buy X Get Y
	$( '.revx-buyx-gety' ).on(
		'change',
		'input[data-name=revx_quantity]',
		function ( e ) {
			e.preventDefault();
			const parent = $( this ).closest( '.revx-campaign-container' );

			const quantity = $( this ).val();

			const campaign_id = parent.data( 'campaign-id' );

			const product_id = $( this ).data( 'product-id' );

			let offerData = $( `input[name=revx-offer-data-${ campaign_id }]` );

			offerData = offerData[ 0 ].value;
			const jsonData = JSON.parse( offerData );

			const regularPrice = (
				jsonData[ product_id ].regular_price * quantity
			).toFixed( 2 );
			const salePrice = calculateCurrentPrice(
				jsonData,
				product_id,
				quantity
			).toFixed( 2 );

			const item = $( this ).closest( '.revx-campaign-item__content' );

			item.find( '.revx-campaign-item__regular-price' ).text(
				formatPrice( regularPrice )
			);
			item.find( '.revx-campaign-item__sale-price' ).text(
				formatPrice( salePrice )
			);

			if ( regularPrice == salePrice ) {
				if (
					! item
						.find( '.revx-campaign-item__regular-price' )
						.hasClass( 'revx-d-none' )
				) {
					item.find( '.revx-campaign-item__regular-price' ).addClass(
						'revx-d-none'
					);
				}
			} else if (
				item
					.find( '.revx-campaign-item__regular-price' )
					.hasClass( 'revx-d-none' )
			) {
				item.find( '.revx-campaign-item__regular-price' ).removeClass(
					'revx-d-none'
				);
			}

			let totalRegularPrice = 0;
			let totalSalePrice = 0;

			Object.keys( jsonData ).forEach( ( pid ) => {
				const qty = $(
					`input[name="revx-quantity-${ campaign_id }-${ pid }"]`
				).val();
				const rp = ( jsonData[ pid ].regular_price * qty ).toFixed( 2 );
				const sp = parseFloat(
					calculateCurrentPrice( jsonData, pid, qty ).toFixed( 2 )
				);
				totalRegularPrice += parseFloat( rp );
				totalSalePrice += parseFloat( sp );
			} );

			parent
				.find( '.revx-total-price .revx-campaign-item__regular-price' )
				.html( formatPrice( totalRegularPrice ) );
			parent
				.find( '.revx-total-price .revx-campaign-item__sale-price' )
				.html( formatPrice( totalSalePrice ) );
		}
	);

	// Slider -----------------------

	function checkOverflow( container ) {
		$( container ).each( function () {
			const $this = $( this );

			const isOverflowing =
				$this[ 0 ].scrollWidth > $this[ 0 ].offsetWidth;

			if ( isOverflowing ) {
				$( this )
					.find( '.revx-builderSlider-icon' )
					.addClass( 'revx-has-overflow' );
			} else {
				$( this )
					.find( '.revx-builderSlider-icon' )
					.removeClass( 'revx-has-overflow' );
			}
		} );
	}

	// function initializeSlider(
	// 	$sliderContainer,
	// 	$containerSelector = '.revx-inpage-container',
	// 	$campaign_type = ''
	// ) {
	// 	const $container = $sliderContainer.closest( $containerSelector );
	// 	const containerElement = $container.get( 0 );
	// 	const computedStyle = getComputedStyle( containerElement );
	// 	const gridColumnValue = computedStyle
	// 		.getPropertyValue( '--revx-grid-column' )
	// 		.trim();
	// 	let itemGap = parseInt(
	// 		computedStyle.getPropertyValue( 'gap' ).trim()
	// 	);

	// 	if ( ! itemGap ) {
	// 		itemGap = 16;
	// 	}

	// 	const $slides = $sliderContainer.find( '.revx-campaign-item' );
	// 	const minSlideWidth = 100; // 12rem in pixels (assuming 1rem = 16px)

	// 	let containerWidth = $sliderContainer.parent().width();

	// 	if ( $campaign_type == 'mix_match' ) {
	// 		containerWidth = $sliderContainer
	// 			.closest( '.revx-slider-items-wrapper' )
	// 			.innerWidth();
	// 	}
	// 	if ( $campaign_type == 'bundle_discount' ) {
	// 		containerWidth = $sliderContainer
	// 			.closest( '.revx-slider-items-wrapper' )
	// 			.outerWidth();
	// 		itemGap = 0;
	// 	}
	// 	if ( $campaign_type == 'fbt' ) {
	// 		containerWidth = $container
	// 			.find( '.revx-slider-items-wrapper' )
	// 			.innerWidth();
	// 	}
	// 	if ( $campaign_type == 'normal_discount' ) {
	// 		containerWidth = $container
	// 			.closest( '.revx-slider-items-wrapper' )
	// 			.innerWidth();
	// 		itemGap = 0;
	// 	}

	// 	let slidesVisible = Math.min(
	// 		gridColumnValue,
	// 		Math.floor( containerWidth / minSlideWidth )
	// 	); // Calculate initial slides visible

	// 	let slideWidth = containerWidth / slidesVisible;
	// 	slideWidth -= itemGap;

	// 	if ( $campaign_type == 'bundle_discount' ) {
	// 		slideWidth -= $container
	// 			.find( '.revx-builder__middle_element' )
	// 			.width();
	// 	}

	// 	const totalSlides = $slides.length;
	// 	let slideIndex = 0;

	// 	function updateSlideWidth() {
	// 		containerWidth = $sliderContainer
	// 			.closest( '.revx-slider-items-wrapper' )
	// 			.innerWidth();

	// 		slidesVisible = Math.min(
	// 			gridColumnValue,
	// 			Math.floor( containerWidth / minSlideWidth )
	// 		); // Recalculate slides visible
	// 		slideWidth = containerWidth / slidesVisible;
	// 		slideWidth -= itemGap;

	// 		if ( $campaign_type == 'bundle_discount' ) {
	// 			slideWidth -= $sliderContainer
	// 				.find( '.revx-builder__middle_element' )
	// 				.width();
	// 		}

	// 		$slides.css( 'width', slideWidth + 'px' );

	// 		moveToSlide( slideIndex );
	// 	}

	// 	setTimeout( () => {
	// 		updateSlideWidth();
	// 	} );

	// 	function moveToSlide( index ) {
	// 		let tempWidth = slideWidth;
	// 		if ( $campaign_type == 'fbt' ) {
	// 			tempWidth += $sliderContainer
	// 				.find( '.revx-product-bundle' )
	// 				.width();
	// 		}
	// 		if ( $campaign_type == 'bundle_discount' ) {
	// 			tempWidth += $sliderContainer
	// 				.find( '.revx-builder__middle_element' )
	// 				.width();
	// 		}
	// 		if ( $campaign_type == 'mix_match' ) {
	// 			tempWidth += itemGap;
	// 		}
	// 		const offset = -tempWidth * index;

	// 		$sliderContainer.css( {
	// 			transition: 'transform 0.5s ease-in-out',
	// 			transform: `translateX(${ offset }px)`,
	// 		} );
	// 	}

	// 	function moveToNextSlide() {
	// 		slideIndex++;

	// 		if ( slideIndex > totalSlides - slidesVisible ) {
	// 			slideIndex = 0;
	// 		}

	// 		moveToSlide( slideIndex );
	// 	}

	// 	function moveToPrevSlide() {
	// 		slideIndex--;

	// 		if ( slideIndex < 0 ) {
	// 			slideIndex = totalSlides - slidesVisible;
	// 		}

	// 		moveToSlide( slideIndex );
	// 	}

	// 	$sliderContainer
	// 		.siblings( '.revx-builderSlider-right' )
	// 		.click( function () {
	// 			if ( ! $sliderContainer.is( ':animated' ) ) {
	// 				moveToNextSlide();
	// 			}
	// 		} );

	// 	$sliderContainer
	// 		.siblings( '.revx-builderSlider-left' )
	// 		.click( function () {
	// 			if ( ! $sliderContainer.is( ':animated' ) ) {
	// 				moveToPrevSlide();
	// 			}
	// 		} );

	// 	setTimeout( () => {
	// 		// // const initialWidth = $sliderContainer.width();
	// 		// $sliderContainer.width(containerWidth + 1); // Increase width by 1px
	// 		// $sliderContainer.width(containerWidth); // Reset to original width
	// 		$sliderContainer.parent().width( containerWidth ); // Reset to original width
	// 		$sliderContainer.parent().width( containerWidth + 1 ); // Reset to original width
	// 		$( window ).trigger( 'resize' ); // Trigger window resize
	// 	} );

	// 	$( window ).resize( function () {
	// 		updateSlideWidth();
	// 	} );

	// 	$sliderContainer
	// 		.closest( '.revx-inpage-container' )
	// 		.css( 'visibility', 'visible' );
	// }

	// function buxXGetYSlider() {
	// 	$( '.revx-inpage-container.revx-buyx-gety-grid' ).each( function () {
	// 		const $container = $( this ).find(
	// 			'.revx-campaign-container__wrapper'
	// 		);
	// 		const containerElement = $container.get( 0 );
	// 		const computedStyle = getComputedStyle( containerElement );

	// 		let gridColumnValue = parseInt(
	// 			computedStyle.getPropertyValue( '--revx-grid-column' ).trim()
	// 		);
	// 		const minSlideWidth = 132; // 12rem in pixels (assuming 1rem = 16px)

	// 		const $triggerItemContainer = $container.find(
	// 			'.revx-bxgy-trigger-items'
	// 		);
	// 		const $offerItemContainer = $container.find(
	// 			'.revx-bxgy-offer-items'
	// 		);

	// 		let triggerItemColumn = parseInt(
	// 			getComputedStyle( $triggerItemContainer.get( 0 ) )
	// 				.getPropertyValue( '--revx-grid-column' )
	// 				.trim()
	// 		);
	// 		let offerItemColumn = parseInt(
	// 			getComputedStyle( $offerItemContainer.get( 0 ) )
	// 				.getPropertyValue( '--revx-grid-column' )
	// 				.trim()
	// 		);

	// 		let containerWidth = $container.width();

	// 		const seperatorWidth = $container
	// 			.find( '.revx-product-bundle' )
	// 			.width();

	// 		containerWidth -= seperatorWidth - 16;

	// 		gridColumnValue = gridColumnValue ? gridColumnValue : 4;

	// 		gridColumnValue = Math.min(
	// 			gridColumnValue,
	// 			Math.floor( containerWidth / minSlideWidth )
	// 		);
	// 		triggerItemColumn = Math.min(
	// 			$triggerItemContainer.find( '.revx-campaign-item' ).length,
	// 			triggerItemColumn
	// 		);
	// 		offerItemColumn = Math.min(
	// 			$offerItemContainer.find( '.revx-campaign-item' ).length,
	// 			offerItemColumn
	// 		);

	// 		gridColumnValue = Math.min(
	// 			gridColumnValue,
	// 			triggerItemColumn + offerItemColumn
	// 		);

	// 		// gridColumnValue = gridColumnValue ? gridColumnValue : 4;

	// 		// Ensure the total columns for trigger and offer items do not exceed the available grid columns
	// 		if ( triggerItemColumn + offerItemColumn > gridColumnValue ) {
	// 			const excessColumns =
	// 				triggerItemColumn + offerItemColumn - gridColumnValue;

	// 			// Adjust columns proportionally to ensure total columns match gridColumnValue
	// 			const triggerAdjustment = Math.floor(
	// 				( triggerItemColumn /
	// 					( triggerItemColumn + offerItemColumn ) ) *
	// 					excessColumns
	// 			);
	// 			const offerAdjustment = excessColumns - triggerAdjustment;

	// 			triggerItemColumn -= triggerAdjustment;
	// 			offerItemColumn -= offerAdjustment;
	// 		}

	// 		const slideWidth = containerWidth / gridColumnValue;

	// 		initializeSubSlider(
	// 			$triggerItemContainer,
	// 			triggerItemColumn,
	// 			slideWidth,
	// 			'trigger'
	// 		);
	// 		initializeSubSlider(
	// 			$offerItemContainer,
	// 			offerItemColumn,
	// 			slideWidth,
	// 			'offer'
	// 		);

	// 		$( this ).css( 'visibility', 'visible' );
	// 	} );
	// }

	// function initializeSubSlider(
	// 	$sliderContainer,
	// 	itemColumn,
	// 	slideWidth,
	// 	type
	// ) {
	// 	const $container = $sliderContainer.find( '.revx-slider-container' );
	// 	const itemGap = parseInt(
	// 		getComputedStyle( $container.get( 0 ) )
	// 			.getPropertyValue( 'gap' )
	// 			.trim()
	// 	);

	// 	// slideWidth -=itemGap;
	// 	slideWidth -= itemGap;
	// 	const containerWidth = itemColumn * slideWidth;
	// 	$sliderContainer.width( containerWidth );
	// 	slideWidth -= 16;

	// 	if ( type == 'offer' ) {
	// 		slideWidth += itemGap;
	// 	}

	// 	$sliderContainer = $container;

	// 	const $slides = $sliderContainer.find( '.revx-campaign-item' );
	// 	$slides.css( { width: slideWidth + 'px' } );

	// 	const totalSlides = $slides.length;
	// 	let slideIndex = 0; // Start at the first slide

	// 	function moveToSlide( index ) {
	// 		let tempWidth = slideWidth;
	// 		tempWidth += itemGap + 16;
	// 		tempWidth += index;

	// 		if ( itemColumn == 1 ) {
	// 			tempWidth += itemGap;
	// 		}

	// 		if ( type == 'offer' ) {
	// 			tempWidth -= 16;
	// 		}

	// 		const offset = -tempWidth * index;

	// 		$sliderContainer.css( {
	// 			transition: 'transform 0.5s ease-in-out',
	// 			transform: `translateX(${ offset }px)`,
	// 		} );
	// 	}

	// 	function moveToNextSlide() {
	// 		slideIndex++;
	// 		if ( slideIndex > totalSlides - itemColumn ) {
	// 			slideIndex = 0;
	// 		}
	// 		moveToSlide( slideIndex );
	// 	}

	// 	function moveToPrevSlide() {
	// 		slideIndex--;
	// 		if ( slideIndex < 0 ) {
	// 			slideIndex = totalSlides - itemColumn;
	// 		}
	// 		moveToSlide( slideIndex );
	// 	}

	// 	$sliderContainer
	// 		.siblings( '.revx-builderSlider-right' )
	// 		.click( function () {
	// 			if ( ! $sliderContainer.is( ':animated' ) ) {
	// 				moveToNextSlide();
	// 			}
	// 		} );

	// 	$sliderContainer
	// 		.siblings( '.revx-builderSlider-left' )
	// 		.click( function () {
	// 			if ( ! $sliderContainer.is( ':animated' ) ) {
	// 				moveToPrevSlide();
	// 			}
	// 		} );

	// 	$( window ).resize( function () {
	// 		moveToSlide( slideIndex );
	// 	} );

	// 	moveToSlide( slideIndex );
	// }

	// buxXGetYSlider();

	// $( window ).resize( function () {
	// 	buxXGetYSlider();
	// } );

	// $(
	// 	'.revx-inpage-container.revx-normal-discount-grid .revx-slider-container'
	// ).each( function () {
	// 	initializeSlider(
	// 		$( this ),
	// 		'.revx-campaign-view__items',
	// 		'normal_discount'
	// 	);
	// } );
	// $(
	// 	'.revx-inpage-container.revx-mix-match-grid .revx-slider-container'
	// ).each( function () {
	// 	initializeSlider(
	// 		$( this ),
	// 		'.revx-campaign-view__items',
	// 		'mix_match'
	// 	);
	// } );
	// $(
	// 	'.revx-inpage-container.revx-bundle-discount-grid .revx-slider-container'
	// ).each( function () {
	// 	initializeSlider(
	// 		$( this ),
	// 		'.revx-campaign-view__items',
	// 		'bundle_discount'
	// 	);
	// } );
	// $(
	// 	'.revx-inpage-container.revx-frequently-bought-together-grid .revx-slider-container'
	// ).each( function () {
	// 	initializeSlider( $( this ), '.revx-inpage-container', 'fbt' );
	// } );

	// $( window ).on( 'load resize', function () {
	// 	checkOverflow( '.revx-slider' );
	// } );

	// ---------------- Slider

	// $('.revx-ticket-type')

	$( '.revx-ticket-type' ).change( function () {
		const selectedSlug = $( this ).val();
		const campaignID = $( this ).data( 'campaign-id' );
		const offerQty = $( this ).data( 'quantity' );

		const prices = $( this ).data( 'prices' );

		$( this )
			.closest( '.revx-campaign-item' )
			.find( '.revx-total-ticket-price' )
			.text(
				`Total Price: ${ formatPrice(
					prices[ selectedSlug ] * offerQty
				) }`
			);

		const selectedItem = $( this )
			.closest( '.revx-volume-discount' )
			.find( '.revx-campaign-item[data-revx-selected=true]' );

		if ( selectedItem.length ) {
			// Check if the selectedItem exists
			// selectedItem.data('data-selected-ticket', selectedSlug);
			selectedItem.attr( 'data-selected-ticket', selectedSlug );
			selectedItem.attr(
				'data-selected-ticket-price',
				prices[ selectedSlug ] * offerQty
			);
		}
	} );

	$( '.revx-ticket-type' ).trigger( 'change' );

	// Tooltip - Spending Goal
	function updateTooltipPosition( $container ) {
		const $tooltip = $container.find( '.revx-gift-tooltip' );
		if ( ! $tooltip.length ) {
			return;
		}

		const containerRect = $container[ 0 ].getBoundingClientRect();
		const tooltipRect = $tooltip[ 0 ].getBoundingClientRect();
		const windowHeight = $( window ).height();
		const windowWidth = $( window ).width();

		// Calculate available space
		const spaceAbove = containerRect.top;
		const spaceBelow = windowHeight - containerRect.bottom;

		// Determine position (top or bottom)
		const position = spaceAbove > spaceBelow ? 'top' : 'bottom';
		$tooltip.attr( 'data-position', position );

		// Handle horizontal overflow
		const tooltipWidth = $tooltip.outerWidth();
		const containerCenterX = containerRect.left + containerRect.width / 2;
		const spaceLeft = containerCenterX;
		const spaceRight = windowWidth - containerCenterX;

		if ( tooltipWidth / 2 > spaceLeft ) {
			// Not enough space on the left
			$tooltip.css( {
				left: '0',
				transform: 'translateX(0)',
			} );
		} else if ( tooltipWidth / 2 > spaceRight ) {
			// Not enough space on the right
			$tooltip.css( {
				left: 'auto',
				right: '0',
				transform: 'translateX(0)',
			} );
		} else {
			// Center aligned
			$tooltip.css( {
				// left: '50%',
				right: 'auto',
				transform: 'translateX(-50%)',
			} );
		}
	}

	// Handle mouse enter
	$( '.revx-step-icon-container' ).on( 'mouseenter', function () {
		updateTooltipPosition( $( this ) );
	} );

	// Update positions on window resize
	let resizeTimer;
	$( window ).on( 'resize', function () {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( function () {
			$( '.revx-step-icon-container:hover' ).each( function () {
				updateTooltipPosition( $( this ) );
			} );
		}, 250 );
	} );

	function updateGiftPosition( $container ) {
		const $gift = $container.find( '.revx-gift-container' );
		const $giftWrapper = $container.find( '.revx-spending-gift' );
		if ( ! $gift.length ) {
			return;
		}
		// remove the class before. otherwise top calculation is wrong.
		$gift.removeClass( 'revx-d-none' );

		const iconRect = $container[ 0 ].getBoundingClientRect(); // relative to viewport
		const giftRect = $gift[ 0 ].getBoundingClientRect();

		const giftWidth = giftRect?.width;
		const iconWidth = iconRect?.width;
		const windowWidth = window.innerWidth;
		const giftCenterToLeft = Math.abs( giftWidth - iconWidth ) / 2;

		const isAbove = iconRect?.top > giftRect?.height + 10;
		const isRightSpace = iconRect?.left + giftCenterToLeft < windowWidth;
		const isLeftSpace = iconRect?.left - giftCenterToLeft >= 0;

		const top = isAbove
			? ( giftRect?.height + 8 ) * -1
			: iconRect?.height + 8;

		let left = 0;
		if ( ! isLeftSpace && isRightSpace ) {
			left = -20; // small offset from left edge
		} else if ( isLeftSpace && ! isRightSpace ) {
			left = iconWidth - giftWidth;
		} else {
			// for space both left/right OR no space left/right keep center.
			left = giftCenterToLeft * -1;
		}

		$gift.css( {
			position: 'fixed',
			top: `${ top }px`,
			left: `${ left }px`,
			zIndex: 999999,
			visibility: 'visible',
			opacity: 1,
		} );
		$giftWrapper.css( {
			transformOrigin: `${ isAbove ? 'bottom' : 'top' } ${
				! isLeftSpace && isRightSpace
					? 'left'
					: isLeftSpace && ! isRightSpace
					? 'right'
					: 'center'
			}`,
		} );
	}

	// Stock Scarcity
	$( '.revx-flip-wrapper' ).each( function () {
		const $this = $( this ); // Define $this as the current .revx-flip-wrapper element
		const flipTextHeight = $this.find( '.revx-flip-text' ).outerHeight();
		$this.css( 'min-height', flipTextHeight + 'px' );
	} );

	let giftHideTimer;
	$( document )
		.on( 'mouseenter', '.revx-progress-step-icon-container', function () {
			clearTimeout( giftHideTimer ); // prevent hiding
			updateGiftPosition( $( this ) );
		} )
		.on( 'mouseleave', '.revx-progress-step-icon-container', function () {
			const $this = $( this );
			giftHideTimer = setTimeout( () => {
				$this
					.find( '.revx-gift-container' )
					.addClass( 'revx-d-none' )
					.css( {
						visibility: 'hidden',
						opacity: 0,
						left: '-250px', // to handle the overflow x case
						top: 'unset',
					} );
			}, 200 ); // adjust delay as needed
		} );

	$( document )
		.on( 'mouseenter', '.revx-gift-container', function () {
			clearTimeout( giftHideTimer ); // prevent hiding
		} )
		.on( 'mouseleave', '.revx-gift-container', function () {
			$( this ).addClass( 'revx-d-none' ).css( {
				visibility: 'hidden',
				opacity: 0,
				left: 'unset',
				top: 'unset',
			} );
		} );

	// Reposition gift on resize or scroll
	$( window ).on( 'resize scroll', function () {
		$( '.revx-progress-step-icon-container:hover' ).each( function () {
			updateGiftPosition( $( this ) );
		} );
	} );

	function adjustContentMarginTop() {
		// const helloBar = $( '.revx-spending-goal-top' ).length
		// 	? $( '.revx-spending-goal-top' )
		// 	: $( '.revx-campaign-fsb-top' );
		const helloBar = $( '.revx-campaign-top' ).length
			? $( '.revx-campaign-top' )
			: $( '.revx-campaign-fsb-top' );

		// Check if hello bar exists
		if ( helloBar.length === 0 ) {
			return;
		}

		const helloBarHeight = helloBar.outerHeight();

		// Only adjust if we have a valid height
		if ( helloBarHeight ) {
			$( 'body' ).css( 'margin-top', helloBarHeight );

			// Remove margin-bottom as it's causing double spacing
			// helloBar.css( 'margin-top', -helloBarHeight );
		}
	}

	adjustContentMarginTop();
	function adjustContentMarginBottom() {
		const helloBar = $( '.revx-campaign-bottom' ).length
			? $( '.revx-campaign-bottom' )
			: $( '.revx-campaign-fsb-bottom' );

		// Check if hello bar exists
		if ( helloBar.length === 0 ) {
			return;
		}

		const helloBarHeight = helloBar.outerHeight();

		// Only adjust if we have a valid height
		if ( helloBarHeight ) {
			$( 'body' ).css( 'margin-bottom', helloBarHeight );

			// Remove margin-bottom as it's causing double spacing
			// helloBar.css('margin-bottom', -helloBarHeight);
		}
	}

	adjustContentMarginBottom();

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
			warning: 'revx-toaster__warning',
			error: 'revx-toaster__error',
		};

		const icon = `
				<svg xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" fill="none" viewBox="0 0 16 16" class="revx-toaster__close-icon revx-toaster__icon">
					<path stroke="#fff" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.2" d="m12 4-8 8M4 4l8 8"></path>
				</svg>
			`;

		// Create a new toast element as a jQuery object
		const $toast = $( `
			<div class="revx-toaster revx-justify-space revx-toaster-lg ${ toastClasses[ type ] }" style="display: flex;">
				<div class="revx-paragraph--xs revx-align-center-xs">
					${ message }
				</div>
				<div class="revx-paragraph--xs revx-align-center">
					${ icon }
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

	const $drawerContainer = $( '.revx-drawer-container' ).first();
	if ( $drawerContainer.length ) {
		// Clone the entire container including content
		// const $drawerContent = $drawerContainer.find( '.revx-drawer-content' );
		// $drawerContent.each( function () {
		// 	this.style.setProperty( 'max-width', '0vh', 'important' );
		// 	this.style.setProperty( 'padding', '0vh', 'important' );
		// } );

		const $clone = $drawerContainer.clone( true, true );
		const $cloneContent = $clone.find( '.revx-drawer-content' );
		// Remove max-width and padding limits on drawer-content inside clone
		$cloneContent.each( function () {
			this.style.setProperty( 'max-width', 'none', 'important' );
			this.style.setProperty(
				'padding-top',
				'var(--revx-drawer-padding-top)',
				'important'
			);
			this.style.setProperty(
				'padding-right',
				'var(--revx-drawer-padding-right)',
				'important'
			);
			this.style.setProperty(
				'padding-bottom',
				'var(--revx-drawer-padding-bottom)',
				'important'
			);
			this.style.setProperty(
				'padding-left',
				'var(--revx-drawer-padding-left)',
				'important'
			);
			// this.style.setProperty( 'position', 'static', 'important' );
		} );
		// $cloneContent.find( '*' ).each( function () {
		// 	this.style.setProperty( 'position', 'static', 'important' );
		// } );

		$clone.css( {
			display: 'flex',
			position: 'absolute',
			top: '-9999px',
			left: '-9999px',
			visibility: 'hidden',
			bottom: 'unset',
			right: 'unset',
		} );
		// Append clone to body so you can see it on top
		$( 'body' ).append( $clone );
		const cloneHeight = $clone.innerHeight();
		// const cloneHeight = $clone.outerHeight( true );
		$drawerContainer.each( function () {
			this.style.setProperty( 'height', cloneHeight + 'px', 'important' );
			this.style.setProperty( 'display', 'flex', 'important' );
		} );
	}

	// Open campaign drawer
	$( document ).on( 'click', '.revx-drawer-opener', function () {
		const $container = $( this ).closest( '.revx-drawer-container' );
		const $drawerContent = $container.find( '.revx-drawer-content' );

		// $drawerContent.each( function () {
		// 	this.style.setProperty( 'transition', 'all 0.2s', 'important' );
		// } );
		$drawerContent.addClass( 'revx-transition' );

		if ( $container.hasClass( 'revx-active' ) ) {
			$container.removeClass( 'revx-active' );
			$container.css( { overflow: 'hidden' } );
			// $drawerContent.each( function () {
			// 	this.style.setProperty( 'max-width', '0vh', 'important' );
			// 	this.style.setProperty( 'padding', '0vh', 'important' );
			// } );
		} else {
			$container.addClass( 'revx-active' );
			$container.css( { overflow: 'visible' } );
			// $drawerContent.each( function () {
			// 	this.style.removeProperty( 'max-width' );
			// 	this.style.removeProperty( 'padding' );
			// } );
		}
	} );
	// Close campaign drawer
	$( document ).on( 'click', '.revx-drawer-closer', function () {
		const $container = $( this ).closest( '.revx-drawer-container' );
		// const $drawerContent = $container.find( '.revx-drawer-content' );
		$container.removeClass( 'revx-active' );
		// $drawerContent.each( function () {
		// 	this.style.setProperty( 'max-width', '0vh', 'important' );
		// 	this.style.setProperty( 'padding', '0vh', 'important' );
		// } );
	} );

	// Hide Campaign on Close Button Click
	$( document ).on( 'click', '.revx-campaign-close', function () {
		const campaignID = $( this ).data( 'campaign-id' );
		$( `.revx-campaign-${ campaignID }` ).hide();
		$( 'body' ).css( 'margin-bottom', 0 );
		$( 'body' ).css( 'margin-top', 0 );
	} );

	// Next Order Coupon Copy Clip-Borad
	$( '.revx-coupon-copy-btn' ).on( 'click', function () {
		const $btn = $( this );

		// // const $content = $btn.closest( '.revx-Coupon-button' );
		// const text = $content
		// 	.clone() // Clone to avoid modifying original
		// 	.children() // Remove children (like .revx-coupon-copy-btn)
		// 	.remove()
		// 	.end() // Go back to cloned parent
		// 	.text()
		// 	.trim(); // Get just the raw text

		const $content = $btn.siblings( '.revx-coupon-value' );
		const text = $content.text().trim();

		// Fallback for browsers that do not support navigator.clipboard
		const tempInput = $( '<input>' );
		$( 'body' ).append( tempInput );
		tempInput.val( text ).select();
		try {
			document.execCommand( 'copy' );
			// $btn.text( 'Copied!' );
			$( '.revx-coupon-value' ).css( 'background-color', '#008000' );
			$( '.revx-coupon-value' ).css( {
				transition: 'background-color 0.4s',
			} );

			setTimeout(
				() =>
					$( '.revx-coupon-value' ).css(
						'background-color',
						'unset'
					),
				400
			);
		} catch ( err ) {
			console.error( 'Fallback: Failed to copy:', err );
		}
		tempInput.remove();
	} );

	window.Revenue = {
		getCookie,
		setCookie,
		calculateCurrentPrice,
		formatPrice,
		updatePriceDisplay,
		updateMixMatchHeaderAndPrices,
		// fbtCalculation,
		// updateStyles,
		showToast,
	};
	// eslint-disable-next-line no-undef

	$( '.revx-slider-wrapper' ).each( function () {
		if ( ! $( this ).data( 'slider-initialized' ) ) {
			$( this ).revxSlider();
			$( this ).data( 'slider-initialized', true );
		}
	} );

	// Auto initialize on page load
	// function initTimers( $context ) {
	// 	$context.find( '.revx-countdown-timer' ).revxCountdownTimer();
	// }

	// $( document ).ready( function () {
	// 	initTimers( $( document ) );

	// 	// MutationObserver for dynamically added timers
	// 	const observer = new MutationObserver( function ( mutations ) {
	// 		mutations.forEach( function ( mutation ) {
	// 			$( mutation.addedNodes ).each( function () {
	// 				if ( this.nodeType === 1 ) {
	// 					const $el = $( this );
	// 					if ( $el.is( '.revx-countdown-timer' ) ) {
	// 						$el.revxCountdownTimer();
	// 					} else {
	// 						initTimers( $el );
	// 					}
	// 				}
	// 			} );
	// 		} );
	// 	} );

	// 	observer.observe( document.body, {
	// 		childList: true,
	// 		subtree: true,
	// 	} );
	// } );

	function getSelectedAttributes( $form ) {
		const selectedData = {};
		$form.find( 'select[name^="attribute_"]' ).each( function () {
			const key = $( this ).attr( 'name' );
			selectedData[ key ] = $( this ).val() || '';
		} );
		return selectedData;
	}

	// Update data-selected-value when any attribute dropdown changes
	$( '.product' ).on( 'change', 'select[name^="attribute_"]', function () {
		const $form = $( this ).closest( '.product' );
		const selectedData = getSelectedAttributes( $form );
		// Update the data-selected-value attribute
		$form.attr( 'data-selected-value', JSON.stringify( selectedData ) );
	} );
} )( jQuery );

// move jquery code from the template1 volume discount selection.
jQuery( function ( $ ) {
	$( document ).on( 'click', '.revx-volume-discount-item', function () {
		const $radio = $( this ).find( '.revx-radio-wrapper' );
		const $attribute = $( this ).find( '.revx-volume-attributes' );

		// deactivate every other option
		$( '.revx-radio-wrapper, .revx-volume-attributes' )
			.removeClass( 'revx-active' )
			.addClass( 'revx-inactive' );

		// activate the clicked one
		$radio
			.add( $attribute )
			.addClass( 'revx-active' )
			.removeClass( 'revx-inactive' );
	} );
} );
