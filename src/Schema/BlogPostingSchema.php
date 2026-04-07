<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

final class BlogPostingSchema extends AbstractSchema {

    public function is_applicable(): bool {
        return is_singular('post');
    }

    public function generate(): array {
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return [];
        }

        $data = [
            '@type'            => 'BlogPosting',
            '@id'              => get_permalink($post) . '#blogposting',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => get_permalink($post),
            ],
            'headline'         => get_the_title($post),
            'description'      => $this->get_excerpt($post),
            'datePublished'    => get_the_date('c', $post),
            'dateModified'     => get_the_modified_date('c', $post),
            'author'           => $this->get_author_data($post),
            'publisher'        => $this->get_publisher_ref(),
            'isPartOf'         => [
                '@id' => $this->get_website_id(),
            ],
            'inLanguage'       => get_bloginfo('language'),
        ];

        $image = $this->get_post_image($post);
        if ($image) {
            $data['image'] = $image;
        }

        $categories = get_the_category($post->ID);
        if (! empty($categories)) {
            $data['articleSection'] = array_map(
                static fn(\WP_Term $cat): string => $cat->name,
                $categories,
            );
        }

        $tags = get_the_tags($post->ID);
        if (is_array($tags) && ! empty($tags)) {
            $data['keywords'] = array_map(
                static fn(\WP_Term $tag): string => $tag->name,
                $tags,
            );
        }

        $word_count = str_word_count(wp_strip_all_tags($post->post_content));
        if ($word_count > 0) {
            $data['wordCount'] = $word_count;
        }

        return $data;
    }
}
