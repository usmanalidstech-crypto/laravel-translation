<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\TranslationValue;
use App\Models\TranslationKey;
use App\Models\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TranslationValueTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_translation_value()
    {
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'test.key']);
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        
        $value = TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Hello World',
            'version' => 1
        ]);

        $this->assertDatabaseHas('translation_values', [
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Hello World',
            'version' => 1
        ]);
    }

    public function test_translation_value_belongs_to_key()
    {
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'test.key']);
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        
        $value = TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Test Value'
        ]);

        $this->assertEquals($key->id, $value->key->id);
    }

    public function test_translation_value_belongs_to_locale()
    {
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'test.key']);
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        
        $value = TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Test Value'
        ]);

        $this->assertEquals($locale->id, $value->locale->id);
    }

    public function test_translation_value_has_unique_constraint()
    {
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'test.key']);
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        
        TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'First Value'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Second Value'
        ]);
    }

    public function test_translation_value_has_default_version()
    {
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'test.key']);
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        
        $value = TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Test Value'
        ]);

        $this->assertEquals(1, $value->version);
    }
}
