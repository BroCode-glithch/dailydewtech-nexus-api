<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreativeMediaController extends Controller
{
    private const MAX_IMAGE_UPLOAD_KB = 10240; // 10MB

    public function mediaHealth(Request $request)
    {
        $payload = $request->validate([
            'media_id' => 'nullable|integer|exists:media,id|required_without:path',
            'path' => 'nullable|string|max:1024|required_without:media_id',
        ]);

        $result = [
            'input' => [
                'media_id' => $payload['media_id'] ?? null,
                'path' => $payload['path'] ?? null,
            ],
            'environment' => [
                'app_url' => config('app.url'),
                'public_disk_url' => config('filesystems.disks.public.url'),
                'storage_symlink_path' => public_path('storage'),
                'storage_symlink_exists' => is_link(public_path('storage')) || is_dir(public_path('storage')),
            ],
            'checks' => [],
        ];

        if (!empty($payload['media_id'])) {
            $media = Media::query()->findOrFail((int) $payload['media_id']);
            $result['media'] = [
                'id' => $media->id,
                'disk' => $media->disk,
                'path' => $media->path,
                'thumbnail_path' => $media->thumbnail_path,
                'mime_type' => $media->mime_type,
                'size_bytes' => $media->size_bytes,
            ];

            $result['checks']['original'] = $this->buildPathHealth((string) $media->path);
            if (!empty($media->thumbnail_path)) {
                $result['checks']['thumbnail'] = $this->buildPathHealth((string) $media->thumbnail_path);
            }
        }

        if (!empty($payload['path'])) {
            $normalized = $this->normalizeToPublicDiskPath((string) $payload['path']);
            $result['checks']['input_path'] = $this->buildPathHealth($normalized);
            $result['checks']['input_path']['raw_input'] = (string) $payload['path'];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function uploadLimits()
    {
        $uploadMax = (string) ini_get('upload_max_filesize');
        $postMax = (string) ini_get('post_max_size');

        $uploadMaxBytes = $this->iniSizeToBytes($uploadMax);
        $postMaxBytes = $this->iniSizeToBytes($postMax);
        $appMaxBytes = self::MAX_IMAGE_UPLOAD_KB * 1024;

        $effectiveServerMaxBytes = min($uploadMaxBytes, $postMaxBytes);
        $effectiveMaxBytes = min($effectiveServerMaxBytes, $appMaxBytes);

        return response()->json([
            'success' => true,
            'data' => [
                'php' => [
                    'upload_max_filesize' => $uploadMax,
                    'post_max_size' => $postMax,
                    'upload_max_filesize_bytes' => $uploadMaxBytes,
                    'post_max_size_bytes' => $postMaxBytes,
                    'effective_server_max_bytes' => $effectiveServerMaxBytes,
                ],
                'application' => [
                    'max_image_upload_kb' => self::MAX_IMAGE_UPLOAD_KB,
                    'max_image_upload_bytes' => $appMaxBytes,
                ],
                'effective_max_bytes' => $effectiveMaxBytes,
                'effective_max_mb' => round($effectiveMaxBytes / 1024 / 1024, 2),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $media = Media::query()->findOrFail($id);
        $disk = $media->disk ?: 'public';

        $useThumbnail = $request->query('variant') === 'thumb';
        $path = $useThumbnail && !empty($media->thumbnail_path)
            ? (string) $media->thumbnail_path
            : (string) $media->path;

        if ($path === '' || !Storage::disk($disk)->exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Media file not found on disk.',
            ], 404);
        }

        $headers = [
            'Cache-Control' => 'public, max-age=86400',
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
        ];

        return response(Storage::disk($disk)->get($path), 200, $headers);
    }

    public function upload(Request $request)
    {
        $uploadMaxBytes = $this->iniSizeToBytes((string) ini_get('upload_max_filesize'));
        $postMaxBytes = $this->iniSizeToBytes((string) ini_get('post_max_size'));
        $appMaxBytes = self::MAX_IMAGE_UPLOAD_KB * 1024;
        $effectiveServerMaxBytes = min($uploadMaxBytes, $postMaxBytes);
        $effectiveMaxBytes = min($effectiveServerMaxBytes, $appMaxBytes);

        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        if ($contentLength > 0 && $effectiveMaxBytes > 0 && $contentLength > $effectiveMaxBytes) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded payload exceeds current effective upload limits.',
                'errors' => [
                    'file' => [
                        'Payload too large for current server/app limits. Check /api/media/upload-limits.',
                    ],
                ],
                'limits' => [
                    'effective_max_bytes' => $effectiveMaxBytes,
                    'effective_max_mb' => round($effectiveMaxBytes / 1024 / 1024, 2),
                    'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
                    'post_max_size' => (string) ini_get('post_max_size'),
                    'app_max_mb' => round($appMaxBytes / 1024 / 1024, 2),
                ],
            ], 413);
        }

        $file = $this->resolveUploadFile($request);
        if ($file) {
            $request->files->set('file', $file);
        } elseif ($contentLength > 0 && str_contains(strtolower((string) $request->header('content-type')), 'multipart/form-data')) {
            return response()->json([
                'success' => false,
                'message' => 'No valid uploaded file was detected. This is usually caused by PHP upload limits.',
                'errors' => [
                    'file' => [
                        'The file failed to upload. If this is a valid image under 10MB, check PHP upload_max_filesize/post_max_size and restart WAMP.',
                    ],
                ],
                'limits' => [
                    'effective_max_bytes' => $effectiveMaxBytes,
                    'effective_max_mb' => round($effectiveMaxBytes / 1024 / 1024, 2),
                    'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
                    'post_max_size' => (string) ini_get('post_max_size'),
                ],
            ], 422);
        }

        $payload = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:' . self::MAX_IMAGE_UPLOAD_KB,
            'alt_text' => 'nullable|string|max:255',
        ], [
            'file.required' => 'An image file is required. Use one of: file, image, cover_image, coverImage, featured_image, featuredImage.',
            'file.max' => 'Image too large. Maximum allowed size is 10MB.',
        ]);

        $file = $payload['file'];
        $disk = 'public';
        $path = $file->store('creative/uploads', $disk);

        [$width, $height] = $this->imageSize($file->getRealPath());
        $thumbnailPath = $this->makeThumbnail($file->getRealPath(), $disk);

        $media = Media::create([
            'disk' => $disk,
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize() ?: 0,
            'width' => $width,
            'height' => $height,
            'alt_text' => $payload['alt_text'] ?? null,
            'uploaded_by' => optional($request->user())->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'media_id' => $media->id,
                'url' => $this->publicStorageUrl((string) $media->path),
                'thumbnail_url' => $media->thumbnail_path ? $this->publicStorageUrl((string) $media->thumbnail_path) : null,
                'api_url' => url('/api/media/' . $media->id),
                'api_thumbnail_url' => $media->thumbnail_path ? url('/api/media/' . $media->id . '?variant=thumb') : null,
                'preferred_image_url' => url('/api/media/' . $media->id),
                'preferred_thumbnail_url' => $media->thumbnail_path ? url('/api/media/' . $media->id . '?variant=thumb') : null,
                'media' => $media,
            ],
        ], 201);
    }

    private function resolveUploadFile(Request $request): ?UploadedFile
    {
        foreach (['file', 'image', 'cover_image', 'coverImage', 'featured_image', 'featuredImage'] as $key) {
            if ($request->hasFile($key)) {
                return $request->file($key);
            }
        }

        return null;
    }

    private function imageSize(string $path): array
    {
        $size = @getimagesize($path);
        if (!$size) {
            return [null, null];
        }

        return [(int) ($size[0] ?? 0), (int) ($size[1] ?? 0)];
    }

    private function makeThumbnail(string $sourcePath, string $disk): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return null;
        }

        [$width, $height] = $info;
        if ($width < 2 || $height < 2) {
            return null;
        }

        $targetWidth = 360;
        $targetHeight = (int) round(($height / $width) * $targetWidth);

        $mime = $info['mime'] ?? '';
        $sourceImage = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (!$sourceImage) {
            return null;
        }

        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $thumbRelativePath = 'creative/uploads/thumbs/' . Str::uuid() . '.jpg';
        $absolute = Storage::disk($disk)->path($thumbRelativePath);
        $dir = dirname($absolute);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        imagejpeg($thumb, $absolute, 85);
        imagedestroy($thumb);
        imagedestroy($sourceImage);

        return $thumbRelativePath;
    }

    private function iniSizeToBytes(string $value): int
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return 0;
        }

        $unit = strtolower(substr($trimmed, -1));
        $number = (float) $trimmed;

        return match ($unit) {
            'g' => (int) round($number * 1024 * 1024 * 1024),
            'm' => (int) round($number * 1024 * 1024),
            'k' => (int) round($number * 1024),
            default => (int) round((float) $trimmed),
        };
    }

    private function normalizeToPublicDiskPath(string $path): string
    {
        $candidate = trim($path);

        if (preg_match('/^https?:\/\//i', $candidate)) {
            $parsedPath = parse_url($candidate, PHP_URL_PATH);
            $candidate = is_string($parsedPath) ? $parsedPath : $candidate;
        }

        $candidate = ltrim($candidate, '/');

        if (str_starts_with($candidate, 'storage/')) {
            return ltrim(substr($candidate, strlen('storage/')), '/');
        }

        return $candidate;
    }

    private function buildPathHealth(string $relativePath): array
    {
        $disk = Storage::disk('public');
        $relativePath = ltrim($relativePath, '/');
        $exists = $disk->exists($relativePath);
        $relativeUrl = '/storage/' . $relativePath;
        $absoluteUrl = $this->publicStorageUrl($relativePath);

        return [
            'disk' => 'public',
            'relative_path' => $relativePath,
            'exists_on_disk' => $exists,
            'absolute_file_path' => $disk->path($relativePath),
            'relative_url' => $relativeUrl,
            'absolute_url' => $absoluteUrl,
        ];
    }

    private function publicStorageUrl(string $relativePath): string
    {
        $base = (string) (config('filesystems.disks.public.url') ?: (rtrim((string) config('app.url'), '/') . '/storage'));
        return rtrim($base, '/') . '/' . ltrim($relativePath, '/');
    }
}
