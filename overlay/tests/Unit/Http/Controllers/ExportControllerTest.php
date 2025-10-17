<?php

namespace Tests\Unit\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\ExportController;
use App\Models\TranslationKey;
use App\Models\Locale;
use App\Models\TranslationValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ExportController();
    }

    public function test_export_returns_json_structure()
    {
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'welcome']);
        
        TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Welcome!'
        ]);

        $request = Request::create('/api/export', 'GET', ['locale' => 'en']);
        $response = $this->controller->export($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('test', $data);
        $this->assertEquals('Welcome!', $data['test']['welcome']);
    }

    public function test_export_handles_missing_locale()
    {
        $request = Request::create('/api/export', 'GET', ['locale' => 'nonexistent']);
        $response = $this->controller->export($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data);
    }

    public function test_export_uses_default_locale()
    {
        $request = Request::create('/api/export', 'GET');
        $response = $this->controller->export($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_export_filters_by_namespace()
    {
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        
        $key1 = TranslationKey::create(['namespace' => 'web', 'key' => 'welcome']);
        $key2 = TranslationKey::create(['namespace' => 'mobile', 'key' => 'welcome']);
        
        TranslationValue::create([
            'translation_key_id' => $key1->id,
            'locale_id' => $locale->id,
            'value' => 'Web Welcome'
        ]);
        
        TranslationValue::create([
            'translation_key_id' => $key2->id,
            'locale_id' => $locale->id,
            'value' => 'Mobile Welcome'
        ]);

        $request = Request::create('/api/export', 'GET', [
            'locale' => 'en',
            'namespace' => 'web'
        ]);
        
        $response = $this->controller->export($request);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('web', $data);
        $this->assertArrayNotHasKey('mobile', $data);
    }

    public function test_export_sets_etag_header()
    {
        $request = Request::create('/api/export', 'GET', ['locale' => 'en']);
        $response = $this->controller->export($request);

        $this->assertTrue($response->headers->has('ETag'));
        $this->assertTrue($response->headers->has('Cache-Control'));
    }

    public function test_export_handles_if_none_match()
    {
        $request = Request::create('/api/export', 'GET', ['locale' => 'en']);
        $response1 = $this->controller->export($request);
        $etag = $response1->headers->get('ETag');

        $request2 = Request::create('/api/export', 'GET', ['locale' => 'en'], [], [], [
            'HTTP_IF_NONE_MATCH' => $etag
        ]);
        
        $response2 = $this->controller->export($request2);
        $this->assertEquals(304, $response2->getStatusCode());
    }
}
