<?php
/**
 * Tests for Ligase_Score (AI Search Readiness Score).
 *
 * @package BlogSchemaPro\Tests\Unit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Ligase_Score::class)]
final class ScoreTest extends TestCase {

	private Ligase_Score $subject;

	protected function setUp(): void {
		MockData::reset();
		$this->subject = new Ligase_Score();
	}

	protected function tearDown(): void {
		MockData::reset();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Build a complete site-level data set that should yield a perfect score.
	 */
	private function perfect_site_data(): array {
		return [
			'site_name'        => 'Test Blog',
			'site_description' => 'A blog about technology and programming.',
			'site_url'         => 'https://example.com',
			'site_icon'        => 'https://example.com/icon.png',
			'site_logo'        => 'https://example.com/logo.png',
			'language'         => 'pl_PL',
			'organization'     => 'Test Org',
			'social_profiles'  => [ 'https://twitter.com/test', 'https://facebook.com/test' ],
			'search_action'    => true,
			'breadcrumbs'      => true,
		];
	}

	/**
	 * Build a complete post-level data set.
	 */
	private function complete_post_data(): array {
		return [
			'post_id'       => 42,
			'title'         => 'A Properly Sized Post Title',
			'excerpt'       => 'This is a well-written excerpt for the post.',
			'content'       => 'Full post content with enough words for readability.',
			'date'          => '2025-01-15T10:00:00+00:00',
			'modified'      => '2025-01-20T12:00:00+00:00',
			'url'           => 'https://example.com/test-post/',
			'author_id'     => 1,
			'author_name'   => 'Jan Kowalski',
			'image_url'     => 'https://example.com/image.jpg',
			'image_width'   => 1200,
			'image_height'  => 800,
			'categories'    => [ 'Technologia' ],
			'tags'          => [ 'PHP', 'WordPress' ],
			'word_count'    => 800,
		];
	}

	/**
	 * Build a complete author data set.
	 */
	private function complete_author_data(): array {
		return [
			'user_id'      => 1,
			'display_name' => 'Jan Kowalski',
			'description'  => 'Experienced PHP developer and technical writer.',
			'user_url'     => 'https://jankowalski.com',
			'avatar_url'   => 'https://example.com/avatar.jpg',
			'social_links' => [ 'https://twitter.com/jankowalski' ],
			'same_as'      => [ 'https://linkedin.com/in/jankowalski' ],
		];
	}

	// -----------------------------------------------------------------------
	// Site score tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_perfect_site_score_returns_100(): void {
		$result = $this->subject->calculate_site_score( $this->perfect_site_data() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertSame( 100, $result['score'] );
	}

	#[Test]
	public function test_empty_site_score_returns_0(): void {
		$result = $this->subject->calculate_site_score( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertSame( 0, $result['score'] );
	}

	#[Test]
	public function test_partial_site_score(): void {
		$data = [
			'site_name' => 'Test Blog',
			'site_url'  => 'https://example.com',
			'language'  => 'pl_PL',
		];

		$result = $this->subject->calculate_site_score( $data );

		$this->assertIsArray( $result );
		$this->assertGreaterThan( 0, $result['score'] );
		$this->assertLessThan( 100, $result['score'] );
	}

	// -----------------------------------------------------------------------
	// Post score tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_post_score_with_all_fields(): void {
		$result = $this->subject->calculate_post_score( $this->complete_post_data() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertGreaterThanOrEqual( 90, $result['score'] );
	}

	#[Test]
	public function test_post_score_missing_image(): void {
		$data = $this->complete_post_data();
		unset( $data['image_url'], $data['image_width'], $data['image_height'] );

		$result = $this->subject->calculate_post_score( $data );

		$this->assertIsArray( $result );
		$full_result = $this->subject->calculate_post_score( $this->complete_post_data() );
		$this->assertLessThan( $full_result['score'], $result['score'] );
	}

	#[Test]
	public function test_post_score_headline_too_long(): void {
		$data          = $this->complete_post_data();
		$data['title'] = str_repeat( 'A', 150 );

		$result = $this->subject->calculate_post_score( $data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'checks', $result );

		// Find the headline check and ensure it failed.
		$headline_check = array_filter(
			$result['checks'],
			fn( array $c ) => str_contains( strtolower( $c['id'] ?? $c['key'] ?? '' ), 'headline' )
				|| str_contains( strtolower( $c['label'] ?? '' ), 'headline' )
				|| str_contains( strtolower( $c['label'] ?? '' ), 'tytu' ), // Polish: tytuł
		);
		$this->assertNotEmpty( $headline_check, 'A headline-related check should be present.' );
	}

	// -----------------------------------------------------------------------
	// Author score tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_author_score_complete_profile(): void {
		$result = $this->subject->calculate_author_score( $this->complete_author_data() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'score', $result );
		$this->assertGreaterThanOrEqual( 90, $result['score'] );
	}

	#[Test]
	public function test_author_score_minimal_profile(): void {
		$data = [
			'user_id'      => 1,
			'display_name' => 'Jan',
		];

		$result = $this->subject->calculate_author_score( $data );

		$this->assertIsArray( $result );
		$this->assertLessThan( 50, $result['score'] );
	}

	// -----------------------------------------------------------------------
	// Check labels and recommendations
	// -----------------------------------------------------------------------

	#[Test]
	public function test_checks_contain_polish_labels(): void {
		$result = $this->subject->calculate_site_score( $this->perfect_site_data() );

		$this->assertArrayHasKey( 'checks', $result );
		$this->assertIsArray( $result['checks'] );
		$this->assertNotEmpty( $result['checks'] );

		// At least some labels should contain Polish diacritics or Polish words.
		$labels = array_column( $result['checks'], 'label' );
		$all_labels = implode( ' ', $labels );

		// Polish-language checks should include words like "Nazwa", "Opis", "Logo", etc.
		$has_polish = preg_match( '/[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]{3,}/u', $all_labels );
		$this->assertGreaterThan( 0, $has_polish, 'Check labels should be in Polish.' );
	}

	#[Test]
	public function test_recommendations_for_failed_checks(): void {
		$result = $this->subject->calculate_site_score( [] );

		$this->assertArrayHasKey( 'checks', $result );

		$failed = array_filter(
			$result['checks'],
			fn( array $c ) => ( $c['passed'] ?? $c['status'] ?? false ) === false
				|| ( $c['status'] ?? '' ) === 'fail',
		);

		$this->assertNotEmpty( $failed, 'Empty site data should produce failed checks.' );

		foreach ( $failed as $check ) {
			$this->assertArrayHasKey( 'recommendation', $check,
				'Each failed check should include a recommendation.' );
			$this->assertNotEmpty( $check['recommendation'] );
		}
	}
}
