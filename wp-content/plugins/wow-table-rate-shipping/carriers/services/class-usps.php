<?php // phpcs:ignore
namespace WTRS\Carriers\Services;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WTRS\Carriers\AbstractCarrier;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Rate;

/**
 * USPS Carrier (OAuth JSON Rates)
 *
 * This implementation focuses only on the modern USPS OAuth JSON endpoints, intentionally
 * ignoring deprecated XML APIs. It follows the structural patterns of other carrier
 * classes (e.g. Sendle) to provide normalized Rate objects.
 */
final class Usps extends AbstractCarrier {

	private const LIVE_HOST = 'https://apis.usps.com';
	private const TEST_HOST = 'https://apis-tem.usps.com';

	private const EP_DOMESTIC_RATES    = '/prices/v3/total-rates/search';
	private const EP_INTL_RATES        = '/international-prices/v3/base-rates-list/search';
	private const EP_TOKEN             = '/oauth2/v3/token';
	private const EP_SERVICE_STANDARDS = '/service-standards/v3/standards'; // live.
	private const EP_SERVICE_ESTIMATES = '/service-standards/v3/estimates';  // sandbox.

	/** Token transient key */
	private const TOKEN_TRANSIENT = 'wtrs_usps_token';

	private const TOKEN_TTL = 5 * 60 * 60;

	/** Default weight & dimension fallback (imperial). */
	private const DEFAULT_LB = 1.0;

	/**
	 * Supported USPS service groups (domestic & international) mapped to canonical code patterns.
	 * Derived from legacy data-wf-auth-services.php. Codes are normalized (with dimensional variants kept).
	 */
	private const VALUE_DELIM = '#|#';

