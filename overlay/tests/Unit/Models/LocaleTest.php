<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Locale;
use App\Models\TranslationValue;
use App\Models\TranslationKey;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_locale()
    {
        $locale = Locale::create([
            'code' => 'en',
            'name' => 'English'
        ]);

        $this->assertDatabaseHas('locales', [
            'code' => 'en',
            'name' => 'English'
        ]);
    }

    public function test_locale_has_unique_code()
    {
        Locale::create(['code' => 'en', 'name' => 'English']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        Locale::create(['code' => 'en', 'name' => 'English Duplicate']);
    }

    public function test_locale_has_translation_values()
    {
        $locale = Locale::create(['code' => 'en', 'name' => 'English']);
        $key = TranslationKey::create(['namespace' => 'test', 'key' => 'test.key']);
        
        $value = TranslationValue::create([
            'translation_key_id' => $key->id,
            'locale_id' => $locale->id,
            'value' => 'Test Value'
        ]);

        $this->assertTrue($locale->translationValues()->where('id', $value->id)->exists());
    }

    public function test_locale_name_can_be_null()
    {
        $locale = Locale::create(['code' => 'xx']);
        
        $this->assertNull($locale->name);
        $this->assertEquals('xx', $locale->code);
    }
}
