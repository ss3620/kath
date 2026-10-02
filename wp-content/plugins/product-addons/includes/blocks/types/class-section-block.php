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
		return $this->render_plain_section();
	}

	/**
	 * Render standard (non-repeater) section — mirrors the JS fallback return.
	 *
	 * The repeater variant of this block (section repeated N times) is a pro-only
	 * feature implemented entirely in product-addons-pro, registered for the
	 * 'section' block type via Block_Factory::register_block() when active.
	 */
	protected function render_plain_section(): string {
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
	 * Render section header
	 *
	 * Protected so the pro-only repeater section subclass (product-addons-pro) can
	 * reuse it for its own accordion shell.
	 *
	 * @param bool $show_accordion Whether accordion is active.
	 * @param bool $is_title_hidden Whether title is hidden.
	 */
	protected function render_section_header( bool $show_accordion, bool $is_title_hidden ): string {
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
	protected function render_section_title( bool $is_title_hidden, bool $show_accordion ): string {
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
	protected function render_accordion_icon(): string {
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
	protected function render_section_body_with_content( bool $show_accordion, bool $is_title_hidden, string $content ): string {
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
	protected function render_inner_blocks(): string {
		$inner_blocks = $this->get_property( 'innerBlocks', array() );

		if ( empty( $inner_blocks ) ) {
			return '';
		}

		return $this->renderer->render_blocks( $inner_blocks, $this->product_id );
	}
}
