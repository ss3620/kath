<?php
/**
 * SURFACE: dashboard admin notices. Siblings: hellobar.php, plugin-meta.php.
 *
 * To rotate a promo, copy an entry and change its `type`, `key`, dates and
 * payload. `type` picks the template (templates/<type>.php).
 *
 * - Dates are 'Y-m-d H:i e', Asia/Dhaka, `end` at 23:59.
 * - `key` must be unique — it is the dismissal transient, so a duplicate means
 *   dismissing one promo also dismisses the other.
 * - $config, $prefix, $asset_url, $brand_name, $brand_color come from
 *   Notice::get_promos(). Never hardcode those values here.
 * - Only UTM `medium` varies per promo; `source`/`campaign` are in config.php.
 * - `content_heading` is optional; omit it rather than passing an empty string.
 */

use REVX\Includes\Durbin\Xpo;

defined( 'ABSPATH' ) || exit;

return array(

	// -- split-countdown --------------------------------------------------
	array(
		'type'               => 'split-countdown',
		'key'                => $prefix . '_spring_sale_2026_1',
		'start'              => '2026-04-05 00:00 Asia/Dhaka',
		'end'                => '2026-04-14 23:59 Asia/Dhaka',
		'brand_color'        => $brand_color,
		'left_image'         => $asset_url . 'dashboard_banner/spring_sale/left_image.png',
		'right_image'        => $asset_url . 'dashboard_banner/spring_sale/right_image.png',
		'bg_image'           => $asset_url . 'dashboard_banner/spring_sale/bg.png',
		'text'               => 'Hurry Before It Ends!',
		'countdown_duration' => 259200, // Duration in seconds.
		'countdown_color'    => '#000',
		'url'                => Xpo::generate_utm_link(
			array(
				'config' => array(
					'source'   => $config['utm_source'],
					'medium'   => 'spring-sale',
					'campaign' => $config['utm_campaign'],
				),
			)
		),
		'visibility'         => ! Xpo::is_lc_active(),
	),

	// -- icon-message -----------------------------------------------------
	array(
		'type'               => 'icon-message',
		'key'                => $prefix . '_dashboard_content_notice_summer_sale_vv2',
		'start'              => '2026-07-06 00:00 Asia/Dhaka',
		'end'                => '2026-07-12 23:59 Asia/Dhaka',
		'url'                => Xpo::generate_utm_link(
			array(
				'config' => array(
					'source'   => $config['utm_source'],
					'medium'   => 'summer-sale',
					'campaign' => $config['utm_campaign'],
				),
			)
		),
		'visibility'         => ! Xpo::is_lc_active(),
		'content_subheading' => $brand_name . __( ' Summer Sale Offer is Live - Enjoy Up to %s NOW.', 'revenue' ),
		'discount_content'   => ' 55% Off',
		'border_color'       => $brand_color,
		'icon'               => $asset_url . 'dashboard_banner/discount.svg',
		'button_text'        => __( 'Upgrade Now', 'revenue' ),
		'is_discount_logo'   => true,
	),
	array(
		'type'               => 'icon-message',
		'key'                => $prefix . '_dashboard_content_notice_summer_sale_vv1',
		'start'              => '2026-07-20 00:00 Asia/Dhaka',
		'end'                => '2026-08-01 23:59 Asia/Dhaka',
		'url'                => Xpo::generate_utm_link(
			array(
				'config' => array(
					'source'   => $config['utm_source'],
					'medium'   => 'summer-sale',
					'campaign' => $config['utm_campaign'],
				),
			)
		),
		'visibility'         => ! Xpo::is_lc_active(),
		'content_subheading' => $brand_name . __( ' Summer Sale Offer is Live - Enjoy Up to %s NOW.', 'revenue' ),
		'discount_content'   => ' 55% Off',
		'border_color'       => $brand_color,
		'icon'               => $asset_url . 'dashboard_banner/logo.svg',
		'button_text'        => __( 'Upgrade Now', 'revenue' ),
		'is_discount_logo'   => true,
	),
	array(
		'type'               => 'icon-message',
		'key'                => $prefix . '_dashboard_content_notice_summer_sale_vv3',
		'start'              => '2026-08-09 00:00 Asia/Dhaka',
		'end'                => '2026-08-16 23:59 Asia/Dhaka',
		'url'                => Xpo::generate_utm_link(
			array(
				'config' => array(
					'source'   => $config['utm_source'],
					'medium'   => 'summer-sale',
					'campaign' => $config['utm_campaign'],
				),
			)
		),
		'visibility'         => ! Xpo::is_lc_active(),
		'content_subheading' => $brand_name . __( ' Summer Sale Offer is Live - Enjoy Up to %s NOW.', 'revenue' ),
		'discount_content'   => ' 55% Off',
		'border_color'       => $brand_color,
		'icon'               => $asset_url . 'dashboard_banner/logo.svg',
		'button_text'        => __( 'Upgrade Now', 'revenue' ),
		'is_discount_logo'   => true,
	),

	// -- image-only ------------------------------------------------------
	array(
		'type'        => 'image-only',
		'key'         => $prefix . '_summer_sale_2026_v1',
		'start'       => '2026-07-13 00:00 Asia/Dhaka',
		'end'         => '2026-07-19 23:59 Asia/Dhaka',
		'banner_src'  => $asset_url . 'dashboard_banner/summer_sale/summer_sale_26.png',
		'url'         => Xpo::generate_utm_link(
			array(
				'config' => array(
					'source'   => $config['utm_source'],
					'medium'   => 'summer-sale',
					'campaign' => $config['utm_campaign'],
				),
			)
		),
		'close_color' => '#000000',
		'visibility'  => ! Xpo::is_lc_active(),
	),
	array(
		'type'        => 'image-only',
		'key'         => $prefix . '_summer_sale_2026_v2',
		'start'       => '2026-08-02 00:00 Asia/Dhaka',
		'end'         => '2026-08-08 23:59 Asia/Dhaka',
		'banner_src'  => $asset_url . 'dashboard_banner/summer_sale/summer_sale_26.png',
		'url'         => Xpo::generate_utm_link(
			array(
				'config' => array(
					'source'   => $config['utm_source'],
					'medium'   => 'summer-sale',
					'campaign' => $config['utm_campaign'],
				),
			)
		),
		'close_color' => '#000000',
		'visibility'  => ! Xpo::is_lc_active(),
	),

	// WowRecommend pre-order — cross-product promo, so it is not gated on the
	// WowRevenue license.
	array(
		'type'        => 'image-only',
		'key'         => $prefix . '_preco_sale_campaign_262_1',
		'start'       => '2026-08-23 00:00 Asia/Dhaka',
		'end'         => '2026-08-29 23:59 Asia/Dhaka',
		'banner_src'  => $asset_url . 'dashboard_banner/wowrecommend_banner/preco_banner_insider_deal.png',
		'url'         => Xpo::generate_utm_link(
			array(
				'url'    => 'https://www.wpxpo.com/product/wowrecommend/',
				'config' => array(
					'source'   => $config['utm_source'],
					'medium'   => 'insider-deal',
					'campaign' => $config['utm_campaign'],
				),
			)
		),
		'close_color' => '#ffffff',
		'visibility'  => ! Xpo::is_lc_active(),
	),
	array(
		'type'        => 'image-only',
		'key'         => $prefix . '_preco_sale_campaign_262_2',
		'start'       => '2026-08-30 00:00 Asia/Dhaka',
		'end'         => '2026-09-19 23:59 Asia/Dhaka',
		'banner_src'  => $asset_url . 'dashboard_banner/wowrecommend_banner/preco_banner_early_bird.png',
		'url'         => Xpo::generate_utm_link(
			array(
				'url'    => 'https://www.wpxpo.com/product/wowrecommend/',
				'config' => array(
					'source'   => $config['utm_source'],
					'medium'   => 'early-bird',
					'campaign' => $config['utm_campaign'],
				),
			)
		),
		'close_color' => '#ffffff',
		'visibility'  => ! Xpo::is_lc_active(),
	),
);

