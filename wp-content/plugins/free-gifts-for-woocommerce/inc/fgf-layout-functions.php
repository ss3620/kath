<?php
/**
 * Layout functions.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!function_exists('fgf_select2_html')) {

	/**
	 * Return or display Select2 HTML
	 *
	 * @return string
	 */
	function fgf_select2_html( $args, $echo = true ) {
		$args = wp_parse_args(
				$args, array(
			'class' => '',
			'id' => '',
			'name' => '',
			'list_type' => '',
			'action' => '',
			'placeholder' => '',
			'exclude_global_variable' => 'no',
			'display_stock' => 'no',
			'custom_attributes' => array(),
			'multiple' => true,
			'allow_clear' => true,
			'selected' => true,
			'options' => array(),
				)
		);

		$multiple = $args['multiple'] ? 'multiple="multiple"' : '';
		$name = esc_attr('' !== $args['name'] ? $args['name'] : $args['id']) . '[]';
		$options = array_filter(fgf_check_is_array($args['options']) ? $args['options'] : array());

		$allowed_html = array(
			'select' => array(
				'id' => array(),
				'class' => array(),
				'data-placeholder' => array(),
				'data-allow_clear' => array(),
				'data-display-stock' => array(),
				'data-exclude-global-variable' => array(),
				'data-action' => array(),
				'data-nonce' => array(),
				'multiple' => array(),
				'name' => array(),
			),
			'option' => array(
				'value' => array(),
				'selected' => array(),
			),
		);

		// Custom attribute handling.
		$custom_attributes = fgf_format_custom_attributes($args);
		$data_nonce = ( 'products' == $args['list_type'] ) ? 'data-nonce="' . wp_create_nonce('search-products') . '"' : '';

		ob_start();
		?><select <?php echo esc_attr($multiple); ?> 
			name="<?php echo esc_attr($name); ?>" 
			id="<?php echo esc_attr($args['id']); ?>" 
			data-action="<?php echo esc_attr($args['action']); ?>" 
			data-display-stock="<?php echo esc_attr($args['display_stock']); ?>" 
			data-exclude-global-variable="<?php echo esc_attr($args['exclude_global_variable']); ?>" 
			class="fgf_select2_search <?php echo esc_attr($args['class']); ?>" 
			data-placeholder="<?php echo esc_attr($args['placeholder']); ?>" 
			<?php echo wp_kses(implode(' ', $custom_attributes), $allowed_html); ?>
			<?php echo wp_kses($data_nonce, $allowed_html); ?>
			<?php echo $args['allow_clear'] ? 'data-allow_clear="true"' : ''; ?> >
				<?php
				if (is_array($args['options'])) {
					foreach ($args['options'] as $option_id) {
						$option_value = '';
						switch ($args['list_type']) {
							case 'post':
								$option_value = get_the_title($option_id);
								break;
							case 'coupons':
								$option_value = get_the_title($option_id) . ' (#' . absint($option_id) . ')';
								break;
							case 'products':
								$product = wc_get_product($option_id);
								if ($product) {
									$formatted_name = $product->get_formatted_name();
									if ('yes' === $args['display_stock'] && $product->managing_stock()) {
										/* Translators: %d stock amount */
										$formatted_name .= ' &ndash; ' . sprintf(__('Stock: %d', 'free-gifts-for-woocommerce'), wc_format_stock_quantity_for_display($product->get_stock_quantity(), $product));
									}

									$option_value = wp_strip_all_tags($formatted_name);
								}
								break;
							case 'customers':
								$user = get_user_by('id', $option_id);
								if ($user) {
									$option_value = $user->display_name . '(#' . absint($user->ID) . ' &ndash; ' . $user->user_email . ')';
								}
								break;
						}

						if ($option_value) {
							?>
						<option value="<?php echo esc_attr($option_id); ?>" <?php echo $args['selected'] ? 'selected="selected"' : ''; // WPCS: XSS ok. ?>><?php echo esc_html($option_value); ?></option>
							<?php
						}
					}
				}
				?>
		</select>
		<?php
		$html = ob_get_clean();

		if ($echo) {
			echo wp_kses($html, $allowed_html);
		}

		return $html;
	}

}

