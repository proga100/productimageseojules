<?php
/**
 * Dashboard View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$prodimg_seo_stats = $this->statistics->get_stats();

$prodimg_seo_avg_score   = isset( $prodimg_seo_stats['avg_score'] ) ? intval( $prodimg_seo_stats['avg_score'] ) : 0;
$prodimg_seo_total       = isset( $prodimg_seo_stats['total_products'] ) ? intval( $prodimg_seo_stats['total_products'] ) : 0;
$prodimg_seo_missing     = isset( $prodimg_seo_stats['missing_alt'] ) ? intval( $prodimg_seo_stats['missing_alt'] ) : 0;
$prodimg_seo_weak        = isset( $prodimg_seo_stats['weak_alt'] ) ? intval( $prodimg_seo_stats['weak_alt'] ) : 0;
$prodimg_seo_breakdown   = isset( $prodimg_seo_stats['breakdown'] ) ? $prodimg_seo_stats['breakdown'] : array(
    'featured'   => 0,
    'gallery'    => 0,
    'variations' => 0,
);

$prodimg_seo_gauge_band = $prodimg_seo_avg_score >= 80 ? 'good' : ( $prodimg_seo_avg_score >= 50 ? 'ok' : 'poor' );

$prodimg_seo_csv_url = wp_nonce_url(
    admin_url( 'admin-ajax.php?action=prodimg_seo_1972adm_export_csv' ),
    'prodimg_seo_1972adm_admin_nonce',
    'nonce'
);
?>
<div class="wrap">
    <h1>
        <?php esc_html_e( 'Product Image SEO', 'product-image-seo' ); ?>
        <?php if ( defined( 'PRODIMG_SEO_1972ADM_VERSION' ) ) : ?>
            <span class="prodimg-version-tag">v<?php echo esc_html( PRODIMG_SEO_1972ADM_VERSION ); ?></span>
        <?php endif; ?>
    </h1>

    <?php if ( 0 === $prodimg_seo_total ) : ?>

        <div class="prodimg-empty-state">
            <div class="prodimg-empty-state__icon">
                <span class="dashicons dashicons-format-image"></span>
            </div>
            <h2 class="prodimg-empty-state__title">
                <?php esc_html_e( 'Welcome to Product Image SEO', 'product-image-seo' ); ?>
            </h2>
            <p class="prodimg-empty-state__body">
                <?php esc_html_e( 'Once you have products in your catalog, run a scan to audit image alt text and surface SEO opportunities.', 'product-image-seo' ); ?>
            </p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-settings' ) ); ?>" class="button button-secondary">
                    <?php esc_html_e( 'Visit Settings', 'product-image-seo' ); ?>
                </a>
                <button type="button" class="button button-primary" id="prodimg-seo-scan-catalog">
                    <?php esc_html_e( 'Start Catalog Scan', 'product-image-seo' ); ?>
                </button>
                <span class="spinner" id="prodimg-seo-scan-spinner"></span>
            </p>
            <div id="prodimg-seo-scan-result" style="margin-top: 10px;"></div>
        </div>

    <?php else : ?>

        <p><?php esc_html_e( 'Snapshot of your catalog\'s image SEO health.', 'product-image-seo' ); ?></p>

        <div class="prodimg-grid">

            <!-- Avg score gauge card -->
            <div class="prodimg-card">
                <h2 class="prodimg-card__title"><?php esc_html_e( 'Average score', 'product-image-seo' ); ?></h2>
                <div
                    class="prodimg-score-gauge prodimg-score-gauge--<?php echo esc_attr( $prodimg_seo_gauge_band ); ?>"
                    data-score="<?php echo esc_attr( $prodimg_seo_avg_score ); ?>"
                    style="--prodimg-gauge-pct: <?php echo esc_attr( $prodimg_seo_avg_score ); ?>;"
                >
                    <div style="text-align:center;">
                        <span class="prodimg-score-gauge__value"><?php echo esc_html( $prodimg_seo_avg_score ); ?></span>
                        <span class="prodimg-score-gauge__label"><?php esc_html_e( '/ 100', 'product-image-seo' ); ?></span>
                    </div>
                </div>
                <p class="prodimg-card__footnote">
                    <?php
                    /* translators: %d total products */
                    printf( esc_html__( 'Across %d products.', 'product-image-seo' ), intval( $prodimg_seo_total ) );
                    ?>
                </p>
            </div>

            <!-- Missing alt -->
            <div class="prodimg-card">
                <h2 class="prodimg-card__title"><?php esc_html_e( 'Missing alt text', 'product-image-seo' ); ?></h2>
                <p class="prodimg-card__value"><?php echo esc_html( $prodimg_seo_missing ); ?></p>
                <p class="prodimg-card__footnote"><?php esc_html_e( 'Products needing review', 'product-image-seo' ); ?></p>
                <a class="prodimg-card__cta" href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-bulk' ) ); ?>">
                    <?php esc_html_e( 'Fix in bulk →', 'product-image-seo' ); ?>
                </a>
            </div>

            <!-- Weak alt -->
            <div class="prodimg-card">
                <h2 class="prodimg-card__title"><?php esc_html_e( 'Weak alt text', 'product-image-seo' ); ?></h2>
                <p class="prodimg-card__value"><?php echo esc_html( $prodimg_seo_weak ); ?></p>
                <p class="prodimg-card__footnote"><?php esc_html_e( 'Could be improved', 'product-image-seo' ); ?></p>
                <a class="prodimg-card__cta" href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-catalog&prodimg_status=partial' ) ); ?>">
                    <?php esc_html_e( 'Review →', 'product-image-seo' ); ?>
                </a>
            </div>

            <!-- Coverage breakdown -->
            <div class="prodimg-card">
                <h2 class="prodimg-card__title"><?php esc_html_e( 'Coverage', 'product-image-seo' ); ?></h2>
                <ul class="prodimg-coverage-list">
                    <li>
                        <span><?php esc_html_e( 'Featured', 'product-image-seo' ); ?></span>
                        <strong><?php echo esc_html( intval( $prodimg_seo_breakdown['featured'] ?? 0 ) ); ?></strong>
                    </li>
                    <li>
                        <span><?php esc_html_e( 'Gallery', 'product-image-seo' ); ?></span>
                        <strong><?php echo esc_html( intval( $prodimg_seo_breakdown['gallery'] ?? 0 ) ); ?></strong>
                    </li>
                    <li>
                        <span><?php esc_html_e( 'Variations', 'product-image-seo' ); ?></span>
                        <strong><?php echo esc_html( intval( $prodimg_seo_breakdown['variations'] ?? 0 ) ); ?></strong>
                    </li>
                </ul>
            </div>

        </div>

        <div class="prodimg-quick-actions">
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-catalog' ) ); ?>">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e( 'Run Catalog Audit', 'product-image-seo' ); ?>
            </a>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-bulk' ) ); ?>">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e( 'Bulk Fix', 'product-image-seo' ); ?>
            </a>
            <a class="button" href="<?php echo esc_url( $prodimg_seo_csv_url ); ?>">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e( 'Export CSV', 'product-image-seo' ); ?>
            </a>
        </div>

        <p>
            <button type="button" class="button" id="prodimg-seo-scan-catalog">
                <?php esc_html_e( 'Re-scan catalog', 'product-image-seo' ); ?>
            </button>
            <span class="spinner" id="prodimg-seo-scan-spinner"></span>
        </p>
        <div id="prodimg-seo-scan-result" style="margin-top: 10px;"></div>

    <?php endif; ?>

</div>
