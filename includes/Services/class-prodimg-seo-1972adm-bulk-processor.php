<?php
/**
 * Bulk Processor.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Bulk_Processor {

    private $api_client;
    private $settings;
    private $calculator;

    public function __construct( Prodimg_Seo_1972adm_Api_Client $api_client, Prodimg_Seo_1972adm_Settings $settings, Prodimg_Seo_1972adm_Score_Calculator $calculator ) {
        $this->api_client = $api_client;
        $this->settings   = $settings;
        $this->calculator = $calculator;
    }

    public function init_hooks() {
        add_action( 'prodimg_seo_1972adm_process_product_batch', array( $this, 'process_batch' ) );
    }

    public function enqueue_batch( array $product_ids ) {
        if ( ! function_exists( 'as_enqueue_async_action' ) ) {
            return new WP_Error( 'action_scheduler_missing', __( 'Action Scheduler is not available.', 'product-image-seo' ) );
        }

        $batches = array_chunk( $product_ids, 10 );
        $total_batches = count( $batches );

        // Setup transient for progress tracking
        set_transient( 'prodimg_seo_1972adm_bulk_progress', array(
            'total_batches'     => $total_batches,
            'completed_batches' => 0,
            'total_products'    => count( $product_ids ),
            'status'            => 'processing',
        ), HOUR_IN_SECONDS );

        foreach ( $batches as $batch ) {
            as_enqueue_async_action( 'prodimg_seo_1972adm_process_product_batch', array( $batch ) );
        }

        return true;
    }

    public function process_batch( $product_ids ) {
        if ( empty( $product_ids ) || ! is_array( $product_ids ) ) {
            return;
        }

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            $featured_id = $product->get_image_id();
            if ( $featured_id ) {
                $this->process_image( $product_id, $featured_id, 'featured' );
            }

            $gallery_ids = $product->get_gallery_image_ids();
            foreach ( $gallery_ids as $gid ) {
                $this->process_image( $product_id, $gid, 'gallery' );
            }

            if ( $product->is_type( 'variable' ) ) {
                foreach ( $product->get_children() as $child_id ) {
                    $child = wc_get_product( $child_id );
                    if ( $child ) {
                        $vid = $child->get_image_id();
                        if ( $vid ) {
                            $this->process_image( $child_id, $vid, 'variation' );
                        }
                    }
                }
            }
        }

        $this->update_progress();
    }

    private function process_image( $product_id, $image_id, $role ) {
        // Simple logic: overwrite if setting says so, or skip if exists
        $skip_existing = $this->settings->get( 'skip_existing', 'yes' );
        if ( 'yes' === $skip_existing ) {
            $alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
            if ( ! empty( $alt ) ) {
                return;
            }
        }

        $result = $this->api_client->generate_for_product( $product_id, $image_id, $role );
        if ( ! is_wp_error( $result ) && ! empty( $result['alt_text'] ) ) {
            update_post_meta( $image_id, '_wp_attachment_image_alt', sanitize_text_field( $result['alt_text'] ) );

            // Per-attachment local quality score (image-level) — calculator returns data; we persist.
            $attachment_score = $this->calculator->calculate_for_attachment( $image_id, 'product' );
            update_post_meta( $image_id, '_prodimg_seo_1972adm_quality_score', $attachment_score['score'] );
            Prodimg_Seo_1972adm_Status_Taxonomy::set_status_for_attachment( $image_id, $attachment_score['band'] );

            $prodimg_seo_score = $this->calculator->calculate_for_product( $product_id );
            update_post_meta( $product_id, '_prodimg_seo_1972adm_score_local', $prodimg_seo_score['score'] );
            update_post_meta( $product_id, '_prodimg_seo_1972adm_score_breakdown', wp_json_encode( $prodimg_seo_score ) );

            if ( isset( $result['quality_score'] ) ) {
                // Legacy: product-level remote score from the API. Per-attachment local score lives in _prodimg_seo_1972adm_quality_score (see Score_Calculator).
                update_post_meta( $product_id, '_prodimg_seo_1972adm_score', absint( $result['quality_score'] ) );
            }
            update_post_meta( $product_id, '_prodimg_seo_1972adm_processed_at', time() );
        }
    }

    private function update_progress() {
        $progress = get_transient( 'prodimg_seo_1972adm_bulk_progress' );
        if ( is_array( $progress ) ) {
            $progress['completed_batches']++;
            if ( $progress['completed_batches'] >= $progress['total_batches'] ) {
                $progress['status'] = 'completed';
            }
            set_transient( 'prodimg_seo_1972adm_bulk_progress', $progress, HOUR_IN_SECONDS );
        }
    }
}
