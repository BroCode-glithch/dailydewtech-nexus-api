<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Story;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CreativeSeeder extends Seeder
{
    /**
     * Seed the creative module with demo data.
     */
    public function run(): void
    {
        // Create categories
        $categories = [
            ['name' => 'Fiction', 'slug' => 'fiction', 'description' => 'Fictional stories and narratives'],
            ['name' => 'Science Fiction', 'slug' => 'sci-fi', 'description' => 'Science fiction and futuristic tales'],
            ['name' => 'Fantasy', 'slug' => 'fantasy', 'description' => 'Fantasy and magical adventures'],
            ['name' => 'Mystery', 'slug' => 'mystery', 'description' => 'Mystery and thriller stories'],
            ['name' => 'Romance', 'slug' => 'romance', 'description' => 'Romance and love stories'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // Create tags
        $tags = [
            ['name' => 'Adventure', 'slug' => 'adventure'],
            ['name' => 'Drama', 'slug' => 'drama'],
            ['name' => 'Humor', 'slug' => 'humor'],
            ['name' => 'Action', 'slug' => 'action'],
            ['name' => 'Suspense', 'slug' => 'suspense'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(['slug' => $tag['slug']], $tag);
        }

        // Get or create an author (admin role)
        $author = User::firstOrCreate(
            ['email' => 'author@example.com'],
            [
                'name' => 'Demo Author',
                'username' => 'demo_author',
                'password' => bcrypt('password'),
                'role' => 'author',
                'status' => 'active',
            ]
        );

        // Create demo stories
        $story1 = Story::firstOrCreate(
            ['slug' => 'the-midnight-echo'],
            [
                'title' => 'The Midnight Echo',
                'slug' => 'the-midnight-echo',
                'summary' => 'A mysterious signal from deep space changes everything.',
                'description' => 'When a young astronomer detects an impossible signal from beyond our galaxy, she embarks on a journey that will challenge everything humanity believes about the universe.',
                'author_id' => $author->id,
                'language' => 'en',
                'status' => 'published',
                'visibility' => 'public',
                'is_featured' => true,
                'published_at' => now()->subDays(5),
            ]
        );

        // Attach categories and tags
        $story1->categories()->sync([Category::where('slug', 'sci-fi')->first()->id]);
        $story1->tags()->sync([
            Tag::where('slug', 'adventure')->first()->id,
            Tag::where('slug', 'suspense')->first()->id,
        ]);

        // Create chapters for story 1
        Chapter::firstOrCreate(
            ['story_id' => $story1->id, 'chapter_number' => 1],
            [
                'title' => 'First Contact',
                'slug' => 'first-contact',
                'content_html' => '<p>The observatory was silent except for the hum of cooling fans. Dr. Sarah Chen stared at her monitor, her coffee forgotten and cold.</p><p>"This can\'t be right," she whispered.</p><p>The signal pattern was too perfect, too deliberate. It wasn\'t random cosmic noise—it was communication.</p>',
                'excerpt' => 'The discovery that changed everything...',
                'word_count' => 156,
                'read_time_minutes' => 1,
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ]
        );

        Chapter::firstOrCreate(
            ['story_id' => $story1->id, 'chapter_number' => 2],
            [
                'title' => 'The Message',
                'slug' => 'the-message',
                'content_html' => '<p>Sarah worked through the night, running every algorithm she could think of. Each analysis confirmed the impossible.</p><p>The signal was repeating. Evolving. <em>Responding</em> to her attempts to decode it.</p><p>She reached for her phone with trembling hands. This was beyond her now.</p>',
                'excerpt' => 'Decoding the impossible signal...',
                'word_count' => 145,
                'read_time_minutes' => 1,
                'status' => 'published',
                'published_at' => now()->subDays(4),
            ]
        );

        // Create a second story
        $story2 = Story::firstOrCreate(
            ['slug' => 'beneath-the-oak'],
            [
                'title' => 'Beneath the Oak',
                'slug' => 'beneath-the-oak',
                'summary' => 'A forgotten journal reveals secrets buried for generations.',
                'description' => 'When Emma inherits her grandmother\'s estate, she discovers a journal that uncovers a family mystery stretching back to the 1920s.',
                'author_id' => $author->id,
                'language' => 'en',
                'status' => 'published',
                'visibility' => 'public',
                'is_featured' => false,
                'published_at' => now()->subDays(10),
            ]
        );

        $story2->categories()->sync([Category::where('slug', 'mystery')->first()->id]);
        $story2->tags()->sync([Tag::where('slug', 'drama')->first()->id]);

        Chapter::firstOrCreate(
            ['story_id' => $story2->id, 'chapter_number' => 1],
            [
                'title' => 'The Inheritance',
                'slug' => 'the-inheritance',
                'content_html' => '<p>The old house stood at the end of a winding dirt road, surrounded by ancient oaks.</p><p>Emma had never met her grandmother, but the lawyer\'s letter was clear: everything was hers now.</p><p>Inside, dust motes danced in afternoon sunlight filtering through lace curtains. The house felt frozen in time.</p>',
                'excerpt' => 'A house full of secrets awaits...',
                'word_count' => 128,
                'read_time_minutes' => 1,
                'status' => 'published',
                'published_at' => now()->subDays(10),
            ]
        );

        $this->command->info('✅ Creative demo data seeded successfully!');
        $this->command->info('📖 Created 2 stories with chapters');
        $this->command->info('👤 Demo author: author@example.com / password');
    }
}
