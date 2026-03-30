<?php

defined( 'ABSPATH' ) || exit;

class Ligase_Type_VideoObject {

    public function build(): ?array {
        if ( ! is_single() ) {
            return null;
        }

        $post_id = get_the_ID();

        $meta = get_post_meta( $post_id, '_ligase_video', true );
        if ( ! empty( $meta ) && is_array( $meta ) && ! empty( $meta['embed_url'] ) ) {
            return $this->build_from_meta( $meta, $post_id );
        }

        $content    = get_the_content();
        $youtube_id = $this->extract_youtube_id( $content ?: '' );
        if ( $youtube_id ) {
            return $this->build_youtube( $youtube_id, $post_id );
        }

        return null;
    }

    private function extract_youtube_id( string $content ): ?string {
        if ( preg_match(
            '~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([\w-]{11})~',
            $content,
            $m
        ) ) {
            return preg_match( '/^[\w-]{11}$/', $m[1] ) ? $m[1] : null;
        }
        return null;
    }

    private function build_youtube( string $vid, int $post_id ): array {
        $schema = [
            '@type'        => 'VideoObject',
            '@id'          => esc_url( get_permalink( $post_id ) ) . '#video',
            'name'         => esc_html( get_the_title( $post_id ) ),
            'description'  => esc_html( wp_strip_all_tags( get_the_excerpt( $post_id ) ) ),
            'inLanguage'   => str_replace( '_', '-', get_locale() ),
            'thumbnailUrl' => "https://img.youtube.com/vi/{$vid}/maxresdefault.jpg",
            'uploadDate'   => get_the_date( 'c', $post_id ),
            'embedUrl'     => "https://www.youtube.com/embed/{$vid}",
            'contentUrl'   => "https://www.youtube.com/watch?v={$vid}",
        ];

        // Try to get duration from post meta (set by entity pipeline or manually)
        $duration = get_post_meta( $post_id, '_ligase_video_duration', true );
        if ( $duration && preg_match( '/^P(?:\d+[YMWD])*(?:T(?:\d+[HMS])*)?$/', $duration ) ) {
            $schema['duration'] = $duration;
        }

        return $schema;
    }

    private function build_from_meta( array $meta, int $post_id ): array {
        $schema = [
            '@type'        => 'VideoObject',
            'name'         => esc_html( $meta['name'] ?? get_the_title( $post_id ) ),
            'thumbnailUrl' => esc_url( $meta['thumbnail'] ?? '' ),
            'uploadDate'   => esc_html( $meta['upload_date'] ?? get_the_date( 'c', $post_id ) ),
            'embedUrl'     => esc_url( $meta['embed_url'] ),
        ];
        if ( ! empty( $meta['duration'] ) && preg_match( '/^P(?:\d+[YMWD])*(?:T(?:\d+[HMS])*)?$/', $meta['duration'] ) ) {
            $schema['duration'] = esc_html( $meta['duration'] );
        }
        return $schema;
    }
}
