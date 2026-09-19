<?php
/**
 * Block Interface
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Block Interface - All blocks must implement this
 */
interface Block_Interface {

	/**
	 * Render the block HTML
	 *
	 * @return string
	 */
	public function render(): string;

	/**
	 * Get the block type identifier
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Validate block data and state
	 *
	 * @return bool
	 */
	public function validate(): bool;

	/**
	 * Get the price value for this block
	 *
	 * @return float
	 */
	public function get_price(): float;

	/**
	 * Get block configuration data
	 *
	 * @return array
	 */
	public function get_config(): array;

	/**
	 * Check if block should be displayed
	 *
	 * @return bool
	 */
	public function should_display(): bool;
}
