<div class="zip-overlay" onclick="this.style.display = 'none';">
	<div class="zip-overlay-text">Creating charge, please wait...</div>
</div>

<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			//your code

			$('#place_order').on('click', function(e) {
				var zipMoneyErrorDiv = $('#zipMoneyErrorDiv');
				var zipMoneyInfoDiv = $('#zipMoneyInfoDiv');
				zipMoneyErrorDiv.hide();
				zipMoneyInfoDiv.hide();

				var payment_method = $('form[name="checkout"] input[name="payment_method"]:checked').val();

				var checkoutId = null; //log the checkoutId. if the user close the iframe, it will make an ajax call to remove the record in

				if (payment_method == 'zipmoney') {

					Zip.Checkout.init({
						redirect: <?php echo $is_iframe_checkout ? 0 : 1; ?>,
						checkoutUri: '<?php echo WC_Zipmoney_Payment_Gateway_Util::get_checkout_endpoint_url(); ?>',
						redirectUri: '<?php echo WC_Zipmoney_Payment_Gateway_Util::get_complete_endpoint_url(); ?>',
						onCheckout: function(resolve, reject, args) {
							var errorDiv = $('#zipMoneyErrorDiv');
							errorDiv.hide();

							$.ajax({
								type: 'POST',
								url: '<?php echo WC_Zipmoney_Payment_Gateway_Util::get_wc_checkout_url(); ?>',
								data: $('form.checkout').serialize(),
								dataType: 'json',
								success: function(result) {
									console.log('successfully triggered');
									// console.log("test ". result);

									if (result.checkout_id) {
										checkoutId = result.checkout_id;
									}
									if (result.token === true) {
										$('div.zip-overlay').css('display', 'block');
										window.location.href = result.redirect;
										return;
									}
									if (result.redirect) {
										//if the checkout is successful
										console.log('resolved');
										resolve({
											data: {
												redirect_uri: result.redirect
											}
										});
									} else {
										//console.log('reject');
										errorDiv.html(result.messages);
										errorDiv.show();
										//scroll up to show the error
										$('html, body').animate({
											scrollTop: ($('form.checkout').offset().top - 200)
										}, 1000);
										reject(result);
									}
								},
								error: function(jqXHR, textStatus, errorThrown) {
									//console.log('error triggered');
									//console.log(errorThrown);
									reject(result);
								}
							});
						},
						onComplete: function(response) {
							//console.log('onComplete is called.');

							//console.log(response);

							if (response.state == "approved" || response.state == "referred") {
								//show the redirect block
								$('div.zip-overlay').css('display', 'block');

								location.href = "<?php echo WC_Zipmoney_Payment_Gateway_Util::get_complete_endpoint_url(); ?>&result=" +
									response.state + "&checkoutId=" + response.checkoutId;

								//show the over lay
							} else if ((response.state == 'cancelled' || response.state == 'declined') && checkoutId) {
								//clear the wp_options
								//  console.log('clear options. ' + checkoutId);
								$.post('<?php echo WC_Zipmoney_Payment_Gateway_Util::get_clear_options_url(); ?>', {
									checkout_id: checkoutId
								});
							}
						},
						onError: function(response) {
							//console.log('onError is called.');
							var errorMessage = "An error occurred while redirect to Zip.";
							var message = $(response.detail.messages).text();
							if (typeof message != 'undefined' && message != null && message.length > 0) {
								message = message.trim();
								message = message.replace(/\s+/g, ' ');
								message = message.replace(/\./g, '.\n');
								errorMessage = message;
							}
							alert(errorMessage);
						}

					});

					e.preventDefault();
				}
			});

		});

	})(window.jQuery || window.$);
</script>
