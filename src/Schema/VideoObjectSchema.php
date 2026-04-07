<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Schema;

use Soderlind\JsonLd\ContentAnalyzer;

final class VideoObjectSchema extends AbstractSchema {

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
        $videos = $this->analyzer->extract_videos($post->post_content);
        return ! empty($videos);
    }

    public function generate(): array {
        $post = get_post();
        if (! $post instanceof \WP_Post) {
            return [];
        }

        $videos = $this->analyzer->extract_videos($post->post_content);
        if (empty($videos)) {
            return [];
        }

        // If multiple videos, return the first as primary VideoObject.
        $video = $videos[0];

        $data = [
            '@type'       => 'VideoObject',
            '@id'         => get_permalink($post) . '#video',
            'name'        => $video['name'] ?: get_the_title($post),
            'description' => $this->get_excerpt($post),
            'uploadDate'  => get_the_date('c', $post),
        ];

        if ($video['embed_url']) {
            $data['embedUrl'] = $video['embed_url'];
        }

        if ($video['url']) {
            $data['contentUrl'] = $video['url'];
        }

        // Use post thumbnail as video thumbnail.
        $image = $this->get_post_image($post);
        if ($image) {
            $data['thumbnailUrl'] = $image['url'] ?? '';
        } elseif (str_contains($video['url'], 'youtube.com') || str_contains($video['url'], 'youtu.be')) {
            // YouTube thumbnail fallback.
            if (preg_match('#(?:v=|embed/|youtu\.be/)([\w\-]{11})#', $video['url'], $m)) {
                $data['thumbnailUrl'] = 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
            }
        }

        return $data;
    }
}
