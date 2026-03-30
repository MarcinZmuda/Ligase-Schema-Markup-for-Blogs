<?php

defined( 'ABSPATH' ) || exit;

class Ligase_Type_Organization {

    public function build(): array {
        $opts = get_option( 'ligase_options', [] );
        $name = ! empty( $opts['org_name'] ) ? $opts['org_name'] : get_bloginfo( 'name' );

        $schema = [
            '@type' => 'Organization',
            '@id'   => home_url( '/#org' ),
            'name'  => esc_html( $name ),
            'url'   => esc_url( home_url( '/' ) ),
        ];

        $logo = $this->build_logo( $opts );
        if ( $logo ) {
            $schema['logo'] = $logo;
        }

        $social_keys = [
            'social_wikidata', 'social_wikipedia', 'social_linkedin',
            'social_facebook', 'social_twitter', 'social_youtube',
        ];
        $same_as = [];
        foreach ( $social_keys as $k ) {
            $url = $opts[ $k ] ?? '';
            if ( empty( $url ) ) {
                continue;
            }
            $url = esc_url( $url );
            // Validate URL has proper scheme and host
            $parsed = wp_parse_url( $url );
            if ( ! empty( $parsed['scheme'] ) && ! empty( $parsed['host'] ) && in_array( $parsed['scheme'], [ 'http', 'https' ], true ) ) {
                $same_as[] = $url;
            } else if ( class_exists( 'Ligase_Logger' ) ) {
                Ligase_Logger::warning( 'Invalid sameAs URL skipped', [ 'key' => $k, 'url' => $url ] );
            }
        }
        if ( ! empty( $same_as ) ) {
            $schema['sameAs'] = $same_as;
        }

        $knows = $opts['knows_about'] ?? '';
        if ( $knows ) {
            $schema['knowsAbout'] = array_map( 'trim', explode( ',', $knows ) );
        }

        if ( ! empty( $opts['org_email'] ) ) {
            $schema['email'] = sanitize_email( $opts['org_email'] );
        }

        if ( ! empty( $opts['org_phone'] ) ) {
            $schema['telephone'] = esc_html( $opts['org_phone'] );
            $schema['contactPoint'] = [
                '@type'       => 'ContactPoint',
                'telephone'   => esc_html( $opts['org_phone'] ),
                'contactType' => 'customer service',
            ];
        }

        if ( ! empty( $opts['org_description'] ) ) {
            $schema['description'] = esc_html( $opts['org_description'] );
        }

        // founder — linked Person @id
        if ( ! empty( $opts['org_founder_id'] ) ) {
            $founder_id = absint( $opts['org_founder_id'] );
            $schema['founder'] = [ '@id' => home_url( '/#author-' . $founder_id ) ];
        }

        // employee — all published authors linked by @id
        $authors = get_users( [
            'has_published_posts' => true,
            'fields'             => 'ID',
        ] );
        if ( ! empty( $authors ) ) {
            $schema['employee'] = array_map( fn( $uid ) => [
                '@id' => home_url( '/#author-' . $uid ),
            ], $authors );
        }

        return apply_filters( 'ligase_organization', $schema );
    }

    private function build_logo( array $opts ): ?array {
        $url = ! empty( $opts['org_logo'] ) ? $opts['org_logo'] : get_site_icon_url( 600 );
        if ( ! $url ) {
            return null;
        }

        return [
            '@type'  => 'ImageObject',
            '@id'    => home_url( '/#logo' ),
            'url'    => esc_url( $url ),
            'width'  => (int) ( $opts['logo_width']  ?? 600 ),
            'height' => (int) ( $opts['logo_height'] ?? 60 ),
        ];
    }
}
