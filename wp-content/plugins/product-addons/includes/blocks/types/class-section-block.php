<?php
/**
 * Section Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;
use PRAD\Includes\Blocks\Renderers\Block_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Section Block Class
 */
class Section_Block extends Abstract_Block {

	/**
	 * Block renderer for inner blocks
	 *
	 * @var Block_Renderer
	 */
	private Block_Renderer $renderer;

	/**
	 * Initialize section block
	 */
	protected function init(): void {
		$this->renderer = new Block_Renderer();
	}

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'section';
	}

	/**
	 * Render the section block
	 *
	 * @return string
	 */
	public function render(): string {
		$repeater            = $this->get_property( 'repeater', array() );
		$is_repeater_enabled = ! empty( $repeater['enable'] );

		if ( $is_repeater_enabled && product_addons()->is_pro_feature_available() ) {
			return $this->render_repeater( $repeater );
		}

		return $this->render_plain_section();
	}

	/**
	 * Render standard (non-repeater) section — mirrors the JS fallback return.
	 */
	private function render_plain_section(): string {
		$show_accordion  = $this->get_property( 'showAccordion', true );
		$is_title_hidden = $this->is_title_hidden();
		$init_class      = $show_accordion ? 'prad-section-init-' . $this->get_property( 'initState', 'open' ) : '';

		$css_classes = array(
			'prad-parent',
			'prad-section-block',
			'prad-section-wrapper',
			'prad-w-full',
			'prad-block-' . $this->get_block_id(),
			$init_class,
			$this->get_css_class(),
		);

		$data_attributes = array(
			'btype'           => 'section',
			'bid'             => $this->get_block_id(),
			'sectionid'       => $this->get_property( 'sectionid', '' ),
			'label'           => $this->get_label(),
			'enlogic'         => $this->is_logic_enabled() ? 'yes' : 'no',
			'fieldconditions' => $this->get_field_conditions(),
		);

		$attributes = array_merge(
			array(
				'class' => $this->build_css_classes( $css_classes ),
				'id'    => 'prad-bid-' . $this->get_block_id(),
			),
			$this->build_data_attributes( $data_attributes )
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_section_header( $show_accordion, $is_title_hidden );
		$html .= $this->render_section_body( $show_accordion, $is_title_hidden );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render repeater section — mirrors the JS isRepeaterEnabled branch.
	 *
	 * @param array $repeater Repeater settings from block data.
	 */
	private function render_repeater( array $repeater ): string {
		$show_accordion  = $this->get_property( 'showAccordion', true );
		$is_blank        = 'blank' === $show_accordion;
		$is_title_hidden = $this->is_title_hidden();

		$repetition_action_type = ! empty( $repeater['repetitionActionType'] )
			? $repeater['repetitionActionType']
			: 'add_button';
		$is_quantity_driven     = 'quantity_input' === $repetition_action_type;

		$add_button_text = ! empty( $repeater['addButtonText'] )
			? $repeater['addButtonText']
			: '+ Add Another';

		$add_button_row  = '<div class="prad-repeater-add-btn prad-button-item prad-cursor-pointer prad-active prad-w-fit prad-d-flex prad-item-center prad-gap-8">';
		$add_button_row .= esc_html( $add_button_text );
		$add_button_row .= '</div>';

		$bid = $this->get_block_id();

		$options      = $this->get_property( '_options', array() );
		$first_option = is_array( $options ) && ! empty( $options ) ? (array) $options[0] : array();
		$price_info   = $this->get_price_info( $first_option );

		$first_instance  = sprintf(
			'<div class="prad-parent prad-repeater-instance prad-repeater-instance-parent" data-repeater-id="%s-repeater-1" data-repeater-sectionid="%s" data-repeater-index="1" data-bid="' . esc_attr( $this->get_block_id() ) . '">',
			esc_attr( $bid ),
			esc_attr( $bid )
		);
		$first_instance .= $this->render_single_repeater_instance( $repeater, 0, $is_blank, $price_info );
		$first_instance .= '</div>';

		$max_repetation = ( ! $is_quantity_driven && ! empty( $repeater['maxRepetation'] ) ) ? (int) $repeater['maxRepetation'] : 0;

		$attr = array(
			'data-instance-cost'          => $price_info['price'],
			'data-instance-cost-type'     => $price_info['type'],
			'data-repetition-action-type' => $repetition_action_type,
		);

		$instances_container = sprintf(
			'<div class="prad-repeater-instances prad-w-full" data-max="%d" %s>',
			$max_repetation,
			$this->build_attributes( $attr ),
		);

		$instances_container .= $first_instance;
		if ( ! $is_quantity_driven && 1 !== $repeater['maxRepetation'] ) {
			$instances_container .= $add_button_row;
		}
		$instances_container .= '</div>';

		$body_content  = '<div class="prad-section-container">';
		$body_content .= $instances_container;
		$body_content .= '</div>';

		$html  = '<div class="prad-repeater prad-w-full">';
		$html .= $this->render_repeater_section_shell( $show_accordion, $is_title_hidden, $body_content, $is_blank );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render a single repeater instance (one map() iteration in JS).
	 *
	 * @param array $repeater  Repeater settings.
	 * @param int   $index     Zero-based instance index.
	 * @param bool  $is_blank  Whether showAccordion === 'blank'.
	 */
	private function render_single_repeater_instance( array $repeater, int $index, bool $is_blank, array $price_info ): string {
		$instance_title = $this->get_instance_title( $repeater, $index );

		$price_html = $this->render_price_html( $price_info, 'with_title' );

		$inner_class = 'prad-section-container prad-bg-transparent' . ( $is_blank ? ' prad-p-0' : ' prad-section-wrapper prad-repeater-section-wrapper' );

		$html  = '<div class="prad-d-flex prad-item-center prad-justify-between">';
		$html .= '<div class="prad-d-flex prad-item-center prad-gap-12 prad-repeater-instance-title">';
		$html .= sprintf( '<div class="prad-mb-0 prad-block-title">%s</div>', esc_html( $instance_title ) );
		if ( $price_html ) {
			$html .= $price_html;
		}
		$html .= '</div>';
		$html .= '<div class="prad-repeater-remove-btn prad-cursor-pointer prad-active prad-w-fit prad-d-flex prad-item-center prad-gap-8" style="display:none"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" style="fill: none;"><path stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M12.883 6.312s-.362 4.49-.572 6.381c-.1.904-.658 1.433-1.572 1.45-1.74.031-3.48.033-5.22-.004-.878-.018-1.427-.553-1.525-1.44-.211-1.909-.571-6.387-.571-6.387M13.806 4.16H2.5m9.127 0a1.1 1.1 0 0 1-1.077-.883l-.162-.81a.853.853 0 0 0-.824-.633H6.742a.853.853 0 0 0-.825.632l-.162.811a1.099 1.099 0 0 1-1.077.883"></path></svg></div>';
		$html .= '</div>';

		$html .= sprintf( '<div class="%s">', esc_attr( $inner_class ) );
		$html .= $this->render_inner_blocks();

		$html .= '</div>';

		return $html;
	}

	/**
	 * Build instance title — mirrors getInstanceTitle() in JS.
	 *
	 * @param array $repeater Repeater settings.
	 * @param int   $index    Zero-based instance index.
	 */
	private function get_instance_title( array $repeater, int $index ): string {
		$title_template = $repeater['title'] ?? '';
		if ( $title_template ) {
			return str_replace( '{n}', $index + 1, $title_template );
		}
		return $this->get_label() . ' ' . ( $index + 1 );
	}

	/**
	 * Render the outer section shell (header + body) for a repeater with accordion/title.
	 * Mirrors the non-blank repeater JSX return.
	 *
	 * @param mixed  $show_accordion  Value of showAccordion setting.
	 * @param bool   $is_title_hidden Whether title is hidden.
	 * @param string $instance_cards  Rendered instance cards HTML.
	 */
	private function render_repeater_section_shell( $show_accordion, bool $is_title_hidden, string $instance_cards, bool $is_blank ): string {
		$is_accordion = true === $show_accordion;
		$init_class   = $is_accordion ? 'prad-section-init-' . $this->get_property( 'initState', 'open' ) : '';

		$css_classes = array(
			'prad-parent',
			'prad-section-block',
			$is_blank ? 'prad-repeater-blank' : 'prad-section-wrapper',
			'prad-w-full',
			'prad-block-' . $this->get_block_id(),
			$init_class,
			$this->get_css_class(),
		);

		$data_attributes = array(
			'btype'           => 'section',
			'bid'             => $this->get_block_id(),
			'sectionid'       => $this->get_property( 'sectionid', '' ),
			'label'           => $this->get_label(),
			'enlogic'         => $this->is_logic_enabled() ? 'yes' : 'no',
			'fieldconditions' => $this->get_field_conditions(),
		);

		$attributes = array_merge(
			array(
				'class' => $this->build_css_classes( $css_classes ),
				'id'    => 'prad-bid-' . $this->get_block_id(),
			),
			$this->build_data_attributes( $data_attributes )
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $is_blank ? '' : $this->render_section_header( $is_accordion, $is_title_hidden );
		$html .= $this->render_section_body_with_content( $is_accordion, $is_title_hidden, $instance_cards );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render section header
	 *
	 * @param bool $show_accordion Whether accordion is active.
	 * @param bool $is_title_hidden Whether title is hidden.
	 */
	private function render_section_header( bool $show_accordion, bool $is_title_hidden ): string {
		$cursor_class = $show_accordion ? 'pointer' : 'default';
		$status_class = ( $show_accordion || ! $is_title_hidden ) ? 'active' : 'inactive';

		$header_classes = array(
			'prad-section-header',
			'prad-accordion-header',
			'prad-cursor-' . $cursor_class,
			'prad-section-head-' . $status_class,
		);

		$html  = sprintf( '<div class="%s">', $this->build_css_classes( $header_classes ) );
		$html .= $this->render_section_title( $is_title_hidden, $show_accordion );

		if ( $show_accordion ) {
			$html .= $this->render_accordion_icon();
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render section title — mirrors JS Label + tooltip + description below title.
	 *
	 * @param bool $is_title_hidden Whether title is hidden.
	 * @param bool $show_accordion  Whether accordion is active.
	 */
	private function render_section_title( bool $is_title_hidden, bool $show_accordion ): string {
		if ( ! $is_title_hidden ) {
			return sprintf(
				'<div class="prad-section-title">
					<div class="prad-d-flex prad-gap-10 prad-item-center">
						<div class="prad-block-title">%s</div>
						%s
					</div>
					%s
				</div>',
				wp_kses( $this->get_label(), $this->allowed_html_tags ),
				$this->render_description_tooltip(),
				$this->render_description_below_title()
			);
		}

		if ( $is_title_hidden && $show_accordion ) {
			return '<div></div>';
		}

		return '';
	}

	/**
	 * Render accordion chevron icon.
	 */
	private function render_accordion_icon(): string {
		return '
			<div class="prad-section-accordion">
				<div data-active="active" class="prad-accordion-icon prad-active">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="8" fill="none">
						<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m1 1 6 6 6-6" />
					</svg>
				</div>
			</div>';
	}

	/**
	 * Render section body wrapping arbitrary HTML content.
	 *
	 * @param bool   $show_accordion Whether accordion is active.
	 * @param bool   $is_title_hidden Whether title is hidden.
	 * @param string $content        Inner HTML to wrap.
	 */
	private function render_section_body_with_content( bool $show_accordion, bool $is_title_hidden, string $content ): string {
		$body_classes = array( 'prad-section-body' );

		if ( $show_accordion ) {
			$body_classes[] = 'prad-section-accordian';
		}

		if ( $show_accordion || ! $is_title_hidden ) {
			$body_classes[] = 'prad-block-border-top';
		}

		if ( 'close' === $this->get_property( 'initState', 'open' ) && $show_accordion ) {
			$body_classes[] = 'prad-inactive';
		} else {
			$body_classes[] = 'prad-active';
		}

		$html  = sprintf( '<div class="%s" style="max-height:100%%">', $this->build_css_classes( $body_classes ) );
		$html .= $content;
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render section body wrapping inner blocks (non-repeater path).
	 *
	 * @param bool $show_accordion  Whether accordion is active.
	 * @param bool $is_title_hidden Whether title is hidden.
	 */
	private function render_section_body( bool $show_accordion, bool $is_title_hidden ): string {
		$inner = '<div class="prad-section-container">' . $this->render_inner_blocks() . '</div>';
		return $this->render_section_body_with_content( $show_accordion, $is_title_hidden, $inner );
	}

	/**
	 * Render inner blocks.
	 */
	private function render_inner_blocks(): string {
		$inner_blocks = $this->get_property( 'innerBlocks', array() );

		if ( empty( $inner_blocks ) ) {
			return '';
		}

		return $this->renderer->render_blocks( $inner_blocks, $this->product_id );
	}
}
