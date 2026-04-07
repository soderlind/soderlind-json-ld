<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

use Soderlind\JsonLd\ContentAnalyzer;

final class HowToSchema extends AbstractSchema {

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
        $steps = $this->analyzer->extract_howto_steps($post->post_content);
        return count($steps) >= 2;
    }

    public function generate(): array {
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return [];
        }

        $steps = $this->analyzer->extract_howto_steps($post->post_content);
        if (count($steps) < 2) {
            return [];
        }

        $howto_steps = [];
        foreach ($steps as $i => $step) {
            $howto_steps[] = [
                '@type'    => 'HowToStep',
                'position' => $i + 1,
                'name'     => $step['name'],
                'text'     => $step['text'],
            ];
        }

        return [
            '@type' => 'HowTo',
            '@id'   => get_permalink($post) . '#howto',
            'name'  => get_the_title($post),
            'step'  => $howto_steps,
        ];
    }
}
