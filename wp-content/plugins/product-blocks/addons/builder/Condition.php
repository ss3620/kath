<?php
namespace WOPB;

defined( 'ABSPATH' ) || exit;

class Condition {
	private $header_id      = '';
	private $footer_id      = '';
	private $theme_name     = '';
	private $is_block_theme = '';
	public function __construct() {
		add_action( 'wp', array( $this, 'checkfor_header_footer' ), 999 );
		add_filter( 'template_include', array( $this, 'include_builder_files' ), 999 );
	}

	public function checkfor_header_footer() {
		$this->theme_name     = get_template();
		$this->is_block_theme = wp_is_block_theme();
		$header_id            = wopb_function()->conditions( 'header' );
		$footer_id            = wopb_function()->conditions( 'footer' );
		global $WOPB_HEADER_ID;
		global $WOPB_FOOTER_ID;

		if ( $header_id ) {
			$WOPB_HEADER_ID  = $header_id;
			$this->header_id = $header_id;
			do_action(
				'wopb_enqueue_wowstore_block_css',
				array(
					'post_id' => $header_id,
					'css'     => '',
				)
			);
			if ( $this->is_block_theme ) {
				add_action( 'wp_head', array( $this, 'wopb_header_builder_template' ) );
			} else {
				switch ( $this->theme_name ) {
					case 'astra':
						remove_all_actions( 'astra_header' );
						add_action( 'astra_header', array( $this, 'wopb_header_builder_template' ) );
						break;
					default:
						add_action( 'get_header', array( $this, 'wopb_header_builder_template' ) );
				}
			}
		}
		if ( $footer_id ) {
			$WOPB_FOOTER_ID  = $footer_id;
			$this->footer_id = $footer_id;
			do_action(
				'wopb_enqueue_wowstore_block_css',
				array(
					'post_id' => $footer_id,
					'css'     => '',
				)
			);
			if ( $this->is_block_theme ) {
				add_action( 'wp_footer', array( $this, 'wopb_footer_builder_template' ) );
			} else {
				switch ( $this->theme_name ) {
					case 'astra':
						remove_all_actions( 'astra_footer' );
						add_action( 'astra_footer', array( $this, 'wopb_footer_builder_template' ) );
						break;
					case 'generatepress':
						remove_action( 'generate_footer', 'generate_construct_footer_widgets' );
						remove_action( 'generate_footer', 'generate_construct_footer' );
						add_action( 'generate_footer', array( $this, 'wopb_footer_builder_template' ) );
						break;
					default:
						add_action( 'get_footer', array( $this, 'wopb_footer_builder_template' ) );
				}
			}
		}
	}

	public function wopb_header_builder_template() {
		if ( $this->header_id ) {
			if ( $this->is_block_theme ) {
				remove_action( 'wp_head', array( $this, 'wopb_header_builder_template' ) );
			} else {
				if ( $this->theme_name != 'astra' ) {  // Astra theme issue
					require_once WOPB_PATH . 'addons/builder/templates/header.php';
				}
				$templates   = array();
				$templates[] = 'header.php';
				remove_all_actions( 'wp_head' );
				if ( $this->theme_name != 'bricks' ) {  // Conflict with Bricks Builder Backend
					ob_start();
				}
				locate_template( $templates, true );
				if ( $this->theme_name != 'bricks' ) { // Conflict with Bricks Builder Backend
					ob_get_clean();
				} else {
					wp_enqueue_style( 'wp-block-library' );  // Gutenberg CSS issue Bricks Builder frontend
				}
			}
			?> 
				<header id="wopb-header-template">
					<?php
						$header_safe = wopb_function()->wp_kses_safe( wopb_function()->content( $this->header_id ) );
						echo $header_safe;
					?>
				</header> 
			<?php
		}
	}
	public function wopb_footer_builder_template() {
		if ( $this->footer_id ) {
			if ( ! $this->is_block_theme ) {
				if ( $this->theme_name == 'astra' ) {  // Astra theme issue
					wp_footer();
				} else {
					require_once WOPB_PATH . 'addons/builder/templates/footer.php';
				}
				$templates   = array();
				$templates[] = 'footer.php';
				remove_all_actions( 'wp_footer' );
				if ( $this->theme_name != 'bricks' ) { // Conflict with Bricks Builder Backend
					ob_start();
				}
				locate_template( $templates, true );
				if ( $this->theme_name != 'bricks' ) { // Conflict with Bricks Builder Backend
					ob_get_clean();
				}
			}
			?>
							<footer id="wopb-footer-template" class="<?php echo esc_attr( 'wopb-builderid-' . $this->footer_id ); ?>" role="contentinfo">
					<?php
						$footer_safe = wopb_function()->wp_kses_safe( wopb_function()->content( $this->footer_id ) );
						echo $footer_safe;
					?>
				</footer> 
			<?php
		}
	}

	// Load Media
	public function load_media() {
		if ( ! $this->is_builder() ) {
			return;
		}
		wp_enqueue_style( 'builder-style', WOPB_URL . 'addons/builder/builder.css', array(), WOPB_VER );
		wp_enqueue_script( 'builder-script', WOPB_URL . 'addons/builder/builder.js', array( 'jquery' ), WOPB_VER, true );
		wp_localize_script(
			'builder-script',
			'builder_option',
			array(
				'security' => wp_create_nonce( 'wopb-nonce' ),
				'ajax'     => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	public function include_builder_files( $template ) {
		$includes = wopb_function()->conditions( 'includes' );
		return $includes ? $includes : $template;
	}

	public function is_builder() {
		global $post;
        return isset( $_GET['post_type'] ) ? ( sanitize_text_field( $_GET['post_type'] ) == 'wopb_builder' ) : ( isset( $post->post_type ) ? ( $post->post_type == 'wopb_builder' ) : false ); // phpcs:ignore
	}
}
