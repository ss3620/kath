<?php
/**
 * Template Action.
 *
 * @package WOPB\Templates
 * @since v.1.0.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * Templates class.
 */
class Templates {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_filter( 'template_include', array( $this, 'set_template_callback' ) );
		add_filter( 'theme_page_templates', array( $this, 'add_template_callback' ) );
	}

	/**
	 * Include Template File
	 *
	 * @since v.1.0.0
	 * @param STRING | Attachment
	 * @return STRING | Template File Path
	 */
	public function set_template_callback( $template ) {
		if ( is_singular( 'page' ) ) {
			if ( get_post_meta( get_the_ID(), '_wp_page_template', true ) === 'wopb_page_template' ) {
				return WOPB_PATH . 'classes/template-without-title.php';
			}
		}
		return $template;
	}

	/**
	 * Add A Page Template
	 *
	 * @since v.1.0.0
	 * @param ARRAY | Page Template List
	 * @return ARRAY | Page Template List as Array
	 */
	public function add_template_callback( $templates ) {
		$templates['wopb_page_template'] = __( 'WowStore Template (Without Title)', 'product-blocks' );
		return $templates;
	}
}
