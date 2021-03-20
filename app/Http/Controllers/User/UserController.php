<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
// use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use App\Transformers\UserTransformer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

class UserController extends ApiController
{
    public function __construct()
    {
        $this->middleware('client.credentials')->only(['store']);

        $this->middleware('auth:api')->except(['store']);

        $this->middleware('transform.input:' . UserTransformer::class)->only(['store', 'update']);

        $this->middleware('scope:manage-account')->only(['show', 'update']);

        $this->middleware('can:view,user')->only(['show']);
        
        $this->middleware('can:update,user')->only(['update']);

        $this->middleware('can:delete,user')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->allowedAdminAction();

        $users = User::all();
        return $this->showAll($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ];

        $this->validate($request, $rules);

        $data = $request->all();

        $data['password'] = bcrypt($request->password);
        $data['admin'] = User::REGULAR_USER;

        $user = User::create($data);

        Auth::login($user);

        $accessToken = auth()->user()->createToken('registerAuthToken', ['read-general', 'manage-products', 'purchase-product', 'manage-account'])
        ->accessToken;

        event(new Registered ($user));

        return response()->json(['message' => 'User registration successfull. Please check your email to verify.',
        'data' => $user, 'access_token' => $accessToken]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {   
        // try {
        //      $checkedUser = User::findOrFail($user->id);
        // } 
        // catch (ModelNotFoundException $exception) {
        //     $modelName = strtolower(class_basename($exception->getModel()));
        //     return $this->errorResponse("Does not exist any {$modelName} of the specified indentificator {$user} .", 404);
        // }

        return $this->showOne($user);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return $this->showOne($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {   
        $rules = [
            'email' => 'email|unique:users,email,'.$user->id,
            'password' => 'min:8|confirmed',
            'admin' => 'in:' . User::ADMIN_USER . ',' . User::REGULAR_USER,
        ];

        $this->validate($request, $rules);

        if($request->has('name')) {
            $user->name = $request->name;
        }

        if($request->has('email') && $user->email != $request->email) {
            $user->email_verified_at = null;
            // $user->sendEmailVerificationNotification();
            // Email verification is needed to initiate 
            $user->email = $request->email;
        }

        if($request->has('password')) {
            $user->password = bcrypt($request->password);
        }

        if($request->has('admin')) {
            
            $this->allowedAdminAction();

            if(!$user->hasVerifiedEmail()) {
                return $this->errorResponse('Only verified users can modify thte admin field.', 409);
            }

            $user->admin = $request->admin;
        }

        if(!$user->isDirty()) {
            return $this->errorResponse('You need to specify different value to update', 422);
        }

        $user->save();

        return $this->showOne($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */ 
    public function destroy(User $user)
    {
        $user->delete();
        return $this->showOne($user);
    }
}
