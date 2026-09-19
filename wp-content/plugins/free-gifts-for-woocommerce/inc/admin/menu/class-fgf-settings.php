<?php
/**
 * Admin Settings Class
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Settings')) {

	/**
	 * FGF_Settings Class
	 */
	class FGF_Settings {

		/**
		 * Setting pages.
		 * 
		 * @var array
		 */
		private static $settings = array();

		/**
		 * Error messages.
		 * 
		 * @var array
		 */
		private static $errors = array();

		/**
		 * Plugin slug.
		 * 
		 * @var string
		 */
		private static $plugin_slug = 'fgf';

		/**
		 * Update messages.
		 * 
		 * @var array
		 */
		private static $messages = array();

		/**
		 * Include the settings page classes.
		 * 
		 * @return array
		 */
		public static function get_settings_pages() {
			if (!empty(self::$settings)) {
				return self::$settings;
			}

			include_once FGF_PLUGIN_PATH . '/inc/abstracts/class-fgf-settings-page.php';

			$settings = array();
			$tabs = self::settings_page_tabs();

			foreach ($tabs as $tab_name) {
				$settings[sanitize_key($tab_name)] = include 'tabs/' . sanitize_key($tab_name) . '.php';
			}
			/**
			 * This hook is used to alter the settings pages.
			 * 
			 * @since 1.0
			 */
			self::$settings = apply_filters(sanitize_key(self::$plugin_slug . '_get_settings_pages'), $settings);

			return self::$settings;
		}

		/**
		 * Add a message.
		 */
		public static function add_message( $text ) {
			self::$messages[] = $text;
		}

		/**
		 * Add an error.
		 */
		public static function add_error( $text ) {
			self::$errors[] = $text;
		}

		/**
		 * Output messages + errors.
		 */
		public static function show_messages() {
			if (count(self::$errors) > 0) {
				foreach (self::$errors as $error) {
					self::error_message($error);
				}
			} elseif (count(self::$messages) > 0) {
				foreach (self::$messages as $message) {
					self::success_message($message);
				}
			}
		}

		/**
		 * Show an success message.
		 */
		public static function success_message( $text, $echo = true ) {
			ob_start();
			$contents = '<div id="message " class="updated inline ' . esc_html(self::$plugin_slug) . '_save_msg"><p><strong>' . $text . '</strong></p></div>';
			ob_end_clean();

			if ($echo) {
				echo wp_kses_post($contents);
			} else {
				return $contents;
			}
		}

		/**
		 * Show an error message.
		 */
		public static function error_message( $text, $echo = true ) {
			ob_start();
			$contents = '<div id="message" class="error inline"><p><strong>' . $text . '</strong></p></div>';
			ob_end_clean();

			if ($echo) {
				echo wp_kses_post($contents);
			} else {
				return $contents;
			}
		}

		/**
		 * Show an notice.
		 */
		public static function notice( $text, $echo = true ) {
			ob_start();
			$contents = '<div id="message" class="notice inline"><p><strong>' . $text . '</strong></p></div>';
			ob_end_clean();

			if ($echo) {
				echo wp_kses_post($contents);
			} else {
				return $contents;
			}
		}

		/**
		 * Settings page tabs.
		 * 
		 * @return array
		 */
		public static function settings_page_tabs() {

			return array(
				'rules',
				'manual-gifts',
				'master-logs',
				'settings',
				'shortcodes',
			);
		}

		/**
		 * Handles the display of the settings page in admin.
		 */
		public static function output() {
			global $current_section, $current_tab;

			$tabs = fgf_get_allowed_setting_tabs();

			/* Include admin html settings */
			include_once 'views/html-settings.php' ;
		}

		/**
		 * Handles the display of the settings page buttons in page.
		 */
		public static function output_buttons( $reset = true ) {

			/* Include admin html settings buttons */
			include_once 'views/html-settings-buttons.php' ;
		}

		/**
		 * Output admin fields.
		 */
		public static function output_fields( $value ) {
			if (!isset($value['type']) || 'fgf_custom_fields' != $value['type']) {
				return;
			}

			$value['id'] = isset($value['id']) ? $value['id'] : '';
			$value['css'] = isset($value['css']) ? $value['css'] : '';
			$value['desc'] = isset($value['desc']) ? $value['desc'] : '';
			$value['title'] = isset($value['title']) ? $value['title'] : '';
			$value['class'] = isset($value['class']) ? $value['class'] : '';
			$value['default'] = isset($value['default']) ? $value['default'] : '';
			$value['name'] = isset($value['name']) ? $value['name'] : $value['id'];
			$value['placeholder'] = isset($value['placeholder']) ? $value['placeholder'] : '';
			$value['without_label'] = isset($value['without_label']) ? $value['without_label'] : false;
			$value['custom_attributes'] = isset($value['custom_attributes']) ? $value['custom_attributes'] : '';

			// Custom attribute handling.
			$custom_attributes = fgf_format_custom_attributes($value);

			// Description handling.
			$field_description = WC_Admin_Settings::get_field_description($value);
			$description = $field_description['description'];
			$tooltip_html = $field_description['tooltip_html'];

			// Switch based on type
			switch ($value['fgf_field']) {

				case 'button':
					?>
					<tr valign="top">
						<?php if (!$value['without_label']) : ?>
							<th scope="row">
								<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label><?php echo wp_kses_post($tooltip_html); ?>
							</th>
						<?php endif; ?>
						<td>
							<button
								id="<?php echo esc_attr($value['id']); ?>"
								type="<?php echo esc_attr($value['fgf_field']); ?>"
								class="<?php echo esc_attr($value['class']); ?>"
								<?php echo wp_kses_post(implode(' ', $custom_attributes)); ?>
								><?php echo esc_html($value['default']); ?> </button>
								<?php echo wp_kses_post($description); ?>
						</td>
					</tr>
					<?php
					break;

				case 'ajaxmultiselect':
					$option_value = get_option($value['id'], $value['default']);
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label><?php echo wp_kses_post($tooltip_html); ?>
						</th>
						<td>
							<?php
							$value['options'] = $option_value;
							fgf_select2_html($value);
							echo wp_kses_post($description);
							?>
						</td>
					</tr>
					<?php
					break;

				case 'wpeditor':
					$option_value = get_option($value['id'], $value['default']);
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label><?php echo wp_kses_post($tooltip_html); ?>
						</th>
						<td>
							<?php
							wp_editor(
									$option_value, $value['id'], array(
								'media_buttons' => false,
								'editor_class' => esc_attr($value['class']),
									)
							);

							echo wp_kses_post($description);
							?>
						</td>
					</tr>
					<?php
					break;
				// Days/months/years selector.
				case 'relative_date_selector':
					$option_value = get_option($value['id'], $value['default']);
					?>
					<tr valign="top" class='fgf-relative-date-selector'>
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?> <?php echo wp_kses_post($tooltip_html); // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp">
							<input
								name="<?php echo esc_attr($value['id']); ?>[number]"
								id="<?php echo esc_attr($value['id']); ?>"
								type="number"
								value="<?php echo esc_attr($option_value['number']); ?>"
								class="fgf-relative-date-selector-number <?php echo esc_attr($value['class']); ?>"
								placeholder="<?php echo esc_attr($value['placeholder']); ?>"
								step="1"
								min="1"
								<?php echo wp_kses_post(implode(' ', $custom_attributes)); // WPCS: XSS ok. ?>
								/>&nbsp;
							<select name="<?php echo esc_attr($value['id']); ?>[unit]" class='fgf-relative-date-selector-unit'>
								<?php
								foreach ($value['periods'] as $value => $label) {
									echo '<option value="' . esc_attr($value) . '"' . selected($option_value['unit'], $value, false) . '>' . esc_html($label) . '</option>';
								}
								?>
							</select> <?php echo wp_kses_post($description); // WPCS: XSS ok. ?>
						</td>
					</tr>
					<?php
					break;

				// Price
				case 'price':
					$option_value = get_option( $value[ 'id' ], $value[ 'default' ] ) ;
					?>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"><?php echo esc_html( $value[ 'title' ] ) ; ?></label><?php echo wp_kses_post( $tooltip_html ) ; ?>
						</th>
						<td>
							<input
								id="<?php echo esc_attr( $value[ 'id' ] ) ; ?>"
								name="<?php echo esc_attr( $value[ 'name' ] ) ; ?>"
								type="text"
								class="wc_input_price <?php echo esc_attr( $value[ 'class' ] ) ; ?>"
								placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
								<?php echo wp_kses_post( implode( ' ', $custom_attributes ) ) ; ?>
								value="<?php echo esc_html( wc_format_localized_price( $option_value ) ) ; ?>"/>
								<?php echo wp_kses_post( $description ) ; ?>
						</td>
					</tr>
					<?php
					break ;
			}
		}

		/**
		 * Save admin fields.
		 */
		public static function save_fields( $value, $option, $raw_value ) {

			if (!isset($option['type']) || 'fgf_custom_fields' != $option['type']) {
				return $value;
			}

			$value = null;

			// Format the value based on option type.
			switch ($option['fgf_field']) {
				case 'ajaxmultiselect':
					$value = array_filter((array) $raw_value);
					break;
				case 'relative_date_selector':
				case 'wpeditor':
					$value = $raw_value;
					break;
				case 'price':
					$value = wc_format_decimal( $raw_value ) ;
					break ;
			}

			return $value;
		}

		/**
		 * Reset admin fields.
		 */
		public static function reset_fields( $options ) {
			if (!is_array($options)) {
				return false;
			}

			// Loop options and get values to reset.
			foreach ($options as $option) {
				if (!isset($option['id']) || !isset($option['type']) || !isset($option['default'])) {
					continue;
				}

				update_option($option['id'], $option['default']);
			}
			return true;
		}
	}

}
