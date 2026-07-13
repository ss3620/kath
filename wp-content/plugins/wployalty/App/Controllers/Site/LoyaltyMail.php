<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wlr\App\Controllers\Site;

use Wlr\App\Controllers\Base;
use Wlr\App\Emails\WlrBirthdayEmail;
use Wlr\App\Emails\WlrEarnPointEmail;
use Wlr\App\Emails\WlrEarnRewardEmail;
use Wlr\App\Emails\WlrExpireEmail;
use Wlr\App\Emails\WlrNewLevelEmail;
use Wlr\App\Emails\WlrPointExpireEmail;

defined( 'ABSPATH' ) or die;

class LoyaltyMail extends Base {
	public static function initNotification() {
		add_filter( 'woocommerce_email_classes', [ self::class, 'addEmailClass' ] );
		add_filter( 'woocommerce_template_directory', function ( $template_dir, $template ) {
			if ( in_array( $template, [
				'emails/wlr-earn-point.php',
				'emails/plain/wlr-earn-point.php',
				'emails/wlr-earn-reward.php',
				'emails/plain/wlr-earn-reward.php',
				'emails/wlr-expire-email.php',
				'emails/plain/wlr-expire-email.php',
				'emails/wlr-birthday-email.php',
				'emails/plain/wlr-birthday-email.php',
				'emails/wlr-new-level-email.php',
				'emails/plain/wlr-new-level-email.php',
				'emails/wlr-point-expire-email.php',
				'emails/plain/wlr-point-expire-email.php'
			] ) ) {
				return 'wployalty';
			}

			return $template_dir;
		}, 10, 2 );
		add_action( 'woocommerce_email_settings_after', [ self::class, 'displayShortCodes' ], 100 );
	}

	public static function addEmailClass( $emails ) {
		require_once plugin_dir_path( WC_PLUGIN_FILE ) . 'includes/emails/class-wc-email.php';
		if ( class_exists( 'Wlr\App\Emails\WlrEarnPointEmail' ) ) {
			$emails['WlrEarnPointEmail'] = new WlrEarnPointEmail();
		}
		if ( class_exists( 'Wlr\App\Emails\WlrEarnRewardEmail' ) ) {
			$emails['WlrEarnRewardEmail'] = new WlrEarnRewardEmail();
		}
		if ( class_exists( 'Wlr\App\Emails\WlrExpireEmail' ) ) {
			$emails['WlrExpireEmail'] = new WlrExpireEmail();
		}
		if ( class_exists( 'Wlr\App\Emails\WlrBirthdayEmail' ) ) {
			$emails['WlrBirthdayEmail'] = new WlrBirthdayEmail();
		}

		if ( class_exists( 'Wlr\App\Emails\WlrNewLevelEmail' ) ) {
			$emails['WlrNewLevelEmail'] = new WlrNewLevelEmail();
		}

		if ( class_exists( 'Wlr\App\Emails\WlrPointExpireEmail' ) ) {
			$emails['WlrPointExpireEmail'] = new WlrPointExpireEmail();
		}

		return $emails;
	}

