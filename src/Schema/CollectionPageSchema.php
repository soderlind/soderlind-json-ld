<?php
/**
 * CollectionPage schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

/**
 * Generates CollectionPage JSON-LD schema for archives and blog page.
 */
final class CollectionPageSchema extends AbstractSchema {

	/**
	 * Whether this schema applies to the current page.
	 *
	 * @return bool
	 */
	public function is_applicable(): bool {
		return is_archive() || is_home();
	}

	/**
	 * Generate the CollectionPage schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$data = array(
			'@type'      => 'CollectionPage',
			'@id'        => $this->get_current_url() . '#collectionpage',
			'name'       => $this->get_archive_title(),
			'url'        => $this->get_current_url(),
			'isPartOf'   => array(
				'@id' => $this->get_website_id(),
			),
			'publisher'  => $this->get_publisher_ref(),
			'inLanguage' => get_bloginfo( 'language' ),
		);

		$description = $this->get_archive_description();
		if ( $description ) {
			$data['description'] = $description;
		}

		$items = $this->build_item_list();
		if ( ! empty( $items ) ) {
			$data['mainEntity'] = array(
				'@type'           => 'ItemList',
				'itemListElement' => $items,
			);
		}

		return $data;
	}

	/**
	 * Get the archive page title.
	 *
	 * @return string
	 */
	private function get_archive_title(): string {
		if ( is_home() ) {
			return get_bloginfo( 'name' ) . ' — ' . __( 'Blog', 'soderlind-json-ld' );
		}

		return wp_strip_all_tags( (string) get_the_archive_title() );
	}

	/**
	 * Get the archive page description.
	 *
	 * @return string
	 */
	private function get_archive_description(): string {
		if ( is_home() ) {
			return get_bloginfo( 'description' );
		}

		$description = get_the_archive_description();
		return $description ? wp_strip_all_tags( $description ) : '';
	}

	/**
	 * Get the current archive URL.
	 *
	 * @return string
	 */
	private function get_current_url(): string {
		if ( is_home() && get_option( 'page_for_posts' ) ) {
			$permalink = get_permalink( get_option( 'page_for_posts' ) );
			return $permalink ? $permalink : home_url( '/' );
		}

		global $wp;
		return trailingslashit( home_url( $wp->request ) );
	}

	/**
	 * Build item list for the collection page.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function build_item_list(): array {
		global $wp_query;

		$items    = array();
		$position = 1;

		if ( ! $wp_query || empty( $wp_query->posts ) ) {
			return $items;
		}

		foreach ( $wp_query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$permalink = get_permalink( $post );
			$items[]   = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'url'      => $permalink ? $permalink : '',
				'name'     => get_the_title( $post ),
			);

			// Cap at 50 items for performance.
			if ( $position > 50 ) {
				break;
			}
		}

		return $items;
	}
}