	/**
	 * Flat list of USPS service code entries (domestic first, then international).
	 * Each entry contains the code description as label and a compound value of
	 * <group_key>#|#<code_key> so the original grouping can be reconstructed.
	 */
	private const SERVICE_GROUPS = array(
		// Domestic - USPS Ground Advantage.
		array(
			'label' => 'USPS Ground Advantage Single-piece | Domestic',
			'value' => 'D_GROUND_ADVANTAGE#|#DUXP0XXXX',
		),
		array(
			'label' => 'USPS Ground Advantage  Cubic Soft Pack Tier 6 | Domestic',
			'value' => 'D_GROUND_ADVANTAGE#|#DUUP6XXXX',
		),
		array(
			'label' => 'USPS Ground Advantage Cubic Soft Pack Tier 2 | Domestic',
			'value' => 'D_GROUND_ADVANTAGE#|#DUUP2XXXX',
		),
		array(
			'label' => 'USPS Ground Advantage Cubic Soft Pack Tier 1 | Domestic',
			'value' => 'D_GROUND_ADVANTAGE#|#DUUP1XXXX',
		),
		array(
			'label' => 'USPS Ground Advantage Cubic Non-Soft Pack Tier 3 | Domestic',
			'value' => 'D_GROUND_ADVANTAGE#|#DUUP3XXXX',
		),
		array(
			'label' => 'USPS Ground Advantage Oversized | Domestic',
			'value' => 'D_GROUND_ADVANTAGE#|#DUXO0XXXX',
		),
		array(
			'label' => 'USPS Ground Advantage Dimensional Nonrectangular | Domestic',
			'value' => 'D_GROUND_ADVANTAGE#|#DUXR0XXXX_1',
		),
		array(
			'label' => 'USPS Ground Advantage Dimensional Rectangular | Domestic',
			'value' => 'D_GROUND_ADVANTAGE#|#DUXR0XXXX_2',
		),

		// Domestic - First Class Mail.
		array(
			'label' => 'First Class Mail Letter(Stamped) | Domestic',
			'value' => 'D_FIRST_CLASS_MAIL#|#DFXL0XXXX',
		),
		array(
			'label' => 'First Class Mail Letter(Metered) | Domestic',
			'value' => 'D_FIRST_CLASS_MAIL#|#DFLL0XXXX',
		),
		array(
			'label' => 'First Class Mail Large Envelopes(Flats) | Domestic',
			'value' => 'D_FIRST_CLASS_MAIL#|#DFXF0XXXX',
		),
		array(
			'label' => 'First Class Mail Postcards | Domestic',
			'value' => 'D_FIRST_CLASS_MAIL#|#DFXC0XXXX',
		),
		array(
			'label' => 'First Class Mail EDDM | Domestic',
			'value' => 'D_FIRST_CLASS_MAIL#|#DDXF0XXNS',
		),
		array(
			'label' => 'First Class Mail Semipostal Stamps | Domestic',
			'value' => 'D_FIRST_CLASS_MAIL#|#DFXXSXXXU',
		),

		// Domestic - Priority Mail Express.
		array(
			'label' => 'Priority Mail Express Flat Rate Envelope | Domestic',
			'value' => 'D_EXPRESS_MAIL#|#DEFE0XXXX',
		),
		array(
			'label' => 'Priority Mail Express Padded Flat Rate Envelope | Domestic',
			'value' => 'D_EXPRESS_MAIL#|#DEFE2XXXX',
		),
		array(
			'label' => 'Priority Mail Express Legal Flat Rate Envelope | Domestic',
			'value' => 'D_EXPRESS_MAIL#|#DEFE1XXXX',
		),
		array(
			'label' => 'Priority Mail Express Single-piece | Domestic',
			'value' => 'D_EXPRESS_MAIL#|#DEXX0XXXX',
		),
		array(
			'label' => 'Priority Mail Express Dimensional Rectangular | Domestic',
			'value' => 'D_EXPRESS_MAIL#|#DEXR0XXXX_2',
		),
		array(
			'label' => 'Priority Mail Express Dimensional Nonrectangular | Domestic',
			'value' => 'D_EXPRESS_MAIL#|#DEXR0XXXX_1',
		),

		// Domestic - Media Mail.
		array(
			'label' => 'Media Mail Basic | Domestic',
			'value' => 'D_MEDIA_MAIL#|#DMXX0XXXB',
		),
		array(
			'label' => 'Media Mail 5-digit | Domestic',
			'value' => 'D_MEDIA_MAIL#|#DMXX0XXX5',
		),
		array(
			'label' => 'Media Mail Large Envelopes & Parcels | Domestic',
			'value' => 'D_MEDIA_MAIL#|#DMXX0XXXU',
		),

		// Domestic - USPS Connect Regional.
		array(
			'label' => 'Parcel Select DNDC Single-piece | Domestic',
			'value' => 'D_USPS_CONNECT_REGIONAL#|#DVXP0XOCX',
		),
		array(
			'label' => 'Parcel Select DSCF SCF | Domestic',
			'value' => 'D_USPS_CONNECT_REGIONAL#|#DVXP0XOFX',
		),
		array(
			'label' => 'Parcel Select DHUB Single-piece | Domestic',
			'value' => 'D_USPS_CONNECT_REGIONAL#|#DVXP0XOBX',
		),
		array(
			'label' => 'Parcel Select DDU Single-piece | Domestic',
			'value' => 'D_USPS_CONNECT_REGIONAL#|#DVXP0XOUX',
		),
		array(
			'label' => 'Parcel Select DNDC Oversized | Domestic',
			'value' => 'D_USPS_CONNECT_REGIONAL#|#DVXO0XXCX',
		),
		array(
			'label' => 'Parcel Select DDU Oversized | Domestic',
			'value' => 'D_USPS_CONNECT_REGIONAL#|#DVXO0XXUX',
		),
		array(
			'label' => 'Parcel Select DSCF Oversized | Domestic',
			'value' => 'D_USPS_CONNECT_REGIONAL#|#DVXO0XXFX',
		),
		array(
			'label' => 'Parcel Select DHUB Oversized | Domestic',
			'value' => 'D_USPS_CONNECT_REGIONAL#|#DVXO0XXBX',
		),
		array(
			'label' => 'Parcel Select DDU Single-piece (Duplicate Mapping) | Domestic',
			'value' => 'D_USPS_CONNECT_REGIONAL#|#DVXP0XXUX',
		),

		// Domestic - USPS Connect Mail.
		array(
			'label' => 'Connect Local Mail | Domestic',
			'value' => 'D_USPS_CONNECT_MAIL#|#DFOFXXXUX',
		),

		// Domestic - USPS Connect Local.
		array(
			'label' => 'Connect Local DDU Small Flat Rate Bag | Domestic',
			'value' => 'D_USPS_CONNECT_LOCAL#|#DVOA0XXXX',
		),
		array(
			'label' => 'Connect Local DDU Large Flat Rate Bag | Domestic',
			'value' => 'D_USPS_CONNECT_LOCAL#|#DVOA1XXXX',
		),
		array(
			'label' => 'Connect Local DDU Flat Rate Box | Domestic',
			'value' => 'D_USPS_CONNECT_LOCAL#|#DVOB0XXXX',
		),
		array(
			'label' => 'Connect Local DDU | Domestic',
			'value' => 'D_USPS_CONNECT_LOCAL#|#DVGXXXXUX',
		),
		array(
			'label' => 'Connect Local DDU Oversized | Domestic',
			'value' => 'D_USPS_CONNECT_LOCAL#|#DVGO0XXUX',
		),

		// Domestic - Bound Printed Matter.
		array(
			'label' => 'Bound Printed Matter DNDC Presorted | Domestic',
			'value' => 'D_BOUND_PRINTED_MATTER#|#DBPP0XXCX',
		),
		array(
			'label' => 'Bound Printed Matter DDU Presorted | Domestic',
			'value' => 'D_BOUND_PRINTED_MATTER#|#DBPP0XXUX',
		),
		array(
			'label' => 'Bound Printed Matter Presorted | Domestic',
			'value' => 'D_BOUND_PRINTED_MATTER#|#DBPP0XXNX',
		),
		array(
			'label' => 'Bound Printed Matter DSCF Presorted | Domestic',
			'value' => 'D_BOUND_PRINTED_MATTER#|#DBPP0XXFX',
		),

		// Domestic - Library Mail.
		array(
			'label' => 'Library Mail Basic | Domestic',
			'value' => 'D_LIBRARY_MAIL#|#DLXX0XXXB',
		),
		array(
			'label' => 'Library Mail 5-digit | Domestic',
			'value' => 'D_LIBRARY_MAIL#|#DLXX0XXX5',
		),
		array(
			'label' => 'Library Mail Large Envelopes & Parcels | Domestic',
			'value' => 'D_LIBRARY_MAIL#|#DLXX0XXXU',
		),

		// Domestic - Priority Mail.
		array(
			'label' => 'Priority Mail Padded Flat Rate Envelope | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPFE2XXXX',
		),
		array(
			'label' => 'Priority Mail Flat Rate Envelope | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPFE0XXXX',
		),
		array(
			'label' => 'Priority Mail Legal Flat Rate Envelope | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPFE1XXXX',
		),
		array(
			'label' => 'Priority Mail Medium Flat Rate Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPFB1XXXX',
		),
		array(
			'label' => 'Priority Mail Large Flat Rate Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPFB0XXXX',
		),
		array(
			'label' => 'Priority Mail Single-piece | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPXX0XXXX',
		),
		array(
			'label' => 'Priority Mail Small Flat Rate Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPFB2XXXX',
		),
		array(
			'label' => 'Priority Mail APO/FPO/DPO Flat Rate Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPFB3XXXX',
		),
		array(
			'label' => 'Priority Mail Cubic Soft Pack Tier 1 | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPUX1XXXX',
		),
		array(
			'label' => 'Priority Mail Cubic Non-Soft Pack Tier 3 | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPUX3XXXX',
		),
		array(
			'label' => 'Priority Mail Dimensional Rectangular | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPXR0XXXX_2',
		),
		array(
			'label' => 'Priority Mail Dimensional Nonrectangular | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DPXR0XXXX_1',
		),
		array(
			'label' => 'PMOD ADC Single-piece | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DOXX0XXXX',
		),
		array(
			'label' => 'PMOD ADC Full Tray Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DOXI0XXXX',
		),
		array(
			'label' => 'PMOD DDU Full Tray Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DOXI0XXUX',
		),
		array(
			'label' => 'PMOD ADC Half Tray Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DOXH0XXXX',
		),
		array(
			'label' => 'PMOD DDU Half Tray Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DOXH0XXUX',
		),
		array(
			'label' => 'PMOD ADC Extended Managed Mail Tray Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DOXJ0XXXX',
		),
		array(
			'label' => 'PMOD DDU Extended Managed Mail Tray Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DOXJ0XXUX',
		),
		array(
			'label' => 'PMOD ADC Flat Tub Tray Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DOXK0XXXX',
		),
		array(
			'label' => 'PMOD DDU Flat Tub Tray Box | Domestic',
			'value' => 'D_PRIORITY_MAIL#|#DOXK0XXUX',
		),

		// International - Priority Mail Express International.
		array(
			'label' => 'Priority Mail Express ISC Single-piece | International',
			'value' => 'I_EXPRESS_MAIL#|#IEXX0XXXX',
		),
		array(
			'label' => 'Priority Mail Express ISC Flat Rate Envelope | International',
			'value' => 'I_EXPRESS_MAIL#|#IEFE0XXXX',
		),
		array(
			'label' => 'Priority Mail Express ISC Legal Flat Rate Envelope | International',
			'value' => 'I_EXPRESS_MAIL#|#IEFE1XXXX',
		),
		array(
			'label' => 'Priority Mail Express ISC Padded Flat Rate Envelope | International',
			'value' => 'I_EXPRESS_MAIL#|#IEFE2XXXX',
		),

		// International - Priority Mail International.
		array(
			'label' => 'Priority Mail ISC Single-piece | International',
			'value' => 'I_PRIORITY_MAIL#|#IPXX0XXXX',
		),
		array(
			'label' => 'Priority Mail ISC Flat Rate Envelope | International',
			'value' => 'I_PRIORITY_MAIL#|#IPFE0XXXX',
		),
		array(
			'label' => 'Priority Mail ISC Large Flat Rate Box | International',
			'value' => 'I_PRIORITY_MAIL#|#IPFB0XXXX',
		),
		array(
			'label' => 'Priority Mail ISC Medium Flat Rate Box | International',
			'value' => 'I_PRIORITY_MAIL#|#IPFB1XXXX',
		),
		array(
			'label' => 'Priority Mail ISC Padded Flat Rate Envelope | International',
			'value' => 'I_PRIORITY_MAIL#|#IPFE2XXXX',
		),
		array(
			'label' => 'Priority Mail ISC Legal Flat Rate Envelope | International',
			'value' => 'I_PRIORITY_MAIL#|#IPFE1XXXX',
		),
		array(
			'label' => 'Priority Mail ISC Small Flat Rate Box | International',
			'value' => 'I_PRIORITY_MAIL#|#IPFB2XXXX',
		),
		array(
			'label' => 'Priority Mail ISC DVD Flat Rate Box | International',
			'value' => 'I_PRIORITY_MAIL#|#IPFB4XXXX',
		),
		array(
			'label' => 'Priority Mail ISC Large Video Flat Rate Box | International',
			'value' => 'I_PRIORITY_MAIL#|#IPFB5XXXX',
		),

		// International - First Class Package International.
		array(
			'label' => 'First-Class Package Service ISC Single-piece | International',
			'value' => 'I_FIRST_CLASS_PACKAGE#|#IFXP0XXXX',
		),
	);

