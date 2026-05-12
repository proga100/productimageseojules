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
        );

        $products = wc_get_products( array(
            'limit'  => -1,
            'status' => 'publish',
            'return' => 'ids',
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

            // Quality score (if set)
            $score = get_post_meta( $pid, '_prodimg_seo_1972adm_score', true );
            if ( is_numeric( $score ) ) {
                $total_score += intval( $score );
                $scored_products++;
                if ( intval( $score ) < 50 ) {
                    $stats['weak_alt']++;
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
