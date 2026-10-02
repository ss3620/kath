<?php
/**
 * Block Renderer
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Renderers;

use PRAD\Includes\Blocks\Factories\Block_Factory;

defined( 'ABSPATH' ) || exit;

/**
 * Main block renderer class
 */
class Block_Renderer {

	/**
	 * Render multiple blocks
	 *
	 * @param array $blocks_data Array of block configuration data.
	 * @param int   $product_id Product ID.
	 * @return string Rendered HTML
	 */
	public function render_blocks( array $blocks_data, int $product_id ): string {
		if ( empty( $blocks_data ) ) {
			return '';
		}

		$output          = '';
		$rendered_blocks = 0;

		foreach ( $blocks_data as $index => $block_data ) {
			$rendered_html = $this->render_single_block( $block_data, $product_id, $index );

			if ( ! empty( $rendered_html ) ) {
				$output .= $rendered_html;
				++$rendered_blocks;
			}
		}

		// Apply filters to final output.
		$output = apply_filters( 'prad_rendered_blocks_output', $output, $blocks_data, $product_id );

		do_action( 'prad_blocks_rendered', $rendered_blocks, $product_id );

		return $output;
	}

	/**
	 * Render a single block
	 *
	 * @param array $block_data Block configuration data.
	 * @param int   $product_id Product ID.
	 * @param int   $index Block index in the collection.
	 * @return string Rendered HTML.
	 */
	public function render_single_block( array $block_data, int $product_id, int $index = 0 ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- part of this public method's signature, called with the loop index from render_blocks().
		$type = $block_data['type'] ?? '';

		if ( empty( $type ) ) {
			do_action( 'prad_empty_block_type', $block_data, $product_id );
			return '';
		}

		// Create block instance.
		$block = Block_Factory::create_block( $type, $block_data, $product_id );

		if ( ! $block ) {
			do_action( 'prad_block_creation_failed_render', $type, $block_data, $product_id );
			return '';
		}

		try {
			// Render the block.
			$html = $block->render();

			// Apply filters to individual block output.
			$html = apply_filters( 'prad_block_rendered', $html, $block, $product_id );
			$html = apply_filters( "prad_block_rendered_{$type}", $html, $block, $product_id );

			do_action( 'prad_after_block_render', $block, $html, $product_id );

			return $html;

		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only logged when WP_DEBUG is enabled.
					sprintf(
						'PRAD Block Render Error: Failed to render block type "%s". Error: %s',
						$type,
						$e->getMessage()
					)
				);
			}

			do_action( 'prad_block_render_exception', $e, $block, $product_id );

			return '';
		}
	}
}
