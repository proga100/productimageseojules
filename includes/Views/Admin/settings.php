<?php
/**
 * Settings View.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$prodimg_seo_api_key          = $this->settings->get( 'api_key', '' );
$prodimg_seo_auto_generate    = $this->settings->get( 'auto_generate', 'no' );
$prodimg_seo_skip_existing    = $this->settings->get( 'skip_existing', 'yes' );
$prodimg_seo_alt_style        = $this->settings->get( 'alt_style', 'seo_balanced' );
$prodimg_seo_include_name     = $this->settings->get( 'include_name', 'yes' );
$prodimg_seo_include_category = $this->settings->get( 'include_category', 'yes' );
$prodimg_seo_include_sku      = $this->settings->get( 'include_sku', 'yes' );
$prodimg_seo_include_price    = $this->settings->get( 'include_price', 'no' );
$prodimg_seo_max_length       = $this->settings->get( 'max_length', 125 );
$prodimg_seo_theme            = $this->settings->get( 'theme', 'auto' );
$prodimg_seo_delete_data      = get_option( 'prodimg_seo_1972adm_delete_data_on_uninstall', false );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page slug for active nav state.
$prodimg_seo_current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'prodimg-seo-settings';
?>
<div class="wrap prodimg-app">

    <header class="prodimg-page-header">
        <div class="prodimg-page-header__inner">
            <div class="prodimg-page-header__titleblock">
                <h1 class="prodimg-page-header__title"><?php esc_html_e( 'Settings', 'product-image-seo' ); ?></h1>
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

    <nav class="prodimg-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'product-image-seo' ); ?>">
        <button type="button" role="tab" id="tab-api" aria-controls="panel-api" aria-selected="true"><?php esc_html_e( 'API', 'product-image-seo' ); ?></button>
        <button type="button" role="tab" id="tab-generation" aria-controls="panel-generation" aria-selected="false"><?php esc_html_e( 'Generation', 'product-image-seo' ); ?></button>
        <button type="button" role="tab" id="tab-autofix" aria-controls="panel-autofix" aria-selected="false"><?php esc_html_e( 'Auto-fix', 'product-image-seo' ); ?></button>
        <button type="button" role="tab" id="tab-advanced" aria-controls="panel-advanced" aria-selected="false"><?php esc_html_e( 'Advanced', 'product-image-seo' ); ?></button>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field( 'prodimg_seo_1972adm_save_settings', 'prodimg_seo_1972adm_nonce' ); ?>

        <section role="tabpanel" id="panel-api" aria-labelledby="tab-api" class="prodimg-card">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="api_key"><?php esc_html_e( 'API Key', 'product-image-seo' ); ?></label></th>
                    <td>
                        <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr( $prodimg_seo_api_key ); ?>" class="regular-text" />
                        <button type="button" class="button" id="prodimg-seo-test-connection"><?php esc_html_e( 'Test Connection', 'product-image-seo' ); ?></button>
                        <span class="spinner" id="prodimg-seo-test-spinner"></span>
                        <p class="description" id="prodimg-seo-test-result">
                            <?php esc_html_e( 'Get your free API key at altaudit.com.', 'product-image-seo' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </section>

        <section role="tabpanel" id="panel-generation" aria-labelledby="tab-generation" class="prodimg-card" hidden>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="alt_style"><?php esc_html_e( 'Alt Text Style', 'product-image-seo' ); ?></label></th>
                    <td>
                        <select name="alt_style" id="alt_style">
                            <option value="seo_focused" <?php selected( $prodimg_seo_alt_style, 'seo_focused' ); ?>><?php esc_html_e( 'SEO Focused', 'product-image-seo' ); ?></option>
                            <option value="accessibility_focused" <?php selected( $prodimg_seo_alt_style, 'accessibility_focused' ); ?>><?php esc_html_e( 'Accessibility Focused', 'product-image-seo' ); ?></option>
                            <option value="seo_balanced" <?php selected( $prodimg_seo_alt_style, 'seo_balanced' ); ?>><?php esc_html_e( 'Balanced', 'product-image-seo' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Include in Alt Text Context', 'product-image-seo' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="include_name" value="yes" <?php checked( $prodimg_seo_include_name, 'yes' ); ?> />
                            <?php esc_html_e( 'Product Name', 'product-image-seo' ); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="include_category" value="yes" <?php checked( $prodimg_seo_include_category, 'yes' ); ?> />
                            <?php esc_html_e( 'Category', 'product-image-seo' ); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="include_sku" value="yes" <?php checked( $prodimg_seo_include_sku, 'yes' ); ?> />
                            <?php esc_html_e( 'SKU', 'product-image-seo' ); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="include_price" value="yes" <?php checked( $prodimg_seo_include_price, 'yes' ); ?> />
                            <?php esc_html_e( 'Price', 'product-image-seo' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="max_length"><?php esc_html_e( 'Max Length', 'product-image-seo' ); ?></label></th>
                    <td>
                        <input type="number" id="max_length" name="max_length" value="<?php echo esc_attr( $prodimg_seo_max_length ); ?>" class="small-text" min="50" max="255" />
                    </td>
                </tr>
            </table>
        </section>

        <section role="tabpanel" id="panel-autofix" aria-labelledby="tab-autofix" class="prodimg-card" hidden>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Auto-generate on save', 'product-image-seo' ); ?></th>
                    <td>
                        <label class="prodimg-switch" for="auto_generate">
                            <input type="checkbox"
                                   id="auto_generate"
                                   name="auto_generate"
                                   value="yes"
                                   <?php checked( $prodimg_seo_auto_generate, 'yes' ); ?> />
                            <span class="prodimg-switch__track">
                                <span class="prodimg-switch__knob"></span>
                            </span>
                            <span class="prodimg-switch__label"><?php esc_html_e( 'Auto-generate on save', 'product-image-seo' ); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Skip images with existing alt text', 'product-image-seo' ); ?></th>
                    <td>
                        <label class="prodimg-switch" for="skip_existing">
                            <input type="checkbox"
                                   id="skip_existing"
                                   name="skip_existing"
                                   value="yes"
                                   <?php checked( $prodimg_seo_skip_existing, 'yes' ); ?> />
                            <span class="prodimg-switch__track">
                                <span class="prodimg-switch__knob"></span>
                            </span>
                            <span class="prodimg-switch__label"><?php esc_html_e( 'Skip images with existing alt text', 'product-image-seo' ); ?></span>
                        </label>
                    </td>
                </tr>
            </table>
        </section>

        <section role="tabpanel" id="panel-advanced" aria-labelledby="tab-advanced" class="prodimg-card" hidden>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="prodimg-theme"><?php esc_html_e( 'Admin theme', 'product-image-seo' ); ?></label></th>
                    <td>
                        <select name="theme" id="prodimg-theme">
                            <option value="auto" <?php selected( $prodimg_seo_theme, 'auto' ); ?>><?php esc_html_e( 'Auto (follow system)', 'product-image-seo' ); ?></option>
                            <option value="light" <?php selected( $prodimg_seo_theme, 'light' ); ?>><?php esc_html_e( 'Light', 'product-image-seo' ); ?></option>
                            <option value="dark" <?php selected( $prodimg_seo_theme, 'dark' ); ?>><?php esc_html_e( 'Dark', 'product-image-seo' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Color scheme for the plugin pages. Auto follows your operating system preference.', 'product-image-seo' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Delete data on uninstall', 'product-image-seo' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( $prodimg_seo_delete_data, true ); ?> />
                            <?php esc_html_e( 'Yes', 'product-image-seo' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Permanently remove all plugin postmeta and options when the plugin is deleted.', 'product-image-seo' ); ?></p>
                    </td>
                </tr>
            </table>
        </section>

        <?php submit_button( __( 'Save Settings', 'product-image-seo' ), 'primary', 'prodimg_seo_1972adm_save_settings' ); ?>
    </form>
</div>
