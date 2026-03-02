<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Media;
use App\Models\Story;
use Illuminate\Http\Response;

class CreativeOgController extends Controller
{
    public function storyMeta(string $slug)
    {
        $story = Story::query()
            ->published()
            ->with(['author:id,name,username', 'coverImage:id,path,thumbnail_path,alt_text'])
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $this->buildStoryMeta($story);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function chapterMeta(string $slug, int $chapterNumber)
    {
        $story = Story::query()
            ->published()
            ->with(['author:id,name,username', 'coverImage:id,path,thumbnail_path,alt_text'])
            ->where('slug', $slug)
            ->firstOrFail();

        $chapter = Chapter::query()
            ->published()
            ->with(['featuredImage:id,path,thumbnail_path,alt_text'])
            ->where('story_id', $story->id)
            ->where('chapter_number', $chapterNumber)
            ->firstOrFail();

        $data = $this->buildChapterMeta($story, $chapter);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function storyImage(string $slug): Response
    {
        $story = Story::query()
            ->published()
            ->with(['author:id,name,username', 'coverImage:id,path,thumbnail_path,alt_text'])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->buildOgPngResponse(
            $story->title,
            $story->author?->name ?? 'Unknown Author',
            $story->coverImage
        );
    }

    public function chapterImage(string $slug, int $chapterNumber): Response
    {
        $story = Story::query()
            ->published()
            ->with(['author:id,name,username', 'coverImage:id,path,thumbnail_path,alt_text'])
            ->where('slug', $slug)
            ->firstOrFail();

        $chapter = Chapter::query()
            ->published()
            ->with(['featuredImage:id,path,thumbnail_path,alt_text'])
            ->where('story_id', $story->id)
            ->where('chapter_number', $chapterNumber)
            ->firstOrFail();

        $title = $chapter->title ?: 'Chapter ' . $chapter->chapter_number;
        $subtitle = $story->title . ' - ' . $title;

        return $this->buildOgPngResponse(
            $subtitle,
            $story->author?->name ?? 'Unknown Author',
            $chapter->featuredImage ?: $story->coverImage
        );
    }

    private function buildStoryMeta(Story $story): array
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $storyUrl = $frontendUrl . '/creative/story/' . $story->slug;
        $coverUrl = $this->mediaUrl($story->coverImage);

        return [
            'type' => 'story',
            'title' => $story->title,
            'description' => $story->summary ?: $story->description,
            'author' => [
                'name' => $story->author?->name,
                'username' => $story->author?->username,
            ],
            'story' => [
                'id' => $story->id,
                'slug' => $story->slug,
            ],
            'story_url' => $storyUrl,
            'cover_image_url' => $coverUrl,
            'og_image_url' => url('/api/creative/og/story/' . $story->slug . '/image'),
        ];
    }

    private function buildChapterMeta(Story $story, Chapter $chapter): array
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $storyUrl = $frontendUrl . '/creative/story/' . $story->slug;
        $chapterUrl = $storyUrl . '/chapter/' . $chapter->chapter_number;
        $coverUrl = $this->mediaUrl($chapter->featuredImage) ?? $this->mediaUrl($story->coverImage);

        return [
            'type' => 'chapter',
            'title' => $chapter->title ?: 'Chapter ' . $chapter->chapter_number,
            'description' => $chapter->excerpt ?: $story->summary,
            'author' => [
                'name' => $story->author?->name,
                'username' => $story->author?->username,
            ],
            'story' => [
                'id' => $story->id,
                'slug' => $story->slug,
                'title' => $story->title,
            ],
            'chapter' => [
                'id' => $chapter->id,
                'chapter_number' => $chapter->chapter_number,
                'title' => $chapter->title,
            ],
            'story_url' => $storyUrl,
            'chapter_url' => $chapterUrl,
            'cover_image_url' => $coverUrl,
            'og_image_url' => url('/api/creative/og/story/' . $story->slug . '/chapter/' . $chapter->chapter_number . '/image'),
        ];
    }

    private function mediaUrl(?Media $media): ?string
    {
        if (!$media || !$media->path) {
            return null;
        }

        $path = $media->path;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url('/storage/' . ltrim($path, '/'));
    }

    private function buildOgPngResponse(string $title, string $author, ?Media $media): Response
    {
        if (!function_exists('imagecreatetruecolor')) {
            return response('PNG generation unavailable.', 501);
        }

        $width = 1200;
        $height = 630;
        $image = imagecreatetruecolor($width, $height);

        $bg = imagecolorallocate($image, 15, 23, 42);
        $panel = imagecolorallocate($image, 17, 24, 39);
        $white = imagecolorallocate($image, 248, 250, 252);
        $muted = imagecolorallocate($image, 203, 213, 245);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);
        imagefilledrectangle($image, 40, 40, 1160, 590, $panel);

        $coverPath = $this->mediaLocalPath($media);
        if ($coverPath) {
            $cover = $this->loadImageResource($coverPath);
            if ($cover) {
                $targetX = 760;
                $targetY = 90;
                $targetW = 360;
                $targetH = 450;
                $srcW = imagesx($cover);
                $srcH = imagesy($cover);
                imagecopyresampled($image, $cover, $targetX, $targetY, 0, 0, $targetW, $targetH, $srcW, $srcH);
                imagedestroy($cover);
            }
        }

        $titleLines = $this->wrapText($title, 30, 3);
        $font = 5;
        $lineHeight = 28;
        $startX = 80;
        $startY = 180;

        foreach ($titleLines as $index => $line) {
            $y = $startY + ($index * $lineHeight);
            imagestring($image, $font, $startX, $y, $line, $white);
        }

        $authorY = $startY + (count($titleLines) * $lineHeight) + 30;
        imagestring($image, 4, $startX, $authorY, $author, $muted);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return response($png, 200, ['Content-Type' => 'image/png']);
    }

    private function wrapText(string $text, int $maxLen, int $maxLines): array
    {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $test = $current === '' ? $word : $current . ' ' . $word;
            if (strlen($test) <= $maxLen) {
                $current = $test;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
                $current = $word;
            }

            if (count($lines) === $maxLines - 1) {
                break;
            }
        }

        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }

        if (count($lines) === $maxLines && count($words) > 0) {
            $last = $lines[$maxLines - 1];
            if (!str_ends_with($last, '...')) {
                $lines[$maxLines - 1] = rtrim($last, '.') . '...';
            }
        }

        return $lines;
    }

    private function mediaLocalPath(?Media $media): ?string
    {
        if (!$media || !$media->path) {
            return null;
        }

        $path = $media->path;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        $storagePath = storage_path('app/public/' . ltrim($path, '/'));
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        $publicPath = public_path('storage/' . ltrim($path, '/'));
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        return null;
    }

    private function loadImageResource(string $path)
    {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            return null;
        }

        return $image;
    }
}
