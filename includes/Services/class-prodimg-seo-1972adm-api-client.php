<?php
/**
 * API Client.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Api_Client {

    private $api_key;
    private $api_url;
    private $timeout;
    private $settings;

    public function __construct( Prodimg_Seo_1972adm_Settings $settings ) {
        $this->settings = $settings;
        $this->api_key = $settings->get( 'api_key', '' );
        $this->api_url = PRODIMG_SEO_1972ADM_API_BASE_URL;
        $this->timeout = PRODIMG_SEO_1972ADM_API_TIMEOUT;
    }

    /**
     * Generate alt text for a product image with full product context.
     */
    public function generate_for_product( $product_id, $image_id, $image_role = 'featured' ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error(
                'missing_api_key',
                __( 'API key required.', 'product-image-seo' )
            );
        }

        $context_builder = new Prodimg_Seo_1972adm_Product_Context( $this->settings );
        $body            = $context_builder->build( $product_id, $image_id, $image_role );

        $body['style']      = $this->settings->get( 'alt_style', 'seo_balanced' );
        $body['max_length'] = absint( $this->settings->get( 'max_length', 125 ) );
        $body['language']   = $this->detect_language();
        $body['source']     = 'product-image-seo-plugin';

        $response = wp_remote_post(
            trailingslashit( $this->api_url ) . 'generate/product',
            array(
                'method'  => 'POST',
                'timeout' => $this->timeout,
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'User-Agent'    => 'ProductImageSeo-WordPress-Plugin/' . PRODIMG_SEO_1972ADM_VERSION,
                ),
                'body'    => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code         = wp_remote_retrieve_response_code( $response );
        $decoded_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code || empty( $decoded_body['success'] ) ) {
            return new WP_Error(
                $decoded_body['error_code'] ?? 'api_error',
                $decoded_body['message'] ?? __( 'API request failed.', 'product-image-seo' )
            );
        }

        return $decoded_body['data'];
    }

    public function test_connection() {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'API key required.', 'product-image-seo' ) );
        }

        $response = wp_remote_post(
            trailingslashit( $this->api_url ) . 'test-connection',
            array(
                'method'  => 'POST',
                'timeout' => 15,
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'User-Agent'    => 'ProductImageSeo-WordPress-Plugin/' . PRODIMG_SEO_1972ADM_VERSION,
                ),
                'body'    => wp_json_encode( array() ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        return ( 200 === $code )
            ? true
            : new WP_Error( 'connection_failed', __( 'API connection failed.', 'product-image-seo' ) );
    }

    private function detect_language() {
        $locale = determine_locale();
        return substr( $locale, 0, 2 );
    }
}
