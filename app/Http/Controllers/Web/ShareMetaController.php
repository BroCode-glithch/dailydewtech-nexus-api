<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Posts;
use App\Models\Projects;
use Illuminate\Support\Str;

class ShareMetaController extends Controller
{
    public function homepage()
    {
        $highlights = $this->getPublicHighlights();

        $title = (string) config('app.name', 'Daily Dew Tech');
        $description = "We deliver professional web development, software solutions, and digital innovation. "
            . "Trusted by clients with {$highlights['projects_delivered']} projects delivered.";

        $defaultImage = asset('/images/og-default.png');

        return response()->view('share.meta', [
            'title' => $title,
            'description' => $description,
            'image' => $defaultImage,
            'url' => url('/'),
            'frontendUrl' => rtrim((string) config('app.frontend_url'), '/'),
            'siteName' => $title,
            'type' => 'website',
        ]);
    }

    public function project(string $identifier)
    {
        $project = Projects::query()
            ->where('status', 'published')
            ->where(function ($query) use ($identifier) {
                $query->where('slug', $identifier);

                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();

        $title = $project->title;
        $description = Str::limit(strip_tags((string) $project->description), 200);
        $slug = (string) ($project->slug ?: $project->id);

        return response()->view('share.meta', [
            'title' => $title,
            'description' => $description,
            'image' => $this->resolveImageUrl($project->thumbnail),
            'url' => url('/share/projects/' . $slug),
            'frontendUrl' => rtrim((string) config('app.frontend_url'), '/') . '/projects/' . $project->id,
            'siteName' => (string) config('app.name', 'DailyDew Tech'),
            'type' => 'article',
        ]);
    }

    public function blog(string $identifier)
    {
        $post = Posts::query()
            ->where('status', 'published')
            ->where(function ($query) use ($identifier) {
                $query->where('slug', $identifier);

                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();

        $title = $post->title;
        $description = Str::limit(strip_tags((string) ($post->excerpt ?: $post->content)), 200);
        $slug = (string) ($post->slug ?: $post->id);

        return response()->view('share.meta', [
            'title' => $title,
            'description' => $description,
            'image' => $this->resolveImageUrl($post->cover_image),
            'url' => url('/share/blog/' . $slug),
            'frontendUrl' => rtrim((string) config('app.frontend_url'), '/') . '/blog/' . $post->id,
            'siteName' => (string) config('app.name', 'Daily Dew Tech'),
            'type' => 'article',
        ]);
    }

    private function resolveImageUrl(?string $image): ?string
    {
        if (!$image) {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        if (Str::startsWith($image, ['/storage/', '/'])) {
            return url($image);
        }

        if (Str::startsWith($image, 'storage/')) {
            return url('/' . $image);
        }

        return asset('storage/' . ltrim($image, '/'));
    }

    private function getPublicHighlights(): array
    {
        try {
            $publishedProjects = Projects::where('status', 'published')->count();
            $projectsDeliveredDisplay = $publishedProjects >= 10
                ? '10+'
                : (string) $publishedProjects;

            return [
                'projects_delivered' => $projectsDeliveredDisplay,
                'client_satisfaction' => (int) config('app.client_satisfaction_target', 98),
                'support_availability' => (string) config('app.support_availability', '24/7'),
            ];
        } catch (\Exception $e) {
            return [
                'projects_delivered' => '10+',
                'client_satisfaction' => 98,
                'support_availability' => '24/7',
            ];
        }
    }
}
