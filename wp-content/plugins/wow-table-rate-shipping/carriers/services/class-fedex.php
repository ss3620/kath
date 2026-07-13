<?php // phpcs:ignore
namespace WTRS\Carriers\Services;

defined( 'ABSPATH' ) || exit;

use WTRS\Carriers\AbstractCarrier;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Rate;

/**
 * FedEx Carrier Class
 *
 * REST integration using FedEx OAuth2 and Rates API v1.
 * Mirrors the structure/pattern used by Australia Post for consistency.
 */
final class Fedex extends AbstractCarrier {

	private const LIVE_RATE_URL = 'https://apis.fedex.com/rate/v1/rates/quotes';
	private const TEST_RATE_URL = 'https://apis-sandbox.fedex.com/rate/v1/rates/quotes';
	private const LIVE_AUTH_URL = 'https://apis.fedex.com/oauth/token';
	private const TEST_AUTH_URL = 'https://apis-sandbox.fedex.com/oauth/token';

	private const SERVICES = array(
		array(
			'label' => 'FedEx International Priority® Express - U.S. Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx International First® - U.S. Region',
			'value' => 'INTERNATIONAL_FIRST',
		),
		array(
			'label' => 'FedEx International Priority® - U.S. Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY',
		),
		array(
			'label' => 'FedEx LTL Freight Priority - U.S. Region',
			'value' => 'FEDEX_FREIGHT_PRIORITY',
		),
		array(
			'label' => 'FedEx LTL Freight Economy - U.S. Region',
			'value' => 'FEDEX_FREIGHT_ECONOMY',
		),
		array(
			'label' => 'FedEx International Economy® - U.S. Region',
			'value' => 'INTERNATIONAL_ECONOMY',
		),
		array(
			'label' => 'FedEx International Ground® and FedEx Domestic Ground® - U.S. Region',
			'value' => 'FEDEX_GROUND',
		),
		array(
			'label' => 'FedEx First Overnight® - U.S. Region',
			'value' => 'FIRST_OVERNIGHT',
		),
		array(
			'label' => 'FedEx First Overnight® Freight - U.S. Region',
			'value' => 'FEDEX_FIRST_FREIGHT',
		),
		array(
			'label' => 'FedEx 1Day® Freight (Hawaii service is to and from the island of Oahu only) - U.S. Region',
			'value' => 'FEDEX_1_DAY_FREIGHT',
		),
		array(
			'label' => 'FedEx 2Day® Freight (Hawaii service is to and from the island of Oahu only) - U.S. Region',
			'value' => 'FEDEX_2_DAY_FREIGHT',
		),
		array(
			'label' => 'FedEx 3Day® Freight (Except Alaska and Hawaii) - U.S. Region',
			'value' => 'FEDEX_3_DAY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Priority® Freight - U.S. Region',
			'value' => 'INTERNATIONAL_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Economy® Freight - U.S. Region',
			'value' => 'INTERNATIONAL_ECONOMY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Connect Plus® - U.S. Region',
			'value' => 'FEDEX_INTERNATIONAL_CONNECT_PLUS',
		),
		array(
			'label' => 'FedEx® International Deferred Freight - U.S. Region',
			'value' => 'FEDEX_INTERNATIONAL_DEFERRED_FREIGHT',
		),
		array(
			'label' => 'FedEx International Priority DirectDistribution® - U.S. Region',
			'value' => 'INTERNATIONAL_PRIORITY_DISTRIBUTION',
		),
		array(
			'label' => 'FedEx International Priority DirectDistribution® Freight - U.S. Region',
			'value' => 'INTERNATIONAL_DISTRIBUTION_FREIGHT',
		),
		array(
			'label' => 'International Ground® Distribution (IGD) - U.S. Region',
			'value' => 'INTL_GROUND_DISTRIBUTION',
		),
		array(
			'label' => 'FedEx Home Delivery® - U.S. Region',
			'value' => 'GROUND_HOME_DELIVERY',
		),
		array(
			'label' => 'FedEx Ground® Economy (Formerly known as FedEx SmartPost®) - U.S. Region',
			'value' => 'SMART_POST',
		),
		array(
			'label' => 'FedEx Priority Overnight® - U.S. Region',
			'value' => 'PRIORITY_OVERNIGHT',
		),
		array(
			'label' => 'FedEx Standard Overnight® (Hawaii outbound only) - U.S. Region',
			'value' => 'STANDARD_OVERNIGHT',
		),
		array(
			'label' => 'FedEx 2Day® (Except Intra-Hawaii) - U.S. Region',
			'value' => 'FEDEX_2_DAY',
		),
		array(
			'label' => 'FedEx 2Day® AM (Hawaii outbound only) - U.S. Region',
			'value' => 'FEDEX_2_DAY_AM',
		),
		array(
			'label' => 'FedEx Express Saver® (Except Alaska and Hawaii) - U.S. Region',
			'value' => 'FEDEX_EXPRESS_SAVER',
		),
		array(
			'label' => 'FedEx SameDay® - U.S. Region',
			'value' => 'SAME_DAY',
		),
		array(
			'label' => 'FedEx SameDay® City (Selected U.S. Metro Areas) - U.S. Region',
			'value' => 'SAME_DAY_CITY',
		),
		array(
			'label' => 'FedEx First Overnight® - Canada Region',
			'value' => 'FIRST_OVERNIGHT',
		),
		array(
			'label' => 'FedEx Priority Overnight® - Canada Region',
			'value' => 'PRIORITY_OVERNIGHT',
		),
		array(
			'label' => 'FedEx Standard Overnight® - Canada Region',
			'value' => 'STANDARD_OVERNIGHT',
		),
		array(
			'label' => 'FedEx 2Day® - Canada Region',
			'value' => 'FEDEX_2_DAY',
		),
		array(
			'label' => 'FedEx Economy - Canada Region',
			'value' => 'FEDEX_EXPRESS_SAVER',
		),
		array(
			'label' => 'FedEx International Ground® and FedEx Domestic Ground® - Canada Region',
			'value' => 'FEDEX_GROUND',
		),
		array(
			'label' => 'FedEx LTL Freight Priority - Canada Region',
			'value' => 'FEDEX_FREIGHT_PRIORITY',
		),
		array(
			'label' => 'FedEx LTL Freight Economy - Canada Region',
			'value' => 'FEDEX_FREIGHT_ECONOMY',
		),
		array(
			'label' => 'FedEx 1Day® Freight - Canada Region',
			'value' => 'FEDEX_1_DAY_FREIGHT',
		),
		array(
			'label' => 'FedEx 2Day® Freight - Canada Region',
			'value' => 'FEDEX_2_DAY_FREIGHT',
		),
		array(
			'label' => 'FedEx 3Day® Freight - Canada Region',
			'value' => 'FEDEX_3_DAY_FREIGHT',
		),
		array(
			'label' => 'FedEx® International Priority Freight - Canada Region',
			'value' => 'INTERNATIONAL_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx® International Economy Freight - Canada Region',
			'value' => 'INTERNATIONAL_ECONOMY_FREIGHT',
		),
		array(
			'label' => 'FedEx® International Deferred Freight - Canada Region',
			'value' => 'FEDEX_INTERNATIONAL_DEFERRED_FREIGHT',
		),
		array(
			'label' => 'FedEx International Connect Plus® - Canada Region',
			'value' => 'FEDEX_INTERNATIONAL_CONNECT_PLUS',
		),
		array(
			'label' => 'FedEx International First® - Canada Region',
			'value' => 'INTERNATIONAL_FIRST',
		),
		array(
			'label' => 'FedEx International Priority® Express - Canada Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx International Priority® - Canada Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY',
		),
		array(
			'label' => 'FedEx® International Economy - Canada Region',
			'value' => 'INTERNATIONAL_ECONOMY',
		),
		array(
			'label' => 'FedEx International Priority DirectDistribution® Freight (consolidation only) - Canada Region',
			'value' => 'INTERNATIONAL_DISTRIBUTION_FREIGHT',
		),
		array(
			'label' => 'FedEx International Priority DirectDistribution® (consolidation only) - Canada Region',
			'value' => 'INTERNATIONAL_PRIORITY_DISTRIBUTION',
		),
		array(
			'label' => 'FedEx International Economy DirectDistribution® (consolidation only) - Canada Region',
			'value' => 'INTERNATIONAL_ECONOMY_DISTRIBUTION',
		),
		array(
			'label' => 'International Ground® Distribution (IGD) (consolidation only) - Canada Region',
			'value' => 'INTL_GROUND_DISTRIBUTION',
		),
		array(
			'label' => 'Transborder distribution (consolidation only) - Canada Region',
			'value' => 'TRANSBORDER_DISTRIBUTION',
		),
		array(
			'label' => 'FedEx International Priority® Express - LAC Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx International Priority® - LAC Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY',
		),
		array(
			'label' => 'FedEx International Economy® - LAC Region',
			'value' => 'INTERNATIONAL_ECONOMY',
		),
		array(
			'label' => 'FedEx International Priority® Freight - LAC Region',
			'value' => 'INTERNATIONAL_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Economy® Freight - LAC Region',
			'value' => 'INTERNATIONAL_ECONOMY_FREIGHT',
		),
		array(
			'label' => 'FedEx® International Deferred Freight - LAC Region',
			'value' => 'FEDEX_INTERNATIONAL_DEFERRED_FREIGHT',
		),
		array(
			'label' => 'FedEx First Overnight® (Only Mexico) - LAC Region',
			'value' => 'FIRST_OVERNIGHT',
		),
		array(
			'label' => 'FedEx Priority Overnight® - LAC Region',
			'value' => 'PRIORITY_OVERNIGHT',
		),
		array(
			'label' => 'FedEx Standard Overnight® - LAC Region',
			'value' => 'STANDARD_OVERNIGHT',
		),
		array(
			'label' => 'FedEx International Priority DirectDistribution® - LAC Region',
			'value' => 'INTERNATIONAL_PRIORITY_DISTRIBUTION',
		),
		array(
			'label' => 'FedEx Express Saver® - LAC Region',
			'value' => 'FEDEX_EXPRESS_SAVER',
		),
		array(
			'label' => 'FedEx SameDay® City (Only for selected cities in Mexico) - LAC Region',
			'value' => 'SAME_DAY_CITY',
		),
		array(
			'label' => 'FedEx 1Day® Freight (Only Mexico) - LAC Region',
			'value' => 'FEDEX_1_DAY_FREIGHT',
		),
		array(
			'label' => 'FedEx 2Day® Freight (Only Mexico) - LAC Region',
			'value' => 'FEDEX_2_DAY_FREIGHT',
		),
		array(
			'label' => 'FedEx First (Only Chile) - LAC Region',
			'value' => 'FEDEX_FIRST',
		),
		array(
			'label' => 'FedEx Economy (Only Chile) - LAC Region',
			'value' => 'FEDEX_ECONOMY',
		),
		array(
			'label' => 'FedEx Priority (Only Chile) - LAC Region',
			'value' => 'FEDEX_PRIORITY',
		),
		array(
			'label' => 'FedEx Priority Express (Only Chile) - LAC Region',
			'value' => 'FEDEX_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx Priority Express Freight (Only Chile) - LAC Region',
			'value' => 'FEDEX_PRIORITY_EXPRESS_FREIGHT',
		),
		array(
			'label' => 'FedEx Priority Freight (Only Chile) - LAC Region',
			'value' => 'FEDEX_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx Economy Freight (Only Chile) - LAC Region',
			'value' => 'FEDEX_ECONOMY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Priority® Express - APAC Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx International Priority® - APAC Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY',
		),
		array(
			'label' => 'FedEx International First® - APAC Region',
			'value' => 'INTERNATIONAL_FIRST',
		),
		array(
			'label' => 'FedEx International Economy® - APAC Region',
			'value' => 'INTERNATIONAL_ECONOMY',
		),
		array(
			'label' => 'FedEx International Priority DirectDistribution® - APAC Region',
			'value' => 'INTERNATIONAL_PRIORITY_DISTRIBUTION',
		),
		array(
			'label' => 'FedEx International Economy DirectDistribution - APAC Region',
			'value' => 'INTERNATIONAL_ECONOMY_DISTRIBUTION',
		),
		array(
			'label' => 'FedEx International Connect Plus® - APAC Region',
			'value' => 'FEDEX_INTERNATIONAL_CONNECT_PLUS',
		),
		array(
			'label' => 'FedEx International Priority® Freight - APAC Region',
			'value' => 'INTERNATIONAL_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Economy® Freight - APAC Region',
			'value' => 'INTERNATIONAL_ECONOMY_FREIGHT',
		),
		array(
			'label' => 'FedEx® International Deferred Freight - APAC Region',
			'value' => 'FEDEX_INTERNATIONAL_DEFERRED_FREIGHT',
		),
		array(
			'label' => 'FedEx Priority (Only Malaysia and Thailand) - APAC Region',
			'value' => 'FEDEX_PRIORITY',
		),
		array(
			'label' => 'FedEx Priority Express (Only Thailand) - APAC Region',
			'value' => 'FEDEX_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx Priority Express Freight (Only Malaysia and Thailand) - APAC Region',
			'value' => 'FEDEX_PRIORITY_EXPRESS_FREIGHT',
		),
		array(
			'label' => 'FedEx Priority Freight (Only Thailand) - APAC Region',
			'value' => 'FEDEX_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Priority® Express - MEISA Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx International Priority® - MEISA Region',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY',
		),
		array(
			'label' => 'FedEx International Economy® - MEISA Region',
			'value' => 'INTERNATIONAL_ECONOMY',
		),
		array(
			'label' => 'FedEx International Priority® Freight - MEISA Region',
			'value' => 'INTERNATIONAL_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Economy® Freight - MEISA Region',
			'value' => 'INTERNATIONAL_ECONOMY_FREIGHT',
		),
		array(
			'label' => 'FedEx® International Deferred Freight - MEISA Region',
			'value' => 'FEDEX_INTERNATIONAL_DEFERRED_FREIGHT',
		),
		array(
			'label' => 'FedEx First Overnight® - MEISA Region',
			'value' => 'FIRST_OVERNIGHT',
		),
		array(
			'label' => 'FedEx Priority Overnight® - MEISA Region',
			'value' => 'PRIORITY_OVERNIGHT',
		),
		array(
			'label' => 'FedEx Standard Overnight® - MEISA Region',
			'value' => 'STANDARD_OVERNIGHT',
		),
		array(
			'label' => 'FedEx First (Only South Africa) - MEISA Region',
			'value' => 'FEDEX_FIRST',
		),
		array(
			'label' => 'FedEx Economy (Only South Africa) - MEISA Region',
			'value' => 'FEDEX_ECONOMY',
		),
		array(
			'label' => 'FedEx Priority (Only South Africa) - MEISA Region',
			'value' => 'FEDEX_PRIORITY',
		),
		array(
			'label' => 'FedEx Priority Express (Only South Africa) - MEISA Region',
			'value' => 'FEDEX_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx Priority Express Freight (Only South Africa) - MEISA Region',
			'value' => 'FEDEX_PRIORITY_EXPRESS_FREIGHT',
		),
		array(
			'label' => 'FedEx Priority Freight (Only South Africa) - MEISA Region',
			'value' => 'FEDEX_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx Economy Freight (Only South Africa) - MEISA Region',
			'value' => 'FEDEX_ECONOMY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Priority® Express - EU International',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx International Priority® Freight - EU International',
			'value' => 'INTERNATIONAL_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx International Priority® - EU International',
			'value' => 'FEDEX_INTERNATIONAL_PRIORITY',
		),
		array(
			'label' => 'FedEx International Connect Plus® - EU International',
			'value' => 'FEDEX_INTERNATIONAL_CONNECT_PLUS',
		),
		array(
			'label' => 'FedEx International Economy® - EU International',
			'value' => 'INTERNATIONAL_ECONOMY',
		),
		array(
			'label' => 'FedEx International Economy® Freight - EU International',
			'value' => 'INTERNATIONAL_ECONOMY_FREIGHT',
		),
		array(
			'label' => 'FedEx® International Deferred Freight - EU International',
			'value' => 'FEDEX_INTERNATIONAL_DEFERRED_FREIGHT',
		),
		array(
			'label' => 'FedEx International First® - EU International',
			'value' => 'INTERNATIONAL_FIRST',
		),
		array(
			'label' => 'FedEx International Priority DirectDistribution® - EU International',
			'value' => 'INTERNATIONAL_PRIORITY_DISTRIBUTION',
		),
		array(
			'label' => 'International Distribution Freight - EU International',
			'value' => 'INTERNATIONAL_DISTRIBUTION_FREIGHT',
		),
		array(
			'label' => 'FedEx International Economy DirectDistribution - EU International',
			'value' => 'INTERNATIONAL_ECONOMY_DISTRIBUTION',
		),
		array(
			'label' => 'FedEx® Regional Economy - EU International',
			'value' => 'FEDEX_REGIONAL_ECONOMY',
		),
		array(
			'label' => 'FedEx® Regional Economy Freight - EU International',
			'value' => 'FEDEX_REGIONAL_ECONOMY_FREIGHT',
		),
		array(
			'label' => 'FedEx Priority Overnight® (Selected Markets) - EU Domestic',
			'value' => 'PRIORITY_OVERNIGHT',
		),
		array(
			'label' => 'FedEx First - EU Domestic',
			'value' => 'FEDEX_FIRST',
		),
		array(
			'label' => 'FedEx Priority Express - EU Domestic',
			'value' => 'FEDEX_PRIORITY_EXPRESS',
		),
		array(
			'label' => 'FedEx Priority - EU Domestic',
			'value' => 'FEDEX_PRIORITY',
		),
		array(
			'label' => 'FedEx Priority Express Freight - EU Domestic',
			'value' => 'FEDEX_PRIORITY_EXPRESS_FREIGHT',
		),
		array(
			'label' => 'FedEx Priority Freight - EU Domestic',
			'value' => 'FEDEX_PRIORITY_FREIGHT',
		),
		array(
			'label' => 'FedEx Economy (Only U.K.) - EU Domestic',
			'value' => 'FEDEX_ECONOMY_SELECT',
		),
	);

