<?php //phpcs:ignore
/**
 * Block Factory
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Factories;

use PRAD\Includes\Blocks\Interfaces\Block_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Factory class for creating block instances
 */
class Block_Factory {

	/**
	 * Registered block types
	 *
	 * @var array
	 */
	private static array $block_types = array();


	/**
	 * Register a block type
	 *
	 * @param string $type Block type identifier.
	 * @param string $class_name Full class name.
	 * @throws \InvalidArgumentException If class doesn't implement Block_Interface.
	 */
	public static function register_block( string $type, string $class_name ): void {
		if ( ! class_exists( $class_name ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Block class %s does not exist', esc_html( $class_name ) )
			);
		}

		if ( ! is_subclass_of( $class_name, Block_Interface::class ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Block class %s must implement Block_Interface', esc_html( $class_name ) )
			);
		}

		self::$block_types[ $type ] = $class_name;

		do_action( 'prad_block_registered', $type, $class_name );
	}

	/**
	 * Create a block instance
	 *
	 * @param string $type Block type.
	 * @param array  $data Block configuration data.
	 * @param int    $product_id Product ID.
	 * @return Block_Interface|null
	 */
	public static function create_block( string $type, array $data, int $product_id ): ?Block_Interface {

		$class_name = self::get_block_class_name_by_type( $type );

		if ( ! $class_name || ! class_exists( $class_name ) ) {
			return null;
		}

		try {
			$block = new $class_name( $data, $product_id );

			// Apply filters to allow modification.
			$block = apply_filters( 'prad_block_created', $block, $type, $data, $product_id );
			$block = apply_filters( "prad_block_created_{$type}", $block, $data, $product_id );

			return $block;

		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only logged when WP_DEBUG is enabled.
					sprintf(
						'PRAD Block Factory Error: Failed to create block type "%s". Error: %s',
						$type,
						$e->getMessage()
					)
				);
			}

			do_action( 'prad_block_creation_failed', $type, $data, $e );

			return null;
		}
	}

	/**
	 * Get ClassName by block type
	 *
	 * @param string $type Block type.
	 * @return string
	 */
	public static function get_block_class_name_by_type( $type ) {
		$blocks_array = array(
			'textfield'      => 'PRAD\Includes\Blocks\Types\Textfield_Block',
			'section'        => 'PRAD\Includes\Blocks\Types\Section_Block',
			'radio'          => 'PRAD\Includes\Blocks\Types\Radio_Block',
			'checkbox'       => 'PRAD\Includes\Blocks\Types\Checkbox_Block',
			'custom_formula' => 'PRAD\Includes\Blocks\Types\Custom_Formula_Block',
			'switch'         => 'PRAD\Includes\Blocks\Types\Switch_Block',
			'select'         => 'PRAD\Includes\Blocks\Types\Select_Block',
			'color_picker'   => 'PRAD\Includes\Blocks\Types\Color_Picker_Block',
			'date'           => 'PRAD\Includes\Blocks\Types\Date_Block',
			'time'           => 'PRAD\Includes\Blocks\Types\Time_Block',
			'datetime'       => 'PRAD\Includes\Blocks\Types\Date_Time_Block',
			'range'          => 'PRAD\Includes\Blocks\Types\Range_Block',
			'url'            => 'PRAD\Includes\Blocks\Types\Url_Block',
			'email'          => 'PRAD\Includes\Blocks\Types\Email_Block',
			'number'         => 'PRAD\Includes\Blocks\Types\Number_Block',
			'telephone'      => 'PRAD\Includes\Blocks\Types\Telephone_Block',
			'textarea'       => 'PRAD\Includes\Blocks\Types\Textarea_Block',
			'heading'        => 'PRAD\Includes\Blocks\Types\Heading_Block',
			'shortcode'      => 'PRAD\Includes\Blocks\Types\Shortcode_Block',
			'separator'      => 'PRAD\Includes\Blocks\Types\Separator_Block',
			'spacer'         => 'PRAD\Includes\Blocks\Types\Spacer_Block',
			'content'        => 'PRAD\Includes\Blocks\Types\Content_Block',
			'popup'          => 'PRAD\Includes\Blocks\Types\Popup_Block',
			// Legacy support: Button, Color Swatches, Image Swatches and Products are
			// deprecated free fields. They can no longer be added in the builder, but
			// are still rendered here so fields existing users already saved on their
			// products keep working. They'll be removed in a future release. The
			// "Advanced" (Pro) versions shown in the builder are added by WowAddons
			// Pro, which also registers its own renderers for these types through
			// register_block(). No Pro logic for them lives in this plugin.
			'button'         => 'PRAD\Includes\Blocks\Types\Button_Block',
			'color_switch'   => 'PRAD\Includes\Blocks\Types\Color_Switch_Block',
			'img_switch'     => 'PRAD\Includes\Blocks\Types\Image_Switch_Block',
			'products'       => 'PRAD\Includes\Blocks\Types\Products_Block',
			'upload'         => 'PRAD\Includes\Blocks\Types\Upload_Block',
		);

		// Types registered via register_block() (e.g. by product-addons-pro) take priority,
		// and are also how block types not shipped in the free plugin (e.g. pro-only ones) get added.
		$blocks_array = array_merge( $blocks_array, self::$block_types );

		return $blocks_array[ $type ] ?? null;
	}
}
