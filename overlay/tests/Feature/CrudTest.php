<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\TranslationKey;
use App\Models\Locale;
use App\Models\Tag;
use App\Models\TranslationValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CrudTest extends TestCase
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

        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'welcome.message']);
        $key->tags()->attach(Tag::whereIn('name', ['web', 'mobile'])->pluck('id'));

        foreach ($locales as $code) {
            TranslationValue::create([
                'translation_key_id' => $key->id,
                'locale_id' => Locale::where('code', $code)->first()->id,
                'value' => "Welcome in {$code}!"
            ]);
        }
    }

    public function test_index_returns_translations()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/keys');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'namespace',
                        'key',
                        'values' => [
                            '*' => ['id', 'value', 'locale']
                        ],
                        'tags' => [
                            '*' => ['id', 'name']
                        ]
                    ]
                ]
            ]);
    }

    public function test_index_filters_by_namespace()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/keys?namespace=test');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('test', $data[0]['namespace']);
    }

    public function test_index_filters_by_tag()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/keys?tag=web');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue(collect($data[0]['tags'])->pluck('name')->contains('web'));
    }

    public function test_index_filters_by_content()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/keys?content=Welcome');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_store_creates_new_translation()
    {
        $data = [
            'namespace' => 'new',
            'key' => 'hello.world',
            'values' => [
                ['locale' => 'en', 'value' => 'Hello World!'],
                ['locale' => 'fr', 'value' => 'Bonjour le monde!']
            ],
            'tags' => ['web']
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/keys', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'namespace',
                'key',
                'values',
                'tags'
            ]);

        $this->assertDatabaseHas('translation_keys', [
            'namespace' => 'new',
            'key' => 'hello.world'
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/keys', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_show_returns_specific_translation()
    {
        $key = TranslationKey::first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/keys/{$key->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'namespace',
                'key',
                'values',
                'tags'
            ]);
    }

    public function test_show_returns_404_for_nonexistent_key()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/keys/99999');

        $response->assertStatus(404);
    }

    public function test_update_modifies_existing_translation()
    {
        $key = TranslationKey::first();
        $data = [
            'namespace' => 'updated',
            'key' => 'updated.key',
            'values' => [
                ['locale' => 'en', 'value' => 'Updated Value!']
            ]
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/keys/{$key->id}", $data);

        $response->assertStatus(200);
        
        $key->refresh();
        $this->assertEquals('updated', $key->namespace);
        $this->assertEquals('updated.key', $key->key);
    }

    public function test_destroy_deletes_translation()
    {
        $key = TranslationKey::first();
        $keyId = $key->id;

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/keys/{$keyId}");

        $response->assertStatus(200)
            ->assertJson(['deleted' => true]);

        $this->assertDatabaseMissing('translation_keys', ['id' => $keyId]);
    }

    public function test_unauthorized_access_returns_401()
    {
        $response = $this->getJson('/api/keys');
        $response->assertStatus(401);
    }

    public function test_invalid_token_returns_401()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/keys');
        $response->assertStatus(401);
    }
}