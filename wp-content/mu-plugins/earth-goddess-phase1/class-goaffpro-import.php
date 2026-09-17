<?php
/**
 * GoAffPro CSV importer: brings existing affiliates into Affiliate for WooCommerce.
 *
 * Parents / genealogy are never assigned automatically. Rows with a parent are
 * collected into a downloadable checklist so an admin can link them in AFWC.
 */

defined( 'ABSPATH' ) || exit;

class EG_GoAffPro_Import {

	const OPTION_STATE = 'eg_goaffpro_import_state';
	const PAGE_SLUG    = 'eg-goaffpro-import';
	const BATCH_SIZE   = 40;
	const LOG_LIMIT    = 600;

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 20 );
		add_action( 'admin_post_eg_goaffpro_upload', array( __CLASS__, 'handle_upload' ) );
		add_action( 'admin_post_eg_goaffpro_run', array( __CLASS__, 'handle_run' ) );
		add_action( 'admin_post_eg_goaffpro_reset', array( __CLASS__, 'handle_reset' ) );
		add_action( 'admin_post_eg_goaffpro_parent_csv', array( __CLASS__, 'handle_parent_csv' ) );
	}

	/**
	 * Submenu under EG Phase 1.
	 */
	public static function register_menu() {
		add_submenu_page(
			'eg-phase1-sop',
			'GoAffPro Import',
			'GoAffPro Import',
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Current import state.
	 *
	 * @return array
	 */
	private static function state() {
		$state = get_option( self::OPTION_STATE );
		if ( ! is_array( $state ) ) {
			$state = array();
		}
		return wp_parse_args(
			$state,
			array(
				'file'      => '',
				'file_name' => '',
				'dry_run'   => 1,
				'offset'    => 0,
				'total'     => 0,
				'done'      => 0,
				'started'   => '',
				'finished'  => '',
				'counts'    => array(),
				'log'       => array(),
				'parents'   => array(),
			)
		);
	}

	/**
	 * @param array $state State.
	 */
	private static function save_state( $state ) {
		update_option( self::OPTION_STATE, $state, false );
	}

	/**
	 * Admin page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$state       = self::state();
		$has_file    = ( ! empty( $state['file'] ) && file_exists( $state['file'] ) );
		$in_progress = ( $has_file && ! $state['done'] && $state['offset'] > 0 );
		$custom_ids  = ( 'yes' === get_option( 'afwc_allow_custom_affiliate_identifier', 'yes' ) );
		?>
		<div class="wrap">
			<h1>GoAffPro → Affiliate for WooCommerce import</h1>

			<?php if ( ! class_exists( 'Affiliate_For_WooCommerce' ) ) : ?>
				<div class="notice notice-error"><p><strong>Affiliate for WooCommerce is not active.</strong> Activate it before importing.</p></div>
			<?php endif; ?>

			<?php if ( ! $custom_ids ) : ?>
				<div class="notice notice-warning"><p>
					AFWC custom referral identifiers are currently disabled, so old <code>?ref=CODE</code> links will not work.
					A live (non dry-run) import turns this setting on automatically.
				</p></div>
			<?php endif; ?>

			<?php if ( ! empty( $_GET['eg_goaffpro_msg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-info is-dismissible"><p>
					<?php echo esc_html( sanitize_text_field( rawurldecode( wp_unslash( $_GET['eg_goaffpro_msg'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				</p></div>
			<?php endif; ?>

			<h2>1. Upload the GoAffPro export</h2>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="eg_goaffpro_upload" />
				<?php wp_nonce_field( 'eg_goaffpro_upload' ); ?>
				<p><input type="file" name="eg_goaffpro_csv" accept=".csv,text/csv" required /></p>
				<p>
					<label>
						<input type="checkbox" name="eg_goaffpro_dry_run" value="1" checked />
						Dry run (report only, no users or affiliate data are written)
					</label>
				</p>
				<p><button type="submit" class="button button-primary">Upload CSV</button></p>
			</form>

			<?php if ( $has_file ) : ?>
				<h2>2. Run the import</h2>
				<table class="widefat striped" style="max-width:640px">
					<tbody>
						<tr><th style="width:35%">File</th><td><?php echo esc_html( (string) $state['file_name'] ); ?></td></tr>
						<tr><th>Mode</th><td><strong><?php echo $state['dry_run'] ? 'DRY RUN' : 'LIVE IMPORT'; ?></strong></td></tr>
						<tr><th>Rows in file</th><td><?php echo (int) $state['total']; ?></td></tr>
						<tr><th>Processed</th><td><?php echo (int) $state['offset']; ?></td></tr>
						<tr><th>Status</th><td><?php echo $state['done'] ? 'Finished ' . esc_html( (string) $state['finished'] ) : ( $in_progress ? 'Partially processed' : 'Not started' ); ?></td></tr>
					</tbody>
				</table>

				<p>
					<?php if ( ! $state['done'] ) : ?>
						<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=eg_goaffpro_run' ), 'eg_goaffpro_run' ) ); ?>">
							<?php echo $in_progress ? 'Continue import' : 'Start import'; ?>
						</a>
					<?php endif; ?>
					<?php if ( ! empty( $state['parents'] ) ) : ?>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=eg_goaffpro_parent_csv' ), 'eg_goaffpro_parent_csv' ) ); ?>">
							Download parent checklist CSV (<?php echo count( $state['parents'] ); ?>)
						</a>
					<?php endif; ?>
					<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=eg_goaffpro_reset' ), 'eg_goaffpro_reset' ) ); ?>">
						Reset state
					</a>
				</p>
				<p class="description">Each click processes <?php echo (int) self::BATCH_SIZE; ?> rows, then returns here. Keep clicking Continue until it reports Finished.</p>
			<?php endif; ?>

			<?php if ( ! empty( $state['counts'] ) ) : ?>
				<h2>Report</h2>
				<table class="widefat striped" style="max-width:640px">
					<thead><tr><th>Result</th><th>Rows</th></tr></thead>
					<tbody>
					<?php foreach ( $state['counts'] as $key => $n ) : ?>
						<tr><td><?php echo esc_html( self::label( $key ) ); ?></td><td><?php echo (int) $n; ?></td></tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $state['log'] ) ) : ?>
				<h2>Row log</h2>
				<p class="description">Newest last. Capped at <?php echo (int) self::LOG_LIMIT; ?> rows.</p>
				<table class="widefat striped">
					<thead><tr><th>Email</th><th>Result</th><th>Detail</th></tr></thead>
					<tbody>
					<?php foreach ( $state['log'] as $row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $row['email'] ); ?></td>
							<td><?php echo esc_html( self::label( (string) $row['result'] ) ); ?></td>
							<td><?php echo esc_html( (string) $row['detail'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2>What this importer does not do</h2>
			<ul>
				<li>It never assigns parents. Use the parent checklist CSV and set <strong>Parent Affiliate</strong> in AFWC manually.</li>
				<li>It does not import historical GoAffPro commissions or payouts.</li>
				<li>It does not email affiliates. New accounts get a random password; affiliates use "Lost your password" on first login.</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Friendly labels for counters / log results.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private static function label( $key ) {
		$labels = array(
			'imported'         => 'Imported (affiliate ready)',
			'would_import'     => 'Would import (dry run)',
			'user_created'     => 'New WP user created',
			'would_create'     => 'Would create new WP user',
			'user_matched'     => 'Matched existing WP user',
			'code_set'         => 'Referral code applied',
			'code_invalid'     => 'Referral code rejected by AFWC pattern',
			'code_conflict'    => 'Referral code already used by another affiliate',
			'code_missing'     => 'No referral code in CSV',
			'skipped_status'   => 'Skipped (status not approved)',
			'skipped_email'    => 'Skipped (missing or invalid email)',
			'skipped_dupe'     => 'Skipped (duplicate email in file)',
			'parent_recorded'  => 'Parent recorded for manual linking',
			'error'            => 'Error',
		);
		return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
	}

	/**
	 * Store uploaded CSV and reset state.
	 */
	public static function handle_upload() {
		self::guard( 'eg_goaffpro_upload' );

		if ( empty( $_FILES['eg_goaffpro_csv']['tmp_name'] ) ) {
			self::redirect( 'No file received.' );
		}

		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'eg-goaffpro';
		wp_mkdir_p( $dir );

		$name = sanitize_file_name( (string) $_FILES['eg_goaffpro_csv']['name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$dest = trailingslashit( $dir ) . 'import-' . gmdate( 'Ymd-His' ) . '-' . $name;

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! move_uploaded_file( $_FILES['eg_goaffpro_csv']['tmp_name'], $dest ) ) {
			self::redirect( 'Could not save the uploaded file.' );
		}

		$total = self::count_rows( $dest );
		if ( ! $total ) {
			self::redirect( 'The file has no data rows, or the header could not be read.' );
		}

		self::save_state(
			array(
				'file'      => $dest,
				'file_name' => $name,
				'dry_run'   => empty( $_POST['eg_goaffpro_dry_run'] ) ? 0 : 1,
				'offset'    => 0,
				'total'     => $total,
				'done'      => 0,
				'started'   => '',
				'finished'  => '',
				'counts'    => array(),
				'log'       => array(),
				'parents'   => array(),
			)
		);

		self::redirect( sprintf( 'Uploaded. %d affiliate rows detected.', $total ) );
	}

	/**
	 * Process one batch.
	 */
	public static function handle_run() {
		self::guard( 'eg_goaffpro_run' );

		$state = self::state();
		if ( empty( $state['file'] ) || ! file_exists( $state['file'] ) ) {
			self::redirect( 'Upload a CSV first.' );
		}
		if ( $state['done'] ) {
			self::redirect( 'This import already finished. Reset state to run again.' );
		}

		if ( empty( $state['started'] ) ) {
			$state['started'] = current_time( 'mysql' );
			if ( empty( $state['dry_run'] ) ) {
				// Old ?ref=CODE links only resolve when custom identifiers are allowed.
				update_option( 'afwc_allow_custom_affiliate_identifier', 'yes', false );
			}
		}

		$handle = fopen( $state['file'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $handle ) {
			self::redirect( 'Could not open the CSV file.' );
		}

		$header = fgetcsv( $handle );
		if ( ! is_array( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			self::redirect( 'Could not read the CSV header.' );
		}
		$header = array_map( array( __CLASS__, 'clean_key' ), $header );

		// Skip rows already processed.
		for ( $i = 0; $i < (int) $state['offset']; $i++ ) {
			if ( false === fgetcsv( $handle ) ) {
				break;
			}
		}

		$processed = 0;
		while ( $processed < self::BATCH_SIZE ) {
			$row = fgetcsv( $handle );
			if ( false === $row || null === $row ) {
				break;
			}
			if ( 1 === count( $row ) && ( null === $row[0] || '' === trim( (string) $row[0] ) ) ) {
				++$processed;
				++$state['offset'];
				continue;
			}

			$data = self::row_to_assoc( $header, $row );
			self::process_row( $data, $state );

			++$processed;
			++$state['offset'];
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( $state['offset'] >= (int) $state['total'] || 0 === $processed ) {
			$state['done']     = 1;
			$state['finished'] = current_time( 'mysql' );
		}

		self::save_state( $state );

		$msg = $state['done']
			? sprintf( 'Import finished. %d rows processed.', (int) $state['offset'] )
			: sprintf( 'Processed %d of %d rows. Click Continue import.', (int) $state['offset'], (int) $state['total'] );

		self::redirect( $msg );
	}

	/**
	 * Import a single CSV row.
	 *
	 * @param array $data  Row keyed by header.
	 * @param array $state State (by reference).
	 */
	private static function process_row( $data, &$state ) {
		$email  = sanitize_email( self::field( $data, 'email address' ) );
		$status = strtolower( trim( self::field( $data, 'status' ) ) );
		$name   = trim( self::field( $data, 'name' ) );

		if ( ! $email || ! is_email( $email ) ) {
			self::record( $state, 'skipped_email', $name ? $name : '(no email)', 'Row has no usable email address.' );
			return;
		}

		if ( 'approved' !== $status ) {
			self::record( $state, 'skipped_status', $email, 'GoAffPro status: ' . ( $status ? $status : 'empty' ) );
			return;
		}

		$parent_email = sanitize_email( self::field( $data, 'parent email' ) );
		$parent_name  = trim( self::field( $data, 'parent name' ) );
		$sponsor      = trim( self::field( $data, 'sponsor' ) );
		$code         = trim( self::field( $data, 'referral code' ) );
		$paypal       = self::paypal_email( $data );
		$first        = trim( self::field( $data, 'first name' ) );
		$last         = trim( self::field( $data, 'last name' ) );
		$approved_at  = trim( self::field( $data, 'date approved' ) );

		$user    = get_user_by( 'email', $email );
		$dry_run = ! empty( $state['dry_run'] );

		if ( ! $user ) {
			if ( $dry_run ) {
				self::bump( $state, 'would_create' );
				self::bump( $state, 'would_import' );
				self::note_code( $state, $email, $code, 0, true );
				self::note_parent( $state, $email, $name, $parent_email, $parent_name, 0, $sponsor );
				self::record( $state, 'would_import', $email, 'Would create user and mark as affiliate.' );
				return;
			}

			$user_id = self::create_user( $email, $first, $last, $name );
			if ( is_wp_error( $user_id ) ) {
				self::record( $state, 'error', $email, $user_id->get_error_message() );
				return;
			}
			$user = get_user_by( 'id', $user_id );
			self::bump( $state, 'user_created' );
		} else {
			self::bump( $state, 'user_matched' );

			if ( $dry_run ) {
				self::bump( $state, 'would_import' );
				self::note_code( $state, $email, $code, (int) $user->ID, true );
				self::note_parent( $state, $email, $name, $parent_email, $parent_name, (int) $user->ID, $sponsor );
				self::record( $state, 'would_import', $email, 'Existing user #' . (int) $user->ID . ' would become an affiliate.' );
				return;
			}
		}

		if ( ! $user ) {
			self::record( $state, 'error', $email, 'User could not be loaded after creation.' );
			return;
		}

		// Program role: keep customer/other roles, add the builder role.
		if ( ! in_array( 'affiliate_business_builder', (array) $user->roles, true ) ) {
			$user->add_role( 'affiliate_business_builder' );
		}

		self::ensure_affiliate_record( $user->ID, $approved_at );
		self::assign_affiliate_tag( $user->ID, 'clear-quartz-partner' );

		if ( $paypal && is_email( $paypal ) ) {
			update_user_meta( $user->ID, 'afwc_paypal_email', $paypal );
			if ( ! get_user_meta( $user->ID, 'afwc_payout_method', true ) ) {
				update_user_meta( $user->ID, 'afwc_payout_method', 'paypal' );
			}
		}

		if ( $first && ! get_user_meta( $user->ID, 'first_name', true ) ) {
			update_user_meta( $user->ID, 'first_name', $first );
		}
		if ( $last && ! get_user_meta( $user->ID, 'last_name', true ) ) {
			update_user_meta( $user->ID, 'last_name', $last );
		}

		self::note_code( $state, $email, $code, (int) $user->ID, false );
		self::note_parent( $state, $email, $name, $parent_email, $parent_name, (int) $user->ID, $sponsor );

		self::bump( $state, 'imported' );
		self::record( $state, 'imported', $email, 'Affiliate ready. User #' . (int) $user->ID );
	}

	/**
	 * Apply (or evaluate) the GoAffPro referral code as the AFWC URL identifier.
	 *
	 * @param array  $state   State (by reference).
	 * @param string $email   Email.
	 * @param string $code    Referral code.
	 * @param int    $user_id User ID (0 in dry run for new users).
	 * @param bool   $dry_run Whether this is a dry run.
	 */
	private static function note_code( &$state, $email, $code, $user_id, $dry_run ) {
		if ( '' === $code ) {
			self::bump( $state, 'code_missing' );
			return;
		}

		$pattern = function_exists( 'afwc_affiliate_identifier_regex_pattern' )
			? afwc_affiliate_identifier_regex_pattern()
			: '^[a-zA-Z]\w*$';

		if ( is_numeric( $code ) || ! preg_match( '/' . $pattern . '/', $code ) ) {
			self::bump( $state, 'code_invalid' );
			self::record( $state, 'code_invalid', $email, 'Code "' . $code . '" is not a valid AFWC identifier; affiliate keeps the default link.' );
			return;
		}

		$owner = function_exists( 'afwc_get_affiliate_id_by_assigned_identifier' )
			? (int) afwc_get_affiliate_id_by_assigned_identifier( $code )
			: 0;

		if ( $owner && $owner !== (int) $user_id ) {
			self::bump( $state, 'code_conflict' );
			self::record( $state, 'code_conflict', $email, 'Code "' . $code . '" already belongs to user #' . $owner . '.' );
			return;
		}

		if ( ! $dry_run && $user_id ) {
			update_user_meta( $user_id, 'afwc_ref_url_id', $code );
		}
		self::bump( $state, 'code_set' );
	}

	/**
	 * Collect a parent relationship for the manual checklist.
	 *
	 * @param array  $state        State (by reference).
	 * @param string $child_email  Child email.
	 * @param string $child_name   Child name.
	 * @param string $parent_email Parent email.
	 * @param string $parent_name  Parent name.
	 * @param int    $child_id     Child user ID.
	 */
	private static function note_parent( &$state, $child_email, $child_name, $parent_email, $parent_name, $child_id, $sponsor = '' ) {
		$has_parent = ( $parent_email && is_email( $parent_email ) );
		if ( ! $has_parent && '' === $sponsor ) {
			return;
		}

		$parent    = $has_parent ? get_user_by( 'email', $parent_email ) : null;
		$parent_id = $parent ? (int) $parent->ID : 0;

		$state['parents'][ strtolower( $child_email ) ] = array(
			'child_email'  => $child_email,
			'child_name'   => $child_name,
			'child_id'     => (int) $child_id,
			'parent_email' => $has_parent ? $parent_email : '',
			'parent_name'  => $parent_name,
			'parent_id'    => $parent_id,
			'sponsor'      => $sponsor,
		);
		self::bump( $state, 'parent_recorded' );
	}

	/**
	 * Create a customer account without emailing the affiliate.
	 *
	 * @param string $email Email.
	 * @param string $first First name.
	 * @param string $last  Last name.
	 * @param string $name  Display name.
	 * @return int|WP_Error
	 */
	private static function create_user( $email, $first, $last, $name ) {
		$login = self::unique_login( $email );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 20, true ),
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => $name ? $name : $login,
				'role'         => 'customer',
			)
		);

		return $user_id;
	}

	/**
	 * @param string $email Email.
	 * @return string
	 */
	private static function unique_login( $email ) {
		$base = sanitize_user( current( explode( '@', $email ) ), true );
		if ( strlen( $base ) < 3 ) {
			$base = 'egaffiliate';
		}
		$login = $base;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . $i;
			++$i;
		}
		return $login;
	}

	/**
	 * Mirror of the Phase 1 approve flow so imported users behave like approved affiliates.
	 *
	 * @param int    $user_id     User ID.
	 * @param string $approved_at GoAffPro approval date.
	 */
	private static function ensure_affiliate_record( $user_id, $approved_at = '' ) {
		update_user_meta( $user_id, 'afwc_is_affiliate', 'yes' );

		if ( ! get_user_meta( $user_id, 'afwc_affiliate_since', true ) ) {
			$since = $approved_at ? gmdate( 'Y-m-d H:i:s', strtotime( $approved_at ) ) : current_time( 'mysql' );
			update_user_meta( $user_id, 'afwc_affiliate_since', $since );
		}

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

		do_action( 'afwc_affiliate_approved', $user_id );
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $slug    Tag slug.
	 */
	private static function assign_affiliate_tag( $user_id, $slug ) {
		if ( ! taxonomy_exists( 'afwc_user_tags' ) ) {
			return;
		}
		$term = get_term_by( 'slug', $slug, 'afwc_user_tags' );
		if ( ! $term ) {
			$created = wp_insert_term( ucwords( str_replace( '-', ' ', $slug ) ), 'afwc_user_tags', array( 'slug' => $slug ) );
			if ( is_wp_error( $created ) ) {
				return;
			}
			$term = get_term( (int) $created['term_id'], 'afwc_user_tags' );
		}
		if ( $term && ! is_wp_error( $term ) ) {
			wp_set_object_terms( $user_id, array( (int) $term->term_id ), 'afwc_user_tags', false );
		}
	}

	/**
	 * Stream the parent checklist for manual AFWC linking.
	 */
	public static function handle_parent_csv() {
		self::guard( 'eg_goaffpro_parent_csv' );

		$state = self::state();
		$rows  = is_array( $state['parents'] ) ? array_values( $state['parents'] ) : array();

		usort(
			$rows,
			static function ( $a, $b ) {
				return strcasecmp( $a['parent_email'] . $a['child_email'], $b['parent_email'] . $b['child_email'] );
			}
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=goaffpro-parent-checklist.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, array( 'Parent Email', 'Parent Name', 'Parent WP User ID', 'Child Email', 'Child Name', 'Child WP User ID', 'GoAffPro Sponsor Code', 'Linked in AFWC (yes/no)' ) );
		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				array(
					$row['parent_email'] ? $row['parent_email'] : 'no parent in CSV',
					$row['parent_name'],
					$row['parent_id'] ? $row['parent_id'] : 'not found',
					$row['child_email'],
					$row['child_name'],
					$row['child_id'] ? $row['child_id'] : 'not imported',
					isset( $row['sponsor'] ) ? $row['sponsor'] : '',
					'',
				)
			);
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * Clear stored state.
	 */
	public static function handle_reset() {
		self::guard( 'eg_goaffpro_reset' );
		delete_option( self::OPTION_STATE );
		self::redirect( 'Import state cleared.' );
	}

	/**
	 * @param array $state  State (by reference).
	 * @param string $key   Counter key.
	 */
	private static function bump( &$state, $key ) {
		if ( ! isset( $state['counts'][ $key ] ) ) {
			$state['counts'][ $key ] = 0;
		}
		++$state['counts'][ $key ];
	}

	/**
	 * @param array  $state  State (by reference).
	 * @param string $result Result key.
	 * @param string $email  Email or label.
	 * @param string $detail Detail text.
	 */
	private static function record( &$state, $result, $email, $detail ) {
		if ( in_array( $result, array( 'skipped_status', 'skipped_email', 'error' ), true ) ) {
			self::bump( $state, $result );
		}
		if ( count( $state['log'] ) >= self::LOG_LIMIT ) {
			return;
		}
		$state['log'][] = array(
			'email'  => $email,
			'result' => $result,
			'detail' => $detail,
		);
	}

	/**
	 * @param array $header Header keys.
	 * @param array $row    Row values.
	 * @return array
	 */
	private static function row_to_assoc( $header, $row ) {
		$data = array();
		foreach ( $header as $i => $key ) {
			$data[ $key ] = isset( $row[ $i ] ) ? (string) $row[ $i ] : '';
		}
		return $data;
	}

	/**
	 * PayPal payout email, falling back to the address embedded in "Payment Details".
	 *
	 * @param array $data Row data.
	 * @return string
	 */
	private static function paypal_email( $data ) {
		$paypal = sanitize_email( self::field( $data, 'paypal email address' ) );
		if ( $paypal && is_email( $paypal ) ) {
			return $paypal;
		}
		$details = self::field( $data, 'payment details' );
		if ( $details && preg_match( '/[\w.+-]+@[\w-]+\.[\w.-]+/', $details, $m ) ) {
			$found = sanitize_email( $m[0] );
			return ( $found && is_email( $found ) ) ? $found : '';
		}
		return '';
	}

	/**
	 * @param array  $data Row data.
	 * @param string $key  Lowercased header name.
	 * @return string
	 */
	private static function field( $data, $key ) {
		return isset( $data[ $key ] ) ? (string) $data[ $key ] : '';
	}

	/**
	 * @param string $key Raw header cell.
	 * @return string
	 */
	private static function clean_key( $key ) {
		$key = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $key );
		return strtolower( trim( $key ) );
	}

	/**
	 * @param string $file File path.
	 * @return int Data rows.
	 */
	private static function count_rows( $file ) {
		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $handle ) {
			return 0;
		}
		$header = fgetcsv( $handle );
		if ( ! is_array( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return 0;
		}
		$rows = 0;
		while ( false !== ( $row = fgetcsv( $handle ) ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			if ( 1 === count( $row ) && ( null === $row[0] || '' === trim( (string) $row[0] ) ) ) {
				continue;
			}
			++$rows;
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return $rows;
	}

	/**
	 * @param string $action Nonce action.
	 */
	private static function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'earth-goddess' ) );
		}
		check_admin_referer( $action );
	}

	/**
	 * @param string $message Notice text.
	 */
	private static function redirect( $message ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => self::PAGE_SLUG,
					'eg_goaffpro_msg'  => rawurlencode( $message ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
