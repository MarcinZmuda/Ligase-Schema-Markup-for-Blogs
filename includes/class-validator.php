<?php
/**
 * Ligase Schema Validator
 *
 * Validates generated JSON-LD against Google's requirements.
 * Used by the admin validator tool and Gutenberg sidebar.
 *
 * @package Ligase
 */

defined( 'ABSPATH' ) || exit;

class Ligase_Validator {

    /**
     * Validate schema for a post.
     *
     * @param int $post_id Post ID.
     * @return array{valid: bool, errors: array, warnings: array, json: string}
     */
    public function validate_post( int $post_id ): array {
        $generator = new Ligase_Generator();
        $graph     = $generator->get_graph_for_post( $post_id );

        if ( empty( $graph ) ) {
            return [
                'valid'    => false,
                'errors'   => [ 'Brak wygenerowanej schema dla tego posta.' ],
                'warnings' => [],
                'json'     => '',
                'types'    => [],
            ];
        }

        $payload = [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];

        $json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

        $errors   = [];
        $warnings = [];
        $types    = [];

        foreach ( $graph as $item ) {
            $type = $item['@type'] ?? 'Unknown';
            $types[] = $type;

            // Validate per type
            match ( $type ) {
                'Article', 'BlogPosting', 'NewsArticle' => $this->validate_article( $item, $errors, $warnings ),
                'Person'        => $this->validate_person( $item, $errors, $warnings ),
                'Organization'  => $this->validate_organization( $item, $errors, $warnings ),
                'Review'        => $this->validate_review( $item, $errors, $warnings ),
                'FAQPage'       => $this->validate_faq( $item, $errors, $warnings ),
                'VideoObject'   => $this->validate_video( $item, $errors, $warnings ),
                'Event'         => $this->validate_event( $item, $errors, $warnings ),
                default         => null,
            };
        }

        // Validate JSON structure
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $errors[] = 'Blad kodowania JSON: ' . json_last_error_msg();
        }

        // Check @graph has @id references
        $has_ids = 0;
        foreach ( $graph as $item ) {
            if ( ! empty( $item['@id'] ) ) {
                $has_ids++;
            }
        }
        if ( $has_ids < 2 ) {
            $warnings[] = 'Mniej niz 2 encje maja @id — entity graph moze byc niekompletny.';
        }

        return [
            'valid'    => empty( $errors ),
            'errors'   => $errors,
            'warnings' => $warnings,
            'json'     => $json ?: '',
            'types'    => array_unique( $types ),
        ];
    }

    private function validate_article( array $s, array &$errors, array &$warnings ): void {
        if ( empty( $s['headline'] ) ) {
            $errors[] = 'Article: brak headline (wymagane).';
        } elseif ( mb_strlen( $s['headline'] ) > 110 ) {
            $warnings[] = 'Article: headline dluzszy niz 110 znakow (' . mb_strlen( $s['headline'] ) . ').';
        }

        if ( empty( $s['datePublished'] ) ) {
            $errors[] = 'Article: brak datePublished (wymagane).';
        }

        if ( empty( $s['author'] ) ) {
            $errors[] = 'Article: brak author (wymagane).';
        }

        if ( empty( $s['image'] ) ) {
            $errors[] = 'Article: brak image (wymagane dla rich results).';
        }

        if ( empty( $s['publisher'] ) ) {
            $warnings[] = 'Article: brak publisher.';
        }

        if ( empty( $s['dateModified'] ) ) {
            $warnings[] = 'Article: brak dateModified.';
        }

        if ( empty( $s['description'] ) ) {
            $warnings[] = 'Article: brak description.';
        }

        if ( empty( $s['speakable'] ) ) {
            $warnings[] = 'Article: brak speakable — zmniejsza szanse na cytowanie przez AI.';
        }
    }

    private function validate_person( array $s, array &$errors, array &$warnings ): void {
        if ( empty( $s['name'] ) ) {
            $errors[] = 'Person: brak name.';
        }

        if ( empty( $s['sameAs'] ) ) {
            $warnings[] = 'Person: brak sameAs — dodaj LinkedIn/Wikidata dla E-E-A-T.';
        }

        if ( empty( $s['knowsAbout'] ) ) {
            $warnings[] = 'Person: brak knowsAbout — wazne dla AI search.';
        }

        if ( empty( $s['jobTitle'] ) ) {
            $warnings[] = 'Person: brak jobTitle.';
        }
    }

    private function validate_organization( array $s, array &$errors, array &$warnings ): void {
        if ( empty( $s['name'] ) ) {
            $errors[] = 'Organization: brak name.';
        }

        if ( empty( $s['logo'] ) ) {
            $warnings[] = 'Organization: brak logo (wymagane dla Article rich results).';
        }

        if ( empty( $s['sameAs'] ) ) {
            $warnings[] = 'Organization: brak sameAs — dodaj Wikidata/LinkedIn.';
        }
    }

    private function validate_review( array $s, array &$errors, array &$warnings ): void {
        if ( empty( $s['reviewRating'] ) ) {
            $errors[] = 'Review: brak reviewRating.';
        }

        if ( empty( $s['itemReviewed'] ) ) {
            $warnings[] = 'Review: brak itemReviewed — Google moze odrzucic rich result.';
        }

        if ( empty( $s['reviewBody'] ) ) {
            $warnings[] = 'Review: brak reviewBody.';
        }
    }

    private function validate_faq( array $s, array &$errors, array &$warnings ): void {
        $entities = $s['mainEntity'] ?? [];
        if ( count( $entities ) < 2 ) {
            $errors[] = 'FAQPage: minimum 2 pytania (znaleziono ' . count( $entities ) . ').';
        }

        $warnings[] = 'FAQPage: rich results ograniczone do gov/health od 2024. Schema nadal dziala dla AI.';
    }

    private function validate_video( array $s, array &$errors, array &$warnings ): void {
        if ( empty( $s['name'] ) ) {
            $errors[] = 'VideoObject: brak name.';
        }

        if ( empty( $s['thumbnailUrl'] ) ) {
            $errors[] = 'VideoObject: brak thumbnailUrl (wymagane).';
        }

        if ( empty( $s['uploadDate'] ) ) {
            $errors[] = 'VideoObject: brak uploadDate (wymagane).';
        }

        if ( empty( $s['duration'] ) ) {
            $warnings[] = 'VideoObject: brak duration.';
        }
    }

    private function validate_event( array $s, array &$errors, array &$warnings ): void {
        if ( empty( $s['name'] ) ) {
            $errors[] = 'Event: brak name (wymagane).';
        }

        if ( empty( $s['startDate'] ) ) {
            $errors[] = 'Event: brak startDate (wymagane).';
        }

        if ( empty( $s['location'] ) ) {
            $warnings[] = 'Event: brak location.';
        }
    }
}
