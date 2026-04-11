<?php
/**
 * Dashboard View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Welcome to your store\'s image SEO audit', 'product-image-seo' ); ?></h1>
    <p><?php esc_html_e( 'Scan your catalog and view overall stats here.', 'product-image-seo' ); ?></p>

    <button type="button" class="button button-primary" id="prodimg-seo-scan-catalog">
        <?php esc_html_e( 'Start Catalog Scan', 'product-image-seo' ); ?>
    </button>
    <span class="spinner" id="prodimg-seo-scan-spinner"></span>
    <div id="prodimg-seo-scan-result" style="margin-top: 10px;"></div>
</div>
