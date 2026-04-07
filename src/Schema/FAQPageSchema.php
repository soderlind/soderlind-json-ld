<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

use Soderlind\JsonLd\ContentAnalyzer;

final class FAQPageSchema extends AbstractSchema {

    public function __construct(
        private readonly ContentAnalyzer $analyzer,
    ) {}

    public function is_applicable(): bool {
        if (! is_singular()) {
            return false;
        }
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return false;
        }
        $pairs = $this->analyzer->extract_faq_pairs($post->post_content);
        return ! empty($pairs);
    }

    public function generate(): array {
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return [];
        }

        $pairs = $this->analyzer->extract_faq_pairs($post->post_content);
        if (empty($pairs)) {
            return [];
        }

        $questions = [];
        foreach ($pairs as $pair) {
            $questions[] = [
                '@type'          => 'Question',
                'name'           => $pair['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $pair['answer'],
                ],
            ];
        }

        return [
            '@type'      => 'FAQPage',
            '@id'        => get_permalink($post) . '#faqpage',
            'mainEntity' => $questions,
        ];
    }
}
