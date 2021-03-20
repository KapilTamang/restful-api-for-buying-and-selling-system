<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Transformers\ProductTransformer;

class Product extends Model
{
    use HasFactory, softDeletes;

    const AVAILABLE_PRODUCT = 'available';
    const UNAVAILABLE_PRODUCT = 'unavailable';

    public $transformer = ProductTransformer::class;

    protected $dates = ['deleted_at'];

    protected $hidden = ['pivot'];

    protected $fillable = [
        'name',
        'description',
        'quantity',
        'status',
        'image',
        'seller_id',
    ];

    public function isavailable() {
        return $this->status == Product::AVAILABLE_PRODUCT;
    }

    public function categories() {
        return $this->belongsToMany('App\Models\Category');
    }

    public function transactions() {
        return $this->hasMany('App\Models\Transaction');
    }

    public function seller() {
        return $this->belongsTo('App\Models\Seller');
    }

}
