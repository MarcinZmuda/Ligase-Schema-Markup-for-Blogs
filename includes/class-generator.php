<?php

defined( 'ABSPATH' ) || exit;

class Ligase_Generator {

    public function get_graph(): array {
        $graph = [];

        $graph[] = ( new Ligase_Type_WebSite() )->build();
        $graph[] = ( new Ligase_Type_Organization() )->build();

        if ( is_single() && get_post_type() === 'post' ) {
            $author_id = (int) get_post_field( 'post_author' );

            $graph[] = ( new Ligase_Type_BlogPosting() )->build();
            $graph[] = ( new Ligase_Type_Person( $author_id ) )->build();
            $graph[] = ( new Ligase_Type_BreadcrumbList() )->build();

            foreach ( $this->get_optional_types() as $type ) {
                $schema = $type->build();
                if ( ! empty( $schema ) ) {
                    $graph[] = $schema;
                }
            }
        }

        if ( is_category() ) {
            $graph[] = ( new Ligase_Type_BreadcrumbList() )->build();
        }

        $graph = apply_filters( 'ligase_schema_graph', $graph );

        return array_values( array_filter( $graph ) );
    }

    /**
     * Generate schema for a specific post without outputting.
     * Used by AJAX preview and testing.
     */
    public function get_graph_for_post( int $post_id ): array {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return [];
        }

        // Set up global post context so get_the_ID(), get_the_title() etc. work
        global $wp_query;
        $original_post  = $GLOBALS['post'] ?? null;
        $original_query = $wp_query->post ?? null;
        $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
        setup_postdata( $post );

        $graph = [];

        $graph[] = ( new Ligase_Type_WebSite() )->build();
        $graph[] = ( new Ligase_Type_Organization() )->build();

        $author_id = (int) get_post_field( 'post_author', $post_id );

        $graph[] = ( new Ligase_Type_BlogPosting() )->build();
        $graph[] = ( new Ligase_Type_Person( $author_id ) )->build();
        $graph[] = ( new Ligase_Type_BreadcrumbList() )->build();

        foreach ( $this->get_optional_types() as $type ) {
            $schema = $type->build();
            if ( ! empty( $schema ) ) {
                $graph[] = $schema;
            }
        }

        // Restore original post context
        if ( $original_post ) {
            $GLOBALS['post'] = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
            setup_postdata( $original_post );
        } else {
            wp_reset_postdata();
        }

        $graph = apply_filters( 'ligase_schema_graph', $graph );

        return array_values( array_filter( $graph ) );
    }

    /**
     * Get all optional schema type instances.
     * Each type checks its own enable flag internally.
     */
    private function get_optional_types(): array {
        return [
            new Ligase_Type_FAQPage(),
            new Ligase_Type_HowTo(),
            new Ligase_Type_VideoObject(),
            new Ligase_Type_Review(),
            new Ligase_Type_QAPage(),
            new Ligase_Type_DefinedTerm(),
            new Ligase_Type_ClaimReview(),
            new Ligase_Type_SoftwareApplication(),
            new Ligase_Type_AudioObject(),
            new Ligase_Type_Course(),
            new Ligase_Type_Event(),
        ];
    }
}
