<?php
/**
 * Bulk Fix View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page slug for active nav state.
$prodimg_seo_current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'prodimg-seo-bulk';
?>
<div class="wrap prodimg-app">

    <header class="prodimg-page-header">
        <div class="prodimg-page-header__inner">
            <div class="prodimg-page-header__titleblock">
                <h1 class="prodimg-page-header__title"><?php esc_html_e( 'Bulk Fix', 'product-image-seo' ); ?></h1>
                <p class="prodimg-page-header__subtitle"><?php esc_html_e( 'Generate alt text for every product image needing review', 'product-image-seo' ); ?></p>
            </div>
        </div>
        <nav class="prodimg-segnav" aria-label="<?php esc_attr_e( 'Plugin sections', 'product-image-seo' ); ?>">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-dashboard' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-dashboard' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Dashboard', 'product-image-seo' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-catalog' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-catalog' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Product Images', 'product-image-seo' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-bulk' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-bulk' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Bulk Fix', 'product-image-seo' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-report' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-report' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Audit Report', 'product-image-seo' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-settings' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-settings' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Settings', 'product-image-seo' ); ?>
            </a>
        </nav>
    </header>

    <?php $prodimg_seo_bulk_pending = intval( $this->statistics->get_stats()['missing_alt'] ?? 0 ); ?>
    <div class="prodimg-card">
        <p class="prodimg-card__value"><?php echo esc_html( $prodimg_seo_bulk_pending ); ?></p>
        <p class="prodimg-card__footnote"><?php esc_html_e( 'Product images currently missing alt text.', 'product-image-seo' ); ?></p>

        <p class="prodimg-bulk-description">
            <?php esc_html_e( 'Bulk Fix generates AI alt text for every product image needing review. It runs in the background, so you can leave this page while it works. Images that already have alt text are skipped unless overwriting is enabled in Settings. Each generated image uses one API credit.', 'product-image-seo' ); ?>
        </p>

        <p>
            <button type="button" class="button button-primary" id="prodimg-seo-bulk-start">
                <?php esc_html_e( 'Start Bulk Fix', 'product-image-seo' ); ?>
            </button>
        </p>

        <div id="prodimg-seo-bulk-progress-container" hidden>
            <div class="prodimg-bulkfix-status" role="status" aria-live="polite">
                <span class="prodimg-spinner" id="prodimg-seo-bulk-spinner" aria-hidden="true"></span>
                <strong id="prodimg-seo-bulk-status-text"><?php esc_html_e( 'Queued — waiting for the background processor…', 'product-image-seo' ); ?></strong>
            </div>
            <div class="prodimg-progress">
                <div id="prodimg-seo-bulk-progress-bar" class="prodimg-progress__bar" style="width: 0%;"></div>
            </div>
            <p id="prodimg-seo-bulk-progress-text" class="prodimg-progress__label"></p>
        </div>
    </div>

    <div id="prodimg-seo-bulk-results" class="prodimg-card" hidden>
        <h2 class="prodimg-card__title"><?php esc_html_e( 'Run summary', 'product-image-seo' ); ?></h2>
        <div id="prodimg-seo-bulk-results-body"></div>
    </div>
</div>
