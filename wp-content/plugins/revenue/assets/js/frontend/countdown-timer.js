/* eslint-disable no-undef */
jQuery( document ).ready( function ( $ ) {
	function initializeCountdownTimer() {
		const isRTL =
			$( 'html' ).attr( 'dir' ) === 'rtl' ||
			$( 'body' ).attr( 'dir' ) === 'rtl' ||
			$( 'html' ).css( 'direction' ) === 'rtl' ||
			$( 'body' ).css( 'direction' ) === 'rtl';

		$( '.revx-countdown-timer-p-wrapper' ).each( function () {
			const $this = $( this );
			const campaignId = $( this ).attr( 'data-campaign-id' );
			let countDownTimerData = $(
				`input[name="revx-countdown-data-${ campaignId }"]`
			);
			countDownTimerData = countDownTimerData[ 0 ].value;
			const config = JSON.parse( countDownTimerData );

			// Elements.
			const $flashSaleContainer = $this.find(
				'.revx-product-cdt-container'
			); // Product page
			let $bannerContainer = $this.find( '.revx-banner-cdt-container' ); // Banner
			const $rvexShopCountdown = $this.find( '.revx-shop-cdt-container' ); // Shop Page
			const $rvexCartCountdown = $this.find( '.revx-cart-cdt-container' ); // Cart Page

			const $daysElement = $this.find( '.revx-product-days' );
			const $hoursElement = $this.find( '.revx-product-hours' );
			const $minutesElement = $this.find( '.revx-product-minutes' );
			const $secondsElement = $this.find( '.revx-product-seconds' );
			//cart Degit
			const $daysCartElement = $this.find( '.revx-cart-days' );
			const $hoursCartElement = $this.find( '.revx-cart-hours' );
			const $minutesCartElement = $this.find( '.revx-cart-minutes' );
			const $secondsCartElement = $this.find( '.revx-cart-seconds' );
			//shop Degit
			const $daysShopElement = $this.find( '.revx-shop-days' );
			const $hoursShopElement = $this.find( '.revx-shop-hours' );
			const $minutesShopElement = $this.find( '.revx-shop-minutes' );
			const $secondsShopElement = $this.find( '.revx-shop-seconds' );

			const $rvexProgressContainer = $this.find(
				'.revx-countdown-progress-container'
			);
			const $progressBar = $this.find(
				'.revx-progress-bar, .revx-stock-bar'
			);
			const $rvexProgressBarIcon = $this.find(
				'.revx-progress-bar-icon'
			);

			$bannerContainer = $this.find( '.revx-countdown-timer-wrapper' );
			const halloBerDisplay = $(
				'.revx-countdown-timer-hellobar-wrapper'
			);
			const $closeButton = $this.find( '.revx-campaign-close' );
			const $daysTop = $this.find( '.revx-banner-days' );
			const $hoursTop = $this.find( '.revx-banner-hours' );
			const $minutesTop = $this.find( '.revx-banner-minutes' );
			const $secondsTop = $this.find( '.revx-banner-seconds' );
			let isCloseButtonClick = false;
			let endDateTime = localStorage.getItem(
				`rvexEndDateTime-${ campaignId }`
			);
			let startDateTime = localStorage.getItem(
				`rvexStartDateTime-${ campaignId }`
			);

			function calculateEndDateTime() {
				startDateTime = config.modifiedDateTime
					? new Date( config.modifiedDateTime ).getTime()
					: new Date().getTime();
				localStorage.setItem(
					`rvexStartDateTime-${ campaignId }`,
					startDateTime
				); // Store it persistently

				// Calculate endDateTime based on user input
				const days = parseInt( config.evergreenDays ) || 0;
				const hours = parseInt( config.evergreenHours ) || 0;
				const minutes = parseInt( config.evergreenMinutes ) || 0;
				const seconds = parseInt( config.evergreenSeconds ) || 0;

				endDateTime = new Date(
					startDateTime +
						days * 24 * 60 * 60 * 1000 +
						hours * 60 * 60 * 1000 +
						minutes * 60 * 1000 +
						seconds * 1000
				).getTime();
				localStorage.setItem(
					`rvexEndDateTime-${ campaignId }`,
					endDateTime
				); // Store it persistently
			}

			function updateLocalStorage() {
				const storedConfig =
					JSON.parse(
						localStorage.getItem(
							`rvexEvergreenConfig-${ campaignId }`
						)
					) || {};

				if (
					storedConfig.evergreenDays !== config.evergreenDays ||
					storedConfig.evergreenHours !== config.evergreenHours ||
					storedConfig.evergreenMinutes !== config.evergreenMinutes ||
					storedConfig.evergreenSeconds !== config.evergreenSeconds
				) {
					// Update localStorage values
					localStorage.setItem(
						`rvexEvergreenConfig-${ campaignId }`,
						JSON.stringify( {
							evergreenDays: config.evergreenDays,
							evergreenHours: config.evergreenHours,
							evergreenMinutes: config.evergreenMinutes,
							evergreenSeconds: config.evergreenSeconds,
						} )
					);

					// Clear previous start and end times
					localStorage.removeItem(
						`rvexStartDateTime-${ campaignId }`
					);
					localStorage.removeItem(
						`rvexEndDateTime-${ campaignId }`
					);

					// Recalculate endDateTime
					calculateEndDateTime();
				}
			}

			function handleDailyMode() {
				const now = new Date();
				const currentTime = now.getHours() * 60 + now.getMinutes(); // Current time in minutes
				localStorage.removeItem( `rvexStartDateTime-${ campaignId }` ); // Remove the stored start time
				localStorage.removeItem( `rvexEndDateTime-${ campaignId }` ); // Remove the stored end time
				// Also clear in-memory values to avoid using stale timestamps from previous page loads
				startDateTime = NaN;
				endDateTime = NaN;
				for ( const slot of config.dailyTimeSlots ) {
					const [ startHour, startMinute ] = slot.startTime
						.split( ':' )
						.map( Number );
					const [ endHour, endMinute ] = slot.endTime
						.split( ':' )
						.map( Number );

					const startTime = startHour * 60 + startMinute;
					const endTime = endHour * 60 + endMinute;

					if (
						startTime < endTime &&
						currentTime >= startTime &&
						currentTime <= endTime
					) {
						startDateTime = new Date(
							now.setHours( startHour, startMinute, 0, 0 )
						).getTime();
						endDateTime = new Date(
							now.setHours( endHour, endMinute, 0, 0 )
						).getTime();
						localStorage.setItem(
							`rvexStartDateTime-${ campaignId }`,
							startDateTime
						);
						localStorage.setItem(
							`rvexEndDateTime-${ campaignId }`,
							endDateTime
						);
						return;
					}
				}

				// If no valid slot found, hide the timer
				$flashSaleContainer.hide();
				$rvexShopCountdown.hide();
				$rvexCartCountdown.hide();
				$rvexProgressContainer.hide();
				$bannerContainer.hide();
				halloBerDisplay.attr( 'data-display', 'false' );
				// Ensure validation later treats the range as invalid
				startDateTime = NaN;
				endDateTime = NaN;
				// $( '.revx-countdown-timer-wrapper' ).css( 'display', 'none' );
			}

			function handleWeekdaysMode() {
				const now = new Date();
				const currentDay = now.getDay(); // 0 (Sunday) to 6 (Saturday)
				localStorage.removeItem( `rvexStartDateTime-${ campaignId }` ); // Remove the stored start time
				localStorage.removeItem( `rvexEndDateTime-${ campaignId }` ); // Remove the stored end time
				// Also clear in-memory values to avoid using stale timestamps from previous page loads
				startDateTime = NaN;
				endDateTime = NaN;
				const daysOfWeek = [
					'sundaySlot',
					'mondaySlot',
					'tuesdaySlot',
					'wednesdaySlot',
					'thursdaySlot',
					'fridaySlot',
					'saturdaySlot',
				];
				const daySlot = daysOfWeek[ currentDay ];

				if (
					config[
						`is${
							daySlot.charAt( 0 ).toUpperCase() +
							daySlot.slice( 1 )
						}`
					] === 'yes' &&
					config.weeklyTimeSlots[ daySlot ]
				) {
					const currentTime = now.getHours() * 60 + now.getMinutes(); // Current time in minutes

					for ( const slot of config.weeklyTimeSlots[ daySlot ] ) {
						const [ startHour, startMinute ] = slot.startTime
							.split( ':' )
							.map( Number );
						const [ endHour, endMinute ] = slot.endTime
							.split( ':' )
							.map( Number );

						const startTime = startHour * 60 + startMinute;
						const endTime = endHour * 60 + endMinute;

						if (
							startTime < endTime &&
							currentTime >= startTime &&
							currentTime <= endTime
						) {
							startDateTime = new Date(
								now.setHours( startHour, startMinute, 0, 0 )
							).getTime();
							endDateTime = new Date(
								now.setHours( endHour, endMinute, 0, 0 )
							).getTime();
							localStorage.setItem(
								`rvexStartDateTime-${ campaignId }`,
								startDateTime
							);
							localStorage.setItem(
								`rvexEndDateTime-${ campaignId }`,
								endDateTime
							);
							return;
						}
					}
				} else {
					localStorage.removeItem(
						`rvexStartDateTime-${ campaignId }`
					); // Remove the stored start time
					localStorage.removeItem(
						`rvexEndDateTime-${ campaignId }`
					); // Remove the stored end time
				}
				// If no valid slot found, hide the timer
				$flashSaleContainer.hide();
				$rvexShopCountdown.hide();
				$rvexCartCountdown.hide();
				$rvexProgressContainer.hide();
				$bannerContainer.hide();
				halloBerDisplay.attr( 'data-display', 'false' );
				// Ensure validation later treats the range as invalid
				startDateTime = NaN;
				endDateTime = NaN;
				// $( '.revx-countdown-timer-wrapper' ).css( 'display', 'none' );
			}

			if ( config.countdownTimerType === 'evergreen' ) {
				// Evergreen countdown type
				updateLocalStorage();
				if ( ! endDateTime ) {
					calculateEndDateTime();
				} else {
					endDateTime = parseInt( endDateTime );
					startDateTime = config.modifiedDateTime
						? new Date( config.modifiedDateTime ).getTime()
						: new Date().getTime();
					localStorage.setItem(
						`rvexStartDateTime-${ campaignId }`,
						startDateTime
					);
				}
			} else if (
				config.countdownTimerType === 'dailyRecurring' &&
				config.setSelectMode === 'dailyMode'
			) {
				localStorage.removeItem(
					`rvexEvergreenConfig-${ campaignId }`
				);
				handleDailyMode();
			} else if (
				config.countdownTimerType === 'dailyRecurring' &&
				config.setSelectMode === 'weekdaysMode'
			) {
				localStorage.removeItem(
					`rvexEvergreenConfig-${ campaignId }`
				);
				handleWeekdaysMode();
			} else {
				// Static countdown type
				endDateTime = new Date( config.endDateTime ).getTime();

				if ( ! startDateTime ) {
					startDateTime = new Date( config.startDateTime ).getTime();
					localStorage.setItem(
						`rvexStartDateTime-${ campaignId }`,
						startDateTime
					); // Store it persistently
				} else {
					startDateTime = parseInt( startDateTime );
				}

				if ( config.timeFrameMode === 'startNow' ) {
					startDateTime = new Date(
						config.modifiedDateTime
					).getTime();
					localStorage.setItem(
						`rvexStartDateTime-${ campaignId }`,
						startDateTime
					); // Store it persistently
				}
			}

			const totalDuration = endDateTime - startDateTime;

			// Apply initial progress (100 → 0) immediately so users
			try {
				if ( startDateTime && endDateTime ) {
					const now = new Date().getTime();
					const remainingTime = endDateTime - now;
					const progress = Math.min(
						( remainingTime / totalDuration ) * 100,
						100
					);
					$progressBar.css( 'width', progress + '%' );
					const position = isRTL ? 'right' : 'left';
					$rvexProgressBarIcon.css(
						position,
						'calc(' +
							progress +
							'% - (var(--revx-progress-height, 8px) * 2.7))'
					);
				}
			} catch ( e ) {
				// ignore
			}

			// Validate the date range
			if (
				isNaN( startDateTime ) ||
				isNaN( endDateTime ) ||
				startDateTime >= endDateTime
			) {
				if ( config.timerEndBehavior === 'hideTimer' ) {
					$flashSaleContainer.hide();
					$rvexShopCountdown.hide();
					$rvexCartCountdown.hide();
					$rvexProgressContainer.hide();
					$bannerContainer.hide();
					halloBerDisplay.attr( 'data-display', 'false' );
				} else {
					$flashSaleContainer.show();
					$rvexShopCountdown.show();
					$rvexCartCountdown.show();
					$rvexProgressContainer.show();
					$bannerContainer.show();
					$( '.revx-countdown-timer-wrapper' ).css( 'display', '' );
				}
			} else {
				// Start countdown
				const updateCountdown = setInterval( function () {
					const now = new Date().getTime(); // Get current local time
					const remainingTime = endDateTime - now;

					// If countdown hasn't started yet
					if ( now < startDateTime ) {
						$flashSaleContainer.hide();
						$bannerContainer.hide();
						halloBerDisplay.attr( 'data-display', 'false' );
					}
					// If countdown is active
					else if ( now >= startDateTime && now <= endDateTime ) {
						const distance = endDateTime - now;
						const days = Math.floor(
							distance / ( 1000 * 60 * 60 * 24 )
						);
						const hours = Math.floor(
							( distance % ( 1000 * 60 * 60 * 24 ) ) /
								( 1000 * 60 * 60 )
						);
						const minutes = Math.floor(
							( distance % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 )
						);
						const seconds = Math.floor(
							( distance % ( 1000 * 60 ) ) / 1000
						);

						if ( config.timerEndBehavior === 'hideTimer' ) {
							$flashSaleContainer.hide();
							$rvexShopCountdown.hide();
							$rvexCartCountdown.hide();
							$bannerContainer.hide();
							halloBerDisplay.attr( 'data-display', 'false' );
						} else if (
							config.currentPage === 'product_page' &&
							config.isProductPageEnable === 'yes'
						) {
							// Just show zeros.
							$daysElement.text( '00' );
							$hoursElement.text( '00' );
							$minutesElement.text( '00' );
							$secondsElement.text( '00' );
						}

						// Update the timer display.
						if (
							config.currentPage === 'product_page' &&
							config.isProductPageEnable === 'yes'
						) {
							$flashSaleContainer.show();
							$daysElement.text(
								String( days ).padStart( 2, '0' )
							);
							$hoursElement.text(
								String( hours ).padStart( 2, '0' )
							);
							$minutesElement.text(
								String( minutes ).padStart( 2, '0' )
							);
							$secondsElement.text(
								String( seconds ).padStart( 2, '0' )
							);

							if ( config.isShopPageEnable === 'yes' ) {
								// Shop countdown timer For Related Product
								$rvexShopCountdown.show();
								$rvexProgressContainer.show();
							}
						} else if (
							config.currentPage === 'shop_page' &&
							config.isShopPageEnable === 'yes'
						) {
							// Shop countdown timer
							$rvexShopCountdown.show();
							$rvexProgressContainer.show();
							$daysShopElement.text(
								String( days ).padStart( 2, '0' )
							);
							$hoursShopElement.text(
								String( hours ).padStart( 2, '0' )
							);
							$minutesShopElement.text(
								String( minutes ).padStart( 2, '0' )
							);
							$secondsShopElement.text(
								String( seconds ).padStart( 2, '0' )
							);
						} else if (
							config.currentPage === 'cart_page' &&
							config.isCartPageEnable === 'yes'
						) {
							// Cart countdown timer
							$rvexCartCountdown.show();
							$rvexProgressContainer.show();
							$daysCartElement.text(
								String( days ).padStart( 2, '0' )
							);
							$hoursCartElement.text(
								String( hours ).padStart( 2, '0' )
							);
							$minutesCartElement.text(
								String( minutes ).padStart( 2, '0' )
							);
							$secondsCartElement.text(
								String( seconds ).padStart( 2, '0' )
							);
						}

						// Update progress (100 → 0) for any displayed progress bar/icon
						try {
							if ( startDateTime && endDateTime ) {
								const progress = Math.min(
									( remainingTime / totalDuration ) * 100,
									100
								);
								$progressBar.css( 'width', progress + '%' );
								const position = isRTL ? 'right' : 'left';
								$rvexProgressBarIcon.css(
									position,
									'calc(' +
										progress +
										'% - (var(--revx-progress-height, 8px) * 2.7))'
								);
							}
						} catch ( e ) {
							// ignore
						}

						// Top bar countdown
						if ( config.isAllPageEnable === 'yes' ) {
							// Home page countdown timer top bar countdown
							if ( ! isCloseButtonClick ) {
								$bannerContainer.show();
								$( '.revx-countdown-timer-wrapper' ).css(
									'display',
									''
								);
							}
							$daysTop.text( String( days ).padStart( 2, '0' ) );
							$hoursTop.text(
								String( hours ).padStart( 2, '0' )
							);
							$minutesTop.text(
								String( minutes ).padStart( 2, '0' )
							);
							$secondsTop.text(
								String( seconds ).padStart( 2, '0' )
							);

							// Close button action
							if ( $closeButton.length ) {
								$closeButton.on( 'click', function () {
									//$bannerContainer.hide();
									$( '#revx-countdown-bottom' ).css(
										'margin-bottom',
										'-1000px'
									);
									halloBerDisplay.attr(
										'data-display',
										'false'
									);
									isCloseButtonClick = true;
								} );
							}
						}
					}
					// If countdown has expired
					else {
						clearInterval( updateCountdown );
						localStorage.removeItem(
							`rvexStartDateTime-${ campaignId }`
						); // Remove the stored start time
						localStorage.removeItem(
							`rvexEndDateTime-${ campaignId }`
						); // Remove the stored end time

						if ( config.isRepeatAfterFinished === 'yes' ) {
							// Restart the countdown
							startDateTime = new Date().getTime();
							localStorage.setItem(
								`rvexStartDateTime-${ campaignId }`,
								startDateTime
							);

							if ( config.countdownTimerType === 'evergreen' ) {
								calculateEndDateTime();
							} else {
								endDateTime = new Date(
									config.endDateTime
								).getTime();
							}

							updateCountdown();
						}
					}
				}, 1000 );
			}

			// Action on banner click
			const $banner = $this.find( '.revx-banner-countdown-content' );
			if (
				$banner.length &&
				config.actionType === 'makeFullAction' &&
				config.actionEnable === 'yes' &&
				config.currentPage !== ''
			) {
				$banner.css( 'cursor', 'pointer' ); // Change cursor to indicate it's clickable
				$banner.on( 'click', function () {
					window.location.href = config.actionLink;
				} );
			}
		} );
	}

	// Initialize countdown timer on page load.
	initializeCountdownTimer();

	// Reinitialize countdown timer after cart update via AJAX.
	$( document.body ).on( 'updated_cart_totals', function () {
		initializeCountdownTimer();
	} );
	$( document ).on( 'submit', 'form.cart', function () {
		const currentPage = $( '#wsx_current_page' ).val();
		$( '<input>' )
			.attr( {
				type: 'hidden',
				name: 'wsx_current_page',
				value: currentPage,
			} )
			.appendTo( this );
	} );

	function adjustContentMarginTop() {
		const helloBar = $( '#revx-countdown-top' );

		// Check if hello bar exists
		if ( helloBar.length === 0 ) {
			return;
		}
		const halloBerDisplay = $(
			'.revx-countdown-timer-hellobar-wrapper'
		).data( 'display' );
		const helloBarHeight = helloBar.outerHeight();

		// Only adjust if we have a valid height
		if (
			( helloBarHeight &&
				$( '.revx-countdown-timer-wrapper' ).css( 'display' ) ===
					'block' ) ||
			halloBerDisplay
		) {
			$( 'body' ).css( 'margin-top', helloBarHeight );

			// Remove margin-bottom as it's causing double spacing
			helloBar.css( 'margin-top', -helloBarHeight );
		}
	}

	adjustContentMarginTop();
} );

