<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Tag;
use App\Models\TranslationKey;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tag()
    {
        $tag = Tag::create(['name' => 'web']);

        $this->assertDatabaseHas('tags', ['name' => 'web']);
    }

    public function test_tag_has_unique_name()
    {
        Tag::create(['name' => 'web']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        Tag::create(['name' => 'web']);
    }

    public function test_tag_belongs_to_many_translation_keys()
    {
        $tag = Tag::create(['name' => 'web']);
        $key1 = TranslationKey::create(['namespace' => 'test', 'key' => 'key1']);
        $key2 = TranslationKey::create(['namespace' => 'test', 'key' => 'key2']);
        
        $tag->translationKeys()->attach([$key1->id, $key2->id]);

        $this->assertCount(2, $tag->translationKeys()->get());
        $this->assertTrue($tag->translationKeys()->where('translation_keys.id', $key1->id)->exists());
        $this->assertTrue($tag->translationKeys()->where('translation_keys.id', $key2->id)->exists());
    }

    public function test_tag_name_is_required()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Tag::create([]);
    }
}
