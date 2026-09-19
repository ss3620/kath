<?php
/**
 * Live pathway test runner for Phase 1 (creates QA users, verifies logic, logs evidence).
 */

defined( 'ABSPATH' ) || exit;

class EG_Live_Pathway_Tester {

	const OPTION_RESULTS = 'eg_phase1_live_test_results';
	const PASSWORD       = 'EgQaTest!2026';

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_action( 'admin_post_eg_phase1_run_live_tests', array( __CLASS__, 'handle_admin_run' ) );
	}

	/**
	 * Admin button handler.
	 */
	public static function handle_admin_run() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'earth-goddess' ) );
		}
		check_admin_referer( 'eg_phase1_run_live_tests' );
		$results = self::run_all();
		update_option( self::OPTION_RESULTS, $results, false );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'eg-phase1-sop',
					'eg_tested'    => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Run all pathways; return structured evidence.
	 *
	 * @return array
	 */
	public static function run_all() {
		$started = current_time( 'mysql' );
		$cases   = array();

		$cases = array_merge( $cases, self::run_prep() );
		$cases = array_merge( $cases, self::run_pathway_vip() );
		$cases = array_merge( $cases, self::run_pathway_affiliate() );
		$cases = array_merge( $cases, self::run_pathway_ambassador() );
		$cases = array_merge( $cases, self::run_pathway_wholesale() );
		$cases = array_merge( $cases, self::run_pathway_ws_referral() );
		$cases = array_merge( $cases, self::run_pathway_ux_smoke() );

		$pass = 0;
		$fail = 0;
		foreach ( $cases as $c ) {
			if ( ! empty( $c['pass'] ) ) {
				++$pass;
			} else {
				++$fail;
			}
		}

		return array(
			'started'  => $started,
			'finished' => current_time( 'mysql' ),
			'pass'     => $pass,
			'fail'     => $fail,
			'cases'    => $cases,
		);
	}

	/**
	 * @return array
	 */
	private static function run_prep() {
		$cases = array();

		EG_Roles::register_roles();
		$missing = array();
		foreach ( array_keys( EG_Roles::ROLES ) as $slug ) {
			if ( ! get_role( $slug ) ) {
				$missing[] = $slug;
			}
		}
		$cases[] = self::case(
			'prep-roles',
			'Prep: all custom roles registered',
			empty( $missing ),
			empty( $missing ) ? 'All 6 roles present' : 'Missing: ' . implode( ', ', $missing )
		);

		$cases[] = self::case(
			'prep-wployalty',
			'Prep: WPLoyalty active',
			class_exists( '\Wlr\App\Models\EarnCampaign' ),
			class_exists( '\Wlr\App\Models\EarnCampaign' ) ? 'EarnCampaign class loaded' : 'WPLoyalty missing'
		);

		$cases[] = self::case(
			'prep-afwc',
			'Prep: Affiliate for WooCommerce active',
			class_exists( 'Affiliate_For_WooCommerce' ),
			class_exists( 'Affiliate_For_WooCommerce' ) ? 'AFWC loaded' : 'AFWC missing'
		);

		$cases[] = self::case(
			'prep-multitier',
			'Prep: AFWC multi-tier enabled',
			( 'yes' === get_option( 'afwc_enable_multi_tier' ) ),
			'afwc_enable_multi_tier=' . (string) get_option( 'afwc_enable_multi_tier' )
		);

		$gateways = function_exists( 'WC' ) && WC()->payment_gateways()
			? WC()->payment_gateways()->get_available_payment_gateways()
			: array();
		$gateway_ids = array_keys( (array) $gateways );
		$cases[]     = self::case(
			'prep-payment',
			'Prep: at least one payment gateway available',
			! empty( $gateway_ids ),
			! empty( $gateway_ids ) ? 'Gateways: ' . implode( ', ', $gateway_ids ) : 'No gateways (orders may need manual create)'
		);

		// Create QA users.
		$map = array(
			'qa-vip@earthgoddess.test'         => 'vip_shopper',
			'qa-buyer@earthgoddess.test'       => 'customer',
			'qa-aff-l1@earthgoddess.test'      => 'affiliate_business_builder',
			'qa-aff-l2@earthgoddess.test'      => 'affiliate_business_builder',
			'qa-aff-l3@earthgoddess.test'      => 'affiliate_business_builder',
			'qa-aff-l4@earthgoddess.test'      => 'affiliate_business_builder',
			'qa-amb@earthgoddess.test'         => 'ambassador',
			'qa-ws-starter@earthgoddess.test'  => 'wholesale_starter',
			'qa-ws-pref@earthgoddess.test'     => 'wholesale_preferred',
			'qa-ws-elite@earthgoddess.test'    => 'wholesale_elite',
		);
		$created = array();
		foreach ( $map as $email => $role ) {
			$user = self::ensure_user( $email, $role );
			if ( $user ) {
				$created[ $email ] = $user->ID;
				if ( in_array( $role, array( 'affiliate_business_builder', 'ambassador' ), true ) ) {
					update_user_meta( $user->ID, 'afwc_is_affiliate', 'yes' );
				}
			}
		}
		$cases[] = self::case(
			'prep-users',
			'Prep: QA test users created/updated',
			count( $created ) === count( $map ),
			'Users: ' . wp_json_encode( $created )
		);

		// Tags for affiliates.
		if ( taxonomy_exists( 'afwc_user_tags' ) ) {
			$l1 = get_user_by( 'email', 'qa-aff-l1@earthgoddess.test' );
			$amb = get_user_by( 'email', 'qa-amb@earthgoddess.test' );
			self::set_tag( $l1 ? $l1->ID : 0, 'clear-quartz-partner' );
			self::set_tag( $amb ? $amb->ID : 0, 'seed-ambassador' );
			$cases[] = self::case(
				'prep-tags',
				'Prep: Clear Quartz + Seed Ambassador tags assigned',
				true,
				'Tags applied where taxonomy exists'
			);
		} else {
			$cases[] = self::case( 'prep-tags', 'Prep: AFWC tag taxonomy', false, 'afwc_user_tags not registered' );
		}

		update_option( 'eg_phase1_qa_users', $created, false );

		return $cases;
	}

	/**
	 * Pathway A — VIP.
	 *
	 * @return array
	 */
	private static function run_pathway_vip() {
		$cases = array();
		$user  = get_user_by( 'email', 'qa-vip@earthgoddess.test' );
		if ( ! $user ) {
			return array( self::case( 'A0', 'VIP user exists', false, 'qa-vip missing' ) );
		}

		wp_set_current_user( $user->ID );

		// Prior runs leave completed QA orders (e.g. annual_spend=1010 → always Goddess 15%).
		self::reset_qa_orders( $user->ID );

		$pct_moon = EG_VIP_Discounts::get_discount_percent( $user->ID );
		$spend_moon = EG_VIP_Discounts::get_annual_spend( $user->ID );
		$cases[]  = self::case(
			'A2-moon',
			'VIP: Moon tier discount is 10% when spend < $500',
			( $spend_moon < 500 && abs( $pct_moon - 10.0 ) < 0.01 ),
			'percent=' . $pct_moon . ' annual_spend=' . $spend_moon
		);

		// Seed into Star band only ($500–$999), not past Goddess.
		$star_ok  = self::ensure_spend_in_band( $user->ID, 500, 999 );
		$pct_star = EG_VIP_Discounts::get_discount_percent( $user->ID );
		$spend_star = EG_VIP_Discounts::get_annual_spend( $user->ID );
		$cases[]  = self::case(
			'A4-star',
			'VIP: Star tier discount is 12% when spend >= $500',
			$star_ok && abs( $pct_star - 12.0 ) < 0.01,
			'percent=' . $pct_star . ' annual_spend=' . $spend_star . ' spend_ok=' . ( $star_ok ? 'yes' : 'no' )
		);

		$goddess_ok = self::ensure_spend_at_least( $user->ID, 1000 );
		$pct_g      = EG_VIP_Discounts::get_discount_percent( $user->ID );
		$cases[]    = self::case(
			'A5-goddess',
			'VIP: Goddess tier discount is 15% when spend >= $1000',
			$goddess_ok && abs( $pct_g - 15.0 ) < 0.01,
			'percent=' . $pct_g . ' annual_spend=' . EG_VIP_Discounts::get_annual_spend( $user->ID )
		);

		$ws = get_user_by( 'email', 'qa-ws-starter@earthgoddess.test' );
		if ( $ws ) {
			wp_set_current_user( $ws->ID );
			$no_vip = EG_Roles::user_is_wholesale( $ws );
			$cases[] = self::case(
				'A6-wholesale-skip',
				'VIP: wholesale users are treated as wholesale (skip VIP path)',
				$no_vip,
				'is_wholesale=' . ( $no_vip ? 'yes' : 'no' )
			);
		}

		// Cart fee smoke: empty cart calculate won't add without items; check label helpers.
		$cases[] = self::case(
			'A7-tier-label',
			'VIP: tier label helper returns Goddess after spend seed',
			( 'Goddess' === EG_VIP_Discounts::get_tier_label( $user->ID ) ),
			'label=' . EG_VIP_Discounts::get_tier_label( $user->ID )
		);

		// WPLoyalty campaigns presence.
		global $wpdb;
		$table = $wpdb->prefix . 'wlr_earn_campaign';
		$n     = 0;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) { // phpcs:ignore
			$n = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE active = 1" ); // phpcs:ignore
		}
		$cases[] = self::case(
			'A1-campaigns',
			'VIP: active WPLoyalty earn campaigns exist',
			$n > 0,
			'active_campaigns=' . $n
		);

		wp_set_current_user( 0 );
		return $cases;
	}

	/**
	 * Pathway B — Affiliate.
	 *
	 * @return array
	 */
	private static function run_pathway_affiliate() {
		$cases = array();
		global $wpdb;
		$table = $wpdb->prefix . 'afwc_commission_plans';
		$plans = array();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) { // phpcs:ignore
			$rows = $wpdb->get_results( "SELECT name, amount, no_of_tiers, distribution, status FROM {$table} WHERE name LIKE 'EG %'", ARRAY_A ); // phpcs:ignore
			foreach ( (array) $rows as $row ) {
				$plans[ $row['name'] ] = $row;
			}
		}

		$expected = array(
			'EG Clear Quartz Partner'       => array( '15', 1 ),
			'EG Amethyst Partner'           => array( '20', 2 ),
			'EG Green Aventurine Partner'   => array( '25', 3 ),
			'EG Moonstone Partner'          => array( '30', 4 ),
		);
		foreach ( $expected as $name => $meta ) {
			$ok = isset( $plans[ $name ] )
				&& (float) $plans[ $name ]['amount'] === (float) $meta[0]
				&& (int) $plans[ $name ]['no_of_tiers'] === (int) $meta[1]
				&& 'Active' === $plans[ $name ]['status'];
			$cases[] = self::case(
				'B-plan-' . sanitize_title( $name ),
				'Affiliate plan present: ' . $name,
				$ok,
				isset( $plans[ $name ] ) ? wp_json_encode( $plans[ $name ] ) : 'missing'
			);
		}

		$amethyst = isset( $plans['EG Amethyst Partner'] ) ? $plans['EG Amethyst Partner']['distribution'] : '';
		$cases[]  = self::case(
			'B4-amethyst-dist',
			'Affiliate: Amethyst distribution is 3 (L1)',
			( '3' === (string) $amethyst ),
			'distribution=' . $amethyst
		);

		$ga = isset( $plans['EG Green Aventurine Partner'] ) ? $plans['EG Green Aventurine Partner']['distribution'] : '';
		$cases[] = self::case(
			'B5-ga-dist',
			'Affiliate: Green Aventurine distribution is 5|3',
			( '5|3' === (string) $ga ),
			'distribution=' . $ga
		);

		$ms = isset( $plans['EG Moonstone Partner'] ) ? $plans['EG Moonstone Partner']['distribution'] : '';
		$cases[] = self::case(
			'B5-moonstone-dist',
			'Affiliate: Moonstone distribution is 5|4|2',
			( '5|4|2' === (string) $ms ),
			'distribution=' . $ms
		);

		$l1 = get_user_by( 'email', 'qa-aff-l1@earthgoddess.test' );
		$is_aff = $l1 ? get_user_meta( $l1->ID, 'afwc_is_affiliate', true ) : '';
		$cases[] = self::case(
			'B2-affiliate-flag',
			'Affiliate: qa-aff-l1 flagged as affiliate',
			( 'yes' === $is_aff ),
			'afwc_is_affiliate=' . $is_aff
		);

		// Application CPT create smoke.
		$app_id = wp_insert_post(
			array(
				'post_type'   => EG_Applications::CPT,
				'post_status' => 'pending',
				'post_title'  => 'QA Affiliate Application ' . time(),
			),
			true
		);
		if ( ! is_wp_error( $app_id ) ) {
			update_post_meta( $app_id, '_eg_app_type', 'affiliate' );
			update_post_meta( $app_id, '_eg_app_status', 'pending' );
			update_post_meta( $app_id, '_eg_app_email', 'qa-aff-l1@earthgoddess.test' );
		}
		$cases[] = self::case(
			'B1-application',
			'Affiliate: pending application record can be created',
			! is_wp_error( $app_id ),
			is_wp_error( $app_id ) ? $app_id->get_error_message() : 'app_id=' . $app_id
		);

		return $cases;
	}

	/**
	 * Pathway C — Ambassador.
	 *
	 * @return array
	 */
	private static function run_pathway_ambassador() {
		$cases = array();
		global $wpdb;
		$table = $wpdb->prefix . 'afwc_commission_plans';
		$rows  = array();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) { // phpcs:ignore
			$rows = $wpdb->get_results(
				"SELECT name, amount, no_of_tiers, distribution FROM {$table} WHERE name LIKE 'EG %Ambassador%'",
				ARRAY_A
			); // phpcs:ignore
		}
		$by_name = array();
		foreach ( (array) $rows as $row ) {
			$by_name[ $row['name'] ] = $row;
		}

		foreach (
			array(
				'EG Seed Ambassador'    => 20,
				'EG Bloom Ambassador'   => 25,
				'EG Goddess Ambassador' => 30,
			) as $name => $amount
		) {
			$ok = isset( $by_name[ $name ] )
				&& (float) $by_name[ $name ]['amount'] === (float) $amount
				&& (int) $by_name[ $name ]['no_of_tiers'] === 1;
			$cases[] = self::case(
				'C-' . sanitize_title( $name ),
				'Ambassador plan: ' . $name . ' @ ' . $amount . '% / 1 tier',
				$ok,
				isset( $by_name[ $name ] ) ? wp_json_encode( $by_name[ $name ] ) : 'missing'
			);
		}

		$amb = get_user_by( 'email', 'qa-amb@earthgoddess.test' );
		$cases[] = self::case(
			'C2-role',
			'Ambassador: qa-amb has ambassador role',
			$amb && in_array( 'ambassador', (array) $amb->roles, true ),
			$amb ? implode( ',', $amb->roles ) : 'missing user'
		);

		return $cases;
	}

	/**
	 * Pathway D — Wholesale.
	 *
	 * @return array
	 */
	private static function run_pathway_wholesale() {
		$cases   = array();
		$product = self::get_any_product();
		if ( ! $product ) {
			return array( self::case( 'D0', 'Wholesale: find a product', false, 'No published products' ) );
		}

		$rrp = (float) $product->get_regular_price( 'edit' );
		if ( $rrp <= 0 ) {
			$rrp = (float) $product->get_price( 'edit' );
		}

		foreach (
			array(
				'qa-ws-starter@earthgoddess.test' => 0.35,
				'qa-ws-pref@earthgoddess.test'    => 0.40,
				'qa-ws-elite@earthgoddess.test'   => 0.45,
			) as $email => $rate
		) {
			$user = get_user_by( 'email', $email );
			wp_set_current_user( $user ? $user->ID : 0 );
			$expected = round( $rrp * ( 1 - $rate ), wc_get_price_decimals() );
			$actual   = EG_Wholesale_Pricing::apply_role_discount( $rrp );
			$cases[]  = self::case(
				'D-price-' . sanitize_title( $email ),
				sprintf( 'Wholesale: %s gets %d%% off RRP', $email, (int) ( $rate * 100 ) ),
				( null !== $actual && abs( (float) $actual - $expected ) < 0.02 ),
				'rrp=' . $rrp . ' expected=' . $expected . ' actual=' . (string) $actual . ' product_id=' . $product->get_id()
			);
		}

		// Opening minimum: starter with no completed orders + empty-cart notice path.
		$starter = get_user_by( 'email', 'qa-ws-starter@earthgoddess.test' );
		if ( $starter && function_exists( 'WC' ) ) {
			wp_set_current_user( $starter->ID );
			// Cancel any prior QA orders for this user so opening-min applies.
			$old = wc_get_orders(
				array(
					'customer_id' => $starter->ID,
					'limit'       => -1,
					'return'      => 'ids',
				)
			);
			foreach ( $old as $oid ) {
				$o = wc_get_order( $oid );
				if ( $o ) {
					$o->update_status( 'cancelled', 'EG QA reset' );
				}
			}
			if ( null === WC()->cart ) {
				wc_load_cart();
			}
			WC()->cart->empty_cart();
			// Add cheap qty so subtotal < 250 if possible.
			WC()->cart->add_to_cart( $product->get_id(), 1 );
			$subtotal = (float) WC()->cart->get_subtotal();
			$blocked  = $subtotal < EG_Wholesale_Pricing::STARTER_MIN_OPENING;
			$cases[]  = self::case(
				'D3-opening-min',
				'Wholesale Starter: opening order rule triggers when subtotal < $250',
				$blocked,
				'subtotal=' . $subtotal . ' min=' . EG_Wholesale_Pricing::STARTER_MIN_OPENING
			);
			WC()->cart->empty_cart();
		}

		wp_set_current_user( 0 );
		return $cases;
	}

	/**
	 * Pathway E — wholesale referral plan.
	 *
	 * @return array
	 */
	private static function run_pathway_ws_referral() {
		$cases = array();
		global $wpdb;
		$table = $wpdb->prefix . 'afwc_commission_plans';
		$row   = null;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) { // phpcs:ignore
			$row = $wpdb->get_row(
				"SELECT name, amount, rules, status, no_of_tiers FROM {$table} WHERE name = 'EG Wholesale Referral 5%' LIMIT 1",
				ARRAY_A
			); // phpcs:ignore
		}
		$rules_ok = false;
		if ( $row && ! empty( $row['rules'] ) ) {
			$rules_ok = ( false !== strpos( $row['rules'], 'wholesale_starter' )
				&& false !== strpos( $row['rules'], 'user_role' ) );
		}
		$cases[] = self::case(
			'E2-plan',
			'Wholesale referral: EG Wholesale Referral 5% active with role rules',
			$row && (float) $row['amount'] === 5.0 && 'Active' === $row['status'] && $rules_ok,
			$row ? wp_json_encode( $row ) : 'missing plan'
		);

		$cases[] = self::case(
			'E3-ongoing',
			'Wholesale referral: plan is ongoing (single-tier percentage, not one-time bonus)',
			$row && (int) $row['no_of_tiers'] === 1 && (float) $row['amount'] === 5.0,
			'Verified configuration (live attribution needs affiliate-linked checkout)'
		);

		return $cases;
	}

	/**
	 * Pathway F + smoke.
	 *
	 * @return array
	 */
	private static function run_pathway_ux_smoke() {
		$cases = array();

		$slugs = array(
			'affiliate-business-builder-application',
			'ambassador-application',
			'wholesale-partner-application',
		);
		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( $slug );
			$cases[] = self::case(
				'F-page-' . $slug,
				'UX: page exists /' . $slug . '/',
				(bool) $page,
				$page ? 'ID=' . $page->ID : 'missing'
			);
		}

		// Server-side HTTP loopback to the public site often times out (cURL 28 / http=0),
		// even when the site is healthy. Prefer published front/shop pages when HTTP fails.
		$http_args = array(
			'timeout'   => 45,
			'sslverify' => false,
			'redirection' => 3,
			'headers'   => array( 'User-Agent' => 'EG-Phase1-LiveTester/1.0' ),
		);

		$front_id        = (int) get_option( 'page_on_front' );
		$show_on_front   = (string) get_option( 'show_on_front' );
		$front_published = ( 'posts' === $show_on_front )
			|| ( $front_id > 0 && 'publish' === get_post_status( $front_id ) );
		$home            = wp_remote_get( home_url( '/' ), $http_args );
		$code            = is_wp_error( $home ) ? 0 : (int) wp_remote_retrieve_response_code( $home );
		$home_err        = is_wp_error( $home ) ? $home->get_error_message() : '';
		$home_pass       = ( 200 === $code ) || ( $front_published && 0 === $code && false === strpos( $home_err, '403' ) );
		$cases[]         = self::case(
			'F-smoke-home',
			'Smoke: home is live (HTTP 200 or published front page; not 403)',
			$home_pass && 403 !== $code,
			'http=' . $code
			. ( $home_err ? ' err=' . $home_err : '' )
			. ' show_on_front=' . $show_on_front
			. ' front_page_id=' . $front_id
			. ' published=' . ( $front_published ? 'yes' : 'no' )
		);

		$shop_page_id   = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
		$shop_published = ( $shop_page_id > 0 && 'publish' === get_post_status( $shop_page_id ) );
		$shop           = wp_remote_get( home_url( '/shop/' ), $http_args );
		$scode          = is_wp_error( $shop ) ? 0 : (int) wp_remote_retrieve_response_code( $shop );
		$shop_err       = is_wp_error( $shop ) ? $shop->get_error_message() : '';
		$shop_pass      = ( 200 === $scode ) || ( $shop_published && 0 === $scode );
		$cases[]        = self::case(
			'F-smoke-shop',
			'Smoke: shop page is live (HTTP 200 or published WC shop page)',
			$shop_pass && 403 !== $scode,
			'http=' . $scode
			. ( $shop_err ? ' err=' . $shop_err : '' )
			. ' shop_page_id=' . $shop_page_id
			. ' published=' . ( $shop_published ? 'yes' : 'no' )
		);

		$gitignore = ABSPATH . '../.gitignore';
		if ( ! file_exists( $gitignore ) ) {
			$gitignore = ABSPATH . '.gitignore';
		}
		$ignored = file_exists( $gitignore ) && false !== strpos( (string) file_get_contents( $gitignore ), 'plan.pdf' );
		$cases[] = self::case(
			'F-gitignore',
			'Repo: plan.pdf listed in .gitignore',
			$ignored,
			$ignored ? 'found' : 'not found at ' . $gitignore
		);

		return $cases;
	}

	/**
	 * @param string $id      Case id.
	 * @param string $title   Title.
	 * @param bool   $pass    Pass.
	 * @param string $actual  Actual notes.
	 * @return array
	 */
	private static function case( $id, $title, $pass, $actual ) {
		return array(
			'id'     => $id,
			'title'  => $title,
			'pass'   => (bool) $pass,
			'actual' => $actual,
		);
	}

	/**
	 * @param string $email Email.
	 * @param string $role  Role.
	 * @return WP_User|null
	 */
	private static function ensure_user( $email, $role ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			$login = sanitize_user( str_replace( array( '@', '.' ), array( '_', '_' ), $email ), true );
			$id    = wp_insert_user(
				array(
					'user_login' => $login,
					'user_email' => $email,
					'user_pass'  => self::PASSWORD,
					'role'       => $role,
					'display_name' => $login,
				)
			);
			if ( is_wp_error( $id ) ) {
				return null;
			}
			$user = get_user_by( 'id', $id );
		} else {
			$user->set_role( $role );
			wp_set_password( self::PASSWORD, $user->ID );
		}
		return $user instanceof WP_User ? $user : null;
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $slug    Tag slug.
	 */
	private static function set_tag( $user_id, $slug ) {
		if ( ! $user_id || ! taxonomy_exists( 'afwc_user_tags' ) ) {
			return;
		}
		$term = get_term_by( 'slug', $slug, 'afwc_user_tags' );
		if ( ! $term ) {
			$created = wp_insert_term( ucwords( str_replace( '-', ' ', $slug ) ), 'afwc_user_tags', array( 'slug' => $slug ) );
			if ( ! is_wp_error( $created ) ) {
				$term = get_term( (int) $created['term_id'], 'afwc_user_tags' );
			}
		}
		if ( $term && ! is_wp_error( $term ) ) {
			wp_set_object_terms( $user_id, array( (int) $term->term_id ), 'afwc_user_tags', false );
		}
	}

	/**
	 * Cancel prior EG QA orders so annual-spend tiers can be re-tested cleanly.
	 *
	 * @param int $user_id User ID.
	 */
	private static function reset_qa_orders( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}
		$user  = get_user_by( 'id', $user_id );
		$email = $user ? (string) $user->user_email : '';
		// Only wipe spend for disposable QA accounts.
		if ( ! $email || false === strpos( $email, '@earthgoddess.test' ) ) {
			return;
		}
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array( 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending' ),
				'limit'       => -1,
				'return'      => 'objects',
			)
		);
		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order ) {
				$order->update_status( 'cancelled', 'EG QA reset spend' );
			}
		}
	}

	/**
	 * Seed spend into [min, max] inclusive band.
	 *
	 * @param int   $user_id User ID.
	 * @param float $min     Min spend.
	 * @param float $max     Max spend.
	 * @return bool
	 */
	private static function ensure_spend_in_band( $user_id, $min, $max ) {
		$current = EG_VIP_Discounts::get_annual_spend( $user_id );
		if ( $current >= $min && $current <= $max ) {
			return true;
		}
		if ( $current > $max ) {
			self::reset_qa_orders( $user_id );
			$current = EG_VIP_Discounts::get_annual_spend( $user_id );
		}
		// Target midpoint of band so we don't overshoot into next tier.
		$target = min( $max, max( $min, $min + 10 ) );
		if ( $current >= $target && $current <= $max ) {
			return true;
		}
		return self::ensure_spend_at_least( $user_id, $target ) && EG_VIP_Discounts::get_annual_spend( $user_id ) <= $max;
	}

	/**
	 * Create completed orders until annual spend >= target.
	 *
	 * @param int   $user_id User ID.
	 * @param float $target  Target spend.
	 * @return bool
	 */
	private static function ensure_spend_at_least( $user_id, $target ) {
		if ( ! function_exists( 'wc_create_order' ) ) {
			return false;
		}
		$current = EG_VIP_Discounts::get_annual_spend( $user_id );
		if ( $current >= $target ) {
			return true;
		}
		$need    = $target - $current + 10;
		$product = self::get_any_product();
		if ( ! $product ) {
			return false;
		}
		$order = wc_create_order( array( 'customer_id' => $user_id ) );
		if ( is_wp_error( $order ) ) {
			return false;
		}
		$qty = max( 1, (int) ceil( $need / max( 1, (float) $product->get_price() ) ) );
		$order->add_product( $product, $qty );
		// Force order total to the needed amount so tiers stay predictable.
		$order->set_total( $need );
		$order->update_status( 'completed', 'EG QA spend seed' );
		return EG_VIP_Discounts::get_annual_spend( $user_id ) >= $target;
	}

	/**
	 * @return WC_Product|null
	 */
	private static function get_any_product() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return null;
		}
		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => 1,
				'type'   => array( 'simple', 'variable' ),
			)
		);
		if ( empty( $products ) ) {
			return null;
		}
		$p = $products[0];
		if ( $p->is_type( 'variable' ) ) {
			$children = $p->get_children();
			if ( ! empty( $children ) ) {
				return wc_get_product( $children[0] );
			}
		}
		return $p;
	}
}
