<?php
// Sanitize wrapper class
$wrapper_class = Revenue_Template_Utils::get_element_class( $template_data, 'wrapper' );

// Get safely rendered text sections
$heading    = Revenue_Template_Utils::render_rich_text( $template_data, 'heading' );
$subheading = Revenue_Template_Utils::render_rich_text( $template_data, 'subHeading' );
?>

<div class="<?php echo esc_attr( $wrapper_class ); ?>"> <!-- Wrapper Class -->
	<div class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'class' ) ); ?>">
		<?php
		// Output safe HTML (allows tags like <p>, <strong>, etc.)
		echo wp_kses_post( $heading );
		echo wp_kses_post( $subheading );
		?>
	</div>

	<div class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'shippingCountdownWrapper' ) ); ?>">
	</div>

	<div class=""> <!-- Products Wrapper Class -->
	</div>
</div>
