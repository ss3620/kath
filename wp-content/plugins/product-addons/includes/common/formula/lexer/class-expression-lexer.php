<?php // phpcs:ignore
/**
 * Expression lexer/tokenizer.
 *
 * @package PRAD
 * @since 1.5.8
 */

namespace PRAD\Includes\Common\Formula\Lexer;

use PRAD\Includes\Common\Formula\Expression_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Converts an expression string into a token stream.
 */
final class Expression_Lexer {
	/**
	 * The input expression string.
	 *
	 * @var string
	 */
	private $input;
	/**
	 * The length of the input expression string.
	 *
	 * @var int
	 */
	private $len;
	/**
	 * The current position in the input string.
	 *
	 * @var int
	 */
	private $i = 0;

	/**
	 * Constructor for Expression_Lexer.
	 *
	 * @param string $input The input expression string.
	 */
	public function __construct( $input ) {
		$this->input = (string) $input;
		$this->len   = strlen( $this->input );
	}

	/**
	 * Tokenizes the input expression string into an array of tokens.
	 *
	 * @return Expression_Token[]
	 * @throws Expression_Exception When tokenization fails.
	 */
	public function tokenize() {
		$tokens = array();

		while ( $this->i < $this->len ) {
			$ch = $this->input[ $this->i ];

			if ( ctype_space( $ch ) ) {
				++$this->i;
				continue;
			}

			// Dynamic placeholder: [ ... ].
			if ( '[' === $ch ) {
				$start = $this->i;
				++$this->i;
				$name = '';
				while ( $this->i < $this->len && ']' !== $this->input[ $this->i ] ) {
					$name .= $this->input[ $this->i ];
					++$this->i;
				}
				if ( $this->i >= $this->len || ']' !== $this->input[ $this->i ] ) {
					throw new Expression_Exception( 'Unclosed dynamic placeholder starting at position ' . esc_html( $start ) );
				}
				++$this->i;
				$name = trim( $name );
				if ( '' === $name ) {
					throw new Expression_Exception( 'Empty dynamic placeholder at position ' . esc_html( $start ) );
				}
				$tokens[] = new Expression_Token( Expression_Token::T_VARIABLE, $name, $start );
				continue;
			}

			// Numbers: 12, 12.34, .5.
			if ( ctype_digit( $ch ) || '.' === $ch ) {
				$start  = $this->i;
				$number = '';
				$dot    = 0;
				while ( $this->i < $this->len ) {
					$c = $this->input[ $this->i ];
					if ( '.' === $c ) {
						++$dot;
						if ( $dot > 1 ) {
							break;
						}
						$number .= $c;
						++$this->i;
						continue;
					}
					if ( ctype_digit( $c ) ) {
						$number .= $c;
						++$this->i;
						continue;
					}
					break;
				}

				if ( '.' === $number ) {
					throw new Expression_Exception( 'Invalid number at position ' . esc_html( $start ) );
				}
				$tokens[] = new Expression_Token( Expression_Token::T_NUMBER, (float) $number, $start );
				continue;
			}

			// Identifiers (function names): if, min, max...
			if ( ctype_alpha( $ch ) || '_' === $ch ) {
				$start = $this->i;
				$id    = '';
				while ( $this->i < $this->len ) {
					$c = $this->input[ $this->i ];
					if ( ctype_alnum( $c ) || '_' === $c ) {
						$id .= $c;
						++$this->i;
						continue;
					}
					break;
				}
				$tokens[] = new Expression_Token( Expression_Token::T_IDENTIFIER, $id, $start );
				continue;
			}

			// Punctuation.
			if ( '(' === $ch ) {
				$tokens[] = new Expression_Token( Expression_Token::T_LPAREN, '(', $this->i );
				++$this->i;
				continue;
			}
			if ( ')' === $ch ) {
				$tokens[] = new Expression_Token( Expression_Token::T_RPAREN, ')', $this->i );
				++$this->i;
				continue;
			}
			if ( ',' === $ch ) {
				$tokens[] = new Expression_Token( Expression_Token::T_COMMA, ',', $this->i );
				++$this->i;
				continue;
			}

			// Operators (2-char first).
			$two = ( $this->i + 1 < $this->len ) ? $ch . $this->input[ $this->i + 1 ] : '';
			if ( '>=' === $two || '<=' === $two || '!=' === $two || '||' === $two ) {
				$tokens[] = new Expression_Token( Expression_Token::T_OPERATOR, $two, $this->i );
				$this->i += 2;
				continue;
			}

			if ( '>' === $ch || '<' === $ch || '=' === $ch || '&' === $ch || '+' === $ch || '-' === $ch || '*' === $ch || '/' === $ch ) {
				$tokens[] = new Expression_Token( Expression_Token::T_OPERATOR, $ch, $this->i );
				++$this->i;
				continue;
			}

			throw new Expression_Exception( 'Unexpected character "' . esc_html( $ch ) . '" at position ' . esc_html( $this->i ) );
		}

		$tokens[] = new Expression_Token( Expression_Token::T_EOF, null, $this->len );
		return $tokens;
	}
}
