<?php

defined( 'ABSPATH' ) || exit;

class Ligase_Cache {
    const PREFIX = 'ligase_schema_';
    const TTL    = HOUR_IN_SECONDS * 12;

    public static function get( string $key ): mixed {
        return get_transient( self::PREFIX . md5( $key ) );
    }

    public static function set( string $key, string $value ): void {
        set_transient( self::PREFIX . md5( $key ), $value, self::TTL );
    }

    public static function invalidate_post( int $post_id ): void {
        $locales = [ get_locale() ];
        foreach ( $locales as $locale ) {
            delete_transient( self::PREFIX . md5( 'ligase_' . $post_id . '_' . $locale . '_' . LIGASE_VERSION ) );
        }

        if ( class_exists( 'Ligase_Logger' ) ) {
            Ligase_Logger::debug( 'Cache invalidated for post', [ 'post_id' => $post_id ] );
        }
    }

    public static function invalidate_all(): void {
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . self::PREFIX . '%',
            '_transient_timeout_' . self::PREFIX . '%'
        ) );

        if ( class_exists( 'Ligase_Logger' ) ) {
            Ligase_Logger::info( 'All schema cache invalidated' );
        }
    }
}
