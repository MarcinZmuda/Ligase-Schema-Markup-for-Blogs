<?php
/**
 * Ligase Multilingual Support
 *
 * Adapts schema output for WPML and Polylang.
 * Auto-detects active multilingual plugin and adjusts
 * inLanguage, hreflang sameAs, and translation linking.
 *
 * @package Ligase
 */

defined( 'ABSPATH' ) || exit;

class Ligase_Multilingual {

    /**
     * Detected multilingual plugin: 'wpml', 'polylang', or null.
     */
    private static ?string $plugin = null;

    /**
     * Detect active multilingual plugin.
     */
    public static function detect(): ?string {
        if ( self::$plugin !== null ) {
            return self::$plugin;
        }

        if ( defined( 'ICL_SITEPRESS_VERSION' ) || class_exists( 'SitePress' ) ) {
            self::$plugin = 'wpml';
        } elseif ( defined( 'POLYLANG_VERSION' ) || function_exists( 'pll_current_language' ) ) {
            self::$plugin = 'polylang';
        } else {
            self::$plugin = '';
        }

        return self::$plugin ?: null;
    }

    /**
     * Get the current language code (e.g., 'pl', 'en').
     */
    public static function get_current_language(): string {
        $plugin = self::detect();

        if ( $plugin === 'wpml' ) {
            return apply_filters( 'wpml_current_language', get_locale() );
        }

        if ( $plugin === 'polylang' && function_exists( 'pll_current_language' ) ) {
            return pll_current_language( 'slug' ) ?: get_locale();
        }

        return str_replace( '_', '-', get_locale() );
    }

    /**
     * Get the BCP47 language tag for schema inLanguage.
     */
    public static function get_language_tag(): string {
        $lang = self::get_current_language();

        // Convert short codes to BCP47 if needed
        $map = [
            'pl' => 'pl-PL',
            'en' => 'en-US',
            'de' => 'de-DE',
            'fr' => 'fr-FR',
            'es' => 'es-ES',
            'it' => 'it-IT',
            'pt' => 'pt-BR',
            'nl' => 'nl-NL',
            'cs' => 'cs-CZ',
            'sk' => 'sk-SK',
        ];

        if ( strlen( $lang ) === 2 && isset( $map[ $lang ] ) ) {
            return $map[ $lang ];
        }

        return str_replace( '_', '-', $lang );
    }

    /**
     * Get translation URLs for a post (for sameAs linking between translations).
     *
     * @param int $post_id Post ID.
     * @return array<string, string> Language code => URL.
     */
    public static function get_translation_urls( int $post_id ): array {
        $plugin = self::detect();
        $urls   = [];

        if ( $plugin === 'wpml' ) {
            $trid = apply_filters( 'wpml_element_trid', null, $post_id, 'post_post' );
            if ( $trid ) {
                $translations = apply_filters( 'wpml_get_element_translations', [], $trid, 'post_post' );
                foreach ( $translations as $lang => $trans ) {
                    if ( (int) $trans->element_id !== $post_id && ! empty( $trans->element_id ) ) {
                        $url = get_permalink( (int) $trans->element_id );
                        if ( $url ) {
                            $urls[ $lang ] = $url;
                        }
                    }
                }
            }
        }

        if ( $plugin === 'polylang' && function_exists( 'pll_get_post_translations' ) ) {
            $translations = pll_get_post_translations( $post_id );
            foreach ( $translations as $lang => $trans_id ) {
                if ( (int) $trans_id !== $post_id ) {
                    $url = get_permalink( (int) $trans_id );
                    if ( $url ) {
                        $urls[ $lang ] = $url;
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * Register filter to augment BlogPosting with multilingual data.
     */
    public static function init(): void {
        if ( ! self::detect() ) {
            return;
        }

        add_filter( 'ligase_blogposting', [ __CLASS__, 'augment_blogposting' ], 10, 2 );
        add_filter( 'ligase_website', [ __CLASS__, 'augment_website' ] );
    }

    /**
     * Add translation sameAs links to BlogPosting.
     */
    public static function augment_blogposting( array $schema, int $post_id ): array {
        // Override inLanguage with detected language
        $schema['inLanguage'] = self::get_language_tag();

        // Add translation URLs as sameAs
        $translations = self::get_translation_urls( $post_id );
        if ( ! empty( $translations ) ) {
            $existing = $schema['sameAs'] ?? [];
            if ( ! is_array( $existing ) ) {
                $existing = [ $existing ];
            }
            $schema['sameAs'] = array_merge( $existing, array_values( $translations ) );
        }

        return $schema;
    }

    /**
     * Override WebSite inLanguage.
     */
    public static function augment_website( array $schema ): array {
        $schema['inLanguage'] = self::get_language_tag();
        return $schema;
    }
}
