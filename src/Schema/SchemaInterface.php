<?php
/**
 * Schema interface.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

/**
 * Interface for JSON-LD schema generators.
 */
interface SchemaInterface {

	/**
	 * Whether this schema applies to the current page context.
	 */
	public function is_applicable(): bool;

	/**
	 * Generate the schema data array (without @context).
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array;
}
