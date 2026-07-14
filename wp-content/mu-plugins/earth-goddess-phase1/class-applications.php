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
		add_action( 'admin_post_nopriv_eg_submit_application', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_eg_submit_application', array( __CLASS__, 'handle_submit' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_approval' ), 10, 2 );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_pages' ) );
		add_action( 'woocommerce_account_dashboard', array( __CLASS__, 'render_my_account_pathways' ), 5 );
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
				'full_name'      => 'Name',
				'email'          => 'Email',
				'social_links'   => 'Social Media Profiles',
				'audience_size'  => 'Audience Size',
				'main_platform'  => 'Main Platform',
				'why_partner'    => 'Why do you wish to partner with Earth Goddess AU?',
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
				'business_name' => 'Business Name',
				'abn'           => 'ABN',
				'website'       => 'Website',
				'business_type' => 'Business Type',
				'contact_person'=> 'Contact Person',
				'email'         => 'Email',
				'phone'         => 'Phone',
				'reason'        => 'Reason for Applying',
			)
		);
	}

	/**
	 * @param string               $type   Form type.
	 * @param array<string,string> $fields Fields.
	 * @return string
	 */
	private static function render_form( $type, $fields ) {
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
		echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<form class="eg-application-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="eg_submit_application" />
			<input type="hidden" name="eg_app_type" value="<?php echo esc_attr( $type ); ?>" />
			<?php wp_nonce_field( 'eg_submit_application_' . $type, 'eg_app_nonce' ); ?>
			<?php foreach ( $fields as $name => $label ) : ?>
				<p>
					<label for="eg_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label><br />
					<?php if ( in_array( $name, array( 'why_join', 'why_partner', 'reason', 'social_links' ), true ) ) : ?>
						<textarea id="eg_<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="4" required></textarea>
					<?php else : ?>
						<input id="eg_<?php echo esc_attr( $name ); ?>" type="<?php echo 'email' === $name ? 'email' : 'text'; ?>" name="<?php echo esc_attr( $name ); ?>" required />
					<?php endif; ?>
				</p>
			<?php endforeach; ?>
			<p><button type="submit"><?php esc_html_e( 'Submit Application', 'earth-goddess' ); ?></button></p>
		</form>
		<?php
		return (string) ob_get_clean();
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

		if ( empty( $email ) || empty( $name ) ) {
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
		$type   = get_post_meta( $post->ID, '_eg_app_type', true );
		$data   = get_post_meta( $post->ID, '_eg_app_data', true );
		$status = get_post_meta( $post->ID, '_eg_app_status', true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		wp_nonce_field( 'eg_app_approve_' . $post->ID, 'eg_app_approve_nonce' );
		echo '<p><strong>Type:</strong> ' . esc_html( (string) $type ) . '</p>';
		echo '<p><strong>Status:</strong> ' . esc_html( $status ? $status : 'pending' ) . '</p>';
		echo '<table class="widefat"><tbody>';
		foreach ( $data as $k => $v ) {
			echo '<tr><th>' . esc_html( $k ) . '</th><td>' . esc_html( (string) $v ) . '</td></tr>';
		}
		echo '</tbody></table>';

		if ( 'approved' !== $status ) {
			echo '<p><label><input type="checkbox" name="eg_approve_application" value="1" /> Approve and assign role / default affiliate rank</label></p>';
			echo '<p class="description">Affiliate → role affiliate_business_builder + tag Clear Quartz Partner. Ambassador → ambassador + Seed Ambassador. Wholesale → wholesale_starter.</p>';
		} else {
			echo '<p><em>Already approved.</em></p>';
		}
	}

	/**
	 * On approve: assign role and AFWC defaults.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_approval( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( empty( $_POST['eg_app_approve_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eg_app_approve_nonce'] ) ), 'eg_app_approve_' . $post_id ) ) {
			return;
		}
		if ( empty( $_POST['eg_approve_application'] ) ) {
			return;
		}
		if ( 'approved' === get_post_meta( $post_id, '_eg_app_status', true ) ) {
			return;
		}

		$type  = get_post_meta( $post_id, '_eg_app_type', true );
		$email = get_post_meta( $post_id, '_eg_app_email', true );
		$data  = get_post_meta( $post_id, '_eg_app_data', true );
		$user  = get_user_by( 'email', $email );

		if ( ! $user ) {
			$password = wp_generate_password();
			if ( function_exists( 'wc_create_new_customer' ) ) {
				$user_id = wc_create_new_customer(
					$email,
					sanitize_user( current( explode( '@', $email ) ), true ),
					$password
				);
			} else {
				$user_id = wp_create_user( sanitize_user( current( explode( '@', $email ) ), true ), $password, $email );
			}
			if ( is_wp_error( $user_id ) ) {
				return;
			}
			$user = get_user_by( 'id', $user_id );
			if ( is_array( $data ) && ! empty( $data['full_name'] ) ) {
				$parts = explode( ' ', $data['full_name'], 2 );
				wp_update_user(
					array(
						'ID'         => $user_id,
						'first_name' => $parts[0],
						'last_name'  => isset( $parts[1] ) ? $parts[1] : '',
						'display_name' => $data['full_name'],
					)
				);
			}
		}

		if ( ! $user ) {
			return;
		}

		// Reset to a single program role.
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
		}

		update_post_meta( $post_id, '_eg_app_status', 'approved' );
		update_post_meta( $post_id, '_eg_app_user_id', $user->ID );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'private',
			)
		);
	}

	/**
	 * @param int $user_id User ID.
	 */
	private static function ensure_affiliate_record( $user_id ) {
		update_user_meta( $user_id, 'afwc_is_affiliate', 'yes' );
		if ( ! get_user_meta( $user_id, 'afwc_affiliate_since', true ) ) {
			update_user_meta( $user_id, 'afwc_affiliate_since', current_time( 'mysql' ) );
		}
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
			return;
		}
		wp_set_object_terms( $user_id, array( (int) $term->term_id ), 'afwc_user_tags', false );
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
			echo esc_html( (string) get_post_meta( $post_id, '_eg_app_status', true ) );
		} elseif ( 'eg_email' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_eg_app_email', true ) );
		}
	}
}
