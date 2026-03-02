<?php

namespace App\Support;

class CreativeRichText
{
    public static function sanitize(string $html): string
    {
        $cleaned = trim($html);

        $cleaned = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('#<object(.*?)>(.*?)</object>#is', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('#<embed(.*?)>(.*?)</embed>#is', '', $cleaned) ?? $cleaned;

        $cleaned = preg_replace('/\son[a-z]+\s*=\s*"[^"]*"/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace("/\son[a-z]+\s*=\s*'[^']*'/i", '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/javascript\s*:/i', '', $cleaned) ?? $cleaned;

        $allowedTags = '<p><br><h1><h2><h3><h4><ul><ol><li><strong><em><a><img><blockquote>';

        return strip_tags($cleaned, $allowedTags);
    }

    public static function estimateWordCount(string $html): int
    {
        $text = trim(strip_tags($html));
        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/', $text) ?: []);
    }

    public static function estimateReadTimeMinutes(int $wordCount): int
    {
        return max(1, (int) ceil($wordCount / 220));
    }
}