	private const EXTRA_SERVICES = array(
		array(
			'value' => '415',
			'label' => 'USPS Label Delivery Service',
		),
		array(
			'value' => '480',
			'label' => 'Tracking Plus 6 Months',
		),
		array(
			'value' => '481',
			'label' => 'Tracking Plus 1 Year',
		),
		array(
			'value' => '482',
			'label' => 'Tracking Plus 3 Years',
		),
		array(
			'value' => '483',
			'label' => 'Tracking Plus 5 Years',
		),
		array(
			'value' => '484',
			'label' => 'Tracking Plus 7 Years',
		),
		array(
			'value' => '485',
			'label' => 'Tracking Plus 10 Years',
		),
		array(
			'value' => '486',
			'label' => 'Tracking Plus Signature 3 Years',
		),
		array(
			'value' => '487',
			'label' => 'Tracking Plus Signature 5 Years',
		),
		array(
			'value' => '488',
			'label' => 'Tracking Plus Signature 7 Years',
		),
		array(
			'value' => '489',
			'label' => 'Tracking Plus Signature 10 Years',
		),
		array(
			'value' => '498',
			'label' => 'PO Box Locker – Stocking Fee (NSA Only)',
		),
		array(
			'value' => '500',
			'label' => 'PO Box Locker – Self-Service Pickup Fee (NSA Only)',
		),
		array(
			'value' => '501',
			'label' => 'PO Box Locker – Clerk-Assisted Pickup Fee (NSA Only)',
		),
		array(
			'value' => '502',
			'label' => 'PO Box Locker – Local Delivery Fee (NSA Only)',
		),
		array(
			'value' => '810',
			'label' => 'Hazardous Materials - Air Eligible Ethanol',
		),
		array(
			'value' => '811',
			'label' => 'Hazardous Materials - Class 1 – Toy Propellant/Safety Fuse Package',
		),
		array(
			'value' => '812',
			'label' => 'Hazardous Materials - Class 3 - Flammable and Combustible Liquids',
		),
		array(
			'value' => '813',
			'label' => 'Hazardous Materials - Class 7 – Radioactive Materials',
		),
		array(
			'value' => '814',
			'label' => 'Hazardous Materials - Class 8 – Air Eligible Corrosive Materials',
		),
		array(
			'value' => '815',
			'label' => 'Hazardous Materials - Class 8 – Nonspillable Wet Batteries',
		),
		array(
			'value' => '816',
			'label' => 'Hazardous Materials - Class 9 - Lithium Battery Marked Ground Only',
		),
		array(
			'value' => '817',
			'label' => 'Hazardous Materials - Class 9 - Lithium Battery Returns',
		),
		array(
			'value' => '818',
			'label' => 'Hazardous Materials - Class 9 - Marked Lithium Batteries',
		),
		array(
			'value' => '819',
			'label' => 'Hazardous Materials - Class 9 – Dry Ice',
		),
		array(
			'value' => '820',
			'label' => 'Hazardous Materials - Class 9 – Unmarked Lithium Batteries',
		),
		array(
			'value' => '821',
			'label' => 'Hazardous Materials - Class 9 – Magnetized Materials',
		),
		array(
			'value' => '822',
			'label' => 'Hazardous Materials - Division 4.1 – Mailable Flammable Solids and Safety Matches',
		),
		array(
			'value' => '823',
			'label' => 'Hazardous Materials - Division 5.1 – Oxidizers',
		),
		array(
			'value' => '824',
			'label' => 'Hazardous Materials - Division 5.2 – Organic Peroxides',
		),
		array(
			'value' => '825',
			'label' => 'Hazardous Materials - Division 6.1 – Toxic Materials',
		),
		array(
			'value' => '826',
			'label' => 'Hazardous Materials - Division 6.2 Biological Materials',
		),
		array(
			'value' => '827',
			'label' => 'Hazardous Materials - Excepted Quantity Provision',
		),
		array(
			'value' => '828',
			'label' => 'Hazardous Materials - Ground Only Hazardous Materials',
		),
		array(
			'value' => '829',
			'label' => 'Hazardous Materials - Air Eligible ID8000 Consumer Commodity',
		),
		array(
			'value' => '830',
			'label' => 'Hazardous Materials - Lighters',
		),
		array(
			'value' => '831',
			'label' => 'Hazardous Materials - Limited Quantity Ground',
		),
		array(
			'value' => '832',
			'label' => 'Hazardous Materials - Small Quantity Provision (Markings Required)',
		),
		array(
			'value' => '853',
			'label' => 'Special Handling - Perishable Material',
		),
		array(
			'value' => '856',
			'label' => 'Live Animal Transportation Fee',
		),
		array(
			'value' => '857',
			'label' => 'Hazardous Materials',
		),
		array(
			'value' => '858',
			'label' => 'Cremated Remains',
		),
		array(
			'value' => '910',
			'label' => 'Certified Mail',
		),
		array(
			'value' => '911',
			'label' => 'Certified Mail Restricted Delivery',
		),
		array(
			'value' => '912',
			'label' => 'Certified Mail Adult Signature Required',
		),
		array(
			'value' => '913',
			'label' => 'Certified Mail Adult Signature Restricted Delivery',
		),
		array(
			'value' => '915',
			'label' => 'Collect on Delivery',
		),
		array(
			'value' => '917',
			'label' => 'Collect on Delivery Restricted Delivery',
		),
		array(
			'value' => '920',
			'label' => 'USPS Tracking Electronic',
		),
		array(
			'value' => '921',
			'label' => 'Signature Confirmation',
		),
		array(
			'value' => '922',
			'label' => 'Adult Signature Required',
		),
		array(
			'value' => '923',
			'label' => 'Adult Signature Restricted Delivery',
		),
		array(
			'value' => '924',
			'label' => 'Signature Confirmation Restricted Delivery',
		),
		array(
			'value' => '925',
			'label' => 'Priority Mail Express Merchandise Insurance',
		),
		array(
			'value' => '930',
			'label' => 'Insurance <= $500',
		),
		array(
			'value' => '931',
			'label' => 'Insurance > $500',
		),
		array(
			'value' => '934',
			'label' => 'Insurance Restricted Delivery',
		),
		array(
			'value' => '940',
			'label' => 'Registered Mail',
		),
		array(
			'value' => '941',
			'label' => 'Registered Mail Restricted Delivery',
		),
		array(
			'value' => '955',
			'label' => 'Return Receipt',
		),
		array(
			'value' => '957',
			'label' => 'Return Receipt Electronic',
		),
		array(
			'value' => '972',
			'label' => 'Live Animal and Perishable Handling Fee',
		),
		array(
			'value' => '981',
			'label' => 'Signature Requested (PRIORITY_MAIL_EXPRESS only)',
		),
		array(
			'value' => '984',
			'label' => 'Parcel Locker Delivery',
		),
		array(
			'value' => '986',
			'label' => 'PO to Addressee (PRIORITY_MAIL_EXPRESS only)',
		),
		array(
			'value' => '991',
			'label' => 'Sunday Delivery',
		),
	);

