/* global jQuery */
jQuery( function ( $ ) {
	function toggleCheckbox( $checkbox ) {
		const isChecked = $checkbox.attr( 'data-is-checked' );

		if ( isChecked === 'yes' ) {
			$checkbox
				.data( 'is-checked', 'no' )
				.attr( 'data-is-checked', 'no' )
				.removeClass( 'revx-active' )
				.addClass( 'revx-inactive' );
		} else {
			$checkbox
				.data( 'is-checked', 'yes' )
				.attr( 'data-is-checked', 'yes' )
				.removeClass( 'revx-inactive' )
				.addClass( 'revx-active' );
		}

		$checkbox.trigger( 'revx-checkbox-toggled' );
	}

	$( document ).on( 'click', '.revx-checkbox-container', function () {
		toggleCheckbox( $( this ) );
	} );
} );
