<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

use Soderlind\JsonLd\ContentAnalyzer;

final class SoftwareApplicationSchema extends AbstractSchema {

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
        $types = $this->analyzer->detect($post);
        return in_array('SoftwareApplication', $types, true);
    }

    public function generate(): array {
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return [];
        }

        $data = [
            '@type'               => 'SoftwareApplication',
            '@id'                 => get_permalink($post) . '#software',
            'name'                => get_the_title($post),
            'description'         => $this->get_excerpt($post),
            'applicationCategory' => $this->detect_category($post),
            'url'                 => get_permalink($post),
            'author'              => $this->get_publisher_ref(),
        ];

        $image = $this->get_post_image($post);
        if ($image) {
            $data['image'] = $image;
        }

        return $data;
    }

    private function detect_category(\WP_Post $post): string {
        $text = mb_strtolower(wp_strip_all_tags($post->post_content));

        $categories = [
            'BrowserApplication'  => ['browser', 'web app', 'extension', 'add-on'],
            'GameApplication'     => ['game', 'gaming'],
            'DeveloperApplication' => ['developer', 'ide', 'programming', 'api', 'sdk', 'cli'],
            'DesignApplication'   => ['design', 'graphic', 'photo', 'image editor'],
            'EducationApplication' => ['education', 'learning', 'course', 'tutorial'],
            'HealthApplication'   => ['health', 'fitness', 'medical'],
            'BusinessApplication' => ['business', 'enterprise', 'crm', 'erp'],
            'SecurityApplication' => ['security', 'antivirus', 'firewall', 'vpn'],
            'MultimediaApplication' => ['video', 'audio', 'media player', 'streaming'],
        ];

        foreach ($categories as $schema_type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $schema_type;
                }
            }
        }

        // WordPress plugin.
        if (str_contains($text, 'plugin') || str_contains($text, 'wordpress')) {
            return 'DeveloperApplication';
        }

        return 'SoftwareApplication';
    }
}
