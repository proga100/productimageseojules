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
        header( 'Content-Disposition: attachment; filename=product-image-seo-audit-' . date( 'Y-m-d' ) . '.csv' );

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

        $products = wc_get_products( array(
            'limit'  => -1,
            'status' => 'publish',
        ) );

        foreach ( $products as $product ) {
            $pid = $product->get_id();
            $sku = $product->get_sku();
            $name = $product->get_name();

            // Status and score
            $terms = wp_get_post_terms( $pid, Prodimg_Seo_1972adm_Status_Taxonomy::TAXONOMY, array( 'fields' => 'names' ) );
            $status = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0] : '';
            $score = get_post_meta( $pid, '_prodimg_seo_1972adm_score', true );

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

        fclose( $output );
        exit;
    }

    private function write_row( $output, $pid, $sku, $name, $type, $image_id, $score, $status ) {
        $url = wp_get_attachment_url( $image_id );
        $alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

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
