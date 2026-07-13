<?php
/**
 * Shortcode Core.
 *
 * @package WOPB\Shortcode
 * @since v.1.1.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

class Shortcode {
	public function __construct() {
		add_shortcode( 'product_blocks', array( $this, 'shortcode_callback' ) );
	}

	/**
	 * Shortcode Callback
	 *
	 * @since v.1.1.0
	 * @return STRING | HTML of the shortcode
	 */
	function shortcode_callback( $atts = array(), $content = null ) {
		extract(
			shortcode_atts(
				array(
					'id' => '',
				),
				$atts
			)
		);

		$content     = '';
		$pre_content = '';
		$id          = is_numeric( $id ) ? (float) $id : false;
		if ( $id ) {
			$content_post = get_post( $id );
			if ( $content_post && $content_post->post_status == 'publish' && $content_post->post_password == '' ) {
				do_action(
					'wopb_enqueue_wowstore_block_css',
					array(
						'post_id' => $id,
						'css'     => '',
					)
				);

				// Breakdance builder support for its shortcode render
				$current_url            = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( $_SERVER['REQUEST_URI'] ) : '';
                $_breakdance_doing_ajax = isset( $_GET['_breakdance_doing_ajax'] ) ? sanitize_text_field( $_GET['_breakdance_doing_ajax'] ) : ''; // phpcs:ignore
				if ( ! empty( $_breakdance_doing_ajax ) ||
					strpos( $current_url, 'bricks/v1/render_element' ) !== false
				) {

					if ( ! empty( $_breakdance_doing_ajax ) && get_template() == 'bricks' ) {
						get_header();
					}
					$pre_content .= wopb_function()->build_css_for_inline_print( $id, true );
				}
				$content = $content_post->post_content;
				$content = do_blocks( $content );
				$content = do_shortcode( $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
				$content = preg_replace( '%<p>&nbsp;\s*</p>%', '', $content );
				$content = preg_replace( '/^(?:<br\s*\/?>\s*)+/', '', $content );
				return $pre_content . '<div class="wopb-shortcode" data-postid="' . esc_attr( $id ) . '">' . wopb_function()->core_esc_wp( $content ) . '</div>';
			}
		}
		return '';
	}
}
