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

class PerformanceTest extends TestCase
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
    }

    public function test_crud_endpoints_performance_under_200ms()
    {
        $this->createLargeDataset(1000);

        // Test index endpoint performance
        $startTime = microtime(true);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/keys');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, 'Index endpoint should respond in less than 200ms');

        // Test show endpoint performance
        $key = TranslationKey::first();
        $startTime = microtime(true);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/keys/{$key->id}");
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, 'Show endpoint should respond in less than 200ms');
    }

    public function test_export_performance_under_500ms_with_large_dataset()
    {
        $this->createLargeDataset(1000); // 1k keys = ~5k translation values (reduced for CI)

        $startTime = microtime(true);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, 'Export endpoint should respond in less than 500ms with large dataset');
    }

    public function test_search_performance_with_large_dataset()
    {
        $this->createLargeDataset(1000);

        // Test search by content performance
        $startTime = microtime(true);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/keys?content=test');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, 'Search by content should respond in less than 200ms');

        // Test search by tag performance
        $startTime = microtime(true);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/keys?tag=web');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, 'Search by tag should respond in less than 200ms');
    }

    public function test_bulk_operations_performance()
    {
        $this->createTestLocalesAndTags();

        // Test bulk creation performance
        $startTime = microtime(true);
        
        $data = [
            'namespace' => 'bulk',
            'key' => 'bulk.test',
            'values' => [
                ['locale' => 'en', 'value' => 'Bulk Test EN'],
                ['locale' => 'fr', 'value' => 'Bulk Test FR'],
                ['locale' => 'es', 'value' => 'Bulk Test ES']
            ],
            'tags' => ['web', 'mobile']
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/keys', $data);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(201);
        $this->assertLessThan(200, $responseTime, 'Bulk creation should complete in less than 200ms');
    }

    public function test_cache_performance_improvement()
    {
        $this->createLargeDataset(1000);

        // First request (cache miss)
        $startTime = microtime(true);
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en');
        $endTime = microtime(true);
        $firstRequestTime = ($endTime - $startTime) * 1000;

        // Second request (cache hit)
        $startTime = microtime(true);
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/export?locale=en');
        $endTime = microtime(true);
        $secondRequestTime = ($endTime - $startTime) * 1000;

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Cache hit should be significantly faster
        $this->assertLessThan($firstRequestTime, $secondRequestTime, 'Cached request should be faster than cache miss');
    }

    public function test_memory_usage_with_large_dataset()
    {
        $initialMemory = memory_get_usage();
        
        $this->createLargeDataset(1000);
        
        $memoryAfterCreation = memory_get_usage();
        $memoryUsed = ($memoryAfterCreation - $initialMemory) / 1024 / 1024; // MB
        
        // Should not use more than 20MB for 1k records
        $this->assertLessThan(20, $memoryUsed, 'Memory usage should be reasonable for large datasets');
    }

    private function createLargeDataset($keyCount)
    {
        $this->createTestLocalesAndTags();

        $locales = Locale::all();
        $tags = Tag::all();

        // Use database transactions for better performance
        DB::transaction(function () use ($keyCount, $locales, $tags) {
            $chunkSize = 500;
            
            for ($i = 0; $i < $keyCount; $i += $chunkSize) {
                $keys = [];
                $values = [];
                $keyTags = [];
                
                $currentChunk = min($chunkSize, $keyCount - $i);
                
                for ($j = 0; $j < $currentChunk; $j++) {
                    $keyId = $i + $j + 1;
                    $namespace = $this->getRandomNamespace();
                    $key = "key_{$keyId}";
                    
                    $keys[] = [
                        'id' => $keyId,
                        'namespace' => $namespace,
                        'key' => $key,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    
                    // Create values for all locales
                    foreach ($locales as $locale) {
                        $values[] = [
                            'translation_key_id' => $keyId,
                            'locale_id' => $locale->id,
                            'value' => "Value for {$key} in {$locale->code}",
                            'version' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    
                    // Attach random tags
                    $randomTags = $tags->random(rand(1, 3));
                    foreach ($randomTags as $tag) {
                        $keyTags[] = [
                            'translation_key_id' => $keyId,
                            'tag_id' => $tag->id
                        ];
                    }
                }
                
                // Bulk insert
                DB::table('translation_keys')->insert($keys);
                DB::table('translation_values')->insert($values);
                DB::table('key_tag')->insert($keyTags);
            }
        });
    }

    private function createTestLocalesAndTags()
    {
        $locales = ['en', 'fr', 'es', 'de', 'ar'];
        foreach ($locales as $code) {
            Locale::create(['code' => $code, 'name' => ucfirst($code)]);
        }

        $tags = ['web', 'mobile', 'desktop', 'admin', 'public'];
        foreach ($tags as $name) {
            Tag::create(['name' => $name]);
        }
    }

    private function getRandomNamespace()
    {
        $namespaces = ['web', 'mobile', 'admin', 'common', 'api'];
        return $namespaces[array_rand($namespaces)];
    }
}
