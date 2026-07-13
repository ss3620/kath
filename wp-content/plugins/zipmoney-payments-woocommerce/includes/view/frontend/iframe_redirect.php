<p>Redirecting please wait...</p>
<script type="text/javascript">
	if (window.self !== window.top) { // detect if current windw is an iFrame
		// setting parent redirect info
		window.parent.postMessage({
			msg: {
				eventType: 'complete',
				data: {
					state: "<?php echo esc_attr( $state ); ?>", // get from URL parameter
					checkoutId: "<?php echo esc_attr( $checkoutId ); ?>" // get from URL parameter
				}
			},
			zipmoney: true
		}, '*');
		// close iframe
		window.parent.postMessage({
			msg: {
				eventType: 'close'
			},
			zipmoney: true
		}, '*');
	} else {
		window.location.href = '<?php echo esc_url( $redirectUrl ); ?>';
	}
</script>
