<?php
/**
 * Settings View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$api_key = $this->settings->get( 'api_key', '' );
$auto_generate = $this->settings->get( 'auto_generate', 'no' );
$skip_existing = $this->settings->get( 'skip_existing', 'yes' );
$alt_style = $this->settings->get( 'alt_style', 'seo_balanced' );
$include_name = $this->settings->get( 'include_name', 'yes' );
$include_category = $this->settings->get( 'include_category', 'yes' );
$include_sku = $this->settings->get( 'include_sku', 'yes' );
$include_price = $this->settings->get( 'include_price', 'no' );
$max_length = $this->settings->get( 'max_length', 125 );
$delete_data = get_option( 'prodimg_seo_1972adm_delete_data_on_uninstall', false );
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Product Image SEO Settings', 'product-image-seo' ); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field( 'prodimg_seo_1972adm_save_settings', 'prodimg_seo_1972adm_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="api_key"><?php esc_html_e( 'API Key', 'product-image-seo' ); ?></label></th>
                <td>
                    <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" />
                    <button type="button" class="button" id="prodimg-seo-test-connection"><?php esc_html_e( 'Test Connection', 'product-image-seo' ); ?></button>
                    <span class="spinner" id="prodimg-seo-test-spinner"></span>
                    <p class="description" id="prodimg-seo-test-result">
                        <?php esc_html_e( 'Get your free API key at altaudit.com.', 'product-image-seo' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Auto-generate on save', 'product-image-seo' ); ?></th>
                <td>
                    <label>
                        <input type="radio" name="auto_generate" value="yes" <?php checked( $auto_generate, 'yes' ); ?> />
                        <?php esc_html_e( 'Yes', 'product-image-seo' ); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="auto_generate" value="no" <?php checked( $auto_generate, 'no' ); ?> />
                        <?php esc_html_e( 'No', 'product-image-seo' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Skip images with existing alt text', 'product-image-seo' ); ?></th>
                <td>
                    <label>
                        <input type="radio" name="skip_existing" value="yes" <?php checked( $skip_existing, 'yes' ); ?> />
                        <?php esc_html_e( 'Yes', 'product-image-seo' ); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="skip_existing" value="no" <?php checked( $skip_existing, 'no' ); ?> />
                        <?php esc_html_e( 'No', 'product-image-seo' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="alt_style"><?php esc_html_e( 'Alt Text Style', 'product-image-seo' ); ?></label></th>
                <td>
                    <select name="alt_style" id="alt_style">
                        <option value="seo_focused" <?php selected( $alt_style, 'seo_focused' ); ?>><?php esc_html_e( 'SEO Focused', 'product-image-seo' ); ?></option>
                        <option value="accessibility_focused" <?php selected( $alt_style, 'accessibility_focused' ); ?>><?php esc_html_e( 'Accessibility Focused', 'product-image-seo' ); ?></option>
                        <option value="seo_balanced" <?php selected( $alt_style, 'seo_balanced' ); ?>><?php esc_html_e( 'Balanced', 'product-image-seo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Include in Alt Text Context', 'product-image-seo' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="include_name" value="yes" <?php checked( $include_name, 'yes' ); ?> />
                        <?php esc_html_e( 'Product Name', 'product-image-seo' ); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="include_category" value="yes" <?php checked( $include_category, 'yes' ); ?> />
                        <?php esc_html_e( 'Category', 'product-image-seo' ); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="include_sku" value="yes" <?php checked( $include_sku, 'yes' ); ?> />
                        <?php esc_html_e( 'SKU', 'product-image-seo' ); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="include_price" value="yes" <?php checked( $include_price, 'yes' ); ?> />
                        <?php esc_html_e( 'Price', 'product-image-seo' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="max_length"><?php esc_html_e( 'Max Length', 'product-image-seo' ); ?></label></th>
                <td>
                    <input type="number" id="max_length" name="max_length" value="<?php echo esc_attr( $max_length ); ?>" class="small-text" min="50" max="255" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Delete data on uninstall', 'product-image-seo' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( $delete_data, true ); ?> />
                        <?php esc_html_e( 'Yes', 'product-image-seo' ); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php submit_button( __( 'Save Settings', 'product-image-seo' ), 'primary', 'prodimg_seo_1972adm_save_settings' ); ?>
    </form>
</div>
