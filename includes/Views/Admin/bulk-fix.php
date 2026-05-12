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

    <div class="prodimg-card">
        <p><?php esc_html_e( 'Generate alt text for all products needing review.', 'product-image-seo' ); ?></p>

        <p>
            <button type="button" class="button button-primary" id="prodimg-seo-bulk-start">
                <?php esc_html_e( 'Start Bulk Fix', 'product-image-seo' ); ?>
            </button>
        </p>

        <div id="prodimg-seo-bulk-progress-container" hidden>
            <div class="prodimg-progress">
                <div id="prodimg-seo-bulk-progress-bar" class="prodimg-progress__bar" style="width: 0%;"></div>
            </div>
            <p id="prodimg-seo-bulk-progress-text" class="prodimg-progress__label">0%</p>
        </div>
    </div>

    <div id="prodimg-seo-bulk-results" class="prodimg-card" hidden>
        <h2 class="prodimg-card__title"><?php esc_html_e( 'Run summary', 'product-image-seo' ); ?></h2>
        <div id="prodimg-seo-bulk-results-body"></div>
    </div>
</div>
