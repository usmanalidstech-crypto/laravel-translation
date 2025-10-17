<?php
namespace App\Console\Commands; use Illuminate\Console\Command; use Illuminate\Support\Facades\DB; use Illuminate\Support\Str;
class MakeToken extends Command {
  protected $signature='tms:token {name}'; protected $description='Create API token for Translation API';
  public function handle(){ $plain=Str::random(40);
    DB::table('api_tokens')->insert(['name'=>$this->argument('name'),'token_hash'=>hash_hmac('sha256',$plain,config('app.key')),'created_at'=>now(),'updated_at'=>now()]);
    $this->info("Token (save now): {$plain}"); return 0; }
}