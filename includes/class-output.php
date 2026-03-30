<?php

defined( 'ABSPATH' ) || exit;

class Ligase_Output {

    public static function render(): void {
        if ( is_404() ) {
            return;
        }

        if ( ! self::should_render() ) {
            return;
        }

        $post_id   = get_the_ID() ?: 0;
        $cache_key = 'ligase_' . $post_id . '_' . get_locale() . '_' . LIGASE_VERSION;
        $cached    = Ligase_Cache::get( $cache_key );

        if ( false !== $cached ) {
            echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput
            return;
        }

        // Check if auditor flagged this post for own schema generation
        if ( $post_id && self::needs_own_schema( $post_id ) ) {
            Ligase_Logger::info( 'Generating replacement schema for audited post', [ 'post_id' => $post_id ] );
        }

        $graph = ( new Ligase_Generator() )->get_graph();
        if ( empty( $graph ) ) {
            return;
        }

        $payload = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];

        $json = wp_json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        if ( false === $json || json_last_error() !== JSON_ERROR_NONE ) {
            Ligase_Logger::error( 'Schema JSON encoding failed', [
                'post_id' => $post_id,
                'error'   => json_last_error_msg(),
            ] );
            return;
        }

        $html = sprintf(
            "<script type=\"application/ld+json\">\n%s\n</script>\n",
            $json
        );

        Ligase_Cache::set( $cache_key, $html );
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
    }

    /**
     * Check if auditor flagged this post to need own schema (replacement).
     */
    private static function needs_own_schema( int $post_id ): bool {
        $flag = get_post_meta( $post_id, '_ligase_needs_own_schema', true );
        return $flag === '1';
    }

    private static function should_render(): bool {
        $opts = get_option( 'ligase_options', [] );

        // Force output — always render regardless of conflicts
        if ( ! empty( $opts['force_output'] ) ) {
            return true;
        }

        // Standalone mode — suppress other plugins, always render
        if ( ! empty( $opts['standalone_mode'] ) ) {
            return true;
        }

        // Default mode — don't render if another SEO plugin outputs schema
        if ( class_exists( 'Ligase_Suppressor' ) ) {
            $suppressor = new Ligase_Suppressor();
            $active = $suppressor->get_active_seo_plugins();
            if ( ! empty( $active ) ) {
                Ligase_Logger::info( 'Schema output skipped — active SEO plugins detected', [
                    'plugins' => array_column( $active, 'name' ),
                ] );
                return false;
            }
        }

        return true;
    }

    /**
     * Run suppressor early (called from init_hooks at plugins_loaded).
     * Must run before other SEO plugins register their wp_head output.
     */
    public static function maybe_suppress_early(): void {
        $opts = get_option( 'ligase_options', [] );

        if ( empty( $opts['standalone_mode'] ) ) {
            return;
        }

        if ( class_exists( 'Ligase_Suppressor' ) ) {
            $suppressor = new Ligase_Suppressor();
            $suppressor->suppress_all();
        }
    }
}
