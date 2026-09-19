<?php
/**
 * Main class for Affiliates Coupon functionality
 *
 * @package     affiliate-for-woocommerce/includes/tracking/
 * @since       1.7.0
 * @version     1.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Coupon' ) ) {

	/**
	 * Main class for Affiliate Coupon functionality
	 */
	class AFWC_Coupon {

		/**
		 * Variable to hold instance of AFWC_Coupon
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		public function __construct() {
			$use_referral_coupons = get_option( 'afwc_use_referral_coupons', 'yes' );
			if ( is_admin() && 'yes' === $use_referral_coupons ) {
				add_action( 'woocommerce_coupon_options', array( $this, 'affiliate_restriction' ), 10, 2 );

				// Filter for WooCommerce > Coupons table.
				add_filter( 'views_edit-shop_coupon', array( $this, 'add_referral_coupons_filter' ) );
				add_action( 'pre_get_posts', array( $this, 'filter_referral_coupons' ) );
			}
			add_action( 'woocommerce_coupon_options_save', array( $this, 'save_affiliate_coupon_fields' ), 10, 2 );

			add_action( 'woocommerce_applied_coupon', array( $this, 'coupon_applied' ) );

			add_action( 'wc_ajax_afwc_generate_referral_coupon', array( $this, 'afwc_create_coupon_by_affiliate' ) );
		}

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_Coupon Singleton object of this class
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Assign a coupon to an affiliate
		 *
		 * @param int    $coupon_id The Coupon ID.
		 * @param object $coupon The Coupon Object.
		 */
		public function affiliate_restriction( $coupon_id = 0, $coupon = null ) {

			if ( empty( $coupon_id ) ) {
				return;
			}

			if ( ! empty( $coupon_id ) && ( empty( $coupon ) || ! is_object( $coupon ) || ! $coupon instanceof WC_Coupon ) ) {
				$coupon = new WC_Coupon( $coupon_id );
			}

			$user_id = $coupon->get_meta( 'afwc_referral_coupon_of', true );

			if ( empty( $user_id ) && ! empty( $_GET ) && is_array( $_GET ) && ! empty( $_GET['afwc_referral_coupon_of'] ) && is_numeric( $_GET['afwc_referral_coupon_of'] ) ) { // phpcs:ignore
				$user_id = afwc_is_user_affiliate( intval( $_GET['afwc_referral_coupon_of'] ) ) === 'yes' ? intval( $_GET['afwc_referral_coupon_of'] ) : 0; // phpcs:ignore
			}

			global $affiliate_for_woocommerce;

			?>
			<div class="options_group afwc-field">
				<p class="form-field">
					<label for="afwc_referral_coupon_of">
						<?php echo esc_html_x( 'Assign to affiliate', 'Label for coupon affiliate assignment field', 'affiliate-for-woocommerce' ); ?>
					</label>
					<?php
					is_callable( array( $affiliate_for_woocommerce, 'render_affiliate_search' ) )
						? $affiliate_for_woocommerce->render_affiliate_search(
							'afwc_referral_coupon_of',
							array(
								'affiliate_id' => $user_id,
								'style'        => 'width: 50%;',
							)
						)
						: '';
					echo wp_kses_post( wc_help_tip( _x( 'Search affiliate by email, username, name or user id to assign this coupon to them. Affiliates will see this coupon in their My account > Affiliates > Profile.', 'help tip for search and assign affiliate', 'affiliate-for-woocommerce' ) ) );
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Method to add referral coupons filter on Coupons list page.
		 *
		 * @param array $views Existing filters on Coupons list page.
		 *
		 * @return array Modified filters with referral coupons filter added.
		 */
		public function add_referral_coupons_filter( $views = array() ) {
			if ( empty( $views ) || ! is_array( $views ) ) {
				return $views;
			}

			$referral_coupons_count = $this->get_referral_coupon(
				array(
					'post_status' => 'any',
					'return'      => array( 'count_only' => true ),
				)
			);

			$class = ( ! empty( $_GET['filter_afwc_referral_coupons'] ) && '1' === $_GET['filter_afwc_referral_coupons'] ) ? 'current' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$views['filter_afwc_referral_coupons'] = sprintf(
				// translators: 1. URL to filter referral coupons, 2. CSS class for the filter link, 3. Label for the filter link, 4. Count of referral coupons.
				'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$s)</span></a>',
				add_query_arg( array( 'filter_afwc_referral_coupons' => '1' ), admin_url( 'edit.php?post_type=shop_coupon' ) ),
				$class,
				_x( 'Affiliate', 'coupon filter label on coupon list', 'affiliate-for-woocommerce' ),
				$referral_coupons_count
			);

			return $views;
		}

		/**
		 * Method to filter the Coupons list to display only referral coupons when the "Referral Coupons" filter is selected.
		 *
		 * @param \WP_Query $query The WordPress Query object.
		 *
		 * @return void
		 */
		public function filter_referral_coupons( $query = null ) {
			if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'shop_coupon' ) {
				return;
			}

			if ( ! empty( $_GET['filter_afwc_referral_coupons'] ) && '1' === $_GET['filter_afwc_referral_coupons'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$query->set(
					'meta_query',
					array(
						'relation' => 'OR',
						array(
							'key'     => 'afwc_referral_coupon_of',
							'value'   => '',
							'compare' => '!=',
						),
					)
				);
			}
		}

		/**
		 * Saves our custom coupon fields.
		 *
		 * @param int    $coupon_id The coupon's ID.
		 * @param object $coupon    The coupon's object.
		 */
		public function save_affiliate_coupon_fields( $coupon_id = 0, $coupon = null ) {
			// Verify the nonce.
			if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) { // phpcs:ignore
				return;
			}

			if ( empty( $coupon_id ) ) {
				return;
			}

			// Always refresh object to get updated meta values.
			$coupon = new WC_Coupon( intval( $coupon_id ) );
			if ( ! $coupon instanceof WC_Coupon ) {
				return;
			}

			$affiliate_id = ( ! empty( $_POST['afwc_referral_coupon_of'] ) ) ? wc_clean( wp_unslash( $_POST['afwc_referral_coupon_of'] ) ) : 0; // phpcs:ignore
			if ( ! empty( $affiliate_id ) ) {
				$coupon->update_meta_data( 'afwc_referral_coupon_of', $affiliate_id );
			} else {
				$coupon->delete_meta_data( 'afwc_referral_coupon_of' );
			}
			$coupon->save();
		}

		/**
		 * Method to get referral coupons for a user.
		 *
		 * @param array $args  Arguments to filter and format referral coupons.
		 *
		 * @return array|int List of referral coupons or count of referral coupons, formatted according to provided arguments.
		 */
		public function get_referral_coupon( $args = array() ) {
			if ( empty( $args ) ) {
				return array();
			}

			$user_id = isset( $args['user_id'] ) ? intval( $args['user_id'] ) : 0;

			$defaults = array(
				'post_status' => 'publish',
				'numberposts' => -1,
				'return'      => array(
					'count_only'             => false,
					'with_edit_url'          => false,
					'with_self_created_flag' => false,
				),
			);
			$args     = wp_parse_args( $args, $defaults );

			$query_args = array(
				'post_type'   => 'shop_coupon',
				'post_status' => $args['post_status'],
				'numberposts' => $args['numberposts'],
				'order'       => 'ASC',
				'meta_key'    => 'afwc_referral_coupon_of', // phpcs:ignore
			);

			if ( $user_id > 0 ) {
				$query_args['meta_value'] = $user_id; // phpcs:ignore
			}

			if ( ! empty( $args['author'] ) ) {
				$query_args['author'] = intval( $args['author'] );
			}

			if ( ! empty( $args['return']['count_only'] ) ) {
				$query_args['fields'] = 'ids';
			}

			$coupons = get_posts( $query_args );

			if ( ! empty( $args['return']['count_only'] ) ) {
				return ! empty( $coupons ) && is_array( $coupons ) ? count( $coupons ) : 0;
			}

			if ( empty( $coupons ) ) {
				return array();
			}

			$coupons_list = array();
			foreach ( $coupons as $coupon ) {
				if ( empty( $coupon->ID ) || empty( $coupon->post_title ) ) {
					continue;
				}

				$coupon_id   = $coupon->ID;
				$coupon_code = $coupon->post_title;

				if ( ! empty( $args['return'] ) && in_array( true, $args['return'], true ) ) {
					$coupons_list[ $coupon_id ] = array(
						'coupon_code' => $coupon_code,
					);
					if ( ! empty( $args['return']['with_edit_url'] ) ) {
						$coupons_list[ $coupon_id ]['coupon_edit_url'] = get_edit_post_link( $coupon_id, '&' );
					} elseif ( ! empty( $args['return']['with_self_created_flag'] ) ) {
						$coupons_list[ $coupon_id ]['is_coupon_self_created'] = ( intval( get_post_field( 'post_author', $coupon_id, 'raw' ) ) === $user_id );
					}
				} else {
					$coupons_list[ $coupon_id ] = $coupon_code;
				}
			}

			return $coupons_list;
		}

		/**
		 * Handle hit if the referral coupon is applied.
		 *
		 * @param string $coupon_code The coupon code.
		 */
		public function coupon_applied( $coupon_code = null ) {

			if ( empty( $coupon_code ) ) {
				return;
			}

			$coupon = new WC_Coupon( $coupon_code );
			if ( ! $coupon instanceof WC_Coupon ) {
				return;
			}

			$affiliate_id = $coupon->get_meta( 'afwc_referral_coupon_of', true );
			if ( empty( $affiliate_id ) ) {
				return;
			}

			$afwc = Affiliate_For_WooCommerce::get_instance();
			$afwc->handle_hit( $affiliate_id, 'coupon' );
		}

		/**
		 * Given a coupon code, return some params to show with the coupon.
		 *
		 * @param string $coupon_code The coupon code.
		 * @return array
		 */
		public function get_coupon_params( $coupon_code = '' ) {
			if ( empty( $coupon_code ) ) {
				return array();
			}

			$coupon_params = array();

			$coupon                 = new WC_Coupon( $coupon_code );
			$coupon_discount_type   = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_discount_type' ) ) ) ? $coupon->get_discount_type() : '';
			$coupon_discount_amount = ( is_object( $coupon ) && is_callable( array( $coupon, 'get_amount' ) ) ) ? $coupon->get_amount() : '';

			$coupon_params = array(
				'discount_type'   => $coupon_discount_type,
				'discount_amount' => $coupon_discount_amount,
			);

			return $coupon_params;
		}

		/**
		 * Get the coupon URL.
		 * Needs a 3rd party plugin/developer to generate.
		 *
		 * @param string $code The coupon code.
		 *
		 * @return string The filtered coupon URL.
		 */
		public function get_coupon_url( $code = '' ) {
			if ( empty( $code ) ) {
				return '';
			}

			/**
			 * Filter for coupon URL.
			 * Default URL is empty as Affiliate For WooCommerce could not generate coupon URL.
			 *
			 * @since 8.7.0
			 *
			 * @param string Coupon URL.
			 * @param string $code Coupon code.
			 */
			return apply_filters( 'afwc_coupon_url', '', $code );
		}

		/**
		 * Retrieve the affiliate ID assigned to a given coupon.
		 *
		 * @param string|WC_Coupon $code Coupon code or WC_Coupon object.
		 *
		 * @return int Affiliate ID if assigned and valid, otherwise 0.
		 */
		public function get_affiliate( $code = '' ) {
			if ( empty( $code ) ) {
				return 0;
			}

			$coupon = $code instanceof WC_Coupon ? $code : new WC_Coupon( $code );

			if ( ! $coupon instanceof WC_Coupon ) {
				return 0;
			}

			$affiliate_id = $coupon->get_meta( 'afwc_referral_coupon_of', true );

			if ( empty( $affiliate_id ) ) {
				return 0;
			}

			$affiliate_id = intval( $affiliate_id );

			// Check if user is a valid affiliate.
			return 'yes' === afwc_is_user_affiliate( $affiliate_id ) ? $affiliate_id : 0;
		}

		/**
		 * Method to generate a referral coupon for affiliates.
		 *
		 * This callback validates the security nonce, confirms the user is a logged-in affiliate,
		 * and checks that they do not already have an existing referral coupon,
		 * and validates the submitted coupon parameters (code, amount, and maximum allowed amount).
		 * If all checks pass, it generates a unique referral coupon associated with the affiliate.
		 *
		 * @return void Outputs JSON response for success or failure.
		 */
		public function afwc_create_coupon_by_affiliate() {
			check_ajax_referer( 'afwc-affiliate-create-referral-coupon', 'security' );

			$user    = wp_get_current_user();
			$user_id = ! empty( $user->ID ) ? intval( $user->ID ) : 0;
			if ( empty( $user_id ) ) {
				wp_send_json_error( array( 'message' => _x( 'Log in to create a coupon.', 'affiliate coupon creation error when user is not logged in', 'affiliate-for-woocommerce' ) ) );
			}

			if ( 'yes' !== afwc_is_user_affiliate( $user_id ) ) {
				wp_send_json_error( array( 'message' => _x( 'Only affiliates can create referral coupons.', 'affiliate coupon creation error when user is not an affiliate', 'affiliate-for-woocommerce' ) ) );
			}

			$afwc_maximum_self_created_coupons = afwc_get_self_made_coupon_create_limit( $user_id );
			if ( empty( $afwc_maximum_self_created_coupons )
				|| $afwc_maximum_self_created_coupons <= 0
				|| ( ! ( 'yes' === get_option( 'afwc_enable_affiliate_made_coupons', 'no' ) ) )
			) {
				wp_send_json_error( array( 'message' => _x( 'You cannot create referral coupons.', 'affiliate coupon creation error when affiliate is not allowed to create referral coupon', 'affiliate-for-woocommerce' ) ) );
			}

			$coupon_code     = ! empty( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
			$coupon_discount = ! empty( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;

			$max_allowed_discount = floatval( get_option( 'afwc_affiliate_made_coupon_discount_limit', 0 ) );

			if ( $coupon_discount < 0 || $coupon_discount > 100 || $max_allowed_discount < 0 || $coupon_discount > $max_allowed_discount ) {
				wp_send_json_error( array( 'message' => _x( 'Discount amount is invalid.', 'affiliate coupon creation error when discount amount is not valid', 'affiliate-for-woocommerce' ) ) );
			}

			if ( empty( $coupon_code ) || wc_get_coupon_id_by_code( $coupon_code ) ) {
				wp_send_json_error( array( 'message' => _x( 'Invalid code. Please try another.', 'affiliate coupon creation error when coupon code is existed or invalid', 'affiliate-for-woocommerce' ) ) );
			}

			$existing_self_created_coupons = is_callable( array( $this, 'get_referral_coupon' ) )
				? $this->get_referral_coupon(
					array(
						'user_id'     => $user_id,
						'numberposts' => $afwc_maximum_self_created_coupons,
						'author'      => $user_id,
					)
				)
				: array();
			if ( ! empty( $existing_self_created_coupons ) && is_array( $existing_self_created_coupons ) && count( $existing_self_created_coupons ) >= $afwc_maximum_self_created_coupons ) {
				wp_send_json_error( array( 'message' => _x( 'You’ve reached your referral coupon limit.', 'affiliate coupon creation error when affiliate already enough self created referral coupon', 'affiliate-for-woocommerce' ) ) );
			}

			$generated_code = '';
			$coupon_api     = ( is_callable( array( 'AFWC_Coupon_API', 'get_instance' ) ) ) ? AFWC_Coupon_API::get_instance() : null;
			if ( is_callable( array( $coupon_api, 'generate_coupon_code' ) ) ) {
				$args           = array(
					'return'                  => 'code',
					'discount_type'           => 'percent',
					'amount'                  => $coupon_discount,
					'code'                    => $coupon_code,
					'description'             => esc_html_x( 'Referral coupon created by affiliate', 'affiliate coupon description template', 'affiliate-for-woocommerce' ),
					'filter'                  => 'afwc_affiliate_generated_referral_coupon_meta',
					'afwc_referral_coupon_of' => $user_id,
				);
				$generated_code = $coupon_api->generate_coupon_code( $args );
			}

			if ( empty( $generated_code ) ) {
				wp_send_json_error( array( 'message' => _x( 'Could not create a coupon.', 'affiliate coupon create failure', 'affiliate-for-woocommerce' ) ) );
			}

			wp_send_json_success( array( 'message' => _x( 'Coupon created successfully.', 'affiliate coupon success notification', 'affiliate-for-woocommerce' ) ) );
		}
	}

}

return new AFWC_Coupon();
