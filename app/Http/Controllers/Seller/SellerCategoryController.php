<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\ApiController;
use App\Models\Seller;
use Illuminate\Http\Request;

class SellerCategoryController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('scope:read-general')->only(['index']);

        $this->middleware('can:view,seller')->only(['index']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Seller $seller)
    {
        $categories = $seller->products()
        ->wherehas('categories')
        ->with('categories')
        ->get()
        ->pluck('categories')
        ->collapse()
        ->unique()
        ->values();

        return $this->showAll($categories);
    }
}