	/**
	 * Supported price types.
	 */
	private const PRICE_TYPES = array(
		array(
			'label' => 'Commercial',
			'value' => 'COMMERCIAL',
		),
		array(
			'label' => 'Retail',
			'value' => 'RETAIL',
		),
	);

	/*
	 ***************************************************
	 * Required interface methods
	 ***************************************************
	*/

	/**
	 * Unique carrier key.
	 *
	 * @return string
	 */
	public static function get_key(): string {
		return 'usps';
	}

	/**
	 * Human readable label.
	 *
	 * @return string
	 */
	public static function get_label(): string {
		return 'USPS';
	}

	/**
	 * Supported packers.
	 *
	 * @return array
	 */
	public static function get_supported_packers(): array {
		return array( 'single_package', 'per_item', 'box', 'weight' );
	}

	/**
	 * Return structured values for UI contexts.
	 *
	 * @param string $type    Type key.
	 * @param string $context Context (view/edit).
	 * @return mixed
	 */
	public static function get_values( $type, $context = 'view' ) {
		if ( 'supported_packers' === $type ) {
			return self::add_packer_labels( self::get_supported_packers() );
		}
		if ( 'services' === $type ) {
			return self::SERVICE_GROUPS;
		}

		if ( 'priceTypes' === $type ) {
			return self::PRICE_TYPES;
		}

		if ( 'extraServices' === $type ) {
			return self::EXTRA_SERVICES;
		}

		return array();
	}

