<?php
/**
 * Affiliate For WooCommerce About/Welcome/Landing page
 *
 * @package   affiliate-for-woocommerce/includes/admin/
 * @since     1.0.0
 * @version   1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_data = Affiliate_For_WooCommerce::get_plugin_data();
?>
<style type="text/css">
	.wrap.about-wrap h1#afwc-heading {
		margin-right: 0;
	}
	.afwc {
		margin-bottom: 2rem;
	}
	.afwc .about-text {
		margin-right: 0;
	}
	.wrap.about-wrap,
	.has-2-columns.feature-section.col.two-col {
		max-width: unset !important;
	}
	.afwc .column {
		margin-left: 0;
	}
	.afwc .column.last-feature {
		margin-right: 0;
	}
	.about-wrap .afwc .button-hero {
		color: #FFF !important;
		border-color: #03a025 !important;
		background: #03a025 !important;
		box-shadow: 0 1px 0 #03a025;
		font-weight: bold;
	}
	.about-wrap .afwc .button-hero:hover {
		color: #FFF !important;
		background: #0aab2e !important;
		border-color: #0aab2e !important;
	}
	.afwc-starter {
		background: #FFF;
		padding: 1.5rem;
		border-radius: 0.75rem;
		box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
		margin: 1.5rem 0;
	}
	.about-wrap h3.afwc-starter-title {
		margin-top: 0;
		margin-bottom: 0.35rem;
		font-size: 1.25rem;
		font-weight: 600;
	}
	.afwc-starter-desc {
		margin: 0 0 1.25rem 0;
		color: #646970;
		font-size: 0.95rem;
		line-height: 1.5;
	}
	.afwc-guides {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 0.75rem;
	}
	.afwc-guide-link {
		display: flex;
		align-items: center;
		gap: 0.6rem;
		padding: 0.75rem 0.85rem;
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		border-radius: 0.5rem;
		text-decoration: none !important;
		color: #2c3338 !important;
		box-sizing: border-box;
		transition: border-color 0.15s ease-in-out, background-color 0.15s ease-in-out, color 0.15s ease-in-out;
	}
	.afwc-guide-link:hover,
	.afwc-guide-link:focus {
		background: #FFF;
		border-color: #2271b1;
		color: #2271b1 !important;
	}
	.afwc-title-icons {
		width: 1.25rem;
		height: 1.25rem;
		min-width: 1.25rem;
		color: #2271b1;
		flex-shrink: 0;
		display: block;
	}
	.afwc-guide-link:hover .afwc-title-icons,
	.afwc-guide-link:focus .afwc-title-icons {
		color: #135e96;
	}
	.afwc-guide-text {
		font-weight: 500;
		font-size: 0.9rem;
		line-height: 1.35;
		color: inherit;
		white-space: nowrap;
	}
	.afwc-grid {
		display: grid;
		grid-template-columns: 30fr 35fr 35fr;
		/* grid-template-columns: repeat(3, 1fr); */
		gap: 1.5rem;
		margin-bottom: 2rem;
	}
	.afwc-box {
		background: #FFF;
		padding: 1.5rem;
		border-radius: 0.75rem;
		box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
		box-sizing: border-box;
	}
	.about-wrap h3.afwc-box-title {
		margin-top: 0;
		margin-bottom: 1rem;
		font-size: 1.1rem;
		font-weight: 600;
	}
	.afwc-box-list {
		font-size: 1rem;
		margin: 0 0 0 1.25rem;
		padding: 0;
		line-height: 1.5;
	}
	.afwc-box-list li {
		margin-bottom: 0.25rem;
	}
	.afwc-box-list li:last-child {
		margin-bottom: 0;
	}
	.afwc-box-list a {
		text-decoration: none;
		font-size: 1rem;
	}
	.afwc-box-list a:hover,
	.afwc-box-list a:focus {
		text-decoration: underline;
	}
	.afwc-help {
		margin-top: 2.5rem;
	}
	.afwc-help h3 {
		margin-top: 0;
		margin-bottom: 0.5rem;
		font-size: 1.1rem;
	}
	.afwc-help .afwc-help-links a {
		margin-right: 0.25rem;
		margin-left: 0.25rem;
	}
	.afwc-help .afwc-help-links a:first-child {
		margin-left: 0;
	}
	.afwc-help .afwc-help-links a:last-child {
		margin-right: 0;
	}
	@media (max-width: 1200px) {
		.afwc-guides {
			grid-template-columns: repeat(2, 1fr);
		}
		.afwc-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	@media (max-width: 600px) {
		.afwc-guides {
			grid-template-columns: 1fr;
		}
		.afwc-grid {
			grid-template-columns: 1fr;
		}
		.afwc .has-2-columns {
			display: flex;
			flex-direction: column;
			align-items: flex-start;
			gap: 0.75rem;
		}
		.afwc .column p.alignleft,
		.afwc .column p.alignright {
			float: none;
			text-align: left;
			margin: 0;
		}
	}
</style>
<script type="text/javascript">
	jQuery( function(){
		jQuery('#toplevel_page_woocommerce').find('a[href$="admin.php?page=affiliate-for-woocommerce"]').addClass('current');
		jQuery('#toplevel_page_woocommerce').find('a[href$="admin.php?page=affiliate-for-woocommerce"]').parent().addClass('current');
	});
</script>

<div class="wrap about-wrap">
	<h1 id="afwc-heading"><?php echo esc_html__( 'Thank you for choosing Affiliate for WooCommerce', 'affiliate-for-woocommerce' ) . ' ' . esc_html( $plugin_data['Version'] ) . '!'; ?></h1>
	<?php
	// Some old migration code.
	if ( ( afwc_is_plugin_active( 'affiliates/affiliates.php' ) || afwc_is_plugin_active( 'affiliates-pro/affiliates-pro.php' ) ) && defined( 'AFFILIATES_TP' ) ) {
		$tables            = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . AFFILIATES_TP ) . '%' ), ARRAY_A ); // phpcs:ignore
		$show_notification = get_option( 'show_migrate_affiliates_notification', 'yes' );
		if ( ! empty( $tables ) && 'no' !== $show_notification ) {
			?>
			<div>
				<div>
					<?php echo esc_html__( 'We discovered that you are using another "Affiliates" plugin. Do you want to migrate your existing data to this new Affiliates for WooCommerce plugin?', 'affiliate-for-woocommerce' ); ?>
					<span class="migrate_affiliates_actions">
						<a href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'page'         => 'affiliate-for-woocommerce-settings',
										'migrate'      => 'affiliates',
										'is_from_docs' => 1,
									),
									admin_url( 'admin.php' )
								)
							);
							?>
									" class="button-primary" id="migrate_yes">
							<?php echo esc_html__( 'Yes, Migrate existing data.', 'affiliate-for-woocommerce' ); ?>
						</a>
						<a href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'page'         => 'affiliate-for-woocommerce-settings',
										'migrate'      => 'ignore_affiliates',
										'is_from_docs' => 1,
									),
									admin_url( 'admin.php' )
								)
							);
							?>
									" class="button" id="migrate_no">
							<?php echo esc_html__( 'No, I want to start afresh.', 'affiliate-for-woocommerce' ); ?>
						</a>
					</span>
					<p><?php echo esc_html__( 'Note: Once you migrate from Affiliates plugin, please deactivate it. Affiliates and Affiliate for WooCommerce can\'t work simultaneously.', 'affiliate-for-woocommerce' ); ?></p>
				</div>
			</div>
			<?php
		}
	}
	?>
	<div class="afwc">
		<p class="about-text"><?php echo esc_html__( "🏆 We're here to support your growth. Find everything you need to run and scale your affiliate program.", 'affiliate-for-woocommerce' ); ?></p>
		<div class="has-2-columns feature-section col two-col">
			<div class="is-vertically-aligned-center column col">
				<p class="alignleft">
					<?php
					echo '<a class="button button-hero" target="_blank" href="' . esc_url(
						add_query_arg(
							array(
								'page' => 'affiliate-for-woocommerce',
							),
							admin_url( 'admin.php' )
						)
					) . '">' . esc_html_x( 'Visit Dashboard', 'Link to the admin affiliate dashboard', 'affiliate-for-woocommerce' ) . '</a>';
					?>
				</p>
			</div>
			<div class="is-vertically-aligned-center column col last-feature">
				<p class="alignright">
					<a target="_blank" href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'page' => 'wc-settings',
								'tab'  => 'affiliate-for-woocommerce-settings',
							),
							admin_url( 'admin.php' )
						)
					);
					?>
					">
						<?php echo esc_html_x( 'Settings', 'link to view plugin settings', 'affiliate-for-woocommerce' ); ?></a> |
					<a target="_blank" href="<?php echo esc_url( AFWC_DOC_DOMAIN ); ?>"><?php echo esc_html_x( 'Docs', 'link to documentation', 'affiliate-for-woocommerce' ); ?></a> |
					<a target="_blank" href="<?php echo esc_url( AFW_CONTACT_SUPPORT_URL ); ?>"><?php echo esc_html_x( 'Contact us', 'link to contact support', 'affiliate-for-woocommerce' ); ?></a>
				</p>
			</div>
		</div>
		<div class="afwc-starter">
			<h3 class="afwc-starter-title"><?php echo esc_html__( '🚀 Getting started', 'affiliate-for-woocommerce' ); ?></h3>
			<p class="afwc-starter-desc"><?php echo esc_html__( 'Start with these essential steps to set up your affiliate program and get it up and running.', 'affiliate-for-woocommerce' ); ?></p>
			<div class="afwc-guides">
				<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . '#section-6' ); ?>" target="_blank" class="afwc-guide-link">
					<svg class="afwc-title-icons" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
					</svg>
					<span class="afwc-guide-text"><?php echo esc_html__( 'Add an affiliate', 'affiliate-for-woocommerce' ); ?></span>
				</a>
				<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-create-affiliate-commission-plans/' ); ?>" target="_blank" class="afwc-guide-link">
					<svg class="afwc-title-icons" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
					</svg>
					<span class="afwc-guide-text"><?php echo esc_html__( 'Set up commission rate', 'affiliate-for-woocommerce' ); ?></span>
				</a>
				<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-customize-affiliate-referral-link/' ); ?>" target="_blank" class="afwc-guide-link">
					<svg class="afwc-title-icons" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
					</svg>
					<span class="afwc-guide-text"><?php echo esc_html__( 'Find, customize & share referral links', 'affiliate-for-woocommerce' ); ?></span>
				</a>
				<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-payout-commissions-in-affiliate-for-woocommerce/' ); ?>" target="_blank" class="afwc-guide-link">
					<svg class="afwc-title-icons" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
					</svg>
					<span class="afwc-guide-text"><?php echo esc_html__( 'Set up payouts', 'affiliate-for-woocommerce' ); ?></span>
				</a>
			</div>
		</div>
		<div class="afwc-grid">
			<div class="afwc-box">
				<h3 class="afwc-box-title"><?php echo esc_html__( '🔥 More referral methods', 'affiliate-for-woocommerce' ); ?></h3>
				<ol class="afwc-box-list">
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-create-and-assign-coupons-to-affiliates/' ); ?>" target="_blank"><?php echo esc_html__( 'Referral coupons', 'affiliate-for-woocommerce' ); ?></a></li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-create-and-assign-affiliate-landing-pages/' ); ?>" target="_blank"><?php echo esc_html__( 'Landing pages', 'affiliate-for-woocommerce' ); ?></a></li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-set-up-direct-link-tracking/' ); ?>" target="_blank"><?php echo esc_html__( 'Direct/Domain tracking', 'affiliate-for-woocommerce' ); ?></a></li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-set-up-lifetime-commissions/' ); ?>" target="_blank"><?php echo esc_html__( 'Lifetime commissions', 'affiliate-for-woocommerce' ); ?></a></li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-set-different-affiliate-commission-rates-for-subscription-parent-and-renewal-orders/' ); ?>" target="_blank"><?php echo esc_html__( 'Subscription renewals', 'affiliate-for-woocommerce' ); ?></a></li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-assign-unassign-an-order-to-an-affiliate/' ); ?>" target="_blank"><?php echo esc_html__( 'Manual commissions', 'affiliate-for-woocommerce' ); ?></a></li>
				</ol>
			</div>
			<div class="afwc-box">
				<h3 class="afwc-box-title"><?php echo esc_html__( '📚 Setup guides', 'affiliate-for-woocommerce' ); ?></h3>
				<ol class="afwc-box-list">
					<li>
						<?php echo esc_html__( 'Set up', 'affiliate-for-woocommerce' ); ?>
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-create-affiliate-registration-form-to-let-users-sign-up-for-your-affiliate-program/' ); ?>" target="_blank"><?php echo esc_html__( 'registration form to let users sign up', 'affiliate-for-woocommerce' ); ?></a>
					</li>
					<li>
						<?php echo esc_html__( 'Set commissions by', 'affiliate-for-woocommerce' ); ?>
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-set-different-affiliate-commission-rates-for-affiliates/' ); ?>" target="_blank"><?php echo esc_html__( 'affiliate', 'affiliate-for-woocommerce' ); ?></a>,
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'affiliate-how-to-set-different-affiliate-commission-rates-for-affiliate-tags/' ); ?>" target="_blank"><?php echo esc_html__( 'tag', 'affiliate-for-woocommerce' ); ?></a>,
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-set-different-affiliate-commission-rates-for-product-or-product-category/' ); ?>" target="_blank"><?php echo esc_html__( 'product/product category', 'affiliate-for-woocommerce' ); ?></a>
					</li>
					<li>
						<?php echo esc_html__( 'Set up', 'affiliate-for-woocommerce' ); ?>
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-set-up-a-multilevel-referral-multi-tier-affiliate-program/' ); ?>" target="_blank"><?php echo esc_html__( 'multi-tier affiliate program', 'affiliate-for-woocommerce' ); ?></a>
					</li>
					<li>
						<?php echo esc_html__( 'Set up', 'affiliate-for-woocommerce' ); ?>
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-create-affiliate-marketing-campaigns/' ); ?>" target="_blank"><?php echo esc_html__( 'campaigns to share assets and creatives', 'affiliate-for-woocommerce' ); ?></a>
					</li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'faqs/#section-23' ); ?>" target="_blank"><?php echo esc_html__( 'Review and manage plugin emails', 'affiliate-for-woocommerce' ); ?></a></li>
					<li>
						<?php echo esc_html__( 'Pay commissions via', 'affiliate-for-woocommerce' ); ?>
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-pay-affiliate-commissions-via-paypal/' ); ?>" target="_blank"><?php echo esc_html__( 'PayPal', 'affiliate-for-woocommerce' ); ?></a>,
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-pay-affiliate-commissions-via-stripe/' ); ?>" target="_blank"><?php echo esc_html__( 'Stripe', 'affiliate-for-woocommerce' ); ?></a>,
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-pay-affiliate-commission-as-a-coupon/' ); ?>" target="_blank"><?php echo esc_html__( 'coupons', 'affiliate-for-woocommerce' ); ?></a>,
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-pay-store-credit-as-affiliate-commission/' ); ?>" target="_blank"><?php echo esc_html__( 'credits', 'affiliate-for-woocommerce' ); ?></a>,
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-manually-payout-commission-via-bank-transfer/' ); ?>" target="_blank"><?php echo esc_html__( 'etc', 'affiliate-for-woocommerce' ); ?></a>
					</li>
				</ol>
			</div>
			<div class="afwc-box">
				<h3 class="afwc-box-title"><?php echo esc_html__( '💡 How-to guides', 'affiliate-for-woocommerce' ); ?></h3>
				<ol class="afwc-box-list">
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-restrict-affiliate-commission-for-products-on-sale/' ); ?>" target="_blank"><?php echo esc_html__( 'Restrict commissions on sale products', 'affiliate-for-woocommerce' ); ?></a></li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-promote-affiliate-links-on-social-media/' ); ?>" target="_blank"><?php echo esc_html__( 'Promote affiliate links on social media', 'affiliate-for-woocommerce' ); ?></a></li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'affiliate-program-terms-and-conditions/' ); ?>" target="_blank"><?php echo esc_html__( 'Generate affiliate terms & conditions', 'affiliate-for-woocommerce' ); ?></a></li>
					<li>
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-export-affiliate-data-to-csv/' ); ?>" target="_blank"><?php echo esc_html__( 'Export affiliate data to CSV', 'affiliate-for-woocommerce' ); ?></a>
						<?php echo esc_html__( ' or ', 'affiliate-for-woocommerce' ); ?>
						<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-automatically-add-affiliates-to-email-list/' ); ?>" target="_blank"><?php echo esc_html__( 'sync with Mailchimp', 'affiliate-for-woocommerce' ); ?></a>
					</li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-to-override-templates/' ); ?>" target="_blank"><?php echo esc_html__( 'Override plugin templates', 'affiliate-for-woocommerce' ); ?></a></li>
					<li><a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'how-does-affiliate-for-woocommerce-plugin-work-with-caching/' ); ?>" target="_blank"><?php echo esc_html__( 'See how the plugin works with caching', 'affiliate-for-woocommerce' ); ?></a></li>
				</ol>
			</div>
			<?php do_action( 'afwc_welcome_page_doc_group_end' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment ?>
		</div>
		<div class="afwc-help">
			<h3><?php echo esc_html__( 'Need help?', 'affiliate-for-woocommerce' ); ?></h3>
			<div class="afwc-help-links">
				<a href="<?php echo esc_url( AFWC_DOC_DOMAIN . 'faqs/' ); ?>" target="_blank">
					<?php echo esc_html__( 'FAQs', 'affiliate-for-woocommerce' ); ?></a> •
				<a href="<?php echo esc_url( AFW_CONTACT_SUPPORT_URL ); ?>" target="_blank">
					<?php echo esc_html__( 'Contact support', 'affiliate-for-woocommerce' ); ?></a> •
				<a href="<?php echo esc_url( AFWC_CHANGELOG_URL ); ?>" target="_blank">
					<?php echo esc_html__( "What's new", 'affiliate-for-woocommerce' ); ?></a>
				<?php do_action( 'afwc_welcome_page_need_help_end' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment ?>
			</div>
		</div>
	</div>
</div>
<?php
