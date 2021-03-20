<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Scopes\SellerScope;
use App\Transformers\SellerTransformer;

class Seller extends User
{
    use HasFactory;

    public $transformer = SellerTransformer::class;

    protected static function booted()
    {
        static::addGlobalScope(new SellerScope);
    }

    public function products() {
        return $this->hasMany('App\Models\Product');
    }
}
 