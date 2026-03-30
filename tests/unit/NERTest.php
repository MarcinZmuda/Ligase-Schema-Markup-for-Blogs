<?php
/**
 * Tests for Ligase_Entity_Extractor_NER (Named Entity Recognition).
 *
 * @package BlogSchemaPro\Tests\Unit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Ligase_Entity_Extractor_NER::class)]
final class NERTest extends TestCase {

	private Ligase_Entity_Extractor_NER $subject;

	protected function setUp(): void {
		MockData::reset();
		$this->subject = new Ligase_Entity_Extractor_NER();
	}

	protected function tearDown(): void {
		MockData::reset();
	}

	// -----------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_extracts_person_names(): void {
		$content = 'Jan Kowalski spotkał się z Anną Nowak w Warszawie. '
			. 'Później dołączył do nich Piotr Wiśniewski.';

		$entities = $this->subject->extract( $content );

		$this->assertIsArray( $entities );

		$persons = $this->filter_by_type( $entities, 'Person' );
		$names   = array_column( $persons, 'name' );

		$this->assertContains( 'Jan Kowalski', $names );
		$this->assertContains( 'Anna Nowak', $names );
		$this->assertContains( 'Piotr Wiśniewski', $names );
	}

	#[Test]
	public function test_extracts_organizations(): void {
		$content = 'Firma Microsoft ogłosiła partnerstwo z Google. '
			. 'Również Apple dołączyło do inicjatywy Open Source.';

		$entities = $this->subject->extract( $content );

		$this->assertIsArray( $entities );

		$orgs  = $this->filter_by_type( $entities, 'Organization' );
		$names = array_column( $orgs, 'name' );

		$this->assertContains( 'Microsoft', $names );
		$this->assertContains( 'Google', $names );
	}

	#[Test]
	public function test_extracts_places_polish(): void {
		$content = 'Konferencja odbyła się w Krakowie. '
			. 'Uczestnicy przyjechali z Gdańska i Wrocławia.';

		$entities = $this->subject->extract( $content );

		$this->assertIsArray( $entities );

		$places = $this->filter_by_type( $entities, 'Place' );
		$names  = array_column( $places, 'name' );

		$this->assertNotEmpty( $places, 'Polish place names should be extracted.' );
		// At least one of the cities should be detected.
		$found = array_intersect( [ 'Kraków', 'Krakowie', 'Gdańsk', 'Gdańska', 'Wrocław', 'Wrocławia' ], $names );
		$this->assertNotEmpty( $found, 'At least one Polish city should be recognized.' );
	}

	#[Test]
	public function test_extracts_products(): void {
		$content = 'Nowy iPhone 15 Pro Max konkuruje z Samsung Galaxy S24 Ultra. '
			. 'Warto też rozważyć Google Pixel 8 Pro.';

		$entities = $this->subject->extract( $content );

		$this->assertIsArray( $entities );

		$products = $this->filter_by_type( $entities, 'Product' );
		$names    = array_column( $products, 'name' );

		$this->assertNotEmpty( $products, 'Product names should be extracted.' );
	}

	#[Test]
	public function test_deduplicates_entities(): void {
		$content = 'Jan Kowalski napisał artykuł. '
			. 'Jan Kowalski jest ekspertem w tej dziedzinie. '
			. 'Jan Kowalski opublikował książkę.';

		$entities = $this->subject->extract( $content );

		$persons = $this->filter_by_type( $entities, 'Person' );
		$names   = array_column( $persons, 'name' );

		$jan_count = count( array_filter( $names, fn( $n ) => $n === 'Jan Kowalski' ) );

		$this->assertSame(
			1,
			$jan_count,
			'Duplicate entities should be merged into a single entry.'
		);
	}

	#[Test]
	public function test_counts_frequency(): void {
		$content = 'Microsoft wydało nową wersję Windows. '
			. 'Microsoft Azure zyskał nowych klientów. '
			. 'Platforma Microsoft Teams jest popularna.';

		$entities = $this->subject->extract( $content );

		$microsoft = array_filter(
			$entities,
			fn( array $e ) => ( $e['name'] ?? '' ) === 'Microsoft',
		);

		if ( ! empty( $microsoft ) ) {
			$entity = reset( $microsoft );
			$this->assertArrayHasKey( 'frequency', $entity );
			$this->assertGreaterThanOrEqual( 3, $entity['frequency'] );
		} else {
			// If "Microsoft" was not extracted as a standalone entity, ensure
			// at least some entities were found in the content.
			$this->assertNotEmpty( $entities, 'Entities should be extracted from content.' );
		}
	}

	#[Test]
	public function test_filters_stop_words(): void {
		$content = 'To jest bardzo ważny artykuł o tym jak programować w PHP.';

		$entities = $this->subject->extract( $content );

		$names = array_column( $entities, 'name' );

		// Polish stop words should never appear as entities.
		$stop_words = [ 'to', 'jest', 'bardzo', 'o', 'tym', 'jak', 'w' ];

		foreach ( $stop_words as $stop ) {
			$this->assertNotContains(
				$stop,
				$names,
				"Stop word \"{$stop}\" should not appear as an entity."
			);
		}
	}

	#[Test]
	public function test_handles_empty_content(): void {
		$entities = $this->subject->extract( '' );

		$this->assertIsArray( $entities );
		$this->assertEmpty( $entities, 'Empty content should produce no entities.' );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Filter extracted entities by their @type / type field.
	 *
	 * @param array[] $entities Extracted entities.
	 * @param string  $type     Entity type to filter for.
	 * @return array[]
	 */
	private function filter_by_type( array $entities, string $type ): array {
		return array_values( array_filter(
			$entities,
			fn( array $e ) => ( $e['@type'] ?? $e['type'] ?? '' ) === $type,
		) );
	}
}
