<?php

defined( 'ABSPATH' ) || exit;

/**
 * Cache bypass utility.
 * Only used during admin AJAX scan operations, NOT on frontend.
 */
class Ligase_Cache_Bypass {

    /**
     * Bypass page caching for the current AJAX request only.
     * Call this before auditor scan operations.
     */
    public static function bypass_for_ajax(): void {
        if ( ! wp_doing_ajax() ) {
            return;
        }

        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }

        if ( ! headers_sent() ) {
            header( 'Cache-Control: no-store, no-cache, must-revalidate' );
            header( 'X-Ligase-Cache-Bypass: 1' );
        }
    }

    /**
     * Flush page cache for a specific post URL after schema replacement.
     * Supports WP Rocket, LiteSpeed, W3TC.
     */
    public static function flush_post_cache( int $post_id ): void {
        // WP Rocket
        if ( function_exists( 'rocket_clean_post' ) ) {
            rocket_clean_post( $post_id );
        }

        // LiteSpeed Cache
        if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_post' ) ) {
            \LiteSpeed_Cache_API::purge_post( $post_id );
        }

        // W3 Total Cache
        if ( function_exists( 'w3tc_flush_post' ) ) {
            w3tc_flush_post( $post_id );
        }

        // WP Super Cache
        if ( function_exists( 'wp_cache_post_change' ) ) {
            wp_cache_post_change( $post_id );
        }

        if ( class_exists( 'Ligase_Logger' ) ) {
            Ligase_Logger::debug( 'Post cache flushed', [ 'post_id' => $post_id ] );
        }
    }
}
