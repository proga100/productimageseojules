<?php
/**
 * Coverage Calculator.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Coverage_Calculator {

    public function calculate( $product ) {
        $product_id = $product->get_id();

        $featured_id = $product->get_image_id();
        $gallery_ids = $product->get_gallery_image_ids();

        $stats = array(
            'featured' => false,
            'gallery' => 0,
            'gallery_total' => count( $gallery_ids ),
            'variations' => 0,
            'variations_total' => 0,
        );

        if ( $featured_id ) {
            $alt = get_post_meta( $featured_id, '_wp_attachment_image_alt', true );
            if ( ! empty( $alt ) ) {
                $stats['featured'] = true;
            }
        }

        foreach ( $gallery_ids as $gid ) {
            $alt = get_post_meta( $gid, '_wp_attachment_image_alt', true );
            if ( ! empty( $alt ) ) {
                $stats['gallery']++;
            }
        }

        if ( $product->is_type( 'variable' ) ) {
            $children = $product->get_children();
            $stats['variations_total'] = count( $children );

            foreach ( $children as $child_id ) {
                $child = wc_get_product( $child_id );
                if ( $child ) {
                    $vid = $child->get_image_id();
                    if ( $vid ) {
                        $alt = get_post_meta( $vid, '_wp_attachment_image_alt', true );
                        if ( ! empty( $alt ) ) {
                            $stats['variations']++;
                        }
                    } else {
                        // if variation has no image, we might consider it covered or skip it
                        // let's count it as covered if it has no image because it falls back to featured.
                        // Actually, only count variations with their own images, or subtract total?
                        // Let's decrement total if variation has no image, so coverage isn't penalized
                        $stats['variations_total']--;
                    }
                }
            }
        }

        update_post_meta( $product_id, '_prodimg_seo_1972adm_coverage', wp_json_encode( $stats ) );

        $this->determine_and_save_status( $product_id, $stats );

        return $stats;
    }

    private function determine_and_save_status( $product_id, $stats ) {
        // Needs review: featured missing or gallery missing
        // Partial: some gallery missing or some variations missing
        // Optimized: featured + gallery have alt text
        // Excellent: optimized + variations have alt text

        $status = 'needs_review';

        $has_featured = $stats['featured'];
        $gallery_full = $stats['gallery_total'] > 0 ? ( $stats['gallery'] === $stats['gallery_total'] ) : true;
        $var_full = $stats['variations_total'] > 0 ? ( $stats['variations'] === $stats['variations_total'] ) : true;
        $gallery_partial = $stats['gallery'] > 0 && $stats['gallery'] < $stats['gallery_total'];
        $var_partial = $stats['variations'] > 0 && $stats['variations'] < $stats['variations_total'];

        if ( $has_featured && $gallery_full ) {
            $status = 'optimized';
            if ( $var_full ) {
                $status = 'excellent';
            }
            if ( $var_partial ) {
                $status = 'partial';
            }
        } elseif ( $has_featured || $gallery_partial || $var_partial ) {
            $status = 'partial';
        }

        // Ensure status gets saved
        Prodimg_Seo_1972adm_Status_Taxonomy::set_status( $product_id, $status );
    }
}
