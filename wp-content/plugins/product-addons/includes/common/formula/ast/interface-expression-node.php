<?php // phpcs:ignore
/**
 * AST node interface.
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula\Ast;

use PRAD\Includes\Common\Formula\Abstract_Expression_Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Common interface for all AST nodes.
 */
interface Expression_Node {
	/**
	 * Evaluates the expression node with the given engine and context.
	 *
	 * @param Abstract_Expression_Engine $engine Engine instance.
	 * @param array                      $context Context passed to evaluate().
	 *
	 * @return mixed
	 */
	public function evaluate( Abstract_Expression_Engine $engine, array $context = array() );
}
