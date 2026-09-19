<?php
/**
 * Blocks Bootstrap Class
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstrap class to initialize the blocks system
 */
class Blocks_Bootstrap {

	/**
	 * Instance of this class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Block system initialized flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the blocks system
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Initialize main render blocks class.
		$this->init_render_blocks();

		$this->initialized = true;

		do_action( 'prad_blocks_bootstrap_initialized' );
	}


	/**
	 * Initialize main render blocks controller
	 */
	private function init_render_blocks(): void {
		if ( class_exists( 'PRAD\Includes\Blocks\Render_Product_Fields' ) ) {
			new Render_Product_Fields();
		}
	}


	/**
	 * Get initialization status
	 *
	 * @return bool
	 */
	public function is_initialized(): bool {
		return $this->initialized;
	}


	/**
	 * Force re-initialization (useful for testing)
	 */
	public function force_reinit(): void {
		$this->initialized = false;
		$this->init();
	}
}