	/**
	 * Parse settings, applying defaults & validation.
	 *
	 * @param array $values Raw settings.
	 * @return array
	 */
	protected static function parse_settings( $values ) {
		$parsed = array();

		$parsed['mode']          = in_array( $values['mode'] ?? 'sandbox', array( 'sandbox', 'live' ), true ) ? $values['mode'] : 'sandbox';
		$parsed['priceType']     = in_array( $values['priceType'] ?? 'COMMERCIAL', array( 'COMMERCIAL', 'RETAIL' ), true ) ? $values['priceType'] : 'COMMERCIAL';
		$parsed['enableEta']     = ! empty( $values['enableEta'] );
		$parsed['extraServices'] = array();

		if ( isset( $values['extraServices'] ) && is_array( $values['extraServices'] ) ) {
			foreach ( $values['extraServices'] as $svc ) {
				$parsed['extraServices'][] = intval( trim( $svc ) );
			}
		}

		if ( isset( $values['service'] ) && is_string( $values['service'] ) ) {
			$parsed['service'] = trim( $values['service'] );
		} else {
			$parsed['service'] = '';
		}

		return array_merge( parent::parse_settings( $values ), $parsed );
	}

	/** Determine environment. */
	protected function is_live(): bool {
		return ( $this->settings['mode'] ?? 'sandbox' ) === 'live';
	}

	/** Base host. */
	private function host(): string {
		return $this->is_live() ? self::LIVE_HOST : self::TEST_HOST;
	}

	/*
	 ***************************************************
	 * Public API
	 ***************************************************
	*/
	/**
	 * Fetch normalized USPS rates.
	 *
	 * @param ShipmentRequest $request Shipment abstraction.
	 * @return array<Rate>|false
	 */
	public function fetch_rates( ShipmentRequest $request ) {
		$this->log( 'fetch_rates_started', 'Starting USPS rate request' );

		// Sanity: credentials.
		if ( ! $this->get_secret( 'client_id' ) || ! $this->get_secret( 'client_secret' ) ) {
			$this->error( 'missing_credentials', __( 'USPS credentials not set.', 'table-rate-shipping-pro' ) );
			return false;
		}

		$domestic = $request->origin_location['country'] === $request->destination_location['country'];

		$this->log( 'shipment_type', 'Determined shipment type', array( 'domestic' => $domestic ) );

		$packages = $this->map_packages( $request );
		if ( empty( $packages ) ) {
			$this->warning( 'no_packages', 'No packages to rate' );
			return array();
		}

		$token = $this->get_token();
		if ( ! $token ) {
			$this->error( 'auth_failed', __( 'Unable to authenticate with USPS API.', 'table-rate-shipping-pro' ) );
			return false;
		}

		$this->log( 'token_obtained', 'USPS access token obtained successfully' );

		$endpoint = $domestic ? self::EP_DOMESTIC_RATES : self::EP_INTL_RATES;

		$this->log( 'api_endpoint', 'Using USPS endpoint', array( 'endpoint' => $endpoint ) );

		$responses = array();
		foreach ( $packages as $pkg ) {
			$payload = $this->build_rate_payload( $pkg, $request, $domestic );
			$payload = apply_filters( 'wtrs_usps_rate_payload', $payload, $pkg, $request, $this );

			$this->log( 'sending_request', 'Sending rate request to USPS API' );

			$response = $this->remote_json( 'POST', $this->host() . $endpoint, $payload, $token );
			if ( is_wp_error( $response ) ) {
				$this->error( 'api_error', $response );
				return false;
			}
			$responses[] = array(
				'body'    => $response,
				'package' => $pkg,
			);
		}

		$this->log( 'USPS Rate Responses', 'Received responses from USPS', array( 'response_count' => count( $responses ) ) );

		$eta_map = array();
		if ( $domestic && ! empty( $this->settings['enableEta'] ) ) {
			$this->log( 'fetching_eta', 'Fetching service standards (ETA)' );
			$eta_map = $this->fetch_service_standards( $request, $packages, $token );
			$this->log( 'USPS ETA API', 'Received ETA data', $eta_map );
		}

		$rates = $this->aggregate_responses( $responses, $eta_map, $request, $domestic );

		if ( false === $rates ) {
			$this->warning( 'aggregation_failed', 'Failed to aggregate USPS rates' );
			return false;
		}
		if ( empty( $rates ) ) {
			$this->warning( 'no_rates_returned', 'No rates returned from USPS' );
			return array();
		}

		$this->log( 'rates_fetched', 'USPS rates fetched successfully', array( 'final_count' => count( $rates ) ) );

		return apply_filters( 'wtrs_carrier_filter_normalized_rates', $rates, $this->get_key(), $request );
	}

