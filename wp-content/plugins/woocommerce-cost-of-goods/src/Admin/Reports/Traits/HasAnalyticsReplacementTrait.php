<?php

namespace SkyVerge\WooCommerce\COG\Admin\Reports\Traits;

trait HasAnalyticsReplacementTrait
{
	protected function renderAnalyticsNotice() : void
	{
		?>
		<div class="notice notice-info">
			<p>
				<?php
				printf(
				/* translators: %1$s opening link tag; %2$s closing link tag */
					esc_html__('This report is now available in %1$sWooCommerce Analytics%2$s.', 'woocommerce-cost-of-goods'),
					'<a href="'.esc_url($this->getNewAnalyticsUrl()).'">',
					'</a>'
				)
				?>
			</p>
		</div>
		<?php
	}

	protected function getNewAnalyticsUrl() : string
	{
		return admin_url('admin.php?page=wc-admin&path=/analytics/'.urlencode($this->getAnalyticsReportSlug()));
	}

	/**
	 * Should return the slug of the new, equivalent analytics page. Used to build the analytics URL.
	 */
	abstract protected function getAnalyticsReportSlug() : string;
}
