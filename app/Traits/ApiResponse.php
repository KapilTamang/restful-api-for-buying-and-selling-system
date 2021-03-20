<?php

namespace App\Traits;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

trait ApiResponse {
    
    private function successResponse($data, $code) 
    {
        return response()->json($data, $code);
    }


    protected function errorResponse($msg, $code)
    {
        return response()->json(['error' => $msg, 'code' => $code], $code);
    }


    protected function showAll(Collection $collection, $code = 200)
    {
        if($collection->isEmpty())
        {
            return $this->successResponse(['data' => $collection], $code);   
        }

        $transformer = $collection->first()->transformer;

        $collection = $this->filterData($collection, $transformer);

        $collection = $this->sortData($collection, $transformer);

        $collection = $this->paginate($collection);

        $collection = $this->transformData($collection, $transformer);

        $collection = $this->cacheResponse($collection);

        return $this->successResponse($collection, $code);
    }

 
    protected function showOne(Model $model, $code = 200) 
    {    
        $transformer = $model->transformer;

        $model = $this->transformData($model, $transformer);

        return $this->successResponse($model, $code);
    }


    protected function showMessage($message, $code = 200)
    {
        return $this->successResponse(['data' => $message], $code);
    }


    protected function filterData(Collection $collection, $transformer)
    {
        foreach(request()->query() as $key => $value)
        {
            $attribute = $transformer::originalAttribute($key);

            if (isset($attribute, $value))
            {
                $collection = $collection->where($attribute, $value);
            }

        }

        return $collection;
    }
    

    protected function sortData(Collection $collection, $transformer)
    {
        if(request()->has('sort_by'))
        {
            $attribute = $transformer::originalAttribute(request()->sort_by);

            $collection = $collection->sortBy->{$attribute};
        }

        return $collection;
    }


    protected function paginate(Collection $collection)
    {
        $rules = [
            'per_page' => 'integer|min:2|max:10',
        ];

        Validator::validate(request()->all(), $rules);

        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $perPage = 10;

        if(request()->has('per_page')) 
        {
            $perPage = request()->per_page;
        }

        $results = $collection->slice(($currentPage - 1) * $perPage, $perPage);

        $paginated = new LengthAwarePaginator($results, $collection->count(), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        $paginated->appends(request()->all());

        return $paginated;
    }


    protected function transformData($data, $transformer)
    {
        $transformation = fractal($data, new $transformer);

        return $transformation->toArray();
    }


    protected function cacheResponse($data)
    {
        $url = request()->url();

        $queryParams = request()->query();

        ksort($queryParams);

        $queryString = http_build_query($queryParams);

        $newUrl = "{$url}?{$queryString}";

        return Cache::remember($newUrl, 30, function() use ($data) {
            return $data;
        });
    }

 } 

 //TOdo
 //Filter by verified users








































 










