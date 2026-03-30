<?php
/**
 * Tests for Ligase_Type_BlogPosting.
 *
 * @package Ligase\Tests\Unit
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Ligase_Type_BlogPosting::class)]
final class BlogPostingTest extends TestCase {

	private Ligase_Type_BlogPosting $subject;

	protected function setUp(): void {
		MockData::reset();
		$this->subject = new Ligase_Type_BlogPosting();
	}

	protected function tearDown(): void {
		MockData::reset();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Configure MockData for a standard single-post context.
	 */
	private function setup_single_post( array $overrides = [] ): void {
		MockData::set( 'is_single', true );
		MockData::set( 'the_id', $overrides['id'] ?? 42 );
		MockData::set( 'the_title', $overrides['title'] ?? 'Test Post Title' );
		MockData::set( 'the_date', $overrides['date'] ?? '2025-01-15' );
		MockData::set( 'the_modified_date', $overrides['modified'] ?? '2025-01-20' );
		MockData::set( 'permalink', $overrides['permalink'] ?? 'https://example.com/test-post/' );
		MockData::set( 'the_excerpt', $overrides['excerpt'] ?? 'A short excerpt.' );
		MockData::set( 'the_content', $overrides['content'] ?? '<p>Full post content.</p>' );
		MockData::set( 'post_field_post_author', $overrides['author_id'] ?? '1' );
		MockData::set( 'post_thumbnail_id', $overrides['thumbnail_id'] ?? 100 );
		MockData::set( 'attachment_image_src', $overrides['image_src'] ?? [
			'https://example.com/image.jpg',
			1200,
			800,
		] );
		MockData::set( 'userdata', (object) [
			'ID'           => 1,
			'display_name' => $overrides['author_name'] ?? 'Jan Kowalski',
			'user_url'     => 'https://example.com',
			'description'  => 'Author bio.',
		] );
		MockData::set( 'post_tags', $overrides['tags'] ?? [] );
		MockData::set( 'the_category', $overrides['categories'] ?? [] );

		// Default plugin options.
		MockData::set_option( 'ligase_options', array_merge( [
			'schema_type' => 'BlogPosting',
		], $overrides['options'] ?? [] ) );
	}

	// -----------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------

	#[Test]
	public function test_build_returns_null_when_not_single(): void {
		MockData::set( 'is_single', false );

		$result = $this->subject->build();

		$this->assertNull( $result, 'build() should return null when not on a single post.' );
	}

	#[Test]
	public function test_build_returns_valid_blogposting_schema(): void {
		$this->setup_single_post();

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertSame( 'https://schema.org', $schema['@context'] ?? '' );
		$this->assertSame( 'BlogPosting', $schema['@type'] ?? '' );
		$this->assertArrayHasKey( 'headline', $schema );
		$this->assertArrayHasKey( 'datePublished', $schema );
		$this->assertArrayHasKey( 'dateModified', $schema );
		$this->assertArrayHasKey( 'url', $schema );
		$this->assertArrayHasKey( 'author', $schema );
		$this->assertArrayHasKey( 'mainEntityOfPage', $schema );
	}

	#[Test]
	public function test_headline_truncated_to_110_chars(): void {
		$long_title = str_repeat( 'A', 150 );
		$this->setup_single_post( [ 'title' => $long_title ] );

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertLessThanOrEqual(
			110,
			mb_strlen( $schema['headline'] ),
			'Headline must be truncated to at most 110 characters.'
		);
	}

	#[Test]
	public function test_image_excluded_when_below_1200px(): void {
		$this->setup_single_post( [
			'image_src' => [ 'https://example.com/small.jpg', 400, 300 ],
		] );

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertArrayNotHasKey(
			'image',
			$schema,
			'Images narrower than 696px should be excluded from the schema.'
		);
	}

	#[Test]
	public function test_image_included_at_696px(): void {
		$this->setup_single_post( [
			'image_src' => [ 'https://example.com/ok.jpg', 696, 464 ],
		] );

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey(
			'image',
			$schema,
			'Images at exactly 696px width should be included.'
		);
	}

	#[Test]
	public function test_image_multiple_ratios_at_1200px(): void {
		$this->setup_single_post( [
			'image_src' => [ 'https://example.com/big.jpg', 1200, 900 ],
		] );

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'image', $schema );
		$this->assertGreaterThanOrEqual(
			2,
			count( $schema['image'] ),
			'Images at 1200x900 should produce multiple ratio variants.'
		);
	}

	#[Test]
	public function test_speakable_present(): void {
		$this->setup_single_post();

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'speakable', $schema );
		$this->assertSame( 'SpeakableSpecification', $schema['speakable']['@type'] );
	}

	#[Test]
	public function test_potential_action_read_action(): void {
		$this->setup_single_post();

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'potentialAction', $schema );
		$this->assertSame( 'ReadAction', $schema['potentialAction']['@type'] );
	}

	#[Test]
	public function test_access_mode_with_image(): void {
		$this->setup_single_post();

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'accessMode', $schema );
		$this->assertContains( 'visual', $schema['accessMode'] );
		$this->assertContains( 'textual', $schema['accessMode'] );
	}

	#[Test]
	public function test_default_type_is_blogposting(): void {
		$this->setup_single_post();

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertSame(
			'BlogPosting',
			$schema['@type'],
			'Default schema type should be BlogPosting, not Article.'
		);
	}

	#[Test]
	public function test_keywords_from_tags(): void {
		$tags = [
			(object) [ 'name' => 'PHP' ],
			(object) [ 'name' => 'WordPress' ],
			(object) [ 'name' => 'Schema' ],
		];
		$this->setup_single_post( [ 'tags' => $tags ] );

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'keywords', $schema );
		$this->assertStringContainsString( 'PHP', $schema['keywords'] );
		$this->assertStringContainsString( 'WordPress', $schema['keywords'] );
		$this->assertStringContainsString( 'Schema', $schema['keywords'] );
	}

	#[Test]
	public function test_article_section_from_category(): void {
		$categories = [
			(object) [ 'name' => 'Technologia', 'term_id' => 5, 'cat_ID' => 5 ],
		];
		$this->setup_single_post( [ 'categories' => $categories ] );

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'articleSection', $schema );
		$this->assertSame( 'Technologia', $schema['articleSection'] );
	}

	#[Test]
	public function test_custom_schema_type_article(): void {
		$this->setup_single_post( [
			'options' => [ 'schema_type' => 'Article' ],
		] );

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertSame( 'Article', $schema['@type'] );
	}

	#[Test]
	public function test_custom_schema_type_newsarticle(): void {
		$this->setup_single_post( [
			'options' => [ 'schema_type' => 'NewsArticle' ],
		] );

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertSame( 'NewsArticle', $schema['@type'] );
	}

	#[Test]
	public function test_invalid_schema_type_falls_back_to_blogposting(): void {
		$this->setup_single_post( [
			'options' => [ 'schema_type' => 'InvalidType' ],
		] );

		$schema = $this->subject->build();

		$this->assertIsArray( $schema );
		$this->assertSame(
			'BlogPosting',
			$schema['@type'],
			'An invalid schema_type option should fall back to BlogPosting.'
		);
	}
}
