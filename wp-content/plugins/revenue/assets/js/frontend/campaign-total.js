/* global Revenue jQuery */
( function ( $ ) {
	// update bundle campaign total save badge
	function setSaveBadgeText( $container, value ) {
		const $saveBadgeContainer = $container.find(
			'[data-smart-tag="saveBadgeWrapper"]'
		);
		const defaultText = $saveBadgeContainer.data( 'smart-tag-text' );
		const updatedText = defaultText.replace( '{save_amount}', value + '%' );
		$saveBadgeContainer.text( updatedText );
	}
	function setPriceText( $container, price ) {
		const $priceContainer = $container.find( '.woocommerce-Price-amount' );
		$priceContainer.text( Revenue.formatPrice( price ) );
	}

	// can handle both buy x get y and bundle discount
	function setBundleTotal( $campaignItems ) {
		let totalRegular = 0;
		let totalOffer = 0;
		const isBundle =
			$campaignItems.attr( 'campaign_type' ) === 'bundle_discount';
		const isBuyXGetY =
			$campaignItems.attr( 'campaign_type' ) === 'buy_x_get_y';
		// data-product-id is common in buy x get y and bundle.
		$campaignItems.each( function () {
			const qty = parseInt( $( this ).attr( 'data-product-qty' ) ) || 1; // if quantity is not set, then its atleast 1
			const regularPrice =
				parseFloat( $( this ).attr( 'data-regular-price' ) ) || 0; // should always get regular price, still a fallback is set.
			let offeredPrice = parseFloat(
				$( this ).attr( 'data-offered-price' ) // need to decide if user will get the sale price or the regular price
			);

			const isXProduct = $( this ).attr( 'data-is-x-product' ) === 'yes';

			if ( isBuyXGetY && isXProduct ) {
				offeredPrice = parseFloat(
					$( this ).attr( 'data-sale-price' )
				);
			}
			// if sale price is not set, then offered price will be regular price
			offeredPrice = isNaN( offeredPrice ) ? regularPrice : offeredPrice;

			totalRegular += regularPrice * qty;
			totalOffer += offeredPrice * qty;
		} );
		const $totalContainer = $campaignItems
			.first() // take the first child
			.closest( '.revx-template' ) // find the closest top level container.
			.find( '[data-smart-tag="totalText"]' ) // find the total text
			.parent(); // find the parent which containes both old and offered price

		const $oldPriceContainer = $totalContainer.find(
			'.revx-product-old-price'
		);
		const $offeredPriceContainer = $totalContainer.find(
			'.revx-product-offered-price'
		);
		setPriceText( $oldPriceContainer, totalRegular );
		setPriceText( $offeredPriceContainer, totalOffer );

		if ( isBundle ) {
			const saveAmount = totalRegular - totalOffer;
			const percentValue = parseFloat(
				( saveAmount * 100 ) / totalRegular
			).toFixed( 2 );
			setSaveBadgeText( $totalContainer, percentValue );
		}
	}

	// Create observer
	function observeQtyChnages( $campaignItems, campaignType = null ) {
		const observer = new MutationObserver( () => {
			if ( 'frequently_bought_together' === campaignType ) {
				$campaignItems.first().trigger( 'revx-quantity-changed' );
			} else {
				// whenever any qty changes, re-calc totals for this campaign
				setBundleTotal( $campaignItems );
			}
		} );

		$campaignItems.each( function () {
			observer.observe( this, {
				attributes: true,
				attributeFilter: [ 'data-product-qty' ], // only listen for qty changes
			} );
		} );
	}

	function init() {
		// starting from the top, find all our campaigns
		$( document )
			.find( '.revx-template' )
			.each( function () {
				// if this has any child .revx-template, return to avoid double calculation.
				if ( $( this ).children( '.revx-template' ).length ) {
					return;
				}
				// get offers/products for this campaign
				const $bundleDiscounts = $( this ).find(
					'[campaign_type="bundle_discount"]'
				);
				const $buyXGetYDiscounts = $( this ).find(
					'[campaign_type="buy_x_get_y"]'
				);
				const $fbtDiscounts = $( this ).find(
					'[campaign_type="frequently_bought_together"]'
				);
				if ( $bundleDiscounts.length ) {
					setBundleTotal( $bundleDiscounts );
				} else if ( $buyXGetYDiscounts.length ) {
					setBundleTotal( $buyXGetYDiscounts );
					observeQtyChnages( $buyXGetYDiscounts ); // need to handle quantity change for buy x get y
				} else if ( $fbtDiscounts.length ) {
					$fbtDiscounts.first().trigger( 'revx-fbt-init' );
					observeQtyChnages(
						$fbtDiscounts,
						'frequently_bought_together'
					);
				}
			} );
	}
	// Initialize on DOM ready.
	$( function () {
		init();
		// TODO: refactor later to only update specific campaign instead of all.
		$( document ).on( 'revx-variation-changed', function () {
			init();
		} );
	} );
} )( jQuery );
