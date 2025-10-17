<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Support\Facades\DB;
class TokenAuth {
  public function handle($request, Closure $next){
    $raw=$request->bearerToken();
    if(!$raw) return response()->json(['message'=>'Unauthorized'],401);
    $hash=hash_hmac('sha256',$raw,config('app.key'));
    $ok=DB::table('api_tokens')->where('token_hash',$hash)->exists();
    if(!$ok) return response()->json(['message'=>'Unauthorized'],401);
    return $next($request);
  }
}