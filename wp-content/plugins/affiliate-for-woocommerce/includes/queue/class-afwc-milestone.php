<?php
/**
 * Main class for tracking store-growth milestones and queuing feedback-widget prompts for them.
 *
 * @package     affiliate-for-woocommerce/includes/queue/
 * @since       9.15.0
 * @version     1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Milestone' ) ) {

	/**
	 * Class for tracking milestones and queuing a feedback-widget prompt whenever one is newly crossed.
	 */
	class AFWC_Milestone {

		/**
		 * Variable to hold the hook name for the milestone check scheduled action.
		 *
		 * @var string
		 */
		public $schedule_action = 'afwc_milestone_check';

		/**
		 * Variable to hold the group name for the scheduled action.
		 *
		 * @var string
		 */
		public $group = 'affiliate-for-woocommerce';

		/**
		 * Variable to hold the registered milestone types and their associated data.
		 *
		 * @var array
		 */
		public $milestones = array();

		/**
		 * Variable to hold instance of this class.
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get the single instance of this class.
		 *
		 * @return AFWC_Milestone
		 */
		public static function get_instance() {
			// Check if the instance already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			// Register the milestone types and their associated data.
			$this->milestones = array(
				'aff_order_count' => array(
					'tiers'            => array( 10, 100, 500, 1000, 2000, 5000 ),
					'total_callback'   => array( $this, 'get_aff_order_count' ),
					'title_callback'   => array( $this, 'get_aff_order_count_milestone_title' ),
					'message_callback' => array( $this, 'get_aff_order_count_milestone_message' ),
				),
				'aff_count'       => array(
					'tiers'            => array( 5, 10, 50, 100, 200, 500, 1000 ),
					'total_callback'   => array( $this, 'get_aff_count' ),
					'title_callback'   => array( $this, 'get_aff_count_milestone_title' ),
					'message_callback' => array( $this, 'get_aff_count_milestone_message' ),
				),
			);

			add_action( 'current_screen', array( $this, 'attempt_schedule' ) );
			add_action( $this->schedule_action, array( $this, 'run_check' ) );
		}

		/**
		 * Method to schedule the milestone check.
		 */
		public function attempt_schedule() {

			/**
			 * There is no need to schedule regularly if the admin is not visiting the dashboard where milestones are displayed.
			 * So schedule the next run only when the admin comes to a plugin page.
			 */
			$screen_id = function_exists( 'afwc_get_current_screen_data' ) ? afwc_get_current_screen_data() : '';
			if ( empty( $screen_id ) || ! is_string( $screen_id ) || false === strpos( $screen_id, '_affiliate-for-woocommerce' ) ) {
				return;
			}

			if ( ! $this->has_remaining_milestones() ) {
				return;
			}

			$this->schedule_next_run();
		}

		/**
		 * Method to check whether any registered milestone type still has a tier left to reach.
		 *
		 * @return bool True if at least one tier is still ahead, false when all are reached.
		 */
		public function has_remaining_milestones() {
			if ( empty( $this->milestones ) || ! is_array( $this->milestones ) ) {
				return false;
			}

			$state = self::get_state();

			foreach ( $this->milestones as $type => $definition ) {
				if ( empty( $type ) || empty( $definition ) || ! is_array( $definition )
					|| empty( $definition['tiers'] ) || ! is_array( $definition['tiers'] )
				) {
					continue;
				}

				$reached = $this->get_type_reached( $state, $type );
				if ( is_null( $reached ) || $reached < max( $definition['tiers'] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Method to get the highest tier a milestone type has reached, as saved in the state.
		 *
		 * @param array  $state Milestone state.
		 * @param string $type  Milestone type key.
		 *
		 * @return int|null The reached tier (0 when checked but none reached yet), or null when nothing is saved for the type.
		 */
		public function get_type_reached( $state = array(), $type = '' ) {
			if ( empty( $state ) || ! is_array( $state ) || empty( $type ) || ! is_string( $type )
				|| empty( $state[ $type ] ) || ! is_array( $state[ $type ] )
				|| ! isset( $state[ $type ]['reached'] ) || ! is_numeric( $state[ $type ]['reached'] )
			) {
				return null;
			}

			return max( 0, intval( $state[ $type ]['reached'] ) );
		}

		/**
		 * Method to schedule the next run.
		 */
		private function schedule_next_run() {
			if ( ! function_exists( 'as_has_scheduled_action' )
				|| ! function_exists( 'as_schedule_single_action' )
				|| as_has_scheduled_action( $this->schedule_action, array(), $this->group )
			) {
				return;
			}

			// Unique: two admin pages loading at the same moment must not schedule two checks (ignored by Action Scheduler before 3.6).
			as_schedule_single_action( $this->get_next_run_timestamp(), $this->schedule_action, array(), $this->group, true );
		}

		/**
		 * Method to get the timestamp for 3:00 AM, two days from today, in the site's timezone.
		 * Always two days ahead, so a check that already ran today next runs the day after tomorrow, not tomorrow.
		 *
		 * @return int Unix timestamp.
		 */
		public function get_next_run_timestamp() {
			$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
			$next     = new DateTime( 'today 03:00', $timezone );
			$next->modify( '+2 days' );

			return $next->getTimestamp();
		}

		/**
		 * Method to run the milestone check for every registered type.
		 * Not rescheduled from here: the next run is scheduled on the admin's next visit to a plugin page.
		 */
		public function run_check() {
			if ( empty( $this->milestones ) || ! is_array( $this->milestones ) ) {
				return;
			}

			$state = self::get_state();

			foreach ( $this->milestones as $type => $definition ) {
				if ( empty( $type ) || empty( $definition ) || ! is_array( $definition )
					|| empty( $definition['tiers'] ) || ! is_array( $definition['tiers'] )
					|| empty( $definition['total_callback'] ) || ! is_callable( $definition['total_callback'] )
				) {
					continue;
				}
				$total = call_user_func( $definition['total_callback'] );
				$state = $this->advance_type( $state, $type, $definition['tiers'], is_numeric( $total ) ? floatval( $total ) : 0 );
			}

			update_option( 'afwc_milestone_state', $state, 'no' );
		}

		/**
		 * Method to get the saved milestone state, defaulting to an empty array.
		 *
		 * Shape: array(
		 *     '<type>' => array( 'reached' => int ), // Highest tier reached; 0 = checked, none reached yet.
		 *     'queue'  => array( array( 'type' => string, 'tier' => int ) ), // Reached but not shown yet, oldest first.
		 * )
		 * Tier values are stored rather than positions, so the tier lists can change safely later.
		 *
		 * @return array
		 */
		public static function get_state() {
			$state = get_option( 'afwc_milestone_state', array() );

			return ! empty( $state ) && is_array( $state ) ? $state : array();
		}

		/**
		 * Method to get the queued milestone entries, oldest first.
		 *
		 * @return array
		 */
		public static function get_queue() {
			$state = self::get_state();

			return ! empty( $state['queue'] ) && is_array( $state['queue'] ) ? array_values( $state['queue'] ) : array();
		}

		/**
		 * Method to update one milestone type's reached tier against its current total, and queue a prompt for a newly reached tier.
		 *
		 * - A type with nothing saved yet (new install, update, or a type added later) is caught up silently:
		 *   tiers passed in the past get no retroactive prompt.
		 * - Only the highest newly reached tier is queued, and it replaces an unshown prompt of the same type,
		 *   so a big jump (e.g. an order import) shows one prompt, not one per tier.
		 *
		 * @param array  $state Current milestone state.
		 * @param string $type  Milestone type key.
		 * @param array  $tiers Tier values for this type.
		 * @param float  $total Current absolute total for this type.
		 *
		 * @return array Updated state.
		 */
		public function advance_type( $state = array(), $type = '', $tiers = array(), $total = 0 ) {
			$state = ! empty( $state ) && is_array( $state ) ? $state : array();

			if ( empty( $type ) || ! is_string( $type ) || empty( $tiers ) || ! is_array( $tiers ) ) {
				return $state;
			}

			$saved_reached    = $this->get_type_reached( $state, $type );
			$previous_reached = is_null( $saved_reached ) ? 0 : $saved_reached;
			$total            = is_numeric( $total ) ? floatval( $total ) : 0;

			// Highest tier the current total has reached.
			$highest_reached = 0;
			foreach ( $tiers as $tier ) {
				if ( $total >= $tier ) {
					$highest_reached = max( $highest_reached, intval( $tier ) );
				}
			}

			if ( ! is_null( $saved_reached ) && $highest_reached > $previous_reached ) {
				$queue = ! empty( $state['queue'] ) && is_array( $state['queue'] ) ? $state['queue'] : array();

				// Drop an unshown prompt of this type: the new, higher tier supersedes it.
				$queue = array_filter(
					$queue,
					function ( $entry ) use ( $type ) {
						return empty( $entry ) || ! is_array( $entry ) || empty( $entry['type'] ) || $type !== $entry['type'];
					}
				);

				$queue[]        = array(
					'type' => $type,
					'tier' => $highest_reached,
				);
				$state['queue'] = array_values( $queue );
			}

			$state[ $type ] = array( 'reached' => max( $previous_reached, $highest_reached ) );

			return $state;
		}

		/**
		 * Method to get the number of affiliate-referred orders in a paid status.
		 * Counts by order status only, so paid orders count whatever commission status they carry.
		 *
		 * @return int
		 */
		public function get_aff_order_count() {
			$order_statuses = function_exists( 'afwc_get_prefixed_order_statuses' ) ? afwc_get_prefixed_order_statuses() : array();
			if ( empty( $order_statuses ) || ! is_array( $order_statuses ) ) {
				return 0;
			}

			global $wpdb;

			$order_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT ref.post_id)
						FROM {$wpdb->prefix}afwc_referrals AS ref
					WHERE ref.order_status IN (" . implode( ',', array_fill( 0, count( $order_statuses ), '%s' ) ) . ")
						AND (ref.reference = '' OR ref.reference IS NULL)",
					array_values( $order_statuses )
				)
			);

			return ! empty( $order_count ) && is_numeric( $order_count ) ? intval( $order_count ) : 0;
		}

		/**
		 * Method to get the current approved affiliate count.
		 *
		 * @return int
		 */
		public function get_aff_count() {
			if ( ! class_exists( 'AFWC_Admin_Affiliates' ) ) {
				include_once AFWC_PLUGIN_DIRPATH . '/includes/admin/class-afwc-admin-affiliates.php';
			}
			if ( ! class_exists( 'AFWC_Admin_Affiliates' ) ) {
				return 0;
			}

			$admin_affiliates = new AFWC_Admin_Affiliates();
			$counts           = is_callable( array( $admin_affiliates, 'get_all_affiliates_count' ) ) ? $admin_affiliates->get_all_affiliates_count() : array();

			return ! empty( $counts ) && is_array( $counts ) && ! empty( $counts['active_affiliates_count'] ) && is_numeric( $counts['active_affiliates_count'] )
				? intval( $counts['active_affiliates_count'] )
				: 0;
		}

		/**
		 * Method to check whether a milestone-triggered feedback prompt is pending.
		 *
		 * @return bool True if a milestone is pending, false otherwise.
		 */
		public static function has_pending() {
			return ! empty( self::get_pending_copy() );
		}

		/**
		 * Method to get the display copy for the oldest pending milestone that can be shown.
		 * Entries that cannot be shown (e.g. a type that is no longer registered) are skipped.
		 *
		 * @return array|null { 'title' => string, 'message' => string } or null if nothing is pending.
		 */
		public static function get_pending_copy() {
			$queue = self::get_queue();
			if ( empty( $queue ) || ! is_array( $queue ) ) {
				return null;
			}

			$instance = self::get_instance();
			foreach ( $queue as $entry ) {
				$copy = $instance->resolve_copy( $entry );
				if ( ! empty( $copy ) && is_array( $copy ) ) {
					return $copy;
				}
			}

			return null;
		}

		/**
		 * Method to consume (remove) the oldest pending milestone once its prompt has been shown to the admin.
		 * Entries ahead of it that cannot be shown are removed too, so they never block the queue.
		 */
		public static function consume_pending() {
			$state = self::get_state();
			if ( empty( $state['queue'] ) || ! is_array( $state['queue'] ) ) {
				return;
			}

			$instance       = self::get_instance();
			$state['queue'] = array_values( $state['queue'] );
			while ( ! empty( $state['queue'] ) ) {
				$entry = array_shift( $state['queue'] );
				if ( ! empty( $instance->resolve_copy( $entry ) ) ) {
					break; // The one that was shown.
				}
			}

			update_option( 'afwc_milestone_state', $state, 'no' );
		}

		/**
		 * Method to resolve a queued milestone entry into display copy using its type's registered callbacks.
		 *
		 * @param array $entry { 'type' => string, 'tier' => int|float }.
		 *
		 * @return array|null
		 */
		public function resolve_copy( $entry = array() ) {
			if ( empty( $entry ) || ! is_array( $entry )
				|| empty( $entry['type'] ) || ! is_string( $entry['type'] )
				|| empty( $entry['tier'] ) || ! is_numeric( $entry['tier'] ) || intval( $entry['tier'] ) <= 0
				|| empty( $this->milestones ) || ! is_array( $this->milestones )
			) {
				return null;
			}

			$definition = ! empty( $this->milestones[ $entry['type'] ] ) ? $this->milestones[ $entry['type'] ] : array();

			if ( empty( $definition ) || ! is_array( $definition )
				|| empty( $definition['title_callback'] ) || ! is_callable( $definition['title_callback'] )
				|| empty( $definition['message_callback'] ) || ! is_callable( $definition['message_callback'] )
			) {
				return null;
			}

			$tier    = intval( $entry['tier'] );
			$title   = call_user_func( $definition['title_callback'], $tier );
			$message = call_user_func( $definition['message_callback'], $tier );

			// Show a milestone only with both lines of its copy.
			if ( empty( $title ) || ! is_string( $title ) || empty( $message ) || ! is_string( $message ) ) {
				return null;
			}

			return array(
				'title'   => $title,
				'message' => $message,
			);
		}

		/**
		 * Method to build the headline for an affiliate-orders milestone.
		 *
		 * @param int $tier The crossed order-count tier.
		 *
		 * @return string
		 */
		public function get_aff_order_count_milestone_title( $tier = 0 ) {
			return sprintf(
				/* translators: %s: number of orders referred by affiliates */
				_nx( '%s affiliate order', '%s affiliate orders', intval( $tier ), 'Feedback widget headline for affiliate orders milestone', 'affiliate-for-woocommerce' ),
				number_format_i18n( intval( $tier ) )
			);
		}

		/**
		 * Method to build the message for an affiliate-orders milestone.
		 *
		 * @param int $tier The crossed order-count tier.
		 *
		 * @return string
		 */
		public function get_aff_order_count_milestone_message( $tier = 0 ) {
			$messages = array(
				10   => _x( 'Your affiliates are already bringing real sales.', 'Feedback widget message for 10 affiliate orders milestone', 'affiliate-for-woocommerce' ),
				100  => _x( 'Your affiliates are a proven sales channel.', 'Feedback widget message for 100 affiliate orders milestone', 'affiliate-for-woocommerce' ),
				500  => _x( 'Customers keep buying through your affiliates.', 'Feedback widget message for 500 affiliate orders milestone', 'affiliate-for-woocommerce' ),
				1000 => _x( 'This is a strong affiliate program. You built it.', 'Feedback widget message for 1,000 affiliate orders milestone', 'affiliate-for-woocommerce' ),
				2000 => _x( 'Look at what your affiliate program has become!', 'Feedback widget message for 2,000 affiliate orders milestone', 'affiliate-for-woocommerce' ),
				5000 => _x( 'Your affiliate program is a true success story!', 'Feedback widget message for 5,000 affiliate orders milestone', 'affiliate-for-woocommerce' ),
			);

			$tier = intval( $tier );

			return ! empty( $messages[ $tier ] ) ? $messages[ $tier ] : '';
		}

		/**
		 * Method to build the headline for an affiliate-count milestone.
		 *
		 * @param int $tier The crossed affiliate-count tier.
		 *
		 * @return string
		 */
		public function get_aff_count_milestone_title( $tier = 0 ) {
			return sprintf(
				/* translators: %s: number of active affiliates */
				_nx( '%s affiliate joined', '%s affiliates joined', intval( $tier ), 'Feedback widget headline for affiliate-count milestone', 'affiliate-for-woocommerce' ),
				number_format_i18n( intval( $tier ) )
			);
		}

		/**
		 * Method to build the message for an affiliate-count milestone.
		 *
		 * @param int $tier The crossed affiliate-count tier.
		 *
		 * @return string
		 */
		public function get_aff_count_milestone_message( $tier = 0 ) {
			$messages = array(
				5    => _x( 'People already trust your affiliate program.', 'Feedback widget message for 5 affiliates milestone', 'affiliate-for-woocommerce' ),
				10   => _x( 'Your store now has a team of people promoting it.', 'Feedback widget message for 10 affiliates milestone', 'affiliate-for-woocommerce' ),
				50   => _x( 'More and more people want to work with you.', 'Feedback widget message for 50 affiliates milestone', 'affiliate-for-woocommerce' ),
				100  => _x( 'You have built a real affiliate community.', 'Feedback widget message for 100 affiliates milestone', 'affiliate-for-woocommerce' ),
				200  => _x( 'So many people now promote your store!', 'Feedback widget message for 200 affiliates milestone', 'affiliate-for-woocommerce' ),
				500  => _x( 'Your affiliate team keeps getting bigger!', 'Feedback widget message for 500 affiliates milestone', 'affiliate-for-woocommerce' ),
				1000 => _x( 'What you built is now a huge community!', 'Feedback widget message for 1,000 affiliates milestone', 'affiliate-for-woocommerce' ),
			);

			$tier = intval( $tier );

			return ! empty( $messages[ $tier ] ) ? $messages[ $tier ] : '';
		}
	}
}

// Auto initialize the class.
if ( class_exists( 'AFWC_Milestone' ) && is_callable( array( 'AFWC_Milestone', 'get_instance' ) ) ) {
	AFWC_Milestone::get_instance();
}
