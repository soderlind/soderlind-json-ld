<?php
/**
 * VideoObject schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

use Soderlind\JsonLd\ContentAnalyzer;

/**
 * Generates VideoObject JSON-LD schema from detected video content.
 */
final class VideoObjectSchema extends AbstractSchema {

	/**
	 * Constructor.
	 *
	 * @param ContentAnalyzer $analyzer Content analyzer instance.
	 */
	public function __construct(
		private readonly ContentAnalyzer $analyzer,
	) {}

	/**
	 * Whether this schema applies to the current page.
	 *
	 * @return bool
	 */
	public function is_applicable(): bool {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}
		$videos = $this->analyzer->extract_videos( $post->post_content );
		return ! empty( $videos );
	}

	/**
	 * Generate the VideoObject schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		$videos = $this->analyzer->extract_videos( $post->post_content );
		if ( empty( $videos ) ) {
			return array();
		}

		// If multiple videos, return the first as primary VideoObject.
		$video = $videos[0];

		$data = array(
			'@type'       => 'VideoObject',
			'@id'         => get_permalink( $post ) . '#video',
			'name'        => $video['name'] ? $video['name'] : get_the_title( $post ),
			'description' => $this->get_excerpt( $post ),
			'uploadDate'  => get_the_date( 'c', $post ),
		);

		if ( $video['embed_url'] ) {
			$data['embedUrl'] = $video['embed_url'];
		}

		if ( $video['url'] ) {
			$data['contentUrl'] = $video['url'];
		}

		// Use post thumbnail as video thumbnail.
		$image = $this->get_post_image( $post );
		if ( $image ) {
			$data['thumbnailUrl'] = $image['url'] ?? '';
		} elseif ( str_contains( $video['url'], 'youtube.com' ) || str_contains( $video['url'], 'youtu.be' ) ) {
			// YouTube thumbnail fallback.
			if ( preg_match( '#(?:v=|embed/|youtu\.be/)([\w\-]{11})#', $video['url'], $m ) ) {
				$data['thumbnailUrl'] = 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
			}
		}

		return $data;
	}
}
