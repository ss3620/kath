<?php
defined( 'ABSPATH' ) || exit;

function wopb_divi_builder() {
	if ( wopb_function()->get_setting( 'wopb_divi' ) == 'true' ) {
		if ( class_exists( 'ET_Builder_Module' ) ) {
			require_once WOPB_PATH . '/addons/divi/divi.php';
			$action  = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = isset( $_GET['post'] ) ? sanitize_text_field( wp_unslash( $_GET['post'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $action && $post_id ) {
				if ( get_post_type( $post_id ) == 'wopb_templates' ) {
					add_filter( 'et_builder_enable_classic_editor', '__return_false' );
				}
			}
		}
	}
}
add_action( 'init', 'wopb_divi_builder' );
