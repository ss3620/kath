<?php
/**
 * Abstract Block Base Class
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Abstracts;

use PRAD\Includes\Blocks\Interfaces\Block_Interface;
use PRAD\Includes\Traits\Attribute_Builder;
use PRAD\Includes\Traits\Price_Handler;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract base class for all blocks
 */
abstract class Abstract_Block implements Block_Interface {

	use Attribute_Builder;
	use Price_Handler;

	/**
	 * Block data
	 *
	 * @var array
	 */
	protected array $data;

	/**
	 * Product ID
	 *
	 * @var int
	 */
	protected int $product_id;

	/**
	 * Allowed HTML tags for wp_kses
	 *
	 * @var array
	 */
	protected array $allowed_html_tags;

	/**
	 * Constructor
	 *
	 * @param array $data Block configuration data.
	 * @param int   $product_id WooCommerce product ID.
	 */
	public function __construct( array $data, int $product_id ) {
		$this->data              = $data;
		$this->product_id        = $product_id;
		$this->allowed_html_tags = apply_filters( 'prad_allowed_html_tags', array() );// phpcs:ignore

		$this->init();
	}

	/**
	 * Initialize block-specific setup
	 */
	protected function init(): void {
		// Override in child classes if needed.
	}

	/**
	 * Get property from block data
	 *
	 * @param string $key Property key.
	 * @param mixed  $def Default value.
	 * @return mixed
	 */
	protected function get_property( string $key, $def = '' ) {
		return $this->data[ $key ] ?? $def;
	}

	/**
	 * Get the block's options.
	 *
	 * The free blocks call this without an argument and get the options minus the
	 * ones the user has switched off. The Pro blocks pass `true` and get every option.
	 *
	 * @param bool $return_all Return every option, hidden or not.
	 * @return array
	 */
	protected function get_field_options( $return_all = false ) {
		$options = $this->get_property( '_options', array() );

		return $return_all ? $options : $this->filter_visible_options( $options );
	}

	/**
	 * Whether an option/product entry has been switched off with its "visible" toggle.
	 *
	 * @param mixed $item Option entry from the block data.
	 * @return bool
	 */
	protected function is_option_hidden( $item ): bool {
		return is_array( $item ) && false === ( $item['visible'] ?? true );
	}

	/**
	 * Drop options the user has hidden.
	 *
	 * Array keys are preserved so an option keeps the index it was saved with — that
	 * index is what the front end submits and the cart looks up.
	 *
	 * @param mixed $options Option entries from the block data.
	 * @return mixed
	 */
	protected function filter_visible_options( $options ) {
		if ( ! is_array( $options ) ) {
			return $options;
		}

		return array_filter(
			$options,
			function ( $item ) {
				return ! $this->is_option_hidden( $item );
			}
		);
	}

	/**
	 * Get block ID
	 *
	 * @return string
	 */
	protected function get_block_id(): string {
		return $this->get_property( 'blockid', '' );
	}

	/**
	 * Kept only so WowAddons Pro 1.2.1 and older, whose block classes still call it, keep
	 * working. This plugin doesn't use it, and it always returns an empty value.
	 *
	 * @deprecated 1.8.3
	 *
	 * @return string
	 */
	protected function is_formula_value_enabled(): string {
		return '';
	}

	/**
	 * Get block label
	 *
	 * @return string
	 */
	protected function get_label(): string {
		return $this->get_property( 'label', '' );
	}

	/**
	 * Get block placeholder
	 *
	 * @return string
	 */
	protected function get_placeholder(): string {
		return $this->get_property( 'placeholder', '' );
	}
	/**
	 * Get Description Position
	 *
	 * @return string
	 */
	protected function get_description_position(): string {
		return $this->get_property( 'descpPosition', 'belowTitle' );
	}

	/**
	 * Check if block is required
	 *
	 * @return bool
	 */
	protected function is_required(): bool {
		return (bool) $this->get_property( 'required', false );
	}

