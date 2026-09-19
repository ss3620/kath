<?php // phpcs:ignore
/**
 * Token representation for the expression lexer.
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula\Lexer;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable token structure.
 */
final class Expression_Token {
	public const T_NUMBER     = 'number';
	public const T_IDENTIFIER = 'identifier';
	public const T_VARIABLE   = 'variable';
	public const T_OPERATOR   = 'operator';
	public const T_LPAREN     = 'lparen';
	public const T_RPAREN     = 'rparen';
	public const T_COMMA      = 'comma';
	public const T_EOF        = 'eof';

	/**
	 * Token type.
	 *
	 * @var string
	 */
	public $type;
	/**
	 * Token value.
	 *
	 * @var mixed
	 */
	public $value;
	/**
	 * Character position within input.
	 *
	 * @var int
	 */
	public $pos;

	/**
	 * Constructs a new Expression_Token instance.
	 *
	 * @param string $type Token type.
	 * @param mixed  $value Token value.
	 * @param int    $pos Character position within input.
	 */
	public function __construct( $type, $value, $pos ) {
		$this->type  = (string) $type;
		$this->value = $value;
		$this->pos   = (int) $pos;
	}
}
