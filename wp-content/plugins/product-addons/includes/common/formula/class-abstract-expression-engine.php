<?php // phpcs:ignore
/**
 * Abstract expression engine.
 *
 * Supports:
 * - Dynamic placeholders: [product_price], [Label.options.opt_label.checked], etc.
 * - Operators: >, <, >=, <=, !=, =, &, ||, (, ), +, -, *, /
 * - Functions: if, abs, ceil, floor, max, min, pow, round
 *
 * This engine does not use eval(); it tokenizes, parses, and evaluates safely.
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula;

use PRAD\Includes\Common\Formula\Lexer\Expression_Lexer;
use PRAD\Includes\Common\Formula\Parser\Expression_Parser;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for evaluating formulas.
 *
 * Extend this class and implement variable resolution via get_dynamic_value().
 */
abstract class Abstract_Expression_Engine {

	/**
	 * Evaluate an expression.
	 *
	 * @param string $expression The expression (e.g. "min(1,2)+if([x]>0,10,0)").
	 * @param array  $context Optional context passed to get_dynamic_value().
	 *
	 * @return mixed Numeric results are returned as float; booleans may be returned for pure conditions.
	 *
	 * @throws Expression_Exception When tokenization, parsing, or evaluation fails.
	 */
	public function evaluate( $expression, array $context = array() ) {
		if ( ! is_string( $expression ) ) {
			throw new Expression_Exception( 'Expression must be a string.' );
		}

		$lexer  = new Expression_Lexer( $expression );
		$tokens = $lexer->tokenize();

		$parser = new Expression_Parser( $tokens );
		$node   = $parser->parse();

		$result = $node->evaluate( $this, $context );

		if ( is_bool( $result ) ) {
			return $result;
		}
		if ( is_numeric( $result ) ) {
			return $this->normalize_number( $result );
		}

		return $result;
	}

	/**
	 * Normalize numeric return values.
	 *
	 * Internally, evaluation is done using floats (via to_number()). For output, this method:
	 * - returns an int if the value is effectively a whole number
	 * - otherwise returns a float
	 *
	 * This helps keep results natural for expressions like: min(21.2, 10) => 10
	 *
	 * @param mixed $value Numeric value.
	 * @return int|float
	 */
	public function normalize_number( $value ) {
		$num = (float) $value;

		// Floating point tolerance for whole-number detection.
		$rounded = round( $num );
		if ( abs( $num - $rounded ) < 1e-9 ) {
			return (int) $rounded;
		}

		return $num;
	}

	/**
	 * Resolve a dynamic placeholder value.
	 *
	 * Placeholders appear in the expression as "[name]" and are provided here without brackets.
	 *
	 * Implementations should return:
	 * - numeric (int/float/string numeric) for calculations
	 * - boolean for conditions
	 * - null (treated as 0 in numeric contexts)
	 *
	 * @param string $name Placeholder name inside brackets.
	 * @param array  $context Context passed to evaluate().
	 *
	 * @return mixed
	 */
	abstract public function get_dynamic_value( $name, array $context = array() );

	/**
	 * Convert a value to a number for arithmetic.
	 *
	 * @param mixed $value Any value.
	 * @return float
	 */
	public function to_number( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? 1.0 : 0.0;
		}
		if ( null === $value ) {
			return 0.0;
		}
		if ( is_numeric( $value ) ) {
			return (float) $value;
		}
		return 0.0;
	}

	/**
	 * Convert a value to boolean for logical operators.
	 *
	 * @param mixed $value Any value.
	 * @return bool
	 */
	public function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_numeric( $value ) ) {
			return 0.0 !== (float) $value;
		}
		return ! empty( $value );
	}
}
