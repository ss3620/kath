<?php
/**
 * Main class for User Agent Parser.
 *
 * @package     affiliate-for-woocommerce/includes/lib/
 * @since       8.37.0
 * @version     1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_User_Agent_Parser' ) ) {

	/**
	 * Main class for User Agent Parser.
	 */
	class AFWC_User_Agent_Parser {

		/**
		 * Parse user agent string and return browser, OS, and device type
		 * Optimized single-pass parsing with minimal array operations
		 *
		 * @param string $user_agent The user agent string.
		 * @return array Array with 'browser', 'os', and 'device_type' keys
		 */
		public static function parse( $user_agent ) {
			if ( empty( $user_agent ) ) {
				return array(
					'browser'     => 'Unknown',
					'os'          => 'Unknown',
					'device_type' => 'desktop',
				);
			}

			// Initialize result with defaults.
			$browser     = 'Unknown';
			$os          = 'Unknown';
			$device_type = 'desktop';
			$is_mobile   = false;

			// Identify platform/OS in user-agent string.
			if ( preg_match(
				'/(?P<platform>'                                      // Capture subpattern matches into 'platform' array.
				. 'Windows Phone( OS)?|Symbian|SymbOS|Android|iPhone' // Platform tokens.
				. '|iPad|Windows|Linux|Macintosh|FreeBSD|OpenBSD'     // More platform tokens.
				. '|SunOS|RIM Tablet OS|PlayBook'                     // More platform tokens.
				. '|BlackBerry|BB10|Fire OS|Mac OS X|SymbianOS'       // NEW More platform tokens.
				. ')'
				. '(?:'
				. ' (NT|amd64|armv7l|zvav)'                           // Possibly followed by specific modifiers/specifiers.
				. ')*'
				. '(?:'
				. ' [ix]?[0-9._]+'                                    // Possibly followed by architecture modifier (e.g. x86_64).
				. '(\-[0-9a-z\.\-]+)?'                                // Possibly followed by a hypenated version number.
				. ')*'
				. '(;|\))'                                            // Ending in a semi-colon or close parenthesis.
				. '/i',                                               // Case insensitive.
				$user_agent,
				$os_match
			) ) {
				$os = $os_match['platform'];

				// Normalize OS names inline.
				if ( 'Windows Phone' === $os ) {
					$os = 'Windows Phone OS';
				} elseif ( 'SymbOS' === $os || 'SymbianOS' === $os ) {
					$os = 'Symbian';
				} elseif ( 'Macintosh' === $os || 'Mac OS X' === $os ) {
					$os = 'macOS';
				} elseif ( 'iPhone' === $os ) {
					$os        = 'iOS';
					$is_mobile = true;
				}
			}

			// Handle Android/Kindle special case.
			if ( 'Linux' === $os && strpos( $user_agent, 'Android' ) !== false ) {
				if ( strpos( $user_agent, 'Kindle' ) !== false ) {
					$os = 'Fire OS';
				} else {
					$os = 'Android';
				}
			}

			// Detect generic mobile devices if no OS found.
			if ( 'Unknown' === $os && preg_match( '/BlackBerry|Nokia|SonyEricsson/i', $user_agent, $mobile_match ) ) {
				$os        = 'Mobile';
				$is_mobile = true;
			}

			// Identify browser in user-agent string.
			preg_match_all(
				'%(?P<name>'                                          // Capture subpattern matches into the 'name' array.
				. 'Opera Mini|Opera|OPR|Edge|UCBrowser|UCWEB'         // Browser tokens.
				. '|Edg|Brave'                                        // More browser tokens.
				. '|QQBrowser|SymbianOS|Symbian|S40OviBrowser'        // More browser tokens.
				. '|Trident|Silk|Konqueror|PaleMoon|Puffin'           // More browser tokens.
				. '|SeaMonkey|Vivaldi|Camino|Chromium|Kindle|Firefox' // More browser tokens.
				. '|SamsungBrowser|(?:Mobile )?Safari|NokiaBrowser'   // More browser tokens.
				. '|MSIE|RockMelt|AppleWebKit|Chrome|IEMobile'        // More browser tokens.
				. ')'
				. '%i',                                               // Case insensitive.
				$user_agent,
				$browser_matches,
				PREG_PATTERN_ORDER
			);

			// Fast browser detection using direct string matching.
			if ( is_array( $browser_matches ) && ! empty( $browser_matches['name'] ) ) {
				$browsers_list = array_flip( array_reverse( $browser_matches['name'] ) );

				// Explicit browser detection (priority order matters).
				if ( isset( $browsers_list['Opera Mini'] ) ) {
					$browser   = 'Opera Mini';
					$is_mobile = true;
				} elseif ( isset( $browsers_list['Opera'] ) || isset( $browsers_list['OPR'] ) ) {
					$browser = 'Opera';
				} elseif ( isset( $browsers_list['Edge'] ) || isset( $browsers_list['Edg'] ) ) {
					$browser = 'Microsoft Edge';
				} elseif ( isset( $browsers_list['Brave'] ) ) {
					$browser = 'Brave';
				} elseif ( isset( $browsers_list['UCBrowser'] ) || isset( $browsers_list['UCWEB'] ) ) {
					$browser = 'UC Browser';
				} elseif ( isset( $browsers_list['QQBrowser'] ) ) {
					$browser = 'QQ Browser';
				} elseif ( isset( $browsers_list['SamsungBrowser'] ) ) {
					$browser = 'Samsung Browser';
				} elseif ( isset( $browsers_list['Silk'] ) ) {
					$browser   = 'Amazon Silk';
					$is_mobile = true;
				} elseif ( isset( $browsers_list['NokiaBrowser'] ) ) {
					$browser   = 'Nokia Browser';
					$is_mobile = true;
				} elseif ( isset( $browsers_list['Kindle'] ) ) {
					$browser = 'Kindle Browser';
				} elseif ( isset( $browsers_list['Vivaldi'] ) ) {
					$browser = 'Vivaldi';
				} elseif ( isset( $browsers_list['PaleMoon'] ) ) {
					$browser = 'Pale Moon';
				} elseif ( isset( $browsers_list['SeaMonkey'] ) ) {
					$browser = 'SeaMonkey';
				} elseif ( isset( $browsers_list['Camino'] ) ) {
					$browser = 'Camino';
				} elseif ( isset( $browsers_list['Chromium'] ) ) {
					$browser = 'Chromium';
				} elseif ( isset( $browsers_list['RockMelt'] ) ) {
					$browser = 'RockMelt';
				} elseif ( isset( $browsers_list['IEMobile'] ) ) {
					$browser   = 'Internet Explorer Mobile';
					$is_mobile = true;
				} elseif ( isset( $browsers_list['S40OviBrowser'] ) ) {
					$browser   = 'Ovi Browser';
					$is_mobile = true;
				} elseif ( isset( $browsers_list['Puffin'] ) ) {
					$browser   = 'Puffin';
					$is_mobile = true;
				} elseif ( isset( $browsers_list['Trident'] ) ) {
					$browser = 'Internet Explorer';
				} elseif ( isset( $browsers_list['MSIE'] ) ) {
					$browser = 'Internet Explorer';
				} elseif ( isset( $browsers_list['AppleWebKit'] ) ) {
					// WebKit browser detection logic (from original).
					if ( isset( $browsers_list['Mobile Safari'] ) ) {
						if ( isset( $browsers_list['Chrome'] ) ) {
							$browser = 'Chrome';
						} elseif ( 'Android' === $os ) {
							$browser = 'Android Browser';
						} elseif ( 'Fire OS' === $os ) {
							$browser = 'Kindle Browser';
						} elseif ( strpos( $user_agent, 'BlackBerry' ) !== false || strpos( $user_agent, 'BB10' ) !== false ) {
							$browser   = 'BlackBerry Browser';
							$is_mobile = true;
						} else {
							$browser = 'Mobile Safari';
						}
					} elseif ( isset( $browsers_list['Chrome'] ) ) {
						$browser = 'Chrome';
					} elseif ( 'PlayBook' === $os ) {
						$browser = 'PlayBook Browser';
					} elseif ( isset( $browsers_list['Safari'] ) ) {
						if ( 'Android' === $os ) {
							$browser = 'Android Browser';
						} elseif ( 'Symbian' === $os ) {
							$browser = 'Nokia Browser';
						} else {
							$browser = 'Safari';
						}
					}
				} elseif ( isset( $browsers_list['Firefox'] ) ) {
					$browser = 'Firefox';
				} else {
					// Fallback to first token.
					$browsers_names = array_keys( $browsers_list );
					$browser        = reset( $browsers_names );
				}
			} elseif ( strpos( $user_agent, 'BlackBerry' ) !== false ) {
				$browser = 'BlackBerry Browser';
			}

			// Tablet detection first (more specific).
			if ( preg_match( '/iPad|PlayBook|RIM Tablet OS|Tablet|SM-T|GT-P|KFAPWI|KFARWI|KFASWI|KFJWI|KFMEWI/i', $user_agent ) ) {
				$device_type = 'tablet';
			} elseif ( preg_match( '/^(iPad|PlayBook|RIM Tablet OS)$/i', $os ) ) {
				// Platform-based tablet detection.
				$device_type = 'tablet';
			} elseif ( $is_mobile || preg_match( '/Mobile|Android.*Mobile|iPhone|iPod|BlackBerry|BB10|Windows Phone|Opera Mini|UC Browser/i', $user_agent ) ) {
				// Mobile detection.
				$device_type = 'mobile';
			} elseif ( preg_match( '/^(Android|Fire OS|iOS|Symbian|Windows Phone OS)$/i', $os ) ) {
				// Platform-based mobile detection.
				$device_type = 'mobile';
			}

			// Handle Amazon devices.
			if ( strpos( $browser, 'Amazon Silk' ) !== false || strpos( $browser, 'Kindle' ) !== false ) {
				$os          = 'Fire OS';
				$device_type = ( 'desktop' === $device_type ) ? 'mobile' : $device_type;
			}

			$response = array(
				'browser'     => $browser,
				'os'          => $os,
				'device_type' => $device_type,
			);

			return $response;
		}
	}
}
