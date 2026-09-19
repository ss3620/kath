<?php // phpcs:ignore
/**
 * Dynamic placeholder node (e.g. [product_price]).
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula\Ast;

use PRAD\Includes\Common\Formula\Abstract_Expression_Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Expression_Variable_Node represents a dynamic placeholder node in the formula AST.
 *
 * @package PRAD
 * @since 1.5.8
 */
final class Expression_Variable_Node implements Expression_Node {
	/**
	 * The variable name.
	 *
	 * @var string
	 */
	private $name;
	/**
	 * The position of the variable in the expression.
	 *
	 * @var int
	 */
	private $pos;

	/**
	 * Expression_Variable_Node constructor.
	 *
	 * @param string $name The variable name.
	 * @param int    $pos  The position of the variable in the expression.
	 */
	public function __construct( $name, $pos ) {
		$this->name = (string) $name;
		$this->pos  = (int) $pos;
	}

	/**
	 * Evaluates the variable node using the provided expression engine and context.
	 *
	 * @param Abstract_Expression_Engine $engine The expression engine.
	 * @param array                      $context The context array.
	 * @return mixed The evaluated value.
	 */
	public function evaluate( Abstract_Expression_Engine $engine, array $context = array() ) {
		return $engine->get_dynamic_value( $this->name, $context );
	}
}
