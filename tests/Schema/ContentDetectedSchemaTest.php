<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests\Schema;

use Brain\Monkey\Functions;
use Soderlind\JsonLd\ContentAnalyzer;
use Soderlind\JsonLd\Schema\FAQPageSchema;
use Soderlind\JsonLd\Schema\HowToSchema;
use Soderlind\JsonLd\Schema\SoftwareApplicationSchema;
use Soderlind\JsonLd\Schema\VideoObjectSchema;
use Soderlind\JsonLd\Tests\TestCase;

final class ContentDetectedSchemaTest extends TestCase {

	// --- FAQPageSchema ---

	public function test_faq_applicable_when_pairs_found(): void {
		$content = '<h2>What is JSON-LD?</h2><p>A linked data format.</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$analyzer = new ContentAnalyzer();
		$schema   = new FAQPageSchema( $analyzer );

		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_faq_not_applicable_without_questions(): void {
		$content = '<p>No questions here.</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$analyzer = new ContentAnalyzer();
		$schema   = new FAQPageSchema( $analyzer );

		$this->assertFalse( $schema->is_applicable() );
	}

	public function test_faq_generates_question_answer_structure(): void {
		$content = '<h2>What is SEO?</h2><p>Search engine optimization.</p><h2>What is JSON-LD?</h2><p>A linked data format.</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/test-post/' );
		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$analyzer = new ContentAnalyzer();
		$schema   = new FAQPageSchema( $analyzer );
		$data     = $schema->generate();

		$this->assertSame( 'FAQPage', $data[ '@type' ] );
		$this->assertCount( 2, $data[ 'mainEntity' ] );
		$this->assertSame( 'Question', $data[ 'mainEntity' ][ 0 ][ '@type' ] );
		$this->assertSame( 'What is SEO?', $data[ 'mainEntity' ][ 0 ][ 'name' ] );
		$this->assertSame( 'Answer', $data[ 'mainEntity' ][ 0 ][ 'acceptedAnswer' ][ '@type' ] );
	}

	// --- HowToSchema ---

	public function test_howto_applicable_with_enough_steps(): void {
		$content = '<ol><li>First step</li><li>Second step</li><li>Third step</li></ol>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$analyzer = new ContentAnalyzer();
		$schema   = new HowToSchema( $analyzer );

		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_howto_not_applicable_with_single_step(): void {
		$content = '<ol><li>Only step</li></ol>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$analyzer = new ContentAnalyzer();
		$schema   = new HowToSchema( $analyzer );

		$this->assertFalse( $schema->is_applicable() );
	}

	public function test_howto_generates_step_structure(): void {
		$content = '<ol><li>Mix it</li><li>Bake it</li><li>Serve it</li></ol>';
		$post    = $this->make_post( [ 'post_title' => 'How to Cook', 'post_content' => $content ] );

		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/how-to-cook/' );
		Functions\expect( 'get_the_title' )->andReturn( 'How to Cook' );
		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$analyzer = new ContentAnalyzer();
		$schema   = new HowToSchema( $analyzer );
		$data     = $schema->generate();

		$this->assertSame( 'HowTo', $data[ '@type' ] );
		$this->assertSame( 'How to Cook', $data[ 'name' ] );
		$this->assertCount( 3, $data[ 'step' ] );
		$this->assertSame( 'HowToStep', $data[ 'step' ][ 0 ][ '@type' ] );
		$this->assertSame( 1, $data[ 'step' ][ 0 ][ 'position' ] );
		$this->assertSame( 'Mix it', $data[ 'step' ][ 0 ][ 'text' ] );
	}

	// --- SoftwareApplicationSchema ---

	public function test_software_generates_with_category(): void {
		$content = '<p>Download the latest version of this developer plugin. Install via CLI. Check the changelog and release notes for system requirements.</p>';
		$post    = $this->make_post( [ 'post_title' => 'My Plugin', 'post_content' => $content ] );

		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/my-plugin/' );
		Functions\expect( 'get_the_title' )->andReturn( 'My Plugin' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$analyzer = new ContentAnalyzer();
		$schema   = new SoftwareApplicationSchema( $analyzer );
		$data     = $schema->generate();

		$this->assertSame( 'SoftwareApplication', $data[ '@type' ] );
		$this->assertSame( 'My Plugin', $data[ 'name' ] );
		$this->assertSame( 'DeveloperApplication', $data[ 'applicationCategory' ] );
	}

	// --- VideoObjectSchema ---

	public function test_video_generates_youtube_data(): void {
		$content = '<p>Watch: https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>';
		$post    = $this->make_post( [ 'post_title' => 'Video Post', 'post_content' => $content, 'post_excerpt' => 'A video' ] );

		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/video-post/' );
		Functions\expect( 'get_the_title' )->andReturn( 'Video Post' );
		Functions\expect( 'get_the_date' )->andReturn( '2025-01-01T00:00:00+00:00' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$analyzer = new ContentAnalyzer();
		$schema   = new VideoObjectSchema( $analyzer );
		$data     = $schema->generate();

		$this->assertSame( 'VideoObject', $data[ '@type' ] );
		$this->assertSame( 'Video Post', $data[ 'name' ] );
		$this->assertSame( 'https://www.youtube.com/embed/dQw4w9WgXcQ', $data[ 'embedUrl' ] );
		$this->assertSame( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', $data[ 'contentUrl' ] );
		$this->assertSame( 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $data[ 'thumbnailUrl' ] );
	}

	public function test_video_not_applicable_on_archive(): void {
		Functions\expect( 'is_singular' )->andReturn( false );

		$analyzer = new ContentAnalyzer();
		$schema   = new VideoObjectSchema( $analyzer );

		$this->assertFalse( $schema->is_applicable() );
	}
}
