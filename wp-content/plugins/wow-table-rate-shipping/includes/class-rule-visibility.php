<?php // phpcs:ignore

namespace WTRS\Includes;

use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Rule Visibility class.
 */
class RuleVisibility {

	/**
	 * Get rule visibility.
	 *
	 * @param array $rule rule.
	 * @return bool
	 */
	public static function is_viewable( $rule ) {
		$visibility = $rule['visibleToUser'] ?? 'both';

		if ( ! Flags::is_pro_or_can_preview() && ! in_array( $visibility, Flags::RULE_VISIBILITY_FREE_OPTIONS, true ) ) {
			return false;
		}

		if ( 'both' === $visibility ) {
			return true;
		}

		$user = wp_get_current_user();

		if ( 'logged_in' === $visibility ) {
			return $user->ID > 0;
		} elseif ( 'logged_out' === $visibility ) {
			return $user->ID <= 0;
		}
	}
}
