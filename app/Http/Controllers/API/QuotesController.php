<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;

class QuotesController extends Controller
{
    /**
     * Return and store a random inspirational quote (unique).
     */
    public function inspire(): JsonResponse
    {
        $fullQuote = Inspiring::quote();

        // Separate text and author (if any)
        if (str_contains($fullQuote, ' - ')) {
            [$text, $author] = explode(' - ', $fullQuote, 2);
        } else {
            $text = $fullQuote;
            $author = null;
        }

        $text = trim($text);
        $author = $author ? trim($author) : null;

        // Check if this quote already exists
        $existingQuote = Quote::where('text', $text)
            ->where('author', $author)
            ->first();

        if ($existingQuote) {
            return response()->json([
                'success' => true,
                'quote' => $existingQuote,
                'message' => 'Existing quote retrieved.',
            ]);
        }

        // Store if unique
        $quote = Quote::create([
            'text' => $text,
            'author' => $author,
        ]);

        return response()->json([
            'success' => true,
            'quote' => $quote,
            'message' => 'New quote generated and saved.',
        ]);
    }

    /**
     * Return all stored quotes with pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $quotes = Quote::latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $quotes,
        ]);
    }

    /**
     * Return a random stored quote (without regenerating).
     */
    public function random(): JsonResponse
    {
        $quote = Quote::inRandomOrder()->first();

        if (!$quote) {
            return response()->json([
                'success' => false,
                'message' => 'No quotes available yet.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'quote' => $quote,
        ]);
    }
}
