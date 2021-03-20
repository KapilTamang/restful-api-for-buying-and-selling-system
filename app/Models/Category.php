<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Transformers\CategoryTransformer;

class Category extends Model
{
    use HasFactory, softDeletes;

    public $transformer = CategoryTransformer::class;

    protected $dates = ['deleted_at'];

    protected $hidden = ['pivot'];

    protected $fillable = [
        'name',
        'description',
    ];

    public function products() {
        return $this->belongsToMany('App\Models\Product');
    }

}
