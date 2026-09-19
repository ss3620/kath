jQuery( document ).ready( function ( $ ) {
	function adjustStickyStack() {
		let offset = 0;

		// 1. WP Admin Bar
		const wpAdminBar = document.getElementById( 'wpadminbar' );
		if ( wpAdminBar ) {
			offset += wpAdminBar.offsetHeight;
		}

		// 2. WC Install banner
		const wcInstall = document.querySelector( '.revx-wc-install' );
		if ( wcInstall && wcInstall.offsetParent !== null ) {
			wcInstall.style.top = offset + 'px';
			wcInstall.style.opacity = 1; // reveal smoothly
			offset += wcInstall.offsetHeight;
		}

		// 3. Other notices (can be multiple)
		const notices = document.querySelectorAll( '.revx-setting-hellobar' );
		notices.forEach( ( notice ) => {
			// parents are nulll since child has fixed position. removed parent check.
			if ( notice ) {
				notice.style.position = 'fixed';
				notice.style.top = offset + 'px';
				notice.style.opacity = 1; // reveal smoothly
				offset += notice.offsetHeight;
			}
		} );

		// 4. Navbar
		const navbar = document.querySelector( '.revx-nav.revx-nav-wrapper' );
		if ( navbar ) {
			navbar.style.top = offset + 'px';
			navbar.style.opacity = 1; // reveal smoothly
		}
	}

	adjustStickyStack();
	const observer = new MutationObserver( adjustStickyStack );

	observer.observe( document.body, {
		childList: true,
		subtree: true,
	} );

	$( '#revx-activate-woocommerce' ).on( 'click', function ( e ) {
		e.preventDefault();
		const $button = $( this );
		$button.removeClass( 'installing' ).addClass( 'activating' );
		$button
			.text( 'Activating...' )
			.append( '<span class="spinner"></span>' );
		$.ajax( {
			url: revenue?.ajax,
			type: 'POST',
			data: {
				action: 'revx_activate_woocommerce',
				security: revenue?.nonce,
			},
			success: function ( response ) {
				$button.removeClass( 'activating' );
				$button.find( '.spinner' ).hide();
				if ( response.success ) {
					location.reload();
				} else {
					console.log( response.data );
				}
			},
			error: function () {
				$button.removeClass( 'activating' );
				$button.find( '.spinner' ).hide();
				console.log( 'There was an error activating WooCommerce.' );
			},
		} );
	} );

	$( '#revx-install-woocommerce' ).on( 'click', function ( e ) {
		e.preventDefault();
		const $button = $( this );
		$button.addClass( 'installing' );
		$button.find( '.spinner' ).show();
		$button
			.text( 'Installing...' )
			.append( '<span class="spinner"></span>' );
		$.ajax( {
			url: revenue?.ajax,
			type: 'POST',
			data: {
				action: 'revx_install_woocommerce',
				security: revenue?.nonce,
			},
			success: function ( response ) {
				if ( response.success ) {
					$button
						.removeClass( 'installing' )
						.addClass( 'activating' );
					$button
						.text( 'Activating...' )
						.append( '<span class="spinner"></span>' );
					$.ajax( {
						url: revenue?.ajax,
						type: 'POST',
						data: {
							action: 'revx_activate_woocommerce',
							security: revenue?.nonce,
						},
						success: function ( response ) {
							$button.removeClass( 'activating' );
							$button.find( '.spinner' ).hide();
							if ( response.success ) {
								location.reload();
							} else {
								console.log( response.data );
							}
						},
						error: function () {
							$button.removeClass( 'activating' );
							$button.find( '.spinner' ).hide();
							console.log(
								'There was an error activating WooCommerce.'
							);
						},
					} );
				} else {
					$button.removeClass( 'installing' );
					$button.find( '.spinner' ).hide();
					console.log( response.data );
				}
			},
			error: function () {
				$button.removeClass( 'installing' );
				$button.find( '.spinner' ).hide();
				console.log( 'There was an error installing WooCommerce.' );
			},
		} );
	} );
} );
