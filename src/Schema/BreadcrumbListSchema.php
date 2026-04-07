<?php
/**
 * BreadcrumbList schema generator.
 *
 * @package Soderlind\JsonLd\Schema
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

/**
 * Generates BreadcrumbList JSON-LD schema.
 */
final class BreadcrumbListSchema extends AbstractSchema {

	/**
	 * Whether this schema applies to the current page.
	 *
	 * @return bool
	 */
	public function is_applicable(): bool {
		return ! is_front_page();
	}

	/**
	 * Generate the BreadcrumbList schema data.
	 *
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$items = $this->build_items();

		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	/**
	 * Build the breadcrumb items array.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function build_items(): array {
		$items    = array();
		$position = 1;

		// Home is always first.
		$items[] = $this->make_item( $position++, get_bloginfo( 'name' ), trailingslashit( home_url() ) );

		if ( is_singular() ) {
			$post = get_post();
			if ( $post ) {
				$items = $this->add_singular_breadcrumbs( $post, $items, $position );
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$items = $this->add_taxonomy_breadcrumbs( $term, $items, $position );
			}
		} elseif ( is_post_type_archive() ) {
			$post_type = get_queried_object();
			if ( $post_type instanceof \WP_Post_Type ) {
				$archive_link = get_post_type_archive_link( $post_type->name );
				$items[]      = $this->make_item( $position, $post_type->labels->name, $archive_link ? $archive_link : '' );
			}
		} elseif ( is_author() ) {
			$author = get_queried_object();
			if ( $author instanceof \WP_User ) {
				$items[] = $this->make_item( $position, $author->display_name, get_author_posts_url( $author->ID ) );
			}
		} elseif ( is_date() ) {
			$items[] = $this->make_item( $position, wp_title( '', false ), '' );
		} elseif ( is_search() ) {
			$items[] = $this->make_item(
				$position,
				sprintf(
				/* translators: %s: search query */
					__( 'Search results for "%s"', 'soderlind-json-ld' ),
					get_search_query()
				),
				''
			);
		}

		return $items;
	}

	/**
	 * Add singular post breadcrumbs including ancestors and categories.
	 *
	 * @param \WP_Post                   $post     Post object.
	 * @param list<array<string, mixed>> $items    Breadcrumb items.
	 * @param int                        $position Current position counter.
	 * @return list<array<string, mixed>>
	 */
	private function add_singular_breadcrumbs( \WP_Post $post, array $items, int &$position ): array {
		// For hierarchical post types, add ancestors.
		if ( is_post_type_hierarchical( $post->post_type ) && $post->post_parent ) {
			$ancestors = array_reverse( get_post_ancestors( $post ) );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_post( $ancestor_id );
				if ( $ancestor ) {
					$permalink = get_permalink( $ancestor );
					$items[]   = $this->make_item( $position++, get_the_title( $ancestor ), $permalink ? $permalink : '' );
				}
			}
		} elseif ( 'post' === $post->post_type ) {
			// Add primary category for posts.
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				$primary = $categories[0];
				$items   = $this->add_term_ancestors( $primary, $items, $position );
				$items[] = $this->make_item( $position++, $primary->name, get_category_link( $primary->term_id ) );
			}
		}

		$permalink = get_permalink( $post );
		$items[]   = $this->make_item( $position, get_the_title( $post ), $permalink ? $permalink : '' );

		return $items;
	}

	/**
	 * Add taxonomy term breadcrumbs with ancestors.
	 *
	 * @param \WP_Term                   $term     Term object.
	 * @param list<array<string, mixed>> $items    Breadcrumb items.
	 * @param int                        $position Current position counter.
	 * @return list<array<string, mixed>>
	 */
	private function add_taxonomy_breadcrumbs( \WP_Term $term, array $items, int &$position ): array {
		$items   = $this->add_term_ancestors( $term, $items, $position );
		$items[] = $this->make_item( $position, $term->name, get_term_link( $term ) );

		return $items;
	}

	/**
	 * Add term ancestor breadcrumbs.
	 *
	 * @param \WP_Term                   $term     Term object.
	 * @param list<array<string, mixed>> $items    Breadcrumb items.
	 * @param int                        $position Current position counter.
	 * @return list<array<string, mixed>>
	 */
	private function add_term_ancestors( \WP_Term $term, array $items, int &$position ): array {
		if ( $term->parent ) {
			$ancestors = array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, $term->taxonomy );
				if ( $ancestor instanceof \WP_Term ) {
					$items[] = $this->make_item( $position++, $ancestor->name, get_term_link( $ancestor ) );
				}
			}
		}
		return $items;
	}

	/**
	 * Create a single breadcrumb ListItem.
	 *
	 * @param int    $position Item position.
	 * @param string $name     Item name.
	 * @param string $url      Item URL.
	 * @return array<string, mixed>
	 */
	private function make_item( int $position, string $name, string $url ): array {
		$item = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => $name,
		);

		if ( $url ) {
			$item['item'] = $url;
		}

		return $item;
	}
}
