<?php
/**
 * HowTo schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

use Soderlind\JsonLd\ContentAnalyzer;

/**
 * Generates HowTo JSON-LD schema from detected step content.
 */
final class HowToSchema extends AbstractSchema {

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
		$steps = $this->analyzer->extract_howto_steps( $post->post_content );
		return count( $steps ) >= 2;
	}

	/**
	 * Generate the HowTo schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		$steps = $this->analyzer->extract_howto_steps( $post->post_content );
		if ( count( $steps ) < 2 ) {
			return array();
		}

		$howto_steps = array();
		foreach ( $steps as $i => $step ) {
			$howto_steps[] = array(
				'@type'    => 'HowToStep',
				'position' => $i + 1,
				'name'     => $step['name'],
				'text'     => $step['text'],
			);
		}

		return array(
			'@type' => 'HowTo',
			'@id'   => get_permalink( $post ) . '#howto',
			'name'  => get_the_title( $post ),
			'step'  => $howto_steps,
		);
	}
}
