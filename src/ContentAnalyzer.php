<?php

declare(strict_types=1);

namespace Soderlind\JsonLd;

use WP_Post;

final class ContentAnalyzer {

    /**
     * Analyze post content and return detected schema types.
     *
     * @return list<string> e.g. ['FAQPage', 'VideoObject']
     */
    public function detect(WP_Post $post): array {
        $content = $post->post_content;
        $rendered = apply_filters('the_content', $content);
        $types = [];

        if ($this->has_faq_content($content, $rendered)) {
            $types[] = 'FAQPage';
        }

        if ($this->has_howto_content($content, $rendered)) {
            $types[] = 'HowTo';
        }

        if ($this->has_software_content($content, $rendered, $post)) {
            $types[] = 'SoftwareApplication';
        }

        if ($this->has_video_content($content, $rendered)) {
            $types[] = 'VideoObject';
        }

        return $types;
    }

    /**
     * Extract FAQ question-answer pairs from content.
     *
     * @return list<array{question: string, answer: string}>
     */
    public function extract_faq_pairs(string $content): array {
        $rendered = apply_filters('the_content', $content);
        $pairs = [];

        // Pattern 1: <details><summary>Question</summary>Answer</details>
        if (preg_match_all(
            '#<details[^>]*>\s*<summary[^>]*>(.*?)</summary>(.*?)</details>#si',
            $rendered,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $question = wp_strip_all_tags(trim($match[1]));
                $answer = wp_strip_all_tags(trim($match[2]));
                if ($question && $answer) {
                    $pairs[] = ['question' => $question, 'answer' => $answer];
                }
            }
        }

        // Pattern 2: Heading containing "?" followed by paragraph(s).
        if (preg_match_all(
            '#<h[2-4][^>]*>(.*?\?.*?)</h[2-4]>\s*((?:<p[^>]*>.*?</p>\s*)+)#si',
            $rendered,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $question = wp_strip_all_tags(trim($match[1]));
                $answer = wp_strip_all_tags(trim($match[2]));
                if ($question && $answer) {
                    $pairs[] = ['question' => $question, 'answer' => $answer];
                }
            }
        }

        // Deduplicate by question.
        $seen = [];
        $unique = [];
        foreach ($pairs as $pair) {
            $key = mb_strtolower($pair['question']);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $pair;
            }
        }

