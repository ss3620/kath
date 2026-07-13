// eslint-disable-next-line no-unused-vars
( function ( $ ) {
	const toInt = ( v, base = 10 ) => {
		const n = parseInt( v, base );
		return Number.isFinite( n ) ? n : NaN;
	};

	const toFloat = ( v ) => {
		const n = parseFloat( v );
		return Number.isFinite( n ) ? n : NaN;
	};

	const pad2 = ( num ) => ( num < 10 ? `0${ num }` : `${ num }` );

	const getStyleMs = ( el, varName ) => {
		const raw = getComputedStyle( el ).getPropertyValue( varName );
		const s = toFloat( raw );
		return Number.isFinite( s ) ? s * 1000 : NaN;
	};

	// const countdownTimerInit = ( $container ) => {
	// 	return;
	// 	const duration =
	// 		parseInt( $container.data( 'countdown-duration' ), 10 ) || 0;
	// 	const startTime =
	// 		parseInt( $container.data( 'start-time' ), 10 ) || Date.now();
	// 	const endTime = startTime + duration * 1000;

	// 	// Elements
	// 	const $days = $container.find( '.revx-days' );
	// 	const $daysLabel = $container.find( '.revx-days-label' );

	// 	const $hours = $container.find( '.revx-hours' );
	// 	const $hoursLabel = $container.find( '.revx-hours-label' );

	// 	const $minutes = $container.find( '.revx-minutes' );
	// 	const $minutesLabel = $container.find( '.revx-minutes-label' );

	// 	const $seconds = $container.find( '.revx-seconds' );

	// 	function update() {
	// 		const now = Date.now();
	// 		const remaining = endTime - now;

	// 		if ( remaining <= 0 ) {
	// 			// Timer finished → show zeros, hide non-essential units
	// 			$days.hide();
	// 			$daysLabel.hide();
	// 			$hours.hide();
	// 			$hoursLabel.hide();
	// 			$minutes.hide();
	// 			$minutesLabel.hide();
	// 			$seconds.text( '00' ).show();
	// 			clearInterval( interval );
	// 			return;
	// 		}

	// 		let totalSeconds = Math.floor( remaining / 1000 );
	// 		const days = Math.floor( totalSeconds / 86400 );
	// 		totalSeconds %= 86400;

	// 		const hours = Math.floor( totalSeconds / 3600 );
	// 		totalSeconds %= 3600;

	// 		const minutes = Math.floor( totalSeconds / 60 );
	// 		const seconds = totalSeconds % 60;

	// 		// Days
	// 		if ( days > 0 ) {
	// 			$days.show().text( days.toString().padStart( 2, '0' ) );
	// 			$daysLabel.show();
	// 		} else {
	// 			$days.hide();
	// 			$daysLabel.hide();
	// 		}

	// 		// Hours (show if hours exist OR days exist)
	// 		if ( hours > 0 || days > 0 ) {
	// 			$hours.show().text( hours.toString().padStart( 2, '0' ) );
	// 			$hoursLabel.show();
	// 		} else {
	// 			$hours.hide();
	// 			$hoursLabel.hide();
	// 		}

	// 		// Minutes (show if minutes exist OR hours/days exist)
	// 		if ( minutes > 0 || hours > 0 || days > 0 ) {
	// 			$minutes.show().text( minutes.toString().padStart( 2, '0' ) );
	// 			$minutesLabel.show();
	// 		} else {
	// 			$minutes.hide();
	// 			$minutesLabel.hide();
	// 		}

	// 		// Seconds always shown
	// 		$seconds.show().text( seconds.toString().padStart( 2, '0' ) );
	// 	}

	// 	const interval = setInterval( update, 1000 );
	// };

	window.revenueUtils = {
		toFloat,
		toInt,
		pad2,
		getStyleMs,
		// countdownTimerInit,
	};
	// eslint-disable-next-line no-undef
} )( jQuery );
