<?php
/**
 * Short codes 
 * 
 * @since 11.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
$shortcode_tabs = FGF_Shortcode_Tab::get_shortcode_tabs();
?>
<div class='fgf-shortcode-wrapper'>
	<h3><?php esc_html_e('General Syntax', 'free-gifts-for-woocommerce'); ?></h3>
	<p>[shortcode parameter1 = "value" parameter2 = "value" ]</p>

	<h3><?php esc_html_e('Shortcodes', 'free-gifts-for-woocommerce'); ?></h3>
	<div class='fgf-shortcode-tabs-wrapper'>
		<?php foreach ($shortcode_tabs as $tab_key => $tab_name) : ?>
			<button class='fgf-shortcode-tab active' href='#fgf-<?php echo esc_attr($tab_key); ?>-shortcode-content'><?php echo esc_html($tab_name); ?></button>
		<?php endforeach; ?>
	</div>

	<?php
	foreach ($shortcode_tabs as $tab_key => $tab_name) :
		include_once FGF_ABSPATH . "inc/admin/menu/views/shortcode/html-{$tab_key}-shortcode-content.php";
	endforeach;
	?>
</div>
<?php
