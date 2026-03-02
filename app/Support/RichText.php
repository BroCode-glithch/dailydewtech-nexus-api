<?php

namespace App\Support;

use Illuminate\Support\Str;

class RichText
{
    /**
     * Sanitize rich HTML while preserving common formatting tags used by TinyMCE.
     */
    public static function sanitize(string $html): string
    {
        $cleaned = trim($html);

        // Remove dangerous blocks
        $cleaned = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $cleaned) ?? $cleaned;

        // Remove inline event handlers and javascript links
        $cleaned = preg_replace('/\son[a-z]+\s*=\s*"[^"]*"/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace("/\son[a-z]+\s*=\s*'[^']*'/i", '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/javascript\s*:/i', '', $cleaned) ?? $cleaned;

        // Keep only formatting/content tags needed for posts/projects pages
        $allowedTags = '<p><br><strong><b><em><i><u><s><blockquote><code><pre><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><hr><table><thead><tbody><tr><th><td><span><div>';

        return strip_tags($cleaned, $allowedTags);
    }

    public static function toPlainText(?string $value, int $limit = 500): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim(strip_tags($value));

        return Str::limit($text, $limit);
    }
}
