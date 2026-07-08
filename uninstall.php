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

    // Delete the status taxonomy terms. The plugin (and its taxonomy) is not
    // loaded during uninstall, so register it minimally first; otherwise
    // get_terms() on an unregistered taxonomy returns WP_Error in modern WP.
    $prodimg_seo_taxonomy = 'prodimg_seo_1972adm_status';
    if ( ! taxonomy_exists( $prodimg_seo_taxonomy ) ) {
        register_taxonomy( $prodimg_seo_taxonomy, array( 'product', 'attachment' ), array( 'public' => false ) );
    }

    $prodimg_seo_term_ids = get_terms( array(
        'taxonomy'   => $prodimg_seo_taxonomy,
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( ! is_wp_error( $prodimg_seo_term_ids ) && ! empty( $prodimg_seo_term_ids ) ) {
        foreach ( $prodimg_seo_term_ids as $prodimg_seo_term_id ) {
            wp_delete_term( (int) $prodimg_seo_term_id, $prodimg_seo_taxonomy );
        }
    }
}

// Clear scheduled events.
wp_clear_scheduled_hook( 'prodimg_seo_1972adm_daily_scan' );
