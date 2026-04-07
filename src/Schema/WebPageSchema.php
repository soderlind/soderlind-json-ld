<?php
/**
 * WebPage schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

/**
 * Generates WebPage JSON-LD schema for generic pages.
 */
final class WebPageSchema extends AbstractSchema {

	/**
	 * Whether this schema applies to the current page.
	 *
	 * @return bool
	 */
	public function is_applicable(): bool {
		if ( ! is_singular( 'page' ) ) {
			return false;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}
		// Only when no more specific page schema (About/Contact) applies.
		$slug     = $post->post_name;
		$template = get_page_template_slug( $post );
		return ! $this->is_about_page( $slug, $template ) && ! $this->is_contact_page( $slug, $template );
	}

	/**
	 * Generate the WebPage schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		$data = array(
			'@type'         => 'WebPage',
			'@id'           => get_permalink( $post ),
			'name'          => get_the_title( $post ),
			'description'   => $this->get_excerpt( $post ),
			'url'           => get_permalink( $post ),
			'datePublished' => get_the_date( 'c', $post ),
			'dateModified'  => get_the_modified_date( 'c', $post ),
			'isPartOf'      => array(
				'@id' => $this->get_website_id(),
			),
			'publisher'     => $this->get_publisher_ref(),
			'inLanguage'    => get_bloginfo( 'language' ),
		);

		$image = $this->get_post_image( $post );
		if ( $image ) {
			$data['primaryImageOfPage'] = $image;
		}

		return $data;
	}

	/**
	 * Check if this is an about page.
	 *
	 * @param string $slug     Page slug.
	 * @param string $template Page template.
	 * @return bool
	 */
	private function is_about_page( string $slug, string $template ): bool {
		return str_contains( $slug, 'about' ) || str_contains( $template, 'about' );
	}

	/**
	 * Check if this is a contact page.
	 *
	 * @param string $slug     Page slug.
	 * @param string $template Page template.
	 * @return bool
	 */
	private function is_contact_page( string $slug, string $template ): bool {
		return str_contains( $slug, 'contact' ) || str_contains( $template, 'contact' );
	}
}
