<?php // phpcs:ignore
/**
 * Expression parsing / evaluation exceptions.
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown when an expression cannot be tokenized, parsed, or evaluated.
 */
class Expression_Exception extends \Exception {
}
