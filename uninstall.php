<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options.
delete_option( 'prodimg_seo_1972adm_api_key' );
delete_option( 'prodimg_seo_1972adm_settings' );
delete_option( 'prodimg_seo_1972adm_version' );

// Delete transients.
delete_transient( 'prodimg_seo_1972adm_scan_cache' );

// Delete postmeta only if user opted in.
$prodimg_seo_delete_data = get_option( 'prodimg_seo_1972adm_delete_data_on_uninstall', false );
if ( $prodimg_seo_delete_data ) {
    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup; cache layer not loaded at uninstall time.
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_prodimg\\_seo\\_1972adm\\_%'"
    );
}

// Clear scheduled events.
wp_clear_scheduled_hook( 'prodimg_seo_1972adm_daily_scan' );
