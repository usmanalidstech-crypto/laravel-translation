<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void {
Schema::create('translation_keys', function (Blueprint $t){ $t->id(); $t->string('namespace',64)->default('default'); $t->string('key',191); $t->timestamps(); $t->unique(['namespace','key']); $t->index('key'); });
} public function down(): void { Schema::dropIfExists('translation_keys'); } };