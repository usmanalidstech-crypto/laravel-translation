<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void {
Schema::create('api_tokens', function (Blueprint $t){ $t->id(); $t->string('name'); $t->string('token_hash',64)->unique(); $t->timestamps(); });
} public function down(): void { Schema::dropIfExists('api_tokens'); } };