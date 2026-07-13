<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ossm_sendle_tracking_widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'ossm_sendle_tracking_widget',
            __( 'Sendle Tracking', 'official-sendle-shipping-method' ),
            array( 'description' => __( 'Track Sendle Parcel', 'official-sendle-shipping-method' ) )
        );
    }

    public function widget( $args, $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : '';
        $title = apply_filters( 'widget_title', $title );

        // Escape theme-provided wrapper HTML (plugin_check).
        echo wp_kses_post( $args['before_widget'] );

        if ( ! empty( $title ) ) {
            echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
        }
        ?>
        <div class="sendle_tracking_wrapper">
            <div class="sendle_tracking_form">
                <input type="text" name="sendle_reference" value="" placeholder="<?php echo esc_attr__( 'Sendle Reference', 'official-sendle-shipping-method' ); ?>"/>
                <button type="button"><?php echo esc_html__( 'LookUp', 'official-sendle-shipping-method' ); ?></button>
            </div>
            <div class="sendle_tracking_info"></div>
        </div>
        <?php

        echo wp_kses_post( $args['after_widget'] );
    }

    public function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : __( 'New title', 'official-sendle-shipping-method' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'official-sendle-shipping-method' ); ?>
            </label>
            <input class="widefat"
                id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                type="text"
                value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        return $instance;
    }
}

function ossm_sendle_tracking_load_widget() {
    if ( ! ossm_is_sendle_widget_enable() ) {
        return false;
    }
    register_widget( 'ossm_sendle_tracking_widget' );
}
add_action( 'widgets_init', 'ossm_sendle_tracking_load_widget' );

function ossm_sendle_tracking_scripts_basic() {

    wp_enqueue_style(
        'sendle-tracking-style',
        plugins_url( '/style.css', __FILE__ ),
        array(),
        defined( 'SENDLE_JOOVII_WP_SENDLE_PLUGIN_VERSION' ) ? SENDLE_JOOVII_WP_SENDLE_PLUGIN_VERSION : null
    );

    wp_enqueue_script(
        'sendle-tracking-script',
        plugins_url( '/scripts.js', __FILE__ ),
        array( 'jquery' ),
        defined( 'SENDLE_JOOVII_WP_SENDLE_PLUGIN_VERSION' ) ? SENDLE_JOOVII_WP_SENDLE_PLUGIN_VERSION : null,
        true
    );

    wp_localize_script(
        'sendle-tracking-script',
        'sendletracking',
        array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ossm_sendle_track' ),
        )
    );
}

add_action( 'wp_enqueue_scripts', 'ossm_sendle_tracking_scripts_basic' );
add_action( 'wp_ajax_sendletrack', 'ossm_sendle_track_ajax' );
add_action( 'wp_ajax_nopriv_sendletrack', 'ossm_sendle_track_ajax' ); // allow frontend for non-logged-in users

function ossm_sendle_track_ajax() {

    check_ajax_referer( 'ossm_sendle_track', 'nonce' ); // Prevent CSRF in AJAX

    $reference = isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';
    $reference = trim( $reference );
    $reference = preg_replace( '/[^A-Za-z0-9 ]/', '', $reference );

    $error  = '';
    $result = array();

    if ( strlen( $reference ) !== 6 ) {
        $error = __( 'Invalid Sendle reference number.', 'official-sendle-shipping-method' );
    }

    if ( ! empty( $error ) ) {
        $result['result'] = 0;
        $result['info']   = $error;
        wp_send_json( $result );
        wp_die();
    }

    $api_mode = get_option( 'sendle_shipping_api_mode' );
    $api_id   = get_option( 'sendle_shipping_api_id' );
    $api_key  = get_option( 'sendle_shipping_api_key' );

    if ( ! empty( $api_mode ) && ! empty( $api_id ) && ! empty( $api_key ) ) {

        $apiurl = ( 'live' === $api_mode ) ? 'https://api.sendle.com' : SENDLE_JOOVII_API_SANDBOX_URL;

        $url  = $apiurl . '/api/tracking/' . rawurlencode( $reference );
        $args = array(
            'timeout'    => 30,
            'user-agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
        );

        $response = wp_remote_get( esc_url_raw( $url ), $args );
        $content  = wp_remote_retrieve_body( $response );
        $sendle_result = json_decode( $content );

        if ( isset( $sendle_result->tracking_events ) ) {

            if ( count( $sendle_result->tracking_events ) > 0 ) {

                $info = '<ul>';

                foreach ( $sendle_result->tracking_events as $t ) {
                    $scan_time   = isset( $t->scan_time ) ? (string) $t->scan_time : '';
                    $sendle_time = str_replace( array( 'T', 'Z' ), '', $scan_time );

                    $event_type  = isset( $t->event_type ) ? (string) $t->event_type : '';
                    $description = isset( $t->description ) ? (string) $t->description : '';

                    $info .= "<li><div class='sendle-column-left'><div class='sendle_event_type'>"
                        . esc_html( $event_type )
                        . "</div><div class='sendle_scan_time'>"
                        . esc_html( $sendle_time )
                        . "</div></div><div class='sendle-column-right'><div class='sendle_description'>"
                        . esc_html( $description )
                        . '</div></div></li>';
                }

                $info .= '</ul>';

                $result['result'] = 1;
                $result['info']   = $info;

            } else {

                $result['result'] = 1;
                $result['info']   = __( 'Please try again later, we are awaiting tracking info from Sendle.', 'official-sendle-shipping-method' );
            }
        } else {
            $result['result'] = 1;
            $result['info']   = __( 'Your requested reference number was not found.', 'official-sendle-shipping-method' );
        }
    } else {
        $result['result'] = 1;
        $result['info']   = __( 'Your requested reference number was not found.', 'official-sendle-shipping-method' );
    }

    wp_send_json( $result );
    wp_die();
}

add_shortcode( 'sendle_tracking', 'ossm_sendle_tracking_shortcode' );

function ossm_sendle_tracking_shortcode() {
    if ( ! ossm_is_sendle_widget_enable() ) {
        return '';
    }

    ob_start();
    ?>
    <div class="sendle_tracking_wrapper">
        <div class="sendle_tracking_form">
            <input type="text" name="sendle_reference" id="sendle_reference" value="" placeholder="<?php echo esc_attr__( 'Sendle Reference', 'official-sendle-shipping-method' ); ?>"/>
            <button type="button"
                onclick="window.open('https://track.sendle.com/tracking?ref='+encodeURIComponent(document.getElementById('sendle_reference').value),'_blank','noopener,noreferrer');">
                <?php echo esc_html__( 'LookUp', 'official-sendle-shipping-method' ); ?>
            </button>
        </div>
        <div class="sendle_tracking_info"></div>
    </div>
    <?php
    return ob_get_clean();
}
