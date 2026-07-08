<?php
/**
 * Bulk Controller.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Bulk_Controller {

    private $processor;

    public function __construct( Prodimg_Seo_1972adm_Bulk_Processor $processor ) {
        $this->processor = $processor;
    }

    public function init_hooks() {
        add_action( 'wp_ajax_prodimg_seo_1972adm_bulk_start', array( $this, 'ajax_bulk_start' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_bulk_status', array( $this, 'ajax_bulk_status' ) );
    }

    public function ajax_bulk_start() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
            return;
        }

        // Normally we'd take selected IDs or filter parameters, for now we assume 'generate for all missing' or receive IDs
        $product_ids = isset( $_POST['product_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['product_ids'] ) ) : array();

        if ( empty( $product_ids ) ) {
            // Fallback: every product needing review. Enumerate IDs with a plain
            // post query — wc_get_products() implicitly tax-filters on product_type
            // and silently drops products lacking that term (legacy/imported data).
            $product_ids = get_posts( array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'no_found_rows'  => true,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- fallback query when no explicit IDs; bounded by status taxonomy.
                'tax_query'      => array(
                    array(
                        'taxonomy' => Prodimg_Seo_1972adm_Status_Taxonomy::TAXONOMY,
                        'field'    => 'slug',
                        'terms'    => array( 'needs_review', 'partial' ),
                    ),
                ),
            ) );
        }

        if ( empty( $product_ids ) ) {
            wp_send_json_error( __( 'No products to process.', 'product-image-seo' ) );
            return;
        }

        $result = $this->processor->enqueue_batch( $product_ids );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
            return;
        }

        wp_send_json_success( __( 'Bulk process started.', 'product-image-seo' ) );
    }

    public function ajax_bulk_status() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
            return;
        }

        $progress = get_transient( 'prodimg_seo_1972adm_bulk_progress' );
        if ( ! $progress ) {
            wp_send_json_success( array( 'status' => 'idle' ) );
            return;
        }

        wp_send_json_success( $progress );
    }
}
