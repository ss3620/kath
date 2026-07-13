(function(window,$,undefined){
	"use strict";

	var iframeCheckout = function(vUrl, vType,form, vErrorMessage) {
		this.vUrl          = vUrl;
		this.vType         = vType;
		this.vErrorMessage = vErrorMessage;
		this.form          = form
	};

	iframeCheckout.prototype = {
		standardRedirectToCheckout : function(){

			var $form = $( 'form.checkout' );
			var self  = this;

			if ( $form.is( '.processing' ) ) {
				return false;
			}
			self.submit_error()

			var paymentMethod = $( '#order_review' ).find( 'input[name="payment_method"]:checked' ).val();

			// Trigger a handler to let gateways manipulate the checkout if needed
			if ( $form.triggerHandler( 'checkout_place_order' ) !== false && $form.triggerHandler( 'checkout_place_order_' + paymentMethod ) !== false ) {

				$form.addClass( 'processing' );

				var formData = $form.data();

				if ( 1 !== formData['blockUI.isBlocked'] ) {
					$form.block(
						{
							message: null,
							overlayCSS: {
								background: "#fff",
								opacity: 0.6
							}
						}
					);
				}

				// ajaxSetup is global, but we use it to ensure JSON is valid once returned.
				$.ajaxSetup(
					{
						dataFilter: function( rawResponse, dataType ) {
							// We only want to work with JSON
							if ( 'json' !== dataType ) {
								return rawResponse;
							}

							try {
								// check for valid JSON
								var data = $.parseJSON( rawResponse );

								if ( data && 'object' === typeof data ) {

									// Valid - return it so it can be parsed by Ajax handler
									return rawResponse;
								}

							} catch ( e ) {

								// attempt to fix the malformed JSON
								var valid_json = rawResponse.match( /{"result.*"}/ );

								if ( null === valid_json ) {
									console.log( "Unable to fix malformed JSON" );
								} else {
									console.log( "Fixed malformed JSON. Original:" );
									console.log( rawResponse );
									rawResponse = valid_json[0];
								}
							}

							return rawResponse;
						}
					}
				);

				this.redirectToCheckout();
			}

		},
		redirectToCheckout: function () {
			var vUrl          = this.vUrl;
			var vType         = this.vType;
			var vErrorMessage = this.vErrorMessage;
			var data          = {};
			var method        = 'GET';
			var self          = this;

			if (this.form) {
				method = 'POST';
				data   = this.form.serialize();
			}

			this.showRedirectingText();

			$.ajax(
				{
					type : method,
					dataType : "json",
					data:   data,
					url: vUrl,
					success: function(response) {
						self.resetRedirectButtonText();
						var vRedirectUrl = "";
						try {
							if ( response.result === 'success' ) {
								if ( -1 === response.redirect.indexOf( 'https://' ) || -1 === response.redirect.indexOf( 'http://' ) ) {
									vRedirectUrl = response.redirect;        // example: http://app.dev1.zipmoney.com.au/#/cart/10002/91179
								} else {
									vRedirectUrl = response.redirect;        // example: http://app.dev1.zipmoney.com.au/#/cart/10002/91179
								}
								if (typeof(zipMoney) != 'undefined') {

									zipMoney.checkout( vRedirectUrl );    // call zipMoney iframe library.
									$( ".zipmoney-overlay img:first" ).on(
										"click",
										function(){
											self.form.removeClass( "processing" );
										}
									);

									window.scroll( 0, 0 );
									self.form.unblock();
								}

							} else if ( response.result === 'failure' ) {
								throw 'Result failure';
							} else {
								throw 'Invalid response';
							}
						} catch ( err ) {
							// Reload page

							console.log( err );
							if ( response.reload === 'true' ) {
								window.location.reload();
								return;
							}

							// Trigger update in case we need a fresh nonce
							if ( response.refresh === 'true' ) {
								$( document.body ).trigger( 'update_checkout' );
							}

							// Add new errors
							if ( response.messages ) {
								self.submit_error( response.messages );
							} else {
								self.submit_error( '<div class="woocommerce-error">Error processing checkout. Please try again.</div>' );
							}
						}

					},
					error:  function( jqXHR, textStatus, errorThrown ) {
						self.submit_error( '<div class="woocommerce-error">' + errorThrown + '</div>' );
					}
				}
			);
		},
		submit_error: function( error_message ) {
			$( '.woocommerce-error, .woocommerce-message' ).remove();
			if (this.form) {
				this.form.prepend( error_message );
				this.form.removeClass( 'processing' ).unblock();
				this.form.find( '.input-text, select' ).blur();

				$( 'html, body' ).animate(
					{
						scrollTop: ( this.form.offset().top - 100 )
					},
					1000
				);
			}

			$( document.body ).trigger( 'checkout_error' );
		},
		showRedirectingText: function(){
			this.toggleButton( false );
		},
		resetButton: function(vType) {
			if (vType === 'pdp') {
				this.toggleExpressButton( true );
			} else if (vType === 'cart') {
				this.resetRedirectButtonText();
			}
		},
		toggleExpressButton: function($bShowButton){
			if ($bShowButton) {
				$$( '.zip-express-btn' ).each(
					function (oEle) {
						if (oEle == undefined) {
							return true;
						}
						oEle.show();
					}
				);
				$$( '.wait-for-redirecting-to-zip' ).each(
					function (oEle) {
						if (oEle == undefined) {
							return true;
						}
						oEle.hide();
					}
				);
			} else {
				$$( '.zip-express-btn' ).each(
					function (oEle) {
						if (oEle == undefined) {
							return true;
						}
						oEle.hide();
					}
				);
				$$( '.wait-for-redirecting-to-zip' ).each(
					function (oEle) {
						if (oEle == undefined) {
							return true;
						}
						oEle.show();
					}
				);
			}
		},
		toggleButton: function($bShowButton) {
			var cartButton     = $( '#checkout_express_cart' );
			var checkoutButton = $( '#place_order' );
			var waitingImg     = $( '.please-wait' );
			if ($bShowButton) {
				if (cartButton) {
					cartButton.show();
				}
				if (checkoutButton) {
					checkoutButton.show();
				}
				if (waitingImg) {
					waitingImg.hide();
				}
			} else {
				if (cartButton) {
					cartButton.hide();
				}
				if (checkoutButton) {
					checkoutButton.hide();
				}
				if (waitingImg) {
					waitingImg.show();
				}
			}
		},
		resetRedirectButtonText:function(){
			this.toggleButton( true );
		}

	};

	window.iframeCheckout = iframeCheckout;
})( window,window.jQuery );
