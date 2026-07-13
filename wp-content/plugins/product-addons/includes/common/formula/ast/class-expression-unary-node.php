<?php // phpcs:ignore
/**
 * Unary operator node (prefix + / -).
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula\Ast;

use PRAD\Includes\Common\Formula\Abstract_Expression_Engine;
use PRAD\Includes\Common\Formula\Expression_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a unary operator node (prefix + or -) in the expression AST.
 *
 * @package PRAD
 * @since 1.5.8
 */
final class Expression_Unary_Node implements Expression_Node {
	/**
	 * Operator for the unary node.
	 *
	 * @var string
	 */
	private $op;
	/**
	 * The right operand node for the unary operator.
	 *
	 * @var Expression_Node
	 */
	private $right;

	/**
	 * Constructor for Expression_Unary_Node.
	 *
	 * @param string          $op   The unary operator ('+' or '-').
	 * @param Expression_Node $right The right operand node.
	 */
	public function __construct( $op, Expression_Node $right ) {
		$this->op    = (string) $op;
		$this->right = $right;
	}

	/**
	 * Evaluates the unary operator node using the provided expression engine and context.
	 *
	 * @param Abstract_Expression_Engine $engine The expression engine used for evaluation.
	 * @param array                      $context Optional context for evaluation.
	 * @return float|int The evaluated result of the unary operation.
	 * @throws Expression_Exception If an unsupported unary operator is encountered.
	 */
	public function evaluate( Abstract_Expression_Engine $engine, array $context = array() ) {
		$val = $engine->to_number( $this->right->evaluate( $engine, $context ) );
		if ( '+' === $this->op ) {
			return +$val;
		}
		if ( '-' === $this->op ) {
			return -$val;
		}
		throw new Expression_Exception( 'Unsupported unary operator: ' . esc_html( $this->op ) );
	}
}
