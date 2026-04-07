<?php
/**
 * AboutPage schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

/**
 * Generates AboutPage JSON-LD schema.
 */
final class AboutPageSchema extends AbstractSchema {

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
		$slug     = $post->post_name;
		$template = get_page_template_slug( $post );
		return str_contains( $slug, 'about' ) || str_contains( $template, 'about' );
	}

	/**
	 * Generate the AboutPage schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		$data = array(
			'@type'         => 'AboutPage',
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
}
