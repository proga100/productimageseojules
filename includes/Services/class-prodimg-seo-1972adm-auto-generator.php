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

    public function __construct( Prodimg_Seo_1972adm_Settings $settings, Prodimg_Seo_1972adm_Bulk_Processor $bulk_processor ) {
        $this->settings       = $settings;
        $this->bulk_processor = $bulk_processor;
    }

    public function init_hooks() {
        add_action( 'woocommerce_new_product', array( $this, 'auto_generate' ), 10, 2 );
        add_action( 'woocommerce_update_product', array( $this, 'auto_generate' ), 10, 2 );
    }

    public function auto_generate( $product_id, $product ) {
        // Check if auto-generate is enabled
        $auto_generate = $this->settings->get( 'auto_generate', 'no' );
        if ( 'yes' !== $auto_generate ) {
            return;
        }

        // Avoid infinite loops if generation somehow saves product
        if ( doing_action( 'prodimg_seo_1972adm_process_product_batch' ) ) {
            return;
        }

        // Just enqueue this single product for async processing
        $this->bulk_processor->enqueue_batch( array( $product_id ) );
    }
}
