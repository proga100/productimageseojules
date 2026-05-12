<?php
/**
 * Catalog List Table.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Prodimg_Seo_1972adm_Catalog_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'product',
            'plural'   => 'products',
            'ajax'     => false,
        ) );
    }

    public function get_columns() {
        return array(
            'cb'       => '<input type="checkbox" />',
            'thumb'    => __( 'Image', 'product-image-seo' ),
            'name'     => __( 'Name', 'product-image-seo' ),
            'sku'      => __( 'SKU', 'product-image-seo' ),
            'price'    => __( 'Price', 'product-image-seo' ),
            'coverage' => __( 'Coverage', 'product-image-seo' ),
            'score'    => __( 'Score', 'product-image-seo' ),
            'status'   => __( 'Status', 'product-image-seo' ),
            'actions'  => __( 'Actions', 'product-image-seo' ),
        );
    }

    protected function get_sortable_columns() {
        return array(
            'name'   => array( 'title', false ),
            'sku'    => array( '_sku', false ),
            'price'  => array( '_price', false ),
        );
    }

    protected function column_default( $item, $column_name ) {
        $product = wc_get_product( $item->ID );
        if ( ! $product ) {
            return '';
        }

        switch ( $column_name ) {
            case 'thumb':
                return $product->get_image( array( 50, 50 ) );
            case 'name':
                return '<strong><a href="' . esc_url( get_edit_post_link( $item->ID ) ) . '">' . esc_html( $product->get_name() ) . '</a></strong>';
            case 'sku':
                return esc_html( $product->get_sku() );
            case 'price':
                return wp_kses_post( $product->get_price_html() );
            case 'coverage':
                $cov = get_post_meta( $item->ID, '_prodimg_seo_1972adm_coverage', true );
                if ( $cov ) {
                    $cov_data = json_decode( $cov, true );
                    $total = ( $cov_data['featured'] ? 1 : 0 ) + $cov_data['gallery'] + $cov_data['variations'];
                    $expected = 1 + $cov_data['gallery_total'] + $cov_data['variations_total'];
                    return esc_html( $total . ' / ' . $expected );
                }
                return '-';
            case 'score':
                $score_local = get_post_meta( $item->ID, '_prodimg_seo_1972adm_score_local', true );
                if ( '' === $score_local ) {
                    return '<span class="prodimg-score-pill prodimg-score-pill--unknown">—</span>';
                }
                $score_local = intval( $score_local );
                $band        = $score_local >= 80 ? 'good' : ( $score_local >= 50 ? 'ok' : 'poor' );
                return sprintf(
                    '<span class="prodimg-score-pill prodimg-score-pill--%s">%d</span>',
                    esc_attr( $band ),
                    $score_local
                );
            case 'status':
                $terms = wp_get_post_terms( $item->ID, Prodimg_Seo_1972adm_Status_Taxonomy::TAXONOMY, array( 'fields' => 'names' ) );
                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    return esc_html( $terms[0] );
                }
                return esc_html__( 'Unknown', 'product-image-seo' );
            case 'actions':
                return '<button class="button prodimg-seo-generate-single" data-product-id="' . esc_attr( $item->ID ) . '">' . esc_html__( 'Generate Alt Text', 'product-image-seo' ) . '</button>';
            default:
                return '';
        }
    }

    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="product_ids[]" value="%d" />',
            $item->ID
        );
    }

    protected function get_bulk_actions() {
        return array(
            'generate' => __( 'Generate Alt Text', 'product-image-seo' ),
        );
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' !== $which ) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list-table filter inputs; values are unslashed + sanitized and the screen requires manage_woocommerce.
        $current_category = isset( $_REQUEST['product_cat'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['product_cat'] ) ) : '';
        $current_status   = isset( $_REQUEST['prodimg_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['prodimg_status'] ) ) : '';
        $current_stock    = isset( $_REQUEST['stock_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['stock_status'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        ?>
        <div class="alignleft actions">
            <?php
            // Category filter
            wp_dropdown_categories( array(
                'show_option_all' => __( 'All categories', 'product-image-seo' ),
                'taxonomy'        => 'product_cat',
                'name'            => 'product_cat',
                'orderby'         => 'name',
                'selected'        => $current_category,
                'show_count'      => false,
                'hide_empty'      => true,
                'value_field'     => 'slug',
            ) );

            // Status filter
            echo '<select name="prodimg_status">';
            echo '<option value="">' . esc_html__( 'All statuses', 'product-image-seo' ) . '</option>';
            foreach ( Prodimg_Seo_1972adm_Status_Taxonomy::TERMS as $term ) {
                echo '<option value="' . esc_attr( $term ) . '" ' . selected( $current_status, $term, false ) . '>' . esc_html( $term ) . '</option>';
            }
            echo '</select>';

            // Stock filter
            echo '<select name="stock_status">';
            echo '<option value="">' . esc_html__( 'All stock statuses', 'product-image-seo' ) . '</option>';
            echo '<option value="instock" ' . selected( $current_stock, 'instock', false ) . '>' . esc_html__( 'In stock', 'product-image-seo' ) . '</option>';
            echo '<option value="outofstock" ' . selected( $current_stock, 'outofstock', false ) . '>' . esc_html__( 'Out of stock', 'product-image-seo' ) . '</option>';
            echo '<option value="onbackorder" ' . selected( $current_stock, 'onbackorder', false ) . '>' . esc_html__( 'On backorder', 'product-image-seo' ) . '</option>';
            echo '</select>';

            submit_button( __( 'Filter', 'product-image-seo' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
            ?>
        </div>
        <?php
    }

    public function process_bulk_action() {
        if ( 'generate' === $this->current_action() ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WP_List_Table bulk-action submit; values are absint-cast and the screen requires manage_woocommerce.
            $product_ids = isset( $_REQUEST['product_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['product_ids'] ) ) : array();

            if ( ! empty( $product_ids ) ) {
                // Cannot call instance()->container directly if it's private,
                // instead we'll handle this differently or add a getter.
                // Assuming we can't add getter right now easily, we'll instantiate for the bulk process
                // or assume we have it via global or a new instance.
                $settings = new Prodimg_Seo_1972adm_Settings();
                $api_client = new Prodimg_Seo_1972adm_Api_Client( $settings );
                $processor = new Prodimg_Seo_1972adm_Bulk_Processor( $api_client, $settings );
                $result = $processor->enqueue_batch( $product_ids );

                if ( ! is_wp_error( $result ) ) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Bulk generation started. Please go to Bulk Fix to view progress.', 'product-image-seo' ) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
                }
            }
        }
    }

    public function prepare_items() {
        $this->process_bulk_action();

        $per_page = 20;
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $paged = $this->get_pagenum();

        $args = array(
            'limit'    => $per_page,
            'page'     => $paged,
            'status'   => 'publish',
            'paginate' => true,
        );

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list-table sort/filter inputs; values are unslashed + sanitized and the screen requires manage_woocommerce.
        // Sorting
        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $orderby = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
            $order   = ! empty( $_REQUEST['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) : 'DESC';

            if ( $orderby === 'title' ) {
                $args['orderby'] = 'title';
                $args['order'] = $order;
            } elseif ( $orderby === '_sku' ) {
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- catalog audit requires SKU sort.
                $args['meta_key'] = '_sku';
                $args['orderby']  = 'meta_value';
                $args['order']    = $order;
            } elseif ( $orderby === '_price' ) {
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- catalog audit requires price sort.
                $args['meta_key'] = '_price';
                $args['orderby']  = 'meta_value_num';
                $args['order']    = $order;
            }
        }

        // Filters
        if ( ! empty( $_REQUEST['product_cat'] ) ) {
            $args['category'] = array( sanitize_text_field( wp_unslash( $_REQUEST['product_cat'] ) ) );
        }

        if ( ! empty( $_REQUEST['stock_status'] ) ) {
            $args['stock_status'] = sanitize_text_field( wp_unslash( $_REQUEST['stock_status'] ) );
        }

        if ( ! empty( $_REQUEST['prodimg_status'] ) ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- catalog audit requires taxonomy filter; results paginated.
            $args['tax_query'] = array(
                array(
                    'taxonomy' => Prodimg_Seo_1972adm_Status_Taxonomy::TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( wp_unslash( $_REQUEST['prodimg_status'] ) ),
                ),
            );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $results = wc_get_products( $args );

        $this->items = array_map( function( $product ) {
            return (object) array( 'ID' => $product->get_id() );
        }, $results->products );

        $this->set_pagination_args( array(
            'total_items' => $results->total,
            'per_page'    => $per_page,
            'total_pages' => $results->max_num_pages,
        ) );
    }
}
