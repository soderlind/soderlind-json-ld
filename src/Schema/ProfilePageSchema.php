<?php
/**
 * ProfilePage schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

/**
 * Generates ProfilePage JSON-LD schema for author archives.
 */
final class ProfilePageSchema extends AbstractSchema {

	/**
	 * Whether this schema applies to the current page.
	 *
	 * @return bool
	 */
	public function is_applicable(): bool {
		return is_author();
	}

	/**
	 * Generate the ProfilePage schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$author = get_queried_object();
		if ( ! $author instanceof \WP_User ) {
			return array();
		}

		$person = $this->build_person_data( $author );

		$data = array(
			'@type'      => 'ProfilePage',
			'@id'        => get_author_posts_url( $author->ID ) . '#profilepage',
			'name'       => sprintf(
				/* translators: %s: author name */
				__( 'Profile: %s', 'soderlind-json-ld' ),
				$author->display_name,
			),
			'url'        => get_author_posts_url( $author->ID ),
			'mainEntity' => $person,
			'isPartOf'   => array(
				'@id' => $this->get_website_id(),
			),
			'inLanguage' => get_bloginfo( 'language' ),
		);

		$registered = $author->user_registered;
		if ( $registered ) {
			$data['dateCreated'] = mysql2date( 'c', $registered );
		}

		$description = get_the_author_meta( 'description', $author->ID );
		if ( $description ) {
			$data['description'] = wp_strip_all_tags( $description );
		}

		return $data;
	}
}
