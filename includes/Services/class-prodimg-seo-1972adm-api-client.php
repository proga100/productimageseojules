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

    /**
     * API base URL.
     *
     * @var string
     */
    private $api_url;

    /**
     * API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Request timeout.
     *
     * @var int
     */
    private $timeout;

    /**
     * Settings instance.
     *
     * @var Prodimg_Seo_1972adm_Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param Prodimg_Seo_1972adm_Settings $settings Settings service.
     */
    public function __construct( Prodimg_Seo_1972adm_Settings $settings ) {
        $this->settings = $settings;
        $this->api_key  = $settings->get( 'api_key', '' );
        $this->api_url  = PRODIMG_SEO_1972ADM_API_BASE_URL;
        $this->timeout  = PRODIMG_SEO_1972ADM_API_TIMEOUT;
    }

    /**
     * Generate alt text for a product image.
     *
     * Keeps the same public signature for backward compatibility.
     *
     * @param int    $product_id Product post ID.
     * @param int    $image_id   Attachment post ID.
     * @param string $image_role featured | gallery | variation.
     * @return array { success, alt_text, quality_score } or WP_Error.
     */
    public function generate_for_product( $product_id, $image_id, $image_role = 'featured' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- $image_role is part of the documented public signature and reserved for role-aware prompting.
        if ( empty( $this->api_key ) ) {
            return new WP_Error(
                'missing_api_key',
                __( 'API key required.', 'product-image-seo' )
            );
        }

        // Build context string.
        $context = '';
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $name = sanitize_text_field( $product->get_name() );
            $cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
            $cat  = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? sanitize_text_field( $cats[0] ) : '';
            $context = sprintf( 'WooCommerce product: %s', $name );
            if ( $cat ) {
                $context .= sprintf( ', category: %s', $cat );
            }
            $context = substr( $context, 0, 200 );
        }

        $style      = $this->settings->get( 'alt_style', 'seo_balanced' );
        $max_length = absint( $this->settings->get( 'max_length', 125 ) );
        $length     = (string) $max_length;

        $image_url = wp_get_attachment_url( $image_id );
        $is_public = $this->is_image_publicly_accessible( $image_url );

        if ( $is_public ) {
            $body = $this->prepare_public_image_request( $image_url, $style, $context, $length, $max_length );
        } else {
            $body = $this->prepare_private_image_request( $image_id, $style, $context, $length, $max_length );
            if ( isset( $body['error'] ) ) {
                return new WP_Error( $body['error'], $body['message'] );
            }
        }

        $body['source'] = 'wordpress-plugin-product-image-seo';

        $response = $this->make_request( 'generate', $body, 'POST' );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Check API-level error.
        if ( isset( $response['success'] ) && false === $response['success'] ) {
            return new WP_Error(
                $response['error_code'] ?? 'api_error',
                $response['message'] ?? __( 'API request failed.', 'product-image-seo' )
            );
        }

        // Parse response: try data.alt_text first, then flat alt_text.
        $alt_text = '';
        if ( isset( $response['data']['alt_text'] ) ) {
            $alt_text = $response['data']['alt_text'];
        } elseif ( isset( $response['alt_text'] ) ) {
            $alt_text = $response['alt_text'];
        }

        $quality_score = 0;
        if ( isset( $response['data']['quality_score'] ) ) {
            $quality_score = $response['data']['quality_score'];
        } elseif ( isset( $response['quality_score'] ) ) {
            $quality_score = $response['quality_score'];
        }

        return array(
            'success'       => true,
            'alt_text'      => $alt_text,
            'quality_score' => $quality_score,
        );
    }

    /**
     * Test the API connection.
     *
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public function test_connection() {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'missing_api_key', __( 'API key required.', 'product-image-seo' ) );
        }

        $response = $this->make_request( 'test-connection', array(), 'POST' );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return true;
    }

    /**
     * Make an HTTP request to the API with retry logic.
     *
     * @param string $endpoint    API endpoint (relative to base URL).
     * @param array  $body        Request body.
     * @param string $method      HTTP method (POST|GET).
     * @param int    $retry_count Current retry count (internal).
     * @return array|WP_Error Decoded JSON response array or WP_Error.
     */
    /**
     * Safely decode a JSON response body.
     *
     * @param string $body Raw response body.
     * @return array|null Decoded array, or null on parse failure.
     */
    private function try_decode_response_body( $body ) {
        if ( '' === $body ) {
            return null;
        }
        $decoded = json_decode( $body, true );
        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return null;
        }
        return $decoded;
    }

    private function make_request( $endpoint, $body = array(), $method = 'POST', $retry_count = 0 ) {
        $url = trailingslashit( $this->api_url ) . $endpoint;

        $headers = array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key,
            'User-Agent'    => 'ProductImageSeo-WordPress-Plugin/' . PRODIMG_SEO_1972ADM_VERSION,
        );

        $args = array(
            'method'  => $method,
            'headers' => $headers,
            'timeout' => $this->timeout,
        );

        if ( 'POST' === $method && ! empty( $body ) ) {
            $args['body'] = wp_json_encode( $body );
        } elseif ( 'GET' === $method && ! empty( $body ) ) {
            $url = add_query_arg( $body, $url );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        // Retry on rate limit or server error.
        if ( 429 === $response_code || 500 === $response_code ) {
            $max_retries = 2;
            if ( $retry_count < $max_retries ) {
                $delay = $retry_count + 1; // 1s, 2s.
                $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
                if ( $retry_after && is_numeric( $retry_after ) ) {
                    $delay = min( (int) $retry_after, 10 );
                }
                sleep( $delay );
                return $this->make_request( $endpoint, $body, $method, $retry_count + 1 );
            }

            $error_data = $this->try_decode_response_body( $response_body );
            $error_type = 429 === $response_code ? 'rate_limit' : 'server_error';
            $message    = 429 === $response_code
                ? __( 'Rate limit exceeded. Please wait a few minutes and try again.', 'product-image-seo' )
                : __( 'The Product Image SEO service is temporarily unavailable. Please try again later.', 'product-image-seo' );

            return new WP_Error(
                $error_type,
                ( null !== $error_data && isset( $error_data['message'] ) ) ? $error_data['message'] : $message,
                array( 'status' => $response_code, 'retry_count' => $retry_count )
            );
        }

        if ( $response_code >= 400 ) {
            $error_data = $this->try_decode_response_body( $response_body );

            $error_type    = 'api_error';
            $error_message = '';

            if ( 401 === $response_code || 403 === $response_code ) {
                $error_type    = 'auth_error';
                $error_message = __( 'Authentication failed. Please check your API key in plugin settings.', 'product-image-seo' );
            } elseif ( 404 === $response_code ) {
                $error_type    = 'not_found';
                $error_message = __( 'API endpoint not found. Please update the plugin or check your API URL settings.', 'product-image-seo' );
            } elseif ( 422 === $response_code ) {
                $error_type    = 'validation_error';
                $error_message = ( null !== $error_data && isset( $error_data['message'] ) ) ? $error_data['message'] : __( 'Invalid request data.', 'product-image-seo' );
            } elseif ( $response_code >= 500 ) {
                $error_type    = 'server_error';
                $error_message = __( 'Server error. The service may be experiencing issues. Please try again later.', 'product-image-seo' );
            } else {
                $error_message = ( null !== $error_data && isset( $error_data['message'] ) ) ? $error_data['message'] : __( 'API request failed.', 'product-image-seo' );
            }

            return new WP_Error(
                $error_type,
                $error_message,
                array( 'status' => $response_code, 'response' => $error_data )
            );
        }

        $data = $this->try_decode_response_body( $response_body );

        if ( null === $data ) {
            return new WP_Error( 'invalid_response', __( 'Invalid API response.', 'product-image-seo' ) );
        }

        return $data;
    }

    /**
     * Check if an image URL is publicly accessible from the internet.
     *
     * @param string $image_url Image URL.
     * @return bool True if public, false if local/private.
     */
    private function is_image_publicly_accessible( $image_url ) {
        $parsed_url = wp_parse_url( $image_url );
        if ( empty( $parsed_url['host'] ) ) {
            return false;
        }

        $host = $parsed_url['host'];

        $local_patterns = array(
            '.test',
            '.local',
            '.localhost',
            '.example',
            '.invalid',
            'localhost',
            '127.0.0.1',
            '::1',
        );

        foreach ( $local_patterns as $pattern ) {
            if ( $host === $pattern || substr( $host, -strlen( $pattern ) ) === $pattern ) {
                return false;
            }
        }

        // Check private IP ranges.
        $ip = gethostbyname( $host );
        if ( $ip !== $host ) {
            if ( false === filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build request body for a publicly accessible image.
     *
     * @param string $image_url  Public image URL.
     * @param string $style      Generation style.
     * @param string $context    Context string.
     * @param string $length     Length as string.
     * @param int    $max_length Max character length.
     * @return array Request body.
     */
    private function prepare_public_image_request( $image_url, $style, $context, $length, $max_length ) {
        $body = array(
            'image_url' => $image_url,
            'style'     => $style,
            'context'   => $context,
            'length'    => $length,
            'max_chars' => $max_length,
            'source'    => 'wordpress-plugin-product-image-seo',
        );

        $language_text = $this->get_language_surrounding_text();
        if ( ! empty( $language_text ) ) {
            $body['surrounding_text'] = $language_text;
        }

        return $body;
    }

    /**
     * Build request body for a private/local image using base64.
     *
     * @param int    $attachment_id Attachment post ID.
     * @param string $style         Generation style.
     * @param string $context       Context string.
     * @param string $length        Length as string.
     * @param int    $max_length    Max character length.
     * @return array Request body or error array.
     */
    private function prepare_private_image_request( $attachment_id, $style, $context, $length, $max_length ) {
        $image_path = get_attached_file( $attachment_id );
        if ( ! $image_path || ! file_exists( $image_path ) ) {
            return array(
                'error'   => 'image_not_found',
                'message' => __( 'Image file not found.', 'product-image-seo' ),
            );
        }

        // Read the local file via WP_Filesystem rather than raw file_get_contents.
        global $wp_filesystem;
        if ( ! ( $wp_filesystem instanceof WP_Filesystem_Base ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $image_contents = ( $wp_filesystem instanceof WP_Filesystem_Base )
            ? $wp_filesystem->get_contents( $image_path )
            : false;

        if ( false === $image_contents || '' === $image_contents ) {
            return array(
                'error'   => 'image_read_error',
                'message' => __( 'Failed to read image file.', 'product-image-seo' ),
            );
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Base64 encoding required to transmit binary image data to the API.
        $image_data = base64_encode( $image_contents );
        if ( ! $image_data ) {
            return array(
                'error'   => 'image_read_error',
                'message' => __( 'Failed to read image file.', 'product-image-seo' ),
            );
        }

        $image_url = wp_get_attachment_url( $attachment_id );

        $body = array(
            'image_data' => $image_data,
            'image_url'  => $image_url,
            'filename'   => basename( $image_path ),
            'style'      => $style,
            'context'    => $context,
            'length'     => $length,
            'max_chars'  => $max_length,
            'source'     => 'wordpress-plugin-product-image-seo',
        );

        $language_text = $this->get_language_surrounding_text();
        if ( ! empty( $language_text ) ) {
            $body['surrounding_text'] = $language_text;
        }

        return $body;
    }

    /**
     * Get a language instruction for the surrounding_text field when not English.
     *
     * @return string Language instruction or empty string.
     */
    private function get_language_surrounding_text() {
        $locale   = determine_locale();
        $language = substr( $locale, 0, 2 );
        if ( 'en' !== $language ) {
            return sprintf( 'IMPORTANT: Write the alt text in the following language code: %s.', $language );
        }
        return '';
    }
}
