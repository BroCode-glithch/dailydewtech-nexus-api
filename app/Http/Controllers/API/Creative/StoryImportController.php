<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Services\Creative\CreativeDocxImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoryImportController extends Controller
{
    public function __construct(private readonly CreativeDocxImportService $importService) {}

    public function importDocx(Request $request)
    {
        $payload = $request->validate([
            'file' => 'required|file|mimes:docx|max:10240',
            'story_title' => 'nullable|string|max:255',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'import_mode' => ['nullable', Rule::in(['single_chapter', 'split_by_headings'])],
            'language' => 'nullable|string|max:10',
            'visibility' => 'nullable|in:public,unlisted,private',
        ]);

        try {
            $result = $this->importService->import($request->user(), $payload['file'], $payload);

            return response()->json([
                'success' => true,
                'message' => 'Document parsed successfully',
                'data' => $result,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process DOCX import.',
                'errors' => [
                    'file' => ['Failed to parse document. Ensure the DOCX is valid and try again.'],
                ],
            ], 422);
        }
    }

    public function jobStatus(Request $request, string $jobId)
    {
        $log = $this->importService->getJobStatus($request->user(), $jobId);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Import job not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $log->import_reference,
                'status' => $log->status,
                'story' => $log->story,
                'warnings' => $log->warnings_json ?? [],
                'errors' => $log->errors_json ?? [],
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ],
        ]);
    }
}
