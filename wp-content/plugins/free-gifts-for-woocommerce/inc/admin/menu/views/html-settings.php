<?php
/**
 * Admin settings. 
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<div class = "wrap fgf-settings-wrapper woocommerce">
	<?php
	/**
	 * This hook is used to display the extra content before settings form.
	 * 
	 * @since 1.0
	 */
	do_action( 'fgf_before_settings_form' ) ;
	?>
	<form method = "post" enctype = "multipart/form-data" class="fgf-settings-form">
		<div class = "fgf-settings-form-wrapper">

			<nav class = "nav-tab-wrapper woo-nav-tab-wrapper fgf-tab-wrapper">
				<?php foreach ( $tabs as $name => $label ) { ?>
					<a href="<?php echo esc_url( fgf_get_settings_page_url( array( 'tab' => $name ) ) ) ; ?>" class="nav-tab fgf-nav-tab <?php echo esc_attr( $name ) . '_a ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) ; ?>">
						<span><?php echo esc_html( $label ) ; ?></span>
					</a>
				<?php } ?>
			</nav>

			<div class="fgf-settings-tab-content-wrapper">
				<?php
				/**
				 * This hook is used to display extra content before settings tab content.
				 * 
				 * @hooked FGF_Settings_Page->output_sections - 10 (sections).
				 * @since 1.0
				 */
				do_action( sanitize_key( 'fgf_before_settings_tab_content_' . $current_tab ) ) ;
				?>
				<div class="fgf-settings-tab-content">
					<?php
					/**
					 * This hook is used to display the settings content.
					 * 
					 * @hooked FGF_Settings::show_messages - 5 (messages)
					 * @since 1.0
					 */
					do_action( 'fgf_settings_content' ) ;
					/**
					 * This hook is used to display the settings current tab content.
					 * 
					 * @hooked FGF_Settings_Page->output - 10 (content).
					 * @hooked FGF_Settings_Page->output_buttons - 20 (buttons).
					 * @hooked FGF_Settings_Page->output_extra_fields - 30 (extra fields).
					 * @since 1.0
					 */
					do_action( sanitize_key( 'fgf_settings_' . $current_tab ) ) ;
					?>
				</div>
				<?php
				/**
				 * This hook is used to display extra content after settings tab content.
				 * 
				 * @since 1.0
				 */
				do_action( sanitize_key( 'fgf_after_settings_tab_content_' . $current_tab ) ) ;
				?>
			</div>

		</div>
	</form>
	<?php
	/**
	 * This hook is used to display the extra content after settings form.
	 * 
	 * @since 1.0
	 */
	do_action( 'fgf_after_settings_form' ) ;
	?>
</div>
<?php
