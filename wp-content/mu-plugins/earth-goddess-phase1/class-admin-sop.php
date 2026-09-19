<?php
/**
 * Admin SOP checklist and role/affiliate counts.
 */

defined( 'ABSPATH' ) || exit;

class EG_Admin_SOP {

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	/**
	 * Register menu page.
	 */
	public static function register_menu() {
		add_menu_page(
			'EG Phase 1 SOP',
			'EG Phase 1',
			'manage_options',
			'eg-phase1-sop',
			array( __CLASS__, 'render_page' ),
			'dashicons-superhero',
			57
		);
	}

	/**
	 * Render SOP + counts.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$counts = array();
		foreach ( EG_Roles::ROLES as $slug => $label ) {
			$users = get_users(
				array(
					'role'   => $slug,
					'fields' => 'ID',
				)
			);
			$counts[ $label ] = count( $users );
		}

		$tier_hint = array(
			'Moon'    => 'Annual spend $0–$499 → 10%',
			'Star'    => 'Annual spend $500–$999 → 12%',
			'Goddess' => 'Annual spend $1,000+ → 15%',
		);

		?>
		<div class="wrap">
			<h1>Earth Goddess AU – Phase 1 SOP</h1>
			<?php if ( ! empty( $_GET['eg_seeded'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success"><p>Configuration seed completed.</p></div>
			<?php endif; ?>
			<?php if ( ! empty( $_GET['eg_tested'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success"><p>Live pathway tests finished. Scroll to results below.</p></div>
			<?php endif; ?>

			<p>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=eg_phase1_reseed' ), 'eg_phase1_reseed' ) ); ?>">
					Re-run Phase 1 seeder (WPLoyalty + AFWC)
				</a>
				<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=eg_phase1_run_live_tests' ), 'eg_phase1_run_live_tests' ) ); ?>">
					Run live pathway tests
				</a>
			</p>
			<?php self::render_live_test_results(); ?>

			<h2>Role counts</h2>
			<table class="widefat striped" style="max-width:480px">
				<thead><tr><th>Role</th><th>Users</th></tr></thead>
				<tbody>
				<?php foreach ( $counts as $label => $n ) : ?>
					<tr><td><?php echo esc_html( $label ); ?></td><td><?php echo (int) $n; ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>VIP tiers (reference)</h2>
			<ul>
				<?php foreach ( $tier_hint as $name => $desc ) : ?>
					<li><strong><?php echo esc_html( $name ); ?>:</strong> <?php echo esc_html( $desc ); ?></li>
				<?php endforeach; ?>
			</ul>

			<h2>Monthly manual checklist</h2>
			<ol>
				<li><strong>Affiliate rank review</strong> — For each Affiliate Business Builder, check personal sales (30-day) and Active Partner count (≥ $100 personal SV in previous 30 days). Upgrade tags manually:
					<ul>
						<li>Clear Quartz (default) → Amethyst ($500 SV/mo OR $250 + 1 Active Partner)</li>
						<li>Green Aventurine ($1,000 SV + 3 Active Partners)</li>
						<li>Moonstone ($1,500 SV + 5 Active Partners + 1 Green Aventurine in team)</li>
					</ul>
					Update affiliate tag under Users → Affiliate Tags, then confirm matching commission plan applies.
				</li>
				<li><strong>Ambassador unlocks</strong> — Lifetime sales: Bloom at $2,500, Goddess Ambassador at $10,000. Retag manually.</li>
				<li><strong>Wholesale upgrades</strong> — Preferred at $1,000 annual purchases; Elite at $5,000. Change user role Starter → Preferred → Elite.</li>
				<li><strong>Leadership honours</strong> (recognition only — no commission): Key of Growth (3 Active Partners), Moon Keeper (10), Guiding Star (25), Celestial Legacy (50). Assign AFWC leadership tags.</li>
				<li><strong>Applications</strong> — Review pending items under EG Applications; approve to assign roles/default tags.</li>
				<li><strong>Reporting snapshot</strong> — Note VIP discount tiers used, affiliate/ambassador tag counts, wholesale role counts, unpaid commissions (AFWC), loyalty points redeemed (WPLoyalty).</li>
			</ol>

			<h2>Application pages</h2>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/affiliate-business-builder-application/' ) ); ?>">Affiliate Business Builder</a></li>
				<li><a href="<?php echo esc_url( home_url( '/ambassador-application/' ) ); ?>">Ambassador</a></li>
				<li><a href="<?php echo esc_url( home_url( '/wholesale-partner-application/' ) ); ?>">Wholesale Partner</a></li>
			</ul>

			<h2>Verification checklist</h2>
			<ul>
				<li>Assign test user <code>vip_shopper</code>; place orders; confirm Moon/Star/Goddess cart fee % and WPLoyalty points (1/$1, signup 50, review 50, birthday 250, referral 100).</li>
				<li>Approve affiliate application → Clear Quartz tag + 15% commission on referred order.</li>
				<li>Enable multi-tier child affiliate; confirm L1/L2/L3 distributions for Amethyst / Green Aventurine / Moonstone plans.</li>
				<li>Ambassador Seed/Bloom/Goddess plans: no parent commissions.</li>
				<li>Wholesale Starter prices 35% off RRP; Preferred 40%; Elite 45%; opening order ≥ $250 for new Starters.</li>
				<li>Referred wholesale order earns AFWC “EG Wholesale Referral 5%” for the referring affiliate.</li>
				<li>Confirm <code>plan.pdf</code> is listed in <code>.gitignore</code>.</li>
			</ul>

			<h2>Code / install status</h2>
			<ul>
				<?php
				$status_items = array(
					'Roles registered'              => self::roles_ok(),
					'WPLoyalty plugin active'       => class_exists( '\Wlr\App\Models\EarnCampaign' ),
					'AFWC plugin active'            => class_exists( 'Affiliate_For_WooCommerce' ),
					'Multi-tier enabled'            => ( 'yes' === get_option( 'afwc_enable_multi_tier' ) ),
					'Seeder ran'                    => (bool) get_option( EG_PHASE1_OPTION_SEEDED ),
					'Application pages created'     => (bool) get_option( 'eg_phase1_pages_created' ),
					'AFWC EG plans present'         => self::plans_ok(),
				);
				foreach ( $status_items as $label => $ok ) :
					?>
					<li><strong><?php echo $ok ? 'OK' : 'MISSING'; ?>:</strong> <?php echo esc_html( $label ); ?></li>
				<?php endforeach; ?>
			</ul>

			<p><em>Phase 2/3 (auto rank, badges, gamification dashboard) are out of scope.</em></p>
		</div>
		<?php
	}

	/**
	 * Render last live pathway test run.
	 */
	private static function render_live_test_results() {
		$results = get_option( EG_Live_Pathway_Tester::OPTION_RESULTS );
		if ( empty( $results ) || empty( $results['cases'] ) ) {
			return;
		}
		?>
		<h2>Live pathway test results</h2>
		<p>
			Finished: <?php echo esc_html( (string) $results['finished'] ); ?>
			| Pass: <strong><?php echo (int) $results['pass']; ?></strong>
			| Fail: <strong><?php echo (int) $results['fail']; ?></strong>
		</p>
		<p class="description">QA password for created users: <code><?php echo esc_html( EG_Live_Pathway_Tester::PASSWORD ); ?></code></p>
		<table class="widefat striped">
			<thead>
				<tr><th>ID</th><th>Result</th><th>Check</th><th>Actual</th></tr>
			</thead>
			<tbody>
			<?php foreach ( $results['cases'] as $case ) : ?>
				<tr>
					<td><code><?php echo esc_html( $case['id'] ); ?></code></td>
					<td><?php echo ! empty( $case['pass'] ) ? '<span style="color:green">PASS</span>' : '<span style="color:#b32d2e">FAIL</span>'; ?></td>
					<td><?php echo esc_html( $case['title'] ); ?></td>
					<td><?php echo esc_html( (string) $case['actual'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @return bool
	 */
	private static function roles_ok() {
		foreach ( array_keys( EG_Roles::ROLES ) as $slug ) {
			if ( ! get_role( $slug ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return bool
	 */
	private static function plans_ok() {
		global $wpdb;
		$table = $wpdb->prefix . 'afwc_commission_plans';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore
			return false;
		}
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE name LIKE 'EG %'" ); // phpcs:ignore
		return $count >= 8;
	}
}
