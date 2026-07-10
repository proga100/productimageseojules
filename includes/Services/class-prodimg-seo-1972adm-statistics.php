<?php
/**
 * Statistics.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Statistics {

    /**
     * Transient key for the assembled stats array.
     */
    const CACHE_KEY = 'prodimg_seo_1972adm_stats_cache';

    private $calculator;

    public function __construct( Prodimg_Seo_1972adm_Score_Calculator $calculator ) {
        $this->calculator = $calculator;
    }

    /**
     * Delete the cached stats so the next read recomputes from current data.
     * Call after any alt-text / score change.
     *
     * @return void
     */
    public static function flush_cache() {
        delete_transient( self::CACHE_KEY );
    }

    /**
     * Assemble dashboard/report statistics.
     *
     * The headline numbers are IMAGE-level: every image that belongs to a
     * product (featured, gallery, variation) is scored, a missing-alt image
     * counts as 0, and avg_score is the mean across all product images. This
     * matches the per-image catalog table and keeps the dashboard internally
     * consistent (the average can no longer read high while many images are
     * missing alt text). total_products and the coverage breakdown stay
     * product-level as legitimate context.
     *
     * @return array
     */
    public function get_stats() {
        $cached = get_transient( self::CACHE_KEY );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $stats = array(
            'total_products' => 0,
            'total_images'   => 0,
            'missing_alt'    => 0,
            'weak_alt'       => 0,
            'avg_score'      => 0,
            'breakdown'      => array(
                'featured'   => 0,
                'gallery'    => 0,
                'variations' => 0,
            ),
            'by_status'      => array(
                'needs_review' => 0,
                'partial'      => 0,
                'optimized'    => 0,
                'excellent'    => 0,
                'ignored'      => 0,
            ),
            'by_band'        => array(
                'missing'    => 0,
                'weak'       => 0,
                'good'       => 0,
                'excellent'  => 0,
                'decorative' => 0,
            ),
        );

        // Enumerate product IDs directly. wc_get_products() implicitly tax-filters
        // on product_type and silently drops products lacking that term (common in
        // legacy/imported data); a plain post query counts every published product.
        $products = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ) );

        $stats['total_products'] = count( $products );

        $total_score     = 0;
        $scoreable_images = 0; // Excludes decorative images from the average.
        $seen_images     = array(); // Dedupe attachments shared across products.

        foreach ( $products as $pid ) {
            // Product-level status taxonomy (kept for by_status context).
            $terms = wp_get_post_terms( $pid, Prodimg_Seo_1972adm_Status_Taxonomy::TAXONOMY, array( 'fields' => 'names' ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $status = $terms[0];
                if ( isset( $stats['by_status'][ $status ] ) ) {
                    $stats['by_status'][ $status ]++;
                }
            }

            // Coverage breakdown (product-level context).
            $cov = get_post_meta( $pid, '_prodimg_seo_1972adm_coverage', true );
            if ( $cov ) {
                $cov_data = json_decode( $cov, true );
                if ( ! empty( $cov_data['featured'] ) ) {
                    $stats['breakdown']['featured']++;
                }
                if ( isset( $cov_data['gallery'] ) ) {
                    $stats['breakdown']['gallery'] += $cov_data['gallery'];
                }
                if ( isset( $cov_data['variations'] ) ) {
                    $stats['breakdown']['variations'] += $cov_data['variations'];
                }
            }

            // Image-level scoring: every product image contributes.
            $product = wc_get_product( $pid );
            if ( ! $product ) {
                continue;
            }

            foreach ( $this->calculator->get_product_image_ids( $product ) as $att_id ) {
                $att_id = absint( $att_id );
                if ( ! $att_id || isset( $seen_images[ $att_id ] ) ) {
                    continue;
                }
                $seen_images[ $att_id ] = true;

                list( $score_int, $band ) = $this->resolve_image_score( $att_id );

                $stats['total_images']++;

                if ( isset( $stats['by_band'][ $band ] ) ) {
                    $stats['by_band'][ $band ]++;
                }
                if ( 'missing' === $band ) {
                    $stats['missing_alt']++;
                } elseif ( 'weak' === $band ) {
                    $stats['weak_alt']++;
                }

                // Decorative images intentionally have empty alt (correct
                // accessibility practice), so they are not "scoreable" — excluding
                // them from the average avoids penalizing a store for handling
                // decorative images correctly. They still count in by_band and
                // total_images.
                if ( 'decorative' !== $band ) {
                    $total_score += $score_int;
                    $scoreable_images++;
                }
            }
        }

        if ( $scoreable_images > 0 ) {
            $stats['avg_score'] = (int) round( $total_score / $scoreable_images );
        }

        set_transient( self::CACHE_KEY, $stats, 5 * MINUTE_IN_SECONDS );

        return $stats;
    }

    /**
     * Resolve an attachment's score + band. Prefer the stored per-image quality
     * score (written by the scan / save / bulk paths); fall back to computing it
     * on the fly, which returns 0 / 'missing' for empty alt text.
     *
     * @param int $att_id Attachment ID.
     * @return array{0:int,1:string} [ score, band ]
     */
    private function resolve_image_score( $att_id ) {
        $stored = get_post_meta( $att_id, '_prodimg_seo_1972adm_quality_score', true );
        if ( is_numeric( $stored ) ) {
            $score_int = (int) $stored;
            $band      = '';
            $breakdown = get_post_meta( $att_id, '_prodimg_seo_1972adm_score_breakdown', true );
            if ( $breakdown ) {
                $breakdown_data = json_decode( $breakdown, true );
                if ( is_array( $breakdown_data ) && ! empty( $breakdown_data['band'] ) ) {
                    $band = sanitize_key( $breakdown_data['band'] );
                }
            }
            if ( '' === $band ) {
                $band = $this->calculator->get_band_from_score( $score_int );
            }
            return array( $score_int, $band );
        }

        $result = $this->calculator->calculate_for_attachment( $att_id, 'product' );
        return array( (int) $result['score'], (string) $result['band'] );
    }
}
