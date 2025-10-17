<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\TranslationKey;
use App\Models\Locale;
use App\Models\Tag;
use App\Models\TranslationValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test token
        $this->token = 'test-token-' . uniqid();
        $hash = hash_hmac('sha256', $this->token, config('app.key'));
        DB::table('api_tokens')->insert([
            'name' => 'test',
            'token_hash' => $hash,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Create test data
        $this->createTestData();
    }

    private function createTestData()
    {
        $locales = ['en', 'fr', 'es'];
        foreach ($locales as $code) {
            Locale::create(['code' => $code, 'name' => ucfirst($code)]);
        }

        $tags = ['web', 'mobile', 'desktop'];
        foreach ($tags as $name) {
            Tag::create(['name' => $name]);
        }

        // Create multiple translation keys
        $keys = [
            ['namespace' => 'web', 'key' => 'welcome', 'tags' => ['web']],
            ['namespace' => 'mobile', 'key' => 'welcome', 'tags' => ['mobile']],
            ['namespace' => 'common', 'key' => 'hello', 'tags' => ['web', 'mobile']],
        ];

        foreach ($keys as $keyData) {
            $key = TranslationKey::create([
                'namespace' => $keyData['namespace'],
                'key' => $keyData['key']
            ]);
            
            $key->tags()->attach(Tag::whereIn('name', $keyData['tags'])->pluck('id'));

            foreach ($locales as $code) {
                TranslationValue::create([
                    'translation_key_id' => $key->id,
                    'locale_id' => Locale::where('code', $code)->first()->id,
                    'value' => "{$keyData['key']} in {$code} for {$keyData['namespace']}"
                ]);
            }
        }
    }

    public function test_export_returns_json_structure()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'web' => ['welcome'],
                'mobile' => ['welcome'],
                'common' => ['hello']
            ]);
    }

    public function test_export_uses_default_locale()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('web', $data);
    }

    public function test_export_filters_by_namespace()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en&namespace=web');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('web', $data);
        $this->assertArrayNotHasKey('mobile', $data);
        $this->assertArrayNotHasKey('common', $data);
    }

    public function test_export_filters_by_tags()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en&tags=web');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('web', $data);
        $this->assertArrayHasKey('common', $data);
        $this->assertArrayNotHasKey('mobile', $data);
    }

    public function test_export_filters_by_multiple_tags()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en&tags=web,mobile');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('web', $data);
        $this->assertArrayHasKey('mobile', $data);
        $this->assertArrayHasKey('common', $data);
    }

    public function test_export_handles_missing_locale()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=nonexistent');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEmpty($data);
    }

    public function test_export_sets_cache_headers()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en');

        $response->assertStatus(200)
            ->assertHeader('ETag')
            ->assertHeader('Cache-Control', 'public, max-age=60');
    }

    public function test_export_handles_if_none_match()
    {
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en');
        
        $etag = $response1->headers->get('ETag');

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->withHeaders(['If-None-Match' => $etag])
            ->getJson('/api/export?locale=en');

        $response2->assertStatus(304);
    }

    public function test_export_uses_caching()
    {
        Cache::shouldReceive('tags')->with(['export'])->andReturnSelf();
        Cache::shouldReceive('remember')->once()->andReturn(['test' => 'data']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en');

        $response->assertStatus(200);
    }

    public function test_export_unauthorized_access_returns_401()
    {
        $response = $this->getJson('/api/export?locale=en');
        $response->assertStatus(401);
    }

    public function test_export_invalid_token_returns_401()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/export?locale=en');
        $response->assertStatus(401);
    }

    public function test_export_performance_with_large_dataset()
    {
        // Create a small dataset for CI to avoid OOM while still asserting performance behavior
        $this->createLargeDataset(100); // keys

        $startTime = microtime(true);
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, 'Export should complete in less than 500ms');
    }

    private function createLargeDataset($count)
    {
        $locale = Locale::where('code', 'en')->first();
        $tag = Tag::where('name', 'web')->first();

        for ($i = 0; $i < $count; $i++) {
            $key = TranslationKey::create([
                'namespace' => 'test',
                'key' => "key_{$i}"
            ]);
            
            $key->tags()->attach($tag->id);
            
            TranslationValue::create([
                'translation_key_id' => $key->id,
                'locale_id' => $locale->id,
                'value' => "Value for key {$i}"
            ]);
        }
    }
}