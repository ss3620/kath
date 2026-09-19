<?php
/**
 * LAYOUT: headline, blurb, a row of feature tags and one CTA, over a
 * full-bleed background image. Used to cross-promote a SIBLING PLUGIN rather
 * than an upgrade, so the CTA can install and activate it in place.
 *
 * Self-contained: markup, CSS and JS in this one file, every class built from
 * $prefix so a sibling plugin can copy it as-is. Nothing here knows *which*
 * plugin is being promoted — that is entirely the caller's payload.
 *
 * The odd one out among templates/: its data does NOT come from promos/, but
 * from Notice::render_sibling_plugin_notice(), because an evergreen entry in
 * promos/notice.php would hold this plugin's xpo_active_notice_lists slot
 * year-round and mute sibling plugins. See that method for the full reasoning.
 *
 * The CTA has two shapes, chosen by the data, not by logic here:
 *   'install_slug' => ''      -> a plain link to 'button_url'
 *   'install_slug' => 'slug'  -> installs/activates, then goes to 'button_url'
 *
 * EXTERNAL DEPENDENCY (the same kind the hello bar has with its REST path):
 * the install button posts to `<prefix>_install_plugin`, the AJAX action in
 * includes/durbin/class-our-plugins.php, with a `<prefix>-nonce`. Both names
 * derive from config.php's prefix, so there is no literal to port — but the
 * durbin folder must ship alongside this one, and 'install_slug' must be a key
 * Xpo::install_and_active_plugin() understands (e.g. 'wow_revenue').
 *
 * @var array  $notice      Promo entry (title, description, tags, bg_image,
 *                          accent_color, button_color, button_text, button_url,
 *                          install_slug).
 * @var array  $query_args  Dismiss-link query args.
 * @var string $prefix      Identity slug from config.php.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- template partial included directly into a method scope; these are local render-time variables/functions, not plugin globals.

$wrapper_class = $prefix . '-notice-wrapper';
$install_class = $prefix . '-plugin-install-notice';
$inner_class   = $prefix . '-plugin-install-notice__wrapper';
$title_class   = $prefix . '-plugin-install-notice__title';
$desc_class    = $prefix . '-plugin-install-notice__desc';
$tag_class     = $prefix . '-plugin-install-notice__tag';
$button_class  = $prefix . '-plugin-install-notice__button';
$close_class   = $prefix . '-plugin-install-notice__close';
$scope         = '.' . $wrapper_class . '.' . $install_class;

$accent_color = isset( $notice['accent_color'] ) ? $notice['accent_color'] : $brand_color;
$button_color = isset( $notice['button_color'] ) ? $notice['button_color'] : $brand_color;
$install_slug = isset( $notice['install_slug'] ) ? $notice['install_slug'] : '';
$tags         = isset( $notice['tags'] ) && is_array( $notice['tags'] ) ? $notice['tags'] : array();
?>
<style type="text/css">
	<?php echo esc_html( $scope ); ?> {
		margin: 20px 20px 0 2px;
		padding: 0 !important;
		border: 0;
		display: block;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $inner_class ); ?> {
		background-color: #fff;
		padding: 30px 40px;
		box-sizing: border-box;
		box-shadow: 0 0 16px 32px #585C5F1A;
		<?php if ( ! empty( $notice['bg_image'] ) ) : ?>
		background-image: url("<?php echo esc_url( $notice['bg_image'] ); ?>");
		background-repeat: no-repeat;
		background-position: 100% 100%;
		background-size: cover;
		<?php endif; ?>
		border-radius: 8px;
		position: relative;
		border: 0;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $title_class ); ?> {
		font-size: 24px;
		font-weight: 600;
		line-height: 32px;
		color: #0A0D14;
		margin-bottom: 8px;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $desc_class ); ?> {
		color: #525866;
		max-width: 664px;
		margin-bottom: 16px;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $tag_class ); ?>,
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $tag_class ); ?> div {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $tag_class ); ?> div {
		color: <?php echo esc_html( $accent_color ); ?>;
		font-weight: 400;
		text-decoration: none;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $tag_class ); ?> span {
		width: 6px;
		height: 6px;
		display: block;
		border-radius: 10px;
		background-color: <?php echo esc_html( $accent_color ); ?>;
		box-sizing: border-box;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $desc_class ); ?>,
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $button_class ); ?>,
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $tag_class ); ?> div {
		font-size: 14px;
		line-height: 20px;
		text-decoration: none;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $button_class ); ?> {
		color: #fff;
		border-radius: 8px;
		padding: 10px 20px !important;
		box-sizing: border-box;
		display: block;
		width: fit-content;
		margin-top: 24px;
		background-color: <?php echo esc_html( $button_color ); ?>;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $button_class ); ?>:focus,
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $button_class ); ?>:active,
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $button_class ); ?>:hover {
		color: #fff;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $inner_class ); ?>.is-loading {
		opacity: .6;
		pointer-events: none;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $close_class ); ?> {
		position: absolute;
		top: 12px;
		right: 12px;
		display: block;
		width: fit-content;
		text-decoration: none;
	}
	<?php echo esc_html( $scope ); ?> .<?php echo esc_html( $close_class ); ?> .dashicons-no-alt {
		font-size: 25px;
		height: 26px;
		width: 25px;
		cursor: pointer;
		color: #fff;
	}
</style>
<div class="<?php echo esc_attr( $wrapper_class . ' ' . $install_class . ' notice' ); ?>">
	<div class="<?php echo esc_attr( $inner_class ); ?>">
		<div class="<?php echo esc_attr( $title_class ); ?>"><?php echo esc_html( $notice['title'] ); ?></div>
		<div class="<?php echo esc_attr( $desc_class ); ?>"><?php echo esc_html( $notice['description'] ); ?></div>
		<?php if ( $tags ) : ?>
			<div class="<?php echo esc_attr( $tag_class ); ?>">
				<?php foreach ( $tags as $index => $tag ) : ?>
					<?php if ( $index ) : ?>
						<span></span>
					<?php endif; ?>
					<div><?php echo esc_html( $tag ); ?></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $install_slug ) : ?>
			<a
				href="#"
				class="<?php echo esc_attr( $button_class ); ?>"
				data-plugin-slug="<?php echo esc_attr( $install_slug ); ?>"
				data-link="<?php echo esc_url( $notice['button_url'] ); ?>"
			>
				<span class="dashicons dashicons-image-rotate"></span>
				<?php echo esc_html( $notice['button_text'] ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( $notice['button_url'] ); ?>" class="<?php echo esc_attr( $button_class ); ?>">
				<?php echo esc_html( $notice['button_text'] ); ?>
			</a>
		<?php endif; ?>

		<a class="<?php echo esc_attr( $close_class ); ?>" href="<?php echo esc_url( add_query_arg( $query_args ) ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</a>
	</div>
</div>
<?php if ( $install_slug ) : ?>
<script type="text/javascript">
	jQuery( function ( $ ) {
		'use strict';

		$( document ).on( 'click', '<?php echo esc_js( '.' . $button_class . '[data-plugin-slug]' ); ?>', function ( e ) {
			e.preventDefault();

			var $button = $( this );
			var link    = $button.data( 'link' );

			$button.closest( '<?php echo esc_js( '.' . $inner_class ); ?>' ).addClass( 'is-loading' );

			$.post( ajaxurl, {
				action:  '<?php echo esc_js( $prefix . '_install_plugin' ); ?>',
				plugin:  $button.data( 'plugin-slug' ),
				wpnonce: '<?php echo esc_js( wp_create_nonce( $prefix . '-nonce' ) ); ?>'
			} ).always( function () {
				window.location.href = link || window.location.href;
			} );
		} );
	} );
</script>
<?php endif; ?>

<?php // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals ?>
