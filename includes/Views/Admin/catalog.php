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

$prodimg_seo_chips = array(
    ''             => __( 'All', 'product-image-seo' ),
    'needs_review' => __( 'Missing alt', 'product-image-seo' ),
    'partial'      => __( 'Weak', 'product-image-seo' ),
    'optimized'    => __( 'Good', 'product-image-seo' ),
);
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Catalog Audit', 'product-image-seo' ); ?></h1>

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
<div id="prodimg-seo-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div id="prodimg-seo-modal" style="background:#fff; width:800px; max-width:90%; margin:50px auto; padding:20px; border-radius:4px; max-height:80vh; overflow-y:auto; position:relative;">
        <button id="prodimg-seo-modal-close" style="position:absolute; top:10px; right:10px; background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        <h2><?php esc_html_e( 'Review Alt Text Suggestions', 'product-image-seo' ); ?></h2>
        <div id="prodimg-seo-modal-content">
            <p><?php esc_html_e( 'Loading...', 'product-image-seo' ); ?></p>
        </div>
        <div style="margin-top:20px; text-align:right;">
            <button class="button button-primary" id="prodimg-seo-modal-save" style="display:none;"><?php esc_html_e( 'Save Approved Alt Text', 'product-image-seo' ); ?></button>
        </div>
    </div>
</div>
