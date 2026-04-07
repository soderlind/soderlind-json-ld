<?php
/**
 * Person schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

/**
 * Generates Person JSON-LD schema for author archives.
 */
final class PersonSchema extends AbstractSchema {

	/**
	 * Whether this schema applies to the current page.
	 *
	 * @return bool
	 */
	public function is_applicable(): bool {
		return is_author();
	}

	/**
	 * Generate the Person schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$author = get_queried_object();
		if ( ! $author instanceof \WP_User ) {
			return array();
		}

		return $this->build_person_data( $author );
	}
}
