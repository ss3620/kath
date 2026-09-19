<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ossm_getcityziplookup(){

    // CSRF protection (frontend-safe)
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field( wp_unslash($_REQUEST['_wpnonce']) ) : '';
    if ( ! wp_verify_nonce( $nonce, 'ossm_cityzip_lookup' ) ) {
        wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
    }

    $input = isset($_REQUEST['q']) ? sanitize_text_field( wp_unslash($_REQUEST['q']) ) : '';
    if ( empty( $input ) ) {
        wp_send_json_error( array( 'message' => 'Missing query' ), 400 );
    }

    $inputArr = explode( 'countrycode', $input );
    if ( count( $inputArr ) !== 2 ) {
        wp_send_json_error( array( 'message' => 'Invalid input format' ), 400 );
    }

    $placename_startsWith = rawurlencode( $inputArr[0] );
    $country              = rawurlencode( $inputArr[1] );

    $url = 'http://api.geonames.org/postalCodeSearchJSON?placename_startsWith=' .
            $placename_startsWith .
            '&maxRows=400&country=' .
            $country .
            '&username=nerdster';

    $suggestions1 = wp_remote_get( $url );
    if ( is_wp_error( $suggestions1 ) ) {
        wp_send_json_error( array( 'message' => 'Remote request failed' ), 500 );
    }

    $suggestions2 = json_decode( wp_remote_retrieve_body( $suggestions1 ), true );
    if ( empty( $suggestions2['postalCodes'] ) ) {
        wp_send_json_success( array() );
    }

    $results = array();

    foreach ( $suggestions2['postalCodes'] as $k => $v ) {
        $results[] = array(
            'ID'        => $k + 1,
            'label'     => $v['placeName'] . ', ' . $v['postalCode'] . ' ' . $v['adminName1'] . ' ' . $country,
            'city'      => $v['placeName'],
            'zip'       => $v['postalCode'],
            'statecode' => trim( $v['ISO3166-2'] ),
            'statename' => trim( $v['adminName1'] ),
        );
    }

    wp_send_json_success( $results );
}


?>
