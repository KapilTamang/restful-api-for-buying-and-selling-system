<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\ApiController;
use App\Models\Product;
use App\Models\User;
use App\Models\Buyer;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Transformers\TransactionTransformer;

class ProductBuyerTransactionController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('transform.input:' . TransactionTransformer::class)->only(['store']);

        $this->middleware('scope:purchase-product,buyer')->only(['store']);

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Product $product, User $buyer)
    {
        //Implementing User Instance for buyer as the user may not be the buyer initially...Implementing User Policy.
        $this->authorize('purchase-product', $buyer);       

        $rules = [
            'quantity' => 'required|integer|min:1'
        ];

        $this->validate($request, $rules);

        if($product->seller_id == $buyer->id)
        {
            return $this->errorResponse('The buyer must be different from the seller.', 409);
        }
        
        if(!$buyer->hasVerifiedEmail())
        {
           return $this->errorResponse('The buyer must be a verified user.', 409);
        }

        if(!$product->seller->hasVerifiedEmail()) 
        {
            return $this->errorResponse('The seller must be a verified user.', 409);
        }

        if(!$product->isAvailable()) 
        {
            return $this->errorResponse('The product is not available', 409); 
        }

        if($product->quantity < $request->quantity)
        {
            return $this->errorResponse('The product does not have enough units for this transaction.', 409);
        }

        return DB::transaction(function() use ($request, $product, $buyer)
        {
            $product->quantity -= $request->quantity;
            $product->save();

            $transaction = Transaction::create([
                'quantity' => $request->quantity,
                'buyer_id' => $buyer->id,
                'product_id' => $product->id
            ]);

            return $this->showOne($transaction, 201);
        });

    }
}
