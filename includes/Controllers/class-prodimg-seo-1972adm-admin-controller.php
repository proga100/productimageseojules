<?php
/**
 * Admin Controller.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Admin_Controller {

    private $settings;
    private $api_client;
    private $statistics;
    private $scanner;
    private $calculator;

    public function __construct(
        Prodimg_Seo_1972adm_Settings $settings,
        Prodimg_Seo_1972adm_Api_Client $api_client,
        Prodimg_Seo_1972adm_Statistics $statistics,
        Prodimg_Seo_1972adm_Product_Scanner $scanner,
        Prodimg_Seo_1972adm_Score_Calculator $calculator
    ) {
        $this->settings   = $settings;
        $this->api_client = $api_client;
        $this->statistics = $statistics;
        $this->scanner    = $scanner;
        $this->calculator = $calculator;
    }

    public function init_hooks() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_scan_catalog', array( $this, 'ajax_scan_catalog' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_generate_single', array( $this, 'ajax_generate_single' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_save_single', array( $this, 'ajax_save_single' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_recalc_score', array( $this, 'ajax_recalc_score' ) );
        add_filter( 'admin_body_class', array( $this, 'add_skin_body_class' ) );
    }

    public function add_skin_body_class( $classes ) {
        $screen = get_current_screen();
        if ( $screen && false !== strpos( $screen->id, 'prodimg-seo' ) ) {
            $classes .= ' prodimg-seo-skin';
        }
        return $classes;
    }

    public function ajax_recalc_score() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
        }
        $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
        if ( ! $product_id ) {
            wp_send_json_error( __( 'Invalid product ID.', 'product-image-seo' ) );
        }
        $result = $this->calculator->calculate_for_product( $product_id );
        update_post_meta( $product_id, '_prodimg_seo_1972adm_score_local', $result['score'] );
        update_post_meta( $product_id, '_prodimg_seo_1972adm_score_breakdown', wp_json_encode( $result ) );
        wp_send_json_success( $result );
    }

    public function admin_menu() {
        add_menu_page(
            __( 'Product Image SEO', 'product-image-seo' ),
            __( 'Product Image SEO', 'product-image-seo' ),
            'manage_woocommerce',
            'prodimg-seo-dashboard',
            array( $this, 'render_dashboard_page' ),
            'dashicons-format-image',
            58.5
        );

        add_submenu_page(
            'prodimg-seo-dashboard',
            __( 'Dashboard', 'product-image-seo' ),
            __( 'Dashboard', 'product-image-seo' ),
            'manage_woocommerce',
            'prodimg-seo-dashboard',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'prodimg-seo-dashboard',
            __( 'Catalog Audit', 'product-image-seo' ),
            __( 'Catalog', 'product-image-seo' ),
            'manage_woocommerce',
            'prodimg-seo-catalog',
            array( $this, 'render_catalog_page' )
        );

        add_submenu_page(
            'prodimg-seo-dashboard',
            __( 'Bulk Fix', 'product-image-seo' ),
            __( 'Bulk Fix', 'product-image-seo' ),
            'manage_woocommerce',
            'prodimg-seo-bulk',
            array( $this, 'render_bulk_page' )
        );

        add_submenu_page(
            'prodimg-seo-dashboard',
            __( 'SEO Audit Report', 'product-image-seo' ),
            __( 'Audit Report', 'product-image-seo' ),
            'manage_woocommerce',
            'prodimg-seo-report',
            array( $this, 'render_report_page' )
        );

        add_submenu_page(
            'prodimg-seo-dashboard',
            __( 'Product Image SEO Settings', 'product-image-seo' ),
            __( 'Settings', 'product-image-seo' ),
            'manage_woocommerce',
            'prodimg-seo-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'prodimg-seo' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'prodimg-seo-1972adm-admin-css',
            PRODIMG_SEO_1972ADM_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            PRODIMG_SEO_1972ADM_VERSION
        );

        wp_enqueue_script(
            'prodimg-seo-1972adm-admin-js',
            PRODIMG_SEO_1972ADM_PLUGIN_URL . 'admin/js/admin.js',
            array( 'jquery' ),
            PRODIMG_SEO_1972ADM_VERSION,
            true
        );

        wp_localize_script(
            'prodimg-seo-1972adm-admin-js',
            'prodimg_seo_1972adm_admin',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'prodimg_seo_1972adm_admin_nonce' ),
            )
        );
    }

    public function render_dashboard_page() {
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Views/Admin/dashboard.php';
    }

    public function render_catalog_page() {
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Views/Admin/catalog.php';
    }

    public function render_bulk_page() {
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Views/Admin/bulk-fix.php';
    }

    public function render_report_page() {
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Views/Admin/audit-report.php';
    }

    public function render_settings_page() {
        if ( isset( $_POST['prodimg_seo_1972adm_save_settings'] ) ) {
            if ( ! isset( $_POST['prodimg_seo_1972adm_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['prodimg_seo_1972adm_nonce'] ), 'prodimg_seo_1972adm_save_settings' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'product-image-seo' ) );
            }
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_die( esc_html__( 'Permission denied.', 'product-image-seo' ) );
            }

            $new_settings = array(
                'api_key' => sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) ),
                'auto_generate' => sanitize_text_field( wp_unslash( $_POST['auto_generate'] ?? 'no' ) ),
                'skip_existing' => sanitize_text_field( wp_unslash( $_POST['skip_existing'] ?? 'yes' ) ),
                'alt_style' => sanitize_text_field( wp_unslash( $_POST['alt_style'] ?? 'seo_balanced' ) ),
                'include_name' => sanitize_text_field( wp_unslash( $_POST['include_name'] ?? 'no' ) ),
                'include_category' => sanitize_text_field( wp_unslash( $_POST['include_category'] ?? 'no' ) ),
                'include_sku' => sanitize_text_field( wp_unslash( $_POST['include_sku'] ?? 'no' ) ),
                'include_price' => sanitize_text_field( wp_unslash( $_POST['include_price'] ?? 'no' ) ),
                'max_length' => absint( $_POST['max_length'] ?? 125 ),
            );
            $this->settings->update_all( $new_settings );

            $delete_data = isset( $_POST['delete_data_on_uninstall'] ) ? true : false;
            update_option( 'prodimg_seo_1972adm_delete_data_on_uninstall', $delete_data );

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'product-image-seo' ) . '</p></div>';
        }

        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Views/Admin/settings.php';
    }

    public function ajax_test_connection() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
        }

        $result = $this->api_client->test_connection();
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        wp_send_json_success( __( 'Connection successful.', 'product-image-seo' ) );
    }

    public function ajax_scan_catalog() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
        }

        $page = isset( $_POST['scan_page'] ) ? absint( $_POST['scan_page'] ) : 1;
        $result = $this->scanner->scan_all( $page );

        wp_send_json_success( $result );
    }

    public function ajax_generate_single() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
        }

        $product_id = absint( $_POST['product_id'] ?? 0 );

        if ( ! $product_id ) {
            wp_send_json_error( __( 'Invalid parameters.', 'product-image-seo' ) );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( __( 'Product not found.', 'product-image-seo' ) );
        }

        $suggestions = array();

        // Featured image
        $featured_id = $product->get_image_id();
        if ( $featured_id ) {
            $result = $this->api_client->generate_for_product( $product_id, $featured_id, 'featured' );
            if ( ! is_wp_error( $result ) ) {
                $suggestions[] = array(
                    'image_id' => $featured_id,
                    'role'     => 'featured',
                    'url'      => wp_get_attachment_url( $featured_id ),
                    'alt_text' => $result['alt_text'] ?? '',
                    'score'    => $result['quality_score'] ?? 0,
                );
            }
        }

        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ( $gallery_ids as $gid ) {
            $result = $this->api_client->generate_for_product( $product_id, $gid, 'gallery' );
            if ( ! is_wp_error( $result ) ) {
                $suggestions[] = array(
                    'image_id' => $gid,
                    'role'     => 'gallery',
                    'url'      => wp_get_attachment_url( $gid ),
                    'alt_text' => $result['alt_text'] ?? '',
                    'score'    => $result['quality_score'] ?? 0,
                );
            }
        }

        // Variations
        if ( $product->is_type( 'variable' ) ) {
            foreach ( $product->get_children() as $child_id ) {
                $child = wc_get_product( $child_id );
                if ( $child ) {
                    $vid = $child->get_image_id();
                    if ( $vid ) {
                        $result = $this->api_client->generate_for_product( $child_id, $vid, 'variation' );
                        if ( ! is_wp_error( $result ) ) {
                            $suggestions[] = array(
                                'image_id' => $vid,
                                'role'     => 'variation',
                                'url'      => wp_get_attachment_url( $vid ),
                                'alt_text' => $result['alt_text'] ?? '',
                                'score'    => $result['quality_score'] ?? 0,
                            );
                        }
                    }
                }
            }
        }

        if ( empty( $suggestions ) ) {
             wp_send_json_error( __( 'No images found or API failed.', 'product-image-seo' ) );
        }

        wp_send_json_success( array( 'suggestions' => $suggestions ) );
    }

    public function ajax_save_single() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
        }

        $product_id = absint( $_POST['product_id'] ?? 0 );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each element is sanitized via sanitize_text_field in the loop below.
        $alt_texts_raw = isset( $_POST['alt_texts'] ) ? (array) wp_unslash( $_POST['alt_texts'] ) : array();
        $alt_texts     = array();
        foreach ( $alt_texts_raw as $alt_image_id => $alt_value ) {
            $alt_texts[ absint( $alt_image_id ) ] = sanitize_text_field( $alt_value );
        }

        if ( ! $product_id || empty( $alt_texts ) ) {
            wp_send_json_error( __( 'Invalid parameters.', 'product-image-seo' ) );
        }

        foreach ( $alt_texts as $image_id => $alt ) {
            if ( $image_id ) {
                update_post_meta( $image_id, '_wp_attachment_image_alt', $alt );
            }
        }

        update_post_meta( $product_id, '_prodimg_seo_1972adm_processed_at', time() );

        // Calculate coverage again to update status
        $this->scanner->calculate( wc_get_product( $product_id ) );

        wp_send_json_success( __( 'Saved successfully.', 'product-image-seo' ) );
    }
}
