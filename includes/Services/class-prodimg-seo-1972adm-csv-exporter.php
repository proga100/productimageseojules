<?php
/**
 * CSV Exporter.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Csv_Exporter {

    private $statistics;

    public function __construct( Prodimg_Seo_1972adm_Statistics $statistics ) {
        $this->statistics = $statistics;
    }

    public function init_hooks() {
        add_action( 'wp_ajax_prodimg_seo_1972adm_export_csv', array( $this, 'ajax_export_csv' ) );
    }

    public function ajax_export_csv() {
        // Nonce check or simple capability check (if direct link from admin)
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'product-image-seo' ) );
        }

        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['nonce'] ) ), 'prodimg_seo_1972adm_admin_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'product-image-seo' ) );
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=product-image-seo-audit-' . gmdate( 'Y-m-d' ) . '.csv' );

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- php://output stream not supported by WP_Filesystem.
        $output = fopen( 'php://output', 'w' );

        // CSV headers
        fputcsv( $output, array(
            'product_id',
            'sku',
            'product_name',
            'image_type',
            'image_url',
            'alt_text',
            'quality_score',
            'status'
        ) );

        // Enumerate product IDs directly. wc_get_products() implicitly tax-filters
        // on product_type and silently drops products lacking that term (common in
        // legacy/imported data); a plain post query lists every published product.
        $product_ids = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ) );

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            $pid = $product->get_id();
            $sku = $product->get_sku();
            $name = $product->get_name();

            // Status, plus a product-level score used only as a per-image fallback.
            $terms = wp_get_post_terms( $pid, Prodimg_Seo_1972adm_Status_Taxonomy::TAXONOMY, array( 'fields' => 'names' ) );
            $status = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0] : '';
            $score = get_post_meta( $pid, '_prodimg_seo_1972adm_score_local', true );

            // Featured Image
            $fid = $product->get_image_id();
            if ( $fid ) {
                $this->write_row( $output, $pid, $sku, $name, 'featured', $fid, $score, $status );
            }

            // Gallery
            $gids = $product->get_gallery_image_ids();
            foreach ( $gids as $gid ) {
                $this->write_row( $output, $pid, $sku, $name, 'gallery', $gid, $score, $status );
            }

            // Variations
            if ( $product->is_type( 'variable' ) ) {
                foreach ( $product->get_children() as $child_id ) {
                    $child = wc_get_product( $child_id );
                    if ( $child ) {
                        $vid = $child->get_image_id();
                        if ( $vid ) {
                            $this->write_row( $output, $child_id, $child->get_sku(), $child->get_name(), 'variation', $vid, $score, $status );
                        }
                    }
                }
            }
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream not supported by WP_Filesystem.
        fclose( $output );
        exit;
    }

    private function write_row( $output, $pid, $sku, $name, $type, $image_id, $fallback_score, $status ) {
        $url = wp_get_attachment_url( $image_id );
        $alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

        // Export is per-image, so prefer the attachment's own quality score.
        // Fall back to the product-level local score when the image has none.
        $score = get_post_meta( $image_id, '_prodimg_seo_1972adm_quality_score', true );
        if ( ! is_numeric( $score ) ) {
            $score = $fallback_score;
        }

        fputcsv( $output, array(
            $pid,
            $sku,
            $name,
            $type,
            $url,
            $alt,
            $score,
            $status
        ) );
    }
}
