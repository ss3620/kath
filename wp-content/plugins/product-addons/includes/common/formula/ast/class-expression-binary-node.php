<?php // phpcs:ignore
/**
 * Binary operator node.
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula\Ast;

use PRAD\Includes\Common\Formula\Abstract_Expression_Engine;
use PRAD\Includes\Common\Formula\Expression_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a binary operator node in the expression AST.
 *
 * Handles evaluation of binary operations such as arithmetic and logical operators.
 *
 * @package PRAD
 * @since 1.5.8
 */
final class Expression_Binary_Node implements Expression_Node {
	/**
	 * Operator for the binary expression.
	 *
	 * @var string
	 */
	private $op;
	/**
	 * Left operand node.
	 *
	 * @var Expression_Node
	 */
	private $left;
	/**
	 * Right operand node.
	 *
	 * @var Expression_Node
	 */
	private $right;

	/**
	 * Expression_Binary_Node constructor.
	 *
	 * @param string          $op   Operator for the binary expression.
	 * @param Expression_Node $left Left operand node.
	 * @param Expression_Node $right Right operand node.
	 */
	public function __construct( $op, Expression_Node $left, Expression_Node $right ) {
		$this->op    = (string) $op;
		$this->left  = $left;
		$this->right = $right;
	}

	/**
	 * Evaluates the binary expression node.
	 *
	 * @param Abstract_Expression_Engine $engine  The expression engine used for evaluation.
	 * @param array                      $context Contextual variables for evaluation.
	 * @return mixed                     The result of the binary operation.
	 * @throws Expression_Exception      If division by zero or unsupported operator is encountered.
	 */
	public function evaluate( Abstract_Expression_Engine $engine, array $context = array() ) {
		$op = $this->op;

		if ( '||' === $op ) {
			return $engine->to_bool( $this->left->evaluate( $engine, $context ) ) || $engine->to_bool( $this->right->evaluate( $engine, $context ) );
		}
		if ( '&' === $op ) {
			return $engine->to_bool( $this->left->evaluate( $engine, $context ) ) && $engine->to_bool( $this->right->evaluate( $engine, $context ) );
		}

		$left  = $this->left->evaluate( $engine, $context );
		$right = $this->right->evaluate( $engine, $context );

		if ( '>' === $op ) {
			return $engine->to_number( $left ) > $engine->to_number( $right );
		}
		if ( '<' === $op ) {
			return $engine->to_number( $left ) < $engine->to_number( $right );
		}
		if ( '>=' === $op ) {
			return $engine->to_number( $left ) >= $engine->to_number( $right );
		}
		if ( '<=' === $op ) {
			return $engine->to_number( $left ) <= $engine->to_number( $right );
		}
		if ( '!=' === $op ) {
			return $engine->to_number( $left ) != $engine->to_number( $right );
		}
		if ( '=' === $op ) {
			return $engine->to_number( $left ) == $engine->to_number( $right );
		}

		$l = $engine->to_number( $left );
		$r = $engine->to_number( $right );

		if ( '+' === $op ) {
			return $l + $r;
		}
		if ( '-' === $op ) {
			return $l - $r;
		}
		if ( '*' === $op ) {
			return $l * $r;
		}
		if ( '/' === $op ) {
			if ( 0.0 === $r ) {
				throw new Expression_Exception( 'Division by zero.' );
			}
			return $l / $r;
		}

		throw new Expression_Exception( 'Unsupported operator: ' . esc_html( $op ) );
	}
}
