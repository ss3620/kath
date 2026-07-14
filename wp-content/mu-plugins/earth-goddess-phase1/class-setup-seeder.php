<?php
/**
 * Seeds WPLoyalty campaigns/levels/rewards and AFWC tags/commission plans once.
 */

defined( 'ABSPATH' ) || exit;

class EG_Setup_Seeder {

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_seed' ), 20 );
		add_action( 'admin_post_eg_phase1_reseed', array( __CLASS__, 'handle_reseed' ) );
	}

	/**
	 * Run seeding once (or on forced reseed).
	 */
	public static function maybe_seed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_option( EG_PHASE1_OPTION_SEEDED ) ) {
			return;
		}
		self::run();
		update_option( EG_PHASE1_OPTION_SEEDED, EG_PHASE1_VERSION, false );
	}

	/**
	 * Admin-triggered reseed.
	 */
	public static function handle_reseed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'earth-goddess' ) );
		}
		check_admin_referer( 'eg_phase1_reseed' );
		delete_option( EG_PHASE1_OPTION_SEEDED );
		self::run();
		update_option( EG_PHASE1_OPTION_SEEDED, EG_PHASE1_VERSION, false );
		wp_safe_redirect( add_query_arg( array( 'page' => 'eg-phase1-sop', 'eg_seeded' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Execute all seeders.
	 */
	public static function run() {
		self::seed_wployalty();
		self::seed_afwc();
	}

	/**
	 * WPLoyalty: Moon/Star/Goddess levels, earn campaigns, redeem rewards.
	 * Levels use points ≈ annual $ spend (1 pt per $1).
	 */
	private static function seed_wployalty() {
		if ( ! class_exists( '\Wlr\App\Models\EarnCampaign' ) ) {
			return;
		}

		// Levels (Pro). Safe if Lite — save() returns 0.
		if ( class_exists( '\Wlr\App\Models\Levels' ) ) {
			$levels = new \Wlr\App\Models\Levels();
			$wanted = array(
				array(
					'name'        => 'Moon Tier',
					'description' => 'Annual spend $0–$499. 10% member discount. Standard point earning.',
					'from_points' => 0,
					'to_points'   => 499,
					'active'      => 1,
					'text_color'  => '#6B7280',
				),
				array(
					'name'        => 'Star Tier',
					'description' => 'Annual spend $500–$999. 12% discount. Bonus points multiplier.',
					'from_points' => 500,
					'to_points'   => 999,
					'active'      => 1,
					'text_color'  => '#D4AF37',
				),
				array(
					'name'        => 'Goddess Tier',
					'description' => 'Annual spend $1,000+. 15% discount. Birthday gift. Early product access.',
					'from_points' => 1000,
					'to_points'   => 0,
					'active'      => 1,
					'text_color'  => '#7C3AED',
				),
			);
			foreach ( $wanted as $level ) {
				if ( self::wlr_level_exists( $level['name'] ) ) {
					continue;
				}
				$levels->save( $level );
			}
		}

		$campaigns = new \Wlr\App\Models\EarnCampaign();

		self::maybe_save_campaign(
			$campaigns,
			'signup',
			array(
				'name'        => 'Create Account',
				'description' => 'Earn 50 points when creating an account',
				'action_type' => 'signup',
				'point_rule'  => array(
					'earn_point'     => 50,
					'earn_reward'    => '',
					'signup_message' => 'Signup and earn {wlr_points} {wlr_points_label}!',
				),
			)
		);

		self::maybe_save_campaign(
			$campaigns,
			'point_for_purchase',
			array(
				'name'        => 'Place Order',
				'description' => '1 point per $1 spent',
				'action_type' => 'point_for_purchase',
				'point_rule'  => array(
					'wlr_point_earn_price'         => 1,
					'earn_point'                   => 1,
					'earn_reward'                  => '',
					'minimum_point'                => 0,
					'maximum_point'                => 0,
					'variable_product_message'     => 'Earn up to {wlr_product_points} {wlr_points_label}.',
					'single_product_message'       => 'Purchase & earn {wlr_product_points} {wlr_points_label}!',
					'is_rounded_edge'              => 'yes',
					'display_product_message_page' => 'all',
					'product_message_background'   => '',
					'product_message_text_color'   => '',
					'product_message_border_color' => '',
				),
			)
		);

		self::maybe_save_campaign(
			$campaigns,
			'product_review',
			array(
				'name'        => 'Product Review',
				'description' => 'Earn 50 points for a product review',
				'action_type' => 'product_review',
				'point_rule'  => array(
					'earn_point'  => 50,
					'earn_reward' => '',
				),
			)
		);

		self::maybe_save_campaign(
			$campaigns,
			'birthday',
			array(
				'name'        => 'Birthday Reward',
				'description' => 'Earn 250 points on birthday',
				'action_type' => 'birthday',
				'point_rule'  => array(
					'earn_point'               => 250,
					'earn_reward'              => '',
					'birthday_earn_type'       => 'on_their_birthday',
					'birthday_message'         => 'Happy birthday! You earned {wlr_points} {wlr_points_label}!',
				),
			)
		);

		self::maybe_save_campaign(
			$campaigns,
			'referral',
			array(
				'name'        => 'Referral Purchase',
				'description' => 'Advocate earns 100 points on referral purchase',
				'action_type' => 'referral',
				'point_rule'  => array(
					'advocate' => array(
						'campaign_type' => 'point',
						'earn_type'     => 'fixed_point',
						'earn_point'    => 100,
						'earn_reward'   => '',
					),
					'friend'   => array(
						'campaign_type' => 'point',
						'earn_type'     => 'fixed_point',
						'earn_point'    => 50,
						'earn_reward'   => '',
					),
				),
			)
		);

		if ( class_exists( '\Wlr\App\Models\Rewards' ) ) {
			$rewards = new \Wlr\App\Models\Rewards();
			$reward_defs = array(
				array(
					'name'           => 'Store Credit Conversion',
					'description'    => 'Convert points into store credit / cart discount',
					'display_name'   => 'Store Credit',
					'discount_type'  => 'points_conversion',
					'discount_value' => 1,
					'require_point'  => 100,
				),
				array(
					'name'           => 'Discount Coupon 10%',
					'description'    => 'Redeem points for a 10% discount coupon',
					'display_name'   => '10% Off Coupon',
					'discount_type'  => 'percent',
					'discount_value' => 10,
					'require_point'  => 500,
				),
				array(
					'name'           => 'Special Offer $20 Off',
					'description'    => 'Redeem points for a $20 fixed cart coupon',
					'display_name'   => '$20 Off',
					'discount_type'  => 'fixed_cart',
					'discount_value' => 20,
					'require_point'  => 800,
				),
			);
			foreach ( $reward_defs as $def ) {
				if ( self::wlr_reward_exists( $def['name'] ) ) {
					continue;
				}
				$rewards->save(
					array_merge(
						array(
							'reward_type'            => 'redeem_point',
							'active'                 => 1,
							'ordering'               => 0,
							'is_show_reward'         => 1,
							'icon'                   => '',
							'free_product'           => array(),
							'expire_after'           => 0,
							'expire_period'          => 'day',
							'enable_expiry_email'    => 0,
							'expire_email'           => 0,
							'expire_email_period'    => 'day',
							'usage_limits'           => 0,
							'condition_relationship' => 'and',
							'conditions'             => '',
							'minimum_point'          => 0,
							'maximum_point'          => 0,
						),
						$def
					)
				);
			}
		}
	}

	/**
	 * @param string $name Level name.
	 * @return bool
	 */
	private static function wlr_level_exists( $name ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wlr_levels';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return false;
		}
		$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s LIMIT 1", $name ) ); // phpcs:ignore
		return ! empty( $found );
	}

	/**
	 * @param string $name Reward name.
	 * @return bool
	 */
	private static function wlr_reward_exists( $name ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wlr_rewards';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore
			return false;
		}
		$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s LIMIT 1", $name ) ); // phpcs:ignore
		return ! empty( $found );
	}

	/**
	 * @param \Wlr\App\Models\EarnCampaign $model Model.
	 * @param string                       $action_type Action type.
	 * @param array                        $data Data.
	 */
	private static function maybe_save_campaign( $model, $action_type, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wlr_earn_campaign';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore
			return;
		}
		$existing = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE action_type = %s AND name = %s LIMIT 1",
				$action_type,
				$data['name']
			)
		);
		if ( $existing ) {
			return;
		}
		$model->save(
			array_merge(
				array(
					'levels'                 => '',
					'active'                 => 1,
					'ordering'               => 0,
					'is_show_way_to_earn'    => 1,
					'start_at'               => 0,
					'end_at'                 => 0,
					'icon'                   => '',
					'campaign_type'          => 'point',
					'usage_limits'           => 0,
					'condition_relationship' => 'and',
					'conditions'             => '',
					'priority'               => 0,
				),
				$data
			)
		);
	}

	/**
	 * AFWC: enable multi-tier, tags, commission plans.
	 */
	private static function seed_afwc() {
		if ( ! class_exists( 'Affiliate_For_WooCommerce' ) ) {
			return;
		}

		update_option( 'afwc_enable_multi_tier', 'yes', false );

		// Allow our program roles to count as affiliate roles in AFWC settings.
		$affiliate_roles = get_option( 'affiliate_users_roles', array() );
		if ( ! is_array( $affiliate_roles ) ) {
			$affiliate_roles = array();
		}
		foreach ( array( 'affiliate_business_builder', 'ambassador' ) as $role ) {
			if ( ! in_array( $role, $affiliate_roles, true ) ) {
				$affiliate_roles[] = $role;
			}
		}
		update_option( 'affiliate_users_roles', $affiliate_roles, false );

		$builder_tags = array(
			'clear-quartz-partner'     => 'Clear Quartz Partner',
			'amethyst-partner'         => 'Amethyst Partner',
			'green-aventurine-partner' => 'Green Aventurine Partner',
			'moonstone-partner'        => 'Moonstone Partner',
		);
		$ambassador_tags = array(
			'seed-ambassador'            => 'Seed Ambassador',
			'bloom-ambassador'           => 'Bloom Ambassador',
			'goddess-ambassador'         => 'Goddess Ambassador',
			'key-of-growth'              => 'Leadership: Key of Growth',
			'moon-keeper'                => 'Leadership: Moon Keeper',
			'guiding-star'               => 'Leadership: Guiding Star',
			'celestial-legacy'           => 'Leadership: Celestial Legacy',
		);

		$tag_ids = array();
		foreach ( array_merge( $builder_tags, $ambassador_tags ) as $slug => $name ) {
			$tag_ids[ $slug ] = self::ensure_afwc_tag( $slug, $name );
		}

		// Business Builder plans (multi-tier where applicable).
		self::upsert_afwc_plan(
			'EG Clear Quartz Partner',
			15,
			1,
			'',
			self::tag_rule( array( $tag_ids['clear-quartz-partner'] ) )
		);
		self::upsert_afwc_plan(
			'EG Amethyst Partner',
			20,
			2,
			'3',
			self::tag_rule( array( $tag_ids['amethyst-partner'] ) )
		);
		self::upsert_afwc_plan(
			'EG Green Aventurine Partner',
			25,
			3,
			'5|3',
			self::tag_rule( array( $tag_ids['green-aventurine-partner'] ) )
		);
		self::upsert_afwc_plan(
			'EG Moonstone Partner',
			30,
			4,
			'5|4|2',
			self::tag_rule( array( $tag_ids['moonstone-partner'] ) )
		);

		// Ambassador plans — no downline (1 tier).
		self::upsert_afwc_plan(
			'EG Seed Ambassador',
			20,
			1,
			'',
			self::tag_rule( array( $tag_ids['seed-ambassador'] ) )
		);
		self::upsert_afwc_plan(
			'EG Bloom Ambassador',
			25,
			1,
			'',
			self::tag_rule( array( $tag_ids['bloom-ambassador'] ) )
		);
		self::upsert_afwc_plan(
			'EG Goddess Ambassador',
			30,
			1,
			'',
			self::tag_rule( array( $tag_ids['goddess-ambassador'] ) )
		);

		// Wholesale referral: 5% ongoing when buyer has wholesale role.
		self::upsert_afwc_plan(
			'EG Wholesale Referral 5%',
			5,
			1,
			'',
			self::user_role_rule(
				array(
					'wholesale_starter',
					'wholesale_preferred',
					'wholesale_elite',
				)
			),
			'continue'
		);

		// Prefer these plans in order (more specific first).
		global $wpdb;
		$names = array(
			'EG Wholesale Referral 5%',
			'EG Moonstone Partner',
			'EG Green Aventurine Partner',
			'EG Amethyst Partner',
			'EG Clear Quartz Partner',
			'EG Goddess Ambassador',
			'EG Bloom Ambassador',
			'EG Seed Ambassador',
		);
		$order = array();
		foreach ( $names as $name ) {
			$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}afwc_commission_plans WHERE name = %s LIMIT 1", $name ) ); // phpcs:ignore
			if ( $id ) {
				$order[] = $id;
			}
		}
		$existing_order = get_option( 'afwc_plan_order', array() );
		if ( ! is_array( $existing_order ) ) {
			$existing_order = array();
		}
		update_option( 'afwc_plan_order', array_values( array_unique( array_merge( $order, $existing_order ) ) ), false );
	}

	/**
	 * @param string $slug Slug.
	 * @param string $name Name.
	 * @return int Term ID.
	 */
	private static function ensure_afwc_tag( $slug, $name ) {
		if ( ! taxonomy_exists( 'afwc_user_tags' ) ) {
			// Register early if AFWC hasn't yet on this request.
			register_taxonomy(
				'afwc_user_tags',
				'user',
				array(
					'public' => false,
					'labels' => array( 'name' => 'Affiliate Tags' ),
				)
			);
		}
		$term = get_term_by( 'slug', $slug, 'afwc_user_tags' );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}
		$result = wp_insert_term( $name, 'afwc_user_tags', array( 'slug' => $slug ) );
		if ( is_wp_error( $result ) ) {
			return 0;
		}
		return (int) $result['term_id'];
	}

	/**
	 * @param int[] $term_ids Term IDs.
	 * @return array
	 */
	private static function tag_rule( $term_ids ) {
		$term_ids = array_values( array_filter( array_map( 'intval', $term_ids ) ) );
		return array(
			'condition' => 'AND',
			'rules'     => array(
				array(
					'condition' => 'AND',
					'rules'     => array(
						array(
							'type'     => 'affiliate_tag',
							'operator' => 'in',
							'value'    => $term_ids,
						),
					),
				),
			),
		);
	}

	/**
	 * @param string[] $roles Role slugs.
	 * @return array
	 */
	private static function user_role_rule( $roles ) {
		return array(
			'condition' => 'AND',
			'rules'     => array(
				array(
					'condition' => 'AND',
					'rules'     => array(
						array(
							'type'     => 'user_role',
							'operator' => 'in',
							'value'    => array_values( $roles ),
						),
					),
				),
			),
		);
	}

	/**
	 * Insert commission plan if name not present.
	 *
	 * @param string $name Name.
	 * @param float  $amount Amount %.
	 * @param int    $tiers Tier count.
	 * @param string $distribution Distribution string.
	 * @param array  $rules Rules.
	 * @param string $action_remaining Remaining action.
	 */
	private static function upsert_afwc_plan( $name, $amount, $tiers, $distribution, $rules, $action_remaining = 'continue' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'afwc_commission_plans';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore
			return;
		}
		$existing = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s LIMIT 1", $name ) ); // phpcs:ignore
		if ( $existing ) {
			$wpdb->update( // phpcs:ignore
				$table,
				array(
					'rules'                => wp_json_encode( $rules ),
					'amount'               => (float) $amount,
					'type'                 => 'Percentage',
					'status'               => 'Active',
					'apply_to'             => 'all',
					'action_for_remaining' => $action_remaining,
					'no_of_tiers'          => (int) $tiers,
					'distribution'         => $distribution,
				),
				array( 'id' => $existing )
			);
			return;
		}

		$wpdb->insert( // phpcs:ignore
			$table,
			array(
				'name'                 => $name,
				'rules'                => wp_json_encode( $rules ),
				'amount'               => (float) $amount,
				'type'                 => 'Percentage',
				'status'               => 'Active',
				'apply_to'             => 'all',
				'action_for_remaining' => $action_remaining,
				'no_of_tiers'          => (int) $tiers,
				'distribution'         => $distribution,
				'meta_data'            => wp_json_encode( array( 'template' => 'eg-phase1' ) ),
			)
		);
	}
}
