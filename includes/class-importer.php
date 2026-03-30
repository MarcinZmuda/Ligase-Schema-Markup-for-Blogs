<?php
/**
 * Ligase Importer
 *
 * One-click import of settings from Yoast SEO, Rank Math, and All in One SEO.
 * Maps their options to Ligase format.
 *
 * @package Ligase
 */

defined( 'ABSPATH' ) || exit;

class Ligase_Importer {

    /**
     * Available import sources.
     */
    const SOURCES = [
        'yoast'    => 'Yoast SEO',
        'rankmath' => 'Rank Math',
        'aioseo'   => 'All in One SEO',
    ];

    /**
     * Detect which importable plugins have data.
     *
     * @return array<string, array{name: string, available: bool}>
     */
    public function detect_sources(): array {
        $sources = [];

        // Yoast
        $yoast_social = get_option( 'wpseo_social', [] );
        $yoast_titles = get_option( 'wpseo_titles', [] );
        $sources['yoast'] = [
            'name'      => 'Yoast SEO',
            'available' => ! empty( $yoast_social ) || ! empty( $yoast_titles ),
        ];

        // Rank Math
        $rm_titles = get_option( 'rank-math-options-titles', [] );
        $rm_general = get_option( 'rank-math-options-general', [] );
        $sources['rankmath'] = [
            'name'      => 'Rank Math',
            'available' => ! empty( $rm_titles ) || ! empty( $rm_general ),
        ];

        // AIOSEO
        $aioseo = get_option( 'aioseo_options', '' );
        $sources['aioseo'] = [
            'name'      => 'All in One SEO',
            'available' => ! empty( $aioseo ),
        ];

        return $sources;
    }

    /**
     * Run import from a given source.
     *
     * @param string $source Source key (yoast, rankmath, aioseo).
     * @return array{imported: int, skipped: int, details: array}
     */
    public function import( string $source ): array {
        return match ( $source ) {
            'yoast'    => $this->import_yoast(),
            'rankmath' => $this->import_rankmath(),
            'aioseo'   => $this->import_aioseo(),
            default    => [ 'imported' => 0, 'skipped' => 0, 'details' => [ 'Unknown source.' ] ],
        };
    }

    private function import_yoast(): array {
        $social  = get_option( 'wpseo_social', [] );
        $titles  = get_option( 'wpseo_titles', [] );
        $opts    = get_option( 'ligase_options', [] );
        $details = [];
        $imported = 0;
        $skipped  = 0;

        // Organization name
        if ( ! empty( $titles['company_name'] ) && empty( $opts['org_name'] ) ) {
            $opts['org_name'] = sanitize_text_field( $titles['company_name'] );
            $details[] = 'Nazwa organizacji: ' . $opts['org_name'];
            $imported++;
        } else { $skipped++; }

        // Logo
        if ( ! empty( $titles['company_logo'] ) && empty( $opts['org_logo'] ) ) {
            $opts['org_logo'] = esc_url_raw( $titles['company_logo'] );
            $details[] = 'Logo organizacji zaimportowane.';
            $imported++;
        } else { $skipped++; }

        // Social links -> sameAs
        $social_map = [
            'facebook_site'  => 'social_facebook',
            'twitter_site'   => 'social_twitter',
            'linkedin_url'   => 'social_linkedin',
            'youtube_url'    => 'social_youtube',
            'wikipedia_url'  => 'social_wikipedia',
        ];

        foreach ( $social_map as $yoast_key => $ligase_key ) {
            $value = $social[ $yoast_key ] ?? '';
            if ( ! empty( $value ) && empty( $opts[ $ligase_key ] ) ) {
                // Twitter might be just username
                if ( $yoast_key === 'twitter_site' && ! str_starts_with( $value, 'http' ) ) {
                    $value = 'https://twitter.com/' . ltrim( $value, '@' );
                }
                $opts[ $ligase_key ] = esc_url_raw( $value );
                $details[] = ucfirst( str_replace( 'social_', '', $ligase_key ) ) . ': ' . $opts[ $ligase_key ];
                $imported++;
            } else { $skipped++; }
        }

        // Author meta from Yoast user meta
        $authors = get_users( [ 'has_published_posts' => true, 'fields' => 'ID' ] );
        foreach ( $authors as $uid ) {
            $tw = get_user_meta( $uid, 'twitter', true );
            if ( $tw && ! get_user_meta( $uid, 'ligase_twitter', true ) ) {
                $url = str_starts_with( $tw, 'http' ) ? $tw : 'https://twitter.com/' . ltrim( $tw, '@' );
                update_user_meta( $uid, 'ligase_twitter', esc_url_raw( $url ) );
                $imported++;
            }
            $fb = get_user_meta( $uid, 'facebook', true );
            if ( $fb && ! get_user_meta( $uid, 'ligase_linkedin', true ) ) {
                // Yoast stores Facebook, we map to whatever is available
            }
        }

        update_option( 'ligase_options', $opts );

        Ligase_Logger::info( 'Yoast SEO import completed', [ 'imported' => $imported, 'skipped' => $skipped ] );

        return compact( 'imported', 'skipped', 'details' );
    }

