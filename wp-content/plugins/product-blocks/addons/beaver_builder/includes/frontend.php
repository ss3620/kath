<?php
defined( 'ABSPATH' ) || exit;

$id = $settings->template;
?>
<div class="wopb-shortcode" data-postid="<?php echo esc_attr( $id ); ?>">
	<?php
	if ( $id ) {
		$args      = array(
			'p'         => $id,
			'post_type' => 'wopb_templates',
		);
		$the_query = new \WP_Query( $args );
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				the_content();
			}
			wp_reset_postdata();
		}
	} elseif ( isset( $_GET['fl_builder'] ) ) {
		// phpcs:ignore
			echo '<p style="text-align:center;">' . sprintf(
					/* translators: %s: is no of template */
				esc_html__(
					'Pick a Template from your saved ones. Or create a template from: %s.',
					'product-blocks'
				) . ' ',
				'<strong><i>' . esc_html__( 'Dashboard > WowStore > Saved Templates', 'product-blocks' ) . '</i></strong>'
			) . '</p>';
	}
	?>
</div>
