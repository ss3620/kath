<?php
/**
 * Plugin Cache.
 *
 * @package WOPB\Caches
 * @since v.1.0.0
 */
namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * Caches class.
 */
class Caches {

	/**
	 * API Endpoint
	 *
	 * @since v.1.0.0
	 */
	private $api_endpoint = 'https://wopb.wpxpo.com/wp-json/restapi/v2/';

	private $files = array( 'design', 'premade', 'template' );
	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'template_data_callback' ) );
	}

	/**
	 * Get Template or Desing from the API Action
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function template_data_callback() {
		register_rest_route(
			'wopb/v2',
			'/get_preset_data/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'get_file_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				),
			)
		);
	}

	/**
	 * ResetData from API
	 *
	 * @since v.2.0.7
	 * @param ARRAY
	 * @return ARRAY | Data of the Design
	 */
	public function reset_all_callback() {
		$upload     = wp_upload_dir();
		$upload_dir = $upload['basedir'] . '/wopb';
		foreach ( $this->files as $key => $file ) {
			if ( file_exists( $upload_dir . '/' . $file . '.json' ) ) {
				wp_delete_file( $upload_dir . '/' . $file . '.json' );
			}
		}
		foreach ( $this->files as $key => $download ) {
			$this->download_source( $download, false );
		}
		return array(
			'success' => true,
			'data'    => __( 'Data Fetched!!!', 'product-blocks' ),
		);
	}

	/**
	 * Get Data Form File
	 *
	 * @since v.2.3.9
	 * @param ARRAY
	 * @return array | Data of the Premade
	 */
	public function get_file_callback( $request ) {
		$type = $request->get_param( 'type' );
		if ( $type == 'reset' ) {
			return $this->reset_all_callback();
		} else {
			try {
				return $this->get_preset_list( $type );
			} catch ( Exception $e ) {
				return array(
					'success' => false,
					'message' => $e->getMessage(),
				);
			}
		}
	}

	/**
	 * Get Preset Data List Form File
	 *
	 * @since v.2.3.9
	 * @param $type
	 * @return array | Data of the Premade
	 */
	public function get_preset_list( $type ) {
		$res = array();
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$upload_url = wp_upload_dir();
		$dir        = trailingslashit( $upload_url['basedir'] ) . 'wopb/';
		$downloads  = $type == 'all' ? $this->files : array( $type );
		foreach ( $downloads as $key => $download ) {
			$file_path = $dir . $download . '.json';
			if ( file_exists( $file_path ) ) {
				if ( ( current_time( 'timestamp' ) - filemtime( $file_path ) ) > 432000 ) { // 5 Days in Seconds
					$content = $this->download_source( $download );
					if ( $type == 'all' ) {
						$res[ $download ] = json_decode( $content );
					} else {
						return array(
							'success' => true,
							'data'    => $content,
						);
					}
				} else {
					$content = $wp_filesystem->get_contents( $file_path );
					if ( $type == 'all' ) {
						$res[ $download ] = json_decode( $content );
					} else {
						return array(
							'success' => true,
							'data'    => $content,
						);
					}
				}
			} elseif ( $type == 'all' ) {
					$res[ $download ] = json_decode( $this->download_source( $download ) );
			} else {
				return array(
					'success' => true,
					'data'    => $this->download_source( $download ),
				);
			}
		}
		return array(
			'success' => true,
			'data'    => wp_json_encode( $res ),
		);
	}

	/**
	 * Create a Directory in Upload Folder
	 *
	 * @since v.1.0.0
	 * @param NULL
	 * @return STRING | Directory Path
	 */
	public function create_directory( $type = 'template' ) {
		try {
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
			$upload_dir_url = wp_upload_dir();
			$dir            = trailingslashit( $upload_dir_url['basedir'] ) . 'wopb/';
			WP_Filesystem( false, $upload_dir_url['basedir'], true );
			if ( ! $wp_filesystem->is_dir( $dir ) ) {
				$wp_filesystem->mkdir( $dir );
			}
			if ( ! file_exists( $dir . $type . '.json' ) ) {
				$wp_filesystem->put_contents( $dir . $type . '.json', FS_CHMOD_FILE );
			}
			return $dir;
		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Download Source from the Server API
	 *
	 * @since v.1.0.0
	 * @param STRING | Type (STRING)
	 * @return ARRAY | Message from the API
	 */
	public function download_source( $type = '', $output = true ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$response = wp_remote_post(
			$this->api_endpoint . 'wopbdata',
			array(
				'method'  => 'POST',
				'timeout' => 120,
				'body'    => array( 'type' => $type ),
			)
		);
		if ( ! is_wp_error( $response ) ) {
			$path_url = $this->create_directory( $type );
			$wp_filesystem->put_contents( $path_url . $type . '.json', $response['body'] );
			if ( $output ) {
				return $response['body'];
			}
		}
	}
}