        return $unique;
    }

    /**
     * Extract HowTo steps from content.
     *
     * @return list<array{name: string, text: string}>
     */
    public function extract_howto_steps(string $content): array {
        $rendered = apply_filters('the_content', $content);
        $steps = [];

        // Pattern 1: Ordered list items.
        if (preg_match_all(
            '#<ol[^>]*>(.*?)</ol>#si',
            $rendered,
            $ol_matches,
        )) {
            foreach ($ol_matches[1] as $ol_content) {
                if (preg_match_all('#<li[^>]*>(.*?)</li>#si', $ol_content, $li_matches)) {
                    foreach ($li_matches[1] as $li) {
                        $text = wp_strip_all_tags(trim($li));
                        if ($text) {
                            $steps[] = [
                                'name' => wp_trim_words($text, 8, ''),
                                'text' => $text,
                            ];
                        }
                    }
                }
            }
        }

        // Pattern 2: Headings with "Step" prefix.
        if (empty($steps) && preg_match_all(
            '#<h[2-4][^>]*>\s*(?:Step\s+\d+[:\.\s\-–—]*)\s*(.*?)</h[2-4]>\s*((?:<p[^>]*>.*?</p>\s*)+)#si',
            $rendered,
            $matches,
            PREG_SET_ORDER,
        )) {
            foreach ($matches as $match) {
                $name = wp_strip_all_tags(trim($match[1]));
                $text = wp_strip_all_tags(trim($match[2]));
                if ($name && $text) {
                    $steps[] = ['name' => $name, 'text' => $text];
                }
            }
        }

        return $steps;
    }

    /**
     * Extract video data from content.
     *
     * @return list<array{url: string, embed_url: string, name: string}>
     */
    public function extract_videos(string $content): array {
        $rendered = apply_filters('the_content', $content);
        $videos = [];

        // YouTube embeds.
        if (preg_match_all(
            '#(?:https?://)?(?:www\.)?(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([\w\-]{11})#i',
            $rendered,
            $matches,
        )) {
            foreach ($matches[1] as $video_id) {
                $videos[] = [
                    'url'       => 'https://www.youtube.com/watch?v=' . $video_id,
                    'embed_url' => 'https://www.youtube.com/embed/' . $video_id,
                    'name'      => '',
                ];
            }
        }

        // Vimeo embeds.
        if (preg_match_all(
            '#(?:https?://)?(?:www\.)?(?:vimeo\.com|player\.vimeo\.com/video)/(\d+)#i',
            $rendered,
            $matches,
        )) {
            foreach ($matches[1] as $video_id) {
                $videos[] = [
                    'url'       => 'https://vimeo.com/' . $video_id,
                    'embed_url' => 'https://player.vimeo.com/video/' . $video_id,
                    'name'      => '',
                ];
            }
        }

        // Self-hosted <video> tags.
        if (preg_match_all(
            '#<video[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>#i',
            $rendered,
            $matches,
        )) {
            foreach ($matches[1] as $src) {
                $videos[] = [
                    'url'       => $src,
                    'embed_url' => '',
                    'name'      => '',
                ];
            }
        }

        // Deduplicate by URL.
        $seen = [];
        $unique = [];
        foreach ($videos as $video) {
            if (! isset($seen[$video['url']])) {
                $seen[$video['url']] = true;
                $unique[] = $video;
            }
        }

        return $unique;
    }

    private function has_faq_content(string $raw, string $rendered): bool {
        // <details> elements.
        if (str_contains($rendered, '<details')) {
            return true;
        }

        // FAQ block (Gutenberg).
        if (str_contains($raw, 'wp:yoast/faq-block') || str_contains($raw, 'wp:rank-math/faq')) {
            return true;
        }

        // Headings ending with "?".
        if (preg_match('#<h[2-4][^>]*>[^<]*\?\s*</h[2-4]>#i', $rendered)) {
            return true;
        }

        return false;
    }

    private function has_howto_content(string $raw, string $rendered): bool {
        // HowTo block.
        if (str_contains($raw, 'wp:yoast/how-to-block') || str_contains($raw, 'wp:rank-math/howto')) {
            return true;
        }

        // Title contains "how to".
        $post = get_post();
        if ($post instanceof \WP_Post && preg_match('#\bhow[\s\-]to\b#i', $post->post_title)) {
            return true;
        }

        // Ordered list with 3+ items.
        if (preg_match('#<ol[^>]*>(?:\s*<li[^>]*>.*?</li>\s*){3,}</ol>#si', $rendered)) {
            return true;
        }

        // Step headings.
        if (preg_match('#<h[2-4][^>]*>\s*Step\s+\d+#i', $rendered)) {
            return true;
        }

        return false;
    }

    private function has_software_content(string $raw, string $rendered, WP_Post $post): bool {
        $indicators = 0;
        $text = mb_strtolower(wp_strip_all_tags($rendered));

        $keywords = ['download', 'version', 'install', 'plugin', 'app', 'software', 'application', 'changelog', 'release notes', 'system requirements'];
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                $indicators++;
            }
        }

        // Need at least 3 keyword matches to avoid false positives.
        return $indicators >= 3;
    }

    private function has_video_content(string $raw, string $rendered): bool {
        // YouTube/Vimeo URLs.
        if (preg_match('#(?:youtube\.com|youtu\.be|vimeo\.com)#i', $rendered)) {
            return true;
        }

        // Video block or <video> tag.
        if (str_contains($raw, 'wp:video') || str_contains($raw, 'wp:core-embed/youtube') || str_contains($raw, 'wp:core-embed/vimeo')) {
            return true;
        }

        if (str_contains($rendered, '<video')) {
            return true;
        }

        // iframe with video source.
        if (preg_match('#<iframe[^>]*src=["\'][^"\']*(?:youtube|vimeo|dailymotion)[^"\']*["\']#i', $rendered)) {
            return true;
        }

        return false;
    }
}