	/*
	 ***************************************************
	 * Token Handling
	 ***************************************************
	*/
	/**
	 * Retrieve cached token or request a new one.
	 *
	 * @return string|null
	 */
	private function get_token(): ?string {
		$cached = get_transient( self::TOKEN_TRANSIENT );
		if ( $cached ) {
			$this->log( 'token_from_cache', 'Using cached USPS access token' );
			return $cached;
		}
		$this->log( 'requesting_token', 'Requesting new USPS access token' );
		return $this->request_token();
	}

	/**
	 * Request a fresh OAuth token from USPS.
	 *
	 * @return string|null
	 */
	private function request_token(): ?string {
		$body     = array(
			'client_id'     => $this->get_secret( 'client_id' ),
			'client_secret' => $this->get_secret( 'client_secret' ),
			'grant_type'    => 'client_credentials',
		);
		$response = $this->remote_json( 'POST', $this->host() . self::EP_TOKEN, $body, null, true );
		if ( is_wp_error( $response ) ) {
			$this->error( 'token_request_failed', $response );
			return null;
		}
		if ( ( $response['status'] ?? '' ) === 'approved' && ! empty( $response['access_token'] ) ) {
			$this->log( 'token_received', 'USPS access token received successfully' );
			set_transient( self::TOKEN_TRANSIENT, $response['access_token'], self::TOKEN_TTL );
			return $response['access_token'];
		}
		$error = new \WP_Error( 'token_status_error', 'Token request not approved', $response );
		$this->error( 'token_status_error', $error );
		return null;
	}

	/*
	 ***************************************************
	 * Package Mapping & Payload
	 ***************************************************
	*/
	/**
	 * Convert shipment packages to simplified USPS package arrays.
	 *
	 * @param ShipmentRequest $request Shipment request.
	 * @return array
	 */
	private function map_packages( ShipmentRequest $request ): array {
		$out = array();
		foreach ( $request->packages as $idx => $pkg ) {
			// Raw values coming from packing algorithm (assumed stored in base WooCommerce units: weight in kg, dimensions in cm).
			$raw_weight_kg = max( 0.0, (float) $pkg->total_weight );
			$raw_len_cm    = (float) $pkg->packed_dimensions->length;
			$raw_wid_cm    = (float) $pkg->packed_dimensions->width;
			$raw_hgt_cm    = (float) $pkg->packed_dimensions->height;

			// Use WooCommerce helpers to convert to required USPS imperial units (lbs/inches).
			// wc_get_weight( $weight, $to_unit, $from_unit = null )
			// wc_get_dimension( $dimension, $to_unit, $from_unit = null ) converts a single dimension.
			$weight_lb = function_exists( 'wc_get_weight' ) ? (float) wc_get_weight( $raw_weight_kg, 'lbs', 'kg' ) : ( $raw_weight_kg / 0.45359237 );
			if ( $weight_lb <= 0 ) {
				$weight_lb = self::DEFAULT_LB;
			}

			// Convert each dimension to inches, floor later per original logic.
			if ( function_exists( 'wc_get_dimension' ) ) {
				$length_in = (float) wc_get_dimension( $raw_len_cm, 'in', 'cm' );
				$width_in  = (float) wc_get_dimension( $raw_wid_cm, 'in', 'cm' );
				$height_in = (float) wc_get_dimension( $raw_hgt_cm, 'in', 'cm' );
			} else {
				// Fallback constant conversion if WC helpers unavailable.
				$length_in = $raw_len_cm / 2.54;
				$width_in  = $raw_wid_cm / 2.54;
				$height_in = $raw_hgt_cm / 2.54;
			}

			$out[] = array(
				'index'  => $idx,
				'weight' => (float) $weight_lb,
				'length' => (int) floor( max( 0, $length_in ) ),
				'width'  => (int) floor( max( 0, $width_in ) ),
				'height' => (int) floor( max( 0, $height_in ) ),
				'qty'    => 1, // Each package is aggregated already by packer; adapt if multi-qty needed.
			);
		}
		return $out;
	}

	/**
	 * Build USPS rate payload for a single package.
	 *
	 * @param array           $pkg Package array.
	 * @param ShipmentRequest $request Shipment request.
	 * @param bool            $domestic Is domestic shipment.
	 * @return array
	 */
	private function build_rate_payload( array $pkg, ShipmentRequest $request, bool $domestic ): array {
		$origin       = $request->origin_location;
		$destination  = $request->destination_location;
		$mailing_date = gmdate( 'Y-m-d' ); // Could mirror existing cut-off + working day logic if exposed later.

		if ( $domestic ) {
			return array(
				'originZIPCode'      => strtoupper( substr( preg_replace( '/\s+/', '', $origin['postcode'] ), 0, 5 ) ),
				'destinationZIPCode' => strtoupper( substr( preg_replace( '/\s+/', '', $destination['postcode'] ), 0, 5 ) ),
				'weight'             => $pkg['weight'],
				'length'             => $pkg['length'],
				'width'              => $pkg['width'],
				'height'             => $pkg['height'],
				'mailClass'          => 'ALL',
				'priceType'          => $this->settings['priceType'] ?? 'COMMERCIAL',
				'mailingDate'        => $mailing_date,
				'extraServices'      => $this->get_setting( 'extraServices', array() ),
			);
		}
		return array(
			'originZIPCode'          => strtoupper( substr( preg_replace( '/\s+/', '', $origin['postcode'] ), 0, 5 ) ),
			'foreignPostalCode'      => strtoupper( substr( preg_replace( '/\s+/', '', $destination['postcode'] ), 0, 5 ) ),
			'destinationCountryCode' => strtoupper( $destination['country'] ),
			'mailingDate'            => $mailing_date,
			'mailClass'              => 'ALL',
			'weight'                 => $pkg['weight'],
			'length'                 => $pkg['length'],
			'width'                  => $pkg['width'],
			'height'                 => $pkg['height'],
			'extraServices'          => $this->get_setting( 'extraServices', array() ),
		);
	}

