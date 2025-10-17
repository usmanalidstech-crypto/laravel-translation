<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\ExportController;
use App\Http\Middleware\TokenAuth;
use App\Http\Middleware\CdnHeaders;

// Register middleware aliases
Route::middleware([TokenAuth::class, CdnHeaders::class])->group(function(){
  Route::post('/keys',[TranslationController::class,'store']);
  Route::get('/keys',[TranslationController::class,'index']);
  Route::get('/keys/{id}',[TranslationController::class,'show']);
  Route::put('/keys/{id}',[TranslationController::class,'update']);
  Route::delete('/keys/{id}',[TranslationController::class,'destroy']);
  Route::get('/export',[ExportController::class,'export']);
});

// Health check endpoint (no auth required)
Route::get('/health', fn() => response()->json(['ok'=>true]));