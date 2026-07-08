<?php
/**
 * Auto Generator.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Auto_Generator {

    private $settings;
    private $bulk_processor;
    private $calculator;

    public function __construct( Prodimg_Seo_1972adm_Settings $settings, Prodimg_Seo_1972adm_Bulk_Processor $bulk_processor, Prodimg_Seo_1972adm_Score_Calculator $calculator ) {
        $this->settings       = $settings;
        $this->bulk_processor = $bulk_processor;
        $this->calculator     = $calculator;
    }

    public function init_hooks() {
        add_action( 'woocommerce_new_product', array( $this, 'auto_generate' ), 10, 2 );
        add_action( 'woocommerce_update_product', array( $this, 'auto_generate' ), 10, 2 );
    }

    public function auto_generate( $product_id, $product ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- $product is provided by the woocommerce_new_product / woocommerce_update_product hook signature.
        // Check if auto-generate is enabled
        $auto_generate = $this->settings->get( 'auto_generate', 'no' );
        if ( 'yes' !== $auto_generate ) {
            return;
        }

        // Skip if no API key is configured — avoids enqueueing no-op Action Scheduler jobs.
        $api_key = $this->settings->get( 'api_key', '' );
        if ( empty( $api_key ) ) {
            return;
        }

        // Avoid infinite loops if generation somehow saves product
        if ( doing_action( 'prodimg_seo_1972adm_process_product_batch' ) ) {
            return;
        }

        // Just enqueue this single product for async processing
        $this->bulk_processor->enqueue_batch( array( $product_id ) );

        // Also write a synchronous local-score snapshot so the UI reflects current state immediately.
        $prodimg_seo_score = $this->calculator->calculate_for_product( $product_id );
        update_post_meta( $product_id, '_prodimg_seo_1972adm_score_local', $prodimg_seo_score['score'] );
        update_post_meta( $product_id, '_prodimg_seo_1972adm_score_breakdown', wp_json_encode( $prodimg_seo_score ) );
    }
}