	public static function displayShortCodes( $email ) {
		if ( is_object( $email ) && isset( $email->id ) && in_array( $email->id, apply_filters("wlr_allowed_email_ids", [
				'wlr_earn_point_email',
				'wlr_birthday_email',
				'wlr_earn_reward_email',
				'wlr_expire_email',
				'wlr_new_level_email',
				'wlr_point_expire_email',
			] )) && method_exists( $email, 'getShortCodesList' ) ) {
			$short_codes = $email->getShortCodesList();
			?>
            <h3><?php esc_html_e( 'Short Codes', 'wp-loyalty-rules' ) ?></h3>
            <p><?php esc_html_e( 'Short codes are used to customize the email content.', 'wp-loyalty-rules' ) ?></p>
            <div class="wlr-short-code-lists">
                <div>
                    <table class="wlr-shortcodes-table">
                        <thead>
                        <tr align="left">
                            <th><?php esc_html_e( 'Short Code', 'wp-loyalty-rules' ) ?></th>
                            <th><?php esc_html_e( 'Description', 'wp-loyalty-rules' ) ?></th>
                        </tr>
                        </thead>
                        <tbody>
						<?php foreach ( $short_codes as $index => $short_code ) { ?>
                            <tr class="row">
                                <td class="code">
                                    <div class="wlr-shortcode-container">
                                        <code class="wlr-shortcode-text"
                                              id="shortcode-<?php echo esc_attr( $index ); ?>"><?php echo esc_html( $short_code['short_code'] ); ?></code>
                                        <button type="button" class="button button-secondary wlr-copy-shortcode"
                                                data-shortcode="<?php echo esc_attr( $short_code['short_code'] ); ?>"
                                                data-target="shortcode-<?php echo esc_attr( $index ); ?>"
                                                title="<?php esc_attr_e( 'Copy to clipboard', 'wp-loyalty-rules' ); ?>">
                                            <span class="dashicons dashicons-admin-page"></span>
                                            <span class="copy-text"><?php esc_html_e( 'Copy', 'wp-loyalty-rules' ); ?></span>
                                        </button>
                                    </div>
                                </td>
                                <td class="desc"><?php echo esc_html( $short_code['description'] ); ?></td>
                            </tr>
						<?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- CSS Styles -->
                <style>
                    .wlr-short-code-lists {
                        background: white;
                        border: 1px solid #e1e5e9;
                        border-radius: 6px;
                        overflow: hidden;
                        margin: 20px 0;
                    }

                    .wlr-short-code-lists h3 {
                        margin: 0;
                        padding: 16px 20px;
                        background: #f8f9fa;
                        border-bottom: 1px solid #e1e5e9;
                        font-size: 16px;
                        font-weight: 600;
                        color: #1f2937;
                    }

                    .wlr-short-code-lists p {
                        margin: 0;
                        padding: 12px 20px;
                        color: #6b7280;
                        font-size: 14px;
                        border-bottom: 1px solid #e1e5e9;
                        background: #fafbfc;
                    }

                    .wlr-shortcodes-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 0;
                        background: white;
                    }

                    .wlr-shortcodes-table th {
                        padding: 16px 20px;
                        text-align: left;
                        border-bottom: 1px solid #e1e5e9;
                        background-color: #f8f9fa;
                        font-weight: 600;
                        font-size: 14px;
                        color: #374151;
                        text-transform: uppercase;
                        letter-spacing: 0.025em;
                        font-size: 12px;
                    }

                    .wlr-shortcodes-table td {
                        padding: 16px 20px;
                        border-bottom: 1px solid #f3f4f6;
                        vertical-align: middle;
                    }

                    .wlr-shortcodes-table tr:last-child td {
                        border-bottom: none;
                    }

                    .wlr-shortcodes-table tr:hover {
                        background-color: #f9fafb;
                    }

                    .wlr-shortcode-container {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                    }

                    .wlr-shortcode-text {
                        background-color: #f3f4f6;
                        color: #374151;
                        padding: 8px 12px;
                        border-radius: 6px;
                        font-family: 'SFMono-Regular', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
                        border: 1px solid #d1d5db;
                        font-size: 13px;
                        font-weight: 500;
                        margin: 0;
                        min-width: 140px;
                        letter-spacing: 0.025em;
                    }

                    .wlr-copy-shortcode {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        gap: 6px;
                        padding: 8px 12px;
                        background: #3b82f6;
                        color: white;
                        border: 1px solid #3b82f6;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 12px;
                        font-weight: 500;
                        line-height: 14px;
                        transition: all 0.2s ease;
                        text-decoration: none;
                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                        min-height: 32px;
                        vertical-align: top;
                    }

                    .wlr-copy-shortcode:hover {
                        background-color: #2563eb;
                        border-color: #2563eb;
                        box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
                        transform: translateY(-1px);
                    }

                    .wlr-copy-shortcode:active {
                        transform: translateY(0);
                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                    }

                    .wlr-copy-shortcode.copied {
                        background-color: #10b981;
                        border-color: #10b981;
                        color: white;
                    }

                    .wlr-copy-shortcode.copied:hover {
                        background-color: #059669;
                        border-color: #059669;
                    }

                    .wlr-copy-shortcode .dashicons {
                        font-size: 14px;
                        width: 14px;
                        height: 14px;
                        line-height: 14px;
                        margin: 0;
                        vertical-align: middle;
                        display: inline-block;
                    }

                    .wlr-copy-shortcode .copy-text {
                        font-size: 12px;
                        font-weight: 500;
                        line-height: 14px;
                        margin: 0;
                        vertical-align: middle;
                        display: inline-block;
                    }

                    .wlr-shortcodes-table td.desc {
                        color: #6b7280;
                        font-size: 14px;
                        line-height: 1.5;
                    }

                    .wlr-copy-feedback {
                        position: fixed;
                        top: 32px;
                        right: 32px;
                        background-color: #10b981;
                        color: white;
                        padding: 12px 16px;
                        border-radius: 8px;
                        font-size: 14px;
                        font-weight: 500;
                        z-index: 9999;
                        opacity: 0;
                        transition: all 0.3s ease;
                        pointer-events: none;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    }

                    .wlr-copy-feedback.show {
                        opacity: 1;
                        transform: translateY(0);
                    }

                    @media (max-width: 768px) {
                        .wlr-shortcode-container {
                            flex-direction: column;
                            align-items: flex-start;
                            gap: 8px;
                        }

                        .wlr-shortcode-text {
                            min-width: auto;
                            width: 100%;
                        }

                        .wlr-shortcodes-table th,
                        .wlr-shortcodes-table td {
                            padding: 12px 16px;
                        }

                        .wlr-short-code-lists h3 {
                            padding: 12px 16px;
                            font-size: 15px;
                        }

                        .wlr-short-code-lists p {
                            padding: 10px 16px;
                            font-size: 13px;
                        }
                    }
                </style>

                <!-- JavaScript for copy functionality -->
                <script>
                    jQuery(document).ready(function ($) {
                        // Add feedback element to body if it doesn't exist
                        if (!$('.wlr-copy-feedback').length) {
                            $('body').append('<div class="wlr-copy-feedback"></div>');
                        }

                        // Copy shortcode functionality
                        $('.wlr-copy-shortcode').on('click', function (e) {
                            e.preventDefault();

                            var button = $(this);
                            var shortcode = button.data('shortcode');
                            var copyText = button.find('.copy-text');
                            var originalText = copyText.text();

                            // Try to copy to clipboard
                            if (navigator.clipboard && window.isSecureContext) {
                                // Modern async clipboard API
                                navigator.clipboard.writeText(shortcode).then(function () {
                                    showCopySuccess(button, copyText, originalText);
                                }).catch(function (err) {
                                    fallbackCopy(shortcode, button, copyText, originalText);
                                });
                            } else {
                                // Fallback for older browsers
                                fallbackCopy(shortcode, button, copyText, originalText);
                            }
                        });

                        function showCopySuccess(button, copyText, originalText) {
                            // Update button state
                            button.addClass('copied');
                            copyText.text('<?php esc_html_e( 'Copied!', 'wp-loyalty-rules' ); ?>');

                            // Show feedback notification
                            var feedback = $('.wlr-copy-feedback');
                            feedback.text('<?php esc_html_e( 'Short code copied to clipboard!', 'wp-loyalty-rules' ); ?>').addClass('show');

                            // Reset button after 2 seconds
                            setTimeout(function () {
                                button.removeClass('copied');
                                copyText.text(originalText);
                                feedback.removeClass('show');
                            }, 2000);
                        }

                        function fallbackCopy(text, button, copyText, originalText) {
                            // Create temporary textarea for older browsers
                            var textArea = document.createElement('textarea');
                            textArea.value = text;
                            textArea.style.position = 'fixed';
                            textArea.style.left = '-999999px';
                            textArea.style.top = '-999999px';
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();

                            try {
                                document.execCommand('copy');
                                showCopySuccess(button, copyText, originalText);
                            } catch (err) {
                                // If copy fails, show error feedback
                                var feedback = $('.wlr-copy-feedback');
                                feedback.text('<?php esc_html_e( 'Copy failed. Please copy manually.', 'wp-loyalty-rules' ); ?>').addClass('show');
                                feedback.css('background-color', '#dc3232');

                                setTimeout(function () {
                                    feedback.removeClass('show');
                                    feedback.css('background-color', '#00a32a');
                                }, 3000);
                            } finally {
                                document.body.removeChild(textArea);
                            }
                        }
                    });
                </script>
            </div>
			<?php
		}
	}
}