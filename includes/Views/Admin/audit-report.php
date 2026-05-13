<?php
/**
 * Audit Report View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$prodimg_seo_stats = $this->statistics->get_stats();

$prodimg_seo_avg_score = isset( $prodimg_seo_stats['avg_score'] ) ? intval( $prodimg_seo_stats['avg_score'] ) : 0;
$prodimg_seo_total     = isset( $prodimg_seo_stats['total_products'] ) ? intval( $prodimg_seo_stats['total_products'] ) : 0;
$prodimg_seo_missing   = isset( $prodimg_seo_stats['missing_alt'] ) ? intval( $prodimg_seo_stats['missing_alt'] ) : 0;
$prodimg_seo_weak      = isset( $prodimg_seo_stats['weak_alt'] ) ? intval( $prodimg_seo_stats['weak_alt'] ) : 0;
$prodimg_seo_breakdown = isset( $prodimg_seo_stats['breakdown'] ) ? $prodimg_seo_stats['breakdown'] : array(
    'featured'   => 0,
    'gallery'    => 0,
    'variations' => 0,
);

$prodimg_seo_band = isset( $prodimg_seo_stats['by_band'] ) ? $prodimg_seo_stats['by_band'] : array(
    'good' => 0,
    'ok'   => 0,
    'poor' => 0,
);
$prodimg_seo_band_good = isset( $prodimg_seo_band['good'] ) ? intval( $prodimg_seo_band['good'] ) : 0;
$prodimg_seo_band_ok   = isset( $prodimg_seo_band['ok'] ) ? intval( $prodimg_seo_band['ok'] ) : 0;
$prodimg_seo_band_poor = isset( $prodimg_seo_band['poor'] ) ? intval( $prodimg_seo_band['poor'] ) : 0;
$prodimg_seo_band_sum  = $prodimg_seo_band_good + $prodimg_seo_band_ok + $prodimg_seo_band_poor;

$prodimg_seo_pct = function ( $part, $total ) {
    return $total > 0 ? round( ( $part / $total ) * 100, 1 ) : 0;
};

$prodimg_seo_gauge_band = $prodimg_seo_avg_score >= 80 ? 'good' : ( $prodimg_seo_avg_score >= 50 ? 'ok' : 'poor' );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page slug for active nav state.
$prodimg_seo_current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'prodimg-seo-report';
?>
<div class="wrap prodimg-app">

    <header class="prodimg-page-header">
        <div class="prodimg-page-header__inner">
            <div class="prodimg-page-header__titleblock">
                <h1 class="prodimg-page-header__title"><?php esc_html_e( 'SEO Audit Report', 'product-image-seo' ); ?></h1>
            </div>
            <div class="prodimg-page-header__actions">
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=prodimg_seo_1972adm_export_csv' ), 'prodimg_seo_1972adm_admin_nonce', 'nonce' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Download CSV Report', 'product-image-seo' ); ?>
                </a>
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

    <div class="prodimg-grid">

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
        </div>

        <div class="prodimg-card">
            <h2 class="prodimg-card__title"><?php esc_html_e( 'Total products', 'product-image-seo' ); ?></h2>
            <p class="prodimg-card__value"><?php echo esc_html( $prodimg_seo_total ); ?></p>
            <p class="prodimg-card__footnote"><?php esc_html_e( 'Indexed in audit', 'product-image-seo' ); ?></p>
        </div>

        <div class="prodimg-card">
            <h2 class="prodimg-card__title"><?php esc_html_e( 'Missing alt text', 'product-image-seo' ); ?></h2>
            <p class="prodimg-card__value"><?php echo esc_html( $prodimg_seo_missing ); ?></p>
            <p class="prodimg-card__footnote"><?php esc_html_e( 'Products needing review', 'product-image-seo' ); ?></p>
        </div>

        <div class="prodimg-card">
            <h2 class="prodimg-card__title"><?php esc_html_e( 'Weak alt text', 'product-image-seo' ); ?></h2>
            <p class="prodimg-card__value"><?php echo esc_html( $prodimg_seo_weak ); ?></p>
            <p class="prodimg-card__footnote"><?php esc_html_e( 'Could be improved', 'product-image-seo' ); ?></p>
        </div>

    </div>

    <div class="prodimg-card">
        <h2 class="prodimg-card__title"><?php esc_html_e( 'Coverage breakdown', 'product-image-seo' ); ?></h2>
        <ul class="prodimg-coverage-list">
            <li>
                <span><?php esc_html_e( 'Featured images covered', 'product-image-seo' ); ?></span>
                <strong><?php echo esc_html( intval( $prodimg_seo_breakdown['featured'] ?? 0 ) ); ?></strong>
            </li>
            <li>
                <span><?php esc_html_e( 'Gallery images covered', 'product-image-seo' ); ?></span>
                <strong><?php echo esc_html( intval( $prodimg_seo_breakdown['gallery'] ?? 0 ) ); ?></strong>
            </li>
            <li>
                <span><?php esc_html_e( 'Variation images covered', 'product-image-seo' ); ?></span>
                <strong><?php echo esc_html( intval( $prodimg_seo_breakdown['variations'] ?? 0 ) ); ?></strong>
            </li>
        </ul>
    </div>

    <div class="prodimg-card">
        <h2 class="prodimg-card__title"><?php esc_html_e( 'Score distribution', 'product-image-seo' ); ?></h2>
        <div class="prodimg-progress prodimg-progress--stacked" role="img" aria-label="<?php esc_attr_e( 'Score distribution bar', 'product-image-seo' ); ?>">
            <div class="prodimg-progress__segment prodimg-progress__segment--good" style="width: <?php echo esc_attr( $prodimg_seo_pct( $prodimg_seo_band_good, $prodimg_seo_band_sum ) ); ?>%;"></div>
            <div class="prodimg-progress__segment prodimg-progress__segment--ok"   style="width: <?php echo esc_attr( $prodimg_seo_pct( $prodimg_seo_band_ok, $prodimg_seo_band_sum ) ); ?>%;"></div>
            <div class="prodimg-progress__segment prodimg-progress__segment--poor" style="width: <?php echo esc_attr( $prodimg_seo_pct( $prodimg_seo_band_poor, $prodimg_seo_band_sum ) ); ?>%;"></div>
        </div>
        <div class="prodimg-progress__legend">
            <span><span class="prodimg-legend-dot prodimg-legend-dot--good"></span><?php esc_html_e( 'Good', 'product-image-seo' ); ?> (<?php echo esc_html( $prodimg_seo_band_good ); ?>)</span>
            <span><span class="prodimg-legend-dot prodimg-legend-dot--ok"></span><?php esc_html_e( 'OK', 'product-image-seo' ); ?> (<?php echo esc_html( $prodimg_seo_band_ok ); ?>)</span>
            <span><span class="prodimg-legend-dot prodimg-legend-dot--poor"></span><?php esc_html_e( 'Poor', 'product-image-seo' ); ?> (<?php echo esc_html( $prodimg_seo_band_poor ); ?>)</span>
        </div>
    </div>

</div>
