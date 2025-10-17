<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void {
Schema::create('tags', function (Blueprint $t){ $t->id(); $t->string('name',64)->unique(); $t->timestamps(); });
Schema::create('key_tag', function (Blueprint $t){ $t->foreignId('translation_key_id')->constrained()->cascadeOnDelete(); $t->foreignId('tag_id')->constrained()->cascadeOnDelete(); $t->primary(['translation_key_id','tag_id']); $t->index(['tag_id','translation_key_id']); });
} public function down(): void { Schema::dropIfExists('key_tag'); Schema::dropIfExists('tags'); } };