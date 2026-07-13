<?php // phpcs:ignore
/**
 * Function call node (if/abs/ceil/floor/max/min/pow/round).
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula\Ast;

use PRAD\Includes\Common\Formula\Abstract_Expression_Engine;
use PRAD\Includes\Common\Formula\Expression_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Represents a function call node in the expression AST.
 *
 * Handles evaluation of supported functions such as if, abs, ceil, floor, max, min, pow, and round.
 *
 * @package PRAD
 * @since 1.5.8
 */
final class Expression_Function_Node implements Expression_Node {
	/**
	 * Function name.
	 *
	 * @var string
	 */
	private $name;
	/**
	 * Argument nodes.
	 *
	 * @var Expression_Node[]
	 */
	private $args;
	/**
	 * Token position.
	 *
	 * @var int
	 */
	private $pos;

	/**
	 * Constructs a new Expression_Function_Node instance.
	 *
	 * @param string            $name Function name.
	 * @param Expression_Node[] $args Argument nodes.
	 * @param int               $pos  Token position.
	 */
	public function __construct( $name, array $args, $pos ) {
		$this->name = strtolower( (string) $name );
		$this->args = $args;
		$this->pos  = (int) $pos;
	}

	/**
	 * Evaluates the function node using the provided expression engine and context.
	 *
	 * @param Abstract_Expression_Engine $engine  The expression engine to use for evaluation.
	 * @param array                      $context Optional context for evaluation.
	 * @return mixed                     The result of the function evaluation.
	 * @throws Expression_Exception      If the function arguments are invalid or unknown function is called.
	 */
	public function evaluate( Abstract_Expression_Engine $engine, array $context = array() ) {
		$name = $this->name;

		// Lazy evaluation for IF.
		if ( 'if' === $name ) {
			if ( 3 !== count( $this->args ) ) {
				throw new Expression_Exception( 'if() expects 3 arguments.' );
			}
			$condition = $engine->to_bool( $this->args[0]->evaluate( $engine, $context ) );
			return $condition ? $this->args[1]->evaluate( $engine, $context ) : $this->args[2]->evaluate( $engine, $context );
		}

		$evaluated = array();
		foreach ( $this->args as $arg ) {
			$evaluated[] = $arg->evaluate( $engine, $context );
		}

		if ( 'abs' === $name ) {
			if ( 1 !== count( $evaluated ) ) {
				throw new Expression_Exception( 'abs() expects 1 argument.' );
			}
			return abs( $engine->to_number( $evaluated[0] ) );
		}
		if ( 'ceil' === $name ) {
			if ( 1 !== count( $evaluated ) ) {
				throw new Expression_Exception( 'ceil() expects 1 argument.' );
			}
			return ceil( $engine->to_number( $evaluated[0] ) );
		}
		if ( 'floor' === $name ) {
			if ( 1 !== count( $evaluated ) ) {
				throw new Expression_Exception( 'floor() expects 1 argument.' );
			}
			return floor( $engine->to_number( $evaluated[0] ) );
		}
		if ( 'round' === $name ) {
			$argc = count( $evaluated );
			if ( $argc < 1 || $argc > 2 ) {
				throw new Expression_Exception( 'round() expects 1 or 2 arguments.' );
			}
			$value     = $engine->to_number( $evaluated[0] );
			$precision = ( 2 === $argc ) ? (int) $engine->to_number( $evaluated[1] ) : 0;
			// Match PHP's default rounding mode (PHP_ROUND_HALF_UP) explicitly.
			return round( $value, $precision, PHP_ROUND_HALF_UP );
		}
		if ( 'pow' === $name ) {
			if ( 2 !== count( $evaluated ) ) {
				throw new Expression_Exception( 'pow() expects 2 arguments.' );
			}
			return pow( $engine->to_number( $evaluated[0] ), $engine->to_number( $evaluated[1] ) );
		}
		if ( 'min' === $name ) {
			if ( count( $evaluated ) < 1 ) {
				throw new Expression_Exception( 'min() expects at least 1 argument.' );
			}
			$nums = array_map( array( $engine, 'to_number' ), $evaluated );
			return min( $nums );
		}
		if ( 'max' === $name ) {
			if ( count( $evaluated ) < 1 ) {
				throw new Expression_Exception( 'max() expects at least 1 argument.' );
			}
			$nums = array_map( array( $engine, 'to_number' ), $evaluated );
			return max( $nums );
		}

		throw new Expression_Exception(
			'Unknown function "' . esc_html( $this->name ) . '" at position ' . esc_html( $this->pos )
		);
	}
}