	/**
	 * Check if block is hidden
	 *
	 * @return bool
	 */
	protected function is_title_hidden(): bool {
		return (bool) $this->get_property( 'hide', false );
	}

	/**
	 * Get block description
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return $this->get_property( 'description', '' );
	}

	/**
	 * Get CSS class
	 *
	 * @return string
	 */
	protected function get_css_class(): string {
		$container_width = $this->get_property( 'blockWidth', '_100' );
		$classes         = $this->get_property( 'class', '' );

		$field_conditions = $this->get_field_conditions();
		if ( $this->is_logic_enabled() && ! empty( $field_conditions['rules'] ) ) {
			$classes .= ' prad-field-none';
		}

		return $classes . ' prad-cw' . $container_width;
	}

	/**
	 * Get section ID
	 *
	 * @return string
	 */
	protected function get_section_id(): string {
		return $this->get_property( 'sectionid', '' );
	}

	/**
	 * Check if logic is enabled
	 *
	 * @return bool
	 */
	protected function is_logic_enabled(): bool {
		return (bool) $this->get_property( 'en_logic', false );
	}

	/**
	 * Get field conditions
	 *
	 * @return array
	 */
	protected function get_field_conditions(): array {
		return $this->get_property( 'fieldConditions', array() );
	}

	/**
	 * Extra data attributes for the block wrapper.
	 *
	 * Extension point: none in the free plugin. product-addons-pro overrides it.
	 *
	 * @return array Keys become data-* attributes; empty values are skipped.
	 */
	protected function get_extra_data_attributes(): array {
		return array();
	}

