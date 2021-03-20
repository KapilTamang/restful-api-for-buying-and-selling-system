<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Transformers\TransactionTransformer;

class Transaction extends Model
{
    use HasFactory, softDeletes;

    public $transformer = TransactionTransformer::class;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'quantity',
        'buyer_id',
        'product_id'
    ];

    public function product() {
        return $this->belongsTo('App\Models\Product');
    }

    public function buyer() {
        return $this->belongsTo('App\Models\Buyer');
    }
}
