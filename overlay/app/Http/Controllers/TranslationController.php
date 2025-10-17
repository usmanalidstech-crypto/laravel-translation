<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\TranslationKey;
use App\Models\TranslationValue;
use App\Models\Locale;
use App\Models\Tag;
class TranslationController extends Controller {
  public function index(Request $r) {
    $q = TranslationKey::query()->with(['values.locale','tags']);
    if ($ns = $r->query('namespace')) $q->where('namespace',$ns);
    if ($key = $r->query('key')) $q->where('key','LIKE',"%{$key}%");
    if ($tag = $r->query('tag')) $q->whereHas('tags', fn($t)=>$t->where('name',$tag));
    if ($content = $r->query('content')) $q->whereHas('values', fn($v)=>$v->where('value','LIKE',"%{$content}%"));
    if ($loc = $r->query('locale')) $q->with(['values'=>fn($v)=>$v->whereHas('locale', fn($l)=>$l->where('code',$loc))]);
    return response()->json($q->orderBy('id')->cursorPaginate(50));
  }
  public function store(Request $r) {
    $data = $r->validate([
      'namespace'=>['nullable','string','max:64'],
      'key'=>['required','string','max:191'],
      'tags'=>['array'],
      'values'=>['array'],
      'values.*.locale'=>['required','string','max:10'],
      'values.*.value'=>['required','string'],
    ]);
    $key = TranslationKey::firstOrCreate(['namespace'=>$data['namespace']??'default','key'=>$data['key']]);
    if (!empty($data['tags'])) {
      $tagIds = Tag::whereIn('name',$data['tags'])->pluck('id','name')->all();
      $missing = array_diff($data['tags'], array_keys($tagIds));
      foreach ($missing as $name) $tagIds[$name]=Tag::create(['name'=>$name])->id;
      $key->tags()->sync(array_values($tagIds));
    }
    if (!empty($data['values'])) {
      $locales = Locale::whereIn('code', array_column($data['values'],'locale'))->pluck('id','code')->all();
      foreach ($data['values'] as $v) {
        $localeId = $locales[$v['locale']] ?? Locale::firstOrCreate(['code'=>$v['locale']])->id;
        TranslationValue::updateOrCreate(
          ['translation_key_id'=>$key->id,'locale_id'=>$localeId],
          ['value'=>$v['value'],'version'=>DB::raw('version+1')]
        );
      }
    }
    Cache::tags(['export'])->flush();
    return response()->json($key->load(['values.locale','tags']),201);
  }
  public function show($id){ return response()->json(TranslationKey::with(['values.locale','tags'])->findOrFail($id)); }
  public function update(Request $r,$id){
    $data=$r->validate([
      'namespace'=>['nullable','string','max:64'],
      'key'=>['nullable','string','max:191'],
      'tags'=>['array'],
      'values'=>['array'],
      'values.*.locale'=>['required_with:values','string','max:10'],
      'values.*.value'=>['required_with:values','string'],
    ]);
    $key=TranslationKey::findOrFail($id);
    if(array_key_exists('namespace',$data)) $key->namespace=$data['namespace'];
    if(array_key_exists('key',$data)) $key->key=$data['key'];
    $key->save();
    if(isset($data['tags'])){
      $tagIds=Tag::whereIn('name',$data['tags'])->pluck('id','name')->all();
      $missing=array_diff($data['tags'],array_keys($tagIds));
      foreach($missing as $name)$tagIds[$name]=Tag::create(['name'=>$name])->id;
      $key->tags()->sync(array_values($tagIds));
    }
    if(!empty($data['values'])){
      $locales=Locale::whereIn('code',array_column($data['values'],'locale'))->pluck('id','code')->all();
      forEach($data['values'] as $v){
        $localeId=$locales[$v['locale']] ?? Locale::firstOrCreate(['code'=>$v['locale']])->id;
        TranslationValue::updateOrCreate(
          ['translation_key_id'=>$key->id,'locale_id'=>$localeId],
          ['value'=>$v['value'],'version'=>DB::raw('version+1')]
        );
      }
    }
    Cache::tags(['export'])->flush();
    return response()->json($key->load(['values.locale','tags']));
  }
  public function destroy($id){
    TranslationKey::findOrFail($id)->delete();
    Cache::tags(['export'])->flush();
    return response()->json(['deleted'=>true]);
  }
}