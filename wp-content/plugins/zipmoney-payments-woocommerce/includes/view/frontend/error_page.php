<?php
get_header();
?>
	<div class="wrap">
		<div id="primary" class="content-area">
			<div id="content" class="site-content" role="main">

				<header class="page-header">
					<h1 class="page-title"><?php esc_attr_e( 'An error occurred' ); ?></h1>
				</header>

				<div class="page-wrapper">
					<div class="page-content">
						<h2><?php esc_attr_e( 'This is somewhat embarrassing, isn&rsquo;t it?' ); ?></h2>
						<p><?php esc_attr_e( 'It looks like an error occurred while processing your transaction. Please contact zipMoney for further details.' ); ?></p>
					</div><!-- .page-content -->
				</div>

			</div><!-- .site-main -->
		</div><!-- .content-area -->
	</div>
<?php get_footer(); ?>
