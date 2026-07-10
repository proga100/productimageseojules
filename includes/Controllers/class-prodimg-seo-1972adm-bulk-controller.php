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

        // Refuse to double-start: a second run while actions are still queued
        // would have the old jobs reporting into the new run's progress.
        if ( function_exists( 'as_get_scheduled_actions' ) ) {
            $in_flight = as_get_scheduled_actions(
                array(
                    'hook'     => 'prodimg_seo_1972adm_process_product_batch',
                    'status'   => array( ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING ),
                    'per_page' => 1,
                ),
                'ids'
            );
            if ( ! empty( $in_flight ) ) {
                wp_send_json_error( __( 'A bulk run is already in progress. Please wait for it to finish.', 'product-image-seo' ) );
                return;
            }
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

        // Self-heal: if a background job died (e.g. PHP timeout) its batch never
        // reports back, which would leave the UI polling "processing" forever.
        // When Action Scheduler has nothing left for our hook, the run is over —
        // account any unreported remainder as failed and complete the run.
        if ( 'processing' === $progress['status'] && function_exists( 'as_get_scheduled_actions' ) ) {
            $remaining = as_get_scheduled_actions(
                array(
                    'hook'     => 'prodimg_seo_1972adm_process_product_batch',
                    'status'   => array( ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING ),
                    'per_page' => 1,
                ),
                'ids'
            );
            if ( empty( $remaining ) ) {
                $done     = (int) $progress['images_generated'] + (int) $progress['images_skipped'] + (int) $progress['images_failed'];
                $expected = isset( $progress['total_images'] ) ? (int) $progress['total_images'] : $done;
                if ( $expected > $done ) {
                    $progress['images_failed'] = (int) $progress['images_failed'] + ( $expected - $done );
                }
                $progress['completed_batches'] = $progress['total_batches'];
                $progress['status']            = 'completed';
                set_transient( 'prodimg_seo_1972adm_bulk_progress', $progress, HOUR_IN_SECONDS );
            }
        }

        wp_send_json_success( $progress );
    }
}
