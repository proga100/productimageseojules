<?php
/**
 * Product Scanner.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Product_Scanner {

    private $calculator;

    public function __construct( Prodimg_Seo_1972adm_Coverage_Calculator $calculator ) {
        $this->calculator = $calculator;
    }

    public function scan_all( $page = 1 ) {
        $limit = 50;
        $page  = max( 1, absint( $page ) );

        // Enumerate product IDs directly. wc_get_products() implicitly tax-filters
        // on product_type and silently drops products lacking that term (common in
        // legacy/imported data); a plain post query paginates every published product.
        $query = new WP_Query( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => $limit,
            'paged'          => $page,
        ) );

        if ( empty( $query->posts ) ) {
            return array(
                'scanned' => 0,
                'total_pages' => 0,
                'current_page' => $page,
                'done' => true,
            );
        }

        $total_pages = (int) $query->max_num_pages;
        $scanned     = 0;
        foreach ( $query->posts as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }
            $this->calculator->calculate( $product );
            update_post_meta( $product->get_id(), '_prodimg_seo_1972adm_last_scanned', time() );
            $scanned++;
        }

        return array(
            'scanned' => $scanned,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'done' => $page >= $total_pages,
        );
    }
}
