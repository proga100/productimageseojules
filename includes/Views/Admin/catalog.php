<?php
/**
 * Catalog View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$prodimg_seo_list_table = new Prodimg_Seo_1972adm_Catalog_List_Table();
$prodimg_seo_list_table->prepare_items();
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filter inputs; sanitized.
$prodimg_seo_page_slug    = isset( $_REQUEST['page'] ) ? sanitize_key( wp_unslash( $_REQUEST['page'] ) ) : '';
$prodimg_seo_active_chip  = isset( $_REQUEST['prodimg_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['prodimg_status'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page slug for active nav state.
$prodimg_seo_current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'prodimg-seo-catalog';

$prodimg_seo_chips = array(
    ''          => __( 'All', 'product-image-seo' ),
    'missing'   => __( 'Missing alt', 'product-image-seo' ),
    'weak'      => __( 'Weak', 'product-image-seo' ),
    'good'      => __( 'Good', 'product-image-seo' ),
    'excellent' => __( 'Excellent', 'product-image-seo' ),
);
?>
<div class="wrap prodimg-app">

    <header class="prodimg-page-header">
        <div class="prodimg-page-header__inner">
            <div class="prodimg-page-header__titleblock">
                <h1 class="prodimg-page-header__title"><?php esc_html_e( 'Product Image Audit', 'product-image-seo' ); ?></h1>
            </div>
            <div class="prodimg-page-header__actions">
                <button type="button" class="button button-primary" id="prodimg-seo-scan-images">
                    <?php esc_html_e( 'Scan Images', 'product-image-seo' ); ?>
                </button>
                <span class="spinner" id="prodimg-scan-images-spinner"></span>
                <span id="prodimg-scan-images-progress"></span>
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

    <nav class="prodimg-filter-chips" aria-label="<?php esc_attr_e( 'Filter products by status', 'product-image-seo' ); ?>">
        <?php foreach ( $prodimg_seo_chips as $prodimg_seo_chip_slug => $prodimg_seo_chip_label ) :
            $prodimg_seo_chip_url = '' === $prodimg_seo_chip_slug
                ? admin_url( 'admin.php?page=prodimg-seo-catalog' )
                : add_query_arg(
                    array( 'page' => 'prodimg-seo-catalog', 'prodimg_status' => $prodimg_seo_chip_slug ),
                    admin_url( 'admin.php' )
                );
            $prodimg_seo_chip_active = $prodimg_seo_active_chip === $prodimg_seo_chip_slug ? ' is-active' : '';
            ?>
            <a class="prodimg-filter-chip<?php echo esc_attr( $prodimg_seo_chip_active ); ?>" href="<?php echo esc_url( $prodimg_seo_chip_url ); ?>">
                <?php echo esc_html( $prodimg_seo_chip_label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr( $prodimg_seo_page_slug ); ?>" />
        <?php $prodimg_seo_list_table->display(); ?>
    </form>
</div>

<!-- Modal for Single Product Generation -->
<div id="prodimg-seo-modal-overlay" class="prodimg-modal-overlay prodimg-app">
    <div id="prodimg-seo-modal" class="prodimg-modal" role="dialog" aria-modal="true" aria-labelledby="prodimg-seo-modal-title">
        <button id="prodimg-seo-modal-close" class="prodimg-modal__close" aria-label="<?php esc_attr_e( 'Close', 'product-image-seo' ); ?>">&times;</button>
        <h2 id="prodimg-seo-modal-title"><?php esc_html_e( 'Review Alt Text Suggestions', 'product-image-seo' ); ?></h2>
        <div id="prodimg-seo-modal-content">
            <p><?php esc_html_e( 'Loading...', 'product-image-seo' ); ?></p>
        </div>
        <div class="prodimg-modal__footer">
            <button class="button" id="prodimg-seo-modal-regenerate" style="display:none;"><?php esc_html_e( 'Regenerate', 'product-image-seo' ); ?></button>
            <button class="button button-primary" id="prodimg-seo-modal-save" style="display:none;"><?php esc_html_e( 'Save Approved Alt Text', 'product-image-seo' ); ?></button>
        </div>
    </div>
</div>
