<?php
/**
 * Bulk Fix View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Bulk Fix', 'product-image-seo' ); ?></h1>
    <p><?php esc_html_e( 'Generate alt text for all products needing review.', 'product-image-seo' ); ?></p>

    <button type="button" class="button button-primary" id="prodimg-seo-bulk-start">
        <?php esc_html_e( 'Start Bulk Fix', 'product-image-seo' ); ?>
    </button>

    <div id="prodimg-seo-bulk-progress-container" style="display:none; margin-top:20px; max-width: 400px;">
        <div style="background: #e5e5e5; border-radius: 3px; height: 20px; width: 100%;">
            <div id="prodimg-seo-bulk-progress-bar" style="background: #7f54b3; height: 100%; width: 0%; border-radius: 3px;"></div>
        </div>
        <p id="prodimg-seo-bulk-progress-text" style="text-align: center; font-weight: bold; margin-top: 5px;">0%</p>
    </div>
</div>
