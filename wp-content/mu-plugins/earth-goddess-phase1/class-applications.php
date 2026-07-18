<?php
/**
 * Application forms for Affiliate, Ambassador, and Wholesale (manual approval).
 */

defined( 'ABSPATH' ) || exit;

class EG_Applications {

	const CPT = 'eg_application';

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor' ), 10, 2 );

		add_action( 'admin_post_nopriv_eg_submit_application', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_eg_submit_application', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_eg_approve_application', array( __CLASS__, 'handle_approve' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_pages' ) );
		add_action( 'woocommerce_account_dashboard', array( __CLASS__, 'render_my_account_pathways' ), 5 );
	}

	/**
	 * Classic editor for applications so approve actions work reliably.
	 *
	 * @param bool   $use Whether to use block editor.
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public static function disable_block_editor( $use, $post_type ) {
		if ( self::CPT === $post_type ) {
			return false;
		}
		return $use;
	}

	/**
	 * Frontend form CSS.
	 */
	public static function enqueue_assets() {
		if ( ! is_singular( 'page' ) ) {
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			return;
		}

		$content = (string) $post->post_content;
		$has_shortcode = has_shortcode( $content, 'eg_affiliate_application' )
			|| has_shortcode( $content, 'eg_ambassador_application' )
			|| has_shortcode( $content, 'eg_wholesale_application' );
		$slugs = array(
			'affiliate-business-builder-application',
			'ambassador-application',
			'wholesale-partner-application',
		);
		if ( ! $has_shortcode && ! in_array( $post->post_name, $slugs, true ) ) {
			return;
		}

		wp_enqueue_style(
			'eg-applications',
			self::asset_url( 'assets/eg-applications.css' ),
			array(),
			EG_PHASE1_VERSION
		);
	}

	/**
	 * Pathway CTAs on My Account dashboard.
	 */
	public static function render_my_account_pathways() {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->ID ) {
			return;
		}
		$links = array();
		if ( ! EG_Roles::user_is_wholesale( $user ) && ! in_array( 'affiliate_business_builder', (array) $user->roles, true ) ) {
			$links[] = '<a href="' . esc_url( home_url( '/affiliate-business-builder-application/' ) ) . '">' . esc_html__( 'Become an Affiliate Business Builder', 'earth-goddess' ) . '</a>';
		}
		if ( ! in_array( 'ambassador', (array) $user->roles, true ) && ! in_array( 'affiliate_business_builder', (array) $user->roles, true ) ) {
			$links[] = '<a href="' . esc_url( home_url( '/ambassador-application/' ) ) . '">' . esc_html__( 'Join the Ambassador Program', 'earth-goddess' ) . '</a>';
		}
		if ( in_array( 'affiliate_business_builder', (array) $user->roles, true ) ) {
			$links[] = '<a href="' . esc_url( home_url( '/wholesale-partner-application/' ) ) . '">' . esc_html__( 'Refer a Wholesale Partner', 'earth-goddess' ) . '</a>';
		}
		if ( empty( $links ) ) {
			return;
		}
		echo '<div class="eg-pathways"><h3>' . esc_html__( 'Earth Goddess Pathways', 'earth-goddess' ) . '</h3><ul>';
		foreach ( $links as $link ) {
			echo '<li>' . $link . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</ul></div>';
	}

	/**
	 * Register applications CPT.
	 */
	public static function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'          => 'EG Applications',
					'singular_name' => 'EG Application',
					'menu_name'     => 'EG Applications',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 56,
				'menu_icon'           => 'dashicons-id',
				'capability_type'     => 'post',
				'supports'            => array( 'title' ),
				'exclude_from_search' => true,
				'show_in_rest'        => false,
			)
		);
	}

	/**
	 * Shortcodes for forms.
	 */
	public static function register_shortcodes() {
		add_shortcode( 'eg_affiliate_application', array( __CLASS__, 'render_affiliate_form' ) );
		add_shortcode( 'eg_ambassador_application', array( __CLASS__, 'render_ambassador_form' ) );
		add_shortcode( 'eg_wholesale_application', array( __CLASS__, 'render_wholesale_form' ) );
	}

	/**
	 * Create frontend pages once.
	 */
	public static function maybe_create_pages() {
		if ( get_option( 'eg_phase1_pages_created' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pages = array(
			'affiliate-business-builder-application' => array(
				'title'   => 'Affiliate Business Builder Application',
				'content' => '[eg_affiliate_application]',
			),
			'ambassador-application'                 => array(
				'title'   => 'Ambassador Application',
				'content' => '[eg_ambassador_application]',
			),
			'wholesale-partner-application'          => array(
				'title'   => 'Wholesale Partner Application',
				'content' => '[eg_wholesale_application]',
			),
		);

		foreach ( $pages as $slug => $page ) {
			$existing = get_page_by_path( $slug );
			if ( $existing ) {
				continue;
			}
			wp_insert_post(
				array(
					'post_title'   => $page['title'],
					'post_name'    => $slug,
					'post_content' => $page['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
		}

		update_option( 'eg_phase1_pages_created', 1, false );
	}

	/**
	 * @return string
	 */
	public static function render_affiliate_form() {
		return self::render_form(
			'affiliate',
			array(
				'full_name'         => 'Name',
				'email'             => 'Email',
				'social_links'      => 'Social Media Links',
				'why_join'          => 'Why do you want to join?',
				'existing_business' => 'Existing Business? (Yes/No)',
			)
		);
	}

	/**
	 * @return string
	 */
	public static function render_ambassador_form() {
		return self::render_form(
			'ambassador',
			array(
				'full_name'     => 'Name',
				'email'         => 'Email',
				'social_links'  => 'Social Media Profiles',
				'audience_size' => 'Audience Size',
				'main_platform' => 'Main Platform',
				'why_partner'   => 'Why do you wish to partner with Earth Goddess AU?',
			)
		);
	}

	/**
	 * @return string
	 */
	public static function render_wholesale_form() {
		return self::render_form(
			'wholesale',
			array(
				'business_name'  => 'Business Name',
				'abn'            => 'ABN',
				'website'        => 'Website',
				'business_type'  => 'Business Type',
				'contact_person' => 'Contact Person',
				'email'          => 'Email',
				'phone'          => 'Phone',
				'reason'         => 'Reason for Applying',
			)
		);
	}

	/**
	 * @param string               $type   Form type.
	 * @param array<string,string> $fields Fields.
	 * @return string
	 */
	private static function render_form( $type, $fields ) {
		wp_enqueue_style(
			'eg-applications',
			self::asset_url( 'assets/eg-applications.css' ),
			array(),
			EG_PHASE1_VERSION
		);

		$notice = '';
		if ( ! empty( $_GET['eg_app'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status = sanitize_text_field( wp_unslash( $_GET['eg_app'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'success' === $status ) {
				$notice = '<p class="eg-app-notice eg-app-success">' . esc_html__( 'Thank you. Your application has been submitted for manual review.', 'earth-goddess' ) . '</p>';
			} elseif ( 'error' === $status ) {
				$notice = '<p class="eg-app-notice eg-app-error">' . esc_html__( 'Please complete all required fields and try again.', 'earth-goddess' ) . '</p>';
			}
		}

		ob_start();
		?>
		<style id="eg-application-inline-css">
			.eg-application-wrap{display:block!important;width:100%!important;max-width:520px!important;margin:2rem auto 3rem!important;padding:1.75rem 1.5rem 2rem!important;background:#fff!important;border:1px solid rgba(0,0,0,.1)!important;border-radius:12px!important;box-sizing:border-box!important;float:none!important}
			.eg-application-wrap .eg-app-notice{margin:0 0 1.25rem!important;padding:.85rem 1rem!important;border-radius:8px!important}
			.eg-application-wrap .eg-app-success{background:#eef8f0!important;color:#1e5a2c!important;border:1px solid #b7dfc0!important}
			.eg-application-wrap .eg-app-error{background:#fdf0f0!important;color:#8a1f1f!important;border:1px solid #efb4b4!important}
			.eg-application-form .eg-field{margin:0 0 1.1rem!important}
			.eg-application-form label{display:block!important;margin:0 0 .4rem!important;font-weight:600!important}
			.eg-application-form input[type=text],.eg-application-form input[type=email],.eg-application-form input[type=tel],.eg-application-form input[type=url],.eg-application-form textarea{display:block!important;width:100%!important;max-width:100%!important;box-sizing:border-box!important;padding:.7rem .85rem!important;border:1px solid #c9c4bc!important;border-radius:8px!important;font-size:1rem!important}
			.eg-application-form textarea{min-height:110px!important}
			.eg-application-form button[type=submit]{display:inline-block!important;width:100%!important;padding:.85rem 1.25rem!important;border:0!important;border-radius:8px!important;background:#5b3a6e!important;color:#fff!important;font-weight:600!important;cursor:pointer!important}
		</style>
		<div class="eg-application-wrap">
			<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<form class="eg-application-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="eg_submit_application" />
				<input type="hidden" name="eg_app_type" value="<?php echo esc_attr( $type ); ?>" />
				<?php wp_nonce_field( 'eg_submit_application_' . $type, 'eg_app_nonce' ); ?>
				<?php foreach ( $fields as $name => $label ) : ?>
					<div class="eg-field">
						<label for="eg_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
						<?php if ( in_array( $name, array( 'why_join', 'why_partner', 'reason', 'social_links' ), true ) ) : ?>
							<textarea id="eg_<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="4" required></textarea>
						<?php else : ?>
							<input id="eg_<?php echo esc_attr( $name ); ?>" type="<?php echo 'email' === $name ? 'email' : 'text'; ?>" name="<?php echo esc_attr( $name ); ?>" required />
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<div class="eg-submit">
					<button type="submit"><?php esc_html_e( 'Submit Application', 'earth-goddess' ); ?></button>
				</div>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Absolute URL for MU-plugin assets.
	 *
	 * @param string $relative Relative path under earth-goddess-phase1/.
	 * @return string
	 */
	private static function asset_url( $relative ) {
		$mu_url = content_url( 'mu-plugins/earth-goddess-phase1/' . ltrim( $relative, '/' ) );
		return $mu_url;
	}

	/**
	 * Handle form POST.
	 */
	public static function handle_submit() {
		$type = isset( $_POST['eg_app_type'] ) ? sanitize_key( wp_unslash( $_POST['eg_app_type'] ) ) : '';
		if ( ! in_array( $type, array( 'affiliate', 'ambassador', 'wholesale' ), true ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		if ( empty( $_POST['eg_app_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eg_app_nonce'] ) ), 'eg_submit_application_' . $type ) ) {
			self::redirect_back( $type, 'error' );
		}

		$data = array();
		foreach ( $_POST as $key => $value ) {
			if ( in_array( $key, array( 'action', 'eg_app_type', 'eg_app_nonce', '_wp_http_referer' ), true ) ) {
				continue;
			}
			$data[ sanitize_key( $key ) ] = sanitize_textarea_field( wp_unslash( $value ) );
		}

		$email = isset( $data['email'] ) ? $data['email'] : '';
		$name  = isset( $data['full_name'] ) ? $data['full_name'] : ( isset( $data['contact_person'] ) ? $data['contact_person'] : ( isset( $data['business_name'] ) ? $data['business_name'] : 'Applicant' ) );

		if ( empty( $email ) || empty( $name ) || ! is_email( $email ) ) {
			self::redirect_back( $type, 'error' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'pending',
				'post_title'  => sprintf( '%s – %s', ucfirst( $type ), $name ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			self::redirect_back( $type, 'error' );
		}

		update_post_meta( $post_id, '_eg_app_type', $type );
		update_post_meta( $post_id, '_eg_app_data', $data );
		update_post_meta( $post_id, '_eg_app_email', sanitize_email( $email ) );
		update_post_meta( $post_id, '_eg_app_status', 'pending' );

		$admins = get_option( 'admin_email' );
		if ( $admins ) {
			wp_mail(
				$admins,
				sprintf( '[Earth Goddess] New %s application', $type ),
				sprintf( "A new %s application was submitted by %s (%s).\nReview in WP Admin → EG Applications.", $type, $name, $email )
			);
		}

		self::redirect_back( $type, 'success' );
	}

	/**
	 * @param string $type   Type.
	 * @param string $status Status.
	 */
	private static function redirect_back( $type, $status ) {
		$slugs = array(
			'affiliate'  => 'affiliate-business-builder-application',
			'ambassador' => 'ambassador-application',
			'wholesale'  => 'wholesale-partner-application',
		);
		$url = home_url( '/' . $slugs[ $type ] . '/' );
		wp_safe_redirect( add_query_arg( 'eg_app', $status, $url ) );
		exit;
	}

	/**
	 * Meta box for review / approval.
	 */
	public static function add_meta_boxes() {
		add_meta_box( 'eg_app_details', 'Application Details', array( __CLASS__, 'render_meta_box' ), self::CPT, 'normal', 'high' );
	}

	/**
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		$type    = get_post_meta( $post->ID, '_eg_app_type', true );
		$data    = get_post_meta( $post->ID, '_eg_app_data', true );
		$status  = get_post_meta( $post->ID, '_eg_app_status', true );
		$user_id = (int) get_post_meta( $post->ID, '_eg_app_user_id', true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		if ( ! $status ) {
			$status = 'pending';
		}

		$badge = ( 'approved' === $status )
			? '<span style="display:inline-block;padding:4px 10px;border-radius:999px;background:#dcfce7;color:#166534;font-weight:700;">APPROVED</span>'
			: '<span style="display:inline-block;padding:4px 10px;border-radius:999px;background:#fef3c7;color:#92400e;font-weight:700;">PENDING</span>';

		echo '<p><strong>Type:</strong> ' . esc_html( (string) $type ) . '</p>';
		echo '<p><strong>Status:</strong> ' . $badge . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			echo '<p><strong>Linked user:</strong> <a href="' . esc_url( get_edit_user_link( $user_id ) ) . '">';
			echo esc_html( $user ? $user->user_email : ( 'User #' . $user_id ) );
			echo '</a></p>';
			if ( $user && in_array( 'affiliate_business_builder', (array) $user->roles, true ) ) {
				echo '<p><strong>Next:</strong> Ask them to log in → My Account → Affiliate dashboard → copy referral link.</p>';
			} elseif ( $user && in_array( 'ambassador', (array) $user->roles, true ) ) {
				echo '<p><strong>Next:</strong> Ask them to log in → My Account → Affiliate dashboard → copy referral link.</p>';
			} elseif ( $user && EG_Roles::user_is_wholesale( $user ) ) {
				echo '<p><strong>Next:</strong> Ask them to log in and place a wholesale order (≥ $250 for Starter).</p>';
			}
		}
		echo '<table class="widefat striped"><tbody>';
		foreach ( $data as $k => $v ) {
			echo '<tr><th style="width:30%">' . esc_html( $k ) . '</th><td>' . esc_html( (string) $v ) . '</td></tr>';
		}
		echo '</tbody></table>';

		if ( 'approved' !== $status ) {
			$action_url = wp_nonce_url(
				admin_url( 'admin-post.php?action=eg_approve_application&application_id=' . (int) $post->ID ),
				'eg_approve_application_' . (int) $post->ID
			);
			echo '<p style="margin-top:16px"><a class="button button-primary button-hero" href="' . esc_url( $action_url ) . '">Approve Application</a></p>';
			echo '<p class="description">Affiliate → role affiliate_business_builder + Clear Quartz tag. Ambassador → ambassador + Seed tag. Wholesale → wholesale_starter.</p>';
		} else {
			echo '<div class="notice notice-success inline" style="margin:16px 0 0"><p><strong>This application is approved.</strong> Status will also show as Approved in the EG Applications list.</p></div>';
		}
	}

	/**
	 * Approve via dedicated admin-post action (works outside block editor).
	 */
	public static function handle_approve() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'earth-goddess' ) );
		}

		$post_id = isset( $_GET['application_id'] ) ? absint( $_GET['application_id'] ) : 0;
		if ( ! $post_id || empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'eg_approve_application_' . $post_id ) ) {
			wp_die( esc_html__( 'Invalid approval request.', 'earth-goddess' ) );
		}

		$result = self::approve_application( $post_id );
		$args   = array(
			'post' => $post_id,
			'action' => 'edit',
		);
		if ( is_wp_error( $result ) ) {
			$args['eg_app_notice'] = 'error';
			$args['eg_app_msg']    = rawurlencode( $result->get_error_message() );
		} else {
			$args['eg_app_notice'] = 'approved';
			$args['eg_app_user']   = (int) $result;
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'post.php' ) ) );
		exit;
	}

	/**
	 * Approve application and assign role / affiliate flags.
	 *
	 * @param int $post_id Application ID.
	 * @return int|WP_Error User ID on success.
	 */
	public static function approve_application( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || self::CPT !== $post->post_type ) {
			return new WP_Error( 'eg_invalid', 'Application not found.' );
		}

		if ( 'approved' === get_post_meta( $post_id, '_eg_app_status', true ) ) {
			$user_id = (int) get_post_meta( $post_id, '_eg_app_user_id', true );
			return $user_id ? $user_id : new WP_Error( 'eg_already', 'Already approved, but user ID missing.' );
		}

		$type  = get_post_meta( $post_id, '_eg_app_type', true );
		$email = sanitize_email( (string) get_post_meta( $post_id, '_eg_app_email', true ) );
		$data  = get_post_meta( $post_id, '_eg_app_data', true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		if ( ! $email || ! is_email( $email ) ) {
			return new WP_Error( 'eg_email', 'Application is missing a valid email address.' );
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			$password = wp_generate_password( 12, true );
			$login    = self::unique_username_from_email( $email );
			if ( function_exists( 'wc_create_new_customer' ) ) {
				$user_id = wc_create_new_customer( $email, $login, $password );
			} else {
				$user_id = wp_create_user( $login, $password, $email );
			}
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}
			$user = get_user_by( 'id', $user_id );
			if ( ! empty( $data['full_name'] ) ) {
				$parts = explode( ' ', $data['full_name'], 2 );
				wp_update_user(
					array(
						'ID'           => $user_id,
						'first_name'   => $parts[0],
						'last_name'    => isset( $parts[1] ) ? $parts[1] : '',
						'display_name' => $data['full_name'],
					)
				);
			}
			// Send password setup email when possible.
			if ( function_exists( 'wp_new_user_notification' ) ) {
				wp_new_user_notification( $user_id, null, 'user' );
			}
		}

		if ( ! $user ) {
			return new WP_Error( 'eg_user', 'Could not create or load the applicant user.' );
		}

		foreach ( array_keys( EG_Roles::ROLES ) as $role ) {
			$user->remove_role( $role );
		}
		$user->remove_role( 'customer' );

		if ( 'affiliate' === $type ) {
			$user->set_role( 'affiliate_business_builder' );
			self::ensure_affiliate_record( $user->ID );
			self::assign_affiliate_tag( $user->ID, 'clear-quartz-partner' );
		} elseif ( 'ambassador' === $type ) {
			$user->set_role( 'ambassador' );
			self::ensure_affiliate_record( $user->ID );
			self::assign_affiliate_tag( $user->ID, 'seed-ambassador' );
		} elseif ( 'wholesale' === $type ) {
			$user->set_role( 'wholesale_starter' );
		} else {
			return new WP_Error( 'eg_type', 'Unknown application type: ' . $type );
		}

		update_post_meta( $post_id, '_eg_app_status', 'approved' );
		update_post_meta( $post_id, '_eg_app_user_id', $user->ID );

		$title = (string) $post->post_title;
		if ( 0 !== strpos( $title, '[Approved]' ) ) {
			$title = '[Approved] ' . preg_replace( '/^\[Pending\]\s*/i', '', $title );
		}
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'private',
				'post_title'  => $title,
			)
		);

		return (int) $user->ID;
	}

	/**
	 * @param string $email Email.
	 * @return string
	 */
	private static function unique_username_from_email( $email ) {
		$base = sanitize_user( current( explode( '@', $email ) ), true );
		if ( strlen( $base ) < 3 ) {
			$base = 'eguser';
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
	 * Admin notices after approve redirect.
	 */
	public static function admin_notices() {
		if ( empty( $_GET['eg_app_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$notice = sanitize_key( wp_unslash( $_GET['eg_app_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'approved' === $notice ) {
			$user_id = isset( $_GET['eg_app_user'] ) ? absint( $_GET['eg_app_user'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p><strong>Application status: APPROVED.</strong>';
			if ( $user_id ) {
				echo ' Linked user: <a href="' . esc_url( get_edit_user_link( $user_id ) ) . '">' . (int) $user_id . '</a>.';
			}
			echo ' Status also shows as Approved in the EG Applications list.</p>';
			echo '<p>Next: log in as the applicant → My Account → Affiliate dashboard → copy referral link → place a test order in Incognito via that link.</p></div>';
		} elseif ( 'error' === $notice ) {
			$msg = isset( $_GET['eg_app_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['eg_app_msg'] ) ) ) : 'Approval failed.'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}

	/**
	 * @param int $user_id User ID.
	 */
	private static function ensure_affiliate_record( $user_id ) {
		update_user_meta( $user_id, 'afwc_is_affiliate', 'yes' );
		if ( ! get_user_meta( $user_id, 'afwc_affiliate_since', true ) ) {
			update_user_meta( $user_id, 'afwc_affiliate_since', current_time( 'mysql' ) );
		}
		// Keep AFWC affiliate role setting in sync when possible.
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
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$columns['eg_type']   = 'Type';
		$columns['eg_status'] = 'Status';
		$columns['eg_email']  = 'Email';
		return $columns;
	}

	/**
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'eg_type' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_eg_app_type', true ) );
		} elseif ( 'eg_status' === $column ) {
			$status = (string) get_post_meta( $post_id, '_eg_app_status', true );
			if ( ! $status ) {
				$status = 'pending';
			}
			if ( 'approved' === $status ) {
				echo '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:#dcfce7;color:#166534;font-weight:700;">Approved</span>';
			} else {
				echo '<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:#fef3c7;color:#92400e;font-weight:700;">Pending</span>';
			}
		} elseif ( 'eg_email' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_eg_app_email', true ) );
		}
	}
}
