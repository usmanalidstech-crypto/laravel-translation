<?php
namespace Database\Seeders; use Illuminate\Database\Seeder; use Illuminate\Support\Facades\DB; use Illuminate\Support\Arr; use App\Models\Locale; use App\Models\Tag;
class BulkSeeder extends Seeder {
  public function run(): void {
    $locales=['en','fr','es','de','ar']; foreach($locales as $c) Locale::firstOrCreate(['code'=>$c]);
    $tagNames=['web','mobile','desktop']; foreach($tagNames as $n) Tag::firstOrCreate(['name'=>$n]);
    $total=(int)env('SEED_TOTAL',5000); $chunks=(int)env('SEED_CHUNK',500);
    for($i=0; $i<$total; $i+=$chunks){
      $currentChunk = min($chunks, $total - $i);
      DB::transaction(function() use($i,$currentChunk){
        // Capture ID before insert
        $beforeMaxId = (int) DB::table('translation_keys')->max('id');

        // Prepare keys for this chunk
        $keys=[]; for($k=0;$k<$currentChunk;$k++){ $keys[]=['namespace'=>Arr::random(['web','mobile']),'key'=>'key_'.($i+$k),'created_at'=>now(),'updated_at'=>now()]; }

        // Insert keys and compute the inserted ID range safely
        DB::table('translation_keys')->insert($keys);
        $afterMaxId = (int) DB::table('translation_keys')->max('id');
        $keyIds = $afterMaxId > $beforeMaxId ? range($beforeMaxId + 1, $afterMaxId) : [];

        if (empty($keyIds)) return; // nothing inserted

        // Build values for all locales
        $locIds=DB::table('locales')->pluck('id','code'); $values=[];
        foreach($keyIds as $kid){ foreach($locIds as $code=>$lid){ $values[]=['translation_key_id'=>$kid,'locale_id'=>$lid,'value'=>"Value {$code} for {$kid}",'version'=>1,'created_at'=>now(),'updated_at'=>now()]; } }
        // Insert in manageable chunks to avoid packet size limits
        foreach(array_chunk($values, 2000) as $chunk){ DB::table('translation_values')->insert($chunk); }

        // Attach tags
        $tagIds=DB::table('tags')->pluck('id')->all(); $piv=[];
        foreach($keyIds as $kid){ $attach=Arr::random($tagIds, rand(1,2)); foreach((array)$attach as $tid) $piv[]=['translation_key_id'=>$kid,'tag_id'=>$tid]; }
        DB::table('key_tag')->insert($piv);
      });
    }
  }
}