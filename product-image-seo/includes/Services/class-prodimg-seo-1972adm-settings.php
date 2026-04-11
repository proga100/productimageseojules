<?php
/**
 * Settings wrapper.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Settings {

    const OPTION_NAME = 'prodimg_seo_1972adm_settings';

    private $settings;

    public function __construct() {
        $this->settings = get_option( self::OPTION_NAME, array() );
    }

    public function get( $key, $default = null ) {
        // Special case for API key, usually better to store it in a separate option or use the settings array
        if ( 'api_key' === $key ) {
            return get_option( 'prodimg_seo_1972adm_api_key', $default );
        }

        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }

    public function set( $key, $value ) {
        if ( 'api_key' === $key ) {
            update_option( 'prodimg_seo_1972adm_api_key', sanitize_text_field( $value ) );
            return;
        }

        $this->settings[ $key ] = $value;
        update_option( self::OPTION_NAME, $this->settings );
    }

    public function get_all() {
        $all = $this->settings;
        $all['api_key'] = $this->get( 'api_key', '' );
        return $all;
    }

    public function update_all( $new_settings ) {
        if ( isset( $new_settings['api_key'] ) ) {
            $this->set( 'api_key', $new_settings['api_key'] );
            unset( $new_settings['api_key'] );
        }
        $this->settings = $new_settings;
        update_option( self::OPTION_NAME, $this->settings );
    }
}
