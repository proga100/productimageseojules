<?php
/**
 * Plugin Bootstrap
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 */
class Prodimg_Seo_1972adm_Plugin {

    /**
     * Singleton instance.
     *
     * @var Prodimg_Seo_1972adm_Plugin
     */
    private static $instance = null;

    /**
     * Container instance.
     *
     * @var Prodimg_Seo_1972adm_Container
     */
    private $container = null;

    /**
     * Get the singleton instance.
     *
     * @return Prodimg_Seo_1972adm_Plugin
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Container
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'class-prodimg-seo-1972adm-container.php';

        // Services
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-settings.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-status-taxonomy.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-api-client.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-product-context.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-product-scanner.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-coverage-calculator.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-bulk-processor.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-csv-exporter.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-statistics.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-auto-generator.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Services/class-prodimg-seo-1972adm-score-calculator.php';

        // Controllers
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Controllers/class-prodimg-seo-1972adm-admin-controller.php';
        require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Controllers/class-prodimg-seo-1972adm-bulk-controller.php';

        // WP_List_Table subclass must be loaded when needed or included here
        if ( is_admin() && ! class_exists( 'WP_List_Table' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }
        if ( is_admin() ) {
            require_once PRODIMG_SEO_1972ADM_INCLUDES_DIR . 'Controllers/class-prodimg-seo-1972adm-catalog-list-table.php';
        }

        $this->container = new Prodimg_Seo_1972adm_Container();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'register_taxonomy' ) );

        if ( is_admin() ) {
            $admin_controller = $this->container->get( 'Prodimg_Seo_1972adm_Admin_Controller' );
            $admin_controller->init_hooks();

            $bulk_controller = $this->container->get( 'Prodimg_Seo_1972adm_Bulk_Controller' );
            $bulk_controller->init_hooks();
        }

        $bulk_processor = $this->container->get( 'Prodimg_Seo_1972adm_Bulk_Processor' );
        $bulk_processor->init_hooks();

        $csv_exporter = $this->container->get( 'Prodimg_Seo_1972adm_Csv_Exporter' );
        $csv_exporter->init_hooks();

        $auto_generator = $this->container->get( 'Prodimg_Seo_1972adm_Auto_Generator' );
        if ( $auto_generator ) {
            $auto_generator->init_hooks();
        }
    }

    /**
     * Register taxonomy.
     */
    public function register_taxonomy() {
        $taxonomy = $this->container->get( 'Prodimg_Seo_1972adm_Status_Taxonomy' );
        $taxonomy->register();
    }
}