function handleResponsiveDisplay() {
	const width = window.innerWidth;

	//jQuery( '.revx-countdown-timer-wrapper' ).css( 'display', '' );

	if ( width <= 767 ) {
		// Mobile
		jQuery( '.revx-countdown-mobile' ).css( 'display', '' );
		jQuery( '.revx-countdown-timer-hellobar-wrapper' ).css(
			'display',
			'none'
		);
	} else if ( width > 767 && width <= 1024 ) {
		// Tablet
		jQuery( '.revx-countdown-timer-hellobar-wrapper' ).css( 'display', '' );
		jQuery( '.revx-countdown-mobile' ).css( 'display', 'none' );
	} else {
		// Desktop
		if (
			jQuery( '.revx-countdown-timer-wrapper' ).css( 'display' ) ===
			'block'
		) {
			jQuery( '.revx-countdown-timer-hellobar-wrapper' ).css(
				'display',
				''
			);
		}
		jQuery( '.revx-countdown-mobile' ).css( 'display', 'none' );
	}
}

// Run once after page load
document.addEventListener( 'readystatechange', ( event ) => {
	if ( event.target.readyState === 'complete' ) {
		handleResponsiveDisplay();
	}
} );

// Run again on window resize
window.addEventListener( 'resize', handleResponsiveDisplay );
