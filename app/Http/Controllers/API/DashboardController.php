<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Posts;
use App\Models\Messages;
use App\Models\Projects;
use App\Models\User;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterCampaign;

class DashboardController extends Controller
{
    /**
     * Public-facing highlights for frontend hero/about statistics.
     */
    public function publicHighlights(Request $request)
    {
        $publishedProjects = Projects::where('status', 'published')->count();
        $projectsDeliveredDisplay = $publishedProjects >= 10
            ? '10+'
            : (string) $publishedProjects;

        $startedYear = (int) config('app.company_started_year', (int) now()->year);
        $yearsOfExperience = max(0, (int) now()->year - $startedYear);

        $clientSatisfactionTarget = (int) config('app.client_satisfaction_target', 98);
        $supportAvailability = (string) config('app.support_availability', '24/7');

        return response()->json([
            'success' => true,
            'data' => [
                'highlights' => [
                    [
                        'key' => 'projects_delivered',
                        'label' => 'Projects Delivered',
                        'value' => $projectsDeliveredDisplay,
                        'raw_value' => $publishedProjects,
                        'unit' => null,
                        'source' => 'published_projects_count',
                    ],
                    [
                        'key' => 'client_satisfaction',
                        'label' => 'Client Satisfaction',
                        'value' => $clientSatisfactionTarget,
                        'raw_value' => $clientSatisfactionTarget,
                        'unit' => '%',
                        'source' => 'configured_service_target',
                    ],
                    [
                        'key' => 'support_availability',
                        'label' => 'Support Availability',
                        'value' => $supportAvailability,
                        'raw_value' => $supportAvailability,
                        'unit' => null,
                        'source' => 'configured_support_window',
                    ],
                    [
                        'key' => 'years_experience',
                        'label' => 'Years of Experience',
                        'value' => $yearsOfExperience,
                        'raw_value' => $yearsOfExperience,
                        'unit' => 'years',
                        'source' => 'derived_from_company_started_year',
                    ],
                ],
                'meta' => [
                    'company_started_year' => $startedYear,
                    'generated_at' => now()->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Return comprehensive admin dashboard statistics.
     */
    public function stats(Request $request)
    {
        // Post statistics
        $postsCount = Posts::count();
        $publishedPosts = Posts::where('status', 'published')->count();
        $draftPosts = Posts::where('status', 'draft')->count();
        $recentPosts = Posts::with('user:id,name,email')
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'slug', 'user_id', 'published_at', 'status', 'created_at']);

        // Message statistics
        $messagesCount = Messages::count();
        $unreadMessages = Messages::where('status', 'unread')->count();
        $readMessages = Messages::where('status', 'read')->count();
        $recentMessages = Messages::latest()
            ->limit(5)
            ->get(['id', 'name', 'email', 'subject', 'created_at', 'status']);

        // Project statistics
        $projectsCount = Projects::count();
        $publishedProjects = Projects::where('status', 'published')->count();
        $draftProjects = Projects::where('status', 'draft')->count();
        $recentProjects = Projects::latest()
            ->limit(5)
            ->get(['id', 'title', 'slug', 'status', 'created_at']);

        // User statistics
        $usersCount = User::count();
        $adminUsersCount = User::where('role', 'admin')->count();

        // Newsletter statistics
        $newsletterSubscribersTotal = NewsletterSubscriber::count();
        $newsletterSubscribersActive = NewsletterSubscriber::where('status', 'active')->count();
        $newsletterSubscribersUnsubscribed = NewsletterSubscriber::where('status', 'unsubscribed')->count();
        $newsletterCampaignsTotal = NewsletterCampaign::count();
        $newsletterCampaignsSent = NewsletterCampaign::where('status', 'sent')->count();
        $recentNewsletterCampaigns = NewsletterCampaign::latest()
            ->limit(5)
            ->get(['id', 'subject', 'status', 'total_recipients', 'sent_count', 'failed_count', 'sent_at', 'created_at']);

        // Activity summary (last 7 days)
        $lastWeek = now()->subDays(7);
        $newPostsThisWeek = Posts::where('created_at', '>=', $lastWeek)->count();
        $newMessagesThisWeek = Messages::where('created_at', '>=', $lastWeek)->count();
        $newProjectsThisWeek = Projects::where('created_at', '>=', $lastWeek)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'posts' => [
                    'total' => $postsCount,
                    'published' => $publishedPosts,
                    'drafts' => $draftPosts,
                    'recent' => $recentPosts,
                ],
                'messages' => [
                    'total' => $messagesCount,
                    'unread' => $unreadMessages,
                    'read' => $readMessages,
                    'recent' => $recentMessages,
                ],
                'projects' => [
                    'total' => $projectsCount,
                    'published' => $publishedProjects,
                    'drafts' => $draftProjects,
                    'recent' => $recentProjects,
                ],
                'users' => [
                    'total' => $usersCount,
                    'admins' => $adminUsersCount,
                ],
                'newsletter' => [
                    'subscribers_total' => $newsletterSubscribersTotal,
                    'subscribers_active' => $newsletterSubscribersActive,
                    'subscribers_unsubscribed' => $newsletterSubscribersUnsubscribed,
                    'campaigns_total' => $newsletterCampaignsTotal,
                    'campaigns_sent' => $newsletterCampaignsSent,
                    'recent_campaigns' => $recentNewsletterCampaigns,
                ],
                'activity' => [
                    'new_posts_this_week' => $newPostsThisWeek,
                    'new_messages_this_week' => $newMessagesThisWeek,
                    'new_projects_this_week' => $newProjectsThisWeek,
                ],
            ],
        ]);
    }
}
