<?php
/**
 * Main class for plugin's system status report.
 *
 * @package    affiliate-for-woocommerce/includes/admin/
 * @since      8.10.0
 * @version    1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_System_Status_Report' ) ) {

	/**
	 * Class to handle plugin's system status report.
	 */
	class AFWC_System_Status_Report {

		/**
		 * Singleton instance of AFWC_System_Status_Report.
		 *
		 * @var AFWC_System_Status_Report|null
		 */
		private static $instance = null;

		/**
		 * Get the singleton instance of this class
		 *
		 * @return AFWC_System_Status_Report Singleton instance of this class
		 */
		public static function get_instance() {
			// Check if instance already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			add_action( 'woocommerce_system_status_report', array( $this, 'add_system_status_section' ) );
		}

		/**
		 * Method to add system status section.
		 *
		 * @return void
		 */
		public function add_system_status_section() {
			?>
			<table class="wc_status_table widefat afwc-status-table" cellspacing="0">
				<thead>
					<tr>
						<th colspan="3" data-export-label="Affiliate For WooCommerce">
							<h2>
								<?php echo esc_html_x( 'Affiliate For WooCommerce', 'section title at system status report', 'affiliate-for-woocommerce' ); ?>
								<?php echo wp_kses_post( wc_help_tip( esc_html_x( 'This section shows any information about Affiliate For WooCommerce.', 'section description tooltip at system status report', 'affiliate-for-woocommerce' ) ) ); ?>
							</h2>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$this->render_template_override_row();
					$this->render_database_version_row();
					$this->render_database_upgrade_status_row();
					?>
				</tbody>
			</table>
			<?php
		}

		/**
		 * Method to render template override row.
		 *
		 * @return void
		 */
		public function render_template_override_row() {
			?>
			<tr>
				<td data-export-label="Template overrides">
					<?php echo esc_html_x( 'Template overrides', 'title for template override list at system status report', 'affiliate-for-woocommerce' ); ?>
				</td>
				<td class="help">&nbsp;</td>
				<td>
					<?php
					$template_overrides_info = $this->get_template_overrides_info();
					if ( ! empty( $template_overrides_info['overrides'] ) && is_array( $template_overrides_info['overrides'] ) ) {
						$total_overrides = count( $template_overrides_info['overrides'] );
						for ( $i = 0; $i < $total_overrides; $i++ ) {
							$override = $template_overrides_info['overrides'][ $i ];
							if ( $override['core_version'] && ( empty( $override['version'] ) || version_compare( $override['version'], $override['core_version'], '<' ) ) ) {
								$current_version = $override['version'] ? $override['version'] : '-';
								printf(
									/* translators: %1$s: Template name, %2$s: Template version, %3$s: Core version. */
									esc_html_x( '%1$s version %2$s is out of date. The core version is %3$s', 'notice text to display outdated template versions with latest version', 'affiliate-for-woocommerce' ),
									'<code>' . esc_html( $override['file'] ) . '</code>',
									'<strong style="color:red">' . esc_html( $current_version ) . '</strong>',
									esc_html( $override['core_version'] )
								);
							} else {
								echo esc_html( $override['file'] );
							}

							if ( ( $total_overrides - 1 ) !== $i ) {
								echo ', ';
							}
							echo '<br />';
						}

						if ( true === $template_overrides_info['has_outdated_templates'] ) {
							echo "\n";
							echo '<br />';
							echo '<mark class="error"><span class="dashicons dashicons-warning"></span></mark>';
							echo '<a href="https://woocommerce.com/document/fix-outdated-templates-woocommerce/" target="_blank">' . esc_html_x( 'Learn how to update outdated templates', 'link text for template update documentation', 'affiliate-for-woocommerce' ) . '</a>';
						}
					} else {
						echo '&ndash;';
					}
					?>
				</td>
			</tr>
			<?php
		}

		/**
		 * Method to get template overrides info.
		 *
		 * @return array
		 */
		public function get_template_overrides_info() {
			$scan_files = is_callable( array( 'WC_Admin_Status', 'scan_template_files' ) ) ? WC_Admin_Status::scan_template_files( AFWC_PLUGIN_DIRPATH . '/templates/' ) : array();
			if ( empty( $scan_files ) || ! is_array( $scan_files ) ) {
				return array(
					'has_outdated_templates' => false,
					'overrides'              => array(),
				);
			}

			global $affiliate_for_woocommerce;

			$override_files     = array();
			$outdated_templates = false;

			// Get the template migration map for backward compatibility.
			$migration_map = is_callable( array( $affiliate_for_woocommerce, 'get_template_migration_map' ) ) ? $affiliate_for_woocommerce->get_template_migration_map() : array();

			// Check in the theme directory for all AFW templates to see if active theme overrides any of them.
			foreach ( $scan_files as $file ) {
				$file       = str_replace( DIRECTORY_SEPARATOR, '/', $file ); // Always convert separator to `/` to match it with static path defined in migration map.
				$theme_file = false;

				if ( is_callable( array( $affiliate_for_woocommerce, 'locate_template_override' ) ) ) {
					// First check for the the old template path (if defined in the migration map).
					if ( ! empty( $migration_map[ $file ] ) ) {
						$theme_file = $affiliate_for_woocommerce->locate_template_override( $migration_map[ $file ], 'template' );
					}

					// Check for current template path.
					if ( empty( $theme_file ) ) {
						$theme_file = $affiliate_for_woocommerce->locate_template_override( $file, 'template' );
					}
				}

				if ( empty( $theme_file ) ) {
					continue;
				}

				$core_file = $file;

				$core_template_version  = '';
				$theme_template_version = '';
				if ( is_callable( array( 'WC_Admin_Status', 'get_file_version' ) ) ) {
					$core_template_version  = WC_Admin_Status::get_file_version( AFWC_PLUGIN_DIRPATH . '/templates/' . $core_file );
					$theme_template_version = WC_Admin_Status::get_file_version( $theme_file );
				}

				if ( strpos( $file, 'affiliate-reports.php' ) !== false ) {
					$core_template_version  = trim( str_replace( ':', '', $core_template_version ) );
					$theme_template_version = trim( str_replace( ':', '', $theme_template_version ) );
				}

				if ( $core_template_version && ( empty( $theme_template_version ) || version_compare( $theme_template_version, $core_template_version, '<' ) ) ) {
					if ( ! $outdated_templates ) {
						$outdated_templates = true;
					}
				}

				$override_files[] = array(
					'file'         => str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ),
					'version'      => $theme_template_version,
					'core_version' => $core_template_version,
				);
			}

			return array(
				'has_outdated_templates' => $outdated_templates,
				'overrides'              => $override_files,
			);
		}

		/**
		 * Method to render database version row.
		 *
		 * @return void
		 */
		public function render_database_version_row() {
			?>
			<tr>
				<td data-export-label="Database version">
					<?php echo esc_html_x( 'Database version', 'title for database version at system status report', 'affiliate-for-woocommerce' ); ?>
				</td>
				<td class="help">&nbsp;</td>
				<td>
					<?php echo esc_html( get_option( '_afwc_current_db_version' ) ); ?>
				</td>
			</tr>
			<?php
		}

		/**
		 * Method to render database upgrade status row.
		 *
		 * @return void
		 */
		public function render_database_upgrade_status_row() {
			?>
			<tr>
				<td data-export-label="Database upgrade status">
					<?php echo esc_html_x( 'Database upgrade status', 'title for database upgrade status at system status report', 'affiliate-for-woocommerce' ); ?>
				</td>
				<td class="help">&nbsp;</td>
				<td>
					<?php
					echo esc_html( ( ! empty( get_option( 'afwc_db_upgrade_running', false ) ) ) ? _x( 'In progress', 'indicates if database upgrade is running', 'affiliate-for-woocommerce' ) : _x( 'Completed', 'indicates if database upgrade is completed', 'affiliate-for-woocommerce' ) );
					?>
				</td>
			</tr>
			<?php
		}
	}
}

AFWC_System_Status_Report::get_instance();
