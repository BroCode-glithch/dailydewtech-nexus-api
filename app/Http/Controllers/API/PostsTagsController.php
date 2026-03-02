<?php

namespace App\Http\Controllers\API;

use App\Models\Posts;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PostsTagsController extends Controller
{
    public function index(Request $request)
    {
        $posts = Posts::query()->published()->whereNotNull('tags')->pluck('tags');
        $counts = [];

        foreach ($posts as $tags) {
            if (!is_array($tags)) {
                continue;
            }

            foreach ($tags as $tag) {
                $normalized = trim((string) $tag);
                if ($normalized === '') {
                    continue;
                }

                $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
            }
        }

        $payload = collect($counts)
            ->sortByDesc(fn($count) => $count)
            ->map(fn($count, $tag) => ['tag' => $tag, 'count' => $count])
            ->values();

        return response()->json(['success' => true, 'data' => $payload]);
    }
}
