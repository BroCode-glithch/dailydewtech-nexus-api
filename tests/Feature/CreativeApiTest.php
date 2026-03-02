<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreativeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_creative_stories_endpoint_returns_success(): void
    {
        $user = User::factory()->create();

        Story::create([
            'title' => 'Test Story',
            'slug' => 'test-story',
            'summary' => 'Summary',
            'author_id' => $user->id,
            'status' => 'published',
            'visibility' => 'public',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/creative/stories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_media_upload_requires_authentication(): void
    {
        $response = $this->postJson('/api/media/upload', []);

        $response->assertStatus(401);
    }

    public function test_media_upload_accepts_image_field_alias(): void
    {
        Storage::fake('public');
        User::factory()->create();
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/media/upload', [
                'image' => UploadedFile::fake()->image('cover.jpg', 1200, 630),
                'alt_text' => 'Story cover',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['media_id', 'url', 'thumbnail_url', 'media'],
            ]);
    }
}
