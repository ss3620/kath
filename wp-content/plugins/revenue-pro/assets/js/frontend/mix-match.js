/* global revenue_campaign Revenue jQuery */
// the below line ignores revenue_campaign not camel case warning
/* eslint-disable camelcase */
jQuery( function ( $ ) {
	const campaignType = 'mix_match';

	function initializeSlider( $slider, containerSelector ) {
		const $container = $slider.closest( containerSelector );
		const $slides = $slider.find( '.revx-campaign-item' );
		let index = 0;

		function update() {
			const columns = parseInt( getComputedStyle( $container.get( 0 ) ).getPropertyValue( '--revx-grid-column' ), 10 ) || 3;
			const width = $slider.parent().width();
			const visible = Math.max( 1, Math.min( columns, Math.floor( width / 160 ) ) );
			const slideWidth = width / visible;
			$slides.css( 'width', `${ slideWidth }px` );
			$slider.css( 'transform', `translateX(${ -slideWidth * index }px)` );
			return visible;
		}

		let visible = update();
		$slider.siblings( '.revx-builderSlider-right' ).on( 'click', function () {
			index = index >= $slides.length - visible ? 0 : index + 1;
			visible = update();
		} );
		$slider.siblings( '.revx-builderSlider-left' ).on( 'click', function () {
			index = index <= 0 ? Math.max( 0, $slides.length - visible ) : index - 1;
			visible = update();
		} );
		$( window ).on( 'resize', function () {
			visible = update();
		} );
	}

	$( document ).on( 'revx-campaign-popup-opened', function () {
		$(
			'.revx-popup__content.revx-mix-match-grid .revx-slider-container'
		).each( function () {
			initializeSlider( $( this ), '.revx-popup__content' );
		} );
	} );
	$( document ).on( 'revx-campaign-floating-opened', function () {
		$( '.revx-floating.revx-mix-match .revx-slider-container' ).each(
			function () {
				initializeSlider( $( this ), '.revx-floating' );
			}
		);
	} );

	// Selection, totals and add-to-cart: moved from Free's campaign.js/add-to-cart.js.

	function getSelectedAttributes( $container ) {
		const selectedData = {};
		$container.find( 'select[name^="attribute_"]' ).each( function () {
			selectedData[ $( this ).attr( 'name' ) ] = $( this ).val() || '';
		} );
		return selectedData;
	}

	function toIntOr( value, fallback ) {
		const n = parseInt( value, 10 );
		return Number.isFinite( n ) ? n : fallback;
	}

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
		// Eligibility basis: 'total' sums offered-product quantities, otherwise count distinct products.
		const countMode =
			typeof revenue_campaign !== 'undefined' &&
			revenue_campaign.mix_match_count_mode === 'total'
				? 'total'
				: 'unique';
		const eligibleQty =
			countMode === 'total'
				? Object.values( prevData ).reduce(
						( sum, i ) => sum + parseInt( i.quantity ),
						0
				  )
				: itemCounts;
		let $selectedTier = null;
		$( '.revx-tier-button' ).each( function () {
			// Extract the item count from the mix-match-title text
			const titleText = $( this )
				.find( '.revx-mix-match-title' )
				.text()
				.trim();
			const itemCount = parseInt( titleText.split( ' ' )[ 0 ], 10 ); // Extract number from "X item"

			if ( eligibleQty >= itemCount ) {
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

		// Addon: hide the whole footer (selected list + total + add to cart) until a product is added.
		if (
			typeof revenue_campaign !== 'undefined' &&
			revenue_campaign.mix_match_hide_footer_until_selected
		) {
			$( `.revx-mixmatch-footer[revx-campaign-id="${ campaignId }"]` ).toggleClass(
				'revx-d-none',
				! itemCounts
			);
		}

		header.find( '.revx-selected-product-count' ).html( eligibleQty );

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
			if ( eligibleQty >= item.quantity ) {
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
				.html( Revenue.formatPrice( totalRegularPrice ) );
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
				.html( Revenue.formatPrice( totalSalePrice ) );
			header
				.find( '.revx-product-old-price' )
				.html( Revenue.formatPrice( totalRegularPrice ) );
		}
	}

	// Mix Match campaigns single product add to cart button.
	$( '.revx-campaign-product-card .revx-mix-match-product-btn' ).on(
		'click',
		function ( e ) {
			e.preventDefault();

			const campaignId = $( this ).data( 'campaign-id' );
			const item = $( this ).closest( '.revx-mix_match-add-to-cart' );
			let productId = item.data( 'product-id' );
			let productType = item.attr( 'product_type' );

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

			// Addon: split single-attribute variation card. The card already carries the fixed
			// variation context, so add it directly without resolving a dropdown selection.
			const splitRaw = item.attr( 'data-split-variation' );
			const isSplitVariation = !! splitRaw;
			if ( isSplitVariation ) {
				const split = JSON.parse( splitRaw );
				productId = split.variation_id;
				parentId = split.parent_id;
				selectedData = split.attributes || {};
				variationAttributes = Object.values( selectedData ).join( ' - ' );
				variationProductDetails = {
					regular_price: split.regular_price,
					sale_price: split.sale_price,
					image_url: split.thumbnail,
				};
				// Downstream selected-item clone + add-to-cart expect the 'variable' branch.
				productType = 'variable';
				// Ensure the parent name prefix resolves even though offer data omits variations.
				if ( ! jsonData[ parentId ] ) {
					jsonData[ parentId ] = { item_name: split.item_name };
				}
				jsonData[ productId ] = {
					item_name: split.item_name,
					regular_price: split.regular_price,
					sale_price: split.sale_price,
					thumbnail: split.thumbnail,
				};
			}

			if ( productType === 'variable' && ! isSplitVariation ) {
				const variationMap = item
					.find( '[data-variation-map]' )
					.data( 'variation-map' );
				selectedData = getSelectedAttributes( item );
				const odata = container.attr( 'data-variations' );
				parentId = container.data( 'product-id' );
				const a = JSON.parse( odata );

				if ( ! selectedData ) {
					$( document ).trigger( 'revx-campaign-notice', [
						revenue_campaign?.select_all_attributes ||
							'Please select all product attributes before adding to cart.',
						'error',
					] );
					return;
				}

				// Check for empty or undefined attribute values
				for ( const [ , value ] of Object.entries( selectedData ) ) {
					if (
						value === '' ||
						value === null ||
						value === undefined
					) {
						$( document ).trigger( 'revx-campaign-notice', [
							revenue_campaign?.select_all_attributes ||
								'Please select all required attributes',
							'error',
						] );
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
				productName: isSplitVariation
					? jsonData[ productId ]?.item_name
					: productType === 'variable'
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
			let prevData = Revenue.getCookie( cookieName );

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
				).text( `${ Revenue.formatPrice( data.regularPrice ) }` );
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
					.text( `${ Revenue.formatPrice( data.regularPrice ) }` );
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

			Revenue.setCookie( cookieName, JSON.stringify( prevData ), 7 );

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
		let prevData = Revenue.getCookie( cookieName );
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
		Revenue.setCookie( cookieName, JSON.stringify( prevData ), 7 );
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
		Revenue.setCookie( cookieName, JSON.stringify( prevSelectedItems ), 7 );
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

	function getCookieData( cookieName ) {
		try {
			return JSON.parse( Revenue.getCookie( cookieName ) || '{}' );
		} catch ( e ) {
			console.error(
				`Failed to parse cookie data for ${ cookieName }:`,
				e
			);
			return {};
		}
	}

	function getMixMatchData( campaignId ) {
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
	}

	// Mix and match add to cart handler: hands the prepared request to Free's
	// shared dispatcher, which performs the AJAX request and success handling.
	function handleMixAndMatch( e ) {
		e.preventDefault();

		const $button = $( this );
		const campaignId = $button.data( 'campaignId' ) || '';
		const campaignTypeAttr = $button.data( 'campaignType' ) || campaignType;

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
			campaignType: campaignTypeAttr,
			quantity,
			campaignId,
			products: modifiedProducts,
		};
		data.mix_match_data = getMixMatchData( campaignId );

		$( document ).trigger( 'revx-campaign-add-to-cart', [ data, $button ] );
	}

	$( document ).on( 'click', '.revx-mix_match-btn', handleMixAndMatch );

	// Post add-to-cart cleanup: moved from Free's clearData mix_match case.
	$( document.body ).on( 'revx-add-to-cart-btn', function ( e, data ) {
		if ( ! data || data.campaignType !== campaignType ) {
			return;
		}
		const campaignId = data.campaignId;

		Revenue.setCookie( `mix_match_${ campaignId }`, '', -1 );
		$( `input[name=revx-selected-items-${ campaignId }]` ).val( '' );
		updateMixMatchHeaderAndPrices( campaignId, '' );
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

		$( `.revx-campaign-${ campaignId }.revx-mix-match` ).hide();
	} );
} );
