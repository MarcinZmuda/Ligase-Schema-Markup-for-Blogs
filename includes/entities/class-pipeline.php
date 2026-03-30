<?php

defined( 'ABSPATH' ) || exit;

class Ligase_Entity_Pipeline {

    /**
     * @param string $mode 'standard' | 'deep' | 'wikidata'
     */
    public function analyze( int $post_id, string $mode = 'standard' ): array {
        $results = [];

        // Level 1 — always, ~0ms
        $results['native'] = ( new Ligase_Entity_Extractor_Native() )->extract( $post_id );

        // Level 2 — always, ~5ms
        $results['structural'] = ( new Ligase_Entity_Extractor_Structure() )->extract( $post_id );

        // Level 3 — only in deep/wikidata mode, ~20ms
        if ( in_array( $mode, [ 'deep', 'wikidata' ], true ) && class_exists( 'Ligase_Entity_Extractor_NER' ) ) {
            $results['ner'] = ( new Ligase_Entity_Extractor_NER() )->extract_from_post( $post_id );
        }

        // Level 4 — async results from previous Wikidata lookups
        $results['wikidata_suggestions'] = get_post_meta( $post_id, '_ligase_wikidata_suggestions', true ) ?: [];

        return $this->map_to_schema_hints( $results, $post_id );
    }

    private function map_to_schema_hints( array $entities, int $post_id ): array {
        $hints = [];

        // keywords from tags
        if ( ! empty( $entities['native']['keywords'] ) ) {
            $hints['keywords'] = array_column( $entities['native']['keywords'], 'name' );
        }

        // articleSection from first category
        if ( ! empty( $entities['native']['topics'][0] ) ) {
            $hints['articleSection'] = $entities['native']['topics'][0]['name'];
        }

        // about — Wikipedia links in content (ready sameAs)
        if ( ! empty( $entities['structural']['wiki_mentions'] ) ) {
            $hints['about'] = array_map( fn( $l ) => [
                '@type'  => 'Thing',
                'name'   => $l['text'],
                'sameAs' => $l['url'],
            ], $entities['structural']['wiki_mentions'] );
        }

        // VideoObject suggestion
        if ( ! empty( $entities['structural']['youtube_ids'][0] ) ) {
            $hints['_suggest_video'] = $entities['structural']['youtube_ids'][0];
        }

        // FAQ suggestion
        if ( ! empty( $entities['structural']['blocks']['faq'] ) ) {
            $hints['_suggest_faq'] = true;
        }

        // HowTo suggestion
        if ( ! empty( $entities['structural']['blocks']['howto'] ) ) {
            $hints['_suggest_howto'] = true;
        }

        // NER entities
        if ( ! empty( $entities['ner'] ) ) {
            $hints['_ner_entities'] = $entities['ner'];
        }

        // Wikidata suggestions + auto-apply for single-match entities
        if ( ! empty( $entities['wikidata_suggestions'] ) ) {
            $hints['_wikidata'] = $entities['wikidata_suggestions'];

            // Auto-apply: if an entity has exactly 1 Wikidata match with high confidence,
            // add it to sameAs suggestions for automatic linking
            $auto_sameas = [];
            foreach ( $entities['wikidata_suggestions'] as $name => $matches ) {
                if ( is_array( $matches ) && count( $matches ) === 1 ) {
                    $auto_sameas[] = [
                        'name'   => $name,
                        'wikidata_id'  => $matches[0]['id'],
                        'wikidata_url' => $matches[0]['url'],
                        'label'        => $matches[0]['label'],
                    ];
                }
            }
            if ( ! empty( $auto_sameas ) ) {
                $hints['_auto_sameas'] = $auto_sameas;
            }
        }

        // Schedule Wikidata lookup for NER entities that don't have matches yet
        if ( ! empty( $entities['ner'] ) && class_exists( 'Ligase_Wikidata_Lookup' ) ) {
            $names_to_lookup = [];
            $existing = array_keys( $entities['wikidata_suggestions'] ?: [] );
            foreach ( [ 'persons', 'organizations', 'products' ] as $type ) {
                if ( ! empty( $entities['ner'][ $type ] ) ) {
                    foreach ( $entities['ner'][ $type ] as $entity ) {
                        if ( ! in_array( $entity['name'], $existing, true ) && $entity['frequency'] >= 2 ) {
                            $names_to_lookup[] = $entity['name'];
                        }
                    }
                }
            }
            if ( ! empty( $names_to_lookup ) ) {
                ( new Ligase_Wikidata_Lookup() )->schedule( $post_id, array_unique( $names_to_lookup ) );
                $hints['_wikidata_scheduled'] = count( $names_to_lookup );
            }
        }

        return $hints;
    }
}
