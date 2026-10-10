<?php
/**
 * Plugin Name: Earth Goddess pathway forms
 * Description: Saves the Elementor pathway forms into EG Applications, and joins the VIP circle.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Elementor form bridge for the pathway pages.
 */
class EG_Pathway_Forms {

	const FORMS = array(
		'eg-affiliate'  => 'affiliate',
		'eg-ambassador' => 'ambassador',
		'eg-wholesale'  => 'wholesale',
		'eg-vip'        => 'vip',
	);

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_action( 'elementor_pro/forms/actions/register', array( __CLASS__, 'register_action' ) );
		add_action( 'elementor_pro/forms/validation', array( __CLASS__, 'validate' ), 10, 2 );
	}

	/**
	 * @param object $registrar Form actions registrar.
	 */
	public static function register_action( $registrar ) {
		if ( ! class_exists( '\ElementorPro\Modules\Forms\Classes\Action_Base' ) ) {
			return;
		}

		if ( ! class_exists( 'EG_Pathway_Form_Action', false ) ) {
			require_once __DIR__ . '/earth-goddess-phase1/class-pathway-form-action.php';
		}

		$registrar->register( new EG_Pathway_Form_Action() );
	}

	/**
	 * Applications must use an email that already has a shop account.
	 *
	 * @param object $record Form record.
	 * @param object $ajax   Ajax handler.
	 */
	public static function validate( $record, $ajax ) {
		$type = self::type_from_record( $record );
		if ( ! $type || 'vip' === $type ) {
			return;
		}

		$email = self::email_from_record( $record );
		if ( ! is_email( $email ) || get_user_by( 'email', $email ) ) {
			return;
		}

		$ajax->add_error(
			'email',
			__( 'Use the email on your Earth Goddess account. Register on My Account first, then submit this application.', 'earth-goddess' )
		);
	}

	/**
	 * @param object $record Form record.
	 * @param object $ajax   Ajax handler.
	 */
	public static function handle( $record, $ajax ) {
		$type = self::type_from_record( $record );
		if ( ! $type ) {
			return;
		}

		$data = array();
		$fields = $record->get( 'fields' );
		if ( is_array( $fields ) ) {
			foreach ( $fields as $id => $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				$data[ sanitize_key( $id ) ] = isset( $field['value'] ) ? sanitize_textarea_field( (string) $field['value'] ) : '';
			}
		}

		if ( 'vip' === $type ) {
			self::join_vip( $data, $ajax );
			return;
		}

		self::create_application( $type, $data, $ajax );
	}

	/**
	 * @param object $record Form record.
	 * @return string
	 */
	private static function type_from_record( $record ) {
		$name = (string) $record->get_form_settings( 'form_name' );
		return isset( self::FORMS[ $name ] ) ? self::FORMS[ $name ] : '';
	}

	/**
	 * @param object $record Form record.
	 * @return string
	 */
	private static function email_from_record( $record ) {
		$fields = $record->get( 'fields' );
		if ( ! is_array( $fields ) || empty( $fields['email']['value'] ) ) {
			return '';
		}
		return sanitize_email( (string) $fields['email']['value'] );
	}

	/**
	 * @param string               $type Type.
	 * @param array<string,string> $data Fields.
	 * @param object               $ajax Ajax handler.
	 */
	private static function create_application( $type, $data, $ajax ) {
		if ( ! class_exists( 'EG_Applications' ) ) {
			$ajax->add_error_message( __( 'Applications are not available right now. Please try again later.', 'earth-goddess' ) );
			return;
		}

		$email = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
		$first = isset( $data['first_name'] ) ? $data['first_name'] : '';
		$last  = isset( $data['last_name'] ) ? $data['last_name'] : '';
		$name  = trim( $first . ' ' . $last );
		if ( '' === $name && ! empty( $data['contact_name'] ) ) {
			$name = $data['contact_name'];
		}
		if ( '' === $name && ! empty( $data['business_name'] ) ) {
			$name = $data['business_name'];
		}

		$data['full_name']      = $name;
		$data['contact_person'] = ! empty( $data['contact_name'] ) ? $data['contact_name'] : $name;
		$data['social_links']   = isset( $data['social'] ) ? $data['social'] : '';
		$data['why_join']       = self::joined( $data, array( 'interests', 'updates' ) );
		$data['why_partner']    = self::joined( $data, array( 'why', 'about', 'content_types', 'audience' ) );
		$data['reason']         = self::joined( $data, array( 'about', 'products', 'business_type' ) );
		$data['website']        = isset( $data['website'] ) ? $data['website'] : '';
		$data['business_type']  = isset( $data['business_type'] ) ? $data['business_type'] : '';
		$data['phone']          = isset( $data['phone'] ) ? $data['phone'] : '';

		if ( ! is_email( $email ) || '' === $name ) {
			$ajax->add_error_message( __( 'Please complete your name and email.', 'earth-goddess' ) );
			return;
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			$ajax->add_error( 'email', __( 'Use the email on your Earth Goddess account. Register on My Account first, then submit this application.', 'earth-goddess' ) );
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => EG_Applications::CPT,
				'post_status' => 'publish',
				'post_title'  => sprintf( '[Pending] %s – %s', ucfirst( $type ), $name ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$ajax->add_error_message( __( 'We could not save your application. Please try again.', 'earth-goddess' ) );
			return;
		}

		update_post_meta( $post_id, '_eg_app_type', $type );
		update_post_meta( $post_id, '_eg_app_data', $data );
		update_post_meta( $post_id, '_eg_app_email', $email );
		update_post_meta( $post_id, '_eg_app_status', 'pending' );
		update_post_meta( $post_id, '_eg_app_user_id', (int) $user->ID );

		$admin = get_option( 'admin_email' );
		if ( $admin ) {
			wp_mail(
				$admin,
				sprintf( '[Earth Goddess] New %s application', $type ),
				sprintf( "A new %s application was submitted by %s (%s).\nReview in WP Admin → EG Applications.", $type, $name, $email )
			);
		}
	}

	/**
	 * @param array<string,string> $data Fields.
	 * @param object               $ajax Ajax handler.
	 */
	private static function join_vip( $data, $ajax ) {
		$email = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
		$first = isset( $data['first_name'] ) ? $data['first_name'] : '';
		$last  = isset( $data['last_name'] ) ? $data['last_name'] : '';

		if ( ! is_email( $email ) || '' === trim( $first . $last ) ) {
			$ajax->add_error_message( __( 'Please complete your name and email.', 'earth-goddess' ) );
			return;
		}

		$user = get_user_by( 'email', $email );
		if ( $user && ( in_array( 'administrator', (array) $user->roles, true ) || in_array( 'shop_manager', (array) $user->roles, true ) ) ) {
			return;
		}

		if ( ! $user ) {
			$login = sanitize_user( strstr( $email, '@', true ), true );
			if ( '' === $login || preg_match( '/^\d+$/', $login ) || username_exists( $login ) ) {
				$login = 'vip' . strtolower( wp_generate_password( 6, false, false ) );
			}

			$user_id = wp_insert_user(
				array(
					'user_login' => $login,
					'user_email' => $email,
					'user_pass'  => wp_generate_password( 24, true, true ),
					'first_name' => $first,
					'last_name'  => $last,
					'role'       => 'customer',
				)
			);

			if ( is_wp_error( $user_id ) || ! $user_id ) {
				$ajax->add_error_message( __( 'We could not create your VIP account. Please try again.', 'earth-goddess' ) );
				return;
			}

			$user = get_user_by( 'id', $user_id );
			wp_new_user_notification( (int) $user_id, null, 'user' );
		} else {
			if ( '' === $user->first_name && '' !== $first ) {
				wp_update_user(
					array(
						'ID'         => $user->ID,
						'first_name' => $first,
						'last_name'  => $last,
					)
				);
			}
		}

		$user->add_role( 'vip_shopper' );

		if ( ! empty( $data['dob'] ) ) {
			update_user_meta( $user->ID, 'eg_vip_dob', $data['dob'] );
		}
		update_user_meta( $user->ID, 'eg_vip_newsletter', ( ! empty( $data['newsletter'] ) && 'on' === $data['newsletter'] ) ? 'yes' : 'no' );
		update_user_meta( $user->ID, 'billing_first_name', $first );
		update_user_meta( $user->ID, 'billing_last_name', $last );
		update_user_meta( $user->ID, 'billing_email', $email );
		if ( ! empty( $data['phone'] ) ) {
			update_user_meta( $user->ID, 'billing_phone', $data['phone'] );
		}
	}

	/**
	 * @param array<string,string> $data Fields.
	 * @param array<int,string>    $keys Keys.
	 * @return string
	 */
	private static function joined( $data, $keys ) {
		$parts = array();
		foreach ( $keys as $key ) {
			if ( ! empty( $data[ $key ] ) ) {
				$parts[] = $data[ $key ];
			}
		}
		return implode( "\n", $parts );
	}
}

EG_Pathway_Forms::init();
