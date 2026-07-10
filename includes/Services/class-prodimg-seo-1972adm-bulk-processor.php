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

    /**
     * Build the list of products that actually have work to do.
     *
     * With "skip existing" on (the default), this returns only products that
     * own at least one image missing alt text — so a bulk run targets the
     * images that need it instead of iterating the whole "needs review" set
     * (which includes products with no images, or images already covered).
     * With skip-existing off (overwrite mode), it returns every product that
     * has at least one image.
     *
     * @return int[] Product IDs.
     */
    public function get_target_product_ids() {
        $skip_existing = 'yes' === $this->settings->get( 'skip_existing', 'yes' );

        $product_ids = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ) );

        $targets = array();
        foreach ( $product_ids as $pid ) {
            $product = wc_get_product( $pid );
            if ( ! $product ) {
                continue;
            }

            $image_ids = $this->calculator->get_product_image_ids( $product );
            if ( empty( $image_ids ) ) {
                continue; // No images — nothing to generate.
            }

            if ( ! $skip_existing ) {
                $targets[] = $pid; // Overwrite mode: any product with images.
                continue;
            }

            // Skip mode: include only if at least one image is missing alt text.
            foreach ( $image_ids as $att_id ) {
                $alt = get_post_meta( absint( $att_id ), '_wp_attachment_image_alt', true );
                if ( empty( $alt ) ) {
                    $targets[] = $pid;
                    break;
                }
            }
        }

        return $targets;
    }

    public function enqueue_batch( array $product_ids ) {
        if ( ! function_exists( 'as_enqueue_async_action' ) ) {
            return new WP_Error( 'action_scheduler_missing', __( 'Action Scheduler is not available.', 'product-image-seo' ) );
        }

        $batches = array_chunk( $product_ids, 10 );
        $total_batches = count( $batches );

        // Setup transient for progress tracking. Image counters are the honest
        // measure of a run (products may hold several images, or none).
        set_transient( 'prodimg_seo_1972adm_bulk_progress', array(
            'total_batches'     => $total_batches,
            'completed_batches' => 0,
            'total_products'    => count( $product_ids ),
            'images_generated'  => 0,
            'images_skipped'    => 0,
            'images_failed'     => 0,
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

        $counts = array(
            'generated' => 0,
            'skipped'   => 0,
            'failed'    => 0,
        );

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            $featured_id = $product->get_image_id();
            if ( $featured_id ) {
                $counts[ $this->process_image( $product_id, $featured_id, 'featured' ) ]++;
            }

            $gallery_ids = $product->get_gallery_image_ids();
            foreach ( $gallery_ids as $gid ) {
                $counts[ $this->process_image( $product_id, $gid, 'gallery' ) ]++;
            }

            if ( $product->is_type( 'variable' ) ) {
                foreach ( $product->get_children() as $child_id ) {
                    $child = wc_get_product( $child_id );
                    if ( $child ) {
                        $vid = $child->get_image_id();
                        if ( $vid ) {
                            $counts[ $this->process_image( $child_id, $vid, 'variation' ) ]++;
                        }
                    }
                }
            }
        }

        $this->update_progress( $counts );
    }

    /**
     * Generate alt text for a single image.
     *
     * @param int    $product_id Product (or variation) ID for context.
     * @param int    $image_id   Attachment ID.
     * @param string $role       featured | gallery | variation.
     * @return string Outcome: 'generated' | 'skipped' | 'failed'.
     */
    private function process_image( $product_id, $image_id, $role ) {
        // Skip images that already have alt text unless overwriting is enabled.
        $skip_existing = $this->settings->get( 'skip_existing', 'yes' );
        if ( 'yes' === $skip_existing ) {
            $alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
            if ( ! empty( $alt ) ) {
                return 'skipped';
            }
        }

        $result = $this->api_client->generate_for_product( $product_id, $image_id, $role );
        if ( is_wp_error( $result ) || empty( $result['alt_text'] ) ) {
            return 'failed';
        }

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

        return 'generated';
    }

    /**
     * Merge a batch's image outcomes into the progress transient.
     *
     * @param array $counts { generated:int, skipped:int, failed:int }.
     * @return void
     */
    private function update_progress( $counts = array() ) {
        // A batch just wrote new alt text / scores — refresh the dashboard stats.
        Prodimg_Seo_1972adm_Statistics::flush_cache();

        $progress = get_transient( 'prodimg_seo_1972adm_bulk_progress' );
        if ( is_array( $progress ) ) {
            $progress['completed_batches']++;
            $progress['images_generated'] = ( isset( $progress['images_generated'] ) ? $progress['images_generated'] : 0 ) + ( isset( $counts['generated'] ) ? $counts['generated'] : 0 );
            $progress['images_skipped']   = ( isset( $progress['images_skipped'] ) ? $progress['images_skipped'] : 0 ) + ( isset( $counts['skipped'] ) ? $counts['skipped'] : 0 );
            $progress['images_failed']    = ( isset( $progress['images_failed'] ) ? $progress['images_failed'] : 0 ) + ( isset( $counts['failed'] ) ? $counts['failed'] : 0 );
            if ( $progress['completed_batches'] >= $progress['total_batches'] ) {
                $progress['status'] = 'completed';
            }
            set_transient( 'prodimg_seo_1972adm_bulk_progress', $progress, HOUR_IN_SECONDS );
        }
    }
}
