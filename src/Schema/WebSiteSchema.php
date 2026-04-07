<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

final class WebSiteSchema extends AbstractSchema {

    public function is_applicable(): bool {
        return true; // Every page.
    }

    public function generate(): array {
        return [
            '@type'           => 'WebSite',
            '@id'             => $this->get_website_id(),
            'name'            => get_bloginfo('name'),
            'description'     => get_bloginfo('description'),
            'url'             => trailingslashit(home_url()),
            'publisher'       => $this->get_publisher_ref(),
            'inLanguage'      => get_bloginfo('language'),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'        => 'EntryPoint',
                    'urlTemplate'  => home_url('/?s={search_term_string}'),
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}