	/**
	 * Fetch service standards (ETA) for domestic shipments.
	 *
	 * @param ShipmentRequest $request Shipment request.
	 * @param array           $packages Packages array.
	 * @param string          $token OAuth token.
	 * @return array Map of mailClass => ISO date.
	 */
	private function fetch_service_standards( ShipmentRequest $request, array $packages, string $token ): array {
		$total_weight = array_sum(
			array_map(
				function ( $p ) {
					return $p['weight'];
				},
				$packages
			)
		);
		$origin       = $request->origin_location;
		$dest         = $request->destination_location;
		$accept       = gmdate( 'Y-m-d' );

		$query = http_build_query(
			array(
				'originZIPCode'                => strtoupper( substr( preg_replace( '/\s+/', '', $origin['postcode'] ), 0, 5 ) ),
				'destinationZIPCode'           => strtoupper( substr( preg_replace( '/\s+/', '', $dest['postcode'] ), 0, 5 ) ),
				'acceptanceDate'               => $accept,
				'mailClass'                    => 'ALL',
				'destinationType'              => 'STREET',
				'destinationEntryFacilityType' => 'NONE',
				'weight'                       => $total_weight,
			)
		);

		$path = $this->is_live() ? self::EP_SERVICE_STANDARDS : self::EP_SERVICE_ESTIMATES;
		$url  = $this->host() . $path . '?' . $query;

		$response = $this->remote_json( 'GET', $url, array(), $token );
		if ( is_wp_error( $response ) || empty( $response ) ) {
			return array();
		}
		$map = array();
		foreach ( (array) $response as $row ) {
			if ( isset( $row['mailClass'], $row['delivery']['scheduledDeliveryDateTime'] ) ) {
				$map[ $row['mailClass'] ] = $row['delivery']['scheduledDeliveryDateTime'];
			}
		}
		return apply_filters( 'wtrs_usps_eta_map', $map, $request, $this );
	}

	/**
	 * Aggregate multiple package responses into a single Rate object.
	 *
	 * @param array           $responses Response list.
	 * @param array           $eta_map ETA map.
	 * @param ShipmentRequest $request Shipment request.
	 * @param bool            $domestic Domestic flag.
	 * @return array|false Array with one Rate on success, false if no match.
	 */
	private function aggregate_responses( array $responses, array $eta_map, ShipmentRequest $request, bool $domestic ) {

		$selected     = $this->settings['service'] ?? '';
		$target_code  = '';
		$target_label = '';

		if ( $selected && strpos( $selected, self::VALUE_DELIM ) !== false ) {
			list( $group_key_sel, $code_key_sel ) = explode( self::VALUE_DELIM, $selected );
			$target_code = $code_key_sel; // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
			// Look up label from SERVICE_GROUPS constant.
			foreach ( self::SERVICE_GROUPS as $entry ) {
				if ( isset( $entry['value'] ) && $entry['value'] === $selected ) {
					$target_label = $entry['label'];
					break;
				}
			}
		}

		$package_count     = count( $responses );
		$matched_prices    = array(); // index => price.
		$matched_classes   = array(); // Collect mailClasses for ETA analysis.
		$first_description = '';

		foreach ( $responses as $resp ) {
			$body  = $resp['body'];
			$pkg   = $resp['package'];
			$index = $pkg['index'];
			if ( empty( $body['rateOptions'] ) || ! is_array( $body['rateOptions'] ) ) {
				continue;
			}

			$matched_for_package = false;
			foreach ( $body['rateOptions'] as $opt ) {
				$rate_line = $opt['rates'][0] ?? null;
				if ( ! is_array( $rate_line ) ) {
					continue;
				}
				$sku            = substr( (string) ( $rate_line['SKU'] ?? '' ), 0, 9 );
				$description    = (string) ( $rate_line['description'] ?? '' );
				$norm_code      = $this->normalize_sku_code( $sku, $description );
				$price          = isset( $rate_line['price'] ) ? (float) $rate_line['price'] : null;
				$mail_class     = $rate_line['mailClass'] ?? null;
				$extra_services = $this->get_setting( 'extraServices', array() );

				if ( null === $price ) {
					continue;
				}

				if ( $norm_code === $target_code ) {

					if ( ! empty( $extra_services ) && isset( $opt['extraServices'] ) && is_array( $opt['extraServices'] ) ) {
						foreach ( $opt['extraServices'] as $extra ) {
							if ( in_array( (int) ( $extra['extraService'] ?? 0 ), $extra_services, true ) ) {
								if ( isset( $extra['price'] ) ) {
									$price += (float) $extra['price'];
								}
							}
						}
					}

					$matched_prices[ $index ] = $price;
					if ( $mail_class ) {
						$matched_classes[ $mail_class ] = true;
					}
					if ( ! $first_description && $description ) {
						$first_description = $description;
					}
					$matched_for_package = true;
					break; // Take first match for this package.
				}
			}
			if ( ! $matched_for_package ) {
				// A required package had no matching rate; abort.
				return false;
			}
		}

		if ( count( $matched_prices ) !== $package_count ) {
			return false;
		}

		$total_cost = array_sum( $matched_prices );
		if ( $target_label ) {
			$label = $target_label;
		} elseif ( $first_description ) {
			$label = $first_description;
		} else {
			$label = 'USPS Shipping';
		}

		// Determine maximum transit days among matched mailClasses.
		$delivery_txt = null;
		$transit_days = null;
		if ( ! empty( $eta_map ) && ! empty( $matched_classes ) ) {
			$max_days = 0;
			foreach ( array_keys( $matched_classes ) as $mc ) {
				if ( ! isset( $eta_map[ $mc ] ) ) {
					continue;
				}
				try { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					$target = new \DateTime( $eta_map[ $mc ] );
					$now    = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
					$diff   = (int) ceil( ( $target->getTimestamp() - $now->getTimestamp() ) / 86400 );
					if ( $diff > $max_days ) {
						$max_days = $diff;
					}
				} catch ( \Exception $e ) { } // phpcs:ignore
			}
			if ( $max_days > 0 ) {
				// translators: %d is number of business days in delivery estimate (maximum across packages).
				$delivery_txt = sprintf( _n( '%d business day', '%d business days', $max_days, 'table-rate-shipping-pro' ), $max_days );
				$transit_days = $max_days;
			}
		}

		$currency  = $request->currency;
		$converted = $this->convert_currency( $total_cost, 'USD', $currency );
		if ( false === $converted ) {
			return false;
		}

		$service_key = $target_code ? $target_code : 'aggregate';
		$rate        = new Rate(
			$this->get_key(),
			$service_key,
			$label,
			$converted,
			$currency,
			$delivery_txt,
			$transit_days
		);

		return array( $rate );
	}

