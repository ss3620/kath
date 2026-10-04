<?php
/**
 * Loaded only after Elementor Pro has registered form actions.
 */

defined( 'ABSPATH' ) || exit;

class EG_Pathway_Form_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'eg_pathway';
	}

	/**
	 * @return string
	 */
	public function get_label() {
		return 'Earth Goddess Pathway';
	}

	/**
	 * @param object $record        Form record.
	 * @param object $ajax_handler Ajax handler.
	 */
	public function run( $record, $ajax_handler ) {
		EG_Pathway_Forms::handle( $record, $ajax_handler );
	}

	/**
	 * @param object $form Form widget.
	 */
	public function register_settings_section( $form ) {
		unset( $form );
	}

	/**
	 * @param array<string,mixed> $element Element.
	 * @return array<string,mixed>
	 */
	public function on_export( $element ) {
		return $element;
	}
}
