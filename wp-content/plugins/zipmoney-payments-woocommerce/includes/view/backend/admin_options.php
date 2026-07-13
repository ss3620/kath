<h3><?php esc_attr_e( $this->method_title, 'woocommerce' ); ?></h3>
<p><?php esc_attr_e( $this->method_description, 'woocommerce' ); ?></p>
<table class="form-table">
	<?php $this->generate_settings_html(); ?>
</table>
<script>
	var ZipApiKeyCheckUrl = '<?php echo WC_Zipmoney_Payment_Gateway_Util::get_priavte_key_validation_url(); ?>';
</script>
