<?php
/**
 * DI Container
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Simple Dependency Injection Container.
 */
class Prodimg_Seo_1972adm_Container {

    /**
     * Services array.
     *
     * @var array
     */
    private $services = array();

    /**
     * Get a service instance.
     *
     * @param string $class_name The class name.
     * @return mixed
     */
    public function get( $class_name ) {
        if ( ! isset( $this->services[ $class_name ] ) ) {
            $this->services[ $class_name ] = $this->build( $class_name );
        }
        return $this->services[ $class_name ];
    }

    /**
     * Build a service instance.
     *
     * @param string $class_name The class name.
     * @return mixed
     */
    private function build( $class_name ) {
        switch ( $class_name ) {
            case 'Prodimg_Seo_1972adm_Settings':
                return new Prodimg_Seo_1972adm_Settings();

            case 'Prodimg_Seo_1972adm_Status_Taxonomy':
                return new Prodimg_Seo_1972adm_Status_Taxonomy();

            case 'Prodimg_Seo_1972adm_Api_Client':
                return new Prodimg_Seo_1972adm_Api_Client( $this->get( 'Prodimg_Seo_1972adm_Settings' ) );

            case 'Prodimg_Seo_1972adm_Coverage_Calculator':
                return new Prodimg_Seo_1972adm_Coverage_Calculator();

            case 'Prodimg_Seo_1972adm_Product_Scanner':
                return new Prodimg_Seo_1972adm_Product_Scanner(
                    $this->get( 'Prodimg_Seo_1972adm_Coverage_Calculator' )
                );

            case 'Prodimg_Seo_1972adm_Score_Calculator':
                return new Prodimg_Seo_1972adm_Score_Calculator();

            case 'Prodimg_Seo_1972adm_Bulk_Processor':
                return new Prodimg_Seo_1972adm_Bulk_Processor(
                    $this->get( 'Prodimg_Seo_1972adm_Api_Client' ),
                    $this->get( 'Prodimg_Seo_1972adm_Settings' ),
                    $this->get( 'Prodimg_Seo_1972adm_Score_Calculator' )
                );

            case 'Prodimg_Seo_1972adm_Statistics':
                return new Prodimg_Seo_1972adm_Statistics(
                    $this->get( 'Prodimg_Seo_1972adm_Score_Calculator' )
                );

            case 'Prodimg_Seo_1972adm_Csv_Exporter':
                return new Prodimg_Seo_1972adm_Csv_Exporter(
                    $this->get( 'Prodimg_Seo_1972adm_Statistics' )
                );

            case 'Prodimg_Seo_1972adm_Admin_Controller':
                return new Prodimg_Seo_1972adm_Admin_Controller(
                    $this->get( 'Prodimg_Seo_1972adm_Settings' ),
                    $this->get( 'Prodimg_Seo_1972adm_Api_Client' ),
                    $this->get( 'Prodimg_Seo_1972adm_Statistics' ),
                    $this->get( 'Prodimg_Seo_1972adm_Product_Scanner' ),
                    $this->get( 'Prodimg_Seo_1972adm_Score_Calculator' ),
                    $this->get( 'Prodimg_Seo_1972adm_Coverage_Calculator' )
                );

            case 'Prodimg_Seo_1972adm_Bulk_Controller':
                return new Prodimg_Seo_1972adm_Bulk_Controller(
                    $this->get( 'Prodimg_Seo_1972adm_Bulk_Processor' )
                );

            case 'Prodimg_Seo_1972adm_Auto_Generator':
                return new Prodimg_Seo_1972adm_Auto_Generator(
                    $this->get( 'Prodimg_Seo_1972adm_Settings' ),
                    $this->get( 'Prodimg_Seo_1972adm_Bulk_Processor' ),
                    $this->get( 'Prodimg_Seo_1972adm_Score_Calculator' )
                );

            default:
                if ( class_exists( $class_name ) ) {
                    return new $class_name();
                }
                return null;
        }
    }
}
