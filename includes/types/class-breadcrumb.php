<?php

defined( 'ABSPATH' ) || exit;

class Ligase_Type_BreadcrumbList {

    public function build(): ?array {
        if ( ! is_single() && ! is_page() && ! is_category() ) {
            return null;
        }

        $items = [];
        $pos   = 1;

        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => esc_html( get_bloginfo( 'name' ) ),
            'item'     => esc_url( home_url( '/' ) ),
        ];

        if ( is_single() && get_post_type() === 'post' ) {
            $cats = get_the_category();
            if ( ! empty( $cats ) && is_array( $cats ) ) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => esc_html( $cats[0]->name ),
                    'item'     => esc_url( get_category_link( $cats[0]->term_id ) ),
                ];
            }
        }

        // Nested pages: add ancestor hierarchy
        if ( is_page() ) {
            $ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
            foreach ( $ancestors as $ancestor_id ) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => esc_html( get_the_title( $ancestor_id ) ),
                    'item'     => esc_url( get_permalink( $ancestor_id ) ),
                ];
            }
        }

        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos,
            'name'     => esc_html( get_the_title() ),
        ];

        return [
            '@type'           => 'BreadcrumbList',
            '@id'             => esc_url( get_permalink() ) . '#breadcrumb',
            'itemListElement' => $items,
        ];
    }
}
