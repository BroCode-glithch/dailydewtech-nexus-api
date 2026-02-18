<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Projects;

class ProjectsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'E-Commerce Platform',
                'slug' => 'e-commerce-platform',
                'category' => 'Web Development',
                'description' => 'A full-featured online marketplace with real-time inventory management, payment integration, and admin dashboard.',
                'technologies' => json_encode(['React', 'Node.js', 'MongoDB', 'Stripe']),
                'thumbnail' => 'https://images.unsplash.com/photo-1557821552-17105176677c?w=800&h=600&fit=crop',
                'status' => 'published',
            ],
            [
                'title' => 'Healthcare Management System',
                'slug' => 'healthcare-management-system',
                'category' => 'Enterprise Software',
                'description' => 'Comprehensive patient management system with appointment scheduling, electronic health records, and telemedicine features.',
                'technologies' => json_encode(['Laravel', 'Vue.js', 'MySQL', 'WebRTC']),
                'thumbnail' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&h=600&fit=crop',
                'status' => 'published',
            ],
            [
                'title' => 'Mobile Banking App',
                'slug' => 'mobile-banking-app',
                'category' => 'Mobile Development',
                'description' => 'Secure mobile banking application with biometric authentication, transaction history, and bill payment features.',
                'technologies' => json_encode(['React Native', 'Firebase', 'REST API']),
                'thumbnail' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=800&h=600&fit=crop',
                'status' => 'published',
            ],
            [
                'title' => 'Real Estate Portal',
                'slug' => 'real-estate-portal',
                'category' => 'Web Development',
                'description' => 'Property listing platform with advanced search filters, virtual tours, and agent management system.',
                'technologies' => json_encode(['Next.js', 'PostgreSQL', 'Mapbox', 'AWS S3']),
                'thumbnail' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&h=600&fit=crop',
                'status' => 'published',
            ],
            [
                'title' => 'Learning Management System',
                'slug' => 'learning-management-system',
                'category' => 'Education Technology',
                'description' => 'Online learning platform with course management, video streaming, assessments, and progress tracking.',
                'technologies' => json_encode(['React', 'Django', 'Redis', 'PostgreSQL']),
                'thumbnail' => 'https://images.unsplash.com/photo-1501504905252-473c47e087f8?w=800&h=600&fit=crop',
                'status' => 'published',
            ],
            [
                'title' => 'Inventory Management System',
                'slug' => 'inventory-management-system',
                'category' => 'Enterprise Software',
                'description' => 'Cloud-based inventory tracking system with barcode scanning, stock alerts, and analytics dashboard.',
                'technologies' => json_encode(['Angular', 'Laravel', 'MySQL', 'Chart.js']),
                'thumbnail' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=800&h=600&fit=crop',
                'status' => 'published',
            ],
        ];

        foreach ($items as $item) {
            Projects::updateOrCreate(['slug' => $item['slug']], $item);
        }
    }
}
