<?php //phpcs:ignore
/**
 * Class SafeMathEvaluator
 *
 * @package WowAddons
 */

namespace PRAD\Includes\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SafeMathEvaluator class.
 */
class SafeMathEvaluator {

	/**
	 * Evaluates a mathematical expression safely, replacing dynamic variables and handling percentages.
	 *
	 * @param string $expression The mathematical expression to evaluate.
	 * @param array  $dynamic_variables Associative array of dynamic variables to replace in the expression.
	 * @return float The evaluated result or 0 on error.
	 */
	public static function evaluate_expression( $expression, $dynamic_variables = array() ) {
		$expression = sanitize_text_field( $expression );

		// Replace dynamic variables.
		$processed_expression = preg_replace_callback(
			'/\{\{([a-zA-Z0-9_-]+)\}\}/',
			function ( $matches ) use ( $dynamic_variables ) {
				return ! empty( $dynamic_variables[ $matches[1] ] ) ? $dynamic_variables[ $matches[1] ] : 0;
			},
			$expression
		);

		// Handle percentages: convert 'X%' to '(X/100)'.
		$processed_expression = preg_replace( '/(\d+(?:\.\d+)?)%/', '($1/100)', $processed_expression );

		try {
			$result = self::safe_evaluate( $processed_expression );
			return is_numeric( $result ) && $result >= 0 ? (float) $result : 0;
		} catch ( \Exception $e ) {
			echo esc_html( 'Error evaluating expression: ' . $e->getMessage() );
			return 0;
		}
	}

	/**
	 * Safely evaluates a sanitized mathematical expression.
	 *
	 * @param string $expression The sanitized mathematical expression to evaluate.
	 * @return float The evaluated result.
	 * @throws \Exception If the expression contains invalid characters or unbalanced parentheses.
	 */
	public static function safe_evaluate( $expression ) {
		// Remove whitespace.
		$expression = preg_replace( '/\s+/', '', $expression );

		// Validate expression contains only allowed characters.
		if ( ! preg_match( '/^[0-9+\-*\/().]+$/', $expression ) ) {
			throw new \Exception( 'Invalid characters in expression' );
		}

		// Check for balanced parentheses.
		if ( ! self::has_balanced_parentheses( $expression ) ) {
			throw new \Exception( 'Unbalanced parentheses' );
		}

		// Evaluate the expression.
		return self::parse_expression( $expression );
	}

	/**
	 * Checks if the given expression has balanced parentheses.
	 *
	 * @param string $expression The expression to check.
	 * @return bool True if parentheses are balanced, false otherwise.
	 */
	public static function has_balanced_parentheses( $expression ) {
		$count      = 0;
		$exp_length = strlen( $expression );
		for ( $i = 0; $i < $exp_length; $i++ ) {
			if ( '(' === $expression[ $i ] ) {
				++$count;
			} elseif ( ')' === $expression[ $i ] ) {
				--$count;
				if ( $count < 0 ) {
					return false;
				}
			}
		}
		return 0 === $count;
	}

	/**
	 * Parses and evaluates a mathematical expression, handling parentheses recursively.
	 *
	 * @param string $expression The mathematical expression to parse and evaluate.
	 * @return float The evaluated result.
	 * @throws \Exception If the expression is invalid.
	 */
	public static function parse_expression( $expression ) {
		// Handle parentheses first.
		while ( strpos( $expression, '(' ) !== false ) {
			$expression = preg_replace_callback(
				'/\(([^()]+)\)/',
				function ( $matches ) {
					return self::evaluate_simple_expression( $matches[1] );
				},
				$expression
			);
		}

		return self::evaluate_simple_expression( $expression );
	}

	/**
	 * Evaluates a simple mathematical expression without parentheses.
	 *
	 * Handles multiplication, division, addition, and subtraction operations.
	 *
	 * @param string $expression The mathematical expression to evaluate.
	 * @return float The evaluated result.
	 * @throws \Exception If the expression is invalid or division by zero occurs.
	 */
	public static function evaluate_simple_expression( $expression ) {
		// Handle multiplication and division first (left to right).
		while ( preg_match( '/(-?\d+(?:\.\d+)?)\s*([*\/])\s*(-?\d+(?:\.\d+)?)/', $expression, $matches ) ) {
			$left     = (float) $matches[1];
			$operator = $matches[2];
			$right    = (float) $matches[3];

			if ( '*' === $operator ) {
				$result = $left * $right;
			} elseif ( '/' === $operator ) {
				if ( 0 == $right ) {
					throw new \Exception( 'Division by zero' );
				}
				$result = $left / $right;
			}

			$expression = str_replace( $matches[0], $result, $expression );
		}

		// Handle addition and subtraction (left to right).
		while ( preg_match( '/(-?\d+(?:\.\d+)?)\s*([+\-])\s*(-?\d+(?:\.\d+)?)/', $expression, $matches ) ) {
			$left     = (float) $matches[1];
			$operator = $matches[2];
			$right    = (float) $matches[3];

			if ( '+' === $operator ) {
				$result = $left + $right;
			} elseif ( '-' === $operator ) {
				$result = $left - $right;
			}

			$expression = str_replace( $matches[0], $result, $expression );
		}

		// Should be left with just a number.
		if ( ! is_numeric( $expression ) ) {
			throw new \Exception( 'Invalid expression result: ' . esc_html( $expression ) );
		}

		return (float) $expression;
	}
}
