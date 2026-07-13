/* global revenue_campaign jQuery */
// the below line ignores revenue_campaign not camel case warning
/* eslint-disable camelcase */
( function ( $ ) {
	'use strict';

	const padWithZero = ( num ) => num.toString().padStart( 2, '0' );

	// Countdown Timer-----------------
	// Declaration
	//  This function handles countdown timer dynamic update for all campaigns
	const countdown = () => {
		try {
			if ( revenue_campaign ) {
				const countDownData = Object.keys( revenue_campaign.data );

				countDownData.forEach( ( campaignID ) => {
					const _data =
						revenue_campaign?.data?.[ campaignID ]?.countdown_data;

					const startTime = _data?.start_time
						? new Date( _data.start_time ).getTime()
						: null;
					const endTime = _data?.end_time
						? new Date( _data.end_time ).getTime()
						: null;
					let now = new Date().getTime();

					// Skip if the campaign hasn't started yet
					// Skip if the campaign has already ended
					if (
						! $( `#revx-countdown-timer-${ campaignID }` ).length ||
						( startTime && startTime > now ) ||
						endTime < now
					) {
						return;
					}

					// Function to update the countdown timer
					const updateCountdown = () => {
						now = new Date().getTime();
						const distance = endTime - now;

						if ( distance < 0 ) {
							clearInterval( interval );
							$(
								`[data-countdown-timer-container][data-campaign-id=${ campaignID }]`
							).addClass( 'revx-d-none' );
							return;
						}

						// Calculate days, hours, minutes, and seconds
						const units = {
							days: Math.floor(
								distance / ( 1000 * 60 * 60 * 24 )
							),
							hours: Math.floor(
								( distance % ( 1000 * 60 * 60 * 24 ) ) /
									( 1000 * 60 * 60 )
							),
							minutes: Math.floor(
								( distance % ( 1000 * 60 * 60 ) ) /
									( 1000 * 60 )
							),
							seconds: Math.floor(
								( distance % ( 1000 * 60 ) ) / 1000
							),
						};
						// Update the HTML elements
						for ( const unit in units ) {
							$(
								`#revx-countdown-timer-${ campaignID } .revx-${ unit }`
							).text( padWithZero( units[ unit ] ) );
						}
						// hide days if 0, hide hours if days and hours both are 0
						[ 'days', 'hours' ].every(
							( unit ) =>
								units[ unit ] === 0 &&
								$(
									`#revx-countdown-timer-${ campaignID } .revx-${ unit },
									#revx-countdown-timer-${ campaignID } .revx-${ unit }-colon`
								).addClass( 'revx-d-none' )
						);
					};
					// Call the updateCountdown function initially to set the first values
					updateCountdown();

					// Update the countdown every second
					const interval = setInterval( updateCountdown, 1000 ); // using recursion instead of interval, works the same

					// Show the countdown timer only after the initial values are set
					$( `#revx-countdown-timer-${ campaignID }` ).removeClass(
						'revx-d-none'
					);
				} );
			}
		} catch ( error ) {
			// log error
		}
	};
	// Call
	countdown();
} )( jQuery );
