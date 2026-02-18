<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterBroadcast;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AdminNewsletterController extends Controller
{
    /**
     * List subscribers with filters and pagination.
     */
    public function subscribers(Request $request)
    {
        $query = NewsletterSubscriber::query();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $perPage = (int) $request->input('per_page', 20);

        $subscribers = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $subscribers,
        ]);
    }

    /**
     * Update subscriber status manually from admin.
     */
    public function updateSubscriber(Request $request, $id)
    {
        $subscriber = NewsletterSubscriber::find($id);

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,unsubscribed',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $subscriber->update([
            'name' => $data['name'] ?? $subscriber->name,
            'status' => $data['status'],
            'unsubscribed_at' => $data['status'] === 'unsubscribed' ? now() : null,
            'subscribed_at' => $data['status'] === 'active' ? ($subscriber->subscribed_at ?? now()) : $subscriber->subscribed_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscriber updated successfully.',
            'data' => $subscriber,
        ]);
    }

    /**
     * List newsletter campaigns.
     */
    public function campaigns(Request $request)
    {
        $query = NewsletterCampaign::query();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('subject', 'like', "%{$search}%");
        }

        $campaigns = $query
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    /**
     * Send a new broadcast campaign to active subscribers.
     */
    public function sendBroadcast(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $campaign = NewsletterCampaign::create([
            'subject' => $data['subject'],
            'content' => $data['content'],
            'status' => 'sending',
            'created_by' => optional($request->user())->id,
            'meta' => $data['meta'] ?? null,
        ]);

        $subscribers = NewsletterSubscriber::active()->get();

        $total = $subscribers->count();
        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            if (!($subscriber instanceof NewsletterSubscriber)) {
                continue;
            }

            try {
                Mail::to($subscriber->email)->send(new NewsletterBroadcast(
                    $campaign->subject,
                    $campaign->content,
                    $subscriber
                ));

                NewsletterCampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'subscriber_id' => $subscriber->id,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $sent++;
            } catch (\Throwable $e) {
                NewsletterCampaignRecipient::create([
                    'campaign_id' => $campaign->id,
                    'subscriber_id' => $subscriber->id,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_at' => now(),
                ]);

                $failed++;
            }
        }

        $campaign->update([
            'total_recipients' => $total,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $failed > 0 ? 'failed' : 'sent',
            'sent_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Broadcast completed.',
            'data' => [
                'campaign_id' => $campaign->id,
                'total_recipients' => $total,
                'sent_count' => $sent,
                'failed_count' => $failed,
                'status' => $campaign->status,
            ],
        ]);
    }

    /**
     * Campaign details with recipient results.
     */
    public function campaignDetails($id)
    {
        $campaign = NewsletterCampaign::with(['recipients.subscriber'])
            ->find($id);

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }
}
