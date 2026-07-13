<?php
$wrapper_class = Revenue_Template_Utils::get_element_class( $template_data, 'headerWrapper' );
?>
<div class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'class' ) ); ?>">
	<?php
	// Securely render heading and subheading.
	$heading    = Revenue_Template_Utils::render_rich_text( $template_data, 'heading' );
	$subheading = Revenue_Template_Utils::render_rich_text( $template_data, 'subHeading' );

	echo wp_kses_post( $heading );
	echo wp_kses_post( $subheading );
	?>
</div>
