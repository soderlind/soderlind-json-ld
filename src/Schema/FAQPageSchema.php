<?php
/**
 * FAQPage schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

use Soderlind\JsonLd\ContentAnalyzer;

/**
 * Generates FAQPage JSON-LD schema from detected FAQ content.
 */
final class FAQPageSchema extends AbstractSchema {

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
		$pairs = $this->analyzer->extract_faq_pairs( $post->post_content );
		return ! empty( $pairs );
	}

	/**
	 * Generate the FAQPage schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		$pairs = $this->analyzer->extract_faq_pairs( $post->post_content );
		if ( empty( $pairs ) ) {
			return array();
		}

		$questions = array();
		foreach ( $pairs as $pair ) {
			$questions[] = array(
				'@type'          => 'Question',
				'name'           => $pair['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $pair['answer'],
				),
			);
		}

		return array(
			'@type'      => 'FAQPage',
			'@id'        => get_permalink( $post ) . '#faqpage',
			'mainEntity' => $questions,
		);
	}
}