if (!function_exists('fgf_format_custom_attributes')) {

	/**
	 * Format Custom Attributes
	 *
	 * @return array
	 */
	function fgf_format_custom_attributes( $value ) {
		$custom_attributes = array();

		if (!empty($value['custom_attributes']) && is_array($value['custom_attributes'])) {
			foreach ($value['custom_attributes'] as $attribute => $attribute_value) {
				$custom_attributes[] = esc_attr($attribute) . '=' . esc_attr($attribute_value) . '';
			}
		}

		return $custom_attributes;
	}

}

if (!function_exists('fgf_get_datepicker_html')) {

	/**
	 * Return or display Datepicker HTML
	 *
	 * @return string
	 */
	function fgf_get_datepicker_html( $args, $echo = true ) {
		$args = wp_parse_args(
				$args, array(
			'class' => '',
			'id' => '',
			'name' => '',
			'placeholder' => '',
			'custom_attributes' => array(),
			'value' => '',
			'time_only' => false,
			'wp_zone' => true,
			'with_time' => false,
			'error' => '',
				)
				);

		$name = ( '' !== $args['name'] ) ? $args['name'] : $args['id'];

		$allowed_html = array(
			'input' => array(
				'id' => array(),
				'type' => array(),
				'placeholder' => array(),
				'class' => array(),
				'value' => array(),
				'name' => array(),
				'min' => array(),
				'max' => array(),
				'data-error' => array(),
				'style' => array(),
			),
				);

		if ($args['time_only']) {
			$format = 'time';
			$alter_class_name = 'fgf_alter_timepicker_value';
			$class_name = 'fgf_timepicker ';
		} else {
			$alter_class_name = 'fgf_alter_datepicker_value';
			$class_name = ( $args['with_time'] ) ? 'fgf_datetimepicker ' : 'fgf_datepicker ';
			$format = ( $args['with_time'] ) ? 'Y-m-d H:i' : 'date';
		}

		// Custom attribute handling.
		$custom_attributes = fgf_format_custom_attributes($args);
		$value = !empty($args['value']) ? FGF_Date_Time::get_wp_format_datetime($args['value'], $format, $args['wp_zone']) : '';
		ob_start();
		?>
		<input type = "text" 
			   id="<?php echo esc_attr($args['id']); ?>"
			   value = "<?php echo esc_attr($value); ?>"
			   class="<?php echo esc_attr($class_name . $args['class']); ?>" 
			   placeholder="<?php echo esc_attr($args['placeholder']); ?>" 
			   data-error="<?php echo esc_attr($args['error']); ?>" 
			   <?php echo wp_kses(implode(' ', $custom_attributes), $allowed_html); ?>
			   />

		<input type = "hidden" 
			   class="<?php echo esc_attr($alter_class_name); ?>" 
			   name="<?php echo esc_attr($name); ?>"
			   value = "<?php echo esc_attr($args['value']); ?>"
			   /> 
		<?php
		$html = ob_get_clean();

		if ($echo) {
			echo wp_kses($html, $allowed_html);
		}

		return $html;
	}

}

if (!function_exists('fgf_convert_wp_date_format_php_to_jquery')) {

	/**
	 * Convert the WP date format PHP to the jQuery.
	 *
	 * @since 11.5.0
	 * @return string
	 * */
	function fgf_convert_wp_date_format_php_to_jquery() {
		$wp_date_format = get_option('date_format');

		$format_list = array(
			'd' => 'dd',
			'j' => 'd',
			'l' => 'DD',
			'z' => 'o', // Day.
			'F' => 'MM',
			'M' => 'M',
			'n' => 'm',
			'm' => 'mm', // Month.
			'Y' => 'yy',
			'y' => 'y', // Year.
				);

		$jqueryui_format = '';
		$escaping = false;
		for ($i = 0; $i < strlen($wp_date_format); $i++) {
			$char = $wp_date_format[$i];

			if ('\\' === $char) { // PHP date format escaping character
				$i++;

				if ($escaping) {
					$jqueryui_format .= $wp_date_format[$i];
				} else {
					$jqueryui_format .= "'" . $wp_date_format[$i];
				}

				$escaping = true;
			} else {
				if ($escaping) {
					$jqueryui_format .= "'";
					$escaping = false;
				}

				if (isset($format_list[$char])) {
					$jqueryui_format .= $format_list[$char];
				} else {
					$jqueryui_format .= $char;
				}
			}
		}

		return $jqueryui_format;
	}

}
