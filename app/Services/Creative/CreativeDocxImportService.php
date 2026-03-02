<?php

namespace App\Services\Creative;

use App\Models\Chapter;
use App\Models\CreativeImportLog;
use App\Models\Media;
use App\Models\Story;
use App\Models\User;
use App\Support\CreativeRichText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class CreativeDocxImportService
{
    public function import(User $user, UploadedFile $file, array $options = []): array
    {
        $importReference = 'imp_' . Str::lower(Str::random(20));
        $warnings = [];
        $errors = [];

        $log = CreativeImportLog::create([
            'user_id' => $user->id,
            'source_type' => 'docx',
            'original_filename' => $file->getClientOriginalName() ?: 'document.docx',
            'file_size' => (int) $file->getSize(),
            'status' => 'processing',
            'import_reference' => $importReference,
        ]);

        try {
            $zip = new ZipArchive();
            $open = $zip->open($file->getRealPath());
            if ($open !== true) {
                throw new RuntimeException('Unable to open DOCX file.');
            }

            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml === false) {
                $zip->close();
                throw new RuntimeException('Invalid DOCX content.');
            }

            $relationships = $this->extractRelationships((string) $zip->getFromName('word/_rels/document.xml.rels'));
            $blocks = $this->extractBlocks($documentXml, $zip, $relationships, $user, $warnings);
            $zip->close();

            if (empty($blocks)) {
                $warnings[] = 'Document contained little or no supported content; a fallback chapter was created.';
            }

            [$storyData, $chapterPayloads, $stats] = $this->buildImportPayload($file, $blocks, $options, $warnings);

            [$story, $chapters] = DB::transaction(function () use ($user, $storyData, $chapterPayloads, $options) {
                $story = Story::create([
                    'title' => $storyData['title'],
                    'slug' => $this->generateUniqueStorySlug($storyData['title']),
                    'summary' => $storyData['summary'],
                    'description' => $storyData['description'],
                    'cover_image_id' => $storyData['cover_image_id'],
                    'language' => $options['language'] ?? 'en',
                    'visibility' => $options['visibility'] ?? 'public',
                    'author_id' => $user->id,
                    'status' => 'draft',
                ]);

                if (!empty($options['category_ids'])) {
                    $story->categories()->sync($options['category_ids']);
                }

                if (!empty($options['tag_ids'])) {
                    $story->tags()->sync($options['tag_ids']);
                }

                $createdChapters = [];
                foreach ($chapterPayloads as $payload) {
                    $createdChapters[] = Chapter::create([
                        'story_id' => $story->id,
                        'chapter_number' => $payload['chapter_number'],
                        'title' => $payload['title'],
                        'slug' => $this->generateChapterSlug($payload['title'], $payload['chapter_number']),
                        'content_html' => $payload['content_html'],
                        'excerpt' => $payload['excerpt'],
                        'featured_image_id' => $payload['featured_image_id'],
                        'word_count' => $payload['word_count'],
                        'read_time_minutes' => $payload['read_time_minutes'],
                        'status' => 'draft',
                    ]);
                }

                return [$story, $createdChapters];
            });

            $log->update([
                'story_id' => $story->id,
                'status' => 'completed',
                'warnings_json' => $warnings,
                'errors_json' => null,
            ]);

            $story->load(['categories:id,name,slug', 'tags:id,name,slug']);

            return [
                'story' => [
                    'id' => $story->id,
                    'title' => $story->title,
                    'summary' => $story->summary,
                    'description' => $story->description,
                    'status' => $story->status,
                    'category_ids' => $story->categories->pluck('id')->values()->all(),
                    'tag_ids' => $story->tags->pluck('id')->values()->all(),
                ],
                'chapters' => collect($chapters)->map(function (Chapter $chapter) {
                    return [
                        'id' => $chapter->id,
                        'chapter_number' => $chapter->chapter_number,
                        'title' => $chapter->title,
                        'word_count' => $chapter->word_count,
                        'read_time_minutes' => $chapter->read_time_minutes,
                        'status' => $chapter->status,
                    ];
                })->values()->all(),
                'preview' => [
                    'story_html_sample' => (string) Str::limit((string) ($storyData['description'] ?? ''), 1200, ''),
                    'chapter_html_samples' => array_slice(array_map(fn($payload) => (string) Str::limit($payload['content_html'], 1200, ''), $chapterPayloads), 0, 3),
                ],
                'warnings' => $warnings,
                'warnings_summary' => [
                    'unsupported_elements_count' => $stats['unsupported_elements_count'],
                    'images_failed_count' => $stats['images_failed_count'],
                    'chapters_detected' => count($chapterPayloads),
                ],
                'import_reference' => $importReference,
            ];
        } catch (\Throwable $exception) {
            $errors[] = $exception->getMessage();

            $log->update([
                'status' => 'failed',
                'warnings_json' => $warnings,
                'errors_json' => $errors,
            ]);

            throw $exception;
        }
    }

    public function getJobStatus(User $user, string $jobId): ?CreativeImportLog
    {
        return CreativeImportLog::query()
            ->where('import_reference', $jobId)
            ->where('user_id', $user->id)
            ->with('story:id,title,status,slug')
            ->first();
    }

    private function extractRelationships(string $relsXml): array
    {
        if ($relsXml === '') {
            return [];
        }

        $xml = @simplexml_load_string($relsXml);
        if ($xml === false) {
            return [];
        }

        $relationships = [];
        foreach ($xml->Relationship as $relationship) {
            $attributes = $relationship->attributes();
            $id = (string) ($attributes['Id'] ?? '');
            $target = (string) ($attributes['Target'] ?? '');
            if ($id !== '' && $target !== '') {
                $relationships[$id] = $target;
            }
        }

        return $relationships;
    }

    private function extractBlocks(string $documentXml, ZipArchive $zip, array $relationships, User $user, array &$warnings): array
    {
        $dom = new \DOMDocument();
        $loaded = @$dom->loadXML($documentXml);
        if (!$loaded) {
            throw new RuntimeException('Unable to parse DOCX XML.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $paragraphs = $xpath->query('/w:document/w:body/w:p');
        $blocks = [];

        if (!$paragraphs) {
            return $blocks;
        }

        foreach ($paragraphs as $paragraph) {
            $styleNode = $xpath->query('.//w:pPr/w:pStyle/@w:val', $paragraph)?->item(0);
            $style = $styleNode ? strtolower((string) $styleNode->nodeValue) : '';

            $embeds = $xpath->query('.//a:blip/@r:embed', $paragraph);
            if ($embeds) {
                foreach ($embeds as $embedAttr) {
                    $rid = (string) $embedAttr->nodeValue;
                    $imageData = $this->storeEmbeddedImage($zip, $relationships, $rid, $user, $warnings);
                    if ($imageData) {
                        $blocks[] = [
                            'type' => 'image',
                            'html' => '<p><img src="' . e($imageData['url']) . '" alt="Imported image" /></p>',
                            'media_id' => $imageData['media_id'],
                        ];
                    }
                }
            }

            $text = $this->extractParagraphText($xpath, $paragraph);
            if ($text === '') {
                continue;
            }

            $text = $this->normalizeTypography($text);

            if ($this->isHeadingStyle($style)) {
                $level = $this->headingLevel($style);
                $blocks[] = [
                    'type' => 'heading',
                    'text' => $text,
                    'level' => $level,
                    'html' => '<h' . $level . '>' . e($text) . '</h' . $level . '>',
                ];
                continue;
            }

            $blocks[] = [
                'type' => 'paragraph',
                'text' => $text,
                'html' => '<p>' . e($text) . '</p>',
            ];
        }

        return $blocks;
    }

    private function extractParagraphText(\DOMXPath $xpath, \DOMNode $paragraph): string
    {
        $textNodes = $xpath->query('.//w:t', $paragraph);
        if (!$textNodes || $textNodes->length === 0) {
            return '';
        }

        $parts = [];
        foreach ($textNodes as $node) {
            $value = trim((string) $node->nodeValue);
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return trim(implode(' ', $parts));
    }

    private function buildImportPayload(UploadedFile $file, array $blocks, array $options, array &$warnings): array
    {
        $firstHeading = collect($blocks)->first(fn($block) => ($block['type'] ?? '') === 'heading');
        $firstParagraph = collect($blocks)->first(fn($block) => ($block['type'] ?? '') === 'paragraph');
        $firstImage = collect($blocks)->first(fn($block) => ($block['type'] ?? '') === 'image' && !empty($block['media_id']));

        $derivedTitle = trim((string) ($options['story_title'] ?? ''));
        if ($derivedTitle === '') {
            $derivedTitle = (string) ($firstHeading['text'] ?? '');
        }
        if ($derivedTitle === '') {
            $derivedTitle = (string) pathinfo($file->getClientOriginalName() ?: 'Imported Story', PATHINFO_FILENAME);
        }
        if ($derivedTitle === '') {
            $derivedTitle = 'Imported Story';
        }

        $summary = isset($firstParagraph['text']) ? Str::limit((string) $firstParagraph['text'], 500, '') : null;

        $descriptionFragments = collect($blocks)
            ->filter(fn($block) => in_array(($block['type'] ?? ''), ['heading', 'paragraph', 'image'], true))
            ->take(6)
            ->map(fn($block) => (string) ($block['html'] ?? ''))
            ->all();

        $descriptionHtml = CreativeRichText::sanitize(implode("\n", $descriptionFragments));

        $importMode = $options['import_mode'] ?? 'split_by_headings';
        $chapterPayloads = $this->buildChapters($blocks, $importMode, $warnings);

        return [
            [
                'title' => $derivedTitle,
                'summary' => $summary,
                'description' => $descriptionHtml,
                'cover_image_id' => $firstImage['media_id'] ?? null,
            ],
            $chapterPayloads,
            [
                'unsupported_elements_count' => 0,
                'images_failed_count' => collect($warnings)->filter(fn($warning) => str_contains(strtolower($warning), 'image'))->count(),
            ],
        ];
    }

    private function buildChapters(array $blocks, string $importMode, array &$warnings): array
    {
        if ($importMode === 'single_chapter') {
            $html = collect($blocks)->pluck('html')->filter()->implode("\n");
            $html = CreativeRichText::sanitize($html);

            return [
                $this->toChapterPayload(1, 'Chapter 1', $html, $this->firstMediaId($blocks)),
            ];
        }

        $chapters = [];
        $currentTitle = null;
        $currentBlocks = [];
        $currentMediaId = null;

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';

            if ($type === 'heading' && $this->isChapterBoundary((string) ($block['text'] ?? ''), (int) ($block['level'] ?? 3), !empty($currentBlocks))) {
                if (!empty($currentBlocks)) {
                    $chapters[] = $this->toChapterPayload(
                        count($chapters) + 1,
                        $currentTitle ?: 'Chapter ' . (count($chapters) + 1),
                        CreativeRichText::sanitize(implode("\n", $currentBlocks)),
                        $currentMediaId
                    );
                    $currentBlocks = [];
                    $currentMediaId = null;
                }

                $currentTitle = (string) ($block['text'] ?? ('Chapter ' . (count($chapters) + 1)));
                $currentBlocks[] = (string) ($block['html'] ?? '');
                continue;
            }

            if ($type === 'image' && empty($currentMediaId) && !empty($block['media_id'])) {
                $currentMediaId = (int) $block['media_id'];
            }

            $currentBlocks[] = (string) ($block['html'] ?? '');
        }

        if (!empty($currentBlocks)) {
            $chapters[] = $this->toChapterPayload(
                count($chapters) + 1,
                $currentTitle ?: ('Chapter ' . (count($chapters) + 1)),
                CreativeRichText::sanitize(implode("\n", $currentBlocks)),
                $currentMediaId
            );
        }

        if (empty($chapters)) {
            $warnings[] = 'No chapter separators detected; generated fallback Chapter 1.';
            $chapters[] = $this->toChapterPayload(1, 'Chapter 1', '<p>Imported content.</p>', $this->firstMediaId($blocks));
        }

        return $chapters;
    }

    private function toChapterPayload(int $chapterNumber, string $title, string $contentHtml, ?int $featuredImageId): array
    {
        $contentHtml = trim($contentHtml) === '' ? '<p>Imported chapter content.</p>' : $contentHtml;
        $wordCount = CreativeRichText::estimateWordCount($contentHtml);

        return [
            'chapter_number' => $chapterNumber,
            'title' => Str::limit(trim($title), 255, ''),
            'content_html' => $contentHtml,
            'excerpt' => Str::limit(strip_tags($contentHtml), 500, ''),
            'featured_image_id' => $featuredImageId,
            'word_count' => $wordCount,
            'read_time_minutes' => CreativeRichText::estimateReadTimeMinutes($wordCount),
        ];
    }

    private function storeEmbeddedImage(ZipArchive $zip, array $relationships, string $rid, User $user, array &$warnings): ?array
    {
        $target = $relationships[$rid] ?? null;
        if (!$target) {
            $warnings[] = 'Embedded image reference could not be resolved.';
            return null;
        }

        $normalizedTarget = ltrim(str_replace('\\', '/', $target), '/');
        if (str_starts_with($normalizedTarget, '../')) {
            $normalizedTarget = preg_replace('#^\.\./#', '', $normalizedTarget) ?? $normalizedTarget;
        }

        $docxPath = str_starts_with($normalizedTarget, 'word/') ? $normalizedTarget : 'word/' . $normalizedTarget;
        $contents = $zip->getFromName($docxPath);
        if ($contents === false) {
            $warnings[] = 'One embedded image could not be extracted.';
            return null;
        }

        $extension = strtolower(pathinfo($docxPath, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => null,
        };

        if (!$mimeType) {
            $warnings[] = 'Unsupported embedded image format was skipped.';
            return null;
        }

        $filename = 'creative/imports/docx/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($filename, $contents);

        $media = Media::create([
            'disk' => 'public',
            'path' => $filename,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($contents),
            'alt_text' => 'Imported image',
            'uploaded_by' => $user->id,
        ]);

        return [
            'media_id' => $media->id,
            'url' => url('/storage/' . ltrim($filename, '/')),
        ];
    }

    private function isHeadingStyle(string $style): bool
    {
        return str_contains($style, 'heading');
    }

    private function headingLevel(string $style): int
    {
        if (preg_match('/heading\s*([1-4])/', $style, $match)) {
            return (int) $match[1];
        }

        if (preg_match('/heading([1-4])/', $style, $match)) {
            return (int) $match[1];
        }

        return 2;
    }

    private function isChapterBoundary(string $headingText, int $level, bool $hasExistingContent): bool
    {
        if (!$hasExistingContent) {
            return false;
        }

        if (preg_match('/^chapter\s+\d+/i', trim($headingText))) {
            return true;
        }

        return $level <= 2;
    }

    private function normalizeTypography(string $text): string
    {
        return str_replace(
            ["\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x93", "\xE2\x80\x94"],
            ["'", "'", '"', '"', '-', '--'],
            $text
        );
    }

    private function firstMediaId(array $blocks): ?int
    {
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'image' && !empty($block['media_id'])) {
                return (int) $block['media_id'];
            }
        }

        return null;
    }

    private function generateUniqueStorySlug(string $title): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'imported-story';
        }

        $slug = $base;
        $counter = 2;

        while (Story::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function generateChapterSlug(string $title, int $chapterNumber): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'chapter-' . $chapterNumber;
        }

        return $base . '-' . Str::lower(Str::random(6));
    }
}