/*
 * ---------------------------------------------------------------------------
 * BLANKS — copy one into the array above and uncomment. One per design.
 * Optional on any entry: 'repeat_interval' => WEEK_IN_SECONDS (dismissal
 * expires after this long instead of for good).
 * ---------------------------------------------------------------------------
 *
 * // split-countdown — art both flanks, live text + timer centered.
 * array(
 *     'type'               => 'split-countdown',
 *     'key'                => $prefix . '_autumn_sale_2026',
 *     'start'              => '2026-09-01 00:00 Asia/Dhaka',
 *     'end'                => '2026-09-10 23:59 Asia/Dhaka',
 *     'brand_color'        => $brand_color,
 *     'bg_image'           => $asset_url . 'dashboard_banner/autumn_sale/bg.png',
 *     'left_image'         => $asset_url . 'dashboard_banner/autumn_sale/left_image.png',
 *     'right_image'        => $asset_url . 'dashboard_banner/autumn_sale/right_image.png',
 *     'text'               => __( 'Hurry Before It Ends!', 'revenue' ),
 *     'countdown_duration' => 259200, // Seconds.
 *     'countdown_color'    => '#000',
 *     'url'                => Xpo::generate_utm_link(
 *         array(
 *             'config' => array(
 *                 'source'   => $config['utm_source'],
 *                 'medium'   => 'autumn-sale',
 *                 'campaign' => $config['utm_campaign'],
 *             ),
 *         )
 *     ),
 *     'visibility'         => ! Xpo::is_lc_active(),
 * ),
 *
 * // icon-message — icon + text + button, no artwork.
 * // `content_subheading` needs a %s; `discount_content` fills it.
 * // `is_discount_logo` true = discount SVG styling, false = company logo.
 * array(
 *     'type'               => 'icon-message',
 *     'key'                => $prefix . '_dashboard_content_notice_autumn_2026',
 *     'start'              => '2026-09-01 00:00 Asia/Dhaka',
 *     'end'                => '2026-09-10 23:59 Asia/Dhaka',
 *     'content_heading'    => __( 'Autumn Sale', 'revenue' ),
 *     'content_subheading' => $brand_name . __( ' Autumn Sale is Live - Enjoy Up to %s NOW.', 'revenue' ),
 *     'discount_content'   => ' 55% Off',
 *     'border_color'       => $brand_color,
 *     'icon'               => $asset_url . 'dashboard_banner/logo.svg',
 *     'button_text'        => __( 'Upgrade Now', 'revenue' ),
 *     'is_discount_logo'   => true,
 *     'url'                => Xpo::generate_utm_link(
 *         array(
 *             'config' => array(
 *                 'source'   => $config['utm_source'],
 *                 'medium'   => 'autumn-sale',
 *                 'campaign' => $config['utm_campaign'],
 *             ),
 *         )
 *     ),
 *     'visibility'         => ! Xpo::is_lc_active(),
 * ),
 *
 * // image-only — one full-width artwork, no live text.
 * array(
 *     'type'        => 'image-only',
 *     'key'         => $prefix . '_autumn_sale_2026_banner',
 *     'start'       => '2026-09-01 00:00 Asia/Dhaka',
 *     'end'         => '2026-09-10 23:59 Asia/Dhaka',
 *     'banner_src'  => $asset_url . 'dashboard_banner/autumn_sale/banner.png',
 *     'close_color' => '#000000',
 *     'url'         => Xpo::generate_utm_link(
 *         array(
 *             'config' => array(
 *                 'source'   => $config['utm_source'],
 *                 'medium'   => 'autumn-sale',
 *                 'campaign' => $config['utm_campaign'],
 *             ),
 *         )
 *     ),
 *     'visibility'  => ! Xpo::is_lc_active(),
 * ),
 */
