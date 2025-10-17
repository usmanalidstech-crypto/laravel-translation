<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\TranslationKey;
use App\Models\TranslationValue;
use App\Models\Tag;
use App\Models\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TranslationKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_translation_key()
    {
        $key = TranslationKey::create([
            'namespace' => 'test',
            'key' => 'welcome.message'
        ]);

        $this->assertDatabaseHas('translation_keys', [
            'namespace' => 'test',
            'key' => 'welcome.message'
        ]);
    }

    public function test_translation_key_has_values_relationship()
    {
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'test.key']);
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        
        $value = TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Test Value'
        ]);

        $this->assertTrue($key->values->contains($value));
    }

    public function test_translation_key_has_tags_relationship()
    {
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'test.key']);
        $tag = Tag::create(['name' => 'web']);
        
        $key->tags()->attach($tag);

        $this->assertTrue($key->tags->contains($tag));
    }

    public function test_translation_key_uses_default_namespace()
    {
        $key = TranslationKey::create(['key' => 'test.key']);
        
        $this->assertEquals('default', $key->namespace);
    }

    public function test_translation_key_has_unique_constraint()
    {
        TranslationKey::create(['namespace' => 'test', 'key' => 'unique.key']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        TranslationKey::create(['namespace' => 'test', 'key' => 'unique.key']);
    }
}
