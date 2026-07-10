<?php
/**
 * Catalog List Table — Per-Image View.
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

    /**
     * Row-level cache: attachment_id => product post or false.
     *
     * @var array
     */
    private $product_cache = array();

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct( array(
            'singular' => 'image',
            'plural'   => 'images',
            'ajax'     => false,
        ) );
    }

    /**
     * Column definitions.
     *
     * @return array
     */
    public function get_columns() {
        return array(
            'cb'            => '<input type="checkbox" />',
            'thumbnail'     => __( 'Image', 'product-image-seo' ),
            'filename'      => __( 'File Name', 'product-image-seo' ),
            'product'       => __( 'Product', 'product-image-seo' ),
            'role'          => __( 'Role', 'product-image-seo' ),
            'alt_text'      => __( 'Alt Text', 'product-image-seo' ),
            'status'        => __( 'Status', 'product-image-seo' ),
            'quality_score' => __( 'Quality Score', 'product-image-seo' ),
            'actions'       => __( 'Actions', 'product-image-seo' ),
        );
    }

    /**
     * Sortable column definitions.
     *
     * @return array
     */
    protected function get_sortable_columns() {
        return array(
            'quality_score' => array( 'quality_score', false ),
        );
    }

    /**
     * Bulk action definitions.
     *
     * @return array
     */
    protected function get_bulk_actions() {
        return array(
            'generate' => __( 'Generate Alt Text', 'product-image-seo' ),
            'recalc'   => __( 'Recalculate Scores', 'product-image-seo' ),
        );
    }

    /**
     * Checkbox column.
     *
     * @param WP_Post $item Attachment post.
     * @return string
     */
    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="attachment_ids[]" value="%d" />',
            $item->ID
        );
    }

    /**
     * Thumbnail column.
     *
     * @param WP_Post $item Attachment post.
     * @return string
     */
    protected function column_thumbnail( $item ) {
        $img = wp_get_attachment_image( $item->ID, array( 80, 60 ), false, array(
            'style' => 'max-width:80px;height:auto;border-radius:4px;',
        ) );
        return $img ? $img : '<span class="prodimg-text-secondary">—</span>';
    }

    /**
     * File Name column with row actions.
     *
     * @param WP_Post $item Attachment post.
     * @return string
     */
    protected function column_filename( $item ) {
        $title      = get_the_title( $item->ID );
        $edit_link  = get_edit_post_link( $item->ID );
        $view_link  = wp_get_attachment_url( $item->ID );

        $output = '<strong><a href="' . esc_url( (string) $edit_link ) . '">' . esc_html( $title ) . '</a></strong>';

        $row_actions = array(
            'edit'     => '<a href="' . esc_url( (string) $edit_link ) . '">' . esc_html__( 'Edit', 'product-image-seo' ) . '</a>',
            'generate' => '<button type="button" class="button-link prodimg-seo-generate-attachment" data-attachment-id="' . esc_attr( (string) $item->ID ) . '">' . esc_html__( 'Generate', 'product-image-seo' ) . '</button>',
            'recalc'   => '<button type="button" class="button-link prodimg-seo-recalc-attachment" data-attachment-id="' . esc_attr( (string) $item->ID ) . '">' . esc_html__( 'Recalc Score', 'product-image-seo' ) . '</button>',
            'view'     => '<a href="' . esc_url( (string) $view_link ) . '" target="_blank">' . esc_html__( 'View', 'product-image-seo' ) . '</a>',
        );

        return $output . $this->row_actions( $row_actions );
    }

    /**
     * Product column — resolve parent product.
     *
     * @param WP_Post $item Attachment post.
     * @return string
     */
    protected function column_product( $item ) {
        $product = $this->resolve_product_for_attachment( $item->ID );
        if ( ! $product ) {
            return '<span class="prodimg-text-secondary">' . esc_html__( '—', 'product-image-seo' ) . '</span>';
        }

        $edit_link = get_edit_post_link( $product->ID );
        return '<a href="' . esc_url( (string) $edit_link ) . '">' . esc_html( get_the_title( $product->ID ) ) . '</a>';
    }

    /**
     * Role column.
     *
     * @param WP_Post $item Attachment post.
     * @return string
     */
    protected function column_role( $item ) {
        $role = get_post_meta( $item->ID, '_prodimg_seo_1972adm_role', true );
        $role = $role ? sanitize_key( $role ) : '';

        // No stored role — derive it from the resolved parent product (row-cached).
        if ( '' === $role ) {
            $product = $this->resolve_product_for_attachment( $item->ID );
            if ( $product ) {
                if ( 'product_variation' === $product->post_type ) {
                    $role = 'variation';
                } elseif ( (int) get_post_meta( $product->ID, '_thumbnail_id', true ) === (int) $item->ID ) {
                    $role = 'featured';
                } else {
                    $role = 'gallery';
                }
            }
        }

        $labels = array(
            'featured'  => __( 'Featured', 'product-image-seo' ),
            'gallery'   => __( 'Gallery', 'product-image-seo' ),
            'variation' => __( 'Variation', 'product-image-seo' ),
        );

        if ( isset( $labels[ $role ] ) ) {
            return '<span class="prodimg-status-badge prodimg-status-badge--role-' . esc_attr( $role ) . '">'
                . esc_html( $labels[ $role ] ) . '</span>';
        }

        return '<span class="prodimg-text-secondary">—</span>';
    }

    /**
     * Alt Text column.
     *
     * @param WP_Post $item Attachment post.
     * @return string
     */
    protected function column_alt_text( $item ) {
        $alt = (string) get_post_meta( $item->ID, '_wp_attachment_image_alt', true );
        $alt = trim( $alt );

        if ( '' === $alt ) {
            return '<em class="prodimg-text-secondary">' . esc_html__( '(no alt text)', 'product-image-seo' ) . '</em>';
        }

        $display = strlen( $alt ) > 100 ? substr( $alt, 0, 97 ) . '…' : $alt;
        return '<span title="' . esc_attr( $alt ) . '">' . esc_html( $display ) . '</span>';
    }

    /**
     * Status column.
     *
     * @param WP_Post $item Attachment post.
     * @return string
     */
    protected function column_status( $item ) {
        $terms = wp_get_post_terms( $item->ID, Prodimg_Seo_1972adm_Status_Taxonomy::TAXONOMY, array( 'fields' => 'slugs' ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '<span class="prodimg-status-badge prodimg-status-badge--missing">' . esc_html__( 'Missing', 'product-image-seo' ) . '</span>';
        }

        $band = $terms[0];

        $labels = array(
            'missing'    => __( 'Missing', 'product-image-seo' ),
            'weak'       => __( 'Weak', 'product-image-seo' ),
            'good'       => __( 'Good', 'product-image-seo' ),
            'excellent'  => __( 'Excellent', 'product-image-seo' ),
            'decorative' => __( 'Decorative', 'product-image-seo' ),
        );

        $label = isset( $labels[ $band ] ) ? $labels[ $band ] : ucfirst( $band );

        return '<span class="prodimg-status-badge prodimg-status-badge--' . esc_attr( $band ) . '">' . esc_html( $label ) . '</span>';
    }

    /**
     * Quality Score column with score bar.
     *
     * @param WP_Post $item Attachment post.
     * @return string
     */
    protected function column_quality_score( $item ) {
        $score = get_post_meta( $item->ID, '_prodimg_seo_1972adm_quality_score', true );

        if ( '' === $score ) {
            return '<span class="prodimg-text-secondary">—</span>';
        }

        $score = intval( $score );
        $band  = $score >= 86 ? 'excellent' : ( $score >= 61 ? 'good' : ( $score > 0 ? 'weak' : 'missing' ) );
        $pct   = $score . '%';

        return sprintf(
            '<div class="prodimg-score-bar">'
            . '<div class="prodimg-score-bar__track">'
            . '<div class="prodimg-score-bar__fill prodimg-score-bar__fill--%s" style="width:%s;"></div>'
            . '</div>'
            . '<span>%d</span>'
            . '</div>',
            esc_attr( $band ),
            esc_attr( $pct ),
            $score
        );
    }

    /**
     * Actions column.
     *
     * @param WP_Post $item Attachment post.
     * @return string
     */
    protected function column_actions( $item ) {
        $att_id = $item->ID;
        return '<div class="prodimg-actions-group">'
            . '<button type="button" class="button button-small prodimg-seo-generate-attachment" data-attachment-id="' . esc_attr( (string) $att_id ) . '">'
            . esc_html__( 'Generate', 'product-image-seo' )
            . '</button>'
            . '<button type="button" class="button button-small prodimg-seo-recalc-attachment" data-attachment-id="' . esc_attr( (string) $att_id ) . '">'
            . esc_html__( 'Recalc Score', 'product-image-seo' )
            . '</button>'
            . '</div>';
    }

    /**
     * Default column renderer.
     *
     * @param WP_Post $item        Attachment post.
     * @param string  $column_name Column slug.
     * @return string
     */
    protected function column_default( $item, $column_name ) {
        return '';
    }

    /**
     * Extra table nav: status filter + role filter + search.
     *
     * @param string $which 'top' | 'bottom'.
     * @return void
     */
    protected function extra_tablenav( $which ) {
        if ( 'top' !== $which ) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list-table filter inputs; values are unslashed + sanitized and the screen requires manage_woocommerce.
        $current_status = isset( $_REQUEST['prodimg_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['prodimg_status'] ) ) : '';
        $current_role   = isset( $_REQUEST['role_filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['role_filter'] ) ) : '';
        $current_search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        ?>
        <div class="alignleft actions">
            <select name="prodimg_status">
                <option value=""><?php esc_html_e( 'All statuses', 'product-image-seo' ); ?></option>
                <?php foreach ( Prodimg_Seo_1972adm_Status_Taxonomy::IMAGE_TERMS as $term ) : ?>
                    <option value="<?php echo esc_attr( $term ); ?>" <?php selected( $current_status, $term ); ?>>
                        <?php echo esc_html( ucfirst( $term ) ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="role_filter">
                <option value=""><?php esc_html_e( 'All roles', 'product-image-seo' ); ?></option>
                <option value="featured" <?php selected( $current_role, 'featured' ); ?>><?php esc_html_e( 'Featured', 'product-image-seo' ); ?></option>
                <option value="gallery" <?php selected( $current_role, 'gallery' ); ?>><?php esc_html_e( 'Gallery', 'product-image-seo' ); ?></option>
                <option value="variation" <?php selected( $current_role, 'variation' ); ?>><?php esc_html_e( 'Variation', 'product-image-seo' ); ?></option>
            </select>

            <input type="search" name="s" value="<?php echo esc_attr( $current_search ); ?>" placeholder="<?php esc_attr_e( 'Search images…', 'product-image-seo' ); ?>" />

            <?php submit_button( __( 'Filter', 'product-image-seo' ), '', 'filter_action', false ); ?>
        </div>
        <?php
    }

    /**
     * Process bulk actions.
     *
     * @return void
     */
    public function process_bulk_action() {
        if ( 'recalc' === $this->current_action() ) {
            check_admin_referer( 'bulk-' . $this->_args['plural'] );
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked via check_admin_referer above.
            $attachment_ids = isset( $_REQUEST['attachment_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['attachment_ids'] ) ) : array();

            if ( ! empty( $attachment_ids ) ) {
                $calculator = new Prodimg_Seo_1972adm_Score_Calculator();
                foreach ( $attachment_ids as $att_id ) {
                    $result = $calculator->calculate_for_attachment( $att_id );
                    update_post_meta( $att_id, '_prodimg_seo_1972adm_quality_score', $result['score'] );
                    Prodimg_Seo_1972adm_Status_Taxonomy::set_status_for_attachment( $att_id, $result['band'] );
                }
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Scores recalculated.', 'product-image-seo' ) . '</p></div>';
            }
        }

        if ( 'generate' === $this->current_action() ) {
            check_admin_referer( 'bulk-' . $this->_args['plural'] );
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Bulk generation is handled via the Bulk Fix page.', 'product-image-seo' ) . '</p></div>';
        }
    }

    /**
     * Build the query and populate $this->items.
     *
     * @return void
     */
    public function prepare_items() {
        $this->process_bulk_action();

        $per_page = 20;
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $paged = $this->get_pagenum();

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list-table sort/filter; values sanitized.
        $orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : '';
        $order   = isset( $_REQUEST['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) : 'DESC';
        $order   = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

        $status_filter = isset( $_REQUEST['prodimg_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['prodimg_status'] ) ) : '';
        $role_filter   = isset( $_REQUEST['role_filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['role_filter'] ) ) : '';
        $search        = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        // Scope to product images (featured/gallery/variation) only — this is the
        // "Product Images" catalog, not the whole media library. array( 0 ) forces
        // an empty result when the store has no product images (post__in => array()
        // would otherwise be ignored by WP_Query and return everything).
        $product_image_ids = Prodimg_Seo_1972adm_Statistics::get_product_image_ids( new Prodimg_Seo_1972adm_Score_Calculator() );

        $query_args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'post__in'       => ! empty( $product_image_ids ) ? $product_image_ids : array( 0 ),
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => $order,
        );

        if ( 'quality_score' === $orderby ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Catalog audit requires score sort; results paginated.
            $query_args['meta_key'] = '_prodimg_seo_1972adm_quality_score';
            $query_args['orderby']  = 'meta_value_num';
        }

        if ( $search ) {
            $query_args['s'] = $search;
        }

        if ( $status_filter && in_array( $status_filter, Prodimg_Seo_1972adm_Status_Taxonomy::IMAGE_TERMS, true ) ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Catalog audit requires taxonomy filter; results paginated.
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => Prodimg_Seo_1972adm_Status_Taxonomy::TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => $status_filter,
                ),
            );
        }

        if ( $role_filter ) {
            $role_filter = sanitize_key( $role_filter );
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Role filter; results paginated.
            $query_args['meta_query'] = array(
                array(
                    'key'     => '_prodimg_seo_1972adm_role',
                    'value'   => $role_filter,
                    'compare' => '=',
                ),
            );
        }

        $query = new WP_Query( $query_args );

        $this->items = $query->posts;

        $this->set_pagination_args( array(
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => $query->max_num_pages,
        ) );
    }

    /**
     * Resolve the parent WooCommerce product for an attachment.
     *
     * Three-step strategy:
     *  1. post_parent is a product.
     *  2. _thumbnail_id postmeta on a product points to this attachment.
     *  3. _product_image_gallery postmeta on a product contains this attachment.
     *
     * @param int $att_id Attachment post ID.
     * @return WP_Post|false Product WP_Post or false.
     */
    private function resolve_product_for_attachment( $att_id ) {
        if ( array_key_exists( $att_id, $this->product_cache ) ) {
            return $this->product_cache[ $att_id ];
        }

        // Step 1: check post_parent.
        $attachment = get_post( $att_id );
        if ( $attachment && $attachment->post_parent ) {
            $parent = get_post( $attachment->post_parent );
            if ( $parent && 'product' === $parent->post_type ) {
                $this->product_cache[ $att_id ] = $parent;
                return $parent;
            }
        }

        // Step 2: _thumbnail_id postmeta.
        $products = get_posts( array(
            'post_type'      => 'product',
            'meta_key'       => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Resolving the parent product for one attachment row; bounded by numberposts=1 and memoized per-request in $this->product_cache.
            'meta_value'     => $att_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Resolving the parent product for one attachment row; bounded by numberposts=1 and memoized per-request in $this->product_cache.
            'numberposts'    => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );

        if ( ! empty( $products ) ) {
            $product = get_post( $products[0] );
            $this->product_cache[ $att_id ] = $product;
            return $product;
        }

        // Step 3: _product_image_gallery LIKE search.
        global $wpdb;
        $like_value  = '%' . $wpdb->esc_like( (string) $att_id ) . '%';
        $product_id  = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Gallery membership needs a serialized-postmeta LIKE scan not expressible via WP_Query; result (hits and misses) is memoized per-request in $this->product_cache, so each attachment row queries at most once.
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = '_product_image_gallery'
                   AND meta_value LIKE %s
                 LIMIT 1",
                $like_value
            )
        );

        if ( $product_id ) {
            $product = get_post( absint( $product_id ) );
            if ( $product && 'product' === $product->post_type ) {
                $this->product_cache[ $att_id ] = $product;
                return $product;
            }
        }

        $this->product_cache[ $att_id ] = false;
        return false;
    }
}
