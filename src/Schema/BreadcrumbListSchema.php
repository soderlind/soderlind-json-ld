<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

final class BreadcrumbListSchema extends AbstractSchema {

    public function is_applicable(): bool {
        return ! is_front_page();
    }

    public function generate(): array {
        $items = $this->build_items();

        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function build_items(): array {
        $items = [];
        $position = 1;

        // Home is always first.
        $items[] = $this->make_item($position++, get_bloginfo('name'), trailingslashit(home_url()));

        if (is_singular()) {
            $post = get_post();
            if ($post) {
                $items = $this->add_singular_breadcrumbs($post, $items, $position);
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $items = $this->add_taxonomy_breadcrumbs($term, $items, $position);
            }
        } elseif (is_post_type_archive()) {
            $post_type = get_queried_object();
            if ($post_type instanceof \WP_Post_Type) {
                $items[] = $this->make_item($position, $post_type->labels->name, get_post_type_archive_link($post_type->name) ?: '');
            }
        } elseif (is_author()) {
            $author = get_queried_object();
            if ($author instanceof \WP_User) {
                $items[] = $this->make_item($position, $author->display_name, get_author_posts_url($author->ID));
            }
        } elseif (is_date()) {
            $items[] = $this->make_item($position, wp_title('', false), '');
        } elseif (is_search()) {
            $items[] = $this->make_item($position, sprintf(
                /* translators: %s: search query */
                __('Search results for "%s"', 'soderlind-json-ld'),
                get_search_query()
            ), '');
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function add_singular_breadcrumbs(\WP_Post $post, array $items, int &$position): array {
        // For hierarchical post types, add ancestors.
        if (is_post_type_hierarchical($post->post_type) && $post->post_parent) {
            $ancestors = array_reverse(get_post_ancestors($post));
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_post($ancestor_id);
                if ($ancestor) {
                    $items[] = $this->make_item($position++, get_the_title($ancestor), get_permalink($ancestor) ?: '');
                }
            }
        } elseif ($post->post_type === 'post') {
            // Add primary category for posts.
            $categories = get_the_category($post->ID);
            if (! empty($categories)) {
                $primary = $categories[0];
                $items = $this->add_term_ancestors($primary, $items, $position);
                $items[] = $this->make_item($position++, $primary->name, get_category_link($primary->term_id));
            }
        }

        $items[] = $this->make_item($position, get_the_title($post), get_permalink($post) ?: '');

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function add_taxonomy_breadcrumbs(\WP_Term $term, array $items, int &$position): array {
        $items = $this->add_term_ancestors($term, $items, $position);
        $items[] = $this->make_item($position, $term->name, get_term_link($term));

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function add_term_ancestors(\WP_Term $term, array $items, int &$position): array {
        if ($term->parent) {
            $ancestors = array_reverse(get_ancestors($term->term_id, $term->taxonomy, 'taxonomy'));
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, $term->taxonomy);
                if ($ancestor instanceof \WP_Term) {
                    $items[] = $this->make_item($position++, $ancestor->name, get_term_link($ancestor));
                }
            }
        }
        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function make_item(int $position, string $name, string $url): array {
        $item = [
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => $name,
        ];

        if ($url) {
            $item['item'] = $url;
        }

        return $item;
    }
}
