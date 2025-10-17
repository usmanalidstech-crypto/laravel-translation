<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\TranslationKey;
use App\Models\Locale;
class ExportController extends Controller {
  public function export(Request $r){
    $locale=$r->query('locale','en');
    $namespace=$r->query('namespace');
    $tags=collect(explode(',',(string)$r->query('tags')))->filter()->values();
    $cacheKey="export:{$locale}:{$namespace}:".md5($tags->join(','));
    $payload=Cache::tags(['export'])->remember($cacheKey,300,function() use($locale,$namespace,$tags){
      $loc=Locale::firstWhere('code',$locale); if(!$loc) return [];
      $keys=TranslationKey::query()
        ->when($namespace,fn($q)=>$q->where('namespace',$namespace))
        ->when($tags->isNotEmpty(),fn($q)=>$q->whereHas('tags',fn($t)=>$t->whereIn('name',$tags)))
        ->with(['values'=>fn($v)=>$v->where('locale_id',$loc->id)->select('translation_key_id','value')])
        ->select('id','namespace','key','updated_at')->orderBy('id')->lazyById(2000);
      $out=[]; foreach($keys as $k){ $value=optional($k->values->first())->value ?? ''; $ns=$k->namespace ?? 'default'; $out[$ns][$k->key]=$value; }
      return $out;
    });
    $etag='"'.substr(sha1(json_encode($payload)),0,16).'"';
    if($r->header('If-None-Match') === $etag) return response('',304);
    
    // CDN-optimized headers
    $headers = [
      'ETag' => $etag,
      'Cache-Control' => 'public, max-age=' . config('cdn.cache.export_ttl', 300) . ', s-maxage=' . config('cdn.cache.export_smaxage', 600),
      'CDN-Cache-Control' => 'public, max-age=' . config('cdn.cache.export_smaxage', 600),
      'Vary' => 'Accept-Encoding, Authorization',
      // Do not force gzip in app; let the server/CDN handle compression
    ];
    
    return response()->json($payload, 200, $headers);
  }
}