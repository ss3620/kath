/* global Revenue revenue_campaign jQuery */
/* eslint-disable camelcase */
jQuery( function ( $ ) {
	const campaignType = 'frequently_bought_together';

	function notice( message ) {
		$( document ).trigger( 'revx-campaign-notice', [ message, 'error' ] );
	}

	function updatePriceAndQuantity( $container, titleTag, priceTag, quantity, price ) {
		const $quantityContainer = $container.find(
			`[data-smart-tag="${ titleTag }"]`
		);
		const smartTitle = $quantityContainer.data( 'smart-tag-text' ) || '';
		$quantityContainer.text( smartTitle.replace( '{qty}', quantity ) );
		$container.find( `[data-smart-tag="${ priceTag }"]` ).text( Revenue.formatPrice( price ) );
	}

	function updateTotals( $container ) {
		const $parentContainer = $container.parent();
		let totalQuantity = 0;
		let totalPrice = 0;
		let requiredQuantity = 0;
		let requiredTotalPrice = 0;

		$parentContainer.find( '.revx-active' ).each( function () {
			const $item = $( this ).closest( '[data-product-offered-price]' );
			const quantity = parseInt( $item.data( 'product-qty' ), 10 ) || 0;
			const isRequired =
				$item.find( '.revx-required-product' ).length > 0 ||
				$item.data( 'is-trigger' ) === 'yes';
			const price = parseFloat(
				isRequired
					? $item.data( 'product-offered-price' ) || $item.data( 'regular-price' )
					: $item.data( 'product-offered-price' )
			) || 0;

			totalQuantity += quantity;
			totalPrice += price * quantity;
			if ( isRequired ) {
				requiredQuantity += quantity;
				requiredTotalPrice += price * quantity;
			}
		} );

		const $template = $parentContainer.closest( '.revx-template' );
		$template
			.find( '[data-fbt-total] [data-smart-tag="selectedTotalTitle"]' )
			.text( Revenue.formatPrice( totalPrice ) );
		updatePriceAndQuantity(
			$template.find( '[data-fbt-trigger-items]' ),
			'selectedTriggerTitle',
			'selectedPrice',
			requiredQuantity,
			requiredTotalPrice
		);
		updatePriceAndQuantity(
			$template.find( '[data-fbt-offer-items]' ),
			'selectedOfferTitle',
			'selectedPrice',
			totalQuantity - requiredQuantity,
			totalPrice - requiredTotalPrice
		);
	}

	function getSelectedAttributes( $container ) {
		const attributes = {};
		$container.find( '.revx-product-Attr-wrapper' ).each( function () {
			attributes[ $( this ).attr( 'name' ) ] = $( this ).val();
		} );
		return attributes;
	}

	function getMatchedVariation( $container ) {
		const variations = JSON.parse( $container.attr( 'data-variations' ) || '[]' );
		const selected = getSelectedAttributes( $container );
		return variations.find( ( variation ) =>
			Object.entries( selected ).every(
				( [ key, value ] ) =>
					! value ||
					! variation.attributes[ key.replace( /attribute_/, '' ) ] ||
					variation.attributes[ key.replace( /attribute_/, '' ) ] === value
			)
		);
	}

	$( document ).on( 'revx-checkbox-toggled revx-fbt-init revx-quantity-changed', `[campaign_type="${ campaignType }"]`, function () {
		updateTotals( $( this ) );
	} );

	$( document ).on( 'revx-campaign-variation-updated', function ( event, $container, variation, isRequired ) {
		if ( $container.attr( 'campaign_type' ) !== campaignType ) {
			return;
		}
		const offeredPrice = variation.offered_price || ( isRequired ? variation.regular_price : 0 );
		$container.data( 'regular-price', variation.regular_price );
		$container.data( 'product-offered-price', offeredPrice );
		if ( $container.find( '.revx-active' ).length ) {
			$container.trigger( 'revx-fbt-init' );
		}
	} );

	$( document.body ).on( 'updated_cart_totals added_to_cart removed_from_cart', function () {
		$( `[campaign_type="${ campaignType }"]` ).trigger( 'revx-fbt-init' );
	} );

	function observeQuantityChanges( $items ) {
		const observer = new MutationObserver( () => $items.first().trigger( 'revx-quantity-changed' ) );
		$items.each( function () {
			observer.observe( this, { attributes: true, attributeFilter: [ 'data-product-qty' ] } );
		} );
	}

	function init() {
		$( '.revx-template' ).each( function () {
			if ( $( this ).children( '.revx-template' ).length ) {
				return;
			}
			const $items = $( this ).find( `[campaign_type="${ campaignType }"]` );
			if ( $items.length ) {
				$items.first().trigger( 'revx-fbt-init' );
				observeQuantityChanges( $items );
			}
		} );
	}

	function initializeSlider( $sliderContainer, containerSelector ) {
		const $container = $sliderContainer.closest( containerSelector );
		const $slides = $sliderContainer.find( '.revx-campaign-item' );
		let slideIndex = 0;

		function update() {
			const gridColumns = parseInt(
				getComputedStyle( $container.get( 0 ) ).getPropertyValue( '--revx-grid-column' ),
				10
			) || 3;
			const containerWidth = $container.find( '.revx-regular-product' ).width();
			const bundleWidth = $container.find( '.revx-product-bundle' ).width() || 0;
			const visible = Math.max( 1, Math.min( gridColumns, Math.floor( containerWidth / 128 ) ) );
			const width = ( containerWidth / visible ) - bundleWidth;
			$slides.css( 'width', `${ width }px` );
			$sliderContainer.css( 'transform', `translateX(${ -( width + bundleWidth ) * slideIndex }px)` );
			return visible;
		}

		let visible = update();
		$sliderContainer.siblings( '.revx-builderSlider-right' ).on( 'click', function () {
			slideIndex = slideIndex >= $slides.length - visible ? 0 : slideIndex + 1;
			update();
		} );
		$sliderContainer.siblings( '.revx-builderSlider-left' ).on( 'click', function () {
			slideIndex = slideIndex <= 0 ? Math.max( 0, $slides.length - visible ) : slideIndex - 1;
			update();
		} );
		$( window ).on( 'resize', function () {
			visible = update();
		} );
	}

	$( document ).on( 'revx-campaign-popup-opened', function () {
		$( '.revx-popup__content.revx-frequently-bought-together-grid .revx-slider-container' ).each( function () {
			initializeSlider( $( this ), '.revx-popup__content' );
		} );
	} );
	$( document ).on( 'revx-campaign-floating-opened', function () {
		$( '.revx-floating.revx-frequently-bought-together-grid .revx-slider-container' ).each( function () {
			initializeSlider( $( this ), '.revx-floating' );
		} );
	} );

	$( document ).on( 'revx-variation-changed', init );
	init();

	$( document ).on( 'click', '.revx-frequently_bought_together-btn', function ( event ) {
		event.preventDefault();
		const $button = $( this );
		const campaignId = $button.data( 'campaign-id' ) || '';
		const $products = $button.closest( '.revx-campaign-wrapper' ).find( `.revx-${ campaignType }-add-to-cart` ).filter( function () {
			return $( this ).find( '.revx-checkbox-container' ).hasClass( 'revx-active' );
		} );
		const requiredProducts = [];
		let hasEmptyAttributes = false;
		const productsData = $products.map( function () {
			const $product = $( this );
			const productType = $product.attr( 'product_type' );
			const isRequired = $product.find( '.revx-checkbox-wrapper' ).hasClass( 'revx-required-product' );
			if ( isRequired ) {
				requiredProducts.push( $product.data( 'product-id' ) );
			}
			let selectedAttributes = {};
			let variationId = $product.data( 'variation-id' ) || 0;
			if ( productType === 'variable' ) {
				selectedAttributes = getSelectedAttributes( $product );
				if ( Object.values( selectedAttributes ).some( ( value ) => ! value ) ) {
					hasEmptyAttributes = true;
					return null;
				}
				const variation = getMatchedVariation( $product );
				variationId = variation ? variation.id || 0 : 0;
			}
			return { productId: $product.data( 'product-id' ), productType, selectedAttributes, variationId, quantity: parseInt( $product.data( 'product-qty' ), 10 ) || 1 };
		} ).get().filter( Boolean );

		if ( hasEmptyAttributes ) {
			notice( revenue_campaign.select_all_attributes || 'Please select all required attributes' );
			return;
		}
		if ( ! productsData.length ) {
			notice( revenue_campaign.select_at_least_one_product || 'Please select at least one product to add' );
			return;
		}
		const fbtData = {};
		const products = productsData.map( ( product ) => {
			fbtData[ product.productId ] = product.quantity;
			return product.productType === 'variable'
				? { product_id: product.productId, variation_id: product.variationId, selected_attributes: product.selectedAttributes, quantity: product.quantity }
				: { product_id: product.productId, quantity: product.quantity };
		} );
		$( document ).trigger( 'revx-campaign-add-to-cart', [ {
			action: 'revenue_add_to_cart', productId: productsData[ 0 ].productId, campaignId, campaignType, quantity: 1,
			requiredProducts, _wpnonce: revenue_campaign.nonce, fbt_data: fbtData, products,
		}, $button ] );
	} );

	// Post add-to-cart hide: moved from Free's clearData.
	$( document.body ).on( 'revx-add-to-cart-btn', function ( e, data ) {
		if ( ! data || data.campaignType !== campaignType ) {
			return;
		}

		$(
			`.revx-campaign-${ data.campaignId }.revx-frequently-bought-together`
		).hide();
	} );
} );
