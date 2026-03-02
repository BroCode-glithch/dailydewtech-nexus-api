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
    private const TEMPLATE_NONE = 'none';
    private const TEMPLATE_CLASSIC = 'classic';
    private const TEMPLATE_ANNOUNCEMENT = 'announcement';
    private const TEMPLATE_PRODUCT_UPDATE = 'product_update';

    /**
     * List available broadcast templates for rich editor UI.
     */
    public function templates()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'content_formats' => ['plain_text', 'html'],
                'templates' => $this->availableTemplates(),
            ],
        ]);
    }

    /**
     * Render a campaign preview for admin UI.
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'content_format' => 'nullable|in:plain_text,html',
            'template' => 'nullable|in:none,classic,announcement,product_update',
            'preview_subscriber_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $prepared = $this->prepareBroadcastContent($data);

        $previewSubscriber = new NewsletterSubscriber([
            'name' => $data['preview_subscriber_name'] ?? 'Subscriber',
            'email' => 'preview@example.com',
            'unsubscribe_token' => 'preview-token',
            'status' => 'active',
        ]);

        $emailPreviewHtml = view('emails.newsletter_broadcast', [
            'subjectLine' => $data['subject'],
            'contentBody' => $prepared['rendered_content'],
            'subscriber' => $previewSubscriber,
            'contentFormat' => $prepared['content_format'],
        ])->render();

        return response()->json([
            'success' => true,
            'data' => [
                'subject' => $data['subject'],
                'content_format' => $prepared['content_format'],
                'template' => $prepared['template'],
                'rendered_content' => $prepared['rendered_content'],
                'email_preview_html' => $emailPreviewHtml,
            ],
        ]);
    }

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
            'content_format' => 'nullable|in:plain_text,html',
            'template' => 'nullable|in:none,classic,announcement,product_update',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $prepared = $this->prepareBroadcastContent($data);

        $meta = array_merge($data['meta'] ?? [], [
            'content_format' => $prepared['content_format'],
            'template' => $prepared['template'],
            'rendered_content' => $prepared['rendered_content'],
        ]);

        $campaign = NewsletterCampaign::create([
            'subject' => $data['subject'],
            'content' => $data['content'],
            'status' => 'sending',
            'created_by' => optional($request->user())->id,
            'meta' => $meta,
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
                $mail = (new NewsletterBroadcast(
                    $campaign->subject,
                    $prepared['rendered_content'],
                    $subscriber
                ))->setContentFormat($prepared['content_format']);

                Mail::to($subscriber->email)->send($mail);

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
                'content_format' => $prepared['content_format'],
                'template' => $prepared['template'],
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

    private function availableTemplates(): array
    {
        return [
            [
                'key' => self::TEMPLATE_NONE,
                'name' => 'No Template',
                'description' => 'Use only editor content with no extra wrapper.',
            ],
            [
                'key' => self::TEMPLATE_CLASSIC,
                'name' => 'Classic',
                'description' => 'Simple clean layout for general newsletters.',
            ],
            [
                'key' => self::TEMPLATE_ANNOUNCEMENT,
                'name' => 'Announcement',
                'description' => 'Bold announcement style with clear header section.',
            ],
            [
                'key' => self::TEMPLATE_PRODUCT_UPDATE,
                'name' => 'Product Update',
                'description' => 'Sectioned layout for releases, fixes, and highlights.',
            ],
        ];
    }

    private function prepareBroadcastContent(array $data): array
    {
        $subject = trim((string) ($data['subject'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        $contentFormat = (string) ($data['content_format'] ?? 'plain_text');
        $template = (string) ($data['template'] ?? self::TEMPLATE_CLASSIC);

        if ($contentFormat === 'html') {
            $safeContent = $this->sanitizeHtml($content);
            $renderedContent = $this->applyHtmlTemplate($subject, $safeContent, $template);

            return [
                'content_format' => 'html',
                'template' => $template,
                'rendered_content' => $renderedContent,
            ];
        }

        $renderedText = $this->applyTextTemplate($subject, $content, $template);

        return [
            'content_format' => 'plain_text',
            'template' => $template,
            'rendered_content' => $renderedText,
        ];
    }

    private function applyTextTemplate(string $subject, string $content, string $template): string
    {
        if ($template === self::TEMPLATE_NONE) {
            return $content;
        }

        if ($template === self::TEMPLATE_ANNOUNCEMENT) {
            return "{$subject}\n\n{$content}\n\nThank you for staying connected with us.";
        }

        if ($template === self::TEMPLATE_PRODUCT_UPDATE) {
            return "{$subject}\n\n{$content}\n\nNeed help? Reply to this message and our team will assist you.";
        }

        return "{$content}\n\n— Daily Dew Tech Team";
    }

    private function applyHtmlTemplate(string $subject, string $content, string $template): string
    {
        if ($template === self::TEMPLATE_NONE) {
            return $content;
        }

        if ($template === self::TEMPLATE_ANNOUNCEMENT) {
            return '<div style="padding:18px;border-radius:8px;background:#0f172a;color:#ffffff;margin-bottom:16px;">'
                . '<h2 style="margin:0 0 8px 0;">' . e($subject) . '</h2>'
                . '<p style="margin:0;opacity:.9;">Important update from Daily Dew Tech</p>'
                . '</div>'
                . '<div>' . $content . '</div>';
        }

        if ($template === self::TEMPLATE_PRODUCT_UPDATE) {
            return '<div style="padding:14px 16px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;margin-bottom:16px;">'
                . '<strong style="display:block;margin-bottom:4px;">Product Update</strong>'
                . '<span style="font-size:13px;color:#475569;">' . e($subject) . '</span>'
                . '</div>'
                . '<div>' . $content . '</div>';
        }

        return '<div style="padding:16px;border:1px solid #e5e7eb;border-radius:8px;">' . $content . '</div>';
    }

    private function sanitizeHtml(string $html): string
    {
        $cleaned = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html) ?? $html;
        $cleaned = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace("/on[a-z]+\s*=\s*'[^']*'/i", '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/javascript\s*:/i', '', $cleaned) ?? $cleaned;

        return trim($cleaned);
    }
}