	private const SPECIAL_SERVICES = array(
		array(
			'label' => 'FedEx Appointment Home Delivery®',
			'value' => 'APPOINTMENT',
		),
		array(
			'label' => 'Broker Select Option',
			'value' => 'BROKER_SELECT_OPTION',
		),
		array(
			'label' => 'Call Before Delivery',
			'value' => 'CALL_BEFORE_DELIVERY',
		),
		array(
			'label' => 'Collect on Delivery (COD)',
			'value' => 'COD',
		),
		array(
			'label' => 'Custom Delivery Window',
			'value' => 'CUSTOM_DELIVERY_WINDOW',
		),
		array(
			'label' => 'Cut Flowers',
			'value' => 'CUT_FLOWERS',
		),
		array(
			'label' => 'Do Not Break Down Pallets',
			'value' => 'DO_NOT_BREAK_DOWN_PALLETS',
		),
		array(
			'label' => 'Do Not Stack Pallets',
			'value' => 'DO_NOT_STACK_PALLETS',
		),
		array(
			'label' => 'Dry Ice',
			'value' => 'DRY_ICE',
		),
		array(
			'label' => 'East Coast Special Service',
			'value' => 'EAST_COAST_SPECIAL',
		),
		array(
			'label' => 'Exclude From Consolidation',
			'value' => 'EXCLUDE_FROM_CONSOLIDATION',
		),
		array(
			'label' => 'Extreme Length',
			'value' => 'EXTREME_LENGTH',
		),
		array(
			'label' => 'FedEx Inside Delivery',
			'value' => 'INSIDE_DELIVERY',
		),
		array(
			'label' => 'FedEx Inside Pickup',
			'value' => 'INSIDE_PICKUP',
		),
		array(
			'label' => 'FedEx International Controlled Export',
			'value' => 'INTERNATIONAL_CONTROLLED_EXPORT_SERVICE',
		),
		array(
			'label' => 'FedEx One Rate®',
			'value' => 'FEDEX_ONE_RATE',
		),
		array(
			'label' => 'FedEx Third Party Consignee International Priority service (TPC)',
			'value' => 'THIRD_PARTY_CONSIGNEE',
		),
		array(
			'label' => 'FedEx® Electronic Trade Documents',
			'value' => 'ELECTRONIC_TRADE_DOCUMENTS',
		),
		array(
			'label' => 'Food',
			'value' => 'FOOD',
		),
		array(
			'label' => 'International Traffic in Arms Regulations(ITAR)',
			'value' => 'INTERNATIONAL_TRAFFIC_IN_ARMS_REGULATIONS',
		),
		array(
			'label' => 'LiftGate Delivery',
			'value' => 'LIFTGATE_DELIVERY',
		),
		array(
			'label' => 'LiftGate Pickup',
			'value' => 'LIFTGATE_PICKUP',
		),
		array(
			'label' => 'Limited Access Delivery',
			'value' => 'LIMITED_ACCESS_DELIVERY',
		),
		array(
			'label' => 'Limited Access Pickup',
			'value' => 'LIMITED_ACCESS_PICKUP',
		),
		array(
			'label' => 'Over Length',
			'value' => 'OVER_LENGTH',
		),
		array(
			'label' => 'Pending Shipment',
			'value' => 'PENDING_SHIPMENT',
		),
		array(
			'label' => 'Pharmacy Delivery',
			'value' => 'PHARMACY_DELIVERY',
		),
		array(
			'label' => 'Poison',
			'value' => 'POISON',
		),
		array(
			'label' => 'Premium Home Delivery',
			'value' => 'HOME_DELIVERY_PREMIUM',
		),
		array(
			'label' => 'Protection From Freezing',
			'value' => 'PROTECTION_FROM_FREEZING',
		),
		array(
			'label' => 'Return Clearance',
			'value' => 'RETURNS_CLEARANCE',
		),
		array(
			'label' => 'Return Shipment',
			'value' => 'RETURN_SHIPMENT',
		),
		array(
			'label' => 'Saturday Delivery',
			'value' => 'SATURDAY_DELIVERY',
		),
		array(
			'label' => 'Saturday Pickup',
			'value' => 'SATURDAY_PICKUP',
		),
		array(
			'label' => 'Shipment Event Notification',
			'value' => 'EVENT_NOTIFICATION',
		),
		array(
			'label' => 'Delivery on Invoice Acceptance',
			'value' => 'DELIVERY_ON_INVOICE_ACCEPTANCE',
		),
		array(
			'label' => 'Top Load',
			'value' => 'TOP_LOAD',
		),
		array(
			'label' => 'Freight Guarantee',
			'value' => 'FREIGHT_GUARANTEE',
		),
	);

