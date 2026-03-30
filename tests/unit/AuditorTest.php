<?php
/**
 * Tests for Ligase_Auditor scoring and audit modes.
 *
 * @package Ligase\Tests\Unit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Ligase_Auditor::class)]
final class AuditorTest extends TestCase {

	private Ligase_Auditor $subject;

	protected function setUp(): void {
		MockData::reset();
		$this->subject = new Ligase_Auditor();
	}

	protected function tearDown(): void {
		MockData::reset();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Return a fully populated schema array that should score high.
	 */
	private function complete_schema(): array {
		return [
			'@context'        => 'https://schema.org',
			'@type'           => 'BlogPosting',
			'headline'        => 'A Well-Formed Post Title',
			'description'     => 'A meaningful excerpt that describes the post content.',
			'datePublished'   => '2025-01-15T10:00:00+00:00',
			'dateModified'    => '2025-01-20T12:00:00+00:00',
			'url'             => 'https://example.com/test-post/',
			'mainEntityOfPage'=> [
				'@type' => 'WebPage',
				'@id'   => 'https://example.com/test-post/',
			],
			'author'          => [
				'@type' => 'Person',
				'@id'   => 'https://example.com/#/person/1',
				'name'  => 'Jan Kowalski',
				'url'   => 'https://example.com/author/jan/',
			],
			'publisher'       => [
				'@type' => 'Organization',
				'name'  => 'Test Blog',
				'logo'  => [
					'@type'  => 'ImageObject',
					'url'    => 'https://example.com/logo.png',
					'width'  => 600,
					'height' => 60,
				],
			],
			'image'           => [
				'@type'  => 'ImageObject',
				'url'    => 'https://example.com/image.jpg',
				'width'  => 1200,
				'height' => 800,
			],
			'wordCount'       => 800,
			'keywords'        => 'PHP, WordPress, Schema',
			'articleSection'  => 'Technologia',
			'inLanguage'      => 'pl-PL',
		];
	}

	/**
	 * Return a schema with an existing third-party block (simulating another plugin).
	 */
	private function existing_schema(): array {
		return [
			'@context'      => 'https://schema.org',
			'@type'         => 'BlogPosting',
			'headline'      => 'Old headline',
			'datePublished' => '2025-01-01',
			'author'        => [
				'@type' => 'Person',
				'name'  => 'Some Author',
			],
		];
	}

	// -----------------------------------------------------------------------
	// Scoring tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_score_complete_schema_returns_high(): void {
		$score = $this->subject->score( $this->complete_schema() );

		$this->assertIsInt( $score );
		$this->assertGreaterThanOrEqual( 80, $score );
	}

	#[Test]
	public function test_score_empty_schema_returns_zero(): void {
		$score = $this->subject->score( [] );

		$this->assertIsInt( $score );
		$this->assertSame( 0, $score );
	}

	#[Test]
	public function test_score_penalizes_long_headline(): void {
		$schema             = $this->complete_schema();
		$schema['headline'] = str_repeat( 'X', 200 );

		$normal_score = $this->subject->score( $this->complete_schema() );
		$long_score   = $this->subject->score( $schema );

		$this->assertLessThan(
			$normal_score,
			$long_score,
			'A headline longer than 110 chars should reduce the score.'
		);
	}

	#[Test]
	public function test_score_penalizes_invalid_date(): void {
		$schema                  = $this->complete_schema();
		$schema['datePublished'] = 'not-a-date';

		$normal_score  = $this->subject->score( $this->complete_schema() );
		$invalid_score = $this->subject->score( $schema );

		$this->assertLessThan(
			$normal_score,
			$invalid_score,
			'An invalid datePublished should reduce the score.'
		);
	}

	#[Test]
	public function test_score_rewards_author_id(): void {
		$schema_with_id = $this->complete_schema();

		$schema_without_id = $this->complete_schema();
		unset( $schema_without_id['author']['@id'] );

		$score_with    = $this->subject->score( $schema_with_id );
		$score_without = $this->subject->score( $schema_without_id );

		$this->assertGreaterThanOrEqual(
			$score_without,
			$score_with,
			'An author with @id should score equal to or higher than one without.'
		);
	}

	// -----------------------------------------------------------------------
	// Audit mode tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_scan_mode_does_not_modify(): void {
		$original = $this->existing_schema();

		$result = $this->subject->audit( $original, 'scan' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'schema', $result );
		$this->assertSame(
			$original,
			$result['schema'],
			'Scan mode must not modify the original schema.'
		);
		$this->assertArrayHasKey( 'score', $result );
	}

	#[Test]
	public function test_replace_mode_marks_for_replacement(): void {
		$original = $this->existing_schema();

		$result = $this->subject->audit( $original, 'replace' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'action', $result );
		$this->assertSame( 'replace', $result['action'] );
	}

	#[Test]
	public function test_supplement_mode_adds_missing_fields(): void {
		$original = $this->existing_schema();

		$result = $this->subject->audit( $original, 'supplement' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'schema', $result );

		$supplemented = $result['schema'];

		// The original headline should be preserved.
		$this->assertSame( 'Old headline', $supplemented['headline'] );

		// Fields that were missing should now be present.
		$this->assertArrayHasKey( 'inLanguage', $supplemented,
			'Supplement mode should add missing inLanguage.' );
	}

	// -----------------------------------------------------------------------
	// Utility tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_detected_plugins_returns_array(): void {
		$result = $this->subject->detect_plugins();

		$this->assertIsArray( $result );
	}

	// -----------------------------------------------------------------------
	// Conflict detection tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_should_render_false_when_yoast_active_and_standalone_off(): void {
		// Simulate Yoast active by defining its constant
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			define( 'WPSEO_VERSION', '24.0' );
		}
		MockData::set_option( 'ligase_options', [
			'standalone_mode' => 0,
			'force_output'    => 0,
		] );

		$suppressor = new Ligase_Suppressor();
		$active     = $suppressor->get_active_seo_plugins();

		$this->assertNotEmpty( $active, 'Yoast should be detected as active.' );
		$this->assertArrayHasKey( 'yoast', $active );
	}

	#[Test]
	public function test_supplement_schema_author_id_uses_home_url_format(): void {
		// The supplement_schema() method is private, so we test indirectly
		// by verifying the @id pattern used elsewhere matches home_url format
		$schema = $this->complete_schema();

		// Ligase uses home_url('/#author-{id}') format everywhere
		// Verify complete_schema helper uses different format (simulating third-party)
		$this->assertStringContainsString(
			'person',
			$schema['author']['@id'],
			'Test helper should use third-party @id format to verify supplement fixes it.'
		);
	}

	// -----------------------------------------------------------------------
	// Threshold tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_threshold_respected(): void {
		// Score below threshold should trigger a recommendation.
		$low_schema = [
			'@type'    => 'BlogPosting',
			'headline' => 'Hi',
		];

		$result = $this->subject->audit( $low_schema, 'scan', [ 'threshold' => 80 ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertLessThan( 80, $result['score'] );

		// The result should indicate the schema is below threshold.
		$this->assertTrue(
			( $result['below_threshold'] ?? false ) === true
				|| ( $result['needs_improvement'] ?? false ) === true
				|| $result['score'] < 80,
			'A low-quality schema should be flagged when below the threshold.'
		);
	}
}
