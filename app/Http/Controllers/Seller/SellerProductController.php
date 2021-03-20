<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\ApiController;
use App\Models\Seller;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Transformers\ProductTransformer;
use Illuminate\Auth\Access\AuthorizationException;

class SellerProductController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('transform.input:' . ProductTransformer::class)->only(['store', 'update']);

        $this->middleware('scope:manage-products')->except(['index']);

        $this->middleware('can:view,seller')->only(['index']);

        $this->middleware('can:update-product,seller')->only(['update']);

        $this->middleware('can:delete-product,seller')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Seller $seller)
    {
        if(request()->user()->tokenCan('read-general') || request()->user()->tokenCan('manage-products'))
        {
            $products = $seller->products;

            return $this->showAll($products);
        }

        throw new AuthorizationException('Invalid Scope(s) Provided');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $seller)
    {
         //Implementing User Instance for seller as the user may not be the seller initially...Implementing User Policy.
        $this->authorize('add-product', $seller);

        $rules = [
            'name' => 'required',
            'description' => 'required',
            'quantity' => 'required|integer|min:1',
            'image' => 'required|image'
        ]; 
        
        $this->validate($request, $rules);

        $data = $request->all();

        $data['status'] = Product::AVAILABLE_PRODUCT;
        $data['image'] = $request->image->store('');
        $data['seller_id'] = $seller->id;

        $product = Product::create($data);

        return $this->showOne($product);
     }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Seller  $seller
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Seller $seller, Product $product)
    {
        $rules = [
            'quantity' => 'integer|min:1',
            'status' => 'in:'.Product::AVAILABLE_PRODUCT.','.Product::UNAVAILABLE_PRODUCT,
            'image' => 'image'
        ];

        $this->validate($request, $rules);

        $this->checkSeller($seller, $product);

        $product->fill($request->only(
            'name',
            'description',
            'quantity'
        ));

        if($request->has('status'))
        {
            $product->status = $request->status;

            if($product->isAvailable() && $product->categories->count() === 0) 
            {
                return $this->errorResponse('An active product have at least one category.', 409);
            }
        }

        if($request->hasFile('image'))
        {
            Storage::delete($product->image);

            $product->image = $request->image->store('');
        }

        if($product->isClean())
        {
            return $this->errorResponse('You need to specify different value to update', 422);
        }

        $product->save();

        return $this->showOne($product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Seller  $seller 
     * @return \Illuminate\Http\Response
     */
    public function destroy(Seller $seller, Product $product)
    {
        $this->checkSeller($seller, $product);

        $product->delete();

        Storage::delete($product->image);

        return $this->showOne($product);
    }

    public function checkSeller($seller, $product)
    {
       if($seller->id != $product->seller_id)
       {
           throw new HttpException(403,'Seller is not actual owner of the product.');
        }
    }
}