    private function import_rankmath(): array {
        $titles  = get_option( 'rank-math-options-titles', [] );
        $general = get_option( 'rank-math-options-general', [] );
        $opts    = get_option( 'ligase_options', [] );
        $details = [];
        $imported = 0;
        $skipped  = 0;

        // Org name
        if ( ! empty( $titles['knowledgegraph_name'] ) && empty( $opts['org_name'] ) ) {
            $opts['org_name'] = sanitize_text_field( $titles['knowledgegraph_name'] );
            $details[] = 'Nazwa organizacji: ' . $opts['org_name'];
            $imported++;
        } else { $skipped++; }

        // Logo
        if ( ! empty( $titles['knowledgegraph_logo'] ) && empty( $opts['org_logo'] ) ) {
            $opts['org_logo'] = esc_url_raw( $titles['knowledgegraph_logo'] );
            $details[] = 'Logo organizacji zaimportowane.';
            $imported++;
        } else { $skipped++; }

        // Phone
        if ( ! empty( $titles['phone'] ) && empty( $opts['org_phone'] ) ) {
            $opts['org_phone'] = sanitize_text_field( $titles['phone'] );
            $details[] = 'Telefon: ' . $opts['org_phone'];
            $imported++;
        } else { $skipped++; }

        // Social
        $social_map = [
            'social_url_facebook'  => 'social_facebook',
            'social_url_twitter'   => 'social_twitter',
            'social_url_linkedin'  => 'social_linkedin',
            'social_url_youtube'   => 'social_youtube',
            'social_url_wikipedia' => 'social_wikipedia',
        ];

        foreach ( $social_map as $rm_key => $ligase_key ) {
            $value = $titles[ $rm_key ] ?? '';
            if ( ! empty( $value ) && empty( $opts[ $ligase_key ] ) ) {
                $opts[ $ligase_key ] = esc_url_raw( $value );
                $details[] = ucfirst( str_replace( 'social_', '', $ligase_key ) ) . ': ' . $opts[ $ligase_key ];
                $imported++;
            } else { $skipped++; }
        }

        update_option( 'ligase_options', $opts );

        Ligase_Logger::info( 'Rank Math import completed', [ 'imported' => $imported, 'skipped' => $skipped ] );

        return compact( 'imported', 'skipped', 'details' );
    }

    private function import_aioseo(): array {
        $raw     = get_option( 'aioseo_options', '' );
        $aioseo  = is_string( $raw ) ? json_decode( $raw, true ) : ( is_array( $raw ) ? $raw : [] );
        $opts    = get_option( 'ligase_options', [] );
        $details = [];
        $imported = 0;
        $skipped  = 0;

        if ( empty( $aioseo ) ) {
            return [ 'imported' => 0, 'skipped' => 0, 'details' => [ 'Brak danych AIOSEO.' ] ];
        }

        // Org name
        $org_name = $aioseo['searchAppearance']['global']['schema']['organizationName'] ?? '';
        if ( $org_name && empty( $opts['org_name'] ) ) {
            $opts['org_name'] = sanitize_text_field( $org_name );
            $details[] = 'Nazwa organizacji: ' . $opts['org_name'];
            $imported++;
        } else { $skipped++; }

        // Logo
        $logo = $aioseo['searchAppearance']['global']['schema']['organizationLogo'] ?? '';
        if ( $logo && empty( $opts['org_logo'] ) ) {
            $opts['org_logo'] = esc_url_raw( $logo );
            $details[] = 'Logo zaimportowane.';
            $imported++;
        } else { $skipped++; }

        // Social
        $social = $aioseo['social'] ?? [];
        $social_map = [
            'facebookUrl'  => 'social_facebook',
            'twitterUrl'   => 'social_twitter',
            'linkedinUrl'  => 'social_linkedin',
            'youtubeUrl'   => 'social_youtube',
            'wikipediaUrl' => 'social_wikipedia',
        ];

        foreach ( $social_map as $aio_key => $ligase_key ) {
            $profiles = $social['profiles'] ?? $social;
            $value    = $profiles[ $aio_key ] ?? '';
            if ( ! empty( $value ) && empty( $opts[ $ligase_key ] ) ) {
                $opts[ $ligase_key ] = esc_url_raw( $value );
                $details[] = ucfirst( str_replace( 'social_', '', $ligase_key ) ) . ': ' . $opts[ $ligase_key ];
                $imported++;
            } else { $skipped++; }
        }

        update_option( 'ligase_options', $opts );

        Ligase_Logger::info( 'AIOSEO import completed', [ 'imported' => $imported, 'skipped' => $skipped ] );

        return compact( 'imported', 'skipped', 'details' );
    }
}
