<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void {
Schema::create('translation_values', function (Blueprint $t){ $t->id(); $t->foreignId('translation_key_id')->constrained()->cascadeOnDelete(); $t->foreignId('locale_id')->constrained()->cascadeOnDelete(); $t->longText('value'); $t->unsignedBigInteger('version')->default(1); $t->timestamps(); $t->unique(['translation_key_id','locale_id']); $t->index(['locale_id','translation_key_id']); });
} public function down(): void { Schema::dropIfExists('translation_values'); } };