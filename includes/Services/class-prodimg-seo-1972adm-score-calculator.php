<?php
/**
 * Local Score Calculator.
 *
 * Computes a 0..100 image-SEO score for a WooCommerce product based on
 * per-image and per-product signals. Independent of the remote API.
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Score_Calculator {

    /**
     * Generic alt-text tokens that should not earn the "ideal" bonus.
     */
    private $generic_alt_tokens = array( 'image', 'photo', 'picture', 'product', 'img' );

    /**
     * Generic filename tokens.
     */
    private $generic_filename_tokens = array(
        'img', 'dsc', 'image', 'images', 'photo', 'photos',
        'picture', 'pictures', 'product', 'screenshot', 'untitled',
        'file', 'download', 'pic',
    );

    /**
     * Calculate the local score for a product.
     *
     * @param int $product_id Product ID.
     * @return array { score, band, signals }
     */
    public function calculate_for_product( $product_id ) {
        $product_id = absint( $product_id );
        $product    = $product_id ? wc_get_product( $product_id ) : null;

        $signals = array(
            'alt_text'      => array( 'weight' => 30, 'earned' => 0, 'reason' => '' ),
            'filename'      => array( 'weight' => 15, 'earned' => 0, 'reason' => '' ),
            'dimensions'    => array( 'weight' => 12, 'earned' => 0, 'reason' => '' ),
            'file_size'     => array( 'weight' => 15, 'earned' => 0, 'reason' => '' ),
            'modern_format' => array( 'weight' =>  8, 'earned' => 0, 'reason' => '' ),
            'gallery'       => array( 'weight' => 10, 'earned' => 0, 'reason' => '' ),
            'schema'        => array( 'weight' => 10, 'earned' => 0, 'reason' => '' ),
        );

        if ( ! $product ) {
            $signals['alt_text']['reason'] = __( 'Product not found.', 'product-image-seo' );
            return array(
                'score'   => 0,
                'band'    => 'poor',
                'signals' => $signals,
            );
        }

        $product_title = $product->get_name();
        $featured_id   = $product->get_image_id();
        $gallery_ids   = $product->get_gallery_image_ids();

        // Per-image scoring for featured and gallery images.
        $featured_per_image = array(
            'alt_text'      => 0,
            'filename'      => 0,
            'dimensions'    => 0,
            'file_size'     => 0,
            'modern_format' => 0,
            'reasons'       => array(),
        );

        if ( $featured_id ) {
            $featured_per_image = $this->score_image( $featured_id, $product_title );
        } else {
            $featured_per_image['reasons'] = array(
                'alt_text'      => __( 'No featured image set.', 'product-image-seo' ),
                'filename'      => __( 'No featured image set.', 'product-image-seo' ),
                'dimensions'    => __( 'No featured image set.', 'product-image-seo' ),
                'file_size'     => __( 'No featured image set.', 'product-image-seo' ),
                'modern_format' => __( 'No featured image set.', 'product-image-seo' ),
            );
        }

        // Average gallery per-image scores.
        $gallery_averages = array(
            'alt_text'      => 0,
            'filename'      => 0,
            'dimensions'    => 0,
            'file_size'     => 0,
            'modern_format' => 0,
        );

        if ( ! empty( $gallery_ids ) ) {
            $totals = array(
                'alt_text'      => 0,
                'filename'      => 0,
                'dimensions'    => 0,
                'file_size'     => 0,
                'modern_format' => 0,
            );
            $count = 0;
            foreach ( $gallery_ids as $gid ) {
                $img    = $this->score_image( $gid, $product_title );
                $count++;
                foreach ( array_keys( $totals ) as $k ) {
                    $totals[ $k ] += $img[ $k ];
                }
            }
            if ( $count > 0 ) {
                foreach ( $totals as $k => $sum ) {
                    $gallery_averages[ $k ] = $sum / $count;
                }
            }
        }

        // Combine featured (0.6) and gallery average (0.4).
        $has_gallery = ! empty( $gallery_ids );
        foreach ( array( 'alt_text', 'filename', 'dimensions', 'file_size', 'modern_format' ) as $key ) {
            if ( $has_gallery ) {
                $earned = ( $featured_per_image[ $key ] * 0.6 ) + ( $gallery_averages[ $key ] * 0.4 );
            } else {
                $earned = $featured_per_image[ $key ];
            }
            $signals[ $key ]['earned'] = (int) round( $earned );
            $signals[ $key ]['reason'] = isset( $featured_per_image['reasons'][ $key ] )
                ? $featured_per_image['reasons'][ $key ]
                : '';
        }

        // Gallery signal (product-level).
        $gallery_signal              = $this->score_gallery( $featured_id, $gallery_ids );
        $signals['gallery']['earned'] = $gallery_signal['earned'];
        $signals['gallery']['reason'] = $gallery_signal['reason'];

        // Schema signal (heuristic v1).
        $schema_signal               = $this->score_schema( $featured_id );
        $signals['schema']['earned'] = $schema_signal['earned'];
        $signals['schema']['reason'] = $schema_signal['reason'];

        $total = 0;
        foreach ( $signals as $sig ) {
            $total += (int) $sig['earned'];
        }
        if ( $total > 100 ) {
            $total = 100;
        }
        if ( $total < 0 ) {
            $total = 0;
        }

        $band = 'poor';
        if ( $total >= 80 ) {
            $band = 'good';
        } elseif ( $total >= 50 ) {
            $band = 'ok';
        }

        return array(
            'score'   => $total,
            'band'    => $band,
            'signals' => $signals,
        );
    }

    /**
     * Score a single image across the per-image signals.
     *
     * @param int    $image_id      Attachment ID.
     * @param string $product_title Product title for alt-text comparison.
     * @return array
     */
    private function score_image( $image_id, $product_title ) {
        $result = array(
            'alt_text'      => 0,
            'filename'      => 0,
            'dimensions'    => 0,
            'file_size'     => 0,
            'modern_format' => 0,
            'reasons'       => array(),
        );

        $file = get_attached_file( $image_id );
        if ( ! $file || ! file_exists( $file ) ) {
            $missing = __( 'Image file missing.', 'product-image-seo' );
            $result['reasons'] = array(
                'alt_text'      => $missing,
                'filename'      => $missing,
                'dimensions'    => $missing,
                'file_size'     => $missing,
                'modern_format' => $missing,
            );
            return $result;
        }

        // alt_text.
        $alt              = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
        $alt_score        = $this->score_alt_text( $alt, $product_title );
        $result['alt_text']                = $alt_score['earned'];
        $result['reasons']['alt_text']     = $alt_score['reason'];

        // filename.
        $filename_score                    = $this->score_filename( $file );
        $result['filename']                = $filename_score['earned'];
        $result['reasons']['filename']     = $filename_score['reason'];

        // dimensions.
        $dim_score                         = $this->score_dimensions( $image_id, $file );
        $result['dimensions']              = $dim_score['earned'];
        $result['reasons']['dimensions']   = $dim_score['reason'];

        // file_size.
        $size_score                        = $this->score_file_size( $file );
        $result['file_size']               = $size_score['earned'];
        $result['reasons']['file_size']    = $size_score['reason'];

        // modern_format.
        $fmt_score                         = $this->score_modern_format( $image_id, $file );
        $result['modern_format']           = $fmt_score['earned'];
        $result['reasons']['modern_format'] = $fmt_score['reason'];

        return $result;
    }

    /**
     * Score alt text (weight 30).
     */
    private function score_alt_text( $alt, $product_title ) {
        $alt = trim( (string) $alt );
        if ( '' === $alt ) {
            return array( 'earned' => 0, 'reason' => __( 'Alt text is missing.', 'product-image-seo' ) );
        }

        $alt_lc    = strtolower( $alt );
        $title_lc  = strtolower( trim( (string) $product_title ) );
        $length    = function_exists( 'mb_strlen' ) ? mb_strlen( $alt ) : strlen( $alt );

        $is_generic_word = in_array( $alt_lc, $this->generic_alt_tokens, true );
        $is_title_verbatim = ( '' !== $title_lc && $alt_lc === $title_lc );

        if ( $is_generic_word || $is_title_verbatim || $length < 20 ) {
            return array(
                'earned' => 10,
                /* translators: %d is the character length. */
                'reason' => sprintf( __( 'Alt text is generic or too short (%d chars).', 'product-image-seo' ), $length ),
            );
        }

        // Tokenize alt and product title to spot descriptive tokens.
        $alt_tokens   = $this->tokenize( $alt_lc );
        $title_tokens = $this->tokenize( $title_lc );
        $has_descriptor = false;
        foreach ( $alt_tokens as $tok ) {
            if ( strlen( $tok ) < 3 ) {
                continue;
            }
            if ( in_array( $tok, $this->generic_alt_tokens, true ) ) {
                continue;
            }
            if ( ! in_array( $tok, $title_tokens, true ) ) {
                $has_descriptor = true;
                break;
            }
        }

        if ( $length >= 40 && $length <= 140 && $has_descriptor ) {
            return array( 'earned' => 30, 'reason' => __( 'Alt text length and content look ideal.', 'product-image-seo' ) );
        }

        if ( ( $length >= 20 && $length <= 39 ) || $length >= 141 ) {
            return array(
                'earned' => 20,
                /* translators: %d is the character length. */
                'reason' => sprintf( __( 'Alt text is acceptable (%d chars); aim for 40-140 with a descriptor.', 'product-image-seo' ), $length ),
            );
        }

        // 40..140 but no descriptor.
        return array(
            'earned' => 20,
            'reason' => __( 'Alt text is acceptable; add a descriptor not already in the product title.', 'product-image-seo' ),
        );
    }

    /**
     * Score the filename (weight 15).
     */
    private function score_filename( $file ) {
        $basename = pathinfo( $file, PATHINFO_FILENAME );
        if ( '' === (string) $basename ) {
            return array( 'earned' => 0, 'reason' => __( 'Filename is empty.', 'product-image-seo' ) );
        }

        $lower = strtolower( $basename );

        // Patterns that earn 0.
        if ( preg_match( '/^img[_\-]?\d+$/i', $lower ) ) {
            return array( 'earned' => 0, 'reason' => __( 'Filename uses a camera default like IMG_####.', 'product-image-seo' ) );
        }
        if ( preg_match( '/^dsc[_\-]?\d+$/i', $lower ) ) {
            return array( 'earned' => 0, 'reason' => __( 'Filename uses a camera default like DSC_####.', 'product-image-seo' ) );
        }
        if ( preg_match( '/^screenshot/i', $lower ) ) {
            return array( 'earned' => 0, 'reason' => __( 'Filename is a screenshot dump.', 'product-image-seo' ) );
        }
        if ( 'untitled' === $lower || strpos( $lower, 'untitled' ) === 0 ) {
            return array( 'earned' => 0, 'reason' => __( 'Filename is "untitled".', 'product-image-seo' ) );
        }
        if ( preg_match( '/^\d+$/', $lower ) ) {
            return array( 'earned' => 0, 'reason' => __( 'Filename is purely numeric.', 'product-image-seo' ) );
        }
        if ( preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $lower ) ) {
            return array( 'earned' => 0, 'reason' => __( 'Filename is a UUID.', 'product-image-seo' ) );
        }
        // Single short word.
        if ( ! preg_match( '/[\-_]/', $lower ) && strlen( $lower ) < 6 ) {
            return array( 'earned' => 0, 'reason' => __( 'Filename is a single short word.', 'product-image-seo' ) );
        }

        // Normalize underscores to dashes for token counting.
        $normalized = str_replace( '_', '-', $lower );
        $is_kebab   = (bool) preg_match( '/^[a-z0-9]+(-[a-z0-9]+)+$/', $normalized );

        $tokens = array_filter(
            preg_split( '/[\-_]+/', $lower ),
            static function ( $t ) {
                return '' !== $t && ! is_numeric( $t );
            }
        );
        $descriptive = array();
        foreach ( $tokens as $t ) {
            if ( strlen( $t ) < 3 ) {
                continue;
            }
            if ( in_array( $t, $this->generic_filename_tokens, true ) ) {
                continue;
            }
            $descriptive[] = $t;
        }

        if ( $is_kebab && count( $descriptive ) >= 2 ) {
            return array( 'earned' => 15, 'reason' => __( 'Filename is descriptive and kebab-cased.', 'product-image-seo' ) );
        }

        if ( $is_kebab ) {
            return array( 'earned' => 8, 'reason' => __( 'Filename is kebab-cased but lacks descriptive tokens.', 'product-image-seo' ) );
        }

        return array( 'earned' => 0, 'reason' => __( 'Filename is not descriptive; use kebab-case keywords.', 'product-image-seo' ) );
    }

    /**
     * Score dimensions (weight 12).
     */
    private function score_dimensions( $image_id, $file ) {
        $width  = 0;
        $height = 0;

        $meta = wp_get_attachment_metadata( $image_id );
        if ( is_array( $meta ) ) {
            if ( ! empty( $meta['width'] ) ) {
                $width = (int) $meta['width'];
            }
            if ( ! empty( $meta['height'] ) ) {
                $height = (int) $meta['height'];
            }
        }

        if ( ( ! $width || ! $height ) && function_exists( 'getimagesize' ) ) {
            $info = @getimagesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            if ( is_array( $info ) ) {
                $width  = (int) $info[0];
                $height = (int) $info[1];
            }
        }

        if ( ! $width || ! $height ) {
            return array( 'earned' => 0, 'reason' => __( 'Image dimensions are unknown.', 'product-image-seo' ) );
        }

        $long_edge = max( $width, $height );

        if ( $long_edge < 600 ) {
            return array(
                'earned' => 0,
                /* translators: %d is the long-edge size in pixels. */
                'reason' => sprintf( __( 'Image is too small (%dpx long edge); aim for 800-2400px.', 'product-image-seo' ), $long_edge ),
            );
        }

        if ( ( $long_edge >= 600 && $long_edge <= 799 ) || $long_edge > 2400 ) {
            return array(
                'earned' => 6,
                /* translators: %d is the long-edge size in pixels. */
                'reason' => sprintf( __( 'Image long edge is %dpx; aim for 800-2400px.', 'product-image-seo' ), $long_edge ),
            );
        }

        return array(
            'earned' => 12,
            /* translators: %d is the long-edge size in pixels. */
            'reason' => sprintf( __( 'Image dimensions look good (%dpx long edge).', 'product-image-seo' ), $long_edge ),
        );
    }

    /**
     * Score file size (weight 15).
     */
    private function score_file_size( $file ) {
        $bytes = @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        if ( false === $bytes || $bytes <= 0 ) {
            return array( 'earned' => 0, 'reason' => __( 'File size could not be read.', 'product-image-seo' ) );
        }

        $kb = (int) round( $bytes / 1024 );

        if ( $kb < 150 ) {
            return array(
                'earned' => 15,
                /* translators: %d is the file size in KB. */
                'reason' => sprintf( __( 'File size is %d KB; well optimized.', 'product-image-seo' ), $kb ),
            );
        }

        if ( $kb <= 300 ) {
            return array(
                'earned' => 8,
                /* translators: %d is the file size in KB. */
                'reason' => sprintf( __( 'File is %d KB; aim for under 150 KB.', 'product-image-seo' ), $kb ),
            );
        }

        return array(
            'earned' => 0,
            /* translators: %d is the file size in KB. */
            'reason' => sprintf( __( 'File is %d KB; aim for under 150 KB.', 'product-image-seo' ), $kb ),
        );
    }

    /**
     * Score modern format (weight 8).
     */
    private function score_modern_format( $image_id, $file ) {
        $mime = get_post_mime_type( $image_id );
        if ( ! $mime ) {
            $type = wp_check_filetype( $file );
            if ( ! empty( $type['type'] ) ) {
                $mime = $type['type'];
            }
        }

        $mime = strtolower( (string) $mime );

        if ( 'image/webp' === $mime || 'image/avif' === $mime ) {
            return array(
                'earned' => 8,
                /* translators: %s is the MIME type. */
                'reason' => sprintf( __( 'Image uses a modern format (%s).', 'product-image-seo' ), $mime ),
            );
        }

        return array(
            'earned' => 0,
            'reason' => __( 'Image is not WebP or AVIF; convert to a modern format.', 'product-image-seo' ),
        );
    }

    /**
     * Score the gallery composition (weight 10).
     */
    private function score_gallery( $featured_id, $gallery_ids ) {
        if ( ! $featured_id ) {
            return array( 'earned' => 0, 'reason' => __( 'No featured image set.', 'product-image-seo' ) );
        }

        $gallery_count = is_array( $gallery_ids ) ? count( $gallery_ids ) : 0;

        if ( 0 === $gallery_count ) {
            return array( 'earned' => 5, 'reason' => __( 'Featured image only; add gallery images.', 'product-image-seo' ) );
        }

        if ( $gallery_count <= 2 ) {
            return array(
                'earned' => 8,
                /* translators: %d is the gallery image count. */
                'reason' => sprintf( __( 'Featured plus %d gallery image(s); add at least 3 for full credit.', 'product-image-seo' ), $gallery_count ),
            );
        }

        return array(
            'earned' => 10,
            /* translators: %d is the gallery image count. */
            'reason' => sprintf( __( 'Featured plus %d gallery images.', 'product-image-seo' ), $gallery_count ),
        );
    }

    /**
     * Score schema-emitted image (weight 10) — heuristic v1.
     */
    private function score_schema( $featured_id ) {
        if ( $featured_id ) {
            return array( 'earned' => 10, 'reason' => __( 'Product image will appear in schema markup.', 'product-image-seo' ) );
        }
        return array( 'earned' => 0, 'reason' => __( 'No featured image; product schema will lack an image.', 'product-image-seo' ) );
    }

    /**
     * Tokenize a string on whitespace and common separators.
     *
     * @param string $str Input string.
     * @return array
     */
    private function tokenize( $str ) {
        $parts = preg_split( '/[\s\-_,\.\/]+/', (string) $str );
        return array_values(
            array_filter(
                (array) $parts,
                static function ( $t ) {
                    return '' !== $t;
                }
            )
        );
    }
}