	/**
	 * Get common HTML attributes for the block
	 *
	 * @return array
	 */
	protected function get_common_attributes(): array {
		$css_classes = array(
			'prad-parent',
			'prad-block-' . $this->get_type(),
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$data_attributes = array(
			'bid'             => $this->get_block_id(),
			'sectionid'       => $this->get_section_id(),
			'label'           => $this->get_label(),
			'placeholder'     => $this->get_placeholder(),
			'btype'           => $this->get_type(),
			'enlogic'         => $this->is_logic_enabled() ? 'yes' : 'no',
			'required'        => $this->is_required() ? 'yes' : 'no',
			'fieldconditions' => $this->get_field_conditions(),
			'defval'          => $this->get_property( 'defval', null ),
		);
		$data_attributes = array_merge( $this->get_extra_data_attributes(), $data_attributes );

		return array_merge(
			array(
				'class' => $this->build_css_classes( $css_classes ),
				'id'    => 'prad-bid-' . $this->get_block_id(),
			),
			$this->build_data_attributes( $data_attributes )
		);
	}

	/**
	 * Render block Tooltip Description
	 *
	 * @return string
	 */
	protected function render_description_tooltip() {
		if ( ! $this->get_description() || 'tooltip' !== $this->get_description_position() ) {
			return '';
		}

		$html  = '<div class="prad-tooltip-container">';
		$html .= '<div class="prad-tooltip-icon">?</div>';
		$html .= sprintf(
			'<div class="prad-tooltip-box">%s',
			wp_kses( $this->get_description(), $this->allowed_html_tags )
		);
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render Description Below Title
	 *
	 * @return string
	 */
	protected function render_description_below_title() {
		if ( ! $this->get_description() || 'belowTitle' !== $this->get_description_position() ) {
			return '';
		}

		$html = sprintf(
			'<div class="prad-block-description">%s</div>',
			wp_kses( $this->get_description(), $this->allowed_html_tags )
		);

		return $html;
	}

	/**
	 * Render Description Below Field
	 *
	 * @return string
	 */
	protected function render_description_below_field() {
		if ( ! $this->get_description() || 'belowField' !== $this->get_description_position() ) {
			return '';
		}

		$html = sprintf(
			'<div class="prad-block-description prad-mt-12">%s</div>',
			wp_kses( $this->get_description(), $this->allowed_html_tags )
		);

		return $html;
	}

	/**
	 * Render the option description of a single option.
	 *
	 * Extension point: the free plugin renders nothing. product-addons-pro overrides it.
	 *
	 * @param array $item Option item.
	 * @return string
	 */
	protected function render_option_description( $item ): string {
		return '';
	}

	/**
	 * Render the image preview tooltip for the block's option images.
	 *
	 * Extension point: the free plugin renders nothing. product-addons-pro overrides it.
	 *
	 * @return string
	 */
	protected function render_image_preview(): string {
		return '';
	}

	/**
	 * Get the option description used as an image preview tooltip.
	 *
	 * Extension point: the free plugin has none. product-addons-pro overrides it.
	 *
	 * @param array $item Option item.
	 * @return string
	 */
	public function get_option_tooltip_description( $item ): string {
		return '';
	}

	/**
	 * Whether one shared price replaces the per-option prices of this block.
	 *
	 * Extension point: always false in the free plugin. product-addons-pro overrides it.
	 *
	 * @return boolean
	 */
	protected function has_shared_price(): bool {
		return false;
	}

	/**
	 * Extra data attributes for the block wrapper that describe a shared price.
	 *
	 * Extension point: none in the free plugin. product-addons-pro overrides it.
	 *
	 * @return array
	 */
	protected function get_same_price_attributes(): array {
		return array();
	}

	/**
	 * Extra attributes for the input of one option.
	 *
	 * Extension point: none in the free plugin. product-addons-pro overrides it.
	 *
	 * @since 1.8.3
	 *
	 * @param array      $item  Option item.
	 * @param int|string $index Option index, or '' for a block with a single option.
	 * @return array Attribute name => value.
	 */
	protected function get_option_extra_attributes( $item, $index ): array {
		return array();
	}

	/**
	 * Whether the block shows an input next to each option.
	 *
	 * Extension point: always false in the free plugin. product-addons-pro overrides it.
	 *
	 * @since 1.8.3
	 *
	 * @return boolean
	 */
	protected function has_option_input(): bool {
		return false;
	}

	/**
	 * Render the input shown next to one option.
	 *
	 * Extension point: the free plugin renders nothing. product-addons-pro overrides it.
	 *
	 * @since 1.8.3
	 *
	 * @param int|string $index      Option index, or '' for a block with a single option.
	 * @param boolean    $full_width Whether the input spans the option's width (swatch-style options).
	 * @return string
	 */
	protected function render_option_input( $index, bool $full_width = false ): string {
		return '';
	}

	/**
	 * Render the block heading (title and description).
	 *
	 * Extension point: product-addons-pro overrides it to show the shared price next to the title.
	 *
	 * @return string
	 */
	protected function render_block_heading() {
		return $this->render_title_description_noprice();
	}

	/**
	 * Render Title , Description
	 *
	 * @return string
	 */
	protected function render_title_description_noprice() {
		$title_hidden    = $this->is_title_hidden();
		$desc_position   = $this->get_description_position();
		$has_description = $this->get_description();

		// Early exit conditions.
		if (
			( $title_hidden && 'tooltip' === $desc_position ) ||
			( $title_hidden && 'belowField' === $desc_position ) ||
			( $title_hidden && 'belowTitle' === $desc_position && ! $has_description )
		) {
			return '';
		}

		$html  = '<div class="prad-d-flex prad-flex-column prad-mb-12 prad-gap-2">';
		$html .= '<div class="prad-d-flex prad-item-center prad-gap-12 ">';

		if ( ! $this->is_title_hidden() ) {
			$html .= $this->render_title_with_required();
			$html .= $this->render_description_tooltip();
		}

		$html .= '</div>';
		$html .= $this->render_description_below_title();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render Title , Description
	 *
	 * @param array $price_info Price information.
	 * @return string
	 */
	protected function render_title_description_price_with_position( $price_info ) {
		$title_hidden     = $this->is_title_hidden();
		$desc_position    = $this->get_description_position();
		$has_description  = $this->get_description();
		$price_with_title = $this->should_show_price_with_title( $price_info );

		// Early exit conditions.
		if (
			( $title_hidden && 'tooltip' === $desc_position && ! $price_with_title ) ||
			( $title_hidden && 'belowTitle' === $desc_position && ! $has_description )
		) {
			return '';
		}

		$html  = '<div class="prad-d-flex prad-flex-column prad-mb-12 prad-gap-2">';
		$html .= '<div class="prad-d-flex prad-item-center prad-gap-12 ">';

		if ( ! $this->is_title_hidden() ) {
			$html .= $this->render_title_with_required();
			$html .= $this->render_description_tooltip();
		}

		if ( $price_with_title ) {

			$html .= $this->render_price_html( $price_info, 'with_title' );
		}

		$html .= '</div>';
		$html .= $this->render_description_below_title();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render block title section
	 *
	 * @param array|null $price_info Price information.
	 * @return string
	 */
	protected function render_title_section( ?array $price_info = null ): string {
		if ( $this->is_title_hidden() && ( ! $price_info || ! $this->should_show_price_with_title( $price_info ) ) ) {
			return '';
		}

		$html = '<div class="prad-d-flex prad-item-center prad-gap-12 prad-mb-12">';

		// Title and required indicator.
		if ( ! $this->is_title_hidden() ) {
			$html .= $this->render_title_with_required();
		}

		// Price with title.
		if ( $price_info && $this->should_show_price_with_title( $price_info ) ) {
			$html .= $this->render_price_html( $price_info, 'with_title' );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render title with required indicator
	 *
	 * @return string
	 */
	protected function render_title_with_required(): string {
		$html  = '<div class="prad-relative prad-w-fit">';
		$html .= '<div class="prad-block-title">' . wp_kses( $this->get_label(), $this->allowed_html_tags ) . '</div>';

		if ( $this->is_required() ) {
			$html .= '<div class="prad-block-required prad-absolute">*</div>';
		}

		$html .= '</div>';

		return $html;
	}
	/**
	 * Get block configuration
	 *
	 * @return array
	 */
	public function get_config(): array {
		return $this->data;
	}
	/**
	 * Render block content using common template
	 *
	 * @param object  $item Item to render.
	 * @param integer $index Item index.
	 * @param array   $price_info Price information.
	 * @param string  $variation_html Optional variation HTML.
	 * @param boolean $suppress_input Leave out the option input that render_option_input() adds.
	 * @return string Rendered content
	 */
	protected function render_block_content( $item, int $index, array $price_info, string $variation_html = '', bool $suppress_input = false ): string {
		$allowed_tags = $this->allowed_html_tags;

		$p_url = isset( $item->url ) ? $item->url : '';

		ob_start();
		?>
		<div class="prad-d-flex prad-flex-column prad-item-center prad-gap-2 prad-text-center prad-mt-8 prad-block-content-wrapper prad-effect-container">
			<div>
				<?php // The title tooltip repeats the visible label below, so it uses the same wp_kses() allow-list as that content. ?>
				<div title="<?php echo wp_kses( $item->value, $allowed_tags ); ?>" class="prad-block-content prad-ellipsis-2<?php echo esc_attr( $p_url ? ' prad-cursor-pointer prad-product-link' : '' ); ?>" data-phref="<?php echo esc_url( $p_url ); ?>">
					<?php echo wp_kses( $item->value, $allowed_tags ); ?>
				</div>
				<?php echo wp_kses( $this->render_option_description( (array) $item ), $allowed_tags ); ?>
				<?php if ( 'no_cost' !== $item->type ) : ?>
					<div class="prad-block-price prad-text-upper">
						<?php echo wp_kses( $price_info['html'], $allowed_tags ); ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
			if ( $variation_html ) :
				echo wp_kses( $variation_html, $allowed_tags );
			endif;
			?>
			<?php if ( ! $suppress_input ) : ?>
				<?php echo wp_kses( $this->render_option_input( $index, true ), $allowed_tags ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
