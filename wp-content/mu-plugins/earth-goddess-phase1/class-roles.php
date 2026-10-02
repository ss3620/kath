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
	 * Starting rank tag for each program role.
	 * Higher ranks are assigned later by hand and are never replaced.
	 *
	 * @var array<string,string>
	 */
	const DEFAULT_RANK_TAGS = array(
		'affiliate_business_builder' => 'clear-quartz-partner',
		'ambassador'                 => 'seed-ambassador',
	);

	/**
	 * Rank tags that mean a program rank is already set.
	 *
	 * @var array<string,string[]>
	 */
	const RANK_TAGS = array(
		'affiliate_business_builder' => array(
			'clear-quartz-partner',
			'amethyst-partner',
			'green-aventurine-partner',
			'moonstone-partner',
		),
		'ambassador'                 => array(
			'seed-ambassador',
			'bloom-ambassador',
			'goddess-ambassador',
		),
	);

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_roles' ), 5 );
		add_action( 'add_user_role', array( __CLASS__, 'assign_default_rank' ), 10, 2 );
		add_action( 'set_user_role', array( __CLASS__, 'assign_default_rank' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'backfill_default_ranks' ) );
	}

	/**
	 * Give a new Affiliate Business Builder or Ambassador their starting rank.
	 *
	 * @param int    $user_id User ID.
	 * @param string $role    Role just added.
	 */
	public static function assign_default_rank( $user_id, $role ) {
		if ( ! isset( self::DEFAULT_RANK_TAGS[ $role ] ) ) {
			return;
		}

		self::ensure_rank_tag( (int) $user_id, $role );
	}

	/**
	 * People given the role before this hook existed (including test affiliates)
	 * never received a rank tag, so their commission plan never matched.
	 */
	public static function backfill_default_ranks() {
		if ( get_option( 'eg_default_rank_tags_v1' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( array_keys( self::DEFAULT_RANK_TAGS ) as $role ) {
			$users = get_users(
				array(
					'role'   => $role,
					'fields' => array( 'ID' ),
				)
			);
			foreach ( $users as $user ) {
				self::ensure_rank_tag( (int) $user->ID, $role );
			}
		}

		update_option( 'eg_default_rank_tags_v1', '1', false );
	}

	/**
	 * Attach the starting rank unless this user already has a rank in that program.
	 *
	 * @param int    $user_id User ID.
	 * @param string $role    Program role.
	 */
	private static function ensure_rank_tag( $user_id, $role ) {
		if ( ! $user_id || empty( self::DEFAULT_RANK_TAGS[ $role ] ) ) {
			return;
		}

		self::register_tag_taxonomy();

		$existing = wp_get_object_terms( $user_id, 'afwc_user_tags', array( 'fields' => 'slugs' ) );
		if ( is_wp_error( $existing ) ) {
			$existing = array();
		}

		$ranks = isset( self::RANK_TAGS[ $role ] ) ? self::RANK_TAGS[ $role ] : array();
		if ( array_intersect( $ranks, $existing ) ) {
			return;
		}

		$slug = self::DEFAULT_RANK_TAGS[ $role ];
		$term = get_term_by( 'slug', $slug, 'afwc_user_tags' );
		if ( ! $term ) {
			$created = wp_insert_term(
				ucwords( str_replace( '-', ' ', $slug ) ),
				'afwc_user_tags',
				array( 'slug' => $slug )
			);
			if ( is_wp_error( $created ) ) {
				return;
			}
			$term = get_term( (int) $created['term_id'], 'afwc_user_tags' );
		}

		if ( $term && ! is_wp_error( $term ) ) {
			wp_set_object_terms( $user_id, array( (int) $term->term_id ), 'afwc_user_tags', true );
		}

		if ( '' === (string) get_user_meta( $user_id, 'afwc_is_affiliate', true ) ) {
			update_user_meta( $user_id, 'afwc_is_affiliate', 'yes' );
		}
	}

	/**
	 * Affiliate for WooCommerce registers this taxonomy. Create it if that plugin
	 * has not loaded yet so the tag can still be stored.
	 */
	private static function register_tag_taxonomy() {
		if ( taxonomy_exists( 'afwc_user_tags' ) ) {
			return;
		}

		register_taxonomy(
			'afwc_user_tags',
			'user',
			array(
				'public' => false,
				'labels' => array( 'name' => 'Affiliate Tags' ),
			)
		);
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
