jQuery( document ).ready( function ( $ ) {
	// TODO: This function needs refactoring to make more DRY.
	// Handle animation with delay cycles
	$( '.revx-btn-animation' ).each( function () {
		const $button = $( this );
		const animationDelay = $button.data( 'animation-delay' );
		let animationDuration = animationDelay === 0 ? 0.8 : 1.5; // Fixed duration for animation cycle

		// handle countdown timer campaign animation 
		if( $button.data('animate-campaign') === 'countdown_timer' ) {
			animationDuration = $button.data('animation-duration');
		}

		const triggerType = $button.attr( 'data-animated-triggered-type' );
		const isHoverTriggered = triggerType === 'on_hover';

		// Set initial animation duration
		$button.css( 'animation-duration', `${ animationDuration }s` );

		// Store original animation classes (detect all animation-specific classes)
		const allClasses = $button.attr( 'class' ).split( ' ' );
		const animationClasses = allClasses.filter(
			( className ) =>
				className.startsWith( 'revx-btn-' ) &&
				className !== 'revx-btn-animation' &&
				! className.includes( 'uuid' )
		);

		if ( isHoverTriggered ) {
			// Handle hover-triggered animations
			// Remove animation classes initially (no animation until hover)
			$button.removeClass( animationClasses.join( ' ' ) );

			let hoverInterval;
			let hoverTimeout;

			// Add hover event listeners
			$button.on( 'mouseenter', function () {
				const $this = $( this );

				if ( animationDelay && animationDelay > 0 ) {
					// If delay is specified, create cycling animation while hovering
					const delayMs = animationDelay * 1000;
					const animationDurationMs = animationDuration * 1000;
					const totalCycleTime = animationDurationMs + delayMs;

					// Function to cycle animation by toggling classes
					const cycleHoverAnimation = () => {
						// Remove animation classes to stop animation
						$this.removeClass( animationClasses.join( ' ' ) );

						// Add animation classes back after delay to restart animation
						hoverTimeout = setTimeout( () => {
							$this.addClass( animationClasses.join( ' ' ) );
						}, delayMs );
					};

					// Start first animation immediately
					$this.addClass( animationClasses.join( ' ' ) );

					// Set up recurring cycle while hovering
					hoverInterval = setInterval(
						cycleHoverAnimation,
						totalCycleTime
					);
				} else {
					// No delay, start animation immediately and keep it running
					$this.addClass( animationClasses.join( ' ' ) );
				}
			} );

			$button.on( 'mouseleave', function () {
				const $this = $( this );

				// Clear any pending timeout and interval
				if ( hoverTimeout ) {
					clearTimeout( hoverTimeout );
					hoverTimeout = null;
				}
				if ( hoverInterval ) {
					clearInterval( hoverInterval );
					hoverInterval = null;
				}

				// Remove animation classes immediately on mouse leave
				$this.removeClass( animationClasses.join( ' ' ) );
			} );
		} else if ( animationDelay && animationDelay > 0 ) {
			// Handle automatic cycling animations
			// Convert delay from seconds to milliseconds
			const delayMs = animationDelay * 1000;
			const animationDurationMs = animationDuration * 1000;

			// Function to cycle animation by toggling classes
			const cycleAnimation = () => {
				// Remove animation classes to stop animation
				$button.removeClass( animationClasses.join( ' ' ) );

				// Add animation classes back after delay to restart animation
				setTimeout( () => {
					$button.addClass( animationClasses.join( ' ' ) );
				}, delayMs );
			};

			// Start the cycling interval
			// Total cycle time = animation duration + delay
			const totalCycleTime = animationDurationMs + delayMs;

			// Initial state - start with delay
			setTimeout( () => {
				$button.addClass( animationClasses.join( ' ' ) );

				// Set up recurring cycle
				setInterval( cycleAnimation, totalCycleTime );
			}, delayMs );

			// Remove animation classes initially
			$button.removeClass( animationClasses.join( ' ' ) );
		}
	} );

	// Legacy support for old data-animation-trigger-type attribute
	$( document ).on(
		{
			mouseenter() {
				$( this ).addClass( $( this ).data( 'animation-class' ) );
			},
			mouseleave() {
				$( this ).removeClass( $( this ).data( 'animation-class' ) );
			},
		},
		'.revx-btn-animation[data-animation-trigger-type=on_hover]'
	);
} );
