<?php
/**
 * Admin HTML settings buttons.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<p class = 'submit'>
	<?php if ( ! isset( $GLOBALS[ 'hide_save_button' ] ) ) : ?>
		<input name='fgf_save' class='button-primary fgf-settings-save-btn submit' type='submit' value="<?php esc_attr_e( 'Save changes', 'free-gifts-for-woocommerce' ) ; ?>" />
		<input type="hidden" name="save" value="save"/>
		<?php
		wp_nonce_field( 'fgf_save_settings', '_fgf_nonce', false, true ) ;
	endif ;
	?>
</p>
<?php if ( $reset ) : ?>
	</form>
	<form method='post' action='' enctype='multipart/form-data' class="fgf-reset-form">
		<input id='reset' name='fgf_reset' class='button-secondary fgf-settings-reset-btn' type='submit' value="<?php esc_attr_e( 'Reset', 'free-gifts-for-woocommerce' ) ; ?>"/>
		<input type="hidden" name="reset" value="reset"/>
		<?php
		wp_nonce_field( 'fgf_reset_settings', '_fgf_nonce', false, true ) ;
	endif;
