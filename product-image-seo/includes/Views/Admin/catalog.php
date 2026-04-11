<?php
/**
 * Catalog View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$list_table = new Prodimg_Seo_1972adm_Catalog_List_Table();
$list_table->prepare_items();
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Catalog Audit', 'product-image-seo' ); ?></h1>
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
        <?php $list_table->display(); ?>
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