	private const PICKUP_TYPES = array(
		array(
			'value' => 'CONTACT_FEDEX_TO_SCHEDULE',
			'label' => 'Contact FedEx for pickup',
		),
		array(
			'value' => 'DROPOFF_AT_FEDEX_LOCATION',
			'label' => 'Drop off at FedEx location',
		),
		array(
			'value' => 'USE_SCHEDULED_PICKUP',
			'label' => 'Use scheduled pickup',
		),
		array(
			'value' => 'ON_CALL',
			'label' => 'Schedule by phone',
		),
		array(
			'value' => 'PACKAGE_RETURN_PROGRAM',
			'label' => 'FedEx Ground return pickup',
		),
		array(
			'value' => 'REGULAR_STOP',
			'label' => 'Regular pickup stop',
		),
		array(
			'value' => 'TAG',
			'label' => 'Tag/call tag pickup',
		),
	);

	private const DUTY_PAYMENT_TYPES = array(
		array(
			'value' => 'SENDER',
			'label' => 'Sender',
		),
		array(
			'value' => 'RECIPIENT',
			'label' => 'Recipient',
		),
	);

	/**
	 * {@inheritdoc}
	 */
	public static function get_key(): string {
		return 'fedex';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_label(): string {
		return 'FedEx';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_supported_packers(): array {
		return array( 'single_package', 'per_item', 'box', 'weight' );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $type Value type key.
	 * @return mixed
	 */
	public static function get_values( $type ) {
		if ( 'supported_packers' === $type ) {
			return self::add_packer_labels( self::get_supported_packers() );
		}

		if ( 'services' === $type ) {
			return self::SERVICES;
		}

		if ( 'specialServices' === $type ) {
			return self::SPECIAL_SERVICES;
		}

		if ( 'pickupTypes' === $type ) {
			return self::PICKUP_TYPES;
		}

		if ( 'dutyPaymentTypes' === $type ) {
			return self::DUTY_PAYMENT_TYPES;
		}

		return array();
	}

	/**
	 * Parse settings to apply default values and validation.
	 *
	 * @param array $values values.
	 * @return array
	 */
	protected static function parse_settings( $values ) {
		$parsed = array();

		$parsed['mode'] = in_array( $values['mode'] ?? '', array( 'test', 'live' ), true ) ? $values['mode'] : 'test';

		$parsed['service']                 = ! empty( $values['service'] ) ? strval( $values['service'] ) : 'FEDEX_GROUND';
		$parsed['accountRatesOnly']        = ! empty( $values['accountRatesOnly'] );
		$parsed['returnTransitTimes']      = true;
		$parsed['specialServices']         = ! empty( $values['specialServices'] ) && is_array( $values['specialServices'] ) ? $values['specialServices'] : array();
		$parsed['oneRateEnabled']          = in_array( 'FEDEX_ONE_RATE', $parsed['specialServices'], true ); // special service.
		$parsed['saturdayDeliveryEnabled'] = in_array( 'SATURDAY_DELIVERY', $parsed['specialServices'], true ); // special service.
		$parsed['insuranceEnabled']        = ! empty( $values['insuranceEnabled'] );
		$parsed['isResidential']           = ! empty( $values['isResidential'] );
		$pickup_type                       = sanitize_text_field( $values['pickupType'] ?? '' );
		if ( empty( $pickup_type ) ) {
			$pickup_type = 'CONTACT_FEDEX_TO_SCHEDULE';
		}
		$parsed['pickupType'] = $pickup_type;

		$packaging_type = sanitize_text_field( $values['shipPackagingType'] ?? '' );
		if ( empty( $packaging_type ) ) {
			$packaging_type = 'YOUR_PACKAGING';
		}
		$parsed['shipPackagingType'] = $packaging_type;
		$parsed['sendPackAsShip']    = ! empty( $values['sendPackAsShip'] );
		$parsed['dutyPaymentType']   = in_array( $values['dutyPaymentType'] ?? '', array( 'SENDER', 'RECIPIENT' ), true ) ? $values['dutyPaymentType'] : 'SENDER';

		return array_merge( parent::parse_settings( $values ), $parsed );
	}

	/**
	 * Fetch normalized rates.
	 *
	 * @param ShipmentRequest $request Shipment abstraction.
	 * @return array<Rate>|false
	 */
	public function fetch_rates( ShipmentRequest $request ) {
		$token = $this->get_oauth_token();
		if ( is_wp_error( $token ) ) {
			$this->error( 'auth_failed', $token );
			return false;
		}

		$payload  = $this->build_rate_request_payload( $request );
		$endpoint = $this->is_live() ? self::LIVE_RATE_URL : self::TEST_RATE_URL;

		$this->log(
			'fetching_rates',
			'Fetching rates from FedEx API',
			array(
				'endpoint' => $endpoint,
				'payload'  => $payload,
			)
		);

		$response = $this->remote_post_json( $endpoint, $payload, $token );

		if ( is_wp_error( $response ) ) {
			$this->error( 'api_error', $response );
			return false;
		}

		$rates = $this->parse_rate_response( $response, $request );

		if ( empty( $rates ) ) {
			$this->warning( 'no_rates_returned', 'No rates returned from FedEx API' );
			return false;
		}

		$this->log( 'rates_fetched', 'FedEx rates fetched successfully', array( 'rate_count' => count( $rates ) ) );

		return apply_filters( 'wtrs_carrier_filter_normalized_rates', $rates, $this->get_key(), $request );
	}

	/**
	 * Determine environment.
	 */
	protected function is_live(): bool {
		return ( $this->get_setting( 'mode', 'test' ) === 'live' );
	}

	/**
	 * Obtain an OAuth access token from FedEx.
	 *
	 * @return string|\WP_Error
	 */
	protected function get_oauth_token() {
		$url = $this->is_live() ? self::LIVE_AUTH_URL : self::TEST_AUTH_URL;

		$body = array(
			'grant_type'    => 'client_credentials',
			'client_id'     => $this->get_secret( 'api_key' ),
			'client_secret' => $this->get_secret( 'api_secret' ),
		);
		$args = array(
			'timeout'   => 40,
			'sslverify' => false,
			'body'      => http_build_query( $body ),
			'headers'   => array( 'content-type' => 'application/x-www-form-urlencoded' ),
		);
		$res  = wp_remote_post( $url, $args ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning,Generic.WhiteSpace.ScopeIndent.Incorrect
		if ( is_wp_error( $res ) ) {
			$this->error( 'oauth_request_failed', $res );
			return $res;
		}
		$code = wp_remote_retrieve_response_code( $res );
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( 200 !== $code || empty( $json['access_token'] ) ) {
			$error = new \WP_Error(
				'fedex_auth_failed',
				'FedEx auth failed',
				array(
					'status' => $code,
					'body'   => $json,
				)
			);
			$this->error( 'oauth_invalid_response', $error );
			return $error;
		}
		return $json['access_token'];
	}

	/**
	 * Build FedEx rate request body from shipment request.
	 *
	 * @param ShipmentRequest $request Request object to transform into FedEx payload.
	 * @return array
	 */
	protected function build_rate_request_payload( ShipmentRequest $request ): array {
		$origin      = $request->origin_location;
		$destination = $request->destination_location;
		$account     = $this->get_secret( 'account_number' );

		$weight_unit_wc = get_option( 'woocommerce_weight_unit' ) === 'lbs' ? 'LB' : 'KG';
		$dim_unit_wc    = ( get_option( 'woocommerce_dimension_unit' ) === 'in' ) ? 'IN' : 'CM';

		$line_items   = array();
		$total_weight = 0.0;
		foreach ( $request->packages as $p ) {
			$w = max( 0.01, (float) $p->total_weight ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning

			$total_weight += $w;

			$dim = $p->packed_dimensions; // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning

			$item = array( // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
				'subPackagingType'  => 'BOX',
				'groupPackageCount' => 1,
				'weight'            => array(
					'units' => $weight_unit_wc,
					'value' => round( $w, 2 ),
				),
			);
			if ( isset( $dim->length, $dim->width, $dim->height ) && $dim->length > 0 && $dim->width > 0 && $dim->height > 0 ) {
				$units              = $dim_unit_wc;
				$item['dimensions'] = array(
					'length' => max( 0.1, round( $dim->length, 2 ) ),
					'width'  => max( 0.1, round( $dim->width, 2 ) ),
					'height' => max( 0.1, round( $dim->height, 2 ) ),
					'units'  => $units,
				);
			}
			if ( ! empty( $this->get_setting( 'insuranceEnabled', false ) ) && $p->declared_value > 0 ) {
				$item['declaredValue'] = array(
					'amount'   => round( (float) $p->declared_value, 2 ),
					'currency' => $request->currency,
				);
			}
			$line_items[] = $item;
		}

		$domestic  = strtoupper( $origin['country'] ?? '' ) === strtoupper( $destination['country'] ?? '' );
		$rate_type = $this->get_setting( 'accountRatesOnly', false ) ? array( 'ACCOUNT' ) : array( 'LIST', 'ACCOUNT' );
		$pack_type = $this->compute_packaging_type( $total_weight, $weight_unit_wc, $domestic );
		$pickup    = $this->get_setting( 'pickupType', 'CONTACT_FEDEX_TO_SCHEDULE' );
		if ( empty( $pickup ) ) {
			$pickup = 'CONTACT_FEDEX_TO_SCHEDULE';
		}
		$special = $this->get_setting( 'specialServices', array() );
		$customs = $domestic ? array() : $this->build_customs_clearance( $line_items, strtoupper( $destination['country'] ?? '' ) );

		$payload = array(
			'accountNumber'                => array( 'value' => $account ),
			'rateRequestControlParameters' => array( 'returnTransitTimes' => (bool) $this->get_setting( 'returnTransitTimes', true ) ),
			'requestedShipment'            => array(
				'shipper'                   => array(
					'address' => array(
						// 'streetLines'         => array( $origin['address_1'] ?? '', $origin['address_2'] ?? '' ),
						// 'city'                => $origin['city'] ?? '',
						// 'stateOrProvinceCode' => substr( (string) ( $origin['state'] ?? '' ), 0, 2 ),
						'postalCode'  => $origin['postcode'] ?? '',
						'countryCode' => strtoupper( $origin['country'] ?? '' ),
						'residential' => $this->get_setting( 'isResidential', false ) ? true : false,
					),
				),
				'recipient'                 => array(
					'address' => array(
						// 'streetLines'         => array( $destination['address_1'] ?? '', $destination['address_2'] ?? '' ),
						// 'city'                => $destination['city'] ?? '',
						// 'stateOrProvinceCode' => substr( (string) ( $destination['state'] ?? '' ), 0, 2 ),
						'postalCode'  => $destination['postcode'] ?? '',
						'countryCode' => strtoupper( $destination['country'] ?? '' ),
						'residential' => $this->get_setting( 'isResidential', false ) ? true : false,
					),
				),
				'rateRequestType'           => $rate_type,
				'pickupType'                => $pickup,
				'requestedPackageLineItems' => $line_items,
				'packagingType'             => $pack_type,
				'totalPackageCount'         => count( $line_items ),
			),
		);

		if ( ! empty( $special ) ) {
			$payload['requestedShipment']['shipmentSpecialServices'] = $special;
		}
		if ( ! empty( $customs ) ) {
			$payload['requestedShipment']['customsClearanceDetail'] = $customs;
		}

		return $payload;
	}

	/**
	 * Determine packaging type, considering One Rate heuristics.
	 *
	 * @param float  $total_weight Total weight across packages.
	 * @param string $weight_unit  Weight unit (LB or KG).
	 * @param bool   $domestic     Whether the shipment is domestic.
	 * @return string
	 */
	private function compute_packaging_type( float $total_weight, string $weight_unit, bool $domestic ): string {
		$packaging = $this->get_setting( 'shipPackagingType', 'YOUR_PACKAGING' );
		if ( empty( $packaging ) ) {
			$packaging = 'YOUR_PACKAGING';
		}
		if ( $this->get_setting( 'sendPackAsShip', false ) ) {
			return $packaging;
		}
		if ( $domestic && $this->get_setting( 'oneRateEnabled', false ) ) {
			$lbs = ( 'KG' === $weight_unit ) ? $total_weight * 2.205 : $total_weight;
			if ( $lbs > 10 && $lbs <= 50 ) {
				$this->log( 'packaging_type_one_rate', 'Using FedEx One Rate medium box', array( 'weight_lbs' => $lbs ) );
				return 'FEDEX_MEDIUM_BOX';
			}
			$this->log( 'packaging_type_one_rate', 'Using FedEx One Rate small box', array( 'weight_lbs' => $lbs ) );
			return 'FEDEX_SMALL_BOX';
		}
		return $packaging;
	}

	/**
	 * Build customs details for international shipments.
	 *
	 * @param array  $line_items   Line items used to infer weights/values.
	 * @param string $dest_country Destination country code.
	 * @return array
	 */
	private function build_customs_clearance( array $line_items, string $dest_country ): array {
		$origin_country = strtoupper( get_option( 'woocommerce_default_country', '' ) );
		$origin_country = strtoupper( explode( ':', $origin_country )[0] ?? $origin_country );
		if ( strtoupper( $dest_country ) === $origin_country ) {
			return array();
		}
		$pay_type    = $this->get_setting( 'dutyPaymentType', 'SENDER' );
		$commodities = array();
		foreach ( $line_items as $i => $li ) {
			$commodities[ $i ] = array(
				'weight'               => $li['weight'],
				'quantity'             => 1,
				'customsValue'         => $li['declaredValue'] ?? array(
					'amount'   => 1,
					'currency' => get_woocommerce_currency(),
				),
				'unitPrice'            => $li['declaredValue'] ?? array(
					'amount'   => 1,
					'currency' => get_woocommerce_currency(),
				),
				'numberOfPieces'       => 1,
				'countryOfManufacture' => $origin_country,
				'quantityUnits'        => 'PACK',
			);
		}
		$payor = array(
			'responsibleParty' => array(
				'address'       => array( 'countryCode' => $origin_country ),
				'accountNumber' => array( 'value' => $this->get_secret( 'account_number' ) ),
			),
			'paymentType'      => $pay_type,
		);

		return array(
			'commercialInvoice' => array( 'shipmentPurpose' => 'SOLD' ),
			'dutiesPayment'     => array( 'payor' => $payor ),
			'commodities'       => $commodities,
		);
	}

	/**
	 * Perform a remote POST returning decoded JSON or WP_Error.
	 *
	 * @param string $url Endpoint.
	 * @param array  $body JSON-serializable body.
	 * @param string $token OAuth token.
	 * @return array|\WP_Error
	 */
	protected function remote_post_json( string $url, array $body, string $token ) {
		$http_args = array( // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
			'headers'   => array(
				'content-type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token,
				'User-Agent'    => 'WowShipping-Pro/' . ( defined( 'WTRS_VER' ) ? WTRS_VER : 'dev' ),
			),
			'timeout'   => 40,
			'sslverify' => false,
			'body'      => wp_json_encode( $body ),
		);
		$response  = wp_remote_post( $url, $http_args ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
		if ( is_wp_error( $response ) ) {
			$this->error( 'remote_post_failed', $response );
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code ) {
			$error = new \WP_Error(
				'http_error',
				'rate request failed',
				array(
					'status' => $code,
					'body'   => $body,
				)
			);
			$this->error( 'remote_post_error_response', $error );
			return $error;
		}
		return $body;
	}

	/**
	 * Parse FedEx API response into Rate objects.
	 *
	 * @param array           $data    Decoded JSON response.
	 * @param ShipmentRequest $request Original shipment request.
	 * @return array<Rate>
	 */
	protected function parse_rate_response( $data, ShipmentRequest $request ): array {
		$rates = array();

		if ( ! is_array( $data ) || empty( $data['output']['rateReplyDetails'] ) || ! is_array( $data['output']['rateReplyDetails'] ) ) {
			$this->warning( 'invalid_response', 'API response was not in the expected format.', array( 'response_data' => $data ) );
			return $rates;
		}

		$selected_service = $this->get_setting( 'service', 'wtrs_unknown' );
		$this->log(
			'service_filter',
			'Starting Service Filter',
			array(
				'service'           => $selected_service,
				'returned_services' => $data['output']['rateReplyDetails'],
			)
		);

		foreach ( $data['output']['rateReplyDetails'] as $detail ) {
			$service_code = $detail['serviceType'] ?? null;

			if ( $selected_service !== $service_code ) {
				continue;
			}

			$desired_rate_type = $this->get_setting( 'accountRatesOnly', false ) ? 'ACCOUNT' : 'LIST';
			$amount            = null;
			$currency          = $request->currency;

			if ( ! empty( $detail['ratedShipmentDetails'] ) && is_array( $detail['ratedShipmentDetails'] ) ) {
				// Prefer a specific rateType first.
				$ordered = array();
				foreach ( $detail['ratedShipmentDetails'] as $rsd ) {
					$ordered[ $rsd['rateType'] ?? 'UNKNOWN' ] = $rsd;
				}
				$candidates = array();
				if ( isset( $ordered[ $desired_rate_type ] ) ) {
					$candidates[] = $ordered[ $desired_rate_type ];
				}
				// Fallback preference.
				if ( 'ACCOUNT' !== $desired_rate_type && isset( $ordered['ACCOUNT'] ) ) {
					$candidates[] = $ordered['ACCOUNT'];
				}
				if ( 'LIST' !== $desired_rate_type && isset( $ordered['LIST'] ) ) {
					$candidates[] = $ordered['LIST'];
				}
				// As a last resort push all.
				if ( empty( $candidates ) ) {
					$candidates = array_values( $detail['ratedShipmentDetails'] );
				}

				foreach ( $candidates as $rsd ) {
					// Handle both numeric and object shapes of totalNetCharge.
					if ( isset( $rsd['totalNetCharge'] ) ) {
						if ( is_array( $rsd['totalNetCharge'] ) && isset( $rsd['totalNetCharge']['amount'] ) ) {
							$amount   = (float) $rsd['totalNetCharge']['amount'];
							$currency = $rsd['totalNetCharge']['currency'] ?? ( $rsd['currency'] ?? ( $rsd['shipmentRateDetail']['currency'] ?? $currency ) );
							break;
						} elseif ( is_numeric( $rsd['totalNetCharge'] ) ) {
							$amount   = (float) $rsd['totalNetCharge'];
							$currency = $rsd['currency'] ?? ( $rsd['shipmentRateDetail']['currency'] ?? $currency );
							break;
						}
					}
				}
			}

			if ( null === $amount ) {
				continue;
			}

			$cost = $this->convert_currency( $amount, $currency, $request->currency );

			if ( false === $cost ) {
				continue;
			}

			$raw_transit  = $detail['operationalDetail']['transitTime'] ?? ( $detail['commit']['transitTime'] ?? '' ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
			list( $delivery_time, $transit_days ) = $this->normalize_transit_time( $raw_transit );
			if ( empty( $delivery_time ) ) {
				list( $delivery_time, $transit_days ) = $this->derive_transit_time_from_dates( $detail, $data );
			}

			$rates[] = new Rate(
				$this->get_key(),
				$service_code,
				$this->humanize_service( $service_code ),
				$cost,
				$request->currency,
				$delivery_time,
				$transit_days,
				array(
					'carrier'  => 'fedex',
					'rateType' => $desired_rate_type,
				)
			);
		}

		return $rates;
	}

	/**
	 * Derive transit time from commit/delivery dates when transitTime is missing.
	 *
	 * @param array $detail Rate reply detail.
	 * @param array $data   Full response data.
	 * @return array{0:string,1:int|null}
	 */
	private function derive_transit_time_from_dates( array $detail, array $data ): array {
		$delivery_date = $detail['commit']['dateDetail']['dayFormat']
			?? $detail['operationalDetail']['deliveryDate']
			?? $detail['operationalDetail']['commitDate']
			?? '';
		$quote_date    = $data['output']['quoteDate'] ?? '';
		if ( empty( $delivery_date ) ) {
			return array( '', null );
		}

		try {
			$delivery_dt = new \DateTime( $delivery_date );
			$base_dt     = ! empty( $quote_date ) ? new \DateTime( $quote_date ) : new \DateTime( 'now', $delivery_dt->getTimezone() );
			$diff        = $base_dt->diff( $delivery_dt );
			$days        = (int) $diff->days;
			if ( $diff->invert ) {
				return array( '', null );
			}
			$label = $days . ( 1 === $days ? ' day' : ' days' );
			return array( $label, $days );
		} catch ( \Exception $e ) {
			return array( '', null );
		}
	}

	/**
	 * Convert FedEx transitTime tokens into a human label and numeric days.
	 *
	 * Examples: THREE_DAYS -> ["3 days", 3], ONE_TO_TWO_DAYS -> ["1–2 days", 2].
	 *
	 * @param string $raw Transit time token from API.
	 * @return array{0:string,1:int|null}
	 */
	private function normalize_transit_time( $raw ): array {
		if ( empty( $raw ) || ! is_string( $raw ) ) {
			return array( '', null );
		}

		$map = array(
			'ZERO_DAYS'  => 0,
			'ONE_DAY'    => 1,
			'TWO_DAYS'   => 2,
			'THREE_DAYS' => 3,
			'FOUR_DAYS'  => 4,
			'FIVE_DAYS'  => 5,
			'SIX_DAYS'   => 6,
			'SEVEN_DAYS' => 7,
			'EIGHT_DAYS' => 8,
			'NINE_DAYS'  => 9,
			'TEN_DAYS'   => 10,
		);

		// Exact match first.
		if ( isset( $map[ $raw ] ) ) {
			$n = $map[ $raw ];
			return array( $n . ( 1 === $n ? ' day' : ' days' ), $n );
		}

		// Range pattern: ONE_TO_TWO_DAYS, TWO_TO_THREE_DAYS, etc.
		if ( preg_match( '/^([A-Z]+)_TO_([A-Z]+)_DAYS$/', $raw, $m ) ) {
			$from = $this->word_to_int( $m[1] );
			$to   = $this->word_to_int( $m[2] );
			if ( null !== $from && null !== $to ) {
				$label = $from . '-' . $to . ' days';
				return array( $label, (int) $to );
			}
		}

		// Fallback: remove trailing _DAYS and map single word if possible.
		if ( preg_match( '/^([A-Z]+)_DAYS?$/', $raw, $m ) ) {
			$n = $this->word_to_int( $m[1] );
			if ( null !== $n ) {
				return array( $n . ( 1 === $n ? ' day' : ' days' ), (int) $n );
			}
		}

		// Unknown value; return raw in title case for visibility.
		return array( ucwords( strtolower( str_replace( '_', ' ', $raw ) ) ), null );
	}

	/**
	 * Convert number word (e.g., ONE, TWO) to integer.
	 *
	 * @param string $word Uppercase number word.
	 * @return int|null
	 */
	private function word_to_int( string $word ): ?int {
		$words = array(
			'ZERO'  => 0,
			'ONE'   => 1,
			'TWO'   => 2,
			'THREE' => 3,
			'FOUR'  => 4,
			'FIVE'  => 5,
			'SIX'   => 6,
			'SEVEN' => 7,
			'EIGHT' => 8,
			'NINE'  => 9,
			'TEN'   => 10,
		);
		return $words[ strtoupper( $word ) ] ?? null;
	}

	/**
	 * Human readable label from service code.
	 *
	 * @param string $code FedEx service type code.
	 * @return string
	 */
	protected function humanize_service( string $code ): string {
		return str_replace( array( '_', 'FEDEX' ), array( ' ', 'FedEx' ), ucwords( strtolower( $code ), '_' ) );
	}

	/**
	 * Validate credentials & settings, then persist them.
	 *
	 * @param array $credentials Credential inputs: api_key, api_secret, account_number.
	 * @return bool True if stored, false if required fields missing/invalid.
	 */
	public function validate( array $credentials ) {
		$required = array( 'api_key', 'api_secret', 'account_number' );
		$missing  = array();
		foreach ( $required as $field ) {
			if ( empty( $credentials[ $field ] ) ) {
				$missing[] = $field;
			}
		}
		if ( ! empty( $missing ) ) {
			$this->error( 'validation_failed', 'Missing required credential fields', array( 'fields' => $missing ) );
			return false;
		}
		// Try obtaining a token with provided credentials.
		$url  = self::TEST_AUTH_URL; // Use sandbox for validation.
		$body = array(
			'grant_type'    => 'client_credentials',
			'client_id'     => $credentials['api_key'],
			'client_secret' => $credentials['api_secret'],
		);
		$args = array(
			'timeout'   => 40,
			'sslverify' => false,
			'body'      => http_build_query( $body ),
			'headers'   => array( 'content-type' => 'application/x-www-form-urlencoded' ),
		);

		$is_valid = false;

		$attempts     = array();
		$success_url  = '';
		$request_fail = null;

		foreach ( array( self::TEST_AUTH_URL, self::LIVE_AUTH_URL ) as $url ) {
			$res = wp_remote_post( $url, $args ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning,Generic.WhiteSpace.ScopeIndent.Incorrect
			if ( is_wp_error( $res ) ) {
				$request_fail = $res;
				$attempts[]   = array(
					'url'   => $url,
					'error' => $res->get_error_message(),
				);
				break;
			}
			$code       = wp_remote_retrieve_response_code( $res );
			$json       = json_decode( wp_remote_retrieve_body( $res ), true );
			$attempts[] = array(
				'url'    => $url,
				'status' => $code,
			);
			$is_success = ( 200 === $code && ! empty( $json['access_token'] ) );
			if ( $is_success ) {
				$is_valid    = true;
				$success_url = $url;
				// Persist credentials on success.
				$this->credential_manager->bulk_set(
					array(
						'api_key'        => $credentials['api_key'],
						'api_secret'     => $credentials['api_secret'],
						'account_number' => $credentials['account_number'],
					)
				);
				break;
			}
		}

		if ( $request_fail instanceof \WP_Error ) {
			$this->error( 'validation_request_failed', $request_fail );
			return false;
		}

		if ( ! empty( $attempts ) ) {
			$this->warning( 'validation_auth_failed', 'Authentication failed', array( 'attempts' => $attempts ) );
		}
		$this->error( 'validation_failed_all', 'Credentials validation failed for all endpoints' );

		return $is_valid;
	}
}
