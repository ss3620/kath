<?php
defined( 'ABSPATH' ) || exit;

add_action( 'et_builder_ready', 'wopb_productx_template_divi_modules' );

function wopb_productx_template_divi_modules() {

	if ( ! class_exists( 'ET_Builder_Module' ) ) {
		return; }

	class ProductX_Template_Module extends ET_Builder_Module {

		public $slug       = 'wopb_productx_template';
		public $vb_support = 'partial';

		function init() {
			$this->name      = __( 'WowStore Template', 'product-blocks' );
			$this->icon_path = plugin_dir_path( __FILE__ ) . 'icon.svg';
		}

		function get_fields() {
			return array(
				'templates' => array(
					'label'       => __( 'Select Your Template', 'product-blocks' ),
					'type'        => 'select',
					'options'     => wopb_function()->get_all_lists( 'wopb_templates', 'none' ),
					'default'     => 'none',
					'description' => __( 'Pick a Template from your saved ones. Or create a template from: Dashboard > WowStore > Saved Templates', 'product-blocks' ),
				),
			);
		}

		function render( $attrs, $render_slug, $content = null ) {
			$templates = $this->props['templates'];

			$output     = '';
			$content    = '';
			$body_class = get_body_class();
			if ( $templates && $templates != 'none' ) {
				$args      = array(
					'p'         => $templates,
					'post_type' => 'wopb_templates',
				);
				$the_query = new \WP_Query( $args );
				if ( $the_query->have_posts() ) {
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						ob_start();
						if ( in_array( 'et-fb', $body_class ) ) {
							echo wopb_function()->build_css_for_inline_print( $templates, true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
							the_content();
						$content = ob_get_clean();
					}
					wp_reset_postdata();
				}
			} elseif ( in_array( 'et-fb', $body_class ) ) {
					$content = '<p style="text-align:center;">' .
						/* translators: %s: is no of template */
						sprintf(
							esc_html__( 'Pick a Template from your saved ones. Or create a template from: %s.', 'product-blocks' ) . ' ',
							'<strong><i>' . esc_html( 'Dashboard > WowStore > Saved Templates' ) . '</i></strong>'
						) . '</p>';
			}

			// Render module content
			$output = sprintf(
				'<div class="wopb-shortcode" data-postid="%1$s">%2$s</div>',
				esc_html( $templates ),
				et_sanitized_previously( $content )
			);

			return $this->_render_module_wrapper( $output, $render_slug );
		}
	}

	new ProductX_Template_Module();
}
