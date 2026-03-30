<?php

defined( 'ABSPATH' ) || exit;

/**
 * Level 2: Content structure analysis (~5ms).
 * Analyzes HTML blocks, detects YouTube embeds, FAQ sections, Wikipedia links.
 */
class Ligase_Entity_Extractor_Structure {

    public function extract( int $post_id ): array {
        $content = get_the_content( null, false, $post_id ) ?: '';

        return [
            'youtube_ids'    => $this->find_youtube_ids( $content ),
            'wiki_mentions'  => $this->find_wikipedia_links( $content ),
            'blocks'         => $this->analyze_blocks( $post_id, $content ),
            'headings'       => $this->extract_headings( $content ),
            'external_links' => $this->find_external_links( $content ),
        ];
    }

    private function find_youtube_ids( string $content ): array {
        $ids = [];
        if ( preg_match_all(
            '~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([\w-]{11})~',
            $content,
            $matches
        ) ) {
            $ids = array_unique( $matches[1] );
        }
        return array_values( $ids );
    }

    private function find_wikipedia_links( string $content ): array {
        $links = [];
        if ( preg_match_all(
            '/<a[^>]+href=["\']?(https?:\/\/[\w.]*wikipedia\.org\/wiki\/[^"\'>\s]+)["\']?[^>]*>(.*?)<\/a>/si',
            $content,
            $matches,
            PREG_SET_ORDER
        ) ) {
            foreach ( $matches as $m ) {
                $links[] = [
                    'url'  => $m[1],
                    'text' => wp_strip_all_tags( $m[2] ),
                ];
            }
        }
        return $links;
    }

    private function analyze_blocks( int $post_id, string $content ): array {
        $blocks = [
            'faq'   => false,
            'howto' => false,
            'video' => false,
            'table' => false,
            'list'  => false,
        ];

        // Check for FAQ patterns (H2/H3 with question marks or Q&A patterns)
        if ( preg_match_all( '/<h[23][^>]*>[^<]*\?<\/h[23]>/i', $content, $faq_matches ) ) {
            if ( count( $faq_matches[0] ) >= 2 ) {
                $blocks['faq'] = true;
            }
        }

        // Check for HowTo patterns (ordered lists with step-like content)
        if ( preg_match( '/<ol[^>]*>.*?<li.*?<\/ol>/si', $content ) ) {
            // Check if preceded by "how to" / "jak" heading
            if ( preg_match( '/<h[23][^>]*>[^<]*(jak|how to|krok|step|instrukcja|poradnik)[^<]*<\/h[23]>/i', $content ) ) {
                $blocks['howto'] = true;
            }
        }

        // YouTube/video embeds
        if ( ! empty( $this->find_youtube_ids( $content ) ) || preg_match( '/<iframe[^>]+(?:youtube|vimeo|dailymotion)/i', $content ) ) {
            $blocks['video'] = true;
        }

        // Tables
        if ( preg_match( '/<table[^>]*>/i', $content ) ) {
            $blocks['table'] = true;
        }

        // Unordered lists
        if ( preg_match( '/<ul[^>]*>.*?<li/si', $content ) ) {
            $blocks['list'] = true;
        }

        return $blocks;
    }

    private function extract_headings( string $content ): array {
        $headings = [];
        if ( preg_match_all( '/<(h[1-6])[^>]*>(.*?)<\/\1>/si', $content, $matches, PREG_SET_ORDER ) ) {
            foreach ( $matches as $m ) {
                $headings[] = [
                    'level' => (int) substr( $m[1], 1 ),
                    'text'  => wp_strip_all_tags( $m[2] ),
                ];
            }
        }
        return $headings;
    }

    private function find_external_links( string $content ): array {
        $links = [];
        $home  = home_url();
        if ( preg_match_all( '/<a[^>]+href=["\']?(https?:\/\/[^"\'>\s]+)/i', $content, $matches ) ) {
            foreach ( $matches[1] as $url ) {
                if ( ! str_starts_with( $url, $home ) ) {
                    $links[] = $url;
                }
            }
        }
        return array_unique( $links );
    }
}
