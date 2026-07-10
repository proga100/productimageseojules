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
    private $coverage_calculator;

    public function __construct(
        Prodimg_Seo_1972adm_Settings $settings,
        Prodimg_Seo_1972adm_Api_Client $api_client,
        Prodimg_Seo_1972adm_Statistics $statistics,
        Prodimg_Seo_1972adm_Product_Scanner $scanner,
        Prodimg_Seo_1972adm_Score_Calculator $calculator,
        Prodimg_Seo_1972adm_Coverage_Calculator $coverage_calculator
    ) {
        $this->settings            = $settings;
        $this->api_client          = $api_client;
        $this->statistics          = $statistics;
        $this->scanner             = $scanner;
        $this->calculator          = $calculator;
        $this->coverage_calculator = $coverage_calculator;
    }

    public function init_hooks() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_test_connection', array( $this, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_scan_catalog', array( $this, 'ajax_scan_catalog' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_generate_single', array( $this, 'ajax_generate_single' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_save_single', array( $this, 'ajax_save_single' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_recalc_score', array( $this, 'ajax_recalc_score' ) );
        add_action( 'wp_ajax_prodimg_seo_1972adm_scan_all_images', array( $this, 'ajax_scan_all_images' ) );
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
            return;
        }

        // Per-image recalc (catalog list-table row/actions button).
        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;
        if ( $attachment_id ) {
            $result = $this->calculator->calculate_for_attachment( $attachment_id );
            update_post_meta( $attachment_id, '_prodimg_seo_1972adm_quality_score', $result['score'] );
            update_post_meta( $attachment_id, '_prodimg_seo_1972adm_score_breakdown', wp_json_encode( $result ) );
            Prodimg_Seo_1972adm_Status_Taxonomy::set_status_for_attachment( $attachment_id, $result['band'] );
            Prodimg_Seo_1972adm_Statistics::flush_cache();
            wp_send_json_success( $result );
            return;
        }

        // Product-level recalc (worst-image rollup).
        $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
        if ( ! $product_id ) {
            wp_send_json_error( __( 'Invalid product ID.', 'product-image-seo' ) );
            return;
        }
        $result = $this->calculator->calculate_for_product( $product_id );
        update_post_meta( $product_id, '_prodimg_seo_1972adm_score_local', $result['score'] );
        update_post_meta( $product_id, '_prodimg_seo_1972adm_score_breakdown', wp_json_encode( $result ) );
        Prodimg_Seo_1972adm_Statistics::flush_cache();
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
            __( 'Product Image Audit', 'product-image-seo' ),
            __( 'Product Images', 'product-image-seo' ),
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
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'prodimg_seo_1972adm_admin_nonce' ),
                'max_length' => absint( $this->settings->get( 'max_length', 125 ) ),
                'i18n'     => array(
                    'generate'      => __( 'Generate', 'product-image-seo' ),
                    'generating'    => __( 'Generating…', 'product-image-seo' ),
                    'recalc'        => __( 'Recalc Score', 'product-image-seo' ),
                    'recalculating' => __( 'Recalculating…', 'product-image-seo' ),
                    'loading'       => __( 'Loading suggestions…', 'product-image-seo' ),
                    'saving'        => __( 'Saving…', 'product-image-seo' ),
                    'save'          => __( 'Save Approved Alt Text', 'product-image-seo' ),
                    'saved'         => __( 'Saved successfully.', 'product-image-seo' ),
                    'saveError'     => __( 'Error saving:', 'product-image-seo' ),
                    'error'         => __( 'Error:', 'product-image-seo' ),
                    'scoreUpdated'  => __( 'Score updated.', 'product-image-seo' ),
                    'noAltText'     => __( '(no alt text)', 'product-image-seo' ),
                    'image'         => __( 'Image', 'product-image-seo' ),
                    'roleScore'     => __( 'Role / Score', 'product-image-seo' ),
                    'scoreLabel'    => __( 'Score', 'product-image-seo' ),
                    'suggestedAlt'  => __( 'Suggested alt text', 'product-image-seo' ),
                    'currentAlt'    => __( 'Current alt text', 'product-image-seo' ),
                    'noneLabel'     => __( '(none)', 'product-image-seo' ),
                    'aiScore'       => __( 'AI score', 'product-image-seo' ),
                    'genStatus'     => __( 'Generating alt text with AI…', 'product-image-seo' ),
                    'genHint'       => __( 'This usually takes a few seconds.', 'product-image-seo' ),
                    'genFailed'     => __( 'Generation failed.', 'product-image-seo' ),
                    'retry'         => __( 'Try Again', 'product-image-seo' ),
                    'regenerate'    => __( 'Regenerate', 'product-image-seo' ),
                    'regenerating'  => __( 'Regenerating…', 'product-image-seo' ),
                    /* translators: 1: current item number, 2: total items */
                    'bulkProgress'     => __( 'Processing %1$s of %2$s…', 'product-image-seo' ),
                    /* translators: 1: saved count, 2: failed count */
                    'bulkGenerateDone' => __( 'Bulk generate finished: %1$s saved, %2$s failed.', 'product-image-seo' ),
                    /* translators: 1: recalculated count, 2: failed count */
                    'bulkRecalcDone'   => __( 'Recalculated %1$s scores, %2$s failed.', 'product-image-seo' ),
                    'bulkNoSelection'  => __( 'Select at least one image first.', 'product-image-seo' ),
                    'bulkGenTitle'     => __( 'Generating alt text with AI…', 'product-image-seo' ),
                    'bulkRecalcTitle'  => __( 'Recalculating scores…', 'product-image-seo' ),
                    /* translators: 1: processed count, 2: total, 3: succeeded, 4: failed */
                    'bulkMeta'         => __( '%1$s of %2$s processed · %3$s ok · %4$s failed', 'product-image-seo' ),
                    'cancel'           => __( 'Cancel', 'product-image-seo' ),
                    'close'            => __( 'Close', 'product-image-seo' ),
                    'bulkCancelled'    => __( 'Bulk run cancelled.', 'product-image-seo' ),
                    'bulkFixDone'      => __( 'Bulk fix finished.', 'product-image-seo' ),
                    /* translators: %s number of alt texts generated */
                    'bulkFixGenerated' => __( 'Generated alt text for %s images.', 'product-image-seo' ),
                    /* translators: %s number of images skipped */
                    'bulkFixSkipped'   => __( 'Skipped %s images that already had alt text.', 'product-image-seo' ),
                    /* translators: %s number of images that failed */
                    'bulkFixFailed'    => __( '%s images could not be generated.', 'product-image-seo' ),
                    'bulkFixNothing'   => __( 'No images needed alt text.', 'product-image-seo' ),
                    'bands'         => array(
                        'missing'    => __( 'Missing', 'product-image-seo' ),
                        'weak'       => __( 'Weak', 'product-image-seo' ),
                        'good'       => __( 'Good', 'product-image-seo' ),
                        'excellent'  => __( 'Excellent', 'product-image-seo' ),
                        'decorative' => __( 'Decorative', 'product-image-seo' ),
                    ),
                    'roles'         => array(
                        'featured'  => __( 'Featured', 'product-image-seo' ),
                        'gallery'   => __( 'Gallery', 'product-image-seo' ),
                        'variation' => __( 'Variation', 'product-image-seo' ),
                    ),
                ),
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
                'max_length' => absint( wp_unslash( $_POST['max_length'] ?? 125 ) ),
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
            return;
        }

        $result = $this->api_client->test_connection();
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
            return;
        }
        wp_send_json_success( __( 'Connection successful.', 'product-image-seo' ) );
    }

    public function ajax_scan_catalog() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
            return;
        }

        $page = isset( $_POST['scan_page'] ) ? absint( wp_unslash( $_POST['scan_page'] ) ) : 1;
        $result = $this->scanner->scan_all( $page );

        wp_send_json_success( $result );
    }

    public function ajax_generate_single() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
            return;
        }

        // Per-image generate (catalog list-table row/actions button): generate for
        // one attachment, using its parent product as context.
        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;
        if ( $attachment_id ) {
            $context            = $this->resolve_attachment_context( $attachment_id );
            $context_product_id = $context['product_id'];
            $role               = $context['role'];

            $result = $this->api_client->generate_for_product( $context_product_id, $attachment_id, $role );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
                return;
            }

            $suggestion = array(
                'image_id'    => $attachment_id,
                'role'        => $role,
                'url'         => wp_get_attachment_url( $attachment_id ),
                'alt_text'    => $result['alt_text'] ?? '',
                'current_alt' => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
                'score'       => $result['quality_score'] ?? 0,
            );

            wp_send_json_success( array(
                'suggestions' => array( $suggestion ),
                'product_id'  => $context_product_id,
            ) );
            return;
        }

        $product_id = absint( wp_unslash( $_POST['product_id'] ?? 0 ) );

        if ( ! $product_id ) {
            wp_send_json_error( __( 'Invalid parameters.', 'product-image-seo' ) );
            return;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( __( 'Product not found.', 'product-image-seo' ) );
            return;
        }

        $suggestions = array();

        // Featured image
        $featured_id = $product->get_image_id();
        if ( $featured_id ) {
            $result = $this->api_client->generate_for_product( $product_id, $featured_id, 'featured' );
            if ( ! is_wp_error( $result ) ) {
                $suggestions[] = array(
                    'image_id'    => $featured_id,
                    'role'        => 'featured',
                    'url'         => wp_get_attachment_url( $featured_id ),
                    'alt_text'    => $result['alt_text'] ?? '',
                    'current_alt' => (string) get_post_meta( $featured_id, '_wp_attachment_image_alt', true ),
                    'score'       => $result['quality_score'] ?? 0,
                );
            }
        }

        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ( $gallery_ids as $gid ) {
            $result = $this->api_client->generate_for_product( $product_id, $gid, 'gallery' );
            if ( ! is_wp_error( $result ) ) {
                $suggestions[] = array(
                    'image_id'    => $gid,
                    'role'        => 'gallery',
                    'url'         => wp_get_attachment_url( $gid ),
                    'alt_text'    => $result['alt_text'] ?? '',
                    'current_alt' => (string) get_post_meta( $gid, '_wp_attachment_image_alt', true ),
                    'score'       => $result['quality_score'] ?? 0,
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
                                'image_id'    => $vid,
                                'role'        => 'variation',
                                'url'         => wp_get_attachment_url( $vid ),
                                'alt_text'    => $result['alt_text'] ?? '',
                                'current_alt' => (string) get_post_meta( $vid, '_wp_attachment_image_alt', true ),
                                'score'       => $result['quality_score'] ?? 0,
                            );
                        }
                    }
                }
            }
        }

        if ( empty( $suggestions ) ) {
             wp_send_json_error( __( 'No images found or API failed.', 'product-image-seo' ) );
             return;
        }

        wp_send_json_success( array( 'suggestions' => $suggestions ) );
    }

    public function ajax_save_single() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
            return;
        }

        $product_id = absint( wp_unslash( $_POST['product_id'] ?? 0 ) );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each element is sanitized via sanitize_text_field in the loop below.
        $alt_texts_raw = isset( $_POST['alt_texts'] ) ? (array) wp_unslash( $_POST['alt_texts'] ) : array();
        $alt_texts     = array();
        foreach ( $alt_texts_raw as $alt_image_id => $alt_value ) {
            $alt_texts[ absint( $alt_image_id ) ] = sanitize_text_field( $alt_value );
        }

        if ( empty( $alt_texts ) ) {
            wp_send_json_error( __( 'Invalid parameters.', 'product-image-seo' ) );
            return;
        }

        $saved = array();
        foreach ( $alt_texts as $image_id => $alt ) {
            if ( ! $image_id ) {
                continue;
            }
            update_post_meta( $image_id, '_wp_attachment_image_alt', $alt );

            // Refresh the per-image quality score + status so catalog rows stay
            // accurate and the caller can update the row in place without a reload.
            $attachment_score = $this->calculator->calculate_for_attachment( $image_id );
            update_post_meta( $image_id, '_prodimg_seo_1972adm_quality_score', $attachment_score['score'] );
            update_post_meta( $image_id, '_prodimg_seo_1972adm_score_breakdown', wp_json_encode( $attachment_score ) );
            Prodimg_Seo_1972adm_Status_Taxonomy::set_status_for_attachment( $image_id, $attachment_score['band'] );

            $saved[ $image_id ] = array(
                'score'       => $attachment_score['score'],
                'band'        => $attachment_score['band'],
                'explanation' => $attachment_score['explanation'],
            );
        }

        if ( $product_id ) {
            update_post_meta( $product_id, '_prodimg_seo_1972adm_processed_at', time() );

            // Recompute coverage + status taxonomy for this product after alt text save.
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $this->coverage_calculator->calculate( $product );
            }
        }

        Prodimg_Seo_1972adm_Statistics::flush_cache();
        wp_send_json_success( array(
            'message' => __( 'Saved successfully.', 'product-image-seo' ),
            'saved'   => $saved,
        ) );
    }

    /**
     * Resolve the parent product ID and image role for an attachment.
     *
     * Best-effort context for single-image generation: prefer a product whose
     * featured image is this attachment, otherwise a product post_parent. Role
     * falls back to any stored role meta, then to 'featured'.
     *
     * @param int $attachment_id Attachment post ID.
     * @return array { product_id:int, role:string }
     */
    private function resolve_attachment_context( $attachment_id ) {
        $attachment_id = absint( $attachment_id );
        $product_id    = 0;

        $role = get_post_meta( $attachment_id, '_prodimg_seo_1972adm_role', true );
        $role = $role ? sanitize_key( $role ) : '';

        // Featured image: a product whose _thumbnail_id points at this attachment.
        $featured_owner = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'any',
            'fields'         => 'ids',
            'numberposts'    => 1,
            'no_found_rows'  => true,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off single-image context lookup, bounded by numberposts=1.
            'meta_key'       => '_thumbnail_id',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- one-off single-image context lookup, bounded by numberposts=1.
            'meta_value'     => (string) $attachment_id,
        ) );
        if ( ! empty( $featured_owner ) ) {
            $product_id = absint( $featured_owner[0] );
            if ( '' === $role ) {
                $role = 'featured';
            }
        }

        // Otherwise fall back to a product post_parent (image uploaded to a product).
        if ( ! $product_id ) {
            $parent_id = wp_get_post_parent_id( $attachment_id );
            if ( $parent_id && 'product' === get_post_type( $parent_id ) ) {
                $product_id = absint( $parent_id );
                if ( '' === $role ) {
                    $role = 'gallery';
                }
            }
        }

        if ( '' === $role ) {
            $role = 'featured';
        }

        return array(
            'product_id' => $product_id,
            'role'       => $role,
        );
    }

    /**
     * AJAX: Scan all image attachments and compute their quality scores.
     *
     * Paginates through image attachments in chunks of 50. The JS caller
     * increments the page number until `done` is true.
     *
     * @return void Sends JSON response.
     */
    public function ajax_scan_all_images() {
        check_ajax_referer( 'prodimg_seo_1972adm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'product-image-seo' ) );
            return;
        }

        $page     = isset( $_POST['scan_page'] ) ? absint( wp_unslash( $_POST['scan_page'] ) ) : 1;
        $per_page = 50;

        $query = new WP_Query( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'fields'         => 'ids',
        ) );

        $total       = $query->found_posts;
        $total_pages = $query->max_num_pages;
        $processed   = 0;

        foreach ( $query->posts as $att_id ) {
            $result = $this->calculator->calculate_for_attachment( absint( $att_id ) );
            update_post_meta( absint( $att_id ), '_prodimg_seo_1972adm_quality_score', $result['score'] );
            update_post_meta( absint( $att_id ), '_prodimg_seo_1972adm_score_breakdown', wp_json_encode( $result ) );
            Prodimg_Seo_1972adm_Status_Taxonomy::set_status_for_attachment( absint( $att_id ), $result['band'] );
            $processed++;
        }

        Prodimg_Seo_1972adm_Statistics::flush_cache();
        wp_send_json_success( array(
            'done'        => $page >= $total_pages,
            'processed'   => $processed,
            'total'       => $total,
            'page'        => $page,
            'total_pages' => $total_pages,
        ) );
    }
}
