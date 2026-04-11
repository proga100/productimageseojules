<?php
/**
 * Product Context Builder.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Product_Context {

    /**
     * Settings instance.
     *
     * @var Prodimg_Seo_1972adm_Settings
     */
    private $settings;

    public function __construct( Prodimg_Seo_1972adm_Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Build full product context for AI generation.
     *
     * @param int    $product_id  WC product ID.
     * @param int    $image_id    Attachment ID.
     * @param string $image_role  featured | gallery | variation | category.
     * @return array Context payload for /generate/product API.
     */
    public function build( $product_id, $image_id, $image_role = 'featured' ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return array();
        }

        $context = array(
            'image_url'  => wp_get_attachment_url( $image_id ),
            'image_role' => sanitize_key( $image_role ),
            'product'    => array(
                'type'              => $product->get_type(),
                'short_description' => wp_strip_all_tags( $product->get_short_description() ),
                'tags'              => $this->get_term_names( $product_id, 'product_tag' ),
                'attributes'        => $this->get_attributes( $product ),
                'stock_status'      => $product->get_stock_status(),
            ),
        );

        if ( 'yes' === $this->settings->get( 'include_name', 'yes' ) ) {
            $context['product']['name'] = $product->get_name();
        }
        if ( 'yes' === $this->settings->get( 'include_sku', 'yes' ) ) {
            $context['product']['sku'] = $product->get_sku();
        }
        if ( 'yes' === $this->settings->get( 'include_category', 'yes' ) ) {
            $context['product']['categories'] = $this->get_term_names( $product_id, 'product_cat' );
        }
        if ( 'yes' === $this->settings->get( 'include_price', 'no' ) ) {
            $context['product']['price']    = floatval( $product->get_price() );
            $context['product']['currency'] = get_woocommerce_currency();
        }

        // Add variation-specific data if this is a variation image.
        if ( 'variation' === $image_role && $product->is_type( 'variation' ) ) {
            $context['variation'] = array(
                'id'         => $product->get_id(),
                'attributes' => $product->get_variation_attributes(),
            );
        }

        // SEO plugin focus keyword (Yoast / Rank Math / AIOSEO).
        $focus_keyword = $this->get_focus_keyword( $product_id );
        if ( $focus_keyword ) {
            $context['focus_keyword'] = $focus_keyword;
        }

        return $context;
    }

    private function get_term_names( $product_id, $taxonomy ) {
        $terms = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
        if ( is_wp_error( $terms ) ) {
            return array();
        }
        return $terms;
    }

    private function get_attributes( $product ) {
        $attributes = array();
        foreach ( $product->get_attributes() as $attribute ) {
            if ( $attribute->is_taxonomy() ) {
                $values = wc_get_product_terms(
                    $product->get_id(),
                    $attribute->get_name(),
                    array( 'fields' => 'names' )
                );
                $attributes[ $attribute->get_name() ] = $values;
            } else {
                $attributes[ $attribute->get_name() ] = $attribute->get_options();
            }
        }
        return $attributes;
    }

    private function get_focus_keyword( $product_id ) {
        $yoast = get_post_meta( $product_id, '_yoast_wpseo_focuskw', true );
        if ( $yoast ) {
            return $yoast;
        }
        $rank_math = get_post_meta( $product_id, 'rank_math_focus_keyword', true );
        if ( $rank_math ) {
            return $rank_math;
        }
        $aioseo = get_post_meta( $product_id, '_aioseo_focus_keyword', true );
        if ( $aioseo ) {
            return $aioseo;
        }
        return null;
    }
}
