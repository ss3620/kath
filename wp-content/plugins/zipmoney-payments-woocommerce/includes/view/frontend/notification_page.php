<?php
get_header();
?>
<div class="wrap">
	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">

			<header class="page-header">
				<h1 class="page-title"><?php echo esc_html( $title ); ?></h1>
			</header>
			<div class="page-wrapper">
				<div class="page-content">
					<p><?php echo esc_html( $content ); ?></p>
				</div><!-- .page-content -->
			</div>
		</div><!-- .site-main -->
	</div><!-- .content-area -->
</div>
<?php get_footer(); ?>
