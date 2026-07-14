<?php
/**
 * Earth Goddess AU custom user roles.
 */

defined( 'ABSPATH' ) || exit;

class EG_Roles {

	/**
	 * Role definitions: slug => display name.
	 *
	 * @var array<string,string>
	 */
	const ROLES = array(
		'vip_shopper'                 => 'VIP Shopper',
		'affiliate_business_builder'  => 'Affiliate Business Builder',
		'ambassador'                  => 'Ambassador',
		'wholesale_starter'           => 'Wholesale Starter',
		'wholesale_preferred'         => 'Wholesale Preferred',
		'wholesale_elite'             => 'Wholesale Elite',
	);

	/**
	 * Wholesale role slugs.
	 *
	 * @return string[]
	 */
	public static function wholesale_roles() {
		return array( 'wholesale_starter', 'wholesale_preferred', 'wholesale_elite' );
	}

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_roles' ), 5 );
	}

	/**
	 * Register roles idempotently (customer caps).
	 */
	public static function register_roles() {
		$customer = get_role( 'customer' );
		$caps     = ( $customer && ! empty( $customer->capabilities ) )
			? $customer->capabilities
			: array( 'read' => true );

		foreach ( self::ROLES as $slug => $label ) {
			if ( get_role( $slug ) ) {
				continue;
			}
			add_role( $slug, $label, $caps );
		}
	}

	/**
	 * Whether user has a wholesale role.
	 *
	 * @param int|WP_User|null $user User.
	 * @return bool
	 */
	public static function user_is_wholesale( $user = null ) {
		$user = self::resolve_user( $user );
		if ( ! $user ) {
			return false;
		}
		foreach ( self::wholesale_roles() as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Primary wholesale role slug for a user, if any.
	 *
	 * @param int|WP_User|null $user User.
	 * @return string|null
	 */
	public static function get_wholesale_role( $user = null ) {
		$user = self::resolve_user( $user );
		if ( ! $user ) {
			return null;
		}
		foreach ( self::wholesale_roles() as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return $role;
			}
		}
		return null;
	}

	/**
	 * @param int|WP_User|null $user User.
	 * @return WP_User|null
	 */
	private static function resolve_user( $user ) {
		if ( $user instanceof WP_User ) {
			return $user;
		}
		if ( null === $user ) {
			$user = wp_get_current_user();
		} else {
			$user = get_user_by( 'id', (int) $user );
		}
		return ( $user instanceof WP_User && $user->ID ) ? $user : null;
	}
}
