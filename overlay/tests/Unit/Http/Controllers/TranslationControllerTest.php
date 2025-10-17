<?php

namespace Tests\Unit\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\TranslationController;
use App\Models\TranslationKey;
use App\Models\Locale;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TranslationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TranslationController();
        
        // Create test token
        $this->token = hash_hmac('sha256', 'test-token', config('app.key'));
        \DB::table('api_tokens')->insert([
            'name' => 'test',
            'token_hash' => $this->token,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function test_store_creates_translation_key()
    {
        $request = Request::create('/api/keys', 'POST', [
            'namespace' => 'test',
            'key' => 'welcome.message',
            'values' => [
                ['locale' => 'en', 'value' => 'Welcome!'],
                ['locale' => 'fr', 'value' => 'Bienvenue!']
            ],
            'tags' => ['web', 'mobile']
        ]);

        Locale::create(['code' => 'en', 'name' => 'English']);
        Locale::create(['code' => 'fr', 'name' => 'French']);

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertDatabaseHas('translation_keys', [
            'namespace' => 'test',
            'key' => 'welcome.message'
        ]);
    }

    public function test_store_creates_missing_locales()
    {
        $request = Request::create('/api/keys', 'POST', [
            'key' => 'test.key',
            'values' => [
                ['locale' => 'es', 'value' => 'Hola!']
            ]
        ]);

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertDatabaseHas('locales', ['code' => 'es']);
    }

    public function test_store_creates_missing_tags()
    {
        $request = Request::create('/api/keys', 'POST', [
            'key' => 'test.key',
            'tags' => ['new-tag', 'another-tag']
        ]);

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertDatabaseHas('tags', ['name' => 'new-tag']);
        $this->assertDatabaseHas('tags', ['name' => 'another-tag']);
    }

    public function test_store_flushes_cache()
    {
        Cache::shouldReceive('tags')->with(['export'])->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $request = Request::create('/api/keys', 'POST', [
            'key' => 'test.key'
        ]);

        $this->controller->store($request);
    }

    public function test_update_modifies_existing_key()
    {
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'old.key']);
        
        $request = Request::create('/api/keys/1', 'PUT', [
            'namespace' => 'updated',
            'key' => 'new.key'
        ]);

        $response = $this->controller->update($request, $key->id);

        $this->assertEquals(200, $response->getStatusCode());
        $key->refresh();
        $this->assertEquals('updated', $key->namespace);
        $this->assertEquals('new.key', $key->key);
    }

    public function test_destroy_deletes_key()
    {
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'test.key']);
        
        $response = $this->controller->destroy($key->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertDatabaseMissing('translation_keys', ['id' => $key->id]);
    }
}
