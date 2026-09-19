<?php // phpcs:ignore
/**
 * Number literal node.
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula\Ast;

use PRAD\Includes\Common\Formula\Abstract_Expression_Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a number literal node in the expression AST.
 *
 * @package PRAD
 * @since 1.5.8
 */
final class Expression_Number_Node implements Expression_Node {
	/**
	 * The numeric value stored in this node.
	 *
	 * @var float
	 */
	private $value;

	/**
	 * Constructor.
	 *
	 * @param mixed $value The numeric value to store.
	 */
	public function __construct( $value ) {
		$this->value = (float) $value;
	}

	/**
	 * Evaluates the number node and returns its value.
	 *
	 * @param Abstract_Expression_Engine $engine The expression engine instance.
	 * @param array                      $context Optional context for evaluation.
	 * @return float The numeric value stored in this node.
	 */
	public function evaluate( Abstract_Expression_Engine $engine, array $context = array() ) {
		return $this->value;
	}
}
