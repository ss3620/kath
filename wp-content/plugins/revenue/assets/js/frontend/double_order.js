/* global revenue_campaign jQuery */
// the below line ignores revenue_campaign not camel case warning
/* eslint-disable camelcase */
( function ( $ ) {
	'use strict';

	window.revenueUtils = window.revenueUtils || {};
	const { getStyleMs } = window.revenueUtils;

	// Safe trigger helper
	const trigger = ( target, eventName, payload ) => {
		try {
			$( target ).trigger( eventName, payload );
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( `Failed to trigger ${ eventName }`, err );
		}
	};

	// update opacity and visibility
	function updateStyle( $container, curIndex ) {
		$container.find( '[data-double-order-item]' ).each( function () {
			const listIndex = $( this ).data( 'index' );
			if ( listIndex < curIndex ) {
				// introducing new class dimmed in css/frontend/campaign/double_order.css
				// intitial opacity 0.5. :hover opacity 1
				$( this ).addClass( 'revx-dimmed' ).removeClass( 'hidden' );
			} else if ( listIndex <= curIndex + 1 ) {
				$( this ).removeClass( 'revx-dimmed' ).removeClass( 'hidden' );
			} else {
				$( this ).addClass( 'hidden' );
			}
		} );
	}

	function resetHeaderMessage( $container ) {
		const $allLists = $container.find( '[data-double-order-item]' );
		const $allHeaders = $allLists.find(
			'[data-smart-tag="DoubleOrderMessage"]'
		);
		$allHeaders.text( function () {
			return $( this )
				.closest( '[data-default-message]' )
				.data( 'default-message' );
		} );
	}

	function updateSuccessMessage( $container, $checkbox ) {
		resetHeaderMessage( $container );
		const $headerDiv = $checkbox
			.closest( '[data-double-order-item]' )
			.find( '[data-smart-tag="DoubleOrderMessage"]' );
		// set success message on current header
		$headerDiv.text(
			$headerDiv
				.closest( '[data-success-message]' )
				.data( 'success-message' )
		);
	}
	// -----------------------------
	// 2) Double Order (single selection per index)
	// -----------------------------

	function runDoubleOrder( $container ) {
		let currentlyCheckedIndex = null;
		// wait for check box toggle event from main chechkbox-handler.js
		$container.on(
			'revx-checkbox-toggled',
			'.revx-checkbox-container',
			function () {
				doubleOrderToggleCheckbox( $( this ) );
			}
		);

		// working fine
		function initializeDoubleOrderCheckboxes() {
			const $selectedCheckbox = $container.find(
				'.revx-double-order-checkbox-specific[data-is-checked="yes"]'
			);

			if ( $selectedCheckbox.length ) {
				currentlyCheckedIndex = $selectedCheckbox.data( 'index' );

				updateSuccessMessage( $container, $selectedCheckbox );
				// update opacity and visibility
				updateStyle( $container, currentlyCheckedIndex );
			} else {
				// Show only first if none preselected
				updateStyle( $container, -1 ); // -1 : because this function shows current and next. so -1 will show the 0th index only
			}
		}

		initializeDoubleOrderCheckboxes();

		function doubleOrderToggleCheckbox( $checkbox ) {
			const index = $checkbox.data( 'index' );
			const isChecked = $checkbox.attr( 'data-is-checked' );

			if ( isChecked === 'yes' ) {
				// Uncheck previously checked checkbox if any (fixed selector)
				if ( currentlyCheckedIndex !== null ) {
					const $prevCheckbox = $container.find(
						`.revx-double-order-checkbox-specific[data-index="${ currentlyCheckedIndex }"]`
					);
					$prevCheckbox
						.attr( 'style', $prevCheckbox.data( 'default-style' ) )
						.data( 'is-checked', 'no' )
						.attr( 'data-is-checked', 'no' )
						.removeClass( 'revx-active' )
						.addClass( 'revx-inactive' );
				}

				currentlyCheckedIndex = index;
				// Reset headers to default, then set success on current
				updateSuccessMessage( $container, $checkbox );
				// update opacity and visibility till next index
				updateStyle( $container, currentlyCheckedIndex );
			} else {
				currentlyCheckedIndex = null;

				resetHeaderMessage( $container );
				updateStyle( $container, -1 ); // -1 : because this function shows current and next. so -1 will show the 0th index only
			}

			// Dispatch state change
			const payload = {
				index: currentlyCheckedIndex,
				multiplier:
					currentlyCheckedIndex !== null
						? $checkbox.data( 'multiplier' )
						: null,
				value:
					currentlyCheckedIndex !== null
						? $checkbox.data( 'value' )
						: null,
				campaign_id: $checkbox.data( 'campaign-id' ),
			};

			trigger(
				document,
				'revenue_double_order_checkbox_state_change',
				payload
			);
		}
	}

	// -----------------------------
	// 3) Grouped Order (multi select within a single group)
	// -----------------------------

	function runGroupedOrder( $container ) {
		let currentlyCheckedGroup = null;
		const selectedProducts = new Set();

		// wait for check box toggle event from main chechkbox-handler.js
		$container.on(
			'revx-checkbox-toggled',
			'.revx-checkbox-container',
			function () {
				groupedOrderToggleCheckbox( $( this ) );
			}
		);

		function initializeState() {
			const $selected = $container.find(
				'.revx-double-order-checkbox-specific[data-is-checked="yes"]'
			);

			if ( $selected.length ) {
				currentlyCheckedGroup = $selected
					.first()
					.closest( '[data-offer-group-index]' )
					.data( 'offer-group-index' );

				$selected.each( function () {
					const $cb = $( this );
					selectedProducts.add( $cb.data( 'product-id' ) );
				} );
				updateSuccessMessage( $container, $selected.first() );
				updateStyle( $container, currentlyCheckedGroup );
				triggerStateChangeEvent( $selected.first() );
			} else {
				// Show only the first group
				updateStyle( $container, -1 );
			}
		}

		function groupedOrderToggleCheckbox( $checkbox ) {
			const groupIndex = $checkbox
				.closest( '[data-offer-group-index]' )
				.data( 'offer-group-index' );
			const productId = $checkbox.data( 'product-id' );
			const isSelected = $checkbox.data( 'is-checked' ) === 'yes';

			if (
				currentlyCheckedGroup === null ||
				currentlyCheckedGroup === groupIndex
			) {
				if ( isSelected ) {
					selectedProducts.add( productId );
					if ( currentlyCheckedGroup === null ) {
						updateSuccessMessage( $container, $checkbox );
						updateStyle( $container, groupIndex );
					}
					currentlyCheckedGroup = groupIndex;
				} else {
					selectedProducts.delete( productId );

					if ( selectedProducts.size === 0 ) {
						currentlyCheckedGroup = null;
						updateStyle( $container, -1 );
						resetHeaderMessage( $container );
					}
				}
			} else {
				// Switching groups: clear previous group selection
				$container
					.find(
						`[data-offer-group-index="${ currentlyCheckedGroup }"] .revx-double-order-checkbox-specific`
					)
					.each( function () {
						const $cb = $( this );
						$cb.data( 'is-checked', 'no' )
							.attr( 'data-is-checked', 'no' )
							.removeClass( 'revx-active' )
							.addClass( 'revx-inactive' );
					} );

				selectedProducts.clear();
				currentlyCheckedGroup = groupIndex;
				selectedProducts.add( productId );
				updateStyle( $container, currentlyCheckedGroup );
				updateSuccessMessage( $container, $checkbox );
			}

			triggerStateChangeEvent( $checkbox );
		}

		function triggerStateChangeEvent( $checkbox ) {
			const payload = {
				groupIndex: currentlyCheckedGroup,
				selectedProducts: Array.from( selectedProducts ),
				multiplier:
					currentlyCheckedGroup !== null
						? $checkbox.data( 'multiplier' )
						: null,
				value:
					currentlyCheckedGroup !== null
						? $checkbox.data( 'value' )
						: null,
				campaign_id: $checkbox.data( 'campaign-id' ),
			};

			trigger(
				document,
				'revenue_grouped_order_checkbox_state_change',
				payload
			);
		}

		initializeState();
	}

	// -----------------------------
	// 4) Server sync + checkout refresh
	// -----------------------------

	function postRevenueOrderData( action, data, callback ) {
		// Guard: required globals
		if (
			typeof window.revenue_campaign === 'undefined' ||
			! revenue_campaign?.ajax ||
			! revenue_campaign?.nonce
		) {
			// eslint-disable-next-line no-console
			console.error( 'revenue_campaign config missing (ajax/nonce).' );
			if ( typeof callback === 'function' ) {
				callback();
			} // Fail open
			return;
		}

		$.post( revenue_campaign.ajax, {
			action: 'revenue_double_order_multiplier',
			...data,
			_wpnonce: revenue_campaign.nonce,
		} ).always( () => {
			if ( typeof callback === 'function' ) {
				callback();
			}
		} );
	}

	$( document ).on(
		'revenue_double_order_checkbox_state_change',
		function ( e, stateData ) {
			const data = {
				multiplier: stateData.multiplier,
				is_checked: stateData.index !== null ? 'yes' : 'no',
				campaign_id: stateData.campaign_id,
				index: stateData.index,
				product_id: 165, // No specific product for double order
			};

			postRevenueOrderData(
				'revenue_double_order_multiplier',
				data,
				function () {
					$( 'body' ).trigger( 'update_checkout' );
				}
			);
		}
	);

	// Grouped order event
	$( document ).on(
		'revenue_grouped_order_checkbox_state_change',
		function ( _e, stateData ) {
			const data = {
				multiplier: stateData.multiplier,
				is_checked: stateData.groupIndex !== null ? 'yes' : 'no',
				campaign_id: stateData.campaign_id,
				index: stateData.groupIndex,
				product_ids: stateData.selectedProducts,
			};

			postRevenueOrderData(
				'revenue_double_order_multiplier',
				data,
				function () {
					$( 'body' ).trigger( 'update_checkout' );
				}
			);
		}
	);

	// -----------------------------
	// 5) CSS-driven periodic animations
	// -----------------------------

	function initAnimations() {
		$( '[data-campaign-type="double_order"]' ).each( function () {
			const el = this;
			const delayBetween = getStyleMs(
				el,
				'--revx-double-order-animation-delay-between'
			);
			if ( ! Number.isFinite( delayBetween ) || delayBetween <= 0 ) {
				return;
			}

			const activeTime = getStyleMs( el, '--animation-active-time' );
			if ( ! Number.isFinite( activeTime ) || activeTime <= 0 ) {
				return;
			}

			$( el )
				.find(
					'.revx-double-order-animation-shake, .revx-double-order-animation-pulse, .revx-double-order-animation-tada, .revx-double-order-animation-bounce, .revx-double-order-animation-swing'
				)
				.each( function () {
					const $node = $( this );

					const getAnimClass = () =>
						( $node.attr( 'class' ) || '' )
							.split( ' ' )
							.find( ( cls ) =>
								cls.startsWith( 'revx-double-order-animation-' )
							) || '';

					const animationName = getAnimClass();
					const triggerAnimation = () => {
						$node.toggleClass( animationName, false ); // turn off the class to pause animation

						setTimeout( () => {
							$node.toggleClass( animationName, true ); // after passing dealy amount of time turn on the class
						}, delayBetween );
					};
					if ( animationName ) {
						// Staggered loop, hardcoded animation duration 5 second
						setInterval( triggerAnimation, 5000 + delayBetween );
					}
				} );
		} );
	}

	function initDoubleOrderCampaign() {
		$( '[data-campaign-type="double_order"]' ).each( function () {
			// find all the double order campaigns and call them accordingly
			if ( $( this ).data( 'is-all-product' ) === 1 ) {
				runDoubleOrder( $( this ) );
			} else {
				runGroupedOrder( $( this ) );
			}
		} );
	}

	// handles the case when woocommerce checkout updates
	function reApplyStyleAndHeaderMessage() {
		$( '[data-campaign-type="double_order"]' ).each( function () {
			// find all the double order campaigns and call them accordingly
			let $selectedCheckbox = $( this ).find(
				'.revx-double-order-checkbox-specific[data-is-checked="yes"]'
			);

			if ( $selectedCheckbox.length ) {
				// if the campaign is of grouped double order then there can be multiple checkobx selected
				// however we only care about the container of first selected cechckbox to update style and messaage
				if ( $selectedCheckbox.length > 1 ) {
					$selectedCheckbox = $selectedCheckbox.first();
				}
				const index = $selectedCheckbox.data( 'index' );

				updateSuccessMessage( $( this ), $selectedCheckbox );
				// update opacity and visibility
				updateStyle( $( this ), index );
			} else {
				// Show only first if none preselected
				updateStyle( $( this ), -1 ); // -1 : because this function shows current and next. so -1 will show the 0th index only
			}
		} );
	}

	// Initialize countdown timers on page load.
	$( function () {
		// countdownTimerInit( $( '.revx-countdown-timer-container' ) );

		initDoubleOrderCampaign();
		initAnimations();

		// tried to fix the issue of after cart reload
		// Re-run every time checkout updates (AJAX reload)
		$( document.body ).on( 'updated_checkout', function () {
			reApplyStyleAndHeaderMessage();
		} );
	} );
} )( jQuery );
