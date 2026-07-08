<?php
/**
 * Score Calculator.
 *
 * Computes image quality scores using the alt-audit algorithm:
 * Final = base(40%) + context(20%) + accessibility(40%).
 *
 * @package ProductImageSeo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Prodimg_Seo_1972adm_Score_Calculator {

    /**
     * Scoring criteria (inline criteria model).
     *
     * @var array
     */
    private $scoring_criteria = array(
        'length'          => array(
            'optimal_min'    => 30,
            'optimal_max'    => 150,
            'acceptable_min' => 15,
            'acceptable_max' => 200,
            'max_points'     => 30,
        ),
        'word_count'      => array(
            'optimal_min'    => 5,
            'optimal_max'    => 20,
            'acceptable_min' => 3,
            'acceptable_max' => 30,
            'max_points'     => 25,
        ),
        'descriptiveness' => array(
            'min_descriptive_words'   => 2,
            'good_descriptive_words'  => 1,
            'basic_descriptive_words' => 1,
            'max_points'              => 20,
        ),
        'structure'       => array(
            'max_points' => 25,
        ),
    );

    /**
     * Context criteria (inline).
     *
     * @var array
     */
    private $context_criteria = array(
        'product' => array(
            'required_elements' => array( 'product_name', 'key_features', 'visual_appearance' ),
            'length_preference' => array( 'min' => 60, 'max' => 120 ),
        ),
        'general' => array(
            'required_elements' => array( 'relevance_to_content' ),
            'length_preference' => array( 'min' => 40, 'max' => 100 ),
        ),
    );

    /**
     * Descriptive word patterns for scoring.
     *
     * @var array
     */
    private $descriptive_patterns = array();

    /**
     * Constructor — initialise the descriptive patterns list.
     */
    public function __construct() {
        // phpcs:disable Generic.Files.LineLength.TooLong -- Regex patterns cannot be split across lines.
        $this->descriptive_patterns = array(
            '/\b(red|blue|green|yellow|orange|purple|black|white|gray|grey|brown|pink|violet|indigo|cyan|magenta|maroon|navy|olive|teal|silver|gold|beige|cream|tan|burgundy|crimson|scarlet|turquoise|coral|lavender|ivory|charcoal)\b/i',
            '/\b(large|small|big|tiny|huge|massive|little|medium|tall|short|wide|narrow|long|thick|thin|spacious|compact|miniature|giant|oversized)\b/i',
            '/\b(round|square|circular|rectangular|triangular|oval|curved|straight|angular|spherical|flat|arched|domed|cylindrical)\b/i',
            '/\b(bright|dark|light|shadowy|sunny|cloudy|clear|blurry|sharp|soft|vivid|muted|illuminated|glowing|colorful|monochrome|vibrant|faded|dramatic|scenic)\b/i',
            '/\b(happy|sad|smiling|frowning|serious|excited|calm|angry|peaceful|joyful|content|cheerful|relaxed|focused|thoughtful)\b/i',
            '/\b(wooden|metal|glass|plastic|fabric|leather|stone|brick|concrete|ceramic|steel|iron|copper|brass|marble|granite|velvet|silk|cotton|wool|paper|cardboard)\b/i',
            '/\b(smooth|rough|glossy|matte|textured|polished|bumpy|silky|coarse|fine|shiny|rustic|sleek|elegant|modern|vintage|antique|ornate|decorative|patterned)\b/i',
            '/\b(standing|sitting|walking|running|holding|showing|displaying|featuring|depicting|wearing|carrying|pointing|looking|facing|crossing|moving|resting|hanging|floating|flying)\b/i',
        );
        // phpcs:enable Generic.Files.LineLength.TooLong
    }

    /**
     * Calculate quality score for a single attachment.
     *
     * @param int    $attachment_id Attachment post ID.
     * @param string $context_type  Context type: 'product' or 'general'.
     * @param array  $context_data  Optional context data (unused, reserved).
     * @return array { score, band, signals, explanation }
     */
    public function calculate_for_attachment( $attachment_id, $context_type = 'product', $context_data = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $context_data is reserved for future context-aware scoring per the documented method contract.
        $attachment_id = absint( $attachment_id );

        $alt_text = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
        $alt_text = trim( $alt_text );

        // Check for decorative flag.
        $is_decorative = (bool) get_post_meta( $attachment_id, '_prodimg_seo_1972adm_decorative', true );
        if ( $is_decorative && '' === $alt_text ) {
            $result = array(
                'score'       => 0,
                'band'        => 'decorative',
                'signals'     => array(),
                'explanation' => __( 'Image is marked as decorative.', 'product-image-seo' ),
            );
            return $result;
        }

        // Missing alt text.
        if ( '' === $alt_text ) {
            $result = array(
                'score'       => 0,
                'band'        => 'missing',
                'signals'     => array(),
                'explanation' => __( 'Alt text is missing. Add descriptive alt text to improve SEO and accessibility.', 'product-image-seo' ),
            );
            return $result;
        }

        $analysis = $this->analyze_alt_text( $alt_text );
        $context  = array(
            'type' => isset( $this->context_criteria[ $context_type ] ) ? $context_type : 'general',
        );

        $base_score          = $this->calculate_base_score( $alt_text, $analysis );
        $context_score       = $this->calculate_context_score( $alt_text, $context );
        $accessibility_score = $this->calculate_accessibility_score( $alt_text, $analysis );

        $final_score = (int) round( ( $base_score * 0.4 ) + ( $context_score * 0.2 ) + ( $accessibility_score * 0.4 ) );
        $final_score = max( 0, min( 100, $final_score ) );

        $band = $this->get_band_from_score( $final_score );

        $signals = array(
            'base_score'          => $base_score,
            'context_score'       => $context_score,
            'accessibility_score' => $accessibility_score,
            'length'              => $analysis['length'],
            'word_count'          => $analysis['word_count'],
        );

        $explanation = $this->generate_explanation( $band, $final_score, $analysis );

        $result = array(
            'score'       => $final_score,
            'band'        => $band,
            'signals'     => $signals,
            'explanation' => $explanation,
        );

        return $result;
    }

    /**
     * Backward-compatible thin wrapper: calculate for a product.
     *
     * Gets all images for a product, runs calculate_for_attachment on each,
     * returns averaged score, worst band, and aggregated signals.
     *
     * @param int $product_id WooCommerce product post ID.
     * @return array { score, band, signals, explanation }
     */
    public function calculate_for_product( $product_id ) {
        $product_id = absint( $product_id );
        $product    = $product_id ? wc_get_product( $product_id ) : null;

        if ( ! $product ) {
            return array(
                'score'       => 0,
                'band'        => 'missing',
                'signals'     => array(),
                'explanation' => __( 'Product not found.', 'product-image-seo' ),
            );
        }

        $image_ids = array();

        $featured_id = $product->get_image_id();
        if ( $featured_id ) {
            $image_ids[] = absint( $featured_id );
        }

        foreach ( $product->get_gallery_image_ids() as $gid ) {
            $image_ids[] = absint( $gid );
        }

        if ( $product->is_type( 'variable' ) ) {
            foreach ( $product->get_children() as $child_id ) {
                $child = wc_get_product( $child_id );
                if ( $child ) {
                    $vid = $child->get_image_id();
                    if ( $vid ) {
                        $image_ids[] = absint( $vid );
                    }
                }
            }
        }

        if ( empty( $image_ids ) ) {
            return array(
                'score'       => 0,
                'band'        => 'missing',
                'signals'     => array(),
                'explanation' => __( 'This product has no images to score.', 'product-image-seo' ),
            );
        }

        $scores      = array();
        $worst_band  = 'excellent';
        $band_order  = array( 'missing', 'decorative', 'weak', 'good', 'excellent' );
        $all_signals = array();

        foreach ( $image_ids as $att_id ) {
            $result = $this->calculate_for_attachment( $att_id, 'product' );
            $scores[]    = $result['score'];
            $all_signals = array_merge( $all_signals, $result['signals'] );

            // Track worst band.
            $result_band_idx = array_search( $result['band'], $band_order, true );
            $worst_band_idx  = array_search( $worst_band, $band_order, true );
            if ( false !== $result_band_idx && false !== $worst_band_idx && $result_band_idx < $worst_band_idx ) {
                $worst_band = $result['band'];
            }
        }

        $avg_score = (int) round( array_sum( $scores ) / count( $scores ) );

        return array(
            'score'       => $avg_score,
            'band'        => $worst_band,
            'signals'     => $all_signals,
            'explanation' => $this->generate_product_explanation( $worst_band, $avg_score ),
        );
    }

    /**
     * Build a human-readable explanation for a product-level (rollup) score.
     *
     * Summarises the worst-image band across the product's images.
     *
     * @param string $worst_band Worst band across the product's images.
     * @param int    $avg_score  Average image score 0–100.
     * @return string Translated explanation.
     */
    private function generate_product_explanation( $worst_band, $avg_score ) {
        switch ( $worst_band ) {
            case 'missing':
                /* translators: %d: average image score */
                return sprintf( __( 'At least one product image is missing alt text. Average image score: %d/100.', 'product-image-seo' ), $avg_score );

            case 'decorative':
                /* translators: %d: average image score */
                return sprintf( __( 'One or more product images are marked decorative. Average image score: %d/100.', 'product-image-seo' ), $avg_score );

            case 'weak':
                /* translators: %d: average image score */
                return sprintf( __( 'At least one product image has weak alt text that needs improvement. Average image score: %d/100.', 'product-image-seo' ), $avg_score );

            case 'good':
                /* translators: %d: average image score */
                return sprintf( __( 'Product images have good alt text. Average image score: %d/100.', 'product-image-seo' ), $avg_score );

            case 'excellent':
                /* translators: %d: average image score */
                return sprintf( __( 'All product images have excellent alt text. Average image score: %d/100.', 'product-image-seo' ), $avg_score );

            default:
                /* translators: %d: average image score */
                return sprintf( __( 'Average image score: %d/100.', 'product-image-seo' ), $avg_score );
        }
    }

    /**
     * Analyze alt text and return a data array for scoring.
     *
     * @param string $alt_text Alt text to analyze.
     * @return array Analysis data.
     */
    private function analyze_alt_text( $alt_text ) {
        $length     = strlen( $alt_text );
        $word_count = str_word_count( $alt_text );

        // Count descriptive words.
        $desc_count = 0;
        foreach ( $this->descriptive_patterns as $pattern ) {
            if ( preg_match_all( $pattern, $alt_text, $matches ) ) {
                $desc_count += count( $matches[0] );
            }
        }
        $weighted_desc = min( $desc_count, 5 ); // Cap at 5 for weighted_score.

        // Structure checks — up to 25 points.
        $structure_score = 0;
        if ( preg_match( '/^[A-Z]/', $alt_text ) ) {
            $structure_score += 5; // Proper capitalization.
        }
        if ( ! preg_match( '/\.(jpg|jpeg|png|gif|webp|svg|bmp|tiff|ico)$/i', $alt_text ) ) {
            $structure_score += 5; // No file extension.
        }
        if ( ! preg_match( '/\b(image of|picture of|photo of|graphic of|screenshot of)\b/i', $alt_text ) ) {
            $structure_score += 5; // No redundant phrases.
        }
        if ( ! preg_match( '/[.!?]$/', $alt_text ) || preg_match( '/\.$/', $alt_text ) ) {
            $structure_score += 5; // Proper punctuation (no or minimal trailing punct).
        }
        if ( ! preg_match( '/[!?]{2,}/', $alt_text ) ) {
            $structure_score += 5; // No excessive punctuation.
        }

        // WCAG compliance levels.
        $level_a   = ! empty( $alt_text );
        $level_aa  = $level_a && $length >= 10 && $length <= 150 && $word_count >= 3;
        $level_aaa = $level_aa && $length >= 30 && $length <= 125 && $word_count >= 5;

        // Language quality heuristics.
        $words = preg_split( '/\s+/', trim( $alt_text ) );
        $words = array_filter( $words );

        $total_word_len = 0;
        foreach ( $words as $w ) {
            $total_word_len += strlen( $w );
        }
        $avg_word_len = count( $words ) > 0 ? $total_word_len / count( $words ) : 0;

        $readability   = $avg_word_len < 8 ? 80 : 60;
        $clarity       = ! preg_match( '/\.(jpg|jpeg|png|gif|webp)/i', $alt_text ) ? 80 : 50;
        $specificity   = min( 100, $desc_count * 15 );

        return array(
            'length'           => $length,
            'word_count'       => $word_count,
            'descriptive_words' => array(
                'count'          => $desc_count,
                'weighted_score' => $weighted_desc,
            ),
            'structure_score'  => $structure_score,
            'wcag_compliance'  => array(
                'level_a'   => $level_a,
                'level_aa'  => $level_aa,
                'level_aaa' => $level_aaa,
            ),
            'language_quality' => array(
                'readability_score' => $readability,
                'clarity_score'     => $clarity,
                'specificity_score' => $specificity,
            ),
        );
    }

    /**
     * Calculate base quality score (max 100).
     *
     * @param string $alt_text Alt text string.
     * @param array  $analysis Analysis data from analyze_alt_text.
     * @return int Base score 0–100.
     */
    private function calculate_base_score( $alt_text, $analysis ) {
        $score = 0;
        $criteria = $this->scoring_criteria;

        $score += $this->score_length( $analysis['length'], $criteria['length'] );
        $score += $this->score_word_count( $analysis['word_count'], $criteria['word_count'] );
        $score += $this->score_descriptiveness( $analysis['descriptive_words'], $criteria['descriptiveness'] );

        $struct = is_array( $analysis['structure_score'] ) ? $analysis['structure_score']['score'] : $analysis['structure_score'];
        $score += min( $struct, $criteria['structure']['max_points'] );

        return min( 100, $score );
    }

    /**
     * Calculate context-specific score (max 100).
     *
     * @param string $alt_text Alt text string.
     * @param array  $context  Context data with 'type' key.
     * @return int Context score 0–100.
     */
    private function calculate_context_score( $alt_text, $context ) {
        $context_type     = $context['type'] ?? 'general';
        $context_criteria = $this->context_criteria[ $context_type ] ?? $this->context_criteria['general'];

        $score       = 80;
        $length      = strlen( $alt_text );
        $length_pref = $context_criteria['length_preference'];

        if ( $length >= $length_pref['min'] && $length <= $length_pref['max'] ) {
            $score += 20;
        } elseif ( $length > 0 ) {
            $deviation = min(
                abs( $length - $length_pref['min'] ),
                abs( $length - $length_pref['max'] )
            );
            $score -= min( 20, $deviation / 5 );
        }

        $required_elements = $context_criteria['required_elements'] ?? array();
        $found_elements    = $this->check_required_elements( $alt_text, $required_elements );
        $element_score     = ( count( $found_elements ) / max( 1, count( $required_elements ) ) ) * 20;
        $score             = ( $score * 0.8 ) + $element_score;

        return (int) max( 0, min( 100, round( $score ) ) );
    }

    /**
     * Calculate accessibility-focused score (max 100).
     *
     * @param string $alt_text Alt text string.
     * @param array  $analysis Analysis data.
     * @return int Accessibility score 0–100.
     */
    private function calculate_accessibility_score( $alt_text, $analysis ) {
        $score = 0;

        $base_score = $this->calculate_base_score( $alt_text, $analysis );
        $score     += $base_score * 0.7; // 70% of base.

        $wcag_data = $analysis['wcag_compliance'] ?? array();
        if ( ! empty( $wcag_data ) ) {
            if ( ! empty( $wcag_data['level_aaa'] ) ) {
                $score += 15;
            } elseif ( ! empty( $wcag_data['level_aa'] ) ) {
                $score += 10;
            } elseif ( ! empty( $wcag_data['level_a'] ) ) {
                $score += 5;
            } else {
                $score -= 20;
            }
        }

        $language_quality = $analysis['language_quality'] ?? array();
        if ( ! empty( $language_quality ) ) {
            $readability = $language_quality['readability_score'] ?? 0;
            $clarity     = $language_quality['clarity_score'] ?? 0;
            $specificity = $language_quality['specificity_score'] ?? 0;

            $language_score = ( $readability + $clarity + $specificity ) / 3;
            $score         += ( $language_score / 100 ) * 15;
        }

        return (int) max( 0, min( 100, round( $score ) ) );
    }

    /**
     * Score alt text length.
     *
     * @param int   $length   Text length.
     * @param array $criteria Length criteria.
     * @return int Score points.
     */
    private function score_length( $length, $criteria ) {
        $max_points = $criteria['max_points'];

        if ( $length >= $criteria['optimal_min'] && $length <= $criteria['optimal_max'] ) {
            return $max_points;
        } elseif ( $length >= $criteria['acceptable_min'] && $length <= $criteria['acceptable_max'] ) {
            return (int) round( $max_points * 0.8 );
        } elseif ( $length > 0 ) {
            return (int) round( $max_points * 0.4 );
        }

        return 0;
    }

    /**
     * Score word count.
     *
     * @param int   $word_count Word count.
     * @param array $criteria   Word count criteria.
     * @return int Score points.
     */
    private function score_word_count( $word_count, $criteria ) {
        $max_points = $criteria['max_points'];

        if ( $word_count >= $criteria['optimal_min'] && $word_count <= $criteria['optimal_max'] ) {
            return $max_points;
        } elseif ( $word_count >= $criteria['acceptable_min'] && $word_count <= $criteria['acceptable_max'] ) {
            return (int) round( $max_points * 0.75 );
        } elseif ( $word_count > 0 ) {
            return (int) round( $max_points * 0.3 );
        }

        return 0;
    }

    /**
     * Score descriptiveness.
     *
     * @param array $descriptive_data Descriptive words data.
     * @param array $criteria         Descriptiveness criteria.
     * @return int Score points.
     */
    private function score_descriptiveness( $descriptive_data, $criteria ) {
        $max_points = $criteria['max_points'];

        $count          = is_array( $descriptive_data ) ? $descriptive_data['count'] : $descriptive_data;
        $weighted_score = is_array( $descriptive_data ) ? ( $descriptive_data['weighted_score'] ?? $count ) : $count;

        if ( $weighted_score >= $criteria['min_descriptive_words'] ) {
            return $max_points;
        } elseif ( $count >= $criteria['good_descriptive_words'] ) {
            return (int) round( $max_points * 0.75 );
        } elseif ( $count >= $criteria['basic_descriptive_words'] ) {
            return (int) round( $max_points * 0.5 );
        }

        return 0;
    }

    /**
     * Check which required context elements are present in the alt text.
     *
     * @param string $alt_text         Alt text.
     * @param array  $required_elements Required elements list.
     * @return array Found elements.
     */
    private function check_required_elements( $alt_text, $required_elements ) {
        $found = array();

        foreach ( $required_elements as $element ) {
            switch ( $element ) {
                case 'product_name':
                    if ( preg_match( '/\b[A-Z][a-z]+ [A-Z][a-z]+\b/', $alt_text ) ) {
                        $found[] = $element;
                    }
                    break;

                case 'key_features':
                    if ( preg_match( '/\b(wireless|bluetooth|noise.canceling|waterproof|portable)\b/i', $alt_text ) ) {
                        $found[] = $element;
                    }
                    break;

                case 'visual_appearance':
                    foreach ( $this->descriptive_patterns as $pattern ) {
                        if ( preg_match( $pattern, $alt_text ) ) {
                            $found[] = $element;
                            break;
                        }
                    }
                    break;

                case 'relevance_to_content':
                    if ( preg_match( '/\b(shows|displays|illustrates|demonstrates|example)\b/i', $alt_text ) ) {
                        $found[] = $element;
                    }
                    break;

                case 'navigation_purpose':
                    if ( preg_match( '/\b(menu|navigation|home|back|next|previous|search)\b/i', $alt_text ) ) {
                        $found[] = $element;
                    }
                    break;

                case 'destination':
                    if ( preg_match( '/\b(go to|visit|open|link to|page)\b/i', $alt_text ) ) {
                        $found[] = $element;
                    }
                    break;
            }
        }

        return $found;
    }

    /**
     * Get band string from numeric score.
     *
     * Thresholds per plan: missing=0, weak=1-60, good=61-85, excellent=86+.
     * Public so other services (e.g. Statistics) can bucket by the same band
     * logic without duplicating the thresholds.
     *
     * @param int $score Numeric score 0–100.
     * @return string Band slug.
     */
    public function get_band_from_score( $score ) {
        if ( 0 === $score ) {
            return 'missing';
        }
        if ( $score <= 60 ) {
            return 'weak';
        }
        if ( $score <= 85 ) {
            return 'good';
        }
        return 'excellent';
    }

    /**
     * Generate a human-readable explanation for a score band.
     *
     * @param string $band     Score band.
     * @param int    $score    Numeric score.
     * @param array  $analysis Analysis data.
     * @return string Explanation text.
     */
    private function generate_explanation( $band, $score, $analysis ) {
        switch ( $band ) {
            case 'missing':
                return __( 'Alt text is missing. Add descriptive alt text to improve SEO and accessibility.', 'product-image-seo' );

            case 'decorative':
                return __( 'Image is marked as decorative (empty alt text is intentional).', 'product-image-seo' );

            case 'weak':
                $issues = array();
                if ( $analysis['length'] < 15 ) {
                    $issues[] = __( 'too short', 'product-image-seo' );
                } elseif ( $analysis['length'] > 200 ) {
                    $issues[] = __( 'too long', 'product-image-seo' );
                }
                if ( $analysis['word_count'] < 3 ) {
                    $issues[] = __( 'too few words', 'product-image-seo' );
                }
                if ( $analysis['descriptive_words']['count'] < 1 ) {
                    $issues[] = __( 'no descriptive words', 'product-image-seo' );
                }
                $issue_str = ! empty( $issues ) ? ' (' . implode( ', ', $issues ) . ')' : '';
                /* translators: 1: issue list, 2: score value */
                return sprintf( __( 'Alt text needs improvement%1$s. Score: %2$d/100.', 'product-image-seo' ), $issue_str, $score );

            case 'good':
                /* translators: %d score value */
                return sprintf( __( 'Alt text is good. Score: %d/100. Consider adding more descriptive words for an excellent rating.', 'product-image-seo' ), $score );

            case 'excellent':
                /* translators: %d score value */
                return sprintf( __( 'Excellent alt text. Score: %d/100.', 'product-image-seo' ), $score );

            default:
                /* translators: %d score value */
                return sprintf( __( 'Score: %d/100.', 'product-image-seo' ), $score );
        }
    }
}
