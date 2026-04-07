<?php
/**
 * WebSite schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

/**
 * Generates WebSite JSON-LD schema.
 */
final class WebSiteSchema extends AbstractSchema {

	/**
	 * Whether this schema applies to the current page.
	 *
	 * @return bool
	 */
	public function is_applicable(): bool {
		return true; // Every page.
	}

	/**
	 * Generate the WebSite schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		return array(
			'@type'           => 'WebSite',
			'@id'             => $this->get_website_id(),
			'name'            => get_bloginfo( 'name' ),
			'description'     => get_bloginfo( 'description' ),
			'url'             => trailingslashit( home_url() ),
			'publisher'       => $this->get_publisher_ref(),
			'inLanguage'      => get_bloginfo( 'language' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);
	}
}
