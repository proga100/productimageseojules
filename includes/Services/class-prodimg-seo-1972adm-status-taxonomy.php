<?php
/**
 * Custom taxonomy for image SEO status.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Status_Taxonomy {

    const TAXONOMY = 'prodimg_seo_1972adm_status';

    const TERMS = array(
        'needs_review',  // missing or weak alt text on featured/gallery
        'partial',       // some images optimized, some not
        'optimized',     // all images have good alt text
        'excellent',     // all images excellent + variation images covered
        'ignored',       // user marked as not relevant for SEO
    );

    public function register() {
        $args = array(
            'labels'            => array(
                'name'          => __( 'Image SEO Status', 'product-image-seo' ),
                'singular_name' => __( 'Image SEO Status', 'product-image-seo' ),
            ),
            'hierarchical'      => false,
            'public'            => false,
            'show_ui'           => false,
            'show_admin_column' => false,
            'show_in_nav_menus' => false,
            'show_tagcloud'     => false,
            'show_in_rest'      => false,
            'rewrite'           => false,
            'query_var'         => false,
        );

        register_taxonomy( self::TAXONOMY, 'product', $args );
        $this->ensure_terms_exist();
    }

    private function ensure_terms_exist() {
        foreach ( self::TERMS as $term ) {
            if ( ! term_exists( $term, self::TAXONOMY ) ) {
                wp_insert_term( $term, self::TAXONOMY );
            }
        }
    }

    public static function set_status( $product_id, $status ) {
        $product_id = absint( $product_id );
        $status     = sanitize_key( $status );
        if ( ! in_array( $status, self::TERMS, true ) ) {
            return new WP_Error( 'invalid_status', __( 'Invalid status.', 'product-image-seo' ) );
        }
        return wp_set_object_terms( $product_id, $status, self::TAXONOMY, false );
    }
}
