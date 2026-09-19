<?php

/**
 * Settings Tab.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (class_exists('FGF_Settings_Tab')) {
	return new FGF_Settings_Tab();
}

/**
 * Class.
 */
class FGF_Settings_Tab extends FGF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id = 'settings';
		$this->label = __('Settings', 'free-gifts-for-woocommerce');

		//Display the cron information.
		add_action('woocommerce_admin_field_fgf_display_cron_information', array( $this, 'display_cron_information' ));

		parent::__construct();
	}

	/**
	 * Get the sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'general' => __('General', 'free-gifts-for-woocommerce'),
			'display' => __('Display', 'free-gifts-for-woocommerce'),
			'notices' => __('Notices', 'free-gifts-for-woocommerce'),
			'progress_bar' => __('Progress Bar', 'free-gifts-for-woocommerce'),
			'advanced' => __('Advanced', 'free-gifts-for-woocommerce'),
			'notifications' => __('Notifications', 'free-gifts-for-woocommerce'),
			'localizations' => __('Localization', 'free-gifts-for-woocommerce'),
			'messages' => __('Messages', 'free-gifts-for-woocommerce'),
		);
		/**
		 * This hook is used to alter the settings sections.
		 *
		 * @since 1.0
		 */
		return apply_filters($this->plugin_slug . '_get_sections_' . $this->id, $sections);
	}

	/**
	 * Get the settings for general section array.
	 *
	 * @return array
	 */
	public function general_section_array() {
		$section_fields = array();

		// General Section Start
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('General Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_general_options',
		);
		$section_fields[] = array(
			'title' => __('Hide Free Gift Products on Shop and Category Pages', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'no',
			'desc_tip' => true,
			'desc' => __(' When enabled, the products which are configured to be given as Free Gifts will be hidden in Shop and Category Pages.', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('restrict_gift_product_display'),
		);
		$section_fields[] = array(
			'title' => __('Rule Status', 'free-gifts-for-woocommerce'),
			'type' => 'multiselect',
			'class' => 'fgf_select2',
			'default' => array( 'fgf_active', 'fgf_inactive' ),
			'options' => fgf_get_rule_statuses_options(),
			'id' => $this->get_option_key('gift_products_valid_rule_statuses'),
		);
		$section_fields[] = array(
			'title' => __('Maximum Number of Gift Products in "Manual Rule Type" is decided based on', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gifts_count_per_order_type'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Global Settings', 'free-gifts-for-woocommerce'),
				'2' => __('Rule Settings', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Global Settings - The Maximum Gifts Restriction applies for all rules. Rule Settings - The Maximum Gifts Restriction can be set on each rule.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Maximum Gifts in an Order', 'free-gifts-for-woocommerce'),
			'type' => 'number',
			'default' => '5',
			'custom_attributes' => array(
				'min' => '1',
				'data-error' => __('Please set any value as per your need in the Maximum Gifts in an Order field to proceed furthermore', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('The Maximum number of gift products which can be chosen for each order.', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gifts_count_per_order'),
		);
		$section_fields[] = array(
			'title' => __('Ajax Add to Cart for Manual Gift Products', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'no',
			'desc_tip' => true,
			'id' => $this->get_option_key('enable_ajax_add_to_cart'),
		);
		$section_fields[] = array(
			'title' => __('Allow Adding Multiple Quantities of Same Gift Product in an Order (Only for Manual Free Gifts)', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'no',
			'desc_tip' => true,
			'desc' => __('When enabled, a user can add the same product multiple times to the cart. Provided they are eligible to receive multiple gifts for a single purchase.', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gifts_selection_per_user'),
		);
		$section_fields[] = array(
			'title' => __('Allow User(s) to remove the automatically added Gift Product(s)', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'no',
			'desc_tip' => true,
			'desc' => __('When enabled, a user can remove the automatically added gift products.', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('show_automatic_free_gift_product_remove_link'),
		);

		$section_fields[] = array(
			'title' => __('Allow Shipping Cost for Free Gift', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'no',
			'desc_tip' => true,
			'desc' => __('When enabled, the shipping cost will be consider for free gifts in the order.', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('allow_shipping_free_gift'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_general_options',
		);
		// General Section End
		// Restriction Section Start
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Restriction Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_restriction_options',
		);
		$section_fields[] = array(
			'title' => __('Restrict Free Gift if WooCommerce Coupon is used', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'no',
			'desc_tip' => true,
			'desc' => __('When enabled, the user will not be eligible for a free gift if they have used a WooCommerce Coupon in the order.', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_restriction_based_coupon'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_restriction_options',
		);
		// Restriction section end

		return $section_fields;
	}

	/**
	 * Get the settings for display section array.
	 *
	 * @return array
	 */
	public function display_section_array() {
		$section_fields = array();

		// General section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('General Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_general_options',
		);
		$section_fields[] = array(
			'title' => __('Free Gift(s) Display Method in the Cart Items Table', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_product_cart_display_order'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Order in which they get added', 'free-gifts-for-woocommerce'),
				'2' => __('Grouped & displayed at the Bottom of the Table', 'free-gifts-for-woocommerce'),
			),
		);
		$section_fields[] = array(
			'title' => __("Gift Product's Original Price display method", 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_product_price_display_type'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __("Don't display price", 'free-gifts-for-woocommerce'),
				'2' => __('Strike and display the Price', 'free-gifts-for-woocommerce'),
			),
			'desc' => __('Strike and display the price will work only for shortcode based checkout page. For block based cart and checkout page, the price will display as zero.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_restriction_options',
		);
		// General section end.
		// Cart page section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Cart Page Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_cart_page_options',
		);
		$section_fields[] = array(
			'title' => __('Free Gifts display mode in the Cart Page', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_cart_page_display'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Inline', 'free-gifts-for-woocommerce'),
				'2' => __('Pop-Up', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Select Whether the Gift Products should be displayed Inline or Pop-up', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Free Gifts display position in the Cart Page', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_cart_page_display_position'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('After Cart Table', 'free-gifts-for-woocommerce'),
				'2' => __('Before Cart Table', 'free-gifts-for-woocommerce'),
			),
			'desc' => __('Positioning will work only for shortcode based cart page. For positioning in block based cart page, edit the cart page and move the "Free Gifts" block as per your needs.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_cart_page_options',
		);
		// Cart page section end.
		// Checkout page section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Checkout Page Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_checkout_page_options',
		);
		$section_fields[] = array(
			'title' => __('Allow Users to Choose Free Gifts in Checkout Page', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_checkout_page_display'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('No', 'free-gifts-for-woocommerce'),
				'2' => __('Yes', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Select whether to allow the Free Gifts selection in the checkout page', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Free Gifts display mode in the Checkout Page', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('checkout_gift_products_display_type'),
			'type' => 'select',
			'default' => '2',
			'options' => array(
				'1' => __('Inline', 'free-gifts-for-woocommerce'),
				'2' => __('Pop-Up', 'free-gifts-for-woocommerce'),
			),
			'class' => 'fgf-gift-products-checkout-field',
			'desc_tip' => true,
			'desc' => __('Select Whether the Gift Products should be displayed Inline or Pop-up', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Free Gifts display position in the Checkout Page', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('checkout_gift_products_hook_name'),
			'type' => 'select',
			'default' => '1',
			'class' => 'fgf-gift-products-checkout-field fgf-gift-products-checkout-display-type-field',
			'options' => array(
				'1' => __('Before Checkout Form', 'free-gifts-for-woocommerce'),
				'2' => __('After Checkout Billing Form', 'free-gifts-for-woocommerce'),
				'3' => __('Custom Hook', 'free-gifts-for-woocommerce'),
			),
			'desc' => __('Positioning will work only for shortcode based checkout page. For positioning in block based checkout page, edit the checkout page and move the "Free Gifts" block as per your needs.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Custom Hook Name', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => '',
			'id' => $this->get_option_key('checkout_gift_products_custom_hook_name'),
			'class' => 'fgf-gift-products-checkout-field fgf-gift-products-checkout-display-type-field fgf-gift-products-checkout-display-hook-field',
		);
		$section_fields[] = array(
			'title' => __('Custom Hook Priority', 'free-gifts-for-woocommerce'),
			'type' => 'number',
			'default' => '10',
			'id' => $this->get_option_key('checkout_gift_products_custom_hook_priority'),
			'class' => 'fgf-gift-products-checkout-field fgf-gift-products-checkout-display-type-field fgf-gift-products-checkout-display-hook-field',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_checkout_page_options',
		);
		// Checkout page section end.
		// Single product page section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Buy X Get Y Eligible Notice - Single Product Page', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_single_product_page_options',
		);
		$section_fields[] = array(
			'title' => __('Display Eligible Notices on the Single Product Page', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('bogo_eligible_notice_on_single_product'),
			'type' => 'select',
			'default' => '2',
			'options' => array(
				'1' => __('Yes', 'free-gifts-for-woocommerce'),
				'2' => __('No', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Decide whether to display the eligibility notice on the single product page.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Notice Display Position', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('bogo_eligible_notice_on_single_product_position'),
			'type' => 'select',
			'default' => '1',
			'options' => fgf_get_bogo_eligible_notice_on_single_product_display_position_options(),
			'desc_tip' => true,
			'desc' => __('Choose where you want to display the Buy X Get Y eligibility notice on the single product page.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_single_product_page_options',
		);
		// Single product page section end.
		// Loop page section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Buy X Get Y Eligible Notice - Product Archive Pages[Like Category, Shop]', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_loop_page_options',
		);
		$section_fields[] = array(
			'title' => __('Display Eligible Notices on the Archive Pages', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('bogo_eligible_notice_on_loop_page'),
			'type' => 'select',
			'default' => '2',
			'options' => array(
				'1' => __('Yes', 'free-gifts-for-woocommerce'),
				'2' => __('No', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Decide whether to display the eligibility notice on the archive pages.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Notice Display Position', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('bogo_eligible_notice_on_loop_page_position'),
			'type' => 'select',
			'default' => '1',
			'options' => fgf_get_bogo_eligible_notice_on_loop_page_display_position_options(),
			'desc_tip' => true,
			'desc' => __('Choose where you want to display the Buy X Get Y eligibility notice on the product archive pages.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_loop_page_options',
		);
		// Loop page section end.
		// Gift display section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Gift Products Display Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_gift_display_options',
		);
		$section_fields[] = array(
			'title' => __('Gift Products Display Type', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_display_type'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Table', 'free-gifts-for-woocommerce'),
				'2' => __('Carousel', 'free-gifts-for-woocommerce'),
				'3' => __('Select Box(Dropdown)', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Select whether the Gift Products should be displayed in a Table or Carousel or Select Box', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Pagination Display', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_display_table_pagination'),
			'class' => 'fgf_gift_table_display_type',
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Show', 'free-gifts-for-woocommerce'),
				'2' => __('Hide', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Select whether to Display/Hide the Pagination', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Pagination to Display Gift Products', 'free-gifts-for-woocommerce'),
			'type' => 'number',
			'default' => '4',
			'class' => 'fgf_gift_table_display_type',
			'custom_attributes' => array( 'min' => '1' ),
			'id' => $this->get_option_key('free_gift_per_page_column_count'),
		);
		$section_fields[] = array(
			'title' => __('Gift Products Per Page - Desktop', 'free-gifts-for-woocommerce'),
			'type' => 'number',
			'default' => '3',
			'class' => 'fgf_gift_carousel_display_type',
			'custom_attributes' => array(
				'min' => '1',
				'data-error' => __(' Displaying more than 3 products per page in a Carousel can cause Display related issues. Do you want to save anyway?', 'free-gifts-for-woocommerce'),
			),
			'id' => $this->get_option_key('carousel_gift_per_page'),
		);
		$section_fields[] = array(
			'title' => __('Gift Products Per Page - Tablet', 'free-gifts-for-woocommerce'),
			'type' => 'number',
			'default' => '2',
			'class' => 'fgf_gift_carousel_display_type',
			'custom_attributes' => array(
				'min' => '1',
			),
			'id' => $this->get_option_key('carousel_gift_per_page_tablet'),
		);
		$section_fields[] = array(
			'title' => __('Gift Products Per Page - Mobile', 'free-gifts-for-woocommerce'),
			'type' => 'number',
			'default' => '1',
			'class' => 'fgf_gift_carousel_display_type',
			'custom_attributes' => array(
				'min' => '1',
			),
			'id' => $this->get_option_key('carousel_gift_per_page_mobile'),
		);
		$section_fields[] = array(
			'title' => __('Space Between Products in Carousel in Pixels', 'free-gifts-for-woocommerce'),
			'type' => 'number',
			'default' => '10',
			'class' => 'fgf_gift_carousel_display_type',
			'custom_attributes' => array( 'min' => '1' ),
			'id' => $this->get_option_key('carousel_item_margin'),
		);
		$section_fields[] = array(
			'title' => __('Number of Products to Slide During Navigation', 'free-gifts-for-woocommerce'),
			'type' => 'number',
			'default' => '1',
			'class' => 'fgf_gift_carousel_display_type',
			'custom_attributes' => array( 'min' => '1' ),
			'id' => $this->get_option_key('carousel_item_per_slide'),
		);
		$section_fields[] = array(
			'title' => __('Display Pagination', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'yes',
			'class' => 'fgf_gift_carousel_display_type',
			'id' => $this->get_option_key('carousel_pagination'),
		);
		$section_fields[] = array(
			'title' => __('Display Controls', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'yes',
			'class' => 'fgf_gift_carousel_display_type',
			'id' => $this->get_option_key('carousel_navigation'),
		);
		$section_fields[] = array(
			'title' => __('Navigation Previous Text', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => '<',
			'class' => 'fgf_gift_carousel_display_type fgf_carousel_navigation_type',
			'id' => $this->get_option_key('carousel_navigation_prevoius_text'),
		);
		$section_fields[] = array(
			'title' => __('Navigation Next Text', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => '>',
			'class' => 'fgf_gift_carousel_display_type fgf_carousel_navigation_type',
			'id' => $this->get_option_key('carousel_navigation_next_text'),
		);
		$section_fields[] = array(
			'title' => __('Auto Play', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'yes',
			'class' => 'fgf_gift_carousel_display_type',
			'id' => $this->get_option_key('carousel_auto_play'),
		);
		$section_fields[] = array(
			'title' => __('Slide Speed in Milliseconds', 'free-gifts-for-woocommerce'),
			'type' => 'number',
			'default' => '5000',
			'class' => 'fgf_gift_carousel_display_type fgf_carousel_auto_play',
			'custom_attributes' => array( 'min' => '1' ),
			'id' => $this->get_option_key('carousel_slide_speed'),
		);
		$section_fields[] = array(
			'title' => __('Gift Product Name Display Method', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_display_product_linkable'),
			'class' => 'fgf_gift_table_display_type fgf_gift_carousel_display_type',
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Only Product Name', 'free-gifts-for-woocommerce'),
				'2' => __('Product Name with Hyperlink to Product Page', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Select whether to Display the Gift Product Name as Name Only/Name with Hyperlink to Product Page', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Gift product display when choosing select-box', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_dropdown_display_type'),
			'class' => 'fgf_gift_dropdown_display_type',
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Display product name only', 'free-gifts-for-woocommerce'),
				'2' => __('Display product name and image', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Select whether the Gift Products should be displayed with the product name only or product name and image in the select-box', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Gift Product Add to Cart Method', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('dropdown_add_to_cart_behaviour'),
			'class' => 'fgf_gift_dropdown_display_type',
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Manual', 'free-gifts-for-woocommerce'),
				'2' => __('Automatic', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Automatic: Gift Product will be added to cart automatically once the Gift Product is selected in the dropdown(select box). Manually: The Addd to Cart Button has to be clicked for adding the product to the cart.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Allow Quantity selection for Gift Product(s)', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_product_quantity_field_enabled'),
			'class' => 'fgf_gift_table_display_type fgf_gift_carousel_display_type',
			'type' => 'select',
			'default' => '2',
			'options' => array(
				'1' => __('Yes', 'free-gifts-for-woocommerce'),
				'2' => __('No', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Allow Quantity selection for the Manually selecting Gift Product(s)', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_gift_display_options',
		);
		// Gift display section end.

		return $section_fields;
	}

	/**
	 * Get the settings for notices section array.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public function notices_section_array() {
		$section_fields = array();
		// Notices general section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Notice Display Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_notices_general_options',
		);
		$section_fields[] = array(
			'title' => __('Free Gifts Notice Display Type', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('display_notice_mode'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Standard WooCommerce Notice', 'free-gifts-for-woocommerce'),
				'2' => __("Plugin's Own Notice", 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __("By default, Free Gift messages will be displayed in WooCommerce Notices. You can switch to Plugin's Own Notice if your theme doesn't support WooCommerce Notices.", 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Display Free Gifts Notice in Checkout Page', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'no',
			'id' => $this->get_option_key('enable_checkout_free_gift_notice'),
			'desc' => __('When enabled, a notice will be displayed in checkout page asking the users to choose their free gifts. This notice will hidden if the user has already chosen their free gifts on cart page.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_notices_general_options',
		);
		// Notices general section end.
		// Eligible notices section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Eligible Notices Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_eligible_notices_options',
		);
		$section_fields[] = array(
			'title' => __('Free Gift(s) Eligibility Notice Will be Displayed On', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('display_cart_notices_type'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Cart & Checkout', 'free-gifts-for-woocommerce'),
				'2' => __('Cart', 'free-gifts-for-woocommerce'),
				'3' => __('Checkout', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Choose where you want to display the Free Gift(s) eligibility notice.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Free Gift(s) Eligibility Notices Display Type', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('display_eligibility_notices_type'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Multiple Notices Display', 'free-gifts-for-woocommerce'),
				'2' => __('Single Notice Display', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('By default, All the Free Gift(s) Eligibility Notices will display. You can switch to Single Notice Display to show the First Match rule.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_eligible_notices_options',
		);
		// Eligible notices section end.

		return $section_fields;
	}

	/**
	 * Get the settings for progress bar section array.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public function progress_bar_section_array() {
		$section_fields = array();

		// Progess bar section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Gift Product selection Progress Bar Display Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_progress_bar_options',
		);
		$section_fields[] = array(
			'title' => __('Display Gift Product selection Progress Bar in the Cart', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'no',
			'id' => $this->get_option_key('cart_page_progress_bar_enabled'),
			'desc' => __('Positioning will work only for block based cart page. For positioning in block based cart page, edit the cart page and move the "Progress bar" block as per your needs.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Display Gift Product selection Progress Bar in the Checkout', 'free-gifts-for-woocommerce'),
			'type' => 'checkbox',
			'default' => 'no',
			'id' => $this->get_option_key('checkout_page_progress_bar_enabled'),
			'desc' => __('Positioning will work only for block based checkout page. For positioning in block based checkout page, edit the checkout page and move the "Progress bar" block as per your needs.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_progress_bar_options',
		);
		// Progess bar section end.

		return $section_fields;
	}

	/**
	 * Get the settings for advanced section array.
	 *
	 * @return array
	 */
	public function advanced_section_array() {
		$section_fields = array();

		// Trobuleshoot section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Troubleshoot', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_trobuleshoot_options',
		);
		$section_fields[] = array(
			'title' => __('Free Gift Product Price', 'free-gifts-for-woocommerce'),
			'type' => 'fgf_custom_fields',
			'fgf_field' => 'price',
			'default' => '0',
			'desc_tip' => true,
			'desc' => __('The value configured here will be considered as price for the Gift Products and used for Legal as well as Payment Gateway validation.', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('gift_product_price'),
		);
		$section_fields[] = array(
			'title' => __('Frontend Scripts Enqueued on', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('frontend_enqueue_scripts_type'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('Header', 'free-gifts-for-woocommerce'),
				'2' => __('Footer', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('Choose whether the frontend scripts has to be loaded on Header/Footer', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Restrict Redirection when Free Gifts are Added', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('restrict_redirection_after_gifts_added'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('No', 'free-gifts-for-woocommerce'),
				'2' => __('Yes', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('If you are facing any issues (display issues in mini cart, sliders, incorrect URL redirection) when free gifts are added, then select "Yses" option and check.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_trobuleshoot_options',
		);
		// Custom CSS section end.
		// Delete data section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Deletion Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_delete_data_options',
		);
		$section_fields[] = array(
			'title' => __('Delete Master log(s) after X Duration', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('master_log_deletion'),
			'type' => 'select',
			'default' => '2',
			'options' => array(
				'1' => __('Yes', 'free-gifts-for-woocommerce'),
				'2' => __('No', 'free-gifts-for-woocommerce'),
			),
			'desc_tip' => true,
			'desc' => __('If "Yes" is selected, Master log entries will be removed after a specific duration.', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Delete Master log(s) after', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('master_log_deletion_duration'),
			'type' => 'fgf_custom_fields',
			'fgf_field' => 'relative_date_selector',
			'default' => array( 'number' => 1, 'unit' => 'years' ),
			'periods' => array(
				'days' => __('Day(s)', 'free-gifts-for-woocommerce'),
				'weeks' => __('Week(s)', 'free-gifts-for-woocommerce'),
				'months' => __('Month(s)', 'free-gifts-for-woocommerce'),
				'years' => __('Year(s)', 'free-gifts-for-woocommerce'),
			),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_delete_data_options',
		);
		// Delete data section end.
		// Cron section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Cron Information', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_cron_options',
		);
		$section_fields[] = array(
			'type' => 'fgf_display_cron_information',
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_cron_options',
		);
		// Cron section end.
		// Custom CSS section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Custom CSS', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_custom_css_options',
		);
		$section_fields[] = array(
			'title' => __('Custom CSS', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => '',
			'custom_attributes' => array( 'rows' => 10 ),
			'id' => $this->get_option_key('custom_css'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_custom_css_options',
		);
		// Custom CSS section end.

		return $section_fields;
	}

	/**
	 * Get the settings for notifications section array.
	 *
	 * @return array
	 */
	public function notifications_section_array() {
		$section_fields = array();

		// Email settings section start
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Email Settings', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_email_options',
		);
		$section_fields[] = array(
			'title' => __('Email Type', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('email_template_type'),
			'type' => 'select',
			'default' => '1',
			'options' => array(
				'1' => __('HTML', 'free-gifts-for-woocommerce'),
				'2' => __('WooComerce Template', 'free-gifts-for-woocommerce'),
			),
		);
		$section_fields[] = array(
			'title' => __('From Name', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('email_from_name'),
			'type' => 'text',
			'default' => get_option('woocommerce_email_from_name'),
		);
		$section_fields[] = array(
			'title' => __('From Address', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('email_from_address'),
			'type' => 'text',
			'default' => get_option('woocommerce_email_from_address'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_email_options',
		);
		// Email settings section end
		// Manual Gift Email section start
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Manual Gift Email', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_manual_gift_email_options',
		);
		$section_fields[] = array(
			'title' => __('Enable/Disable', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('enable_manual_gift_email'),
			'type' => 'checkbox',
			'default' => 'yes',
		);
		$section_fields[] = array(
			'title' => __('Subject', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('manual_gift_email_subject'),
			'type' => 'text',
			'class' => 'fgf_manual_gift_email',
			'default' => '{site_name}  - Free Gift Received',
			'desc' => __('<b>Supported Shortcodes:</b></br>{site_name} - To show the site name', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Message', 'free-gifts-for-woocommerce'),
			'id' => $this->get_option_key('manual_gift_email_message'),
			'type' => 'fgf_custom_fields',
			'fgf_field' => 'wpeditor',
			'class' => 'fgf_manual_gift_email',
			'default' => 'Hi {user_name},

You have received the following Product(s) as a Gift from the Site Admin.

{free_gifts}

Thanks',
			'desc' => __('<b>Supported Shortcodes:</b></br>{user_name} - To show the username</br>{free_gifts} - To show the free gifts details</br>{order_id} - To show the order ID', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_manual_gift_email_options',
		);
		// Manual Gift Email section end

		return $section_fields;
	}

	/**
	 * Get the settings for localizations section array.
	 *
	 * @return array
	 */
	public function localizations_section_array() {
		$section_fields = array();

		// Gifts localizations section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Gift Product Selection Localization', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_gifts_localizations_options',
		);
		$section_fields[] = array(
			'title' => __('Gift Product Selection Section Heading', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Choose Your Gift(s)',
			'id' => $this->get_option_key('free_gift_heading_label'),
		);
		$section_fields[] = array(
			'title' => __('Gift Product Add to Cart Button Label', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Add to Cart',
			'id' => $this->get_option_key('free_gift_add_to_cart_button_label'),
		);
		$section_fields[] = array(
			'title' => __('Gift Product Selection Label - Select Box(Dropdown)', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Please select a Gift',
			'id' => $this->get_option_key('free_gift_dropdown_default_option_label'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_gifts_localizations_options',
		);
		// Gifts localizations section end.
		// Cart page localizations section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Cart Page Localization', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_cart_page_localizations_options',
		);
		$section_fields[] = array(
			'title' => __('Cart Gift Type Label', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Type',
			'id' => $this->get_option_key('free_gift_cart_item_type_localization'),
		);
		$section_fields[] = array(
			'title' => __('Cart Free Gift Label', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Free Product',
			'id' => $this->get_option_key('free_gift_cart_item_type_value_localization'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_cart_page_localizations_options',
		);
		// Cart page localizations section end.
		// Progress bar localizations section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Progress Bar Localization', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_progress_bar_localizations_options',
		);
		$section_fields[] = array(
			'title' => __("Progress Bar's Heading", 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Gift Product Selection Info',
			'id' => $this->get_option_key('progress_bar_heading_label'),
		);
		$section_fields[] = array(
			'title' => __('Maximum Gift Count Label', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Maximum Gift Count: [maximum_gift_count]',
			'id' => $this->get_option_key('progress_bar_maximum_gift_count_label'),
			'desc' => __('<b>Supported Shortcodes:</b></br>[maximum_gift_count] - To show the Maximum gift count', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Added Gift Count Label', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Added Gift Count: [added_gift_count]',
			'id' => $this->get_option_key('progress_bar_added_gift_count_label'),
			'desc' => __('<b>Supported Shortcodes:</b></br>[added_gift_count] - To show the Added gift count', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Remaining Gift Count Label', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Remaining Gift Count: [remaining_gift_count]',
			'id' => $this->get_option_key('progress_bar_remaining_gift_count_label'),
			'desc' => __('<b>Supported Shortcodes:</b></br>[remaining_gift_count] - To show the remaining gift count', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_progress_bar_localizations_options',
		);
		// Progress bar localizations section end.
		return $section_fields;
	}

	/**
	 * Get the settings for messages section array.
	 *
	 * @return array
	 */
	public function messages_section_array() {
		$section_fields = array();

		// Cart and checkout page messages section start
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Cart & Checkout Page Messages', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_cart_checkout_page_messages_options',
		);
		$section_fields[] = array(
			'title' => __('Free Gift Notice in Cart/Checkout - Inline Mode', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Based on your Current Cart Contents, you are eligible for Free Gift(s). Choose your gift from the Table below.',
			'id' => $this->get_option_key('free_gift_notice_message'),
			'desc' => __('<b>Supported Shortcodes:</b></br>[remaining_gift_count] - To show the remaining gift count that need to be manually added by the customer', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Free Gift Notice in Cart/Checkout - Pop-up Mode', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Based on your Current Cart Contents, you are eligible for Free Gift(s). Choose your gift [popup_link].',
			'id' => $this->get_option_key('free_gift_popup_notice_message'),
			'desc' => __('<b>Supported Shortcodes:</b></br>[remaining_gift_count] - To show the remaining gift count that need to be manually added by the customer', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Popup Link Shortcode Label', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Here',
			'id' => $this->get_option_key('free_gift_popup_link_message'),
		);
		$section_fields[] = array(
			'title' => __('Free Gift Checkout Page Notice', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'class' => 'fgf_checkout_free_gift_notice',
			'default' => 'Based on your Current Cart Contents, you are eligible for Free Gift(s). Choose your gift on [cart_page].',
			'id' => $this->get_option_key('checkout_free_gift_notice_message'),
			'desc' => __('<b>Supported Shortcodes:</b></br>[cart_page] - To show the cart page link as a button', 'free-gifts-for-woocommerce'),
		);
		$section_fields[] = array(
			'title' => __('Cart Link Shortcode Label', 'free-gifts-for-woocommerce'),
			'type' => 'text',
			'default' => 'Cart Page',
			'class' => 'fgf_checkout_free_gift_notice',
			'id' => $this->get_option_key('checkout_free_gift_notice_shortcode_message'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_cart_checkout_page_messages_options',
		);
		// Cart and checkout page messages section end.
		// Shortcode page messages section start
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Shortcode Messages', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_shortcode_messages_options',
		);
		$section_fields[] = array(
			'title' => __("Message - If the criteria don't match to Gift the Product / the cart is empty", 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'As of now no Gift Product(s) available based on your Cart Content',
			'id' => $this->get_option_key('shortcode_free_gift_empty_message'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_shortcode_messages_options',
		);
		// Shortcode messages section end.
		// Success messages section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Success Messages', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_success_messages_options',
		);
		$section_fields[] = array(
			'title' => __('Manual Gift', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Gift product added successfully',
			'id' => $this->get_option_key('free_gift_success_message'),
		);
		$section_fields[] = array(
			'title' => __('Automatic Gift', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Gift product(s) has been added to your cart based on your cart contents.',
			'id' => $this->get_option_key('free_gift_automatic_success_message'),
		);
		$section_fields[] = array(
			'title' => __('Buy X Get Y - Automatic', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Gift product(s) has been added to your cart based on your cart contents.',
			'id' => $this->get_option_key('free_gift_bogo_success_message'),
		);
		$section_fields[] = array(
			'title' => __('Coupon based Gift - Automatic', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Gift product(s) has been added to your cart based on your cart contents.',
			'id' => $this->get_option_key('free_gift_coupon_success_message'),
		);
		$section_fields[] = array(
			'title' => __('Subtotal-based Gift - Automatic', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Gift product(s) has been added to your cart based on your cart contents',
			'id' => $this->get_option_key('subtotal_based_free_gifts_success_message'),
		);
		$section_fields[] = array(
			'title' => __('Bulk Pricing-based Gift - Automatic', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Gift product(s) has been added to your cart based on your cart contents',
			'id' => $this->get_option_key('bulk_pricing_based_free_gifts_success_message'),
		);
		$section_fields[] = array(
			'title' => __('Bulk Quanttity-based Gift - Automatic', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Gift product(s) has been added to your cart based on your cart contents',
			'id' => $this->get_option_key('bulk_quantity_based_free_gifts_success_message'),
		);
		$section_fields[] = array(
			'title' => __('Cheapest Gift-based Gift - Automatic', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Gift product(s) has been added to your cart based on your cart contents',
			'id' => $this->get_option_key('cheapest_gift_based_free_gifts_success_message'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_success_messages_options',
		);
		// Success messages section end.
		// Error messages section start.
		$section_fields[] = array(
			'type' => 'title',
			'title' => __('Error Messages', 'free-gifts-for-woocommerce'),
			'id' => 'fgf_error_messages_options',
		);
		$section_fields[] = array(
			'title' => __('Gift Product not selected in the Select Box(Dropdown) Display', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Please select a Gift',
			'id' => $this->get_option_key('gift_product_dropdown_valid_message'),
		);
		$section_fields[] = array(
			'title' => __("Gift Product(s) removed if the criteria don't matched", 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'Your Free Gift(s) were removed because your current cart contents is not eligible for a free gift',
			'id' => $this->get_option_key('free_gift_error_message'),
		);
		$section_fields[] = array(
			'title' => __('Coupon based Gift limitation', 'free-gifts-for-woocommerce'),
			'type' => 'textarea',
			'default' => 'The Maximum Gift Limit has been reached and the coupon cannot be applied',
			'id' => $this->get_option_key('free_gift_coupon_restriction_error_message'),
		);
		$section_fields[] = array(
			'type' => 'sectionend',
			'id' => 'fgf_error_messages_options',
		);
		// Error messages section end.

		return $section_fields;
	}

	/**
	 * Display the server cron information.
	 * */
	public function display_cron_information() {
		$master_log_deletion_date = get_option('fgf_master_log_deletion_last_updated_date');

		$server_cron_info = array(
			'master_log_deletion' => array(
				'cron' => __('Master Log Deletion Cron', 'free-gifts-for-woocommerce'),
				'last_updated_date' => self::format_last_updated_date($master_log_deletion_date),
			),
		);

		include_once FGF_ABSPATH . 'inc/admin/menu/views/html-cron-info.php';
	}

	/**
	 * Format the last update date.
	 *
	 * @return string.
	 * */
	public function format_last_updated_date( $date ) {
		if (empty($date)) {
			return __('Cron not Triggered', 'free-gifts-for-woocommerce');
		}

		return FGF_Date_Time::get_wp_format_datetime_from_gmt($date, false, ' ', true);
	}
}

return new FGF_Settings_Tab();
