<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Foundation\Inspiring;
use App\Models\Quote;

class QuotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate 15 inspirational quotes
        for ($i = 0; $i < 15; $i++) {
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

            // Create quote if it doesn't exist
            Quote::firstOrCreate(
                ['text' => $text],
                ['author' => $author]
            );
        }

        $count = Quote::count();
        $this->command->info("✅ Quotes seeded successfully! Total: {$count}");
    }
}
