<?php
/**
 * Audit Report View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$stats = $this->statistics->get_stats();
?>
<div class="wrap">
    <h1><?php esc_html_e( 'SEO Audit Report', 'product-image-seo' ); ?></h1>

    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 600px; margin-top: 20px;">
        <h2><?php esc_html_e( 'Catalog Summary', 'product-image-seo' ); ?></h2>
        <ul>
            <li><strong><?php esc_html_e( 'Total Products:', 'product-image-seo' ); ?></strong> <?php echo esc_html( $stats['total_products'] ); ?></li>
            <li><strong><?php esc_html_e( 'Products Missing Alt Text:', 'product-image-seo' ); ?></strong> <?php echo esc_html( $stats['missing_alt'] ); ?></li>
            <li><strong><?php esc_html_e( 'Average Quality Score:', 'product-image-seo' ); ?></strong> <?php echo esc_html( $stats['avg_score'] ); ?></li>
            <li><strong><?php esc_html_e( 'Products with Weak Alt Text:', 'product-image-seo' ); ?></strong> <?php echo esc_html( $stats['weak_alt'] ); ?></li>
        </ul>

        <h3><?php esc_html_e( 'Coverage Breakdown', 'product-image-seo' ); ?></h3>
        <ul>
            <li><strong><?php esc_html_e( 'Featured Images Covered:', 'product-image-seo' ); ?></strong> <?php echo esc_html( $stats['breakdown']['featured'] ); ?></li>
            <li><strong><?php esc_html_e( 'Gallery Images Covered:', 'product-image-seo' ); ?></strong> <?php echo esc_html( $stats['breakdown']['gallery'] ); ?></li>
            <li><strong><?php esc_html_e( 'Variation Images Covered:', 'product-image-seo' ); ?></strong> <?php echo esc_html( $stats['breakdown']['variations'] ); ?></li>
        </ul>

        <p style="margin-top: 20px;">
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=prodimg_seo_1972adm_export_csv' ), 'prodimg_seo_1972adm_admin_nonce', 'nonce' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Download CSV Report', 'product-image-seo' ); ?>
            </a>
        </p>
    </div>
</div>