	/**
	 * Normalize USPS SKU into internal code variant (adds dimensional suffixes or maps special cases).
	 *
	 * @param string $sku Raw SKU from API.
	 * @param string $description Rate description (used to disambiguate dimensional variants).
	 * @return string Normalized code key used for mapping.
	 */
	private function normalize_sku_code( string $sku, string $description ): string {
		$base = $sku;
		if ( in_array( $sku, array( 'DUXR0XXXX', 'DPXR0XXXX', 'DEXR0XXXX' ), true ) ) {
			$base .= ( stripos( $description, 'Nonrectangular' ) !== false ) ? '_1' : '_2';
		}
		if ( 'DUXP0XXXU' === $sku ) {
			$base = 'DUXP0XXXX';
		}
		return $base;
	}

	/**
	 * Perform remote HTTP JSON request.
	 *
	 * @param string      $method HTTP method.
	 * @param string      $url Target URL.
	 * @param array       $body Request body (assoc array) prior to json_encode.
	 * @param string|null $token Bearer token if any.
	 * @param bool        $send_body Whether to attach body for non-GET methods.
	 * @return array|WP_Error Decoded JSON array or WP_Error on failure.
	 */
	private function remote_json( string $method, string $url, array $body, ?string $token, bool $send_body = true ) {
		$args = array(
			'method'      => $method,
			'timeout'     => 40,
			'headers'     => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
				'User-Agent'   => 'WowShipping/' . ( defined( 'WTRS_VER' ) ? WTRS_VER : 'dev' ),
			),
			'sslverify'   => false, // follow existing pattern; can be made configurable.
			'data_format' => 'body',
		);
		if ( $token ) {
			$args['headers']['Authorization'] = 'Bearer ' . $token;
		}
		if ( 'GET' !== $method && $send_body && ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$dec  = json_decode( $raw, true );
		if ( 200 !== $code ) {
			return new WP_Error(
				'http_error',
				'USPS HTTP error',
				array(
					'status' => $code,
					'body'   => $dec,
				)
			);
		}
		return is_array( $dec ) ? $dec : array();
	}

	/**
	 * Validate and persist USPS OAuth credentials.
	 *
	 * Attempts to obtain a token using the provided client_id and client_secret.
	 * On success, credentials are stored via the credential manager and the
	 * retrieved token cached (transient) for subsequent requests.
	 *
	 * @param array $credentials { client_id:string, client_secret:string }.
	 * @return bool True if credentials are valid.
	 */
	public function validate( array $credentials ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$required = array( 'client_id', 'client_secret' );
		foreach ( $required as $field ) {
			if ( empty( $credentials[ $field ] ) ) {
				return false;
			}
		}

		$body = array(
			'client_id'     => $credentials['client_id'],
			'client_secret' => $credentials['client_secret'],
			'response_type' => 'code',
			'scope'         => 'prices labels tracking payments international-prices international-labels carrier-pickup standards service-standards',
			'grant_type'    => 'client_credentials',
		);

		$response = $this->remote_json( 'POST', $this->host() . self::EP_TOKEN, $body, null, true );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		if ( ( $response['status'] ?? '' ) === 'approved' && ! empty( $response['access_token'] ) ) {
			// Persist credentials.
			if ( $this->credential_manager ) {
				$this->credential_manager->bulk_set(
					array(
						'client_id'     => $credentials['client_id'],
						'client_secret' => $credentials['client_secret'],
					)
				);
			}
			// Cache token immediately for subsequent rate calls.
			set_transient( self::TOKEN_TRANSIENT, $response['access_token'], self::TOKEN_TTL );
			return true;
		}

		return false;
	}
}
