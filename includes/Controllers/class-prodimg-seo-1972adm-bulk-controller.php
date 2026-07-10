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
            // Fallback: only products that actually have an image needing alt
            // text (or, in overwrite mode, any product with images). This targets
            // the images that need work instead of every "needs review" product.
            $product_ids = $this->processor->get_target_product_ids();
        }

        if ( empty( $product_ids ) ) {
            wp_send_json_error( __( 'No product images need alt text.', 'product-image-seo' ) );
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
