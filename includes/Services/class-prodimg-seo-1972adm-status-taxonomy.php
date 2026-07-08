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

    /**
     * Product-level terms: the legacy set plus 'excellent'
     * (full coverage including variation images).
     */
    const PRODUCT_TERMS = array(
        'needs_review',  // missing or weak alt text on featured/gallery.
        'partial',       // some images optimized, some not.
        'optimized',     // all featured/gallery images have alt text.
        'excellent',     // optimized plus all variation images covered.
        'ignored',       // user marked as not relevant for SEO.
    );

    /**
     * New per-image/per-attachment terms.
     */
    const IMAGE_TERMS = array(
        'missing',    // no alt text.
        'weak',       // alt text present but score 1–60.
        'good',       // score 61–85.
        'excellent',  // score 86–100.
        'decorative', // intentionally empty alt.
    );

    /**
     * All valid terms (union of legacy + image).
     */
    const TERMS = array(
        // Legacy.
        'needs_review',
        'partial',
        'optimized',
        'ignored',
        // Image-level.
        'missing',
        'weak',
        'good',
        'excellent',
        'decorative',
    );

    /**
     * Register the taxonomy on both product and attachment post types.
     *
     * @return void
     */
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

        register_taxonomy( self::TAXONOMY, array( 'product', 'attachment' ), $args );
        $this->ensure_terms_exist();
    }

    /**
     * Create any missing terms.
     *
     * @return void
     */
    private function ensure_terms_exist() {
        foreach ( self::TERMS as $term ) {
            if ( ! term_exists( $term, self::TAXONOMY ) ) {
                wp_insert_term( $term, self::TAXONOMY );
            }
        }
    }

    /**
     * Set a product-level status term (legacy).
     *
     * @param int    $product_id Product post ID.
     * @param string $status     Status slug (must be one of PRODUCT_TERMS).
     * @return array|WP_Error Result of wp_set_object_terms, or WP_Error.
     */
    public static function set_status( $product_id, $status ) {
        $product_id = absint( $product_id );
        $status     = sanitize_key( $status );
        if ( ! in_array( $status, self::PRODUCT_TERMS, true ) ) {
            return new WP_Error( 'invalid_status', __( 'Invalid status.', 'product-image-seo' ) );
        }
        return wp_set_object_terms( $product_id, $status, self::TAXONOMY, false );
    }

    /**
     * Set a per-attachment (per-image) status term.
     *
     * @param int    $attachment_id Attachment post ID.
     * @param string $status        Status slug (one of IMAGE_TERMS).
     * @return array|WP_Error Result of wp_set_object_terms, or WP_Error.
     */
    public static function set_status_for_attachment( $attachment_id, $status ) {
        $attachment_id = absint( $attachment_id );
        $status        = sanitize_key( $status );
        if ( ! in_array( $status, self::IMAGE_TERMS, true ) ) {
            return new WP_Error( 'invalid_status', __( 'Invalid image status.', 'product-image-seo' ) );
        }
        return wp_set_object_terms( $attachment_id, $status, self::TAXONOMY, false );
    }
}
