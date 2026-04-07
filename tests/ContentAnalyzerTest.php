<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests;

use Brain\Monkey\Functions;
use Soderlind\JsonLd\ContentAnalyzer;

final class ContentAnalyzerTest extends TestCase {

	private ContentAnalyzer $analyzer;

	protected function setUp(): void {
		parent::setUp();
		$this->analyzer = new ContentAnalyzer();
	}

	// --- FAQ Detection ---

	public function test_detects_faq_from_details_elements(): void {
		$post = $this->make_post( [
			'post_content' => '<details><summary>What is JSON-LD?</summary><p>A format for linked data.</p></details>',
		] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $post->post_content )->andReturn( $post->post_content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'FAQPage', $types );
	}

	public function test_detects_faq_from_question_headings(): void {
		$content = '<h2>What is structured data?</h2><p>Structured data is code.</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'FAQPage', $types );
	}

	public function test_detects_faq_from_yoast_block(): void {
		$content = '<!-- wp:yoast/faq-block --><div class="yoast-faq"></div><!-- /wp:yoast/faq-block -->';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( '<div class="yoast-faq"></div>' );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'FAQPage', $types );
	}

	public function test_does_not_detect_faq_without_patterns(): void {
		$content = '<p>Just a normal paragraph.</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertNotContains( 'FAQPage', $types );
	}

	// --- HowTo Detection ---

	public function test_detects_howto_from_title(): void {
		$content = '<p>Some content with ordered list</p>';
		$post    = $this->make_post( [ 'post_title' => 'How to Build a Plugin', 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'HowTo', $types );
	}

	public function test_detects_howto_from_ordered_list(): void {
		$content = '<ol><li>First</li><li>Second</li><li>Third</li></ol>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'HowTo', $types );
	}

	public function test_detects_howto_from_step_headings(): void {
		$content = '<h2>Step 1: Prepare</h2><p>Get ready.</p><h2>Step 2: Execute</h2><p>Do it.</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'HowTo', $types );
	}

	// --- SoftwareApplication Detection ---

	public function test_detects_software_with_enough_keywords(): void {
		$content = '<p>Download the latest version of this plugin. Install it via the app store. Check the changelog for release notes.</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'SoftwareApplication', $types );
	}

	public function test_does_not_detect_software_with_few_keywords(): void {
		$content = '<p>Here is the latest version of the document.</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertNotContains( 'SoftwareApplication', $types );
	}

	// --- VideoObject Detection ---

	public function test_detects_youtube_video(): void {
		$content = '<p>Watch: https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'VideoObject', $types );
	}

	public function test_detects_vimeo_video(): void {
		$content = '<p>Watch: https://vimeo.com/123456789</p>';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'VideoObject', $types );
	}

	public function test_detects_video_block(): void {
		$content = '<!-- wp:video --><figure><video src="https://example.com/vid.mp4"></video></figure><!-- /wp:video -->';
		$post    = $this->make_post( [ 'post_content' => $content ] );

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( '<figure><video src="https://example.com/vid.mp4"></video></figure>' );
		Functions\expect( 'get_post' )->andReturn( $post );

		$types = $this->analyzer->detect( $post );
		$this->assertContains( 'VideoObject', $types );
	}

	// --- Extraction methods ---

	public function test_extract_faq_pairs_from_details(): void {
		$content = '<details><summary>Q1?</summary><p>Answer one.</p></details><details><summary>Q2?</summary><p>Answer two.</p></details>';

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$pairs = $this->analyzer->extract_faq_pairs( $content );

		$this->assertCount( 2, $pairs );
		$this->assertSame( 'Q1?', $pairs[ 0 ][ 'question' ] );
		$this->assertSame( 'Answer one.', $pairs[ 0 ][ 'answer' ] );
	}

	public function test_extract_faq_pairs_from_headings(): void {
		$content = '<h2>What is SEO?</h2><p>Search engine optimization is the process.</p>';

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$pairs = $this->analyzer->extract_faq_pairs( $content );

		$this->assertCount( 1, $pairs );
		$this->assertSame( 'What is SEO?', $pairs[ 0 ][ 'question' ] );
	}

	public function test_extract_faq_pairs_deduplicates(): void {
		$content = '<details><summary>Same question?</summary><p>Answer A.</p></details>'
			. '<h2>Same question?</h2><p>Answer B.</p>';

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$pairs = $this->analyzer->extract_faq_pairs( $content );

		$this->assertCount( 1, $pairs );
	}

	public function test_extract_howto_steps_from_ordered_list(): void {
		$content = '<ol><li>Mix ingredients</li><li>Stir well</li><li>Bake at 350F</li></ol>';

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$steps = $this->analyzer->extract_howto_steps( $content );

		$this->assertCount( 3, $steps );
		$this->assertSame( 'Mix ingredients', $steps[ 0 ][ 'text' ] );
	}

	public function test_extract_videos_youtube(): void {
		$content = '<p>https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>';

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$videos = $this->analyzer->extract_videos( $content );

		$this->assertCount( 1, $videos );
		$this->assertSame( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', $videos[ 0 ][ 'url' ] );
		$this->assertSame( 'https://www.youtube.com/embed/dQw4w9WgXcQ', $videos[ 0 ][ 'embed_url' ] );
	}

	public function test_extract_videos_vimeo(): void {
		$content = '<iframe src="https://player.vimeo.com/video/12345678"></iframe>';

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$videos = $this->analyzer->extract_videos( $content );

		$this->assertCount( 1, $videos );
		$this->assertSame( 'https://vimeo.com/12345678', $videos[ 0 ][ 'url' ] );
	}

	public function test_extract_videos_deduplicates(): void {
		$content = '<p>https://www.youtube.com/watch?v=dQw4w9WgXcQ and also https://youtube.com/embed/dQw4w9WgXcQ</p>';

		Functions\expect( 'apply_filters' )->with( 'the_content', $content )->andReturn( $content );

		$videos = $this->analyzer->extract_videos( $content );

		$this->assertCount( 1, $videos );
	}
}
