<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Public subscribe endpoint.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $subscriber = \App\Models\NewsletterSubscriber::where('email', $data['email'])->first();

        if ($subscriber) {
            $subscriber->update([
                'name' => $data['name'] ?? $subscriber->name,
                'status' => 'active',
                'unsubscribed_at' => null,
                'source' => $data['source'] ?? $subscriber->source,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed. Subscription has been refreshed.',
                'data' => [
                    'email' => $subscriber->email,
                    'status' => $subscriber->status,
                ],
            ]);
        }

        $subscriber = \App\Models\NewsletterSubscriber::create([
            'email' => $data['email'],
            'name' => $data['name'] ?? null,
            'status' => 'active',
            'source' => $data['source'] ?? 'website',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscribed successfully.',
            'data' => [
                'email' => $subscriber->email,
                'status' => $subscriber->status,
            ],
        ], 201);
    }

    /**
     * Public unsubscribe endpoint (by email + token).
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'token' => 'required|string|min:20|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $subscriber = \App\Models\NewsletterSubscriber::where('email', $data['email'])
            ->where('unsubscribe_token', $data['token'])
            ->first();

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid unsubscribe request.',
            ], 404);
        }

        $subscriber->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'You have been unsubscribed successfully.',
        ]);
    }
}
