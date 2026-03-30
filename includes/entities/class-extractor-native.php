<?php

defined( 'ABSPATH' ) || exit;

/**
 * Level 1: WordPress native API extraction (~0ms).
 * Extracts tags, categories, featured image, author from WP database.
 */
class Ligase_Entity_Extractor_Native {

    public function extract( int $post_id ): array {
        $result = [
            'keywords'   => [],
            'topics'     => [],
            'author'     => null,
            'image'      => null,
        ];

        // Tags → keywords
        $tags = wp_get_post_tags( $post_id, [ 'fields' => 'all' ] );
        if ( ! empty( $tags ) && is_array( $tags ) ) {
            foreach ( $tags as $tag ) {
                $result['keywords'][] = [
                    'name'   => $tag->name,
                    'slug'   => $tag->slug,
                    'source' => 'tag',
                ];
            }
        }

        // Categories → topics
        $cats = get_the_category( $post_id );
        if ( ! empty( $cats ) && is_array( $cats ) ) {
            foreach ( $cats as $cat ) {
                $result['topics'][] = [
                    'name'   => $cat->name,
                    'slug'   => $cat->slug,
                    'source' => 'category',
                ];
            }
        }

        // Author
        $author_id = (int) get_post_field( 'post_author', $post_id );
        if ( $author_id ) {
            $user = get_userdata( $author_id );
            if ( $user ) {
                $result['author'] = [
                    'id'           => $author_id,
                    'display_name' => $user->display_name,
                    'job_title'    => get_user_meta( $author_id, 'ligase_job_title', true ) ?: null,
                    'knows_about'  => get_user_meta( $author_id, 'ligase_knows_about', true ) ?: null,
                ];
            }
        }

        // Featured image
        $tid = get_post_thumbnail_id( $post_id );
        if ( $tid ) {
            $img = wp_get_attachment_image_src( $tid, 'full' );
            if ( $img && is_array( $img ) ) {
                $result['image'] = [
                    'url'    => $img[0],
                    'width'  => (int) $img[1],
                    'height' => (int) $img[2],
                ];
            }
        }

        return $result;
    }
}
