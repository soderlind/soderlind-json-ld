<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

final class WebPageSchema extends AbstractSchema {

    public function is_applicable(): bool {
        if (! is_singular('page')) {
            return false;
        }
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return false;
        }
        // Only when no more specific page schema (About/Contact) applies.
        $slug = $post->post_name;
        $template = get_page_template_slug($post);
        return ! $this->is_about_page($slug, $template) && ! $this->is_contact_page($slug, $template);
    }

    public function generate(): array {
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return [];
        }

        $data = [
            '@type'         => 'WebPage',
            '@id'           => get_permalink($post),
            'name'          => get_the_title($post),
            'description'   => $this->get_excerpt($post),
            'url'           => get_permalink($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified'  => get_the_modified_date('c', $post),
            'isPartOf'      => [
                '@id' => $this->get_website_id(),
            ],
            'publisher'     => $this->get_publisher_ref(),
            'inLanguage'    => get_bloginfo('language'),
        ];

        $image = $this->get_post_image($post);
        if ($image) {
            $data['primaryImageOfPage'] = $image;
        }

        return $data;
    }

    private function is_about_page(string $slug, string $template): bool {
        return str_contains($slug, 'about') || str_contains($template, 'about');
    }

    private function is_contact_page(string $slug, string $template): bool {
        return str_contains($slug, 'contact') || str_contains($template, 'contact');
    }
}
