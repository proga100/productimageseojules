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

    private $calculator;

    public function __construct( Prodimg_Seo_1972adm_Score_Calculator $calculator ) {
        $this->calculator = $calculator;
    }

    public function get_stats() {
        $stats = array(
            'total_products' => 0,
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
        $total_score = 0;
        $scored_products = 0;

        foreach ( $products as $pid ) {
            // Get status
            $terms = wp_get_post_terms( $pid, Prodimg_Seo_1972adm_Status_Taxonomy::TAXONOMY, array( 'fields' => 'names' ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $status = $terms[0];
                if ( isset( $stats['by_status'][ $status ] ) ) {
                    $stats['by_status'][ $status ]++;
                }
            }

            // Missing alt means it's needs_review or partial
            if ( ! empty( $terms ) && in_array( $terms[0], array( 'needs_review', 'partial' ), true ) ) {
                $stats['missing_alt']++;
            }

            // Quality score: prefer local, fall back to remote.
            $score = get_post_meta( $pid, '_prodimg_seo_1972adm_score_local', true );
            if ( ! is_numeric( $score ) ) {
                $score = get_post_meta( $pid, '_prodimg_seo_1972adm_score', true );
            }
            if ( is_numeric( $score ) ) {
                $score_int = intval( $score );
                $total_score += $score_int;
                $scored_products++;
                if ( $score_int < 50 ) {
                    $stats['weak_alt']++;
                }

                // Bucket by the shared calculator band vocabulary so the Audit
                // Report distribution matches the per-image row badges. Prefer the
                // stored rollup band (worst image); fall back to band-from-score.
                $band      = '';
                $breakdown = get_post_meta( $pid, '_prodimg_seo_1972adm_score_breakdown', true );
                if ( $breakdown ) {
                    $breakdown_data = json_decode( $breakdown, true );
                    if ( is_array( $breakdown_data ) && ! empty( $breakdown_data['band'] ) ) {
                        $band = sanitize_key( $breakdown_data['band'] );
                    }
                }
                if ( '' === $band || ! isset( $stats['by_band'][ $band ] ) ) {
                    $band = $this->calculator->get_band_from_score( $score_int );
                }
                if ( isset( $stats['by_band'][ $band ] ) ) {
                    $stats['by_band'][ $band ]++;
                }
            }

            // Breakdown from coverage JSON
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
        }

        if ( $scored_products > 0 ) {
            $stats['avg_score'] = round( $total_score / $scored_products );
        }

        return $stats;
    }
}
