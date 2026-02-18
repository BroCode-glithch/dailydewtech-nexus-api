<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Messages;

class MessagesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Jane Doe', 'email' => 'jane.doe@example.com', 'subject' => 'Interested in a website', 'message' => 'Hello, I would like to get a quote for a new company website. Please let me know your rates and timeline.', 'status' => 'unread', 'created_at' => '2025-01-20 09:12:00'],
            ['name' => 'Michael Smith', 'email' => 'michael.smith@example.com', 'subject' => 'Bug report', 'message' => 'Hi team, I found an issue on the pricing page where the plan prices do not display on mobile.', 'status' => 'read', 'created_at' => '2025-01-18 14:45:00'],
            ['name' => 'Aisha Bello', 'email' => 'aisha.bello@example.com', 'subject' => 'Collaboration', 'message' => 'Hello, I am exploring collaboration opportunities on a fintech project. Can we schedule a call next week?', 'status' => 'unread', 'created_at' => '2025-01-12 11:30:00'],
        ];

        foreach ($items as $item) {
            Messages::updateOrCreate([
                'email' => $item['email'],
                'subject' => $item['subject']
            ], $item);
        }
    }
}
