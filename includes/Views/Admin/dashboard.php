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

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page slug for active nav state.
$prodimg_seo_current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'prodimg-seo-dashboard';
?>
<div class="wrap prodimg-app">

    <header class="prodimg-page-header">
        <div class="prodimg-page-header__inner">
            <div class="prodimg-page-header__titleblock">
                <h1 class="prodimg-page-header__title"><?php esc_html_e( 'Dashboard', 'product-image-seo' ); ?></h1>
                <p class="prodimg-page-header__subtitle"><?php esc_html_e( 'Image SEO at a glance', 'product-image-seo' ); ?></p>
            </div>
            <div class="prodimg-page-header__actions">
                <button type="button" class="button button-primary" id="prodimg-seo-scan-catalog">
                    <?php esc_html_e( 'Run Audit', 'product-image-seo' ); ?>
                </button>
                <span class="spinner" id="prodimg-seo-scan-spinner"></span>
            </div>
        </div>
        <nav class="prodimg-segnav" aria-label="<?php esc_attr_e( 'Plugin sections', 'product-image-seo' ); ?>">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-dashboard' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-dashboard' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Dashboard', 'product-image-seo' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-report' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-report' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Audit', 'product-image-seo' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-catalog' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-catalog' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Product Images', 'product-image-seo' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-bulk' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-bulk' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Bulk Fix', 'product-image-seo' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-settings' ) ); ?>"
               class="prodimg-segnav__item<?php echo ( 'prodimg-seo-settings' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
                <?php esc_html_e( 'Settings', 'product-image-seo' ); ?>
            </a>
        </nav>
    </header>

    <div id="prodimg-seo-scan-result" style="margin-top: 10px;"></div>

    <?php if ( 0 === $prodimg_seo_total ) : ?>

        <div class="prodimg-empty-state">
            <span class="prodimg-empty-state__icon-bubble">
                <span class="dashicons dashicons-format-image"></span>
            </span>
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
            </p>
        </div>

    <?php else : ?>

        <div class="prodimg-grid">

            <!-- Avg score gauge card -->
            <div class="prodimg-card">
                <h2 class="prodimg-card__title"><?php esc_html_e( 'Average score', 'product-image-seo' ); ?></h2>
                <svg class="prodimg-score-gauge prodimg-score-gauge--<?php echo esc_attr( $prodimg_seo_gauge_band ); ?>"
                     viewBox="0 0 120 120"
                     data-score="<?php echo esc_attr( $prodimg_seo_avg_score ); ?>"
                     role="img"
                     aria-label="<?php echo esc_attr( sprintf( /* translators: %d score value */ __( 'Score %d', 'product-image-seo' ), $prodimg_seo_avg_score ) ); ?>">
                    <circle class="prodimg-score-gauge__track"    cx="60" cy="60" r="52" />
                    <circle class="prodimg-score-gauge__progress" cx="60" cy="60" r="52" />
                    <text   class="prodimg-score-gauge__value"    x="60" y="64" text-anchor="middle">0</text>
                    <text   class="prodimg-score-gauge__label"    x="60" y="82" text-anchor="middle"><?php esc_html_e( 'Score', 'product-image-seo' ); ?></text>
                </svg>
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

    <?php endif; ?>

</div>
