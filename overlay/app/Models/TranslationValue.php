<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class TranslationValue extends Model {
  protected $fillable=['translation_key_id','locale_id','value','version'];
  protected $attributes=['version'=>1];
  public function key(){return $this->belongsTo(TranslationKey::class,'translation_key_id');}
  public function locale(){return $this->belongsTo(Locale::class);}
}