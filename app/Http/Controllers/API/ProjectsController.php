<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Projects;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectsController extends Controller
{
    /**
     * Display a listing of projects with pagination and filters.
     */
    public function index(Request $request)
    {
        $query = Projects::query();

        // Filter by status (default to published for public routes)
        $status = $request->input('status', 'published');
        if ($status && in_array($status, ['draft', 'published'])) {
            $query->where('status', $status);
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Search in title or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $projects = $query->paginate($perPage);
        $projects->getCollection()->transform(function ($project) {
            return $this->formatProjectPayload($project);
        });

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'thumbnail' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string|max:50',
            'link' => 'nullable|url|max:500',
            'status' => 'nullable|string|in:draft,published',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();
        if (isset($validated['description'])) {
            $validated['description'] = RichText::sanitize($validated['description']);
        }

        // Ensure technologies is properly encoded
        if (isset($validated['technologies'])) {
            $validated['technologies'] = json_encode($validated['technologies']);
        }

        $project = Projects::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'data' => $this->formatProjectPayload($project)
        ], 201);
    }

    public function show(string $id)
    {
        $project = Projects::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatProjectPayload($project)
        ]);
    }

    public function related($id)
    {
        $project = Projects::findOrFail($id);
        if (!$project->category) {
            return response()->json(['data' => []]);
        }
        $related = Projects::where('category', $project->category)
            ->where('id', '!=', $project->id)
            ->latest()
            ->take(6)
            ->get();
        return response()->json([
            'data' => $related->map(function ($relatedProject) {
                return $this->formatProjectPayload($relatedProject);
            })->values(),
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, string $id)
    {
        $project = Projects::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'thumbnail' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string|max:50',
            'link' => 'nullable|url|max:500',
            'status' => 'nullable|string|in:draft,published',
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . uniqid();
        }
        if (isset($validated['description'])) {
            $validated['description'] = RichText::sanitize($validated['description']);
        }

        // Ensure technologies is properly encoded
        if (isset($validated['technologies'])) {
            $validated['technologies'] = json_encode($validated['technologies']);
        }

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data' => $this->formatProjectPayload($project)
        ]);
    }

    public function destroy(string $id)
    {
        $project = Projects::findOrFail($id);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.'
        ]);
    }

    private function formatProjectPayload($project): array
    {
        $payload = $project->toArray();
        $payload['description_html'] = $project->description;
        $payload['description_text'] = RichText::toPlainText($project->description, 2000) ?? '';

        return $payload;
    }
}
