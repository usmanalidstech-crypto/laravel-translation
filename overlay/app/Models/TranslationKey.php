<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class TranslationKey extends Model {
  protected $fillable=['namespace','key'];
  
  protected static function boot()
  {
    parent::boot();
    static::creating(function ($model) {
      if (empty($model->namespace)) {
        $model->namespace = 'default';
      }
    });
  }
  
  public function values(){return $this->hasMany(TranslationValue::class,'translation_key_id');}
  public function tags(){return $this->belongsToMany(Tag::class,'key_tag');}
}