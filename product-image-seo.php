<?php
/**
 * Plugin Name:       Product Image SEO — AI Alt Text & Image SEO Audit
 * Plugin URI:        https://altaudit.com/product-image-seo
 * Description:       AI alt text and catalog-wide image SEO audit for product catalogs. Per-product dashboard, bulk fix by category, Google Image Search readiness scoring, CSV audit reports. Requires an Alt Audit account (free tier available).
 * Version:           1.0.1
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Flance
 * Author URI:        https://altaudit.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       product-image-seo
 * Domain Path:       /languages
 *
 * @package ProductImageSeo
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'PRODIMG_SEO_1972ADM_VERSION', '1.0.1' );
define( 'PRODIMG_SEO_1972ADM_PLUGIN_FILE', __FILE__ );
define( 'PRODIMG_SEO_1972ADM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRODIMG_SEO_1972ADM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PRODIMG_SEO_1972ADM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'PRODIMG_SEO_1972ADM_TEXT_DOMAIN', 'product-image-seo' );
if ( ! defined( 'PRODIMG_SEO_1972ADM_API_BASE_URL' ) ) {
    define( 'PRODIMG_SEO_1972ADM_API_BASE_URL', 'https://altaudit.com/api/v1' );
}
if ( ! defined( 'PRODIMG_SEO_1972ADM_API_TIMEOUT' ) ) {
    define( 'PRODIMG_SEO_1972ADM_API_TIMEOUT', 40 );
}
define( 'PRODIMG_SEO_1972ADM_INCLUDES_DIR', PRODIMG_SEO_1972ADM_PLUGIN_DIR . 'includes/' );
define( 'PRODIMG_SEO_1972ADM_ASSETS_URL', PRODIMG_SEO_1972ADM_PLUGIN_URL . 'assets/' );

// Bootstrap.
require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'class-prodimg-seo-1972adm-plugin.php';

add_action( 'plugins_loaded', 'prodimg_seo_1972adm_bootstrap' );

// Declare WooCommerce HPOS (Custom Order Tables) compatibility.
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PRODIMG_SEO_1972ADM_PLUGIN_FILE, true );
    }
} );

function prodimg_seo_1972adm_bootstrap() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'prodimg_seo_1972adm_woocommerce_required_notice' );
        return;
    }
    Prodimg_Seo_1972adm_Plugin::instance();
}

function prodimg_seo_1972adm_woocommerce_required_notice() {
    echo '<div class="notice notice-error"><p>';
    esc_html_e(
        'Product Image SEO requires WooCommerce to be installed and active.',
        'product-image-seo'
    );
    echo '</p></div>';
}
