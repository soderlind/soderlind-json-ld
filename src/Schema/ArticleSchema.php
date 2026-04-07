<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

final class ArticleSchema extends AbstractSchema {

    public function is_applicable(): bool {
        if (! is_singular()) {
            return false;
        }
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return false;
        }
        // Non-"post" and non-"page" public post types.
        return ! in_array($post->post_type, ['post', 'page', 'attachment'], true)
            && is_post_type_viewable($post->post_type);
    }

    public function generate(): array {
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return [];
        }

        $data = [
            '@type'            => 'Article',
            '@id'              => get_permalink($post) . '#article',
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

        return $data;
    }
}
