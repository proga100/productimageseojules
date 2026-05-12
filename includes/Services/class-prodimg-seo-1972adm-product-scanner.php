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
        $products = wc_get_products( array(
            'limit'    => $limit,
            'page'     => $page,
            'status'   => 'publish',
            'paginate' => true,
        ) );

        if ( empty( $products->products ) ) {
            return array(
                'scanned' => 0,
                'total_pages' => 0,
                'current_page' => $page,
                'done' => true,
            );
        }

        $scanned = 0;
        foreach ( $products->products as $product ) {
            $this->calculator->calculate( $product );
            update_post_meta( $product->get_id(), '_prodimg_seo_1972adm_last_scanned', time() );
            $scanned++;
        }

        return array(
            'scanned' => $scanned,
            'total_pages' => $products->max_num_pages,
            'current_page' => $page,
            'done' => $page >= $products->max_num_pages,
        );
    }
}
