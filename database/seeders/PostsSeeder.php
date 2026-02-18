<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Posts;
use App\Models\User;
use Illuminate\Support\Str;

class PostsSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $items = [
            [
                'title' => 'The Future of Web Development in 2025',
                'slug' => 'the-future-of-web-development-in-2025',
                'excerpt' => 'Explore the latest trends and technologies shaping the future of web development, from AI integration to WebAssembly.',
                'content' => 'Full article content for The Future of Web Development in 2025. Discusses AI, WebAssembly, and emerging tools.',
                'cover_image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800&h=600&fit=crop',
                'tags' => json_encode(['web', 'ai', 'wasm']),
                'status' => 'published',
                'published_at' => '2025-01-15 00:00:00',
            ],
            [
                'title' => 'Building Scalable Cloud Applications',
                'slug' => 'building-scalable-cloud-applications',
                'excerpt' => 'Learn best practices for designing and deploying cloud-native applications that can scale with your business.',
                'content' => 'Full article content for Building Scalable Cloud Applications. Covers architectures, patterns, and best practices.',
                'cover_image' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&h=600&fit=crop',
                'tags' => json_encode(['cloud', 'scaling', 'devops']),
                'status' => 'published',
                'published_at' => '2025-01-10 00:00:00',
            ],
            [
                'title' => 'Cybersecurity Best Practices for Businesses',
                'slug' => 'cybersecurity-best-practices-for-businesses',
                'excerpt' => 'Essential security measures every business should implement to protect their digital assets and customer data.',
                'content' => 'Full article content for Cybersecurity Best Practices for Businesses. Covers security posture, controls, and incident response.',
                'cover_image' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800&h=600&fit=crop',
                'tags' => json_encode(['security', 'compliance']),
                'status' => 'published',
                'published_at' => '2025-01-05 00:00:00',
            ],
            [
                'title' => 'Mobile App Development: Native vs Cross-Platform',
                'slug' => 'mobile-app-development-native-vs-cross-platform',
                'excerpt' => 'A comprehensive comparison of native and cross-platform mobile development approaches to help you make the right choice.',
                'content' => 'Full article content for Mobile App Development: Native vs Cross-Platform. Compares tools, performance, and costs.',
                'cover_image' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=800&h=600&fit=crop',
                'tags' => json_encode(['mobile', 'native', 'cross-platform']),
                'status' => 'published',
                'published_at' => '2024-12-28 00:00:00',
            ],
            [
                'title' => 'AI and Machine Learning in Business',
                'slug' => 'ai-and-machine-learning-in-business',
                'excerpt' => 'How artificial intelligence and machine learning are transforming business operations and decision-making.',
                'content' => 'Full article content for AI and Machine Learning in Business. Use cases, adoption patterns, and ROI.',
                'cover_image' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&h=600&fit=crop',
                'tags' => json_encode(['ai', 'ml', 'business']),
                'status' => 'published',
                'published_at' => '2024-12-20 00:00:00',
            ],
            [
                'title' => 'Database Optimization Techniques',
                'slug' => 'database-optimization-techniques',
                'excerpt' => 'Advanced strategies for optimizing database performance and ensuring efficient data management at scale.',
                'content' => 'Full article content for Database Optimization Techniques. Indexing, query tuning, and schema design.',
                'cover_image' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=800&h=600&fit=crop',
                'tags' => json_encode(['database', 'performance']),
                'status' => 'published',
                'published_at' => '2024-12-15 00:00:00',
            ],
        ];

        foreach ($items as $item) {
            Posts::updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, [
                    'user_id' => $user ? $user->id : null,
                ])
            );
        }
    }
}
