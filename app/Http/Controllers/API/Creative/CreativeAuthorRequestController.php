<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\AuthorRequest;
use App\Models\PolicyVersion;
use App\Models\User;
use App\Notifications\NewAuthorRequestNotification;
use Illuminate\Http\Request;

class CreativeAuthorRequestController extends Controller
{
    public function show(Request $request)
    {
        $requestRow = AuthorRequest::where('user_id', $request->user()->id)->latest()->first();

        return response()->json([
            'success' => true,
            'data' => $requestRow,
        ]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'bio' => 'required|string|max:2000',
            'sample_link' => 'required|string|max:500',
            'accepted_terms' => 'accepted',
            'accepted_privacy' => 'accepted',
            'accepted_ip_policy' => 'accepted',
            'accepted_community_guidelines' => 'accepted',
        ]);

        $policyKeys = [
            'terms',
            'privacy',
            'ip_policy',
            'community_guidelines',
        ];

        $activePolicies = PolicyVersion::query()
            ->active()
            ->whereIn('policy_key', $policyKeys)
            ->get()
            ->keyBy('policy_key');

        if ($activePolicies->count() !== count($policyKeys)) {
            return response()->json([
                'success' => false,
                'message' => 'Policy versions are not configured. Please try again later.',
            ], 422);
        }

        $existing = AuthorRequest::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending author request.',
                'data' => $existing,
            ], 409);
        }

        $requestRow = AuthorRequest::create([
            'user_id' => $request->user()->id,
            'bio' => $payload['bio'],
            'sample_link' => $payload['sample_link'],
            'status' => 'pending',
            'accepted_terms_version' => $activePolicies['terms']->version,
            'accepted_privacy_version' => $activePolicies['privacy']->version,
            'accepted_ip_policy_version' => $activePolicies['ip_policy']->version,
            'accepted_community_guidelines_version' => $activePolicies['community_guidelines']->version,
            'accepted_at' => now(),
            'accepted_ip' => $request->ip(),
            'accepted_user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        foreach ($admins as $admin) {
            $admin->notify(new NewAuthorRequestNotification($requestRow));
        }

        return response()->json([
            'success' => true,
            'message' => 'Author request submitted successfully.',
            'data' => $requestRow,
        ], 201);
    }
}
