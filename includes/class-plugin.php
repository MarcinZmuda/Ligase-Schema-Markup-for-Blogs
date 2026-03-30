<?php

defined( 'ABSPATH' ) || exit;

class Ligase_Plugin {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void {
        $files = [
            'includes/class-logger.php',
            'includes/class-cache.php',
            'includes/class-suppressor.php',
            'includes/class-cache-bypass.php',
            'includes/class-score.php',
            'includes/types/class-blogposting.php',
            'includes/types/class-organization.php',
            'includes/types/class-person.php',
            'includes/types/class-website.php',
            'includes/types/class-breadcrumb.php',
            'includes/types/class-faqpage.php',
            'includes/types/class-howto.php',
            'includes/types/class-videoobject.php',
            'includes/types/class-review.php',
            'includes/types/class-qapage.php',
            'includes/types/class-definedterm.php',
            'includes/types/class-claimreview.php',
            'includes/types/class-softwareapplication.php',
            'includes/types/class-audioobject.php',
            'includes/types/class-course.php',
            'includes/types/class-event.php',
            'includes/class-generator.php',
            'includes/class-output.php',
            'includes/class-auditor.php',
            'includes/entities/class-pipeline.php',
            'includes/entities/class-extractor-native.php',
            'includes/entities/class-extractor-structure.php',
            'includes/entities/class-extractor-ner.php',
            'includes/entities/class-wikidata-lookup.php',
            'includes/class-ajax.php',
            'includes/class-importer.php',
            'includes/class-health-report.php',
            'includes/class-multilingual.php',
            'includes/class-validator.php',
            'includes/class-gsc.php',
        ];
        foreach ( $files as $file ) {
            $path = LIGASE_DIR . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }
        if ( is_admin() ) {
            $admin_files = [
                'admin/class-settings.php',
                'admin/class-admin.php',
            ];
            foreach ( $admin_files as $admin_file ) {
                $admin_path = LIGASE_DIR . $admin_file;
                if ( file_exists( $admin_path ) ) {
                    require_once $admin_path;
                }
            }
        }
    }

    private function init_hooks(): void {
        // Suppress other SEO plugins early (before they register wp_head output)
        add_action( 'wp_loaded', [ Ligase_Output::class, 'maybe_suppress_early' ] );

        add_action( 'wp_head', [ Ligase_Output::class, 'render' ], 5 );

        add_action( 'save_post',      [ Ligase_Cache::class, 'invalidate_post' ] );
        add_action( 'save_post',      function() { delete_transient( 'ligase_site_score' ); } );
        add_action( 'updated_option', [ Ligase_Cache::class, 'invalidate_all' ] );
        add_action( 'updated_option', function( string $option ) {
            if ( $option === 'ligase_options' ) {
                delete_transient( 'ligase_site_score' );
            }
        } );
        add_action( 'profile_update',      function( int $uid ) { delete_transient( 'ligase_author_score_' . $uid ); } );
        add_action( 'updated_user_meta',   function( $meta_id, $uid ) { delete_transient( 'ligase_author_score_' . $uid ); }, 10, 2 );

        add_action( 'ligase_wikidata_lookup', [ Ligase_Wikidata_Lookup::class, 'run_lookup' ], 10, 2 );

        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'init', [ $this, 'register_blocks' ] );

        // Multilingual support (WPML / Polylang)
        if ( class_exists( 'Ligase_Multilingual' ) ) {
            add_action( 'init', [ Ligase_Multilingual::class, 'init' ] );
        }

        // Health report cron
        if ( class_exists( 'Ligase_Health_Report' ) ) {
            Ligase_Health_Report::schedule();
            add_action( Ligase_Health_Report::CRON_HOOK, [ Ligase_Health_Report::class, 'run' ] );
        }

        // AJAX endpoints
        if ( class_exists( 'Ligase_Ajax' ) ) {
            new Ligase_Ajax();
        }

        if ( is_admin() && class_exists( 'Ligase_Admin' ) ) {
            $admin = new Ligase_Admin( LIGASE_VERSION, LIGASE_URL, LIGASE_DIR );
            $admin->init();
        }
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'ligase',
            false,
            dirname( plugin_basename( LIGASE_FILE ) ) . '/languages/'
        );
    }

    public function register_blocks(): void {
        $faq_path = LIGASE_DIR . 'blocks/faq/block.json';
        if ( file_exists( $faq_path ) ) {
            register_block_type( LIGASE_DIR . 'blocks/faq/', [
                'uses_context' => [ 'postId' ],
                'render_callback' => function( array $attrs, string $content, $block ): string {
                    $post_id = $block->context['postId'] ?? get_the_ID();
                    if ( $post_id && ! empty( $attrs['items'] ) ) {
                        update_post_meta( $post_id, '_ligase_faq_items', $attrs['items'] );
                    }
                    return '';
                },
            ] );
        }

        $howto_path = LIGASE_DIR . 'blocks/howto/block.json';
        if ( file_exists( $howto_path ) ) {
            register_block_type( LIGASE_DIR . 'blocks/howto/', [
                'uses_context' => [ 'postId' ],
                'render_callback' => function( array $attrs, string $content, $block ): string {
                    $post_id = $block->context['postId'] ?? get_the_ID();
                    if ( $post_id && ! empty( $attrs['steps'] ) ) {
                        update_post_meta( $post_id, '_ligase_howto', $attrs );
                    }
                    return '';
                },
            ] );
        }
    }
}
